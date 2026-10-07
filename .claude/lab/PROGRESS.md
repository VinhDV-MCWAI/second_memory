# Engineering Lab — progress / handoff

> Handoff log. Updated after every task so a new conversation can resume.
> To resume: read this file, then `docs/plan/03-backlog.md`, then continue at **Next step** with the `/lab-task` skill.

## State

| | |
|---|---|
| Active phase | P2 Slim down (P0, P1 done locally). P2-01…P2-07, P2-09 done (RFC-001 slices 1–6). Left: P2-10/11 auth (slice 7), P2-12 roles (slice 8), P2-08 audit log (slice 9), P2-13 release (slice 10) |
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
7. Before upgrading any deployed environment to v2.0.0: deploy `v1.2.0` and run `docs/runbooks/content-export.md` (the export command is gone after slice 5). `v1.2.0` has a tag but no release notes file yet (`docs/releases/v1.2.0.md`).
8. Open decision (does not block P2): remove the media API, or keep it for Skill Ledger evidence files — see RFC-001 §3 correction.

## Environment gotchas (read before running anything)

- **Containers exit 127** after a Docker Desktop / WSL restart (stale bind mounts, e.g. `pg_hba.conf`): just `make up` to recreate them.
- **`make up` fails with "ml-redis is unhealthy"**: Redis is still replaying a large AOF (`LOADING` on ping). Wait until `redis-cli ping` answers `PONG`, run `BGREWRITEAOF` to compact it, then `make up` again. Root cause (tests flushing dev Redis) fixed in `0b1a294`; tests now use Redis DBs 14/15.
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

- 2026-10-07 — Test Redis isolated (`0b1a294` fix(api)): `phpunit.xml` forces `REDIS_DB=14`, `REDIS_CACHE_DB=15` (dev uses 0/1); 24 `Redis::flushall()` → `flushdb()`. Checked: a probe key in dev DB 0 survives a full `make test`. `make verify` exit 0 (backend 377 passed, Vitest 104). Seen once, not reproduced in 4 later runs: `DeleteUserMgmtTest::delete single success` failed with a `QueryException` in the full suite (passes alone) — flaky; slice 3 deletes that module anyway, so not chased.

