# ADR-0013 — Phân quyền động bằng spatie/laravel-permission

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-13, chủ dự án xác nhận) |
| Ngày | 2026-10-13 |
| Người quyết định | Chủ dự án |
| Thay thế | ADR-0005 (hai vai trò cố định chủ / chỉ xem) |

## Bối cảnh

Hiện tại quyền viết cứng trong code: cột `admin_mst.role` là `owner` hoặc `viewer`; chủ được làm mọi thứ, chỉ xem chỉ được đọc. Muốn thêm một vai trò khác (ví dụ "chỉ quản lý bằng chứng") là phải sửa code. [ADR-0012](0012-restore-features-dev-first.md) đã chốt khôi phục phân quyền động bằng thư viện `spatie/laravel-permission`.

## Quyết định

| Hạng mục | Quyết định |
|---|---|
| Thư viện | `spatie/laravel-permission` bản 8 (hỗ trợ Laravel 13), guard `admin` |
| Bảng mới | `roles`, `permissions`, `model_has_roles`, `role_has_permissions`, `model_has_permissions` (bảng mặc định của thư viện) |
| Một quyền là gì | Một API của trang quản trị. Tên quyền = tên route, ví dụ `skill.list`, `evidence.delete`. Đường dẫn API không đổi |
| Gom theo chức năng | Lấy từ phần đầu tên quyền (`skill.*` → chức năng Skill). Không có bảng chức năng; tên hiển thị nằm trong file dịch của giao diện |
| Tự đăng ký API thành quyền | Lệnh `php artisan permission:sync` đọc các route quản trị, thêm quyền thiếu, báo quyền thừa; `--prune` mới xóa quyền thừa. `make seed` gọi lệnh này |
| Kiểm tra mỗi request | `AdminMiddleware` cho qua khi admin có quyền mang tên route. Route quản trị không có tên → bị chặn. Đăng nhập, đăng xuất, `credential/me` luôn được phép. Token của công cụ nhập Obsidian vẫn kiểm tra ability như ADR-0010 |
| Vai trò Chủ | Luôn có toàn quyền (bỏ qua kiểm tra), nên API mới dùng được ngay và không thể tự khóa mình. Luôn phải còn ít nhất một chủ đang hoạt động |
| Số vai trò của một admin | Nhiều vai trò; quyền là phần gộp |
| Chuyển dữ liệu cũ | Migration tạo vai trò Chủ (`owner`) và Chỉ xem (`viewer`: mọi quyền `*.list`, tìm kiếm, dashboard, nhật ký), gán theo cột `role` cũ. Cột `role` bị bỏ khi API gán vai trò đã thay nó (`down()` khôi phục) |
| Nhật ký | Thay đổi vai trò, quyền của vai trò, vai trò của admin đều ghi vào `audit_log` |

## Các phương án đã cân nhắc

| Câu hỏi | Chọn | Không chọn |
|---|---|---|
| Gom chức năng | Theo tên quyền, không thêm bảng | Bảng chức năng sửa được trên màn hình như `v1.0.0`: thêm bảng, màn hình và việc bảo trì |
| Vai trò Chủ | Toàn quyền | Chủ cũng tick quyền: chặt hơn nhưng dễ tự khóa |
| Vai trò mỗi admin | Nhiều | Một: đơn giản hơn nhưng kém linh hoạt |
| Cách làm | Thư viện spatie | Tự viết bảng `role_mst`, `api_mst`, `feature_mst` như `v1.0.0`: nhiều code tự bảo trì hơn |

## Hệ quả

- API thêm/sửa admin đổi trường `role` thành danh sách vai trò; giao diện sửa cùng lúc.
- Giao diện nhận danh sách quyền từ `credential/me` để ẩn menu và nút.
- Mỗi API mới phải có tên route; một test duyệt mọi route quản trị để bắt lỗi quên.
