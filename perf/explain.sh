#!/usr/bin/env bash
# Plans of the statements behind the hot read endpoints (DB-01). Idempotent: recreates the
# throwaway `perf` database, enables auto_explain (ANALYZE, BUFFERS) on that database only, sends
# one warm-up and one logged round of perf/k6/explain-requests.js, and writes the plans logged
# during the second round to perf/results/explain.log. Then runs perf/sql/search-plans.sql
# (EXPLAIN ANALYZE of the search statements, rolled back) into perf/results/search-plans.txt.
# Results are not committed; dev data is not touched.
#
# Usage: scripts/lane.sh run perf/explain.sh
set -euo pipefail

cd "$(dirname "$0")/.."
# shellcheck source=perf/lib.sh
source perf/lib.sh

OUT=perf/results/explain.log
trap perf_stop_server EXIT

perf_recreate_db
for setting in "session_preload_libraries = 'auto_explain'" "auto_explain.log_min_duration = 0" \
  "auto_explain.log_analyze = on" "auto_explain.log_buffers = on" "auto_explain.log_timing = on"; do
  perf_psql "ALTER DATABASE $PERF_DB SET $setting"
done

perf_start_server 1 "$PERF_APP"
echo "==> Warm-up round (fills shared buffers; not logged)"
perf_k6 explain-requests.js explain-warmup >/dev/null
sleep 1
since=$(date -u +%Y-%m-%dT%H:%M:%S.%NZ)
echo "==> Logged round"
perf_k6 explain-requests.js explain >/dev/null
sleep 1

mkdir -p perf/results
# One backend per request (no persistent connections), so the [pid] in each line identifies the request
docker logs --since "$since" ml-postgres 2>&1 | grep -v -E 'statement: |^\s*$' >"$OUT"
plans=$(grep -c 'Query Text:' "$OUT" || true)
echo "==> $plans plans in $OUT"
[ "$plans" -gt 0 ] || { echo "No plans logged: did the k6 round reach the API?" >&2; exit 1; }
perf_flush_sessions

perf_psql "ALTER DATABASE $PERF_DB RESET ALL"
echo "==> Search plans"
docker exec -i ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d $PERF_DB -X -q" \
  <perf/sql/search-plans.sql >perf/results/search-plans.txt
echo "==> perf/results/search-plans.txt"
