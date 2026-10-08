# laravel-api

REST API for Second Memory. Conventions: `.claude/rules/backend-laravel.md` (auto-loaded for PHP files). Legacy convention doc: `docs/CodingConvention.md`.

## Commands (run from repo root)

```bash
docker exec ml-php composer check                        # lint + analyse + test
docker exec ml-php composer test -- --filter=AdminMst
docker exec ml-php composer lint                         # pint --test (format: composer format)
docker exec ml-php composer analyse                      # Larastan level 6 + phpstan-baseline.neon
docker exec ml-php composer rector                       # Rector dry run (auth files are skipped)
docker exec ml-php php artisan route:list --path=api/admin
docker exec ml-php php artisan migrate:fresh --seed      # DEV ONLY, destroys data
```

## Architecture

`Route → Middleware → Controller → FormRequest → Service → Repository → Model`

- **Module scopes:** `Master` (`*Mst` — admins only), `Audit` (`audit_log`, read-only list; ADR-0006), `Ledger` (Skill Ledger, RFC-002: singular table names, ISO dates `LedgerConst::DATE_FORMAT`, sort allow-lists via `BaseRepository::allowedSort`, case-insensitive names via `Rules\UniqueIgnoringCase`).
- Each entity has: Controller, `List/Store/Update/Delete` FormRequests, Repository, Service, Resource, Model, Factory, Feature tests under `tests/Feature/{Master,Audit,Auth}`. Services type-hint the concrete repository (no interfaces).
- **Controllers** stay explicit (typed FormRequests, `$request->validated()` only). **List requests** extend `Http/Requests/ListRequest` (shared `id`/`page`/`per_page`/`sort_by`/`sort_order` rules) and declare `filters()`. A field without a rule never reaches the service.
- **Services:** `CrudService` (list/store/update/delete via `$resource`) or `AuditedCrudService` (also writes an `audit_log` row per create/update/delete through `AuditLogger`; set `$auditableType`, e.g. `admin`; updates log only the changed fields, secrets never). Entity services usually only declare those properties and a constructor.
- **Repositories:** `CrudRepository` (hard delete) or `SoftDeleteCrudRepository` (`is_delete` flag, refuses updates of deleted rows, `$deleteBlockedBy` relations). Entities implement `list()` and override `fillable()` to normalize payloads (password hashing, date formats). `BaseRepository` has `applyFilters`, `applyDateRange`, `applySorting`, `validateForeignKeys`, `checkCanDelete`.
- Soft delete = `is_delete` column + `Traits/HasSoftDelete` (`notDeleted()` scope).
- Enums: one `label()` per enum; `status`/`gender` are cast to enums on models (not `AdminMst`), Resources emit `->value`.
- Outside production Eloquent throws on lazy loading and on non-fillable mass assignment (`AppServiceProvider`): eager-load what Resources read.

## HTTP contract (FE depends on it — do not break)

- Routes live in `routes/api.php` (credential + admin group) which loads `routes/api/{master,ledger}.php` and the `audit-log/list` route; standard resources are declared in a `$resource => Controller` list. Middleware aliases (`api.response`, `db.transaction`, `auth.admin`) are in `bootstrap/app.php`; `statefulApi()` adds Sanctum's session + CSRF middleware for requests from the SPA.
- Route shape: `GET {resource}/list`, `POST {resource}/store`, `PUT {resource}/update/{id}`, `POST {resource}/delete` (body `{ ids: [] }`). Admin routes under `/api/admin`; there is no public API since RFC-001 slice 5.
- Envelope (`GenerateResponseMiddleware` + `bootstrap/app.php`): `{ "data": ..., "error": { "status": bool, "code": int, "messages": string|object|null } }`. Validation errors → 422 with field map in `error.messages`.
- Auth (ADR-0004): Sanctum SPA session. The SPA calls `GET /api/sanctum/csrf-cookie`, then `POST /api/admin/credential/login`; the `laravel_session` cookie + `X-XSRF-TOKEN` header authenticate later calls through `auth:sanctum` (guard `admin`, provider `active-admins` = not deleted and active). Roles (ADR-0005): `admin_mst.role` is `owner` or `viewer`; `AdminMiddleware` lets reads (GET/HEAD/OPTIONS) through and authorizes every other method with the `write` Gate (owner only): 401 = no session, 403 = viewer writing. The last active owner cannot be demoted, deactivated or deleted (422, `AdminMstService`). Login is throttled per user name + IP (`CommonVal::LOGIN_*`). Use `Auth::id()` for the current admin.
- Skill Ledger rules: `skill/store` creates the first `skill_level` row; levels change only through `skill-level/store` (no update/delete route), which refreshes `skill.current_level` (newest by `changed_on`, then id — a backdated entry does not win) and marks reached open goals `achieved` (`SkillLevelService`). The slug is set once on create.
- Writes run inside `TransactionMiddleware` (commit on success, rollback on any error), except the credential routes: a failed login's `login_failed` audit row must survive the 401.

## Gotchas

- New Larastan errors must be fixed, not added to the baseline; regenerate it only when the baseline shrinks (`composer analyse -- --generate-baseline=phpstan-baseline.neon`).
- Tests hit a real PostgreSQL `testing` DB (see `phpunit.xml`), run inside `ml-php`. Variables the container exports (`APP_ENV`, `DB_DATABASE`, `BROADCAST_CONNECTION`) must be forced as `<server>` in `phpunit.xml`, because Laravel reads `$_SERVER` before `$_ENV`. `Tests\TestCase` sends a `Referer` so Sanctum starts a session, and resets the auth guards before each request. Log in with `Tests\Concerns\AuthenticatesAdmins` (`loginAs`, `loginAsOwner`); factories make viewers unless you call `->owner()`.
- **Contract check on every request** (P3-09): `TestCase::call()` validates each `/api` response against `openapi.json` (route, method, status, body). After changing a response, run `make openapi` before the tests, or they fail on the stale spec; a new status code needs a test-visible reason (a 403/404/429 is added by `App\OpenApi\ResponseEnvelope` from the route's middleware and parameters).
- No DB views or triggers are left (the last ones went in RFC-001 slice 8). Removed modules are dropped by `2026_10_07_*_drop_*` migrations whose `down()` replays the original ones (`App\Support\Database\ReplaysMigrations`).
- No media API since ADR-0007 (RFC-002 slice 1): the `media_mgmt` table and the MinIO objects are kept as data only, no code reads them. Nothing queues jobs or broadcasts any more, so `ml-queue` / `ml-reverb` are idle (removal is a P4 item).