- 2026-10-07 — RFC-001 slice 3 (end-user management) done: `c99ce64` refactor(api)! (module, `UserStatus` enum, factory, tests incl. `UserLifecycleIntegrationTest` and `FullSystemFlowTest` scenario 1, 10 stale baseline entries, migration `2026_10_07_100002_drop_user_mgmt_tables.php`, OpenAPI: exactly 8 paths + 9 schemas removed), `175e1e1` refactor(fe) (page, form, nav, route, endpoint, enum, schema, `entities.user(s)` messages). Dev `user_mgmt` was empty before the drop. Drop migration up → down → up tested on `testing` (both tables back, `address` 100). `make verify` exit 0: backend 358 passed, Vitest 104, lint 0 errors (old `auth-provider.tsx` warning).
- Noticed: the admin dashboard (`nextjs-fe/src/app/admin/page.tsx`) shows hard-coded fake stats (`totalUsers: '1,234'`); left for later (dashboard is not in RFC-001's scope).

- 2026-10-07 — RFC-001 slice 4 (departments & policies) done: `0e69b1a` refactor(api)! (4 modules + 2 history modules, `DepartmentStatus`, factories, tests incl. `OrgStructureIntegrationTest`, `AdminMst::departments`, seeder steps, 7 lang keys, 15 stale baseline entries; migration `2026_10_07_100003_drop_department_policy_tables.php` drops 6 tables + `admin_policy_view` + trigger `after_policy_department_insert` and its function; OpenAPI: exactly 20 paths + 21 schemas removed), `f9dbf98` refactor(fe) (2 pages, 2 forms, nav, routes, 4 endpoints, enum, 2 schemas, unused `DepartmentTree*` types and `AuthUser.departments`, 22 message keys). Dev tables were all empty. up → down → up on `testing`: 6 tables, view, trigger, function all restored then dropped.
- Side findings, separate commits: `5a3c667` fix(fe) — sidebar Settings group linked to 3 pages deleted long ago (404s); `aab405e` test(fe) — `navigation.test.ts` fails when a menu link has no `page.tsx` (it caught that bug) + removed unused `findActiveMenuItem`. Needed anyway: deleting tested code dropped FE statement coverage to 79.9% (< 80% gate); now 81.18%. Watch the coverage gate in slices 5–6.
- `make verify` exit 0: backend 333 passed, Vitest 106 (20 files), lint 0 errors (old `auth-provider.tsx` warning). Docs: `laravel-api/CLAUDE.md`, `laravel-api/README.md`, `nextjs-fe/CLAUDE.md` no longer mention removed modules.

- 2026-10-07 — RFC-001 slice 5 (content CMS + public docs API) done, consumers first so every commit stays green: `a13681f` refactor(docs)! (every `/docs` URL → static "content moved" server page, HTTP 200; checked `/docs`, `/docs/a`, `/docs/a/b` through nginx, CSS loads), `c2e8ca9` refactor(fe) (3 pages, `features/content` incl. Tiptap + layout editors, 29 message keys, `HistoryRecord` now typed from `AdminMstHistResource`), `d6cbb9f` refactor(api)! (modules, history modules, `/api/docs/*`, observers, `LayoutStructureRule`, `IsDisplay`, seeders, `content:export-markdown` — decision already in the runbook: it lives in `v1.2.0`, run it before upgrading; migration `2026_10_07_100004_drop_content_cms_tables.php`; OpenAPI: exactly 28 paths + 31 schemas removed), `fb45475` build(deps) (Tiptap, drag-and-drop, uuid, radix/cva/clsx/lucide/tailwind-merge in docs; lockfile regenerated with pnpm 12.9.1 in a throwaway container; frozen install + production builds of both apps OK).
- Drop migration checked with a column snapshot (64 columns): up → down gives an identical schema, then up again. Dev content tables were empty.
- `make verify` exit 0: backend 242 passed, Vitest 98 (19 files), FE coverage 80.69% (gate 80 — slice 6 must not lower it; add tests if it does), lint 0 errors (old `auth-provider.tsx` warning).
- Found, left for P4 (Docker hardening), not in RFC-001: (1) `ml-nextjs-docs` is always `unhealthy` — the app listens on 3457 (`next dev -p 3457`, nginx upstream 3457) but compose sets `PORT`/healthcheck/port mapping to `NEXTJS_DOCS_PORT_INSIDE_ENV` (3001); already noted as item 17 in `.claude/refactor/PROGRESS.md`. (2) The docs app's production Docker stage cannot build (`output: 'standalone'` missing, no `public/`); CD never builds a docs image, so production has no docs site at all.

- 2026-10-07 — RFC-001 slice 6 (file-manager explorer) done: `066e750` refactor(fe) (`/admin/file-manager`, all of `features/media`, `useWebSocket` hook, FE media endpoints, multipart/MIME/file-size constants, 110 message keys incl. the whole `fileManager` namespace). Media API, `media_mgmt` and MinIO objects kept; `make openapi` unchanged. Finding: avatar upload was never wired up (`admin-form.tsx` shows a preview only), so RFC-001's reason for keeping the media API was wrong — correction note added to RFC-001 §3; kept anyway per REQ-001 Q3/US-3. Side commits: `62ddd1a` refactor(fe) — `ImageUpload` component was already dead before slice 5; `0790528` build(deps) — `laravel-echo`, `pusher-js` (frozen install + FE production build OK; the API still broadcasts upload progress over Reverb with no listener).
- `make verify` exit 0: backend 242 passed, Vitest 61 (16 files), FE coverage 81.23%, lint 0 errors (old `auth-provider.tsx` warning). Backlog: P2-06, P2-07, P2-09 → done.

## Next step

RFC-001 slice 7 = backlog **P2-10 then P2-11** (largest risk in P2). Do the documents first, as their own commit, before any code:

1. **P2-10 (docs only):** `PRB-001` from `docs/templates/problem-record.md` (problem: hand-written JWT + Redis permission table — cost, risks, test failures `RefreshTokenApiTest` t004/005/006/019 that `make test-ci` skips) and `ADR-0004` from `docs/templates/adr.md` (decision: Laravel Sanctum SPA cookie auth; options considered; consequences: Reverb channel auth, FE auth provider, `token_mst` goes in slice 8). Archive `laravel-api/docs/auth/AUTH-GUIDE.md` as a learning record (the owner hand-coded this auth to learn; keep the write-up, mark it historical — do not delete it). Bilingual per `docs/README.md`.
2. **P2-11 (code), after the ADR:** install Sanctum (say why — new dependency), cookie/session login for the SPA, replace `AdminMiddleware`/`BroadcastingAuthMiddleware`/`CredentialService`/`JsonWebToken`, migrate `nextjs-fe/src/providers/auth-provider.tsx` (+ the refresh-token interceptor in `src/shared/api/client`), new auth feature tests (401/403/login/logout/me), remove the AUTH_TODO exclusion from `make test-ci` once the old tests are gone. Keep the HTTP envelope. Login must keep working through nginx (`/api/admin/credential/*` paths may change → update FE in the same change, `make openapi`). Note: `admin_permission_view` is still read by login today; RBAC tables stay until slice 8.
3. Then slice 8 (P2-12 roles `owner`/`viewer`), slice 9 (P2-08 audit log), slice 10 (P2-13 metrics + `v2.0.0`).
