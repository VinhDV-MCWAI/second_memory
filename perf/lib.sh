# shellcheck shell=bash
# Shared by scripts/perf-baseline.sh and perf/profile.sh: the throwaway `perf` database and the
# `php -S` API server inside ml-php. Source it from the repo root. The dev database, dev Redis DBs
# and the nginx/php-fpm stack are never touched.

PERF_DB=perf
# Another conversation may run its own php -S in ml-php: override with PERF_PORT=… if 8099 is taken
PERF_PORT=${PERF_PORT:-8099}
PERF_NETWORK=ml_network
PERF_K6_IMAGE=grafana/k6:0.57.0
# shellcheck disable=SC2034  # used by the scripts that source this file
PERF_APP=/var/www/laravel-api
# Seeded by PerfLedgerSeeder
PERF_OWNER=perf_owner
PERF_PASSWORD=perf-password

# Environment of every artisan / server process: perf database, isolated Redis DB 13 for sessions
PERF_APP_ENV=(
  -e DB_DATABASE="$PERF_DB" -e APP_DEBUG=false -e CACHE_STORE=array
  -e SESSION_DRIVER=redis -e REDIS_DB=13 -e SESSION_DOMAIN= -e SESSION_SECURE_COOKIE=false
  -e SANCTUM_STATEFUL_DOMAINS="ml-php:$PERF_PORT"
)

perf_psql() { docker exec ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d ${2:-postgres} -v ON_ERROR_STOP=1 -qc \"$1\""; }

perf_recreate_db() {
  echo "==> Recreating database '$PERF_DB' with the REQ-002 volume"
  perf_psql "DROP DATABASE IF EXISTS $PERF_DB WITH (FORCE)"
  perf_psql "CREATE DATABASE $PERF_DB"
  docker exec "${PERF_APP_ENV[@]}" ml-php php artisan migrate --force --no-interaction >/dev/null
  docker exec "${PERF_APP_ENV[@]}" ml-php php artisan db:seed --class=PerfLedgerSeeder --force --no-interaction >/dev/null
  perf_psql "ANALYZE" "$PERF_DB"
}

perf_stop_server() { docker exec ml-php pkill -f -- "-S 0.0.0.0:$PERF_PORT" >/dev/null 2>&1 || true; }

# perf_start_server <workers> <app dir in ml-php> [extra docker exec / php args...]
# Extra arguments starting with -e go to docker exec, the rest to php (e.g. -d flags).
perf_start_server() {
  local workers=$1 app=$2 env_args=() php_args=()
  shift 2
  while (($#)); do
    if [ "$1" = -e ]; then env_args+=(-e "$2"); shift 2; else php_args+=("$1"); shift; fi
  done
  echo "==> Starting the API on ml-php:$PERF_PORT ($workers workers, $app)"
  perf_stop_server
  if docker exec ml-php curl -s -o /dev/null --max-time 2 "http://localhost:$PERF_PORT/"; then
    echo "Port $PERF_PORT in ml-php is used by another process; rerun with PERF_PORT=<free port>" >&2
    return 1
  fi
  # Laravel's router script serves from the working directory, so start in public/. The dev image
  # loads Xdebug, which would dominate the timings: off. OPcache on, as under php-fpm.
  docker exec -d "${PERF_APP_ENV[@]}" "${env_args[@]}" -e PHP_CLI_SERVER_WORKERS="$workers" -e XDEBUG_MODE=off \
    -w "$app/public" ml-php \
    php -d opcache.enable_cli=1 "${php_args[@]}" -S "0.0.0.0:$PERF_PORT" \
    "$app/vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php"
  local _
  for _ in $(seq 1 30); do
    if docker exec ml-php curl -fs -o /dev/null "http://localhost:$PERF_PORT/api/public/skills"; then
      docker exec ml-php pgrep -f -- "-S 0.0.0.0:$PERF_PORT" >/dev/null && return 0
      echo "Something else answers on port $PERF_PORT; rerun with PERF_PORT=<free port>" >&2
      return 1
    fi
    sleep 1
  done
  echo "API on port $PERF_PORT did not answer 200" >&2
  return 1
}

# perf_k6 <script in perf/k6> <result name> [docker -e args...]: runs k6 in ml_network against the
# perf server; the summary export lands in perf/results/<result name>.json
perf_k6() {
  local script=$1 result=$2
  shift 2
  mkdir -p perf/results
  docker run --rm --network "$PERF_NETWORK" -u "$(id -u):$(id -g)" \
    -v "$PWD/perf/k6:/scripts:ro" -v "$PWD/perf/results:/results" \
    -e BASE_URL="http://ml-php:$PERF_PORT" -e PERF_USER="$PERF_OWNER" -e PERF_PASSWORD="$PERF_PASSWORD" "$@" \
    "$PERF_K6_IMAGE" run --quiet --summary-trend-stats="avg,min,med,p(95),p(99),max" \
    --summary-export="/results/$result.json" "/scripts/$script"
}

# Drop the perf sessions (Redis DB 13 only; dev uses 0/1, tests 14/15)
perf_flush_sessions() { docker exec ml-redis sh -c 'REDISCLI_AUTH="$REDIS_PASSWORD" redis-cli -n 13 FLUSHDB' >/dev/null; }
