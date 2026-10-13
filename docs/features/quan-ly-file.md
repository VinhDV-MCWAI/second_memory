# Quản lý file

> Trạng thái: **backend chạy đủ** (thư mục, upload file nhỏ tới 100 MB, upload file lớn tới 10 GB chia phần, tải về, đổi tên, di chuyển, xóa, dọn upload treo) và bộ upload phía trình duyệt. Chưa có: màn hình. Thông báo khi file lớn xử lý xong dùng [việc nền và thông báo tức thì](viec-nen-va-thong-bao.md). Thiết kế: [ADR-0014](../adr/0014-media-minio-upload.md).

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

### File lớn (trên 100 MB, tới 10 GB): chia phần

```
Trình duyệt ──1. bắt đầu──▶ API: tạo upload chia phần trong MinIO, trả cỡ phần và số phần
Trình duyệt ──2. xin link từng đợt (tối đa 100 phần/lần, link sống 10 phút)──▶ API
Trình duyệt ──3. PUT từng phần, 4 phần cùng lúc──▶ nginx ──▶ MinIO; giữ ETag của mỗi phần
Trình duyệt ──4. báo xong kèm danh sách ETag──▶ API: file chuyển "đang xử lý", đẩy việc nền, trả lời ngay
                 ml-queue: MinIO ghép các phần → kiểm tra dung lượng → "hoàn tất"
Trình duyệt ◀──5. thông báo tức thì "<tên file> is ready"── Reverb
```

| Cỡ file | Cỡ mỗi phần |
|---|---|
| tới 100 MB | Không chia, gửi một lần |
| 100–500 MB | 16 MB |
| 500 MB – 10 GB | 32 MB (file 10 GB = 320 phần) |

| Lỗi khi gửi một phần | Bộ upload làm gì |
|---|---|
| Mạng lỗi, MinIO trả 5xx | Thử lại tối đa 3 lần, chờ 1 giây rồi 2 giây (cộng ngẫu nhiên tới 0,1 giây để nhiều phần không thử cùng lúc) |
| 403 (link hết hạn vì mạng chậm) | Xin link mới cho phần đó rồi thử lại |
| Lỗi khác, hoặc hết lượt thử, hoặc bấm hủy | Gọi API hủy: MinIO xóa các phần đã gửi, tên file được trả lại |

Đã thử thật với file 3 GiB qua `localhost:81`: 96 phần, gửi trong 44 giây (~70 MiB/s), việc nền ghép và kiểm tra trong khoảng 1 giây, tải về khớp mã SHA-256.

### Các tình huống

| Tình huống | Kết quả |
|---|---|
| Mở file bằng đường dẫn không có chữ ký | 403: kho không cho đọc công khai |
| Sửa link (đổi thời hạn, đổi file) | 403: chữ ký sai |
| Báo xong khi chưa gửi file | 422 "The file has not been uploaded yet" |
| File gửi lên khác dung lượng đã khai | 422, file bị xóa khỏi MinIO, phải upload lại |
| Trùng tên trong cùng thư mục (không phân biệt hoa thường) | 422; tên của mục đã xóa thì dùng lại được |
| File chạy được (`.exe`, `.sh`, `.php`, `.bat`, `.ps1`, …) | 422, kể cả khi đổi tên thành loại đó |
| Bỏ dở sau khi xin link (đóng trình duyệt, mất mạng) | Bản ghi "đang tải lên" không hiện trong danh sách, không giữ tên. Lệnh `media:clean-stuck-uploads` chạy mỗi giờ (container `ml-scheduler`) hủy các upload không động tới quá 24 giờ, xóa phần đã gửi |
| Báo xong file lớn khi thiếu phần | 422 "Some parts of the file are missing" |
| MinIO không ghép được (ETag sai) | Việc nền lỗi ngay, không thử lại; thông báo đỏ "The uploaded parts could not be joined into one file"; bản ghi bị ẩn, tên được trả lại |
| Người khác gọi xin link / hủy upload của mình | 404: chỉ người bắt đầu upload mới tiếp tục hoặc hủy được |
| Xóa | Thư mục xóa kèm mọi thứ bên trong; file trong MinIO bị xóa sau khi DB lưu xong; MinIO còn giữ bản cũ 30 ngày để khôi phục |

