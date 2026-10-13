# Phân quyền động

Mỗi API của trang quản trị là một **quyền**. Quyền được gom vào **vai trò**, vai trò được gán cho quản trị viên. Vai trò và quyền lưu trong DB, nên thêm hay sửa vai trò không phải sửa code. Quyết định thiết kế: [ADR-0013](../adr/0013-dynamic-permissions.md). Làm bằng thư viện `spatie/laravel-permission`.

Trạng thái: **backend xong**. Chưa có API để quản lý vai trò và chưa có màn hình (hai phần này làm ở các bước tiếp theo của giai đoạn phân quyền động). Hiện vai trò của một admin vẫn chọn qua ô "Vai trò" (chủ / chỉ xem) trong form quản trị viên.

## Quyền là gì

| | |
|---|---|
| Tên quyền | Tên route của API, dạng `<chức năng>.<việc>`: `skill.list`, `evidence.delete`, `admin-mst.update` |
| Chức năng | Phần trước dấu chấm: `skill` → Skill, `evidence` → Bằng chứng, `learning-goal` → Mục tiêu học, `tag` → Nhãn, `admin-mst` → Quản trị viên, `audit-log` → Nhật ký thay đổi |
| Số quyền hiện có | 26 (mọi API quản trị, trừ đăng nhập, đăng xuất và `credential/me`) |
| Luôn được phép | `GET /api/admin/credential/me`: ai đăng nhập cũng xem được thông tin của mình |

## Vai trò có sẵn

| Vai trò | Quyền | Ghi chú |
|---|---|---|
| Chủ (`owner`) | Toàn quyền, không cần tick quyền nào | Không thể tự khóa mình. API mới dùng được ngay. Luôn phải còn ít nhất một chủ đang hoạt động |
| Chỉ xem (`viewer`) | Mọi quyền xem (9 quyền `GET`) | Ghi gì cũng bị 403 |

Một admin có thể có nhiều vai trò; quyền là phần gộp của các vai trò đó. Admin không có vai trò nào thì chỉ gọi được `credential/me`.

## Kiểm tra mỗi request

```
Request → đăng nhập (auth:sanctum) → AdminMiddleware
            ├─ token của công cụ nhập Obsidian: phải có ability của route (như cũ)
            ├─ route không có tên            → 403 (chặn khi không chắc)
            ├─ route luôn được phép          → cho qua
            └─ admin có quyền mang tên route? (vai trò Chủ luôn có) → cho qua, không thì 403
```

## Thêm một API mới

1. Đặt tên cho route: `->name('<chức năng>.<việc>')`. Route không có tên sẽ bị chặn với mọi người trừ Chủ.
2. Chạy `make permissions` để đăng ký quyền mới vào DB dev và DB `testing`.
3. Quyền mới chưa thuộc vai trò nào ngoài Chủ. Muốn vai trò Chỉ xem có quyền đó thì gán thêm (khi có màn hình vai trò thì làm trên giao diện).

Test `DynamicPermissionTest` báo lỗi nếu có route quản trị chưa có tên, hoặc chưa chạy `make permissions`.

## Bảng

| Bảng | Chứa gì |
|---|---|
| `permissions` | Một dòng mỗi quyền (`name`, `guard_name = admin`) |
| `roles` | Một dòng mỗi vai trò |
| `role_has_permissions` | Vai trò nào có quyền nào |
| `model_has_roles` | Admin nào có vai trò nào |
| `model_has_permissions` | Gán quyền thẳng cho admin. Không dùng, giữ vì thư viện cần |

Cột `admin_mst.role` vẫn còn và được đồng bộ sang `model_has_roles` mỗi khi lưu admin. Cột này sẽ bị bỏ khi API gán vai trò thay thế nó.

## Lệnh

| Lệnh | Làm gì |
|---|---|
| `make permissions` | Thêm quyền cho route mới, trên DB dev và DB `testing`. Chạy lại nhiều lần không sao |
| `docker exec ml-php php artisan permission:sync-routes --prune` | Xóa quyền của route không còn tồn tại. Quyền bị xóa cũng mất khỏi mọi vai trò |
