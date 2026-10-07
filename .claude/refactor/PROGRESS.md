# Refactor progress log

> Handoff file. Updated after every step so any new Claude Code conversation can resume.
> To resume: read this file top to bottom, then `.claude/refactor/PLAN.md`, then continue at **Next step**.

## Handoff summary (read first) — updated 2026-10-07

### Security alerts (2026-10-07) — branch `fix/security-deps` (from `developer` after PR #9/#10)

PR #9 (`refactor/p4-frontend`) and PR #10 (`developer` → `main`) are merged. GitHub Dependabot alerts were triaged with `pnpm audit` (same GitHub advisory DB; `gh` is not installed on the host):
- **Next.js alerts** (Windows RCE, AVIF image-optimizer RCE, WebSocket SSRF, Server Actions DoS, React flight RCE) and **Vitest UI** alerts: raised against next 16.0.1 / vitest 1.x and the deleted `nextjs-fe/pnpm-lock.yaml`. Current next 16.3.8 / vitest 4.1.11 are not affected → Dependabot closes them on its next scan of `main`. If one stays open, check its "patched version" against the lockfile.
- **Fixed** (`8bfacf5`): 140 advisories (1 critical, 52 high) → **1** (`braces` ≤ 3.0.3, ESLint glob only, no patched release). **Production deps: 0.** Dropped the unused `shadcn` CLI devDependency (source of the critical `proxy-addr` + express/hono/MCP SDK tree; use `pnpm dlx shadcn@latest add`), axios 1.20, next-intl 4.14, uuid 13.0.2, laravel-echo 2.5, Tiptap 3.31.4, all transitive deps refreshed within semver, `seroval` override. Lint tools held (prettier 3.8.1 exact, eslint-plugin-react-hooks 7.0.1 override: 7.1 adds React Compiler errors, mostly in the auth provider).
- CI (`993fdfd`): `pnpm audit --prod --audit-level=high` is now **blocking**; full audit stays informational. `.github/dependabot.yml`: grouped weekly npm + composer PRs, monthly actions, into `developer`.
- Backend: `composer audit` = only `firebase/php-jwt` < 7 (low) → part of the manual auth rework.
- Verified: `make verify` exit 0 (backend 598 tests, Pint, Larastan, FE lint 0 errors, tsc, Prettier, Vitest 90), `next build` OK for both apps (needs `NODE_ENV=production`; inside the dev containers `NODE_ENV=development` makes `/_global-error` prerender fail — not a real error).

### Status: paused by the user (2026-10-07) in the middle of FE6

**Main working branch: `refactor/p4-frontend`**. It contains **everything**: P0, P1, P2, P3 and P4 so far (stacked p0-p1 → p2 → p3 → p4) **plus merges of `origin/developer` and `origin/main`** — ready to push (see Branches). Nothing pushed by Claude.

Last verification (2026-10-07, on `refactor/p4-frontend`): backend **597 tests pass** (4 auth `RefreshTokenApiTest` cases excluded, see AUTH-GUIDE A13), Pint ✓, Larastan level 6 ✓, `openapi.json` current; FE tsc ✓ (both apps), ESLint 0 errors (1 warning, in the auth provider, deliberately not touched), Prettier ✓, **Vitest 90 tests, coverage 90% statements** (gate: 80/80/75/65).

### Done

| Phase | Items |
|---|---|
| P0 Security | S1 Next/React CVE, S2 secrets out of git, S3 mc binary, S5 exception handler, S7 FE error messages, S8 tests use the `testing` DB, T1 test suite repaired (450 failing → 0). S4/S6 = auth guide (manual, user) |
| P1 Tooling | F1 monorepo/lockfile/LF, F2 Pint/Larastan/Rector, F3 ESLint/Prettier/Vitest, F4 CI, F5 dead code, F6 format pass, F7 Makefile |
| P2 Upgrades | U1 Laravel 13 + PHP 8.5 + PHPUnit 12, U2 Next 16.3 Turbopack + React Compiler + Node 24 |
| P3 Backend | B1 generic CRUD core, B2 history in one place, B3 enums, B4 route split, B5 strictness (Larastan 6), B6 OpenAPI (Scramble). B7 skipped |
| P4 Frontend | FE1 tests (90, coverage gate in CI), FE2 admin layout, FE4 generated API types + query keys, FE5 `ResourceListPage` (pages 5139 → ~1060 lines). FE6 **in progress** |
| Lint | Both Next.js apps: 0 errors, warnings 25 → 1 |

### Real bugs found and fixed along the way (each in its own `fix` commit)

- Password hashes returned by the admin/user list APIs (`63933d0`) — **security**.
- Numeric fields (`status`, `rank_order`, `action`, social `id`) were strings → status badges always wrong, social edit never opened, saving an unchanged status failed (`d535475`).
- History viewer showed every record's history and read fields the API does not send; setting-links page was built for another model (create/update always failed); role wizard fallback to a field that does not exist (all in FE4).
- Fake buttons removed: bulk activate/deactivate (admin version always 422), import/export toolbar (`ebd4cd7`, `b2638b8`).
- Default list sorts pointed at nonexistent columns (`0412a1e`).
- Earlier (P0–P3): 8 backend bugs from T1, IsActive import, token_hash validation, banner N+1, etc. (see Item status).

### Remaining (in order)