## API

Tất cả nằm dưới `/api/admin/media/`. Mỗi API là một quyền trong nhóm **Files** của ma trận quyền.

| API | Làm gì | Quyền | Chỉ xem |
|---|---|---|---|
| `GET list?folder=/Docs&name=…` | Con trực tiếp của thư mục (mặc định `/`), thư mục đứng trước | `media.list` | Được |
| `POST store-folder` `{folder, name}` | Tạo thư mục | `media.store-folder` | Không |
| `POST upload-url` `{folder, name, size, mime_type}` | Xin link PUT cho file ≤ 100 MB; trả `{id, url, method, headers, expires_at}` | `media.upload-url` | Không |
| `POST complete/{id}` | Báo đã gửi xong; trả thông tin file | `media.complete` | Không |
| `POST multipart/start` `{folder, name, size, mime_type}` | Bắt đầu upload file lớn (tới 10 GB); trả `{id, part_size, part_count}` | `media.multipart-start` | Không |
| `POST multipart/part-urls/{id}` `{part_numbers}` | Link PUT cho tối đa 100 phần | `media.multipart-part-urls` | Không |
| `POST multipart/complete/{id}` `{parts: [{part_number, etag}]}` | Báo đã gửi đủ; trả trạng thái việc nền (`type = media.process`, `subject_id` = id file) | `media.multipart-complete` | Không |
| `POST multipart/abort/{id}` | Hủy upload, xóa các phần đã gửi | `media.multipart-abort` | Không |
| `GET download-url/{id}` | Xin link tải; trả `{url, expires_at}` | `media.download-url` | Được |
| `PUT update/{id}` `{name?, folder?}` | Đổi tên và/hoặc chuyển thư mục; thư mục mang theo mọi thứ bên trong | `media.update` | Không |
| `POST delete` `{ids}` | Xóa file, thư mục | `media.delete` | Không |

Mọi thay đổi (tạo thư mục, upload xong, đổi tên, chuyển, xóa) ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md) với loại `media`.

## Nơi lưu

| Nơi | Dùng cho |
|---|---|
| Kho `media-official` | File chính thức, tên trong kho là `năm/tháng/uuid.đuôi`; giữ bản cũ 30 ngày |
| Kho `media-temp` | Chưa dùng; tự xóa sau 1 ngày |
| Bảng `media_mgmt` | Tên, đường dẫn ảo (`/Docs/a.pdf`), dung lượng, loại file, trạng thái (0 đang tải lên, 1 đang xử lý, 2 hoàn tất, 3 lỗi), mã upload chia phần (`upload_id`, xóa khi ghép xong), đã xóa hay chưa |

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
| Upload chia phần, dọn upload treo | `app/Services/Media/MultipartUploadService.php`, `app/Jobs/Media/ProcessUploadJob.php`, `app/Console/Commands/CleanStuckUploadsCommand.php` (lịch trong `routes/console.php`) |
| Truy vấn | `app/Repositories/Media/MediaRepository.php` |
| Test | `tests/Feature/Media/{MediaApiTest,MultipartUploadApiTest}.php` (MinIO giả trong bộ nhớ: `tests/Fakes/FakeObjectStorage.php`) |
| Bộ upload phía trình duyệt | `nextjs-fe/src/features/media/lib/uploader.ts` (`uploadFile(file, {folder, onProgress, signal})`) |
| nginx | nhánh `/media-official/` trong `docker/nginx/default.conf` |
| MinIO | `docker/minio/create-buckets.sh` (kho, quyền, tắt đọc công khai) |

## Các bước làm

| Việc | Trạng thái |
|---|---|
| Backend: kết nối MinIO, thư mục, upload file nhỏ, tải về, xóa | Xong |
| [Việc nền và thông báo tức thì](viec-nen-va-thong-bao.md) (dùng chung) | Xong |
| Upload file lớn chia phần, xử lý nền, dọn upload treo | Xong |
| Giao diện quản lý file | Chưa |
