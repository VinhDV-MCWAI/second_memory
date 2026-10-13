# Quản trị viên

Quản lý các tài khoản được vào trang quản trị: thêm, sửa, xóa, đổi vai trò, khóa tài khoản. Quyền của từng tài khoản đến từ vai trò của nó, xem [phân quyền động](phan-quyen-dong.md).

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| http://localhost:81/admin/admins (menu "Master Data" → "Admins") | Bảng tài khoản: tìm, lọc, sắp xếp, phân trang, thêm, sửa, xóa nhiều dòng |
| Form sửa một tài khoản | Sửa thông tin; phía dưới có lịch sử thay đổi của tài khoản đó (lấy từ [nhật ký thay đổi](nhat-ky-thay-doi.md)) |

## API

| API | Làm gì | Quyền |
|---|---|---|
| `GET /api/admin/admin-mst/list` | Danh sách, có lọc và phân trang | `admin-mst.list` |
| `POST /api/admin/admin-mst/store` | Thêm tài khoản | `admin-mst.store` |
| `PUT /api/admin/admin-mst/update/{id}` | Sửa tài khoản | `admin-mst.update` |
| `POST /api/admin/admin-mst/delete` | Xóa nhiều tài khoản (`ids`) | `admin-mst.delete` |

## Thông tin một tài khoản

| Ô | Bắt buộc | Ghi chú |
|---|---|---|
| Tên đăng nhập (`user_name`) | Có | Không trùng, tối đa 50 ký tự. Dùng để đăng nhập |
| Email | Có | Không trùng, tối đa 30 ký tự, tên miền phải có thật |
| Mật khẩu | Có khi thêm | Lưu dạng băm, không bao giờ trả về hay ghi vào nhật ký |
| Họ, tên | Có | Tối đa 20 ký tự mỗi ô |
| Giới tính, trạng thái, đang hoạt động | Có | Trạng thái: chưa kích hoạt / hoạt động / chờ / tạm khóa |
| Vai trò | Không | `owner` (chủ) hoặc `viewer` (chỉ xem). Khi lưu, tài khoản được gán đúng vai trò này trong bảng phân quyền |
| Địa chỉ, số điện thoại, ngày sinh, ảnh đại diện | Không | |

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Luôn còn ít nhất một chủ đang hoạt động | Không cho hạ vai trò, tắt hoạt động hay xóa chủ cuối cùng (lỗi 422). Nếu không, không ai sửa được dữ liệu nữa |
| Xóa là xóa mềm | Dòng được đánh dấu `is_delete`, không mất khỏi DB |
| Tài khoản bị tắt không đăng nhập được | Báo cùng lỗi 401 như sai mật khẩu |
| Mọi thay đổi có nhật ký | Chỉ ghi các ô thay đổi, không ghi mật khẩu |

## Dữ liệu

| Bảng | Dùng để |
|---|---|
| `admin_mst` | Tài khoản quản trị |
| `audit_log` | Lịch sử thay đổi (`auditable_type = admin`) |

## Code

| Phần | File |
|---|---|
| Route | `laravel-api/routes/api/master.php` |
| Xử lý | `laravel-api/app/Services/Master/AdminMstService.php` |
| Kiểm tra dữ liệu gửi lên | `laravel-api/app/Http/Requests/Master/AdminMst/` |
| Giao diện | `nextjs-fe/src/app/admin/admins/page.tsx`, `nextjs-fe/src/features/master/components/admin-form.tsx` |
| Test | `make test f=AdminMst` |
