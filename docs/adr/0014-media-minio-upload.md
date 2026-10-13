# ADR-0014 — Quản lý file trên MinIO, upload thẳng từ trình duyệt, việc nền và thông báo tức thì bằng Reverb

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-13, chủ dự án xác nhận) |
| Ngày | 2026-10-13 |
| Người quyết định | Chủ dự án |
| Thay thế | ADR-0007 (bỏ API media, chỉ giữ file đã lưu) |

## Bối cảnh

[ADR-0012](0012-restore-features-dev-first.md) chốt lấy lại tính năng quản lý file. Hiện trạng trên dev ngày 2026-10-13:

| Mục | Tình trạng |
|---|---|
| MinIO | Chạy, có kho chính `media-official` (giữ bản cũ 30 ngày) và kho tạm `media-temp` (tự xóa sau 1 ngày). Dữ liệu gần như trống (472 KB) |
| Bảng `media_mgmt` | Còn, 0 dòng |
| Thư viện S3, hàng đợi việc nền, Reverb | Đã gỡ theo ADR-0007, không còn container chạy việc nền hay việc hẹn giờ |
| nginx | Chặn request lớn hơn 50 MB |

Kết quả rà cấu hình MinIO:

| Phát hiện | Xử lý |
|---|---|
| Kho chính cho ai cũng đọc được: biết đường dẫn là tải được, kể cả file riêng tư | Tắt; xem và tải file bằng link tạm do API cấp sau khi kiểm tra quyền |
| Unique index `unique_virtual_path` không chặn được trùng tên vì `workspace_id` luôn rỗng (PostgreSQL coi hai giá trị rỗng là khác nhau) | Thay bằng index chỉ tính file chưa xóa: trong một thư mục không có hai file cùng tên |
| Tài khoản backend trên MinIO thiếu quyền `s3:ListMultipartUploadParts` | Thêm vào `docker/minio/create-buckets.sh` |
| Giữ bản cũ 30 ngày ở kho chính, kho tạm tự xóa sau 1 ngày, MinIO tự dọn phần upload dở sau 24 giờ | Giữ nguyên |

## Quyết định

### Upload

| Hạng mục | Quyết định |
|---|---|
| Đường đi của file | Trình duyệt gửi thẳng lên MinIO bằng link ký tạm (presigned URL). Laravel chỉ kiểm tra quyền, cấp link, lưu thông tin file. File không đi qua PHP |
| Kho nhận file | Gửi thẳng vào **kho chính** (phương án B), không qua kho tạm nên không phải chép lại file vài GB. Kho tạm giữ nguyên, để dành cho việc khác |
| File nhỏ (dưới 100 MB) | Gửi một lần |
| File lớn (100 MB đến 10 GB) | Chia phần 16–64 MB (theo công thức trong [ghi chú MinIO cũ](../archive/learning/minio-media-pipeline-notes.md)), gửi 4 phần cùng lúc, mỗi phần lỗi thử lại tối đa 3 lần (chờ lâu dần). Có thanh %, nút hủy. Mỗi phần là một request ngắn nên không bị timeout |
| Tiếp tục upload dở sau khi tải lại trang | Chưa làm |
| Giới hạn | Tối đa 10 GB mỗi file; mọi loại file trừ file chạy được (`.exe`, `.sh`, `.php`, …) |
| Đường tới MinIO từ trình duyệt | Qua nginx cổng 81, đường dẫn `/media-official/...`, giữ nguyên header `Host` để chữ ký khớp; không mở thêm cổng. Nhánh này cho phép request lớn hơn 50 MB |
| Upload treo | Bản ghi ở trạng thái "đang tải lên" quá 24 giờ thì một lệnh hẹn giờ xóa bản ghi và phần đã gửi |
| Xóa file | Đánh dấu đã xóa trong bảng và xóa object; MinIO còn giữ bản cũ 30 ngày để khôi phục |
| Thư viện backend | `league/flysystem-aws-s3-v3` (kéo theo `aws/aws-sdk-php`), cách chính thức để Laravel nói chuyện với S3/MinIO |

### Việc nền và thông báo tức thì

Upload xong chưa phải là xong: server còn phải ghép các phần, kiểm tra dung lượng, đọc kích thước ảnh hoặc thời lượng video, sau này có thể tạo ảnh thu nhỏ hay quét virus. Phần này có thể lâu nên chạy ngầm và báo kết quả khi xong. Cơ chế này làm dùng chung cho mọi tính năng chạy ngầm sau này (nhập từ Obsidian, xuất dữ liệu, sao lưu…).

1. Trình duyệt gửi xong các phần → gọi API "đã xong". API ghi trạng thái **đang xử lý**, đẩy một việc vào hàng đợi, trả lời ngay.
2. Container chạy việc nền lấy việc ra làm, ghi trạng thái **hoàn tất** hoặc **lỗi**.
3. Reverb gửi sự kiện "việc nền đã xong" (loại việc, mã đối tượng, kết quả, lời nhắn) tới kênh riêng của người đã upload. Kênh kiểm tra đăng nhập bằng cookie như các API khác.
4. Chống lỡ thông báo: vừa kết nối Reverb, màn hình gọi API xem trạng thái một lần, nên việc xong trước khi kết nối vẫn hiện kết quả.

| Hạng mục | Quyết định |
|---|---|
| Hàng đợi | Redis có sẵn |
| Container mới | Chạy việc nền (`queue:work`), chạy Reverb, chạy việc hẹn giờ (`schedule:work`) |
| Thư viện | Backend `laravel/reverb`; giao diện `laravel-echo` và `pusher-js` |
| nginx | Thêm nhánh WebSocket cho Reverb ở cổng 81 |

### Dữ liệu và quyền

| Hạng mục | Quyết định |
|---|---|
| Bảng | Dùng lại `media_mgmt` (cột đường dẫn ảo cho thư mục). Migration nhỏ: sửa index chống trùng tên, thêm cột lưu mã upload của file lớn, trạng thái dùng enum PHP |
| Quyền | Nhóm "Quản lý file" trong ma trận quyền (xem, upload, đổi tên / di chuyển, xóa), mỗi API một quyền như [ADR-0013](0013-dynamic-permissions.md). Chủ có tất cả, Chỉ xem chỉ xem |

## Các phương án đã cân nhắc

| Câu hỏi | Chọn | Không chọn |
|---|---|---|
| Kho nhận file lớn | B: thẳng vào kho chính | A: kho tạm rồi việc nền chép sang kho chính — file vài GB phải chép thêm một lần, tốn gấp đôi chỗ lúc chép |
| Báo kết quả việc nền | Reverb, dùng chung cho mọi tính năng | Màn hình hỏi lại liên tục: đơn giản hơn nhưng chậm, tốn request, mỗi tính năng tự làm |
| File qua đâu | Thẳng trình duyệt → MinIO | Qua PHP: nghẽn server, dễ timeout với file lớn |
| Đọc file | Link tạm có kiểm tra quyền | Đọc công khai: ai biết đường dẫn là tải được |

## Hệ quả

- Thêm 3 container và 3 thư viện; `make up` lâu hơn một chút.
- Thứ tự làm: Backend Media upload file nhỏ → **Việc nền và thông báo tức thì** (việc mới, dùng chung) → upload file lớn → giao diện quản lý file.
- Tắt đọc công khai: link cũ trỏ thẳng vào kho chính không còn mở được (hiện không có dữ liệu nào dùng link như vậy).
