# ADR-0006 — One `audit_log` table instead of a history table per entity

> 🇻🇳 Một bảng `audit_log` chung thay cho mỗi thực thể một bảng lịch sử.

| | |
|---|---|
| Status | Accepted |
| Date | 2026-10-07 |
| Deciders | TL |
| Related | [RFC-001](../design/RFC-001-slim-down.md) slice 9 (§4 to-be ERD), [PRB-001](../problems/PRB-001-custom-jwt-auth.md) §9 (login audit), [ADR-0005](0005-owner-viewer-roles.md), backlog P2-08 |

## Context

Each audited entity had its own `*_hist` table: a full copy of the row's columns plus `action`, `author_id`, `created_at`, and its own model, repository, service, requests, controller and four CRUD routes (history rows could even be edited and deleted through the API). There were 14 such tables; after slices 2–8 only `admin_mst_hist` is left. Every new column must be added twice, a new entity needs eight new files to be audited, and the copy shows *what the row looked like*, not *what changed*. The FE history viewer is not mounted on any page. Logins are not recorded at all.

> 🇻🇳 Bối cảnh: mỗi thực thể có một bảng `*_hist` riêng (bản sao toàn bộ cột + `action`, `author_id`, `created_at`) kèm model, repository, service, request, controller và 4 route CRUD (thậm chí sửa/xoá được lịch sử qua API). Từng có 14 bảng; sau slice 2–8 chỉ còn `admin_mst_hist`. Thêm cột phải thêm hai nơi, thêm thực thể cần 8 file, bản sao chỉ cho biết dòng trông thế nào chứ không cho biết đổi gì. Viewer lịch sử ở FE không gắn vào trang nào. Đăng nhập không được ghi lại.

## Options

1. **Keep per-entity history tables.** No migration; all the costs above stay.
2. **A package** (`owen-it/laravel-auditing`, `spatie/laravel-activitylog`). Mature; adds a dependency and its own conventions for a single audited model.
3. **One `audit_log` table written by our own small service**, as in the RFC-001 to-be ERD.

> 🇻🇳 Các phương án: (1) giữ bảng lịch sử riêng; (2) dùng package (laravel-auditing, activitylog) — chín muồi nhưng thêm dependency cho đúng một model; (3) một bảng `audit_log` do một service nhỏ của mình ghi, đúng như ERD to-be của RFC-001.

## Decision

We choose **option 3**.

> 🇻🇳 Chọn **phương án 3**.

- Table `audit_log`: `id`, `auditable_type` (short alias, e.g. `admin`), `auditable_id` (nullable: a failed login for an unknown user has no row), `event`, `old_values` / `new_values` (JSON, nullable), `admin_mst_id` (who did it, nullable), `ip_address`, `created_at`. Index on (`auditable_type`, `auditable_id`, `id`). Append-only: the API only lists it.
- Events (`App\Enums\AuditEvent`): `created` (new values), `updated` (only the changed fields, old and new), `deleted` (old values), and for auth `logged_in`, `logged_out`, `login_failed` (PRB-001 §9). Passwords and remember tokens are never stored.
- Migration by **expand / contract**, each step a separate commit so it could ship as a separate deploy: (1) create `audit_log`, write both tables; (2) backfill old `admin_mst_hist` rows (idempotent, tracked by a temporary `legacy_hist_id`); (3) switch reads (API `GET /api/admin/audit-log/list`, FE viewer); (4) stop writing `admin_mst_hist`, drop it and the `legacy_hist_id` column.

> 🇻🇳 Chi tiết: bảng `audit_log` (loại + id đối tượng, sự kiện, giá trị cũ/mới dạng JSON, người thực hiện, IP, thời điểm), chỉ ghi thêm, API chỉ cho đọc. Sự kiện: `created`, `updated` (chỉ các trường thay đổi), `deleted`, và cho đăng nhập `logged_in`, `logged_out`, `login_failed`. Không bao giờ lưu mật khẩu. Chuyển đổi theo expand / contract, mỗi bước một commit: tạo bảng và ghi song song → backfill dữ liệu cũ (idempotent, đánh dấu bằng cột tạm `legacy_hist_id`) → chuyển phần đọc → ngừng ghi bảng cũ, xoá bảng và cột tạm.

## Consequences

- **Easier:** auditing a new entity is one line in its service; the log answers "what changed and who did it"; one place to look during incidents (P7).
- **Harder:** JSON columns are not type-checked by the schema; reports over values need JSON queries. Acceptable at this size; revisit with a package if many entities need auditing.
- **Must do next:** P6 can ship the audit log to the log pipeline; P8 security review covers who may read it (today any signed-in admin, like the rest of the admin data).

> 🇻🇳 Hệ quả: **dễ hơn** — audit thực thể mới chỉ cần một dòng; log trả lời được "đổi gì, ai đổi"; một chỗ để tra khi có sự cố (P7). **Khó hơn** — cột JSON không được schema kiểm tra kiểu; báo cáo theo giá trị cần truy vấn JSON. Chấp nhận được ở quy mô này. **Việc tiếp** — P6 có thể đẩy audit log vào hệ thống log; P8 xem lại ai được đọc nó.
