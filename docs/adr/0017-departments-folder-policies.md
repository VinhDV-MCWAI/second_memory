# ADR-0017 — Phòng ban và chính sách theo thư mục Files

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-17, chủ dự án xác nhận cả 5 đề xuất) |
| Ngày | 2026-10-15 |
| Người quyết định | Chủ dự án |
| Liên quan | [ADR-0015](0015-defer-end-users-departments.md) (hoãn và các câu hỏi còn mở), [ADR-0013](0013-dynamic-permissions.md) (phân quyền theo vai trò), [ADR-0014](0014-media-minio-upload.md) (Files) |

## Bối cảnh

Ngày 2026-10-15 chủ dự án chọn làm lại phòng ban và chính sách theo hướng **chặn thật**, bắt đầu từ thư mục trong Files. Người dùng cuối vẫn để sau, phòng ban không cần nó.

Hiện tại phân quyền theo vai trò quyết định admin **gọi được API nào** (ví dụ `media.list`), nhưng không quyết định **thấy được dữ liệu nào**: ai có `media.list` thì thấy mọi thư mục. Chính sách phòng ban bổ sung phần còn thiếu này.

## Đề xuất

### 1. Phòng ban

| Phần | Đề xuất |
|---|---|
| Bảng `department` | Mã (duy nhất, không phân biệt hoa thường), tên, mô tả, đang dùng hay không, người tạo / sửa, thời gian |
| Bảng `admin_department` | Admin thuộc phòng ban nào. **Một admin được thuộc nhiều phòng ban** |
| API | `department/list`, `store`, `update`, `delete` như mọi danh mục khác. Mỗi API là một quyền trong ma trận quyền; vai trò Chủ có sẵn, vai trò Chỉ xem được `department.list` |
| Gán admin | Trong form Quản trị viên, thêm ô chọn phòng ban (`department_ids`, giống cách chọn vai trò) |
| Xóa phòng ban | Được xóa cả khi còn người, chỉ bỏ liên kết. Mọi thay đổi ghi vào nhật ký thay đổi chung |

### 2. Chính sách: thư mục nào phòng ban nào được dùng

| Phần | Đề xuất |
|---|---|
| Bảng `department_policy` | Cặp (phòng ban, thư mục). Lưu theo mã thư mục, nên đổi tên hoặc chuyển thư mục vẫn giữ chính sách. Xóa hẳn thư mục thì chính sách đi theo |
| Thư mục mở | Thư mục **chưa gắn phòng ban nào**: ai có quyền Files cũng dùng được, giống hôm nay. Dữ liệu cũ không đổi gì cho tới khi Chủ khóa một thư mục |
| Thư mục khóa | Thư mục đã gắn ít nhất một phòng ban: chỉ thành viên của **một trong** các phòng ban đó (và vai trò Chủ) mới thấy và dùng được nó **cùng mọi thứ bên trong** |
| Khóa lồng nhau | Thư mục khóa nằm trong thư mục khóa khác: phải được **cả hai** cho phép (khóa cha thì con cũng khóa) |
| File ở ngoài cùng (không trong thư mục) | Luôn mở |
| Vai trò Chủ | Luôn thấy hết, như với quyền |
| Nơi cấp quyền | Ở màn hình Files: mỗi thư mục có nút **Access** chọn phòng ban; thư mục khóa có biểu tượng ổ khóa. Thêm một quyền mới `media.access` cho việc này |

### 3. Chỗ nào bị chặn

Không được phép thì trả **404** như khi thư mục không tồn tại, để không lộ là có thư mục đó.

| Chỗ | Cách chặn |
|---|---|
| Danh sách Files, cột Evidence | Không hiện thư mục / file bị khóa với người đó |
| Tải về, xem trước, ảnh nhỏ | 404 |
| Upload, tạo thư mục con | 404 nếu thư mục đích bị khóa |
| Đổi tên, chuyển, xóa | Cả chỗ cũ và chỗ mới đều phải được phép |
| Thùng rác | Chỉ hiện và khôi phục file của thư mục được phép |
| Upload dở | Chỉ người bắt đầu upload mới tiếp tục được (như hiện nay); thư mục bị khóa sau đó thì không ghép tiếp được |
| Hộp chọn file trong form bằng chứng | Chỉ hiện file được phép. File đã gắn sẵn vẫn hiện tên, nhưng không mở được nếu bị khóa |
| Trang public | **Không đổi**: file gắn vào bằng chứng công khai vẫn mở được, vì người xem không đăng nhập. Muốn giấu thì bỏ công khai bằng chứng |

### 4. Chia việc

| Việc | Gồm |
|---|---|
| Phòng ban, phần máy chủ | Bảng, API, gán admin, quyền, nhật ký, test |
| Phòng ban, giao diện | Màn hình Phòng ban (menu Master Data), ô chọn phòng ban trong form Quản trị viên |
| Chính sách, phần máy chủ | Bảng chính sách, API cấp thư mục, chặn mọi chỗ ở mục 3, test từng chỗ |
| Chính sách, giao diện | Nút Access, ổ khóa, Files và hộp chọn file chỉ hiện chỗ được phép |
| Test trình duyệt và tài liệu | Một luồng: Chủ khóa thư mục cho phòng ban A, admin phòng ban B không thấy, admin phòng ban A thấy; file `docs/features/phong-ban.md` |

## Được và mất

| | |
|---|---|
| Được | Chia Files theo phòng ban thật sự (không chỉ lưu như bản cũ); dữ liệu hiện có không đổi; vai trò vẫn quyết định "làm được gì", phòng ban quyết định "trên thư mục nào" |
| Mất | Mỗi lần gọi Files thêm một truy vấn lấy danh sách thư mục khóa (nhỏ: số thư mục khóa thường ít); mọi API Files phải đi qua kiểm tra mới, quên một chỗ là lộ, nên mỗi chỗ ở mục 3 có test riêng; thêm 3 bảng và 1 quyền |

## Quyết định của chủ dự án (2026-10-17)

| # | Câu hỏi | Chọn |
|---|---|---|
| 1 | Admin chưa thuộc phòng ban nào có thấy thư mục khóa không | **Không thấy**: chỉ dùng được thư mục mở |
| 2 | Một admin thuộc nhiều phòng ban | **Có** |
| 3 | Khóa lồng nhau | Phải được **cả cha lẫn con** cho phép |
| 4 | Nơi cấp thư mục cho phòng ban | **Màn hình Files** (nút Access trên thư mục) |
| 5 | Trang public vẫn mở file gắn vào bằng chứng công khai dù thư mục bị khóa | **Có**, giữ như hiện nay |
