#!/usr/bin/env bash
# Coordinates parallel Claude conversations ("lanes") that share one branch and one working tree.
# Board: .claude/lab/BOARD.md. Rules: .claude/rules/parallel-lanes.md.
#
#   scripts/lane.sh status                         every lane, busy or free, and its tasks
#   scripts/lane.sh next [lane]                    tasks that can be claimed now
#   scripts/lane.sh claim <id>                     todo -> doing (fails if taken, lane busy or deps open)
#   scripts/lane.sh done <id> "<note>"             doing -> done; mirrors backlog, logs PROGRESS, commits them
#   scripts/lane.sh block <id> "<reason>"          doing -> blocked: reason (frees the lane)
#   scripts/lane.sh release <id>                   back to todo (abandoned work)
#   scripts/lane.sh add <lane> <id> "<task>" "<docs>" "<deps>"   new row at the end of a lane's table
#   scripts/lane.sh lock <path> | unlock <path>    exclusive edit of a shared file (Makefile, backlog, ...)
#   scripts/lane.sh run <cmd...>                   run a Docker-backed command (tests, verify) one lane at a time
#   scripts/lane.sh commit "<message>" <paths...>  stage and commit only these paths
set -euo pipefail

ROOT="$(git rev-parse --show-toplevel)"
cd "$ROOT"
BOARD=".claude/lab/BOARD.md"
BACKLOG="docs/plan/03-backlog.md"
PROGRESS=".claude/lab/PROGRESS.md"
LOCK_DIR="$(git rev-parse --git-common-dir)/lane-locks"
LOCK_WAIT_SECONDS=300
GIT_LOCK="git-index"
DOCKER_LOCK="$LOCK_DIR/docker.flock"
mkdir -p "$LOCK_DIR"

die() { echo "lane: $*" >&2; exit 1; }
today() { date '+%F'; }

# --- locks (mkdir is atomic; a lock outlives the process so a conversation can hold it across tool calls)
HELD=()
lock_dir() { echo "$LOCK_DIR/$(echo "$1" | tr '/' '_')"; }
acquire() {
    local dir waited=0
    dir="$(lock_dir "$1")"
    until mkdir "$dir" 2>/dev/null; do
        ((waited >= LOCK_WAIT_SECONDS)) && die "'$1' still locked after ${LOCK_WAIT_SECONDS}s by: $(cat "$dir/info" 2>/dev/null). If that conversation is gone: scripts/lane.sh unlock $1"
        ((waited == 0)) && echo "lane: waiting for lock '$1' ($(cat "$dir/info" 2>/dev/null))" >&2
        sleep 1
        waited=$((waited + 1))
    done
    echo "$(date '+%F %T') pid $PPID" >"$dir/info"
}
release_held() { local name; for name in "${HELD[@]+"${HELD[@]}"}"; do rm -rf "$(lock_dir "$name")"; done; }
hold() { acquire "$1"; HELD+=("$1"); }
trap release_held EXIT

# --- board parsing: one line per task "lane<TAB>id<TAB>deps<TAB>status"
rows() {
    awk '
        function trim(s) { gsub(/^[ \t]+|[ \t]+$/, "", s); return s }
        /^## Lane `/ { match($0, /`[^`]+`/); lane = substr($0, RSTART + 1, RLENGTH - 2); next }
        /^## / { lane = ""; next }
        lane != "" && /^\| [A-Z0-9]+-[A-Za-z0-9]+ \|/ {
            split($0, c, "|")
            print lane "\t" trim(c[2]) "\t" trim(c[5]) "\t" trim(c[6])
        }' "$BOARD"
}
field() { rows | awk -F'\t' -v id="$1" -v f="$2" '$2 == id { print $f }'; }
lane_busy() { rows | awk -F'\t' -v lane="$1" '$1 == lane && $4 ~ /^doing/ { found = 1 } END { exit !found }'; }
deps_open() {
    local deps="$1" dep open=()
    [[ "$deps" == "–" || "$deps" == "-" || -z "$deps" ]] && return 0
    IFS=',' read -ra list <<<"$deps"
    for dep in "${list[@]}"; do
        dep="$(echo "$dep" | xargs)"
        [[ "$(field "$dep" 4)" == done* ]] || open+=("$dep")
    done
    echo "${open[*]+"${open[*]}"}"
}
claimable() {
    local lane id deps status
    while IFS=$'\t' read -r lane id deps status; do
        [[ "$status" == todo ]] || continue
        [[ -n "${1:-}" && "$lane" != "$1" ]] && continue
        lane_busy "$lane" && continue
        [[ -z "$(deps_open "$deps")" ]] && printf '%-9s %s\n' "$lane" "$id"
    done < <(rows)
}

# Replace the last cell of the table row whose first cell is <id>.
set_cell() {
    local file="$1" id="$2" value="$3" tmp
    tmp="$(mktemp)"
    awk -v id="$id" -v value="$value" '
        function trim(s) { gsub(/^[ \t]+|[ \t]+$/, "", s); return s }
        /^\|/ {
            n = split($0, c, "|")
            if (trim(c[2]) == id) {
                line = "|"
                for (i = 2; i < n - 1; i++) line = line c[i] "|"
                print line " " value " |"
                next
            }
        }
        { print }' "$file" >"$tmp"
    cat "$tmp" >"$file"
    rm -f "$tmp"
}
in_table() { grep -qE "^\| $2 \|" "$1"; }

commit_paths() {
    local message="$1" attempt
    shift
    hold "$GIT_LOCK"
    git add -A -- "$@"
    if git diff --cached --quiet -- "$@"; then
        echo "lane: nothing to commit in $*"
        return 0
    fi
    for attempt in 1 2 3 4 5; do
        git commit -q -m "$message" -- "$@" && { git log --oneline -1; return 0; }
        echo "lane: commit failed (attempt $attempt), retrying" >&2
        sleep 2
    done
    die "commit failed: $message"
}

