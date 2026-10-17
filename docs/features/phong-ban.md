# Phòng ban

Gom quản trị viên theo phòng ban. Một quản trị viên được thuộc nhiều phòng ban. Mục đích chính: khóa thư mục trong Files để chỉ người của phòng ban được cấp mới thấy và dùng được. Quyết định thiết kế: [ADR-0017](../adr/0017-departments-folder-policies.md).

Trạng thái: **đang làm**.

| Phần | Trạng thái |
|---|---|
| Phòng ban, phần máy chủ: API thêm, sửa, xóa, tìm; gán quản trị viên vào phòng ban | Xong (2026-10-17) |
| Phòng ban, giao diện: màn hình Phòng ban, ô chọn phòng ban trong form Quản trị viên | Chưa làm |
| Khóa thư mục Files theo phòng ban, phần máy chủ và giao diện | Chưa làm |

## Thông tin một phòng ban

| Trường | Bắt buộc | Ghi chú |
|---|---|---|
| Mã (`code`) | Có | Tối đa 30 ký tự, không trùng. Không phân biệt hoa thường: đã có `HR` thì không thêm được `hr` |
| Tên (`name`) | Có | Tối đa 100 ký tự |
| Mô tả (`description`) | Không | Tối đa 1000 ký tự |
| Đang dùng (`is_active`) | Có | 1 hoặc 0 |

Danh sách trả thêm `admin_count` (số quản trị viên trong phòng ban) và `updated_at`. Bảng lưu thêm người tạo, người sửa cuối.

## API

| API | Làm gì | Quyền |
|---|---|---|
| `GET /api/admin/department/list` | Danh sách có phân trang. Lọc: `code`, `name` (tìm một phần, không phân biệt hoa thường), `is_active`, `id`. Sắp xếp: `id`, `code`, `name`, `is_active`, `updated_at` (mặc định theo mã) | `department.list` (Chủ, Chỉ xem) |
| `POST /api/admin/department/store` | Thêm | `department.store` |
| `PUT /api/admin/department/update/{id}` | Sửa | `department.update` |
| `POST /api/admin/department/delete` | Xóa, body `{ ids: [...] }`. Xóa được cả khi phòng ban còn người: chỉ bỏ liên kết, tài khoản vẫn còn | `department.delete` |

Gán người: trong API Quản trị viên, trường `department_ids` ([quản trị viên](quan-tri-vien.md)).

Mọi lần thêm, sửa, xóa phòng ban ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md) với loại `department`. Đổi phòng ban của một quản trị viên ghi ở dòng nhật ký của quản trị viên đó (trường `departments`).

## Bảng

| Bảng | Nội dung |
|---|---|
| `department` | Phòng ban. Mã không trùng nhờ index trên chữ thường của mã |
| `admin_department` | Quản trị viên nào thuộc phòng ban nào. Xóa phòng ban hoặc xóa hẳn tài khoản thì dòng liên kết tự mất |
