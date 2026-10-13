# Nhật ký thay đổi

Ghi lại ai đã thêm, sửa, xóa gì và lúc nào, cùng mọi lần đăng nhập, đăng xuất, đăng nhập sai. Một bảng chung cho mọi loại dữ liệu ([ADR-0006](../adr/0006-audit-log.md)). Chỉ đọc: không ai sửa hay xóa được dòng nhật ký qua API.

## Màn hình

Chưa có màn hình riêng. Nhật ký hiện ở hai chỗ:

| Chỗ | Hiện gì |
|---|---|
| Trang tổng quan http://localhost:81/admin, khung "Hoạt động gần đây" | 10 dòng mới nhất: việc gì, loại dữ liệu + mã, ai làm, lúc nào |
| Phía dưới form sửa quản trị viên, kỹ năng, bằng chứng, mục tiêu học | Lịch sử của đúng bản ghi đó, mới nhất trước, mỗi ô đổi hiện `cũ → mới` |

## API

| API | Làm gì | Ai dùng |
|---|---|---|
| `GET /api/admin/audit-log/list` | Danh sách, lọc theo `auditable_type`, `auditable_id`…, sắp xếp, phân trang | Mọi quản trị viên |

## Ghi những gì

| Việc | Mã | Ghi chú |
|---|---|---|
| Thêm | `created` | Giá trị mới |
| Sửa | `updated` | Chỉ các ô thay đổi: giá trị cũ và mới |
| Xóa | `deleted` | Ảnh chụp bản ghi trước khi xóa |
| Đăng nhập / đăng xuất | `logged_in` / `logged_out` | |
| Đăng nhập sai | `login_failed` | Chỉ tên đã gõ, không có mã tài khoản (để không lộ tài khoản có tồn tại) |

Loại dữ liệu (`auditable_type`): `admin`, `skill`, `skill_level`, `learning_goal`, `evidence`, `tag`.

Không bao giờ ghi: mật khẩu, `remember_token`. Dòng do [nhập từ Obsidian](nhap-tu-obsidian.md) tạo có `new_values.via = importer`. Khi một mục tiêu học tự thành "đã đạt" vì cấp độ mới, nhật ký cũng có dòng `updated` cho mục tiêu đó.

## Dữ liệu

| Bảng | Dùng để |
|---|---|
| `audit_log` | Mỗi dòng: loại, mã bản ghi, việc, giá trị cũ, giá trị mới, người làm, thời điểm |

## Code

| Phần | File |
|---|---|
| Ghi | `laravel-api/app/Services/AuditLogger.php`, `laravel-api/app/Services/AuditedCrudService.php` |
| Đọc | `laravel-api/app/Services/AuditLogService.php` |
| Giao diện | `nextjs-fe/src/features/history/components/history-viewer.tsx`, `nextjs-fe/src/app/admin/page.tsx` |
| Test | `make test f=AuditLog` (đọc và ghi) |
