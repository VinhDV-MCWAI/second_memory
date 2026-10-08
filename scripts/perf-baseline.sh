#!/usr/bin/env bash
# k6 baseline of the Skill Ledger read paths (P3-16). Idempotent: recreates the throwaway
# `perf` database every run, so results always start from the same REQ-002 volume.
#
# Usage: scripts/lane.sh run scripts/perf-baseline.sh [smoke|load|all]   (default: all)
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

trap perf_stop_server EXIT

perf_recreate_db
perf_start_server 8 "$PERF_APP"

status=0
for scenario in "${scenarios[@]}"; do
  echo "==> k6 scenario: $scenario"
  perf_k6 ledger-baseline.js "$scenario" -e SCENARIO="$scenario" || status=$?
done

perf_flush_sessions

# k6 exits non-zero when a threshold is crossed; report it after cleaning up
exit "$status"
