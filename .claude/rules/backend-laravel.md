---
paths:
  - "laravel-api/**/*.php"
---

# Laravel backend conventions

These apply to **new and touched code**. Don't reformat untouched files in feature work (that's a dedicated refactor item).

## Style
- PSR-12 via Laravel Pint (`pint.json` at `laravel-api/`), 4-space indent, `declare(strict_types=1);` in every file.
- Full type declarations on params, returns and properties. Use constructor property promotion and `readonly` where possible.
- Prefer `final` classes for concrete Controllers/Services/Repositories unless they are meant to be extended.
- Imports over FQCN in code (`use Illuminate\Support\Facades\Log;`, not `\Log::`).
- PHPDoc only where it adds type info PHP can't express (`@param array<string, mixed>`, generics) or explains *why*. No docblocks that restate the signature.

## Layers
- **Controller:** thin. Validate via FormRequest, call one Service method, return a Resource / scalar. Use `$request->validated()`, never `$request->all()`.
- **FormRequest:** all validation and authorization-of-input. Rules as arrays, enum rules via `Rule::enum()`.
- **Service:** business logic and orchestration (history, events, cache). No query building here.
- **Repository:** all Eloquent queries for one aggregate. Return models/collections/paginators, never Resources.
- **Resource:** the only place that shapes JSON output.
- Never call `env()` outside `config/*.php`; read via `config()`.

## Data & DB
- Enums are backed PHP enums in `app/Enums`; cast model attributes to enums (`casts()` method). Labels via a shared trait, not copy-pasted `getLabel()`.
- Constants in `app/Constants`; user-facing messages via `Messages` / `lang/` keys.
- Migrations: never edit a migration that has run in production — add a new one. Every migration must have a working `down()`.
- Avoid N+1: eager-load (`with()`), select only needed columns. Target: `Model::shouldBeStrict()` outside production (refactor item).
- Sorting/filtering columns come from an explicit allow-list on the repository/model, not `Schema::hasColumn` per request.

## Errors & security
- Throw domain exceptions (`app/Exceptions`) or Symfony HTTP exceptions; the global handler in `bootstrap/app.php` builds the envelope.
- Never expose exception messages from DB/runtime errors to clients in production.
- 401 = not authenticated, 403 = authenticated but not allowed, 404 = not found, 422 = validation.
- Mass assignment only through `$fillable` + validated data.

## Tests
- Every endpoint change needs a Feature test (happy path, validation 422, auth 401/403, not-found).
- Use factories; no hard-coded IDs. `RefreshDatabase` / `DatabaseTransactions` as the existing tests do.
