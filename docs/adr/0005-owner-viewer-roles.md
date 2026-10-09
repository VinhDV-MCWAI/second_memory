# ADR-0005 — Two fixed roles (`owner`, `viewer`) instead of the role → feature → API permission chain

> 🇻🇳 Hai vai trò cố định (`owner`, `viewer`) thay cho chuỗi phân quyền role → feature → API.

| | |
|---|---|
| Status | Superseded by [ADR-0012](0012-restore-features-dev-first.md) (2026-10-09); was: Accepted |
| Date | 2026-10-07 |
| Deciders | PO, TL |
| Related | [REQ-001](../requirements/REQ-001-slim-down.md), [RFC-001](../design/RFC-001-slim-down.md) slice 8, [ADR-0004](0004-sanctum-spa-cookie-auth.md), [analysis §4](../plan/01-analysis.md#4-keep--simplify--remove), backlog P2-12 |

## Context

Authorization today is an enterprise-style chain: admins get roles (`admin_role_mst`), roles get API routes (`api_role_mst`), routes are grouped into features (`feature_mst`), a DB trigger (`after_api_insert`) grants every new API to the `root` role, and a DB view (`admin_permission_view`) flattens it all for the per-request check in `AdminMiddleware`. Five tables, three history tables, a trigger, a view, five admin pages and a role wizard serve one real user. The only second user the roadmap needs is a read-only demo account for recruiters (P10). `token_mst` (refresh tokens) has had no writer since ADR-0004.

> 🇻🇳 Bối cảnh: phân quyền hiện là chuỗi kiểu doanh nghiệp (admin → role → API → feature, trigger cấp mọi API mới cho `root`, view gộp quyền cho middleware): 5 bảng, 3 bảng lịch sử, 1 trigger, 1 view, 5 trang admin và một wizard — cho một người dùng thật. Người dùng thứ hai duy nhất cần là tài khoản demo chỉ đọc (P10). `token_mst` không còn ai ghi từ ADR-0004.

REQ-001 Q1: the owner still manages admins and their roles weekly; everything else in the chain is configuration nobody changes.

> 🇻🇳 REQ-001 câu 1: owner vẫn quản lý admin và vai trò của họ; phần còn lại của chuỗi là cấu hình không ai đổi.

## Options

1. **Keep the chain.** Fine-grained, already built. Cost stays: tables, trigger, view, wizard, tests; logic hidden in the DB.
2. **Spatie laravel-permission.** Standard package for roles + permissions. Still a permission model nobody needs; new dependency.
3. **A `role` column with two values and a Gate.** `owner` may do everything, `viewer` may only read (safe HTTP methods). Logic in PHP, visible and testable.

> 🇻🇳 Các phương án: (1) giữ nguyên chuỗi; (2) package Spatie laravel-permission — vẫn là mô hình quyền không ai cần, thêm dependency; (3) cột `role` hai giá trị + một Gate: `owner` làm mọi thứ, `viewer` chỉ đọc.

## Decision

We choose **option 3**.

> 🇻🇳 Chọn **phương án 3**.

- `admin_mst.role`: backed enum `App\Enums\AdminRole` (`owner` | `viewer`), default `viewer`.
- Gate `write` (`AppServiceProvider`): only an `owner` passes. `AdminMiddleware` lets safe methods (GET, HEAD, OPTIONS) through for any signed-in admin and authorizes every other method with `write`; a viewer gets 403.
- At least one active owner must remain: demoting, deactivating or deleting the last active owner is refused (422).
- Data migration (expand, then contract): add `role`, set `owner` for admins that held the `root` role; if no admin did, the oldest active admin becomes `owner`. Then drop `role_mst`, `admin_role_mst`, `feature_mst`, `api_mst`, `api_role_mst`, their `*_hist` tables, `token_mst`, the view `admin_permission_view`, the trigger `after_api_insert` and the unused column `admin_mst.limit_access`. `down()` recreates the structure (data only from backup, as in ADR-0003).
- The admin form gets a role selector; the roles, features, APIs and tokens pages and the role wizard are removed.

> 🇻🇳 Chi tiết: cột `role` (enum `owner` | `viewer`, mặc định `viewer`); Gate `write` chỉ cho `owner`; middleware cho mọi admin đã đăng nhập dùng GET/HEAD/OPTIONS, các method khác cần `write` (viewer bị 403); luôn phải còn ít nhất một owner đang hoạt động (hạ quyền / khoá / xoá owner cuối cùng bị từ chối, 422); migration: thêm `role`, admin có role `root` thành `owner` (không ai có thì admin hoạt động cũ nhất), rồi xoá các bảng RBAC, bảng lịch sử, `token_mst`, view, trigger và cột `limit_access`; form admin có ô chọn vai trò, bỏ các trang roles / features / APIs / tokens và wizard.

## Consequences

- **Easier:** one place to read the rules (`AdminMiddleware` + one Gate); no DB trigger or view; adding a page needs no permission rows; the demo account is "create an admin with role `viewer`".
- **Harder:** no per-page permissions. If a real need appears (e.g. a third role), add Policies per model or revisit option 2 in a new ADR.
- **Must do next:** the FE should hide write actions for a viewer (backend already refuses them) — small follow-up, needed before the P10 demo account. Slice 9 (audit log) keeps only `admin_mst_hist` to migrate.

> 🇻🇳 Hệ quả: **dễ hơn** — quy tắc nằm ở một chỗ, không còn trigger/view, thêm trang không cần thêm dòng quyền, tài khoản demo chỉ là một admin `viewer`. **Khó hơn** — không còn quyền theo từng trang; khi cần thật thì thêm Policy hoặc xem lại phương án 2 bằng ADR mới. **Việc tiếp** — FE nên ẩn nút ghi với viewer (backend đã chặn), cần trước khi có tài khoản demo P10; slice 9 chỉ còn `admin_mst_hist` để chuyển.