1. **FE6 (in progress)** — done: 61 files moved into `src/features/{media,roles,content,history,master,management}` (commit `8021a54`), hooks renamed kebab-case. To do:
   - replace `src/components/forms/types.ts` with `ResourceFormProps<T>` (from `resource-list-page.tsx`) and delete the props nobody passes (`hideActions`, `renderActions`, `submitTriggerRef` in category/entry/entry-description forms);
   - split files > 300 lines (outside `ui/`): `layout-structure-editor` 660, `use-file-manager` 607 (two copy-pasted heavy-upload branches → one helper; tests exist), `upload-dialog` 592, `role-wizard-dialog` 520, `admin-form` 463, `file-manager-content` 394, `constant.ts` 390, `step2-permission-setup` 385, `category-form` 360, `user-form` 353, `media-file.service` 326, `api.ts` 314, `data-table.types` 310, `validation.ts` 305 (split per domain into `features/*/schemas.ts`), `file-manager.types` 303, `multipart-uploader` 302. `auth-provider` 319 = auth area, leave to the user.
   - Move tool used for the moves: rewrites every import (alias, relative, `vi.mock`) — recreate from the FE6 commit if needed.
2. **FE7** (optional, shared Tiptap package) — decided skip for now.
3. **D1** docs site: API base URL from env, Next caching + revalidate on publish, metadata, sitemap.
4. **P5 infra**: I1 Docker (pin images, non-root, healthchecks, Redis 8; also fixes the docs container `unhealthy` port mismatch, finding 17), I3 MinIO decision (keep pinned `pgsty/minio`), I4 backup script + restore test, I5 CD with SHA tags + rollback. I2 (Postgres 18) optional/skipped.
5. FE3 (auth guard in `proxy.ts`) — **blocked** on the manual auth rework.

### Needs the user (Claude cannot / must not do these)

1. **Push and PRs** (see Branches below). CI has never run on GitHub (docker job never built locally).
2. **Manual auth rework** (`laravel-api/docs/auth/AUTH-GUIDE.md`, A1–A13; includes a real security bug: a deleted admin can still refresh tokens, A13). Afterwards remove the `--exclude-filter` in `ci.yml` and `AUTH_TODO` in `Makefile`.
3. **Rotate secrets** (DB/Redis/MinIO/JWT/APP_KEY) on any real deployment; old values are in git history. Decide on history purge.
4. **Browser check while logged in** (no credentials available to Claude): list pages, create/edit/delete dialogs, history tab, setting links, role wizard, file manager upload, error toasts.
5. Decide: `media-official` bucket is public although documented as private (finding 12); history restore feature (no backend endpoint); whether bulk status / import-export features are actually wanted (they were fake, now removed).

### Branches — merged locally, ready for the user to push (2026-10-07)

`refactor/p4-frontend` now contains `origin/developer` and `origin/main` (merge commits `bd3a495`, `7719341`), so **both PRs are fast-forwards with no conflicts**. `make verify` on the merged tip: exit 0 (backend 598 tests, Pint ✓, Larastan ✓, FE lint 0 errors, tsc ✓, Prettier ✓, Vitest coverage 90% stmts).