cmd="${1:-status}"
shift || true
case "$cmd" in
status)
    rows | while IFS=$'\t' read -r lane id deps status; do
        if [[ "$lane" != "${current:-}" ]]; then
            current="$lane"
            if lane_busy "$lane"; then echo; echo "[$lane] busy"; else echo; echo "[$lane] free"; fi
        fi
        printf '  %-7s %-40.40s deps: %s\n' "$id" "$status" "$deps"
    done
    echo
    echo "Claimable now:"
    claimable | sed 's/^/  /'
    ;;
next)
    claimable "${1:-}"
    ;;
claim)
    id="${1:?usage: claim <id>}"
    hold "$BOARD"
    lane="$(field "$id" 1)"
    [[ -n "$lane" ]] || die "$id is not on the board"
    status="$(field "$id" 4)"
    [[ "$status" == todo ]] || die "$id is '$status', not todo"
    lane_busy "$lane" && die "lane '$lane' already has a task in progress"
    open="$(deps_open "$(field "$id" 3)")"
    [[ -z "$open" ]] || die "$id waits for: $open"
    set_cell "$BOARD" "$id" "doing since $(date '+%F %H:%M')"
    if in_table "$BACKLOG" "${id%[a-z]}"; then hold "$BACKLOG"; set_cell "$BACKLOG" "${id%[a-z]}" "doing"; fi
    echo "Work only in lane '$lane' paths; when finished: scripts/lane.sh done $id \"<commit + one-line result>\""
    echo "claimed $id (lane $lane)"
    ;;
done)
    id="${1:?usage: done <id> \"<note>\"}"
    note="${2:?usage: done <id> \"<note>\"}"
    hold "$BOARD"
    lane="$(field "$id" 1)"
    [[ "$(field "$id" 4)" == doing* ]] || die "$id is not doing"
    set_cell "$BOARD" "$id" "done $(today): $note"
    files=("$BOARD")
    # A split task (P3-14a, P3-14b, ...) closes its backlog row (P3-14) when its last part is done.
    base="${id%[a-z]}"
    if in_table "$BACKLOG" "$base"; then
        hold "$BACKLOG"
        if [[ "$base" == "$id" ]]; then
            set_cell "$BACKLOG" "$id" "done ($note)"
        elif rows | awk -F'\t' -v base="$base" 'index($2, base) == 1 && $4 !~ /^done/ { open = 1 } END { exit open }'; then
            set_cell "$BACKLOG" "$base" "done (last part $id: $note)"
        else
            set_cell "$BACKLOG" "$base" "doing ($id done)"
        fi
        files+=("$BACKLOG")
    fi
    hold "$PROGRESS"
    tmp="$(mktemp)"
    awk -v line="- $(today) — [lane $lane] $id done: $note" '
        /^## Next step/ && !done { print line; print ""; done = 1 }
        { print }' "$PROGRESS" >"$tmp"
    cat "$tmp" >"$PROGRESS"
    rm -f "$tmp"
    files+=("$PROGRESS")
    commit_paths "docs(lab): $id done" "${files[@]}"
    ;;
block)
    id="${1:?usage: block <id> \"<reason>\"}"
    hold "$BOARD"
    [[ "$(field "$id" 4)" == doing* ]] || die "$id is not doing"
    set_cell "$BOARD" "$id" "blocked: ${2:?reason required}"
    echo "blocked $id"
    ;;
release)
    id="${1:?usage: release <id>}"
    hold "$BOARD"
    [[ -n "$(field "$id" 1)" ]] || die "$id is not on the board"
    set_cell "$BOARD" "$id" "todo"
    if in_table "$BACKLOG" "$id"; then hold "$BACKLOG"; set_cell "$BACKLOG" "$id" "todo"; fi
    echo "released $id"
    ;;
add)
    [[ $# -eq 5 ]] || die 'usage: add <lane> <id> "<task>" "<docs>" "<deps>"'
    lane="$1" id="$2"
    hold "$BOARD"
    [[ -z "$(field "$id" 1)" ]] || die "$id already exists"
    grep -q "^## Lane \`$lane\`" "$BOARD" || die "no lane '$lane'"
    tmp="$(mktemp)"
    awk -v lane="## Lane \`$lane\`" -v row="| $id | $3 | $4 | $5 | todo |" '
        { lines[NR] = $0 }
        index($0, lane) == 1 { inside = 1; next }
        /^## / { inside = 0 }
        inside && /^\|/ { last = NR }
        END { for (i = 1; i <= NR; i++) { print lines[i]; if (i == last) print row } }' "$BOARD" >"$tmp"
    cat "$tmp" >"$BOARD"
    rm -f "$tmp"
    echo "added $id to lane $lane"
    ;;
lock)
    acquire "${1:?usage: lock <path>}"
    echo "locked $1 — run: scripts/lane.sh unlock $1"
    ;;
unlock)
    rm -rf "$(lock_dir "${1:?usage: unlock <path>}")"
    echo "unlocked $1"
    ;;
run)
    [[ $# -gt 0 ]] || die "usage: run <cmd...>"
    exec 9>"$DOCKER_LOCK"
    flock -n 9 || { echo "lane: another lane is running Docker checks, waiting..." >&2; flock 9; }
    "$@"
    ;;
commit)
    [[ $# -ge 2 ]] || die 'usage: commit "<message>" <paths...>'
    message="$1"
    shift
    commit_paths "$message" "$@"
    ;;
*)
    sed -n '4,15p' "$0"
    exit 1
    ;;
esac
