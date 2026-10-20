#!/usr/bin/env bash
# Basic alerts for the dev stack: a Windows notification when a container stops or turns unhealthy,
# when a background job waits too long in the queue, or when new jobs fail. Each problem is
# announced once, and again when it is gone. Runbook: docs/runbooks/canh-bao.md
#
# Usage: scripts/health-watch.sh <check|install|uninstall|status>
#   check      run the checks once (the systemd timer calls this every minute)
#   install    install and start the systemd user timer (WSL with systemd)
#   uninstall  stop and remove the timer; the state folder is removed too
#   status     timer state and the problems seen by the last check
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR="$(dirname "$SCRIPT_DIR")"
COMPOSE_FILE="$PROJECT_DIR/docker/docker-compose.yml"
STATE_DIR="${XDG_STATE_HOME:-$HOME/.local/state}/second-memory-health"
ACTIVE_FILE="$STATE_DIR/active"        # problems seen by the last check, one "key|message" per line
FAILED_FILE="$STATE_DIR/failed-jobs"   # failed_jobs count at the last check
LOG_FILE="$STATE_DIR/alerts.log"
UNIT_NAME="second-memory-health"
UNIT_DIR="${XDG_CONFIG_HOME:-$HOME/.config}/systemd/user"
POWERSHELL="/mnt/c/Windows/System32/WindowsPowerShell/v1.0/powershell.exe"

readonly QUEUE_WAIT_LIMIT_SECONDS=300
# One-shot containers that exit when done
readonly ONE_SHOT_CONTAINERS=" ml-minio-init "

problems=()
add_problem() { problems+=("$1|$2"); }
running() { docker ps --filter "name=^$1\$" --filter status=running -q | grep -q .; }

# Windows toast through PowerShell (WSL interop); the log always gets the line
notify() {
  local title=$1 body=$2
  printf '%s  %s: %s\n' "$(date '+%F %T')" "$title" "$body" >>"$LOG_FILE"
  [ -x "$POWERSHELL" ] || return 0
  # shellcheck disable=SC2016 # $env:… is PowerShell, not bash
  HW_TITLE=$title HW_BODY=$body WSLENV=HW_TITLE:HW_BODY "$POWERSHELL" -NoProfile -NonInteractive -Command '
    [Windows.UI.Notifications.ToastNotificationManager, Windows.UI.Notifications, ContentType = WindowsRuntime] > $null
    $xml = [Windows.UI.Notifications.ToastNotificationManager]::GetTemplateContent([Windows.UI.Notifications.ToastTemplateType]::ToastText02)
    $text = $xml.GetElementsByTagName("text")
    $text.Item(0).AppendChild($xml.CreateTextNode($env:HW_TITLE)) > $null
    $text.Item(1).AppendChild($xml.CreateTextNode($env:HW_BODY)) > $null
    $app = "{1AC14E77-02E7-4E5D-B744-2EB1AE5198B7}\WindowsPowerShell\v1.0\powershell.exe"
    [Windows.UI.Notifications.ToastNotificationManager]::CreateToastNotifier($app).Show([Windows.UI.Notifications.ToastNotification]::new($xml))
  ' >/dev/null 2>&1 || echo "$(date '+%F %T')  warning: Windows notification failed" >>"$LOG_FILE"
}

check_containers() {
  local expected states name state status
  if ! states=$(docker ps -a --filter 'name=^ml-' --format '{{.Names}}|{{.State}}|{{.Status}}' 2>/dev/null); then
    add_problem docker "Docker is not reachable"
    return 1
  fi
  expected=$(docker compose -f "$COMPOSE_FILE" config --services 2>/dev/null || true)
  for name in $expected; do
    [[ $ONE_SHOT_CONTAINERS == *" $name "* ]] && continue
    grep -q "^$name|" <<<"$states" || add_problem "container:$name" "$name does not exist (make up)"
  done
  while IFS='|' read -r name state status; do
    [ -n "$name" ] || continue
    [[ $ONE_SHOT_CONTAINERS == *" $name "* ]] && continue
    if [ "$state" != running ]; then
      add_problem "container:$name" "$name is $state ($status)"
    elif [[ $status == *"(unhealthy)"* ]]; then
      add_problem "container:$name" "$name is unhealthy"
    fi
  done <<<"$states"
}

