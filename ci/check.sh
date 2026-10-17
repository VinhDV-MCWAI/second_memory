#!/usr/bin/env bash
# Every quality gate, in throwaway containers (ADR-0016). The same script runs on a dev machine
# (`make ci`), on GitHub (.github/workflows/ci.yml) and on GitLab (.gitlab-ci.yml).
# Only committed code is checked: `git archive <ref>` goes into a temp folder, so uncommitted
# work in the tree never changes the result and no vendor/ or node_modules/ lands in it.
#
# Usage: ci/check.sh [ref]   (default: HEAD)
# Needs: bash, git, Docker with Compose v2. Downloads are cached in $CI_CACHE between runs.
set -euo pipefail

cd "$(dirname "$0")/.."

ref=${1:-HEAD}
CI_SRC=$(mktemp -d)
CI_CACHE=${CI_CACHE:-${XDG_CACHE_HOME:-$HOME/.cache}/second-memory-ci}
CI_UID=$(id -u)
CI_GID=$(id -g)
export CI_SRC CI_CACHE CI_UID CI_GID
mkdir -p "$CI_CACHE/composer" "$CI_CACHE/pnpm"

compose=(docker compose -f "$CI_SRC/ci/compose.yml")
cleanup() {
  "${compose[@]}" down -v --remove-orphans >/dev/null 2>&1 || true
  rm -rf "$CI_SRC"
}
trap cleanup EXIT

git archive --format=tar "$ref" | tar -x -C "$CI_SRC"
echo "Checking $(git rev-parse --short "$ref") in $CI_SRC"

step() {
  local name=$1
  shift
  echo
  echo "==> $name"
  local start=$SECONDS
  "$@"
  echo "    ok ($((SECONDS - start))s)"
}
php() { "${compose[@]}" run --rm -T php sh -c "$1"; }
node() { "${compose[@]}" run --rm -T node sh -c "$1"; }

# Fails when a regenerated file differs from the committed one ($1 file, $2 command that regenerates it)
unchanged() {
  printf 'cp %s /tmp/committed && %s && { cmp -s %s /tmp/committed || { diff -u /tmp/committed %s | head -40; echo "%s is stale: run make openapi and commit it"; exit 1; }; }' \
    "$1" "$2" "$1" "$1" "$1"
}

step "Build CI images" "${compose[@]}" build --quiet php node

# ---------- Backend: laravel-api against Postgres + Redis ----------
step "Backend: composer install" php 'composer install --no-interaction --no-progress --prefer-dist'
step "Backend: Pint" php 'composer lint'
step "Backend: Larastan" php 'composer analyse -- --no-progress'
# Tests wrap each case in a transaction (DatabaseTransactions), so the schema and the route
# permissions must exist first, as `make migrate` / `make permissions` do on the dev testing DB
step "Backend: prepare testing DB" php 'php artisan migrate --force && php artisan permission:sync-routes'
step "Backend: tests" php 'php artisan test'
# After the tests, so the testing DB has the schema Scramble inspects
step "Backend: openapi.json up to date" php "$(unchanged openapi.json 'php artisan scramble:export')"
step "Backend: composer audit (informational)" php 'composer audit || echo "    findings above do not fail the run"'

# ---------- Frontend: nextjs-fe + public-site (pnpm workspace) ----------
step "Frontend: pnpm install" node 'pnpm install --frozen-lockfile'
step "Frontend: lint" node 'pnpm lint'
step "Frontend: format" node 'pnpm format:check'
step "Frontend: API types up to date" node \
  "$(unchanged nextjs-fe/src/shared/types/openapi.d.ts 'pnpm --filter nextjs-fe gen:api >/dev/null')"
step "Public site: API types up to date" node \
  "$(unchanged public-site/src/types/openapi.d.ts 'pnpm --filter public-site gen:api >/dev/null')"
step "Frontend: typecheck" node 'pnpm typecheck'
# Same as `make fe-test`: only nextjs-fe has unit tests (the e2e package needs the running stack).
# Coverage thresholds on src/shared live in nextjs-fe/vitest.config.ts
step "Frontend: tests" node 'pnpm --filter nextjs-fe test --run --coverage'
step "Frontend: pnpm audit (informational)" node \
  'pnpm audit --audit-level=high || echo "    findings above do not fail the run"'

# ---------- Ledger importer (tools/ledger-importer) ----------
step "Importer: ruff, mypy, pytest" sh -c \
  "docker build -q --target dev -t sm-ci-importer '$CI_SRC/tools/ledger-importer' >/dev/null && docker run --rm sm-ci-importer"

echo
echo "All checks passed."
