#!/usr/bin/env bash
# Print codebase and runtime size metrics as a Markdown table.
# Used for before/after comparisons in release notes (REQ-001 US-5).
#
# Usage: scripts/metrics.sh [--tests] [--images]
#   --tests   also run the backend + FE test suites and report counts and wall time
#   --images  also build the production API and FE images and report their sizes
# Requires the dev stack to be running (make up). Read-only except for the
# throwaway image tags lab-metrics-api / lab-metrics-fe.
set -euo pipefail

cd "$(dirname "$0")/.."

run_tests=false
build_images=false
for arg in "$@"; do
  case "$arg" in
    --tests) run_tests=true ;;
    --images) build_images=true ;;
    *) echo "unknown option: $arg" >&2; exit 2 ;;
  esac
done

count_lines() {
  # Count lines of tracked files under $1 whose names match the regex $2.
  git ls-files "$1" | grep -E "$2" | xargs -r cat | wc -l | tr -d ' '
}

psql_value() {
  docker exec ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d \"\$POSTGRES_DB\" -At -c \"$1\""
}

row() { printf '| %s | %s |\n' "$1" "$2"; }

echo "| Metric | Value |"
echo "|---|---|"
row "Commit" "$(git rev-parse --short HEAD)"
row "Backend app/ lines (PHP)" "$(count_lines laravel-api/app '\.php$')"
row "Backend tests lines (PHP)" "$(count_lines laravel-api/tests '\.php$')"
row "Migrations" "$(git ls-files laravel-api/database/migrations | grep -c '\.php$')"
row "Admin FE src/ lines (TS/TSX)" "$(count_lines nextjs-fe/src '\.(ts|tsx)$')"
row "Docs site src/ lines (TS/TSX)" "$(count_lines nextjs-docs/src '\.(ts|tsx)$')"
row "Admin FE pages (page.tsx)" "$(git ls-files nextjs-fe/src/app | grep -c 'page\.tsx$')"
row "API routes" "$(docker exec ml-php php artisan route:list --path=api --json | python3 -c 'import json,sys; print(len(json.load(sys.stdin)))')"
row "DB tables" "$(psql_value "select count(*) from information_schema.tables where table_schema='public' and table_type='BASE TABLE'")"
row "DB views" "$(psql_value "select count(*) from information_schema.views where table_schema='public'")"
row "DB triggers" "$(psql_value "select count(distinct trigger_name) from information_schema.triggers where trigger_schema='public'")"

if $run_tests; then
  start=$(date +%s)
  be_out=$(docker exec ml-php php artisan test --compact 2>&1 | sed 's/\x1b\[[0-9;]*m//g' || true)
  be_time=$(( $(date +%s) - start ))
  row "Backend tests" "$(grep -E 'Tests:' <<<"$be_out" | sed -E 's/.*Tests: +//; s/ \(.*//' || echo '?'), ${be_time}s"
  start=$(date +%s)
  fe_out=$(docker exec ml-nextjs pnpm test --run 2>&1 | sed 's/\x1b\[[0-9;]*m//g' || true)
  fe_time=$(( $(date +%s) - start ))
  row "Admin FE tests" "$(grep -E '^ +Tests ' <<<"$fe_out" | sed -E 's/^ +Tests +//; s/ \(.*//' || echo '?'), ${fe_time}s"
fi

if $build_images; then
  docker build -q -f docker/laravel/Dockerfile --target production -t lab-metrics-api laravel-api >/dev/null
  docker build -q -f docker/nextjs/Dockerfile --target production --build-arg APP=nextjs-fe -t lab-metrics-fe . >/dev/null
  row "API production image" "$(docker image inspect lab-metrics-api --format '{{.Size}}' | awk '{printf "%.0f MB", $1/1000000}')"
  row "Admin FE production image" "$(docker image inspect lab-metrics-fe --format '{{.Size}}' | awk '{printf "%.0f MB", $1/1000000}')"
fi
