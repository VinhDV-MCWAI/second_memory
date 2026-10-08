# ADR-0008 — Code-first OpenAPI with a reviewed contract table and response validation

> 🇻🇳 OpenAPI sinh từ code, kèm bảng hợp đồng được review trước khi code và kiểm tra response theo spec.

| | |
|---|---|
| Status | Accepted |
| Date | 2026-10-08 |
| Deciders | TL |
| Related | [RFC-002](../design/RFC-002-skill-ledger.md) §4.3 / §9 Q1, roadmap P3 ("OpenAPI-first"), backlog P3-03, P3-09 |

## Context

The roadmap asks for an "OpenAPI-first" Skill Ledger. Today the spec is **generated from code**: Scramble reads routes, FormRequests and Resources and `make openapi` exports `laravel-api/openapi.json`, from which `openapi-typescript` generates the admin FE types. Both files are committed; a drift check fails when they are stale (`make openapi-check`, part of `make verify` since CI is paused). Nothing checks that real responses match the spec: Scramble infers Resource shapes, and an inference error would pass silently into the FE types.

What "first" is meant to buy is a contract that is agreed **before** the code and that the code cannot drift from. There is one developer, and the FE and BE are in the same repo and the same PR.

> 🇻🇳 Bối cảnh: roadmap muốn "OpenAPI-first". Hiện spec sinh từ code (Scramble đọc route, FormRequest, Resource), FE sinh type từ spec, có kiểm tra drift. Chưa có gì kiểm tra response thật khớp spec. Mục đích thật của "first": hợp đồng được thống nhất trước khi code và code không thể lệch khỏi nó. Chỉ có một dev, FE và BE cùng repo, cùng PR.

## Options

1. **Spec-first, hand-written YAML.** The spec is the source; server code is checked against it. Classic contract-first. Cost: every field is written twice (spec + FormRequest/Resource), and keeping 15+ endpoints in sync by hand is the kind of busywork that rots for a single developer.
2. **Spec-first with code generation** (generate Laravel stubs from the spec). Removes the double writing on the server, but PHP generators produce code that does not fit the project's layers (Controller → FormRequest → Service → Repository → Resource) and is overwritten on regeneration.
3. **Code-first, plus a reviewed contract and response validation.** The contract is agreed first as a table in the RFC (routes, fields, status codes), reviewed before code. Code is then written; Scramble exports the spec; feature tests validate every response against that spec, so an inference mistake or a Resource change that the spec does not show fails a test.

> 🇻🇳 Các phương án: (1) viết tay spec YAML trước — mỗi trường phải viết hai lần, một người giữ đồng bộ rất dễ bỏ bê; (2) spec trước rồi sinh code PHP — code sinh ra không hợp kiến trúc tầng của dự án; (3) code trước, nhưng hợp đồng được review trước dưới dạng bảng trong RFC, sau đó test feature kiểm tra mọi response theo spec đã export.

## Decision

We choose **option 3**. "OpenAPI-first" in this project means *contract-first review, code-first spec*.

> 🇻🇳 Chọn **phương án 3**. "OpenAPI-first" ở dự án này nghĩa là: review hợp đồng trước, spec sinh từ code.

- **Before code:** each new endpoint is listed in its RFC with route, request fields (type, required, limits), response fields, and status codes. That table is the reviewed contract (for the Skill Ledger: RFC-002 §4.3 plus the field lists in §4.2; details below).
- **While coding:** FormRequests and Resources implement exactly those fields; `make openapi` regenerates the spec and FE types in the same commit; `make openapi-check` fails on drift.
- **Contract tests (P3-09):** feature tests validate each response body against `openapi.json` for its route and status. Proposed tool: `osteel/openapi-httpfoundation-testing` (dev dependency; validates Laravel test responses against an OpenAPI 3.1 file via `league/openapi-psr7-validator`); the choice is confirmed in P3-09 against Scramble's 3.1 output, and a small custom validator is the fallback.
- A spec change that is not in the RFC contract is a review finding.

