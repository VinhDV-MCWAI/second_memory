#!/usr/bin/env bash
# k6 baseline of the Skill Ledger read paths (P3-16). Idempotent: recreates the throwaway
# `perf` database every run, so results always start from the same REQ-002 volume.
#
# Usage: scripts/lane.sh run scripts/perf-baseline.sh [smoke|load|all]   (default: all)
#        PERF_CACHED=1 … also builds Laravel's config / route / event caches first, as the
#        production images do (P4-03); default off, like the dev stack.
# Results: perf/results/<scenario>.json (k6 summary export, not committed) + stdout.
#
# The API runs as `php -S` with several workers inside ml-php against the `perf` database —
# the dev database, dev Redis DBs and the nginx/php-fpm stack are not touched. Differences
# from production (built-in server, debug off, rate limiter neutralised by the array cache)
# are listed in docs/reports/perf/.
set -euo pipefail

cd "$(dirname "$0")/.."
# shellcheck source=perf/lib.sh
source perf/lib.sh

scenarios=("${1:-all}")
[ "${scenarios[0]}" = all ] && scenarios=(smoke load)

CACHE_DIR=/tmp/perf-baseline-cache
cache_env=()

# shellcheck disable=SC2317  # called by the EXIT trap
cleanup() {
  perf_stop_server
  docker exec ml-php rm -rf "$CACHE_DIR"
}
trap cleanup EXIT

perf_recreate_db
if [ "${PERF_CACHED:-0}" = 1 ]; then
  echo "==> Building Laravel caches in $CACHE_DIR"
  read -ra cache_env <<<"$(perf_cache_env "$CACHE_DIR")"
  perf_build_caches "$PERF_APP" "$CACHE_DIR"
fi
perf_start_server 8 "$PERF_APP" "${cache_env[@]}"

status=0
for scenario in "${scenarios[@]}"; do
  echo "==> k6 scenario: $scenario"
  perf_k6 ledger-baseline.js "$scenario" -e SCENARIO="$scenario" || status=$?
done

perf_flush_sessions

# k6 exits non-zero when a threshold is crossed; report it after cleaning up
exit "$status"
