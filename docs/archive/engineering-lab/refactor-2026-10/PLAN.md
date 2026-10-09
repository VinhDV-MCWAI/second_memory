# Refactor plan — Second Memory (2026-10)

> **FROZEN 2026-10-07.** Superseded by the Engineering Lab roadmap ([docs/plan/02-roadmap.md](../../docs/plan/02-roadmap.md), [ADR-0001](../../docs/adr/0001-engineering-lab-direction.md)). Open items were closed or moved (see the Status column). Do not start items from this file; use `docs/plan/03-backlog.md`.

Status values: `proposed` → `approved` → `in-progress` → `done` | `skipped` | `blocked: <reason>`.
Workflow: `/refactor-item <ID…>`. Only `approved` items may be started.

## Target stack (verified 2026-10-05)

| Area | Current | Target | Why |
|---|---|---|---|
| PHP | 8.3 | **8.5** | Current stable; Laravel 13 supports 8.3–8.5 |
| Laravel | 11.34 (pinned) | **13.x** | 11 is out of security support (ended 2026-03); 13 released 2026-03-17 |
| Reverb | `@beta` | stable 1.x | No beta in prod |
| PHP tests | PHPUnit 11 | PHPUnit 12 *or* Pest 4 (option) | Pest 4: cleaner syntax, sharding, browser tests |
| PHP quality | Pint only | Pint + **Larastan 3** + **Rector** (rector-laravel) | Static analysis + automated upgrades |
| API docs/types | hand-written TS types | **Scramble** (OpenAPI) → **openapi-typescript** | One source of truth for FE types |
| Next.js | 16.0.1 (webpack) | **16.3.x LTS**, Turbopack | 16.0.1 is affected by CVE-2025-55182 |
| React | 19.2.0 | latest 19.2.x + **React Compiler** | CVE fix; auto-memoization |
| Node | 22 (Docker), 20 (CI) | **24 LTS** | 20 is EOL; align everything |
| FE tests | Vitest 1, 0 tests | **Vitest 4** + Testing Library + MSW 2 | |
| Lint | ESLint 9 (fe), broken `next lint` (docs) | Shared ESLint flat config + Prettier | `next lint` was removed in Next 16 |
| PostgreSQL | 16 | **18** (optional) | Async I/O, supported longer |
| Redis | 7 | **8** | Current stable |
| Object storage | `minio/minio:latest` | pinned MinIO **or** S3-compatible alternative (option) | MinIO community edition is in maintenance mode |
| Compose | `docker-compose` v1 CLI | `docker compose` v2 | v1 is deprecated |

---

## P0 — Security & correctness (do first)

| ID | Item | Done when | Depends | Status |
|---|---|---|---|---|
| S9 | Dependabot alerts: patch vulnerable npm deps, blocking prod audit in CI, Dependabot config | `pnpm audit --prod` clean | – | done (`fix/security-deps`: 140 → 1 dev-only advisory, prod 0) |
| S1 | Upgrade `next` → 16.3.x, `react`/`react-dom` → latest 19.2.x in both apps (CVE-2025-55182, critical RSC RCE) | Lockfile shows patched versions; both apps build | – | done (`refactor/p0-p1-foundation`: next 16.3.8, react 19.2.8) |
| S2 | Remove tracked secrets (`docker/postgres/.env`, `laravel-api/.env.testing` if it holds secrets) → `.env.example`; **rotate DB password**. History purge with `git filter-repo` = separate decision | `git ls-files` shows no secret files; setup-env.sh generates them | – | done in code (`refactor/p0-p1-foundation`); manual: rotate secrets, history purge = open decision 5 |
| S3 | Remove 30 MB `docker/minio/mc` binary; use `minio/mc` image in `ml-minio-init` | Binary gone; bucket init still works | – | done (`refactor/p0-p1-foundation`: mc from pinned `pgsty/minio` image) |
| S4 | `env()` at runtime (AdminMiddleware etc.) → `config()` | `grep -rn "env(" app/` empty; works with `config:cache` | – | moved to roadmap P2 (removed together with the custom auth) |
| S5 | Harden exception handler: hide internal messages in prod, correct 401 vs 403, log all 5xx | Feature tests for 401/403/500 envelope | – | done (`refactor/p0-p1-foundation`: `ExceptionHandlerTest`) |
| S6 | Replace hand-rolled JWT. **Option a:** `firebase/php-jwt` (minimal change). **Option b (recommended):** Sanctum SPA cookie auth, drop custom JWT + refresh endpoint, keep Redis permission cache | Auth tests green; FE login/refresh flow works | S4 | moved to roadmap P2 (P2-10/P2-11: Sanctum, documented transition) |
| S8 | `phpunit.xml` env `force="true"` + idempotent `testing` DB creation (tests were wiping the dev DB) | Tests run against `testing` DB | – | done (`refactor/p0-p1-foundation`) |
| T1 | Repair test suite: tests drift from schema (`feature_mst.description`, `social_mgmt.name`, status expectations) → 450 failing on `developer` | Feature suite green | – | done (`refactor/p0-p1-foundation`: 593 pass; 4 auth refresh tests left for the manual auth work, see AUTH-GUIDE A13) |
| S7 | FE never shows server error messages (`useCrud`/`useJunctionTable` read `data.message`, backend sends `error.messages`) → use `error-handler.ts` everywhere | Unit test for error extraction; manual check | – | done (`refactor/p0-p1-foundation`: `getApiErrorMessage` + Vitest spec) |

