# Phòng ban

Gom quản trị viên theo phòng ban. Một quản trị viên được thuộc nhiều phòng ban. Mục đích chính: khóa thư mục trong Files để chỉ người của phòng ban được cấp mới thấy và dùng được. Quyết định thiết kế: [ADR-0017](../adr/0017-departments-folder-policies.md).

Trạng thái: **đang làm**.

| Phần | Trạng thái |
|---|---|
| Phòng ban, phần máy chủ: API thêm, sửa, xóa, tìm; gán quản trị viên vào phòng ban | Xong (2026-10-17) |
| Phòng ban, giao diện: màn hình Phòng ban, ô chọn phòng ban trong form Quản trị viên | Xong (2026-10-17) |
| Khóa thư mục Files theo phòng ban, phần máy chủ | Xong (2026-10-17) |
| Khóa thư mục Files theo phòng ban, giao diện: nút Access, biểu tượng ổ khóa | Chưa làm |

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| http://localhost:81/admin/departments (menu "Master Data" → "Departments") | Bảng phòng ban: mã, tên, mô tả, số quản trị viên, đang dùng hay không, ngày sửa. Lọc theo mã, tên, trạng thái. Thêm, sửa, xóa (nút chỉ hiện khi có quyền) |
| Form phòng ban | Mã, tên, mô tả, ô Đang dùng. Khi sửa, phía dưới có lịch sử thay đổi |
| Form quản trị viên | Ô "Departments": tick nhiều phòng ban. Bỏ tick hết thì quản trị viên không thuộc phòng ban nào |
| Danh sách quản trị viên | Thêm cột Departments và bộ lọc theo phòng ban |

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

## Khóa thư mục trong Files

| Luật | |
|---|---|
| Thư mục mở | Thư mục chưa gắn phòng ban nào: ai có quyền Files cũng dùng được, như trước |
| Thư mục khóa | Thư mục đã gắn ít nhất một phòng ban: chỉ thành viên của **một trong** các phòng ban đó (và vai trò Chủ) thấy và dùng được nó **cùng mọi thứ bên trong** |
| Chưa thuộc phòng ban nào | Chỉ dùng được thư mục mở. Không gán phòng ban không phải là cách vượt khóa |
| Phòng ban ngừng dùng | Không mở khóa cho thành viên của nó, nhưng vẫn giữ khóa thư mục |
| Khóa lồng nhau | Thư mục khóa nằm trong thư mục khóa khác: phải được **cả hai** cho phép |
| Đổi tên, chuyển thư mục khóa | Khóa đi theo thư mục (lưu theo mã thư mục, không theo đường dẫn) |
| File ở ngoài cùng | Luôn mở |
| Vai trò Chủ | Luôn thấy hết |
| Việc chạy nền, lệnh dọn dẹp | Không bị chặn (không có người đăng nhập) |
| Trang public | **Không đổi**: file gắn vào bằng chứng công khai vẫn mở được. Muốn giấu thì bỏ công khai bằng chứng |

Không được phép thì API trả về y như khi thư mục hoặc file không tồn tại, để không lộ là có nó:

| Chỗ | Người không được phép thấy gì |
|---|---|
| Danh sách Files | Không thấy thư mục khóa. Mở thẳng `?folder=/Secret`: lỗi "thư mục không tồn tại" (422 ở `folder`) |
| Tải về, xem trước | 404 |
| Upload (file nhỏ và file lớn), tạo thư mục con | Lỗi "thư mục không tồn tại" |
| Đổi tên, chuyển | Mục bị khóa: 404. Chuyển vào thư mục khóa: lỗi "thư mục không tồn tại" |
| Xóa | Mục bị khóa được bỏ qua như id không tồn tại. Xóa một thư mục có chứa thư mục khóa mà mình không dùng được: từ chối (422, "This folder holds folders you cannot use") |
| Thùng rác | Chỉ thấy, khôi phục, xóa hẳn file của thư mục được phép. Thư mục khóa đã bị xóa vẫn giữ khóa cho file của nó trong thùng rác |
| Upload dở | Thư mục bị khóa sau khi bắt đầu upload: upload biến khỏi khung Unfinished uploads, không gửi tiếp được |
| Gắn file vào bằng chứng | Không gắn được file bị khóa (422 ở `media_ids`). File đã gắn từ trước thì vẫn giữ, vẫn hiện tên, nhưng `locked: true`: không có xem trước, không có ảnh nhỏ, không có nút mở/tải |

### API

| API | Làm gì | Quyền |
|---|---|---|
| `PUT /api/admin/media/access/{id}` | Body `{ department_ids: [...] }`. Gắn thư mục cho các phòng ban (thay toàn bộ danh sách cũ). Danh sách rỗng là mở khóa. Gửi id của file: 422 ("Only folders can be given to departments") | `media.access` (vai trò Chủ có sẵn) |
| `GET /api/admin/media/list` | Mỗi dòng có thêm `department_ids` (rỗng là thư mục mở) | `media.list` |

Mỗi lần đổi khóa ghi vào nhật ký thay đổi của thư mục (loại `media`, sự kiện `updated`, `department_ids` cũ và mới).

Bảng `department_policy`: cặp (phòng ban, thư mục). Xóa phòng ban thì khóa của phòng ban đó mất. Xóa hẳn thư mục (khỏi thùng rác) thì khóa mất theo.

Ghi chú: thư mục khóa bị xóa rồi khôi phục một file bên trong thì thư mục được tạo lại là thư mục **mới, chưa khóa**. Muốn khóa lại thì khóa thư mục mới đó.