# Pending jobs sit in Redis lists "<prefix>queues:<name>", oldest first; the payload carries createdAt
check_queue_wait() {
  # shellcheck disable=SC2016 # expanded inside the container
  local redis=(docker exec ml-redis sh -c 'REDISCLI_AUTH="$REDIS_PASSWORD" exec redis-cli --raw "$@"' --)
  local key payload created now
  now=$(date +%s)
  while IFS= read -r key; do
    [[ -z $key || $key == *:notify ]] && continue
    [ "$("${redis[@]}" type "$key" 2>/dev/null)" = list ] || continue
    payload=$("${redis[@]}" lindex "$key" 0 2>/dev/null) || continue
    [[ $payload =~ \"createdAt\":([0-9]+) ]] || continue
    created=${BASH_REMATCH[1]}
    if ((now - created > QUEUE_WAIT_LIMIT_SECONDS)); then
      add_problem "queue:${key##*queues:}" "a job has waited $(((now - created) / 60)) min in queue ${key##*queues:} (is ml-queue running?)"
    fi
  done < <("${redis[@]}" --scan --pattern '*queues:*' 2>/dev/null || true)
}

# failed_jobs only grows on a new failure; one notification per increase, not a lasting problem
check_failed_jobs() {
  local count previous
  # shellcheck disable=SC2016 # expanded inside the container
  count=$(docker exec ml-postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -qAtc "SELECT count(*) FROM failed_jobs"' 2>/dev/null) || return 0
  previous=$(cat "$FAILED_FILE" 2>/dev/null || echo "$count")
  echo "$count" >"$FAILED_FILE"
  if ((count > previous)); then
    notify "Second Memory: background job failed" "$((count - previous)) new failed job(s), $count in total (php artisan queue:failed)"
  fi
}

run_check() {
  mkdir -p "$STATE_DIR"
  touch "$ACTIVE_FILE"
  if check_containers; then
    running ml-redis && check_queue_wait
    running ml-postgres && check_failed_jobs
  fi

  local current line key
  current=$(printf '%s\n' "${problems[@]}" | sed '/^$/d' | sort -u)
  # New problems: in this check, not in the last one
  while IFS= read -r line; do
    [ -n "$line" ] || continue
    key=${line%%|*}
    grep -q "^$key|" "$ACTIVE_FILE" || notify "Second Memory: problem" "${line#*|}"
  done <<<"$current"
  # Solved problems: in the last check, gone now
  while IFS= read -r line; do
    [ -n "$line" ] || continue
    key=${line%%|*}
    grep -q "^$key|" <<<"$current" || notify "Second Memory: OK again" "${key#*:} is fine again (was: ${line#*|})"
  done <"$ACTIVE_FILE"
  printf '%s\n' "$current" | sed '/^$/d' >"$ACTIVE_FILE"
}

install_timer() {
  mkdir -p "$UNIT_DIR"
  cat >"$UNIT_DIR/$UNIT_NAME.service" <<EOF
[Unit]
Description=Second Memory dev stack health check (scripts/health-watch.sh)

[Service]
Type=oneshot
ExecStart=/usr/bin/env bash $SCRIPT_DIR/health-watch.sh check
EOF
  cat >"$UNIT_DIR/$UNIT_NAME.timer" <<EOF
[Unit]
Description=Run the Second Memory health check every minute

[Timer]
OnBootSec=2min
OnUnitActiveSec=1min
AccuracySec=10s

[Install]
WantedBy=timers.target
EOF
  systemctl --user daemon-reload
  systemctl --user enable --now "$UNIT_NAME.timer"
  # Keep the user's systemd running without an open terminal, so the timer survives
  loginctl enable-linger "$USER" 2>/dev/null || echo "note: 'sudo loginctl enable-linger $USER' keeps the timer running with no terminal open"
  echo "Installed: checks every minute. Log: $LOG_FILE"
}

uninstall_timer() {
  systemctl --user disable --now "$UNIT_NAME.timer" 2>/dev/null || true
  rm -f "$UNIT_DIR/$UNIT_NAME.service" "$UNIT_DIR/$UNIT_NAME.timer"
  systemctl --user daemon-reload
  rm -rf "$STATE_DIR"
  echo "Removed the health check timer and its state."
}

show_status() {
  systemctl --user list-timers "$UNIT_NAME.timer" --no-pager 2>/dev/null || true
  echo "Problems at the last check:"
  if [ -s "$ACTIVE_FILE" ]; then sed 's/^[^|]*|/  - /' "$ACTIVE_FILE"; else echo "  none"; fi
  [ -f "$LOG_FILE" ] && { echo "Last alerts:"; tail -n 5 "$LOG_FILE" | sed 's/^/  /'; }
  return 0
}

case "${1:-}" in
  check) run_check ;;
  install) install_timer ;;
  uninstall) uninstall_timer ;;
  status) show_status ;;
  *) echo "usage: $0 <check|install|uninstall|status>" >&2; exit 2 ;;
esac
