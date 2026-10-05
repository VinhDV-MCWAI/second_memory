# laravel-api

REST API for Second Memory. Conventions: `.claude/rules/backend-laravel.md` (auto-loaded for PHP files). Legacy convention doc: `docs/CodingConvention.md`.

## Commands (run from repo root)

```bash
docker exec ml-php php artisan test                      # all tests
docker exec ml-php php artisan test --filter=CategoryMgmt
docker exec ml-php ./vendor/bin/pint                     # format
docker exec ml-php php artisan route:list --path=api/admin
docker exec ml-php php artisan migrate:fresh --seed      # DEV ONLY, destroys data
```

## Architecture

`Route → Middleware → Controller → FormRequest → Service → Repository (Interface) → Model`

- **Module scopes:** `Master` (`*Mst` — admins, roles, departments, features, APIs, tokens: RBAC), `Management` (`*Mgmt` — categories, entries, banners, sliders, media, …: content), `History` (`*Hist` — audit rows per entity).
- Each entity has: Controller, `List/Store/Update/Delete` FormRequests, Interface + Repository (bound in `Providers/RepositoryServiceProvider.php`), Service, Resource, Model, Factory, Feature tests under `tests/Feature/{Master,Management,History}`.
- `BaseService::recordHistory()` writes a `*_hist` row after each create/update/delete.
- `BaseRepository` has `applyFilters`, `applyDateRange`, `applySorting`, `validateForeignKeys`, `checkCanDelete`.
- Soft delete = `is_delete` column + `Traits/HasSoftDelete` (`notDeleted()` scope).

## HTTP contract (FE depends on it — do not break)

- Routes: `GET {resource}/list`, `POST {resource}/store`, `PUT {resource}/update/{id}`, `DELETE {resource}/delete` (body `{ ids: [] }`). Admin routes under `/api/admin`, public under `/api/docs`.
- Envelope (`GenerateResponseMiddleware` + `bootstrap/app.php`): `{ "data": ..., "error": { "status": bool, "code": int, "messages": string|object|null } }`. Validation errors → 422 with field map in `error.messages`.
- Auth: `access_token` httpOnly cookie (JWT) → `AdminMiddleware` checks Redis key `admin:{id}:{token}` and the per-admin permission hash `admin:{id}:<ADMIN_PERMISSION_TABLE>` (method → allowed route URIs).
- Writes run inside `TransactionMiddleware`; `LoginFailedException` commits instead of rolling back.

## Gotchas

- Tests hit a real PostgreSQL `testing` DB (see `phpunit.xml`), run inside `ml-php`.
- DB views/triggers are created in migrations `..._000046` – `..._000049`; changing RBAC tables means checking those too.
- Media upload goes to MinIO via `Services/MinioService.php` with queued jobs in `Jobs/Media`; broadcast progress over Reverb.
