---
name: verify
description: Run the project's quality gates (backend style, static analysis, tests; frontend lint, typecheck, tests) inside the Docker containers and report real results. Use before declaring any change done, after a refactor item, or when the user asks to check/verify/test.
---

# Verify

All tooling runs inside containers; the host has no PHP/Node.

1. Check the stack is up: `docker ps --format '{{.Names}}'`. Need `ml-php`, `ml-postgres`, `ml-redis`, and for FE `ml-nextjs` / `ml-nextjs-docs`. If missing, tell the user (suggest `./start.sh`) and stop — don't report "passed".
2. Scope to what changed (`git diff --name-only developer...HEAD` plus working tree). Run only the relevant blocks unless asked for everything.

## Backend (`laravel-api/` changed)
```bash
docker exec ml-php ./vendor/bin/pint --test
docker exec ml-php ./vendor/bin/phpstan analyse --memory-limit=1G   # skip if phpstan not installed yet
docker exec ml-php php artisan test --parallel                       # fall back to without --parallel if paratest missing
```

## Admin FE (`nextjs-fe/` changed)
```bash
docker exec ml-nextjs pnpm lint
docker exec ml-nextjs pnpm exec tsc --noEmit
docker exec ml-nextjs pnpm test --run
```

## Docs FE (`nextjs-docs/` changed)
```bash
docker exec ml-nextjs-docs pnpm lint
docker exec ml-nextjs-docs pnpm exec tsc --noEmit
```

## Report
A short table: check → pass/fail/skipped (+ reason). For failures, quote the first relevant error lines and say whether it pre-existed on `developer` (check with `git stash` only if the user agrees) or was introduced by the change.
