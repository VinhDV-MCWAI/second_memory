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

- **Module scopes:** `Master` (`*Mst` — admins only), `Management` (`*Mgmt` — media only), `History` (`*Hist` — `admin_mst_hist` only).
- Each entity has: Controller, `List/Store/Update/Delete` FormRequests, Repository, Service, Resource, Model, Factory, Feature tests under `tests/Feature/{Master,Management,History}`. Services type-hint the concrete repository (no interfaces).
- **Controllers** stay explicit (typed FormRequests, `$request->validated()` only). **List requests** extend `Http/Requests/ListRequest` (shared `id`/`page`/`per_page`/`sort_by`/`sort_order` rules) and declare `filters()`. A field without a rule never reaches the service.
- **Services:** `CrudService` (list/store/update/delete via `$resource`) or `AuditedCrudService` (also writes a `*_hist` row per create/update/delete; set `$historyForeignKey`). Entity services usually only declare those properties and a constructor.
- **Repositories:** `CrudRepository` (hard delete; history tables) or `SoftDeleteCrudRepository` (`is_delete` flag, refuses updates of deleted rows, `$deleteBlockedBy` relations). Entities implement `list()` and override `fillable()` to normalize payloads (password hashing, date formats). `BaseRepository` has `applyFilters`, `applyDateRange`, `applySorting`, `validateForeignKeys`, `checkCanDelete`.
- Soft delete = `is_delete` column + `Traits/HasSoftDelete` (`notDeleted()` scope).
- Enums: one `label()` per enum; `status`/`gender` are cast to enums on management/master models (not `AdminMst`), Resources emit `->value`.
- Outside production Eloquent throws on lazy loading and on non-fillable mass assignment (`AppServiceProvider`): eager-load what Resources read.

## HTTP contract (FE depends on it — do not break)

- Routes live in `routes/api.php` (credential + admin group) which loads `routes/api/{master,management,history}.php`; standard resources are declared in a `$resource => Controller` list. Middleware aliases (`api.response`, `db.transaction`, `auth.admin`) are in `bootstrap/app.php`; `statefulApi()` adds Sanctum's session + CSRF middleware for requests from the SPA.
- Route shape: `GET {resource}/list`, `POST {resource}/store`, `PUT {resource}/update/{id}`, `POST {resource}/delete` (body `{ ids: [] }`). Admin routes under `/api/admin`; there is no public API since RFC-001 slice 5.
- Envelope (`GenerateResponseMiddleware` + `bootstrap/app.php`): `{ "data": ..., "error": { "status": bool, "code": int, "messages": string|object|null } }`. Validation errors → 422 with field map in `error.messages`.
- Auth (ADR-0004): Sanctum SPA session. The SPA calls `GET /api/sanctum/csrf-cookie`, then `POST /api/admin/credential/login`; the `laravel_session` cookie + `X-XSRF-TOKEN` header authenticate later calls through `auth:sanctum` (guard `admin`, provider `active-admins` = not deleted and active). Roles (ADR-0005): `admin_mst.role` is `owner` or `viewer`; `AdminMiddleware` lets reads (GET/HEAD/OPTIONS) through and authorizes every other method with the `write` Gate (owner only): 401 = no session, 403 = viewer writing. The last active owner cannot be demoted, deactivated or deleted (422, `AdminMstService`). Login is throttled per user name + IP (`CommonVal::LOGIN_*`). Use `Auth::id()` for the current admin.
- Writes run inside `TransactionMiddleware` (commit on success, rollback on any error).

## Gotchas

- New Larastan errors must be fixed, not added to the baseline; regenerate it only when the baseline shrinks (`composer analyse -- --generate-baseline=phpstan-baseline.neon`).
- Tests hit a real PostgreSQL `testing` DB (see `phpunit.xml`), run inside `ml-php`. Variables the container exports (`APP_ENV`, `DB_DATABASE`, `BROADCAST_CONNECTION`) must be forced as `<server>` in `phpunit.xml`, because Laravel reads `$_SERVER` before `$_ENV`. `Tests\TestCase` sends a `Referer` so Sanctum starts a session, and resets the auth guards before each request. Log in with `Tests\Concerns\AuthenticatesAdmins` (`loginAs`, `loginAsOwner`); factories make viewers unless you call `->owner()`.
- No DB views or triggers are left (the last ones went in RFC-001 slice 8). Removed modules are dropped by `2026_10_07_*_drop_*` migrations whose `down()` replays the original ones (`App\Support\Database\ReplaysMigrations`).
- Media upload goes to MinIO via `Services/MinioService.php` with queued jobs in `Jobs/Media`; progress is broadcast over Reverb. Since RFC-001 slice 6 no FE screen calls the media API (the file manager is gone and avatar upload was never wired up); the API, `media_mgmt` and the MinIO objects are kept on purpose (REQ-001 US-3).
