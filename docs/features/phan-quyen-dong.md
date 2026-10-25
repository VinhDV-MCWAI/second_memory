# Phân quyền động

Mỗi API của trang quản trị là một **quyền**. Quyền được gom vào **vai trò**, vai trò được gán cho quản trị viên. Vai trò và quyền lưu trong DB, nên thêm hay sửa vai trò không phải sửa code. Quyết định thiết kế: [ADR-0013](../adr/0013-dynamic-permissions.md). Làm bằng thư viện `spatie/laravel-permission`.

Trạng thái: **xong** (backend, API, giao diện).

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| http://localhost:81/admin/roles (menu "Master Data" → "Roles") | Bảng vai trò: tên, loại (có sẵn / tự tạo), số quyền, số admin đang giữ. Thêm, sửa, xóa |
| Form vai trò | Tên + **ma trận quyền**: mỗi hàng là một chức năng, các cột Xem / Thêm / Sửa / Xóa, cột "Khác" cho việc riêng (nhập Obsidian, tìm kiếm, tổng quan). Ô đầu hàng tick cả hàng. Vai trò Chủ chỉ hiện dòng "luôn có toàn quyền". Phía dưới có lịch sử thay đổi |
| Form quản trị viên | Ô "Roles": tick nhiều vai trò. Để trống khi thêm mới thì tài khoản là Chỉ xem |
| Menu bên trái | Chỉ hiện mục mà admin có quyền xem (`*.list`); nhóm không còn mục nào thì ẩn cả nhóm |
| Mọi trang danh sách | Nút Thêm, Sửa, Xóa, chọn nhiều để xóa chỉ hiện khi có quyền `store` / `update` / `delete` của chức năng đó |

Giao diện chỉ ẩn những gì API sẽ từ chối; API vẫn là nơi kiểm tra thật (403).

## Quyền là gì

| | |
|---|---|
| Tên quyền | Tên route của API, dạng `<chức năng>.<việc>`: `article.list`, `media.delete`, `admin-mst.update` |
| Chức năng | Phần trước dấu chấm: `article` → Bài viết, `content-category` → Danh mục, `media` → Files, `department` → Phòng ban, `end-user` → Người dùng cuối, `admin-mst` → Quản trị viên, `role` → Vai trò, `permission` → Danh sách quyền, `audit-log` → Nhật ký thay đổi |
| Số quyền hiện có | 53 (đếm 2026-10-25; mọi API quản trị, trừ đăng nhập, đăng xuất và `credential/me`) |
| Luôn được phép | `GET /api/admin/credential/me`: ai đăng nhập cũng xem được thông tin của mình |

## Vai trò có sẵn

| Vai trò | Quyền | Ghi chú |
|---|---|---|
| Chủ (`owner`) | Toàn quyền, không cần tick quyền nào | Không thể tự khóa mình. API mới dùng được ngay. Luôn phải còn ít nhất một chủ đang hoạt động |
| Chỉ xem (`viewer`) | Mọi quyền xem (16 quyền `GET`) | Ghi gì cũng bị 403. Đổi được quyền, không đổi được tên |

Một admin có thể có nhiều vai trò; quyền là phần gộp của các vai trò đó. Admin không có vai trò nào thì chỉ gọi được `credential/me`.

## API

| API | Làm gì | Quyền |
|---|---|---|
| `GET /api/admin/role/list` | Danh sách vai trò: tên, có phải vai trò có sẵn không (`is_built_in`), danh sách quyền, số admin đang giữ (`admin_count`). Lọc theo `name` | `role.list` |
| `POST /api/admin/role/store` | Thêm vai trò: `name`, `permissions` (danh sách tên quyền) | `role.store` |
| `PUT /api/admin/role/update/{id}` | Sửa tên và quyền | `role.update` |
| `POST /api/admin/role/delete` | Xóa vai trò (`ids`) | `role.delete` |
| `GET /api/admin/permission/list` | Mọi quyền, gom theo chức năng: `[{ feature, permissions: [{ name, action, method }] }]` | `permission.list` |
| `GET /api/admin/credential/me` | Admin đang đăng nhập, kèm `roles` và `permissions` (tên các quyền đang có; Chủ có tất cả) để giao diện ẩn những gì không dùng được | Luôn được phép |

Gán vai trò cho admin: ô `role_ids` của API thêm / sửa quản trị viên (xem [quản trị viên](quan-tri-vien.md)).

## Quy tắc

| Quy tắc | Lỗi |
|---|---|
| Tên vai trò không trùng (không phân biệt hoa thường), tối đa 50 ký tự | 422 |
| Quyền gửi lên phải có thật | 422 (`permissions.0`, …) |
| Vai trò có sẵn (Chủ, Chỉ xem) không đổi tên, không xóa được | 422 |
| Không đổi quyền của vai trò Chủ (Chủ luôn có tất cả) | 422 |
| Không xóa vai trò đang có admin giữ | 422 |
| Thêm, sửa, xóa vai trò đều ghi [nhật ký thay đổi](nhat-ky-thay-doi.md) (loại `role`), có danh sách quyền trước và sau | |

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

Cột cũ `admin_mst.role` đã bỏ (migration `drop_role_from_admin_mst`; chạy ngược thì cột được tạo lại từ vai trò).

## Lệnh

| Lệnh | Làm gì |
|---|---|
| `make permissions` | Thêm quyền cho route mới, trên DB dev và DB `testing`. Chạy lại nhiều lần không sao |
| `docker exec ml-php php artisan permission:sync-routes --prune` | Xóa quyền của route không còn tồn tại. Quyền bị xóa cũng mất khỏi mọi vai trò |