### Contract details for the Skill Ledger (complements RFC-002 §4.3)

| Resource | Request fields (store; update = same, all optional unless noted) | Response fields |
|---|---|---|
| `skill` | `name` string 1–100 required, `category` string 1–50 required, `description` string ≤ 2000, `is_public` bool, `tag_ids` int[]; store only: `level` int 1–4 required, `changed_on` date ≤ today (default today), `reason` string ≤ 500 | `id`, `name`, `slug`, `category`, `description`, `is_public`, `current_level`, `current_level_label`, `tags[]{id,name}`, `created_at`, `updated_at` (ISO 8601) |
| `skill-level` | `skill_id` required, `level` 1–4 required, `changed_on` date ≤ today, `reason` ≤ 500 | `id`, `skill_id`, `level`, `level_label`, `reason`, `changed_on`, `recorded_by_user_name`, `created_at` |
| `tag` | `name` string 1–50 required, unique ignoring case | `id`, `name` |
| `evidence` | `type` enum required, `title` 1–200 required, `url` http(s) ≤ 2048 required, `occurred_on` date required, `summary` ≤ 1000, `is_public` bool, `skill_ids` int[] min 1, `tag_ids` int[] | `id`, `type`, `title`, `url`, `occurred_on`, `summary`, `is_public`, `source`, `unpublished_at`, `skills[]{id,name}`, `tags[]{id,name}`, `created_at`, `updated_at` |
| `learning-goal` | `skill_id` required, `target_level` 1–4 required, `target_date` date, `status` enum (update only), `note` ≤ 1000 | `id`, `skill{id,name,current_level}`, `target_level`, `target_level_label`, `target_date`, `status`, `achieved_on`, `note`, `created_at`, `updated_at` |
| `search?q=` | `q` string 2–100 required | `match` (`exact` \| `fuzzy`, ADR-0009), `skills[]`, `goals[]`, `evidence[]` (≤ 10 each, best first; item = `id`, `title`, `snippet`) |
| `dashboard/summary` | – | `skills`, `public_skills`, `evidence`, `public_evidence` (public and not unpublished), `open_goals`, `levels[]{level, level_label, count}` (all four levels, also when 0; a list instead of the planned `{1..4: count}` map so the type is exact — P3-11) |
| `evidence/import` | `notes[]{external_key, title, url, occurred_on, summary, tags[], skills[]}` (≤ 2000), `dry_run` bool | `created`, `updated`, `unchanged`, `hidden`, `unknown_skills[]` |
| public `skills` | – | `[]{name, slug, category, current_level, current_level_label, tags[]}` |
| public `skills/{slug}` | – | `name`, `slug`, `category`, `description`, `current_level`, `current_level_label`, `tags[]`, `history[]{level, level_label, changed_on}`, `evidence[]{type, title, url, occurred_on, summary}` |

Lists use the existing `ListRequest` paging (`page`, `per_page`, `sort_by`, `sort_order`) and envelope. Delete uses `{ ids: [] }`.

> 🇻🇳 Bảng trên là hợp đồng chi tiết của Skill Ledger (trường request với giới hạn, trường response). Danh sách dùng phân trang `ListRequest` hiện có; xoá dùng `{ ids: [] }`.