How the conflicts were resolved:
- `origin/developer`: its 38 commits since `da659ed` are the **old-message copies** (with the AI trailer) of commits already on this branch (`git cherry`: all equivalent, none unique) → merged with `-s ours`, tree unchanged. Side effect: those 38 old commits stay in history (no force push needed).
- `origin/main`: only real change was `1e59182 add .gitignore` → kept the refactor `.gitignore` (root `pnpm-lock.yaml` must stay tracked; `**/` rules already cover main's Nuxt/build/log entries) and added `laravel-api/storage/*.key` from main.

**User steps (manual):**
1. `git push -u origin refactor/p4-frontend`
2. PR `refactor/p4-frontend` → `developer`; wait for CI (frontend, backend, docker build — first real run on GitHub; the docker job was never built locally). Merge it.
3. PR `developer` → `main`; merge it.
4. Local cleanup: **done 2026-10-07** — `refactor/p0-p1-foundation`, `refactor/p2-upgrades`, `refactor/p3-backend`, `backup/pre-msg-rewrite` deleted (all contained in `refactor/p4-frontend`). Local branches left: `main`, `refactor/p4-frontend`.
   Remote junk branches (user deletes, needs push rights):
   - fully merged into developer and main, safe: `git push origin --delete feature/Refactor-readme feature/laravel-api/create-migration feature/temp-test staging` (`staging` is not used by CI/CD; `cd.yml` deploys only on push to `developer`)
   - **not merged**, throwaway experiments (2023 Nuxt theme demo, `demo2` test): `git push origin --delete feature/nuxtjs-fe/demo feature/temp-test2` — commits are lost after this, keep them only if still wanted
   - after deleting: `git fetch --prune`
   - `refactor/p4-frontend` itself is deleted automatically after its PR is merged (`cleanup-branch.yml`); then locally `git checkout developer && git pull && git branch -d refactor/p4-frontend`.
5. Continue the refactor on a new branch from the updated `developer` (e.g. `refactor/fe6-features`), per the Git flow in CLAUDE.md.

**Warning: merging into `developer` deploys.** `cd.yml` builds images and deploys to the self-hosted server on every push to `developer`. The first merge ships Laravel 13 / PHP 8.5 / Node 24 and new migrations; make sure the server `.env` has the variables `setup-env.sh` now generates and back up the DB first.

**Branch auto-delete:** `.github/workflows/cleanup-branch.yml` deletes the head branch of a merged PR when it starts with `refactor/ feature/ fix/ hotfix/ chore/ docs/ test/` and comes from this repo. It never deletes `developer`/`main`/`staging`. Keep the repo setting "Automatically delete head branches" **off** (it would delete `developer` after the developer → main PR).

**Recommended GitHub settings (manual, free on public repos):** Settings → Rules → Rulesets → New branch ruleset for `developer` and `main`: restrict deletions, block force pushes, require a pull request, require status checks `Frontend (lint, format, typecheck, test)` and `Backend (pint, larastan, tests)` (they appear after CI has run once). Settings → Actions → General → Workflow permissions can stay "Read" — workflows request what they need via `permissions:`.

**Cost:** the repo is public → GitHub Actions minutes are free and unlimited on GitHub-hosted runners (CI, cleanup, CD image build), and the self-hosted deploy job is free too; the 2026 pricing changes only affect private repos. GHCR images of a public repo are free. If the repo is ever made private on the Free plan: 2,000 min/month and no branch rulesets.

If CI fails on GitHub: check first whether it is environment-only (secrets, runner, docker cache) — everything passes locally with `make verify`.

## Findings discovered during work (not in original plan)

1. `origin/developer` is 11 commits ahead of `main`: CI/CD already reworked (blue-green deploy, SHA tags, pnpm setup). **308 files on developer have CRLF line endings** (edited on Windows) → add `.gitattributes` + normalize to LF (part of F1).
2. **phpunit.xml `<env>` without `force="true"`** → inside `ml-php` the container env `DB_DATABASE` (main dev DB) wins over `testing`; tests using `RefreshDatabase` likely **wipe the dev DB**. Fix: `force="true"` + create `testing` DB idempotently. (new item S8)
3. `.gitignore` ignores `**/pnpm-lock.yaml` and `composer.lock` is not tracked → builds not reproducible. Lockfiles must be committed.
4. `laravel-api/.env.example` / `.env.testing` contain fixed JWT secrets; entrypoint falls back to copying `.env.example` → forgeable tokens if `.env` is missing. Blank them in `.env.example`; test-only values in `.env.testing`.
5. `docker/postgres/.env`, `docker/redis/.env` are unused by compose → delete. `docker/minio/mc` is a download cache of `create-buckets.sh` → untrack + gitignore.
6. `nextjs-docs` Docker dev stage has no lockfile → falls back to `npm install` (unpinned).
7. Admin "delete" routes are `POST {resource}/delete` (not `DELETE`). Fix `laravel-api/CLAUDE.md`.
8. CI runs `pnpm test --run` in nextjs-fe with zero test files → fails.
9. **Official MinIO images are gone** (`minio/minio`, `quay.io/minio/*`, `minio/mc` → pull denied) and `dl.min.io/.../mc` returns 410. Stack could not start. Switched to community build `pgsty/minio:RELEASE.2026-08-04T00-00-00Z` (ships `mc` + curl) for both `ml-minio` and `ml-minio-init`; overridable via `MINIO_IMAGE`. Long-term choice still open (PLAN I3).
10. `create-buckets.sh` wrote the MinIO IAM password into tracked `laravel-api/.env.example`; `setup-env.sh` wrote the Redis password into tracked `docker/redis/redis.conf`. Both leaks removed (Redis password now via `--requirepass` from env). **Leaked values are in git history → treat as compromised.**
11. `laravel-api/.env.example` had a real `APP_KEY`, AWS secret and JWT secrets → blanked.
13. **Baseline tests (2026-10-05): `developer` = 450 failed / 143 passed** (Feature suite; run in a throwaway container on a `developer` worktree with the same DB env). Causes are pre-existing test↔schema drift: tests insert `feature_mst.description` (column doesn't exist, 367 failures), `social_mgmt.name` NOT NULL (26), plus ~57 status-code expectation mismatches. Branch failure set is identical to developer's. Needs its own fix item (proposed **T1: repair test suite**) before F2/U1 can rely on tests.
14. `phpunit.xml` declares a `tests/Unit` suite but the dir didn't exist → `php artisan test` aborted immediately. Added `tests/Unit/.gitkeep`.
15. Pint baseline: most files fail style (2-space indent etc.) → expected, fixed by F6.
16. Laravel `prepareException()` wraps `AuthorizationException`/`ModelNotFoundException` in HTTP exceptions *before* render callbacks; handler now unwraps `getPrevious()` (first S5 version turned all auth 401s into 403).
17. **Docs port mismatch (pre-existing):** `nextjs-docs` `dev`/`start` scripts hard-code `-p 3457` (nginx proxies to 3457) while compose sets `PORT=${NEXTJS_DOCS_PORT_INSIDE_ENV}` (3001), maps host 3002→3001 and healthchecks 3001 → container always `unhealthy`, direct host port dead. Fix: drop `-p`, use `PORT` env everywhere (nginx upstream from env) — candidate for I1.
18. Docs Docker dev stage has no lockfile → `npm install` into named volume `ml_docs_node_modules`; volume must be removed to pick up dep changes (`docker volume rm ml_docs_node_modules`). `pnpm` in that container warns deps out of sync. Resolved by F1.
19. FE baseline: lint 25 errors (23 `no-explicit-any`, 1 `ban-ts-comment`, 1 `react-hooks/set-state-in-effect`) + 20 warnings — pre-existing; `pnpm test --run` fails with "No test files found"; docs `next lint` broken (removed in Next 16).
12. `media-official` bucket is set to anonymous public download, while `docker/minio/docs.md` says it holds private/sensitive data. Behavior kept; needs a user decision (presigned URLs).

## Item status (P0 + P1)

| ID | Status | Notes |
|---|---|---|
| S1 Next/React CVE upgrade | done (verified) | next/eslint-config-next 16.3.8, react/react-dom 19.2.8 in both apps; `nextjs-fe/pnpm-lock.yaml` + root lock regenerated (only next/react + their transitive deps changed). FE tsc ✓, docs tsc ✓ (removed now-unused `@ts-expect-error` for tiptap mismatch), FE lint = same 25 pre-existing errors as on 16.0.1, `/docs` `/login` `/admin` → 200 via nginx |
| S2 Secrets out of git | done (code) | Audit 2026-10-05: no secret files tracked; generated `.env` files all gitignored; example values are sentinels that `setup-env.sh` replaces with random secrets; `.env.testing` holds fake base64 "testing-only" values. **User TODO:** rotate DB/Redis/MinIO/JWT/APP_KEY on any real deployment (old values are in git history); decide on history purge (PLAN open decision 5) |
| S3 Remove `mc` binary | done (verified) | `ml-minio-init` completed: buckets, ILM, policy, user created; init uses mc from pinned image; aws-cli/curl/sed no longer needed |
| S4 env() → config() | moved to auth guide (manual) | |
| S5 Exception handler hardening | done (verified) | `tests/Feature/ExceptionHandlerTest.php` 5/5 pass; full suite = developer baseline; `bootstrap/app.php` rewritten: explicit 4xx code wins (keeps auth 401s), HttpException uses status code (abort(403) was 400), internal errors hidden unless `app.debug`, findOrFail class names hidden, `Messages::E0429` added. Auth middleware 401→403 = manual (guide A8) |
| S6 Auth | done (guide) | `laravel-api/docs/auth/AUTH-GUIDE.md` written: 12 findings A1–A12, roadmap A→D |
| S7 FE error messages | done (verified) | Confirmed `react-hot-toast/headless` bundles its own store → interceptor toasts were never rendered (and used raw i18n keys). Removed `api/client/error-handler.ts`; interceptor only rejects. New `getApiErrorMessage()` in `shared/utils/error-handler.ts` reads `error.messages` (string/list/field map); used by `useCrud`, `useJunctionTable`, `file-upload`. `handleBindErrors` typed to real envelope. First Vitest spec (10 tests ✓); dropped dead `setupFiles` from `vitest.config.ts`. tsc ✓, eslint on touched files ✓. Manual browser check of a server error toast still TODO (needs login) |
| S8 phpunit force testing DB | done (verified) | tests run against `testing` DB; `force="true"` + entrypoint creates `testing` DB idempotently |
| F1 Monorepo hygiene + LF | done (verified) | commit `aec169c`. Root lockfile only; Docker builds from repo root (`APP` arg); CI cache path + CD context updated. Verified: throwaway container `pnpm install --frozen-lockfile`/`typecheck`/`test` from root ✓; FE prod image builds; dev containers healthy, `/docs` `/login` `/admin` 200. `engines.node` kept `>=22` (images are Node 22; Node 24 = U2). Root `format` script → F3 (Prettier). Root `lint` fails only on pre-existing errors (F3). Docs prod image never existed (no standalone/public) → I5 |
| F2 Backend tooling | done (verified) | `pint.json` (laravel preset), Larastan 3.1 level 5 + `phpstan-baseline.neon` (631 errors, mostly Resources without `@mixin` / undefined model props), Rector 2.4 + rector-laravel (PHP 8.2 + up-to-Laravel-11 sets, dry-run only: 78 files → apply in U1), composer scripts `lint/format/analyse/rector/test/check`, `composer.lock` now tracked. Follow-up commit: Laravel 11.34.2 → ^11.44.2 (11.57.0) + PHPUnit ^11.5.50 for security advisories, which also unblocked Larastan 3.12 / PHPStan 2.2 / Rector 2.6 (no pin). Larastan findings fixed in separate commits: missing `IsActive` import in 5 history requests (valid `is_display` rejected, regression test added), dead CategoryEntryMgmt stubs, dead MediaFile repository. Tests: 442F/153P (was 450F/143P; −8 dead tests, +5 new, +5 fixed). `composer audit`: 9 → 5 advisories; remaining 4 laravel/framework fixes exist only in 12.60+/13.x → U1; firebase/php-jwt <7 → auth guide |
| T1 Repair test suite | done (verified) | 450F/143P → **4F/593P**. Remaining 4 = `RefreshTokenApiTest` (auth, manual → AUTH-GUIDE A13; T019 deleted admin can still refresh = real security bug). Test fixes: phantom columns in fixtures/factories, `GrantsApiAccess` trait (`grantAccessTo`, `loginWithAccess`), DELETE→`POST {res}/delete`, removed category-entry/parent_id features, `error.messages` envelope, rewrote 12 copy-pasted history test files. **App bugs found & fixed (separate `fix` commits):** FeatureMst phantom `description` (500), `user_mgmt_hist.address` 50<100 (migration), banner list filters ignored, history list `username` + missing relations (500), history delete on nonexistent `is_delete` (500), history store without `created_at` (500), history delete `ids.* max:20`, `executeStore` reusing injected model (bulk delete kept 1 of N history rows). Larastan baseline 630→628 |
| F3 FE tooling | done (verified) | commits `a92145c`, `e623343`, `1b8ea37`. Shared-style ESLint flat config (next core-web-vitals + typescript + prettier) in both apps, `next lint` replaced in docs, Prettier configs, Vitest 4, `typecheck`/`format`/`format:check` scripts. TS `strict` was already on. Verified 2026-10-06 after rebuilding FE containers from the current lockfile: lint 0 errors (FE 20 / docs 5 warnings), tsc ✓ both, Vitest 10/10. Note: eslint-plugin-react-hooks 7.1.1 (a newer resolve) adds ~20 React-Compiler errors (`set-state-in-effect`, `immutability`, `use-memo`) → handle in U2 with React Compiler |
| F4 CI | done (validated locally) | `.github/workflows/ci.yml` rewritten: PR → `developer`/`main` + manual dispatch, `contents: read`, concurrency. Jobs: **frontend** (pnpm from root, Node 22 until U2: install --frozen-lockfile, lint, format:check, typecheck, test, audit non-blocking), **backend** (PHP 8.3 + Postgres 16/Redis 7 services: composer install, Pint, Larastan `--error-format=github`, tests, composer audit non-blocking), **docker** (api + fe production targets build, no push, read-only gha cache). Tests exclude the 4 `RefreshTokenApiTest` auth cases (A13) → remove the exclusion after the manual auth rework. Validated: actionlint clean; frontend job simulated from `git archive HEAD` in node:22-alpine ✓; `php artisan test --exclude-filter ...` = 593 passed. **Not validated:** real GitHub run (needs user push/PR — acceptance "CI green on a PR" pending), docker job (local prod image build was declined by the user). CD (`cd.yml`) shellcheck infos SC2015/SC2086 left for I5 |
| F5 Dead code / deps | done (verified) | backend: unused ProductMgmt stack, `Http/Kernel.php`, `Tmp`, `CommonService`, `SingletonService`, `CategoryMgmt::products()`, Laravel Vite assets. FE: `@dnd-kit/*`, `@reduxjs/toolkit`, `react-redux`, `novel` (local `novel-editor` is Tiptap, not the package), `react-masonry-css`, `shadcn-ui`, `@swc/helpers`; docs: unused `react-dialog`, `react-scroll-area`, `tw-animate-css`, pinned `@next/swc-linux-x64-musl@16.0.1` (stale vs next 16.3.8). `.bak` + tracked `tsbuildinfo` removed (already gitignored). `nodejs npm` dropped from PHP dev image. Verified: fresh `--frozen-lockfile` install, typecheck, test (10 ✓), FE + docs `next build` ✓, PHP dev image builds |
| F6 Format pass | done (verified) | `3669e72`: Pint (550 PHP files) + Prettier (175 FE, 28 docs), formatting only — includes auth files (JsonWebToken etc., whitespace/import order only). Larastan baseline message updated (Pint flipped a yoda comparison). `.git-blame-ignore-revs` + local `git config blame.ignoreRevsFile`. Verified: pint --test ✓, phpstan ✓, prettier --check ✓ both, lint 0 errors, tsc ✓, Vitest 10/10, PHP 4F/593P (same 4 auth tests). Separate fix `740966f`: flaky SliderMgmtFactory link > varchar(100) |
| F7 Makefile | done (verified) | Root `Makefile` (first version was in `1fdca10`) extended: `fresh` (asks for `yes`, then `migrate:fresh --seed`), `format`/`fe-format`, `test-ci` (same auth exclusion as CI), `lint` now also runs Prettier checks, `verify` = lint + analyse + typecheck + test-ci + fe-test (mirrors CI), backend targets use composer scripts, `restart` rebuilds. `start.sh` just runs `make up`. Root CLAUDE.md, README (start/DB/test/quality sections) and `/verify` skill use `make`. Verified: `make verify` exit 0. Separate fix: flaky `RoleMstFactory` (unique `role_mst.name` collided on repeated faker words) |

## Item status (P2+) — branch `refactor/p2-upgrades` (stacked on `refactor/p0-p1-foundation`)

User delegated all P2+ items on 2026-10-06 ("toàn quyền thực hiện, không cần response lại"). Auth-dependent items stay blocked on the manual auth rework.

| ID | Status | Notes |
|---|---|---|
| U1 Laravel 13 / PHP 8.5 | done (verified) | Laravel 11.57 → **13.35** directly (11→12→13 guides: nothing applicable beyond Rector's CSRF rename; config files are published so default changes don't apply), Reverb ^1.12, Sanctum ^4.3, Tinker 3, Predis 3, **PHPUnit 12.5** (no docblock annotations in tests, so nothing silently skipped: still 593). Rector php83 + `UP_TO_LARAVEL_130_WITHOUT_ATTRIBUTES` applied (69 files: `casts()` methods, `Queueable` trait, `fake()`), auth files now in Rector `withSkip`. Pint 1.18 → 1.32 restyled 183 files (FQCN imports) in its own commit, added to `.git-blame-ignore-revs`. Larastan baseline regenerated (637): `casts()` exposed int→bool cast assignments that `$casts` hid. Docker: `PHP_VERSION` arg now actually used, PHP **8.5.11**, phpredis 6.3.0, Xdebug 3.5, Composer 2; CI on 8.5. Verified: 593 pass, Larastan ✓, Pint ✓, Rector dry-run clean, API 200 via nginx, queue + reverb up on 8.5. `composer audit`: only firebase/php-jwt (auth guide). Not verified: production image build (CI docker job) |
| U2 Next/Turbopack/React Compiler/Node 24 | done (verified) | `--webpack` + webpack polling hook removed; Turbopack for dev and build. **Turbopack `watchOptions.pollIntervalMs` did not detect edits in Docker** (probe route stayed stale), native watching does on the WSL filesystem → polling env vars removed from compose, note in `docker/CLAUDE.md` (keep the checkout off `/mnt/c`). `reactCompiler: true` + `babel-plugin-react-compiler` in both apps. Node 24 image/CI/engines, `@types/node` 24 (lockfile diff minimal). Vitest 4 was already done in F3. Verified: lint 0 errors (20/5 warnings, the react-hooks 7.1.1 errors do not appear with the locked plugin), tsc ✓ both, Vitest 10/10, FE prod image builds and serves `/login` 200 on Node 24, docs `next build` (Turbopack) ✓, dev `/login` `/admin` `/docs` 200 |
| B4 Route split + middleware aliases | done (verified) | Branch `refactor/p3-backend` (stacked on `refactor/p2-upgrades`). `routes/api.php` → docs/credential/admin groups + `routes/api/{docs,master,management,history}.php`; 28 CRUD resources + 4 junctions declared as `resource => Controller` lists. Aliases `api.response`, `db.transaction`, `auth.admin`, `auth.broadcasting` in `bootstrap/app.php` (auth middleware classes untouched). Verified: `route:list --json -v` identical incl. order and resolved middleware (145 routes), 593 tests pass, Larastan ✓ |
| B3 Enums | done (verified) | No shared trait needed: the duplicated static `getLabel()`/`toArray()` were unused outside the enums, so each enum keeps one `label()` (match on `$this`). Removed unused `FeatureStatus` (broken `getAll()`), `CategoryStatus`, `EntryStatus`, `SocialStatus`. 158 `new Enum()` → `Rule::enum()`. Enum casts for `status`/`gender` on Banner/Category/Entry/EntryDescription/Feature/Slider/Social (StatusEnum), DepartmentMst (DepartmentStatus), UserMgmt (UserStatus, Gender); Resources emit `?->value` with the same string/int casts → JSON unchanged. **Not cast:** `AdminMst` (auth reads `status`), history models, `is_*` columns (stay boolean). Note: Social/Category validate with `StatusEnum` although dedicated enums existed — kept (behavior). Larastan baseline 637 → 590. 593 tests pass |
| B1 Generic CRUD core | done (verified) | Repository interfaces (34, all 1:1) + `RepositoryServiceProvider` removed. `CrudRepository` / `SoftDeleteCrudRepository` (`fillable()` hook, `$deleteBlockedBy`), `CrudService` / `AuditedCrudService` (replaces `BaseService`). 36 entity services now = resource + history key + constructor; entity repositories keep `list()` + real differences. Controllers stay explicit (typed FormRequests are what Scramble reads in B6). **Not adopted:** `spatie/laravel-query-builder` — it would change filter/sort semantics (contract); allow-list sorting is a follow-up. Net −3000 lines. Fix found: `token_hash` unvalidated → 500 on missing (separate `fix` commit). 593 → 595 tests pass (new `ListPagingTest`) |
| B5 Strictness | done (verified) | `preventLazyLoading` + `preventSilentlyDiscardingAttributes` outside production (`preventAccessingMissingAttributes` left off: freshly created models lack DB defaults → false positives). Found: banner list N+1 on `media` (fixed, separate commit), RoleMst test fixtures with phantom `status`/`note` (incl. one line in `LoginApiTest` fixture — data only), hist `created_at` fillable. `$request->validated()` everywhere: **paging/sorting/id were never validated**, so `ListRequest` base now declares them (lenient: no `per_page` max, FE uses up to 9999); missing filter rules added. `declare(strict_types=1)` in all files except auth; implicit coercions made explicit (dates via `FormatsDates`, media ints, UUID string). Larastan **level 6** (array-shape/generics identifiers ignored), baseline 518. 595 tests pass |
| B2 History in one place | done (via B1) | `recordHistory()` now lives only in `AuditedCrudService`; no entity service calls it. A model observer/`Auditable` trait was **not** adopted: the history row is a snapshot of the *list Resource* (joined columns such as banner `media.url` as `image`), which an observer on the model cannot reproduce without changing stored history data |
| B6 OpenAPI (Scramble) | done (verified) | `dedoc/scramble` ^0.13 (new dependency: generates the spec from FormRequests/Resources, needed for FE4). UI `/api/openapi`, JSON `/api/openapi.json` (not `/docs/api`: nginx sends `/docs` to the docs site and `/api/docs/*` is the public docs API); local env only (`RestrictedDocsAccess`) — "admin-only" would need a Laravel guard, which the custom JWT auth does not provide. Cookie `access_token` security scheme, relative `/api` server. `openapi.json` committed (142 paths, 119 schemas, deterministic); `make openapi`; CI step fails when stale. `@mixin` on all Resources + `@property` for enum casts + typed list responses → each list response references its Resource schema; Larastan baseline 518 → 204. **Spec limitation:** it does not show the `{data, error}` envelope added by `GenerateResponseMiddleware` → FE wraps generated types in its own envelope type |
| FE1 FE tests | done (verified) | Specs for API client, error handler, `useApiData`, `useCrud`, `useHistory`, `useActionLock`, `useFileManager` (listing/filter/sort/selection/operations/light+heavy uploads), `MultipartUploader` (parts, retry, give-up, missing ETag), `mediaFileService`, `authService`, zod schemas, formatters, `notification`. **88 tests, `src/shared` 88% statements / 76% branches.** `vitest.config.ts` scopes coverage to `src/shared` (types excluded) with thresholds 80/80/75/65; CI and `make fe-test` run `--coverage`. Dead code removed first: unused modules, `useJunctionTable`, `calculatePartSize` + `PART_SIZE_CONFIG`. Test gotcha: mock `useTranslations` with a **stable** `t` — `useFileManager` refetches whenever `t` changes identity |
| FE4 Generated API types | done (verified) | `openapi-typescript` (new devDependency: generates TS from the Scramble spec) → `src/shared/types/openapi.d.ts` via `pnpm gen:api`; `make openapi` now regenerates spec **and** FE types (copies the spec into the FE container); CI step fails when `openapi.d.ts` is stale. Models (`types/models/*`, re-exported by `types/api.ts`) are aliases of generated Resource schemas; the two hand-written copies, unused enums in `models/index.ts` and `payloads.ts` are gone. Only refinement: `layout_structure` (free-form JSON) typed as `LayoutStructureItem[]`. `queryKeys` factory (`shared/api/query-keys.ts`) used by `useApiData`, `useCrud`, `admin-form`; test proves a mutation refetches cached lists. **Not merged into `useResource`:** forms only need mutations and selects only lists, so one hook would force opt-outs; the two hooks share the key factory instead. Still hand-written (no schema in the spec): media/file-manager types (media endpoints return no Resource) and auth types (manual auth work). **Bugs surfaced and fixed:** string `status`/`rank_order`/`action`/social `id` (backend `d535475`), history viewer (wrong filter + fields), setting-links page/form (wrong model), role wizard `apis` fallback. Verified: tsc ✓, lint 0 errors (15 warnings), 86 FE tests, coverage 87.6% stmts, backend 597 pass |
| FE5 ResourceListPage | done (verified) | `components/common/resource-list-page.tsx`: state, toolbar (advanced search, saved filters, import/export), filter panel, bulk delete, table, pagination, create/edit dialog (`form` + `dialogClassName`) or a custom `editor` (role wizard), delete confirm. Pages declare endpoint, `entity` message keys, columns, filter/search fields, `defaultSortBy` (`SORT_FIELDS`), `filtersKey`. Shared `StatusBadge`/`ImageCell` (`data-table/cells.tsx`) and `enumOptions()` (`shared/utils/enum-options.ts`). Pages 5139 → ~1060 lines; 12/15 under 80 lines, users/sliders/entry-descriptions 85–97 (more columns; no one-off abstractions added). Edit/delete icon buttons got `aria-label`s. Component test (list + sort param, create/edit dialog, delete confirm). Separate fixes: bulk (de)activate buttons removed (`ebd4cd7`), default sorts on real columns (`0412a1e`). Verified: tsc ✓, lint 0 errors (14 warnings), 90 FE tests, all `/admin/*` pages 200 in dev. **Not verified in a logged-in browser** |
| FE2 Admin layout | done (verified) | Branch `refactor/p4-frontend` (stacked on `refactor/p3-backend`). `src/app/admin/layout.tsx` renders `AdminLayout`; 17 pages lost the wrapper (fragments). tsc ✓, lint 0 errors, `/admin*` 200. Not verified in a logged-in browser (needs credentials) |

## Log

- 2026-10-05 — Claude Code setup done (CLAUDE.md files, `.claude/rules`, settings, skills `verify` + `refactor-item`, PLAN.md). Branch created. Auth flow analysed for the guide.
- 2026-10-05 — LF normalization; S3, S8 done; S2 partly; MinIO image replaced (stack was unbootable).
- 2026-10-05 — S5 handler, S6 guide, F5 backend dead code. Waiting for first `./start.sh` build (FE files untouched until it finishes so the baseline image builds).

- 2026-10-05 — Stack up. Baseline verify: developer 450F/143P; branch identical after S5 fix (unwrap previous). S5 tests added.
- 2026-10-05 — S1 done & verified (FE images rebuilt, docs node_modules volume recreated). PLAN statuses updated; T1 added as proposed.
- 2026-10-05 — S7 done & verified. `pnpm test --run` now passes (finding 8 resolved for local runs).
- 2026-10-05 — S2 audit done. **P0 complete** (S6/S4 manual via auth guide). Paused for user: commit P0? approve T1?

- 2026-10-05 — F1 done & committed (CI/CD fixed for new lockfile/context; stale anon node_modules volume needed `--renew-anon-volumes`).

- 2026-10-05 — F5 done & committed.

- 2026-10-05 — F2 done & committed (+ fix IsActive imports, dead code removal).

- 2026-10-06 — F6 done & committed (+ flaky slider factory fix).
- 2026-10-06 — Stack restarted (Docker Desktop restart). F3 verified & marked done.
- 2026-10-06 — F4 committed (CI rewrite). Green-on-PR check waits for the user to push.
- 2026-10-06 — F7 done & committed (+ flaky role factory fix). **P0 + P1 complete.**

- 2026-10-05 — Security bump (Laravel 11.57, PHPUnit 11.5), T1 done (user said "sửa và tiếp tục"; T1 approved via delegated decisions).

20. **Stale FE container deps:** FE images built before a lockfile change keep old `node_modules` in anonymous volumes → lint/test results differ from the lockfile. After any lockfile change: `docker compose up -d --build --force-recreate --renew-anon-volumes ml-nextjs ml-nextjs-docs`.

21. Factories using bare `faker->word` for unique columns cause random failures (fixed: `RoleMstFactory`, `SliderMgmtFactory`). `ApiMstFactory.name` / `FeatureMstFactory.group_name` also use bare words but have no unique constraint — fine.
22. Per-app `CLAUDE.md` files still show raw `docker exec` commands — still valid; `make` wraps them.
23. **Resources read attributes that don't exist** (kept, they are part of the JSON contract): `BannerMgmtResource.link` (no `banner_mgmt.link` column → always `""`), `UserMgmt.password`, `EntryDescriptionMgmtHist.entry_id`; history resources print `updated_at` although history tables have none.
24. **Empty dates print `01/01/1970`**: Resources format with `date(fmt, strtotime(''))` → epoch. Kept via `FormatsDates` (behavior-preserving); every history row's `updated_at` shows 1970. Candidate fix: return `''`/`null` and update the FE in the same change.
25. Social/Category/Entry validate `status` with `StatusEnum` (draft/published/archived) although `SocialStatus`/`CategoryStatus`/`EntryStatus` existed (unused, removed in B3). FE badges treat status as `IsActive` (0/1).
26. Flaky test seen once: `ListSettingLinkMgmtTest::test_se_t_ln_k_ls_t_003` (passed on 3 reruns). Watch it.
27. Paging/sorting were never validated (any value reached `paginate()`/`orderBy`); now `ListRequest` validates them leniently. Sorting still uses `Schema::hasColumn` per request (allow-list = follow-up, see rules/backend-laravel.md).
28. ~~`useHistory.restoreVersion` called a nonexistent restore route~~ — the button never rendered (`onRestore` was never passed); removed with `useHistory` in FE4.
29. `media-mgmt` keeps REST-style routes (`PUT update/{id}`, `DELETE delete/{id}` with `ids` body) unlike the other resources (`POST {res}/delete`). Consistent between FE and BE, so left as is.
30. **Security (fixed `63933d0`):** `AdminMstResource`, `UserMgmtResource` and their history Resources returned the bcrypt `password` hash in every list response. Removed; regression tests in `ListAdminMstTest` / `ListUserMgmtTest`. Backend 597 pass.
31. **History viewer was broken** (fixed in FE4): it sent `record_id` (not a validated filter → every history row of the table was listed) and read `changed_by/changed_at/old_values` that history Resources never return. Now filters by `{entity}_id`, newest first, shows action/author/date.
32. **Setting links admin page was built for another model** (`name/url/rank_order/status`); the API is `key/value` → create/update always 422. Fixed in FE4.
33. Numeric fields (`status`, `rank_order`, `action`, `gender`, `type`, social `id`) were strings in most Resources → status badges always showed the fallback, social edit from the table never opened, unchanged statuses failed `z.nativeEnum`. Fixed `d535475` (regression test in `ListCategoryMgmtTest`).
34. Throwaway `node:24-alpine` containers writing to the bind mount leave **root-owned files** (`package.json`, `pnpm-lock.yaml`, generated files). `make openapi` chowns its output; after a manual `pnpm add --lockfile-only` run `docker run --rm -v "$PWD":/repo alpine chown 1000:1000 …`.
35. `ml-redis` came up `unhealthy` once after a Docker Desktop restart (AOF load); a second `docker compose up -d` worked.
36. **Bulk activate/deactivate never worked**: 14 pages only refetched; admins sent `{id, is_active}` to the full update endpoint (always 422). Removed (`ebd4cd7`). If bulk status changes are wanted, they need a backend endpoint.
37. **Default list sorts used nonexistent columns**: `SORT_FIELDS.ORDER = 'order'` (→ backend fell back to `id`); banners/sliders showed an Order column although their tables have no `rank_order`. Fixed `0412a1e`.
38. **Import/export toolbar was fake** (export/template had no handler; import only refetched but toasted success; no API endpoints). Removed `b2638b8`.

- 2026-10-06 — Rule: no `Co-Authored-By: Claude` trailer (settings `attribution.commit: ""`, CLAUDE.md, workflow rule, local `commit-msg` hook). All 46 earlier commits rewritten without it (tree identical; old tips kept in branch `backup/pre-msg-rewrite`). **38 of them are already on `origin/developer`** → the remote still has the old messages until someone force-pushes `developer` (user decision).
- 2026-10-06 — U1 done (Laravel 13, PHP 8.5, PHPUnit 12).
- 2026-10-06 — U2 done.
- 2026-10-06 — B4 done (+ Pint fix for U1 media jobs on `refactor/p2-upgrades`, P3 rebased).
- 2026-10-06 — B3 done.
- 2026-10-06 — B1 done.
- 2026-10-06 — B5 done.
- 2026-10-06 — B2 closed (covered by B1).
- 2026-10-06 — B6 done.
- 2026-10-06 — FE2 done.
- 2026-10-07 — Stack restart needed (`make up`; Redis was slow to become healthy on first try, retry worked). FE1 done.
- 2026-10-07 — Password hash leak fixed. FE4 done (+ integer Resource fields, history viewer, setting links).
- 2026-10-07 — FE5 done (+ bulk status buttons removed, default sorts fixed).
- 2026-10-07 — Local junk branches deleted; `cleanup-branch.yml` added (auto-delete merged work branches); remote cleanup commands + GitHub settings documented.
- 2026-10-07 — Merged `origin/developer` (`-s ours`, old-message copies) and `origin/main` (`.gitignore` resolved) into `refactor/p4-frontend`; `make verify` green. Waiting for the user to push/PR.
- 2026-10-07 — PR #9/#10 merged by the user. Security alerts triaged and fixed (`fix/security-deps`).
- 2026-10-07 — Import/export fake toolbar removed, lint warnings cleared (25 → 1). FE6 started: feature folders (`8021a54`). Paused by the user; branch analysis written into the handoff summary.

## Next step

2026-10-07: security fix done on `fix/security-deps`; refactor continues stacked on it (FE6 → D1 → P5).

Paused by the user on 2026-10-07; branch merged with developer/main and ready for the user to push + PR. When resuming (after the PRs are merged, on a new branch from `developer`): finish FE6 (see "Remaining" in the handoff summary): form props → `ResourceFormProps<T>`, then split the files > 300 lines one per commit, verify (`make verify`), then D1, then P5. Before that, the user may push/merge per "Branches".
