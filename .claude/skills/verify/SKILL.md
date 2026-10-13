---
name: verify
description: Run the project's quality gates (backend style, static analysis, tests; frontend lint, typecheck, tests) inside the Docker containers and report real results. Use before declaring any change done, or when the user asks to check/verify/test.
---

# Verify

All tooling runs inside containers; the host has no PHP/Node.

1. Check the stack is up: `docker ps --format '{{.Names}}'`. Need `ml-php`, `ml-postgres`, `ml-redis`, and for FE `ml-nextjs` / `ml-nextjs-docs`. If missing, run `make up` and tell the owner; never report "passed" for a check that did not run.
2. Full run: `scripts/lane.sh run make verify` (the CI steps plus `openapi-check`, which regenerates the spec and FE types and fails on a diff; CI is paused since 2026-10-08, so this is the only gate). Otherwise scope to what changed (`git status` and the task's commits), wrapping each command in `scripts/lane.sh run`. Run only the relevant blocks unless asked for everything.

## Backend (`laravel-api/` changed)
```bash
docker exec ml-php ./vendor/bin/pint --test
docker exec ml-php ./vendor/bin/phpstan analyse --memory-limit=1G
docker exec ml-php php artisan test                                  # paratest not installed: no --parallel
```

## Admin FE (`nextjs-fe/` changed)
```bash
docker exec ml-nextjs pnpm lint
docker exec ml-nextjs pnpm typecheck
docker exec ml-nextjs pnpm test --run
docker exec ml-nextjs pnpm format:check
```

## Docs FE (`nextjs-docs/` changed)
```bash
docker exec ml-nextjs-docs pnpm lint
docker exec ml-nextjs-docs pnpm typecheck
docker exec ml-nextjs-docs pnpm format:check
```

## Report
A short table: check → pass/fail/skipped (+ reason). For failures, quote the first relevant error lines and say whether they come from your change or from files you did not touch (never `git stash` to find out).