## P1 — Foundation & tooling (makes later refactors safe)

| ID | Item | Done when | Depends | Status |
|---|---|---|---|---|
| F1 | Monorepo hygiene: one root `pnpm-lock.yaml` (remove `nextjs-fe/pnpm-lock.yaml`, `laravel-api/package-lock.json`, fix `.gitignore`), `packageManager` + corepack, `engines.node >=24`, root scripts (`lint`, `typecheck`, `test`, `format`), root `.editorconfig`; drop `laravel-api` from pnpm workspace if its Vite assets are unused | `pnpm -r lint/typecheck/test` work from root | – | done (`refactor/p0-p1-foundation`; lint green after F3) |
| F2 | Backend tooling: `pint.json` (PSR-12/laravel preset), Larastan 3 (start level 5 + baseline), Rector with rector-laravel, `composer` scripts `lint`, `analyse`, `test` | Commands run clean (with baseline) | – | done (`refactor/p0-p1-foundation`; Larastan 3.1 + 631-error baseline, Rector dry-run only, pint style debt → F6) |
| F3 | FE tooling: shared ESLint flat config + Prettier for both apps, TS strict, Vitest 4 config; replace `next lint` in docs | Lint + tsc pass in both apps | F1 | done (`refactor/p0-p1-foundation`: lint 0 errors, tsc ✓, Vitest 4) |
| F4 | Rewrite CI: jobs `frontend` (pnpm, Node 24) and `backend` (PHP 8.x + Postgres/Redis services: pint, larastan, tests), Docker build check; trigger on PR → `developer`/`main`; least-privilege permissions | CI green on a PR | F1–F3 | done (validated locally; green-on-PR pending push) |
| F5 | Remove dead code: `Http/Kernel.php`, `Utilities/Tmp.php`, `CommonService`, `SingletonService`, `CategoryMgmt::products()` (class doesn't exist), `.bak` files, `tsconfig.tsbuildinfo`; unused deps `@reduxjs/toolkit`, `react-redux`, `novel`, `@dnd-kit/*`, `react-masonry-css`, `shadcn-ui`, `@swc/helpers` | Build + tests green | – | done (`refactor/p0-p1-foundation`) |
| F6 | One-time format pass (Pint + Prettier) in a dedicated commit; add its SHA to `.git-blame-ignore-revs` | No style diffs remain | F2, F3 | done (`refactor/p0-p1-foundation`: `3669e72`, in `.git-blame-ignore-revs`) |
| F7 | Task runner `Makefile` (`make up/down/test/lint/fresh/backup`) wrapping docker compose; `start.sh` delegates to it | README/CLAUDE.md commands use `make` | – | done |

## P2 — Framework upgrades

| ID | Item | Done when | Depends | Status |
|---|---|---|---|---|
| U1 | PHP 8.5 image; Laravel 11 → 12 → 13 (Rector sets + upgrade guides), unpin exact versions to `^`, Reverb stable, Sanctum latest; PHPUnit 12 **or** convert to Pest 4 (option) | All tests green on 13 | F2, F4 | done (`refactor/p2-upgrades`: Laravel 13.35, PHP 8.5.11, PHPUnit 12.5, 593 pass) |
| U2 | Next 16.3 with Turbopack (drop `--webpack`; keep polling for Docker via env), enable React Compiler, Vitest 4, Node 24 images | Both apps build & run in Docker | S1, F3 | done (`refactor/p2-upgrades`: Turbopack + React Compiler, Node 24) |

## P3 — Backend architecture

| ID | Item | Done when | Depends | Status |
|---|---|---|---|---|
| B1 | **Generic CRUD core**: `BaseCrudController` / `BaseCrudService` / `BaseRepository` with list/store/update/delete; entities declare only model, resource, filters, rules. Consider `spatie/laravel-query-builder` for allow-listed filter/sort/include (replaces `applyFilters`/`applySorting`/`Schema::hasColumn`). Contract unchanged | ~60% fewer per-entity files; all feature tests green | U1 | done (`refactor/p3-backend`) |
| B2 | History via model trait/observer (`Auditable`) instead of manual `recordHistory()` in each service | History tests green; no `recordHistory` calls in services | B1 | done (covered by B1 `AuditedCrudService`) |
| B3 | Enums: shared `HasLabel` trait, native enum casts on models, `Rule::enum` in requests | No duplicated `getLabel()` | U1 | done (`refactor/p3-backend`) |
| B4 | Routing: split `routes/api.php` into `routes/api/{master,management,history,docs}.php`, middleware aliases in `bootstrap/app.php` | `route:list` identical before/after (diff) | – | done (`refactor/p3-backend`: route:list identical) |
| B5 | `Model::shouldBeStrict()` in non-prod, `$request->validated()` everywhere, `declare(strict_types=1)` everywhere | Larastan level raised to 6+ | F2 | done (`refactor/p3-backend`: Larastan level 6) |
| B6 | OpenAPI via **Scramble** at `/docs/api` (admin-only), export spec in CI | Spec generated; consumed by FE4 | B1 | done (`refactor/p3-backend`: `laravel-api/openapi.json`) |
| B7 | *(optional, large)* Custom `is_delete` → Laravel `SoftDeletes` (`deleted_at`) with data migration | Migration reversible; tests green | B1 | skipped (decision 3: no real need; data migration risk) |

## P4 — Frontend architecture

| ID | Item | Done when | Depends | Status |
|---|---|---|---|---|
| FE1 | Tests first: Vitest specs for api client, error-handler, `useApiData`, `useCrud` (MSW) | Coverage on `src/shared` ≥ 70% | F3 | done (`refactor/p4-frontend`: 88 tests, `src/shared` 88% stmts / 76% branches, thresholds enforced in CI) |
| FE2 | `app/admin/layout.tsx` hosts `AdminLayout` (remove per-page wrapping in 48 places) | No page imports `AdminLayout` | – | done (`refactor/p4-frontend`) |
| FE3 | Auth guard in `proxy.ts` (Next 16) for `/admin/*` | Unauthenticated → redirect without flash | S6 | moved to roadmap P2; done in P2-11 (`148af31`) |
| FE4 | Generated API types (openapi-typescript) replace hand-written `types/api.ts`; query-key factory; merge `useApiData`/`useCrud` into `useResource(resource)` | No hand-written API model types | B6, FE1 | done (`refactor/p4-frontend`: generated `openapi.d.ts`, `queryKeys`; `useApiData`/`useCrud` kept separate, see PROGRESS) |
| FE5 | Config-driven `<ResourceListPage>`: 17 near-identical 300+ line pages → column/filter/form config per entity | Each entity page < 80 lines; behavior same | FE1, FE2, FE4 | done (`refactor/p4-frontend`: 12/15 pages < 80 lines, rest 85–97; 5139 → ~1060 lines) |
| FE6 | Feature-based folders: `src/features/<domain>/{api,components,schemas,hooks}`; split oversized files (`layout-structure-editor`, `constant.ts`, `types/api.ts`) | No file > 300 lines outside `ui/` | FE5 | done for kept code (`chore/p0-baseline`); remaining splits are in features removed by roadmap P2 |
| FE7 | *(optional)* Shared workspace package `packages/editor` (Tiptap extensions + schema) used by both admin editor and docs renderer | Single source of extensions | U2 | skipped (Tiptap editor is removed in roadmap P2) |
| D1 | Docs site: API base URL from env (no hard-coded `ml-nginx`), drop custom request dedup, use Next caching (`revalidate`/Cache Components + tag revalidation on publish), `generateMetadata`, sitemap | Pages cached; content updates visible after publish | U2 | superseded (docs site becomes the portfolio site, roadmap P3/P10) |

## P5 — Infrastructure

| ID | Item | Done when | Depends | Status |
|---|---|---|---|---|
| I1 | Docker: pin all images, non-root, healthchecks + `depends_on: service_healthy`, compose v2, Redis 8 | `docker compose up` healthy from clean | – | moved to roadmap P4 |
| I2 | *(optional)* PostgreSQL 16 → 18 via dump/restore script | Data verified after restore | I1, I4 | skipped |
| I3 | Object storage decision: pin last MinIO release **or** move to another S3-compatible server; app code stays on S3 API | Upload/download/multipart tests pass | I1 | moved to roadmap P4 (keep pinned `pgsty/minio` until then) |
| I4 | Backup: `set -euo pipefail`, retention, scheduled run, automated restore test | Restore into scratch DB succeeds | – | moved to roadmap P7 (DR drills) |
| I5 | CD: images tagged by commit SHA (+ `latest` alias), docs image too, deploy health check + one-command rollback | Rollback tested once | F4 | moved to roadmap P5 |

## Open decisions

User delegated P2+ on 2026-10-06 ("toàn quyền thực hiện"); Claude's defaults below can be overridden any time.

1. S6: firebase/php-jwt (a) or Sanctum SPA cookie auth (b)? — **user** (manual auth work).
2. U1: keep PHPUnit or migrate to Pest 4? — **decided: PHPUnit 12** (no rewrite of 593 tests for syntax only).
3. B7, FE7, I2: do the optional large items? — **decided: skipped for now** (data migration / new package / DB major upgrade carry risk without a real need).
4. I3: keep MinIO (pinned) or migrate? — **decided: keep S3 API, pinned `pgsty/minio` community build** (see I3).
5. S2: also purge secrets from git history (rewrites history, needs force-push)? — **user**.
