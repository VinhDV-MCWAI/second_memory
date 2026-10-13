# Quản lý file

> Trạng thái: **backend upload file nhỏ đã chạy** (thư mục, upload tới 100 MB, tải về, đổi tên, di chuyển, xóa). Chưa có: upload file lớn, thông báo khi xử lý xong, màn hình. Thiết kế: [ADR-0014](../adr/0014-media-minio-upload.md).

## Làm gì

Lưu file cá nhân trên MinIO, sắp xếp theo thư mục; upload file nhỏ và file lớn (tới 10 GB) có thanh %; tải về, đổi tên, di chuyển, xóa. Khi file lớn xử lý xong ở nền, màn hình hiện thông báo ngay.

## Cách chạy (giống môi trường thật)

File **không đi qua PHP**. Trình duyệt gửi và tải file thẳng với MinIO bằng link ký tạm (hết hạn sau 5 phút), qua nginx ở cổng 81.

```
Trình duyệt ──1. xin link──▶ API (kiểm tra quyền, tạo bản ghi "đang tải lên")
Trình duyệt ──2. PUT file──▶ nginx /media-official/… ──▶ MinIO (kiểm tra chữ ký)
Trình duyệt ──3. báo xong──▶ API (hỏi MinIO: file có thật, đúng dung lượng?) ──▶ "hoàn tất"
Trình duyệt ──4. xin link tải──▶ API ──▶ link GET ký tạm, tải về đúng tên file
```

| Tình huống | Kết quả |
|---|---|
| Mở file bằng đường dẫn không có chữ ký | 403: kho không cho đọc công khai |
| Sửa link (đổi thời hạn, đổi file) | 403: chữ ký sai |
| Báo xong khi chưa gửi file | 422 "The file has not been uploaded yet" |
| File gửi lên khác dung lượng đã khai | 422, file bị xóa khỏi MinIO, phải upload lại |
| Trùng tên trong cùng thư mục (không phân biệt hoa thường) | 422; tên của mục đã xóa thì dùng lại được |
| File chạy được (`.exe`, `.sh`, `.php`, `.bat`, `.ps1`, …) | 422, kể cả khi đổi tên thành loại đó |
| Bỏ dở sau khi xin link | Bản ghi "đang tải lên" không hiện trong danh sách, không giữ tên. Lệnh dọn upload treo làm ở bước upload file lớn |
| Xóa | Thư mục xóa kèm mọi thứ bên trong; file trong MinIO bị xóa sau khi DB lưu xong; MinIO còn giữ bản cũ 30 ngày để khôi phục |

## API

Tất cả nằm dưới `/api/admin/media/`. Mỗi API là một quyền trong nhóm **Files** của ma trận quyền.

| API | Làm gì | Quyền | Chỉ xem |
|---|---|---|---|
| `GET list?folder=/Docs&name=…` | Con trực tiếp của thư mục (mặc định `/`), thư mục đứng trước | `media.list` | Được |
| `POST store-folder` `{folder, name}` | Tạo thư mục | `media.store-folder` | Không |
| `POST upload-url` `{folder, name, size, mime_type}` | Xin link PUT cho file ≤ 100 MB; trả `{id, url, method, headers, expires_at}` | `media.upload-url` | Không |
| `POST complete/{id}` | Báo đã gửi xong; trả thông tin file | `media.complete` | Không |
| `GET download-url/{id}` | Xin link tải; trả `{url, expires_at}` | `media.download-url` | Được |
| `PUT update/{id}` `{name?, folder?}` | Đổi tên và/hoặc chuyển thư mục; thư mục mang theo mọi thứ bên trong | `media.update` | Không |
| `POST delete` `{ids}` | Xóa file, thư mục | `media.delete` | Không |

Mọi thay đổi (tạo thư mục, upload xong, đổi tên, chuyển, xóa) ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md) với loại `media`.

## Nơi lưu

| Nơi | Dùng cho |
|---|---|
| Kho `media-official` | File chính thức, tên trong kho là `năm/tháng/uuid.đuôi`; giữ bản cũ 30 ngày |
| Kho `media-temp` | Chưa dùng; tự xóa sau 1 ngày |
| Bảng `media_mgmt` | Tên, đường dẫn ảo (`/Docs/a.pdf`), dung lượng, loại file, trạng thái (0 đang tải lên, 1 đang xử lý, 2 hoàn tất, 3 lỗi), đã xóa hay chưa |

## Cấu hình

| Biến (`laravel-api/.env`) | Nghĩa |
|---|---|
| `AWS_ENDPOINT` | Laravel gọi MinIO trong mạng Docker (`http://ml-minio:9000`) |
| `AWS_PUBLIC_ENDPOINT` | Địa chỉ trình duyệt dùng (`http://localhost:81`); link được ký cho địa chỉ này |
| `AWS_BUCKET` | Kho chính (`media-official`) |
| `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` | Tài khoản `backend-user` chỉ được dùng hai kho (do `make setup` sinh) |

## Code

| Phần | File |
|---|---|
| Route | `laravel-api/routes/api/media.php` |
| Nghiệp vụ | `app/Services/Media/MediaService.php` |
| Nói chuyện với MinIO | `app/Services/Media/ObjectStorage.php` (thư viện `aws/aws-sdk-php`) |
| Truy vấn | `app/Repositories/Media/MediaRepository.php` |
| Test | `tests/Feature/Media/MediaApiTest.php` (MinIO giả trong bộ nhớ: `tests/Fakes/FakeObjectStorage.php`) |
| nginx | nhánh `/media-official/` trong `docker/nginx/default.conf` |
| MinIO | `docker/minio/create-buckets.sh` (kho, quyền, tắt đọc công khai) |

## Các bước làm

| Việc | Trạng thái |
|---|---|
| Backend: kết nối MinIO, thư mục, upload file nhỏ, tải về, xóa | Xong |
| Việc nền và thông báo tức thì (dùng chung) | Chưa |
| Upload file lớn chia phần, xử lý nền, dọn upload treo | Chưa |
| Giao diện quản lý file | Chưa |