Update (P3-09, 2026-10-08): the proposed tool was **not** used. `league/openapi-psr7-validator` (under `osteel/…`) reads OpenAPI 3.0 through `cebe/php-openapi`, while Scramble emits 3.1 (`type: ["string", "null"]`). OpenAPI 3.1 schemas are JSON Schema 2020-12, so `tests/Support/OpenApiContract.php` loads `openapi.json` into `opis/json-schema` (dev dependency) and validates by JSON pointer. It runs inside `TestCase::call()`, so **every** response of every feature test is checked, not only the Skill Ledger ones; an undocumented status code fails too. To make that possible the spec had to describe the real body: `App\OpenApi\ResponseEnvelope` (Scramble document transformer) wraps 2xx schemas in `{data, error}`, points every 4xx at a shared `ApiError` schema, writes `data: null` for `void` actions and adds the 403 / 404 / 429 responses Scramble cannot infer, using each route's middleware and parameters. Known gap: paginated lists are documented as `{data: [...]}` without the paginator fields (`links`, `meta`); extra fields pass validation, so it does not fail — fix when the admin UI needs typed paging (P3-10).

> 🇻🇳 Cập nhật (P3-09): **không** dùng công cụ đề xuất vì `league/openapi-psr7-validator` chỉ đọc OpenAPI 3.0, còn Scramble xuất 3.1. Schema của OpenAPI 3.1 chính là JSON Schema 2020-12, nên test dùng `opis/json-schema` (chỉ dev) kiểm tra theo JSON pointer, chạy trong `TestCase::call()` cho **mọi** response của mọi feature test; mã trạng thái chưa khai báo cũng làm test fail. Để spec mô tả đúng body thật, transformer `ResponseEnvelope` bọc `{data, error}`, dùng chung schema `ApiError` cho lỗi 4xx, `data: null` cho action `void`, và thêm 403/404/429 dựa theo middleware và tham số route. Còn thiếu: danh sách phân trang chưa mô tả `links`, `meta` (trường thừa không làm fail) — sửa khi UI admin cần (P3-10).

Update (API-01, 2026-10-08): the gap is closed. Scramble infers a list's type from the code, not from the `@return AnonymousResourceCollection<LengthAwarePaginator<…>>` docblock, so `ResponseEnvelope` adds the paginator fields itself for every action whose `@return` names `LengthAwarePaginator`: `data.links` (`PaginationLinks`: `first`, `last`, `prev`, `next`) and `data.meta` (`PaginationMeta`: `current_page`, `from`, `last_page`, `links[]`, `path`, `per_page`, `to`, `total`), both required. The 7 admin `*/list` routes are paginated; `public/skills` is not. Every list response in the feature tests is now checked against them, and `OpenApiContractTest` proves a list without `meta` fails. The FE types gain `components["schemas"]["PaginationMeta"]`; switching `shared/types/api.ts` to them is FE work.

> 🇻🇳 Cập nhật (API-01): đã bổ sung phần còn thiếu. Scramble suy kiểu từ code chứ không đọc docblock, nên `ResponseEnvelope` tự thêm `data.links` (`PaginationLinks`) và `data.meta` (`PaginationMeta`) cho mọi action có `@return` nhắc tới `LengthAwarePaginator` (7 route `*/list` của admin; `public/skills` không phân trang). Mọi response list trong test giờ được kiểm tra với schema này; `OpenApiContractTest` chứng minh thiếu `meta` thì fail. FE có thêm type `PaginationMeta`; chuyển `shared/types/api.ts` sang dùng nó là việc của lane FE.

## Consequences

- **Easier:** each field is written once (FormRequest / Resource); the spec and FE types cannot be stale (`openapi-check`); response shapes are tested, not only inferred.
- **Harder:** the spec is only as complete as Scramble's inference; some shapes need PHPDoc hints on Resources. A consumer outside this repo (none today) would not get a spec before the code exists.
- **Must do next:** P3-09 adds the response validation to the feature tests of all Skill Ledger endpoints (and the existing ones if cheap).

> 🇻🇳 Hệ quả: **dễ hơn** — mỗi trường viết một lần, spec và type FE không thể cũ, hình dạng response được test. **Khó hơn** — spec phụ thuộc khả năng suy luận của Scramble, đôi khi phải thêm PHPDoc; client bên ngoài (hiện không có) không có spec trước khi có code. **Việc tiếp** — P3-09 thêm kiểm tra response vào test.
