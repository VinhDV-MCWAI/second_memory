#!/usr/bin/env bash
# k6 baseline of the Skill Ledger read paths (P3-16). Idempotent: recreates the throwaway
# `perf` database every run, so results always start from the same REQ-002 volume.
#
# Usage: scripts/perf-baseline.sh [smoke|load|all]   (default: all)
# Results: perf/results/<scenario>.json (k6 summary export, not committed) + stdout.
#
# The API runs as `php -S` with several workers inside ml-php against the `perf` database —
# the dev database, dev Redis DBs and the nginx/php-fpm stack are not touched. Differences
# from production (built-in server, debug off, rate limiter neutralised by the array cache)
# are listed in docs/reports/perf/.
set -euo pipefail

cd "$(dirname "$0")/.."

scenarios=("${1:-all}")
[ "${scenarios[0]}" = all ] && scenarios=(smoke load)

DB=perf
PORT=8099
WORKERS=8
NETWORK=ml_network
K6_IMAGE=grafana/k6:0.57.0
OWNER=perf_owner
PASSWORD=perf-password

# Environment of every artisan / server process: perf database, isolated Redis DB 13 for sessions
APP_ENV_VARS=(
  -e DB_DATABASE="$DB" -e APP_DEBUG=false -e CACHE_STORE=array
  -e SESSION_DRIVER=redis -e REDIS_DB=13 -e SESSION_DOMAIN= -e SESSION_SECURE_COOKIE=false
  -e SANCTUM_STATEFUL_DOMAINS="ml-php:$PORT"
)

psql() { docker exec ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d postgres -v ON_ERROR_STOP=1 -qc \"$1\""; }

stop_server() { docker exec ml-php pkill -f -- "-S 0.0.0.0:$PORT" >/dev/null 2>&1 || true; }
trap stop_server EXIT

echo "==> Recreating database '$DB' with the REQ-002 volume"
psql "DROP DATABASE IF EXISTS $DB WITH (FORCE)"
psql "CREATE DATABASE $DB"
docker exec "${APP_ENV_VARS[@]}" ml-php php artisan migrate --force --no-interaction >/dev/null
docker exec "${APP_ENV_VARS[@]}" ml-php php artisan db:seed --class=PerfLedgerSeeder --force --no-interaction >/dev/null
docker exec ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d $DB -qc 'ANALYZE'"

echo "==> Starting the API on ml-php:$PORT ($WORKERS workers)"
stop_server
# Laravel's router script serves from the working directory, so start in public/. The dev image
# loads Xdebug, which would dominate the timings: off. OPcache on, as under php-fpm.
docker exec -d "${APP_ENV_VARS[@]}" -e PHP_CLI_SERVER_WORKERS="$WORKERS" -e XDEBUG_MODE=off \
  -w /var/www/laravel-api/public ml-php \
  php -d opcache.enable_cli=1 -S "0.0.0.0:$PORT" ../vendor/laravel/framework/src/Illuminate/Foundation/resources/server.php
ready=false
for _ in $(seq 1 30); do
  if docker exec ml-php curl -fs -o /dev/null "http://localhost:$PORT/api/public/skills"; then ready=true; break; fi
  sleep 1
done
$ready || { echo "API on port $PORT did not answer 200" >&2; exit 1; }

mkdir -p perf/results
for scenario in "${scenarios[@]}"; do
  echo "==> k6 scenario: $scenario"
  docker run --rm --network "$NETWORK" -u "$(id -u):$(id -g)" \
    -v "$PWD/perf/k6:/scripts:ro" -v "$PWD/perf/results:/results" \
    -e BASE_URL="http://ml-php:$PORT" -e SCENARIO="$scenario" -e PERF_USER="$OWNER" -e PERF_PASSWORD="$PASSWORD" \
    "$K6_IMAGE" run --quiet --summary-trend-stats="avg,min,med,p(95),p(99),max" \
    --summary-export="/results/$scenario.json" /scripts/ledger-baseline.js
done

# Drop the perf sessions (Redis DB 13 only; dev uses 0/1, tests 14/15)
docker exec ml-redis sh -c 'REDISCLI_AUTH="$REDIS_PASSWORD" redis-cli -n 13 FLUSHDB' >/dev/null
