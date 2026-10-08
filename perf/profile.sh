#!/usr/bin/env bash
# Where does one request's time go (PERF-01)? Runs perf/k6/request-cost.js (1 user, sequential)
# against the perf API in several server variants and logs PHP / database time per request with
# perf/php/timing-prepend.php. Idempotent: recreates the throwaway `perf` database, works in
# /tmp/perf-profile inside ml-php and removes it afterwards; bootstrap/cache and the dev stack are
# not touched (Laravel caches go to /tmp through APP_*_CACHE).
#
# Usage: scripts/lane.sh run perf/profile.sh
set -euo pipefail

cd "$(dirname "$0")/.."
# shellcheck source=perf/lib.sh
source perf/lib.sh

WORK=/tmp/perf-profile
WORKERS=2
WARMUP=20

cleanup() {
  perf_stop_server
  docker exec ml-php rm -rf "$WORK"
}
trap cleanup EXIT

perf_recreate_db

docker exec ml-php rm -rf "$WORK"
docker exec ml-php mkdir -p "$WORK"
docker cp perf/php/. "ml-php:$WORK/"

echo "==> Raw PostgreSQL cost from ml-php (PDO, 100 times each)"
docker exec -e DB_DATABASE="$PERF_DB" -e XDEBUG_MODE=off ml-php php -r '
$dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s", getenv("DB_HOST"), getenv("DB_PORT") ?: 5432, getenv("DB_DATABASE"));
$t = hrtime(true);
for ($i = 0; $i < 100; $i++) { $c = new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD")); $c->query("select 1"); $c = null; }
printf("open connection + select 1: %.2f ms\n", (hrtime(true) - $t) / 1e8);
$c = new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD"));
$t = hrtime(true);
for ($i = 0; $i < 100; $i++) { $c->query("select 1"); }
printf("select 1 on an open connection: %.2f ms\n", (hrtime(true) - $t) / 1e8);'

echo "==> Copying the API code onto the container filesystem ($WORK/app)"
docker exec ml-php sh -c "mkdir -p $WORK/app && tar -C $PERF_APP --exclude=./node_modules --exclude='./storage/logs/*' -cf - . | tar -C $WORK/app -xf -"

# cache_env <name>: Laravel config / route / event caches of a variant, kept under $WORK
cache_env() {
  echo "-e APP_CONFIG_CACHE=$WORK/cache-$1/config.php -e APP_ROUTES_CACHE=$WORK/cache-$1/routes-v7.php -e APP_EVENTS_CACHE=$WORK/cache-$1/events.php"
}

# run_variant <name> <app dir> <laravel caches: yes|no> [php -d args...]
run_variant() {
  local name=$1 app=$2 caches=$3 cache_env=()
  shift 3
  echo
  echo "======== variant: $name"
  if [ "$caches" = yes ]; then
    read -ra cache_env <<<"$(cache_env "$name")"
    docker exec ml-php mkdir -p "$WORK/cache-$name"
    local command
    for command in config:cache route:cache event:cache; do
      docker exec "${PERF_APP_ENV[@]}" "${cache_env[@]}" -w "$app" ml-php php artisan "$command" >/dev/null
    done
  fi
  perf_start_server "$WORKERS" "$app" "${cache_env[@]}" -e "PERF_TIMING_LOG=$WORK/$name.log" \
    -d "auto_prepend_file=$WORK/timing-prepend.php" "$@"
  perf_k6 request-cost.js "profile-$name" -e WARMUP="$WARMUP"
  perf_stop_server
  echo "-- server side (median of the measured requests)"
  docker exec ml-php sh -c "php $WORK/timing-summary.php $WARMUP < $WORK/$name.log"
  perf_flush_sessions
}

# Persistent connections are in the code since API-02, but config:cache bakes them off (API-06):
# until that is fixed the *-cached variants run without them
run_variant mount "$PERF_APP" no
run_variant mount-cached "$PERF_APP" yes
run_variant mount-cached-novalidate "$PERF_APP" yes -d opcache.validate_timestamps=0
run_variant copy-cached "$WORK/app" yes
