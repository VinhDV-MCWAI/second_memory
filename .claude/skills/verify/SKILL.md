---
name: verify
description: Run the project's quality gates (backend style, static analysis, tests; frontend lint, typecheck, tests) inside the Docker containers and report real results. Use before declaring any change done, after a refactor item, or when the user asks to check/verify/test.
---

# Verify

All tooling runs inside containers; the host has no PHP/Node.

1. Check the stack is up: `docker ps --format '{{.Names}}'`. Need `ml-php`, `ml-postgres`, `ml-redis`, and for FE `ml-nextjs` / `ml-nextjs-docs`. If missing, tell the user (suggest `make up`) and stop — don't report "passed".
2. Full run: `make verify` (same steps as CI; backend tests skip the 4 known `RefreshTokenApiTest` auth failures). Otherwise scope to what changed (`git diff --name-only developer...HEAD` plus working tree). Run only the relevant blocks unless asked for everything.

## Backend (`laravel-api/` changed)
```bash
docker exec ml-php ./vendor/bin/pint --test
docker exec ml-php ./vendor/bin/phpstan analyse --memory-limit=1G
docker exec ml-php php artisan test                                  # paratest not installed: no --parallel; baseline = 4 known auth failures (AUTH-GUIDE A13)
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
A short table: check → pass/fail/skipped (+ reason). For failures, quote the first relevant error lines and say whether it pre-existed on `developer` (check with `git stash` only if the user agrees) or was introduced by the change.
