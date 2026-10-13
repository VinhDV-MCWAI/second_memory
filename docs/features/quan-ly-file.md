# Quản lý file

> Trạng thái: **đã thiết kế, chưa có code**. Thiết kế đầy đủ: [ADR-0014](../adr/0014-media-minio-upload.md). File này được viết tiếp khi từng phần chạy được.

## Làm gì

Lưu file cá nhân trên MinIO, sắp xếp theo thư mục; upload file nhỏ và file lớn (tới 10 GB) có thanh %; tải về, đổi tên, di chuyển, xóa. Khi file lớn xử lý xong ở nền, màn hình hiện thông báo ngay.

## Cách chạy

| Bước | Ai làm |
|---|---|
| Xin link upload | Màn hình gọi API; API kiểm tra quyền, cấp link ký tạm |
| Gửi file | Trình duyệt gửi thẳng lên MinIO qua nginx (`/media-official/...`). File lớn chia phần, gửi 4 phần cùng lúc |
| Báo đã gửi xong | Màn hình gọi API; API ghi trạng thái "đang xử lý", đẩy việc vào hàng đợi |
| Xử lý sau upload | Container việc nền kiểm tra file, đọc thông tin (kích thước ảnh, thời lượng video), ghi "hoàn tất" hoặc "lỗi" |
| Báo kết quả | Reverb gửi thông báo tới người đã upload |
| Xem, tải file | API cấp link tạm sau khi kiểm tra quyền; kho không cho đọc công khai |
| Dọn upload treo | Lệnh hẹn giờ xóa upload "đang tải lên" quá 24 giờ |

## Nơi lưu

| Nơi | Dùng cho |
|---|---|
| Kho `media-official` | File chính thức; giữ bản cũ 30 ngày để khôi phục khi xóa nhầm |
| Kho `media-temp` | Chưa dùng; tự xóa sau 1 ngày |
| Bảng `media_mgmt` | Tên, thư mục (đường dẫn ảo), dung lượng, loại file, trạng thái upload |

## Quyền

Nhóm "Quản lý file" trong ma trận quyền: xem, upload, đổi tên / di chuyển, xóa. Chủ có tất cả, Chỉ xem chỉ xem.

## Các bước làm

| Việc | Kết quả |
|---|---|
| Backend: kết nối MinIO, thư mục, upload file nhỏ, tải về, xóa | Upload file nhỏ chạy được |
| Việc nền và thông báo tức thì (dùng chung) | Hàng đợi, Reverb, kênh riêng mỗi người |
| Upload file lớn chia phần, xử lý nền, dọn upload treo | Upload file vài GB chạy được |
| Giao diện quản lý file | Màn hình quản lý file |
