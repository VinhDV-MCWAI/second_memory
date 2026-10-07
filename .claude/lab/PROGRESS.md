# Engineering Lab — progress / handoff

> Handoff log. Updated after every task so a new conversation can resume.
> To resume: read this file, then `docs/plan/03-backlog.md`, then continue at **Next step** with the `/lab-task` skill.

## State

| | |
|---|---|
| Active phase | P2 Slim down (P0, P1 done locally) |
| Working branch | `refactor/p2-slim-down` (from `docs/p1-handbook`); `docs/p1-handbook` (stacked on `chore/p0-baseline`, tag `v1.0.0`); `chore/p0-baseline` (from `refactor/fe6-features` ← `fix/security-deps` ← `developer`); local only, nothing pushed |
| Old refactor | Frozen (`.claude/refactor/PLAN.md`, `PROGRESS.md`) |
| Owner defaults | 8–10 h/week, backend role, §4 remove list accepted, Obsidian vault private (see analysis §9) |

## Needs the owner (Claude cannot do these)

1. Push `chore/p0-baseline`, open PR → `developer`, then `developer` → `main` (merging into `developer` deploys via `cd.yml`).
2. Push tag `v1.0.0` after the merge (`git push origin v1.0.0`) and create the GitHub release from `docs/releases/v1.0.0.md`.
3. GitHub board and labels (P0-06): see `docs/plan/github-setup.md`.
4. Remote branch cleanup (P0-09): commands in `.claude/refactor/PROGRESS.md` → "Branches".
5. Still open from the refactor: rotate secrets on any real deployment; browser check while logged in.
6. When ready: push `refactor/p2-slim-down` (stacked on `docs/p1-handbook`); PRs only after the branches below it are merged.

## Environment gotchas (read before running anything)

- **Containers exit 127** after a Docker Desktop / WSL restart (stale bind mounts, e.g. `pg_hba.conf`): just `make up` to recreate them.
- **`make up` fails with "ml-redis is unhealthy"**: Redis is still replaying a large AOF (`LOADING` on ping). Wait until `redis-cli ping` answers `PONG`, run `BGREWRITEAOF` to compact it, then `make up` again. Root cause is the test Redis bug in Next step 1.
- **`make openapi` exit 137** right after start-up: transient, rerun it.
- **Dev DB is off-limits for rollbacks** (owner's rule). Test migrations on the `testing` DB: `docker exec -e DB_DATABASE=testing ml-php php artisan migrate|migrate:rollback --step=1 --force` (no config cache, so the override works; confirm with `tinker --execute 'echo DB::connection()->getDatabaseName();'`).
- After removing a module: drop its stale entries from `laravel-api/phpstan-baseline.neon` (don't regenerate the whole baseline) and grep FE tests for fixtures that used the removed names.

## Log

- 2026-10-07 — Plan written and accepted (`44b9c89`). P0-01: `fix/security-deps` + `refactor/fe6-features` gathered into `chore/p0-baseline`; the FE6 WIP tip typechecks, lint 0 errors, Vitest 106 ✓.

- 2026-10-07 — P0 done locally: as-is architecture, root docs archived, old plan frozen (open items mapped), `refactor-item` skill → `lab-task`, ADR-0002, GitHub setup guide, v1.0.0 release notes. `make verify` exit 0 (Pint ✓, Larastan ✓, backend 598 tests, FE lint 0 errors, tsc ✓, Vitest 106). Tag `v1.0.0` (local).

- 2026-10-07 — P1-01…P1-13 done: handbook (10 chapters), templates (REQ, RFC, INC, postmortem, runbook, daily, weekly, retro), GitHub issue forms + PR template + CODEOWNERS, skills `simulate-po` / `simulate-qa` / `simulate-ops` (sealed briefs in gitignored `.claude/sim/sealed/`).

- 2026-10-07 — P1-14 dry run: REQ-001 Ready (7 clarification questions; hidden needs surfaced: content export → new task P2-05b, keep MinIO files, "moved" page for /docs). Daily + weekly report, retro P0–P1, `v1.1.0` notes + local tag. Dev DB has no content/admin rows → export tested with factories.

- 2026-10-07 — P2-02…P2-05b done on `refactor/p2-slim-down`: metrics script (`7ab434a`), `content:export-markdown` command (`c785085`, RFC slice 1), RFC-001 with impact analysis, ERD, slices, before-metrics + ADR-0003 (`84278a7`).

- 2026-10-07 — RFC-001 slice 2 (remove sliders, banners, setting links, socials) **started, uncommitted, not verified**: API controllers/requests/resources/models/repos/services/factories/tests deleted, routes edited, migration `2026_10_07_100001_drop_site_decoration_tables.php`, `app/Support/`, `BulkDeleteHistoryTest` moved to `Master/AdminMst`; FE pages/forms deleted, navigation/endpoints/enums/types/validation/messages trimmed. Backlog P2-06/07/09 set to `doing`.

- 2026-10-07 — Slice 2 committed as **WIP, not verified** (Docker stack went down: `ml-php`, `ml-postgres`, `ml-nginx`, `ml-redis` exited 127): `6113376` refactor(api)! (124 files, −12k lines; drop migration ran once on dev DB), `70b3f96` refactor(fe). The owner rejected running `migrate:rollback` on the dev DB → test `down()` on a scratch / `testing` DB instead.

- 2026-10-07 — Slice 2 **verified**. Stack was down after a Docker Desktop restart (stale bind mounts, exit 127); `make up` then stalled on Redis replaying an 18 MB AOF → compacted with `BGREWRITEAOF`. Commits: `ef13b73` OpenAPI regenerated (exactly the 32 removed paths + schemas), `9979148` prettier, `8cfd093` PHPStan (22 stale baseline entries, `ReplaysMigrations` typing), `ae383ad` FE test fixture. `make verify` exit 0: Pint ✓, Larastan ✓, backend 377 passed (was 598; removed modules' tests), FE lint 0 errors (1 old warning in `auth-provider.tsx`), tsc ✓ both apps, Vitest 104. Drop migration up → down → up tested on the `testing` DB (all 8 tables + `banner_mgmt.media_id` restored, then dropped). Backlog P2-06/07/09 stay `doing` (slices 3–6 left).

- Found, not fixed yet (pre-existing bug, needs its own `fix` commit): backend tests call `Redis::flushdb()` / `flushall()` (`tests/TestCase.php`, auth + integration tests) on the **dev** Redis — `phpunit.xml` does not point Redis at a separate DB. Every test run wipes dev Redis and bloats its AOF.

## Next step

1. `fix(api)`: isolate test Redis (e.g. `REDIS_DB` / `REDIS_CACHE_DB` overrides in `phpunit.xml`; check `config/database.php` for the key names) so tests never flush the dev Redis; prefer `flushdb` over `flushall` in tests.
2. RFC-001 slice 3: remove end-user management with the removal pattern in RFC-001 §5 (FE → API → tests → drop migration whose `down()` uses `ReplaysMigrations` → `make openapi` → `make verify` → test `down()` on the `testing` DB via `docker exec -e DB_DATABASE=testing ml-php php artisan ...`).
3. Then slices 4–6 (departments/policies, content CMS + docs "moved" page, file-manager UI).
