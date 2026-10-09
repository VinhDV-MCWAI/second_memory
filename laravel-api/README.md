# laravel-api — Second Memory REST API

> 🇻🇳 REST API của Second Memory (Skill Ledger): Laravel 13, PHP 8.5, PostgreSQL, Redis, Sanctum.

The API behind the admin dashboard, the public site and the Obsidian importer. Laravel 13 on PHP 8.5 (php-fpm), PostgreSQL 16, Redis 7 for sessions and cache, Laravel Sanctum for auth. It runs only in Docker: the container is `ml-php`, reached through nginx at `http://localhost:81/api`.

> 🇻🇳 API cho trang quản trị, trang công khai và công cụ import. Chỉ chạy trong Docker (container `ml-php`), truy cập qua nginx tại `http://localhost:81/api`.

## Endpoints

> 🇻🇳 Các nhóm endpoint. Hợp đồng đầy đủ nằm trong `openapi.json`.

| Group | Path | Access |
|---|---|---|
| Auth | `GET /api/sanctum/csrf-cookie`, `POST /api/admin/credential/login`, `logout`, `GET credential/me` | session cookie (Sanctum SPA, [ADR-0004](../docs/adr/0004-sanctum-spa-cookie-auth.md)) |
| Admin accounts | `/api/admin/admin-mst/*` | `owner` writes, `viewer` reads ([ADR-0005](../docs/adr/0005-owner-viewer-roles.md)) |
| Skill Ledger | `/api/admin/{skill,skill-level,learning-goal,evidence,tag}/*`, `search`, `dashboard/summary` | same |
| Audit log | `GET /api/admin/audit-log/list` | read-only ([ADR-0006](../docs/adr/0006-audit-log.md)) |
| Import | `POST /api/admin/evidence/import` | API token with ability `evidence:import` ([ADR-0010](../docs/adr/0010-python-importer-tooling.md)) |
| Public | `GET /api/public/skills`, `GET /api/public/skills/{slug}` | anyone, rate-limited per IP |

Every response uses one envelope, `{ "data": …, "error": { "status", "code", "messages" } }`. The contract is generated from the code into [openapi.json](openapi.json) ([ADR-0008](../docs/adr/0008-code-first-openapi-contract.md)); the tests validate every response against it and `make openapi` regenerates it with the admin FE types.

> 🇻🇳 Mọi response có cùng một envelope. `openapi.json` sinh từ code; test kiểm tra từng response theo nó; `make openapi` sinh lại cả spec và type cho FE.

## Working on it

> 🇻🇳 Làm việc với API. Chạy từ thư mục gốc repo.

```bash
make up                         # start the stack; an empty DB is migrated and seeded on start
make test f=Skill               # PHPUnit on the separate `testing` database
make lint && make analyse       # Pint (PSR-12) and Larastan level 6
make openapi                    # after changing a response: regenerate the spec and FE types
make migrate                    # apply new migrations to the dev database
docker exec ml-php php artisan route:list --path=api
docker exec ml-php php artisan ledger:import-token <user_name>   # mint the importer token (printed once)
```

The dev seed creates the local-only owner `root@gmail.com` / `12345678` (`database/seeders/RootAccountSeeder.php`).

> 🇻🇳 Seed dev tạo tài khoản owner chỉ dùng local: `root@gmail.com` / `12345678`.

## Code layout

> 🇻🇳 Cấu trúc code: Route → Middleware → Controller → FormRequest → Service → Repository → Model.

`Route → Middleware → Controller → FormRequest → Service → Repository → Model`, grouped by module: `Master` (admin accounts), `Ledger` (Skill Ledger), `Audit`, `Public`. Settings come from the `laravel-api/.env` that `make setup` generates from [.env.example](.env.example).

- Conventions: [.claude/rules/backend-laravel.md](../.claude/rules/backend-laravel.md) and [handbook 05](../docs/handbook/05-coding.md).
- Architecture details, HTTP contract and gotchas: [CLAUDE.md](CLAUDE.md).
- Design of the Skill Ledger: [RFC-002](../docs/design/RFC-002-skill-ledger.md); search: [ADR-0009](../docs/adr/0009-postgres-search.md).

> 🇻🇳 Quy ước, chi tiết kiến trúc và các lưu ý nằm ở các file trên.
