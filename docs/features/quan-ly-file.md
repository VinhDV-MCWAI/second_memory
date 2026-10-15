# Quản lý file

> Trạng thái: **chạy đủ** (màn hình, thư mục, upload file nhỏ và file lớn tới 10 GB, tiếp tục upload dở, xem trước, tải về, đổi tên, di chuyển, xóa, thùng rác, dọn upload treo). Thông báo khi file lớn xử lý xong dùng [việc nền và thông báo tức thì](viec-nen-va-thong-bao.md). Thiết kế: [ADR-0014](../adr/0014-media-minio-upload.md).

## Làm gì

Lưu file cá nhân trên MinIO, sắp xếp theo thư mục; upload file nhỏ và file lớn (tới 10 GB) có thanh %; tải về, đổi tên, di chuyển, xóa. Khi file lớn xử lý xong ở nền, màn hình hiện thông báo ngay.

## Màn hình

Menu **Files** (`/admin/files`, cần quyền `media.list`).

| Thao tác | Cách làm | Quyền |
|---|---|---|
| Mở thư mục | Bấm tên thư mục; thanh đường dẫn phía trên để quay lại. Thư mục đang mở nằm trên URL (`?folder=/Docs`), nên nút Back của trình duyệt và gửi link đều được | `media.list` |
| Lọc theo tên | Ô "Filter by name" | `media.list` |
| Tạo thư mục | Nút **New folder** | `media.store-folder` |
| Upload | Nút **Upload** (chọn nhiều file) hoặc **kéo-thả file vào trang**; file vào thư mục đang mở | `media.upload-url` + `media.multipart-start` (và các quyền `media.multipart-*`, `media.complete`) |
| Theo dõi upload | Khung **Uploads**: %, đã gửi / tổng, nút X để hủy; file lớn hiện "Processing in the background…" rồi "Done" khi việc nền báo về | — |
| Xem trước | Bấm tên file hoặc nút con mắt: ảnh, PDF, video, nhạc, file chữ hiện ngay trong hộp thoại, có nút tải về. Chi tiết ở mục [Xem trước](#xem-trước) | `media.download-url` |
| Tải về | Nút tải ở dòng file (link tạm 5 phút, tải đúng tên file) | `media.download-url` |
| Đổi tên / Di chuyển | Nút bút chì / nút chuyển thư mục; di chuyển bằng cách gõ đường dẫn thư mục đích (ví dụ `/Docs/2026`) | `media.update` |
| Xóa | Nút thùng rác ở dòng, hoặc tick nhiều dòng rồi **Delete (n)**; hỏi lại trước khi xóa. File vào [thùng rác](#thùng-rác) 30 ngày | `media.delete` |
| Thùng rác | Nút **Trash** (`/admin/files/trash`) | `media.trash-list` |
| Xem file đang gắn vào bằng chứng nào | Cột **Evidence**: kẹp giấy + số lần gắn. Rê chuột vào file thấy tên các bằng chứng; bấm vào số của một file thì mở màn hình Evidence chỉ hiện các bằng chứng dùng file đó (cần quyền `evidence.list`); thư mục đếm các file bên trong (không tính file đã xóa). Xóa file / thư mục đang được gắn thì hộp thoại xóa cảnh báo thêm số liên kết sẽ hiện "Deleted" | `media.list` |

Nút nào không có quyền thì không hiện (API vẫn chặn bằng 403). Vai trò Chỉ xem: xem và tải về.

Upload chạy lần lượt từng file (file lớn đã tự gửi 4 phần cùng lúc).

### Thùng rác

File đã xóa nằm trong thùng rác **30 ngày**, sau đó MinIO tự xóa hẳn và dòng đó biến khỏi thùng rác. Mỗi ngày lúc 0 giờ, lệnh `media:prune-trash` (container `ml-scheduler`) xóa hẳn khỏi bảng `media_mgmt` mọi dòng đã xóa quá 30 ngày (file, thư mục, upload hỏng), kèm liên kết của file đó với bằng chứng, và ghi `purged` vào nhật ký. Chạy tay: `docker exec ml-php php artisan media:prune-trash`.

| Thao tác | Kết quả | Quyền |
|---|---|---|
| Xem | Tên, thư mục cũ, dung lượng, ngày xóa, ai xóa, còn mấy ngày. Lọc theo tên | `media.trash-list` (vai trò Chỉ xem cũng có) |
| **Restore** | File về đúng thư mục cũ. Thư mục cũ đã bị xóa thì tự tạo lại (cả các thư mục cha). Tên đã có file khác dùng thì đặt thành `tên (restored).đuôi`, rồi `tên (restored 2).đuôi`, … | `media.trash-restore` |
| **Delete forever** (từng file hoặc tick nhiều) | Hỏi lại, rồi xóa hẳn: không khôi phục được nữa | `media.trash-delete` |
| **Empty trash** | Hỏi lại, rồi xóa hẳn mọi file trong thùng rác | `media.trash-empty` |

| Ghi chú | |
|---|---|
| Chỉ liệt kê file | Thư mục đã xóa không hiện; khôi phục file bên trong là thư mục được tạo lại |
| Ngày xóa | Lấy từ cột "cập nhật lúc" của file (dòng đã xóa không bị sửa nữa), không thêm cột mới |
| Cách MinIO giữ file | Kho chính bật lưu phiên bản. Xóa chỉ đặt một "dấu đã xóa", dữ liệu vẫn còn. Khôi phục là gỡ dấu đó. Xóa vĩnh viễn là xóa mọi phiên bản. Luật trong `docker/minio/ilm-history.json`: phiên bản cũ mất sau 30 ngày |
| Trước ngày 2026-10-15 | Luật cũ giữ 5 phiên bản cũ gần nhất **mãi mãi**, nên file đã xóa không bao giờ được giải phóng dung lượng. Đã sửa |
| Nhật ký thay đổi | Khôi phục ghi `restored`, xóa vĩnh viễn ghi `purged` |

### Xem trước

Chỉ xem trước những loại file trình duyệt hiển thị mà **không chạy code**. File được phục vụ từ cùng địa chỉ với trang quản trị (cổng 81), nên một file `.html` hay `.svg` mở thẳng ra có thể chạy JavaScript như chính người đang đăng nhập. Vì vậy:

| Loại | Đuôi file | Cách hiện |
|---|---|---|
| Ảnh | jpg, jpeg, png, gif, webp, avif, bmp | Ảnh, co vừa khung |
| PDF | pdf | Trình xem PDF của trình duyệt |
| Video | mp4, m4v, webm, ogv | Trình phát, tua được (link sống 1 giờ để xem video dài) |
| Nhạc | mp3, m4a, wav, ogg, oga, flac | Trình phát |
| Chữ (tối đa 2 MB) | txt, md, csv, tsv, log, json, yml, yaml, ini, env, sql, js, ts, py, java, go, rs, css | Đọc nội dung rồi hiện dạng chữ thuần, không hiểu thẻ HTML |
| Còn lại (html, svg, xml, zip, docx, …) | | Không có nút xem trước, chỉ tải về |

Loại nội dung trả về do server chọn theo đuôi file, không tin loại trình duyệt khai lúc upload. Đổi tên `a.html` thành `a.txt` thì file hiện dạng chữ, không chạy. MinIO và nginx đều gửi header `X-Content-Type-Options: nosniff` để trình duyệt không tự đoán lại loại file.

### Tiếp tục upload dở (file trên 100 MB)

Tải lại trang, đóng tab hay mất mạng giữa chừng thì các phần đã gửi vẫn nằm trên MinIO. Lần sau mở màn hình Files sẽ thấy khung **Unfinished uploads**: tên file, thư mục, % đã gửi.

| Thao tác | Kết quả |
|---|---|
| Bấm **Resume** rồi chọn lại đúng file đó | Chỉ gửi các phần còn thiếu. Phần bị cắt giữa chừng (gửi chưa đủ) thì gửi lại phần đó |
| Upload lại đúng file đó (cùng tên, cùng dung lượng) vào đúng thư mục cũ | Tự tiếp tục, không phải bấm Resume |
| Chọn file khác tên hoặc khác dung lượng | Báo lỗi "Choose the same file: …", không gửi gì |
| Chọn file cùng tên, cùng dung lượng nhưng là file khác (trên cùng trình duyệt đã bắt đầu upload) | Báo lỗi "This is not the file the upload was started with", vì trộn phần của hai file sẽ ra file hỏng. Trình duyệt nhớ thời điểm sửa file lúc bắt đầu; tiếp tục từ máy khác thì không kiểm tra được điều này |
| Bấm nút thùng rác của dòng | Hỏi lại, rồi xóa các phần đã gửi |
| Bấm X (hủy) khi đang upload | Xóa các phần đã gửi ngay, không vào danh sách dở |
| Để đó | Quá 24 giờ không động tới thì lệnh dọn upload treo xóa. Mỗi lần bấm tiếp tục tính lại 24 giờ |

Trình duyệt không tự mở lại được file của máy, nên phải chọn lại file. File dưới 100 MB gửi một lần, không tiếp tục được: upload lại từ đầu.

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
| Lỗi khác, hoặc hết lượt thử | Dừng hẳn: các phần khác cũng thôi gửi. Giữ các phần đã gửi để [tiếp tục sau](#tiếp-tục-upload-dở-file-trên-100-mb) |
| Bấm hủy | Gọi API hủy: MinIO xóa các phần đã gửi |

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
| Bỏ dở sau khi xin link (đóng trình duyệt, mất mạng) | Bản ghi "đang tải lên" không hiện trong danh sách, không giữ tên. File lớn thì hiện ở khung Unfinished uploads để tiếp tục. Lệnh `media:clean-stuck-uploads` chạy mỗi giờ (container `ml-scheduler`) hủy các upload không động tới quá 24 giờ, xóa phần đã gửi |
| Báo xong file lớn khi thiếu phần | 422 "Some parts of the file are missing" |
| MinIO không ghép được (ETag sai) | Việc nền lỗi ngay, không thử lại; thông báo đỏ "The uploaded parts could not be joined into one file"; bản ghi bị ẩn, tên được trả lại |
| Người khác gọi xin link / tiếp tục / hủy upload của mình | 404: chỉ người bắt đầu upload mới thấy, tiếp tục hoặc hủy được |
| Tiếp tục một upload mà MinIO đã xóa phần (hết hạn, bị dọn) | 404; dòng đó bị ẩn khỏi danh sách dở ở lần tải sau |
| Xóa | Thư mục xóa kèm mọi thứ bên trong; file trong MinIO bị xóa sau khi DB lưu xong; file vào thùng rác 30 ngày |
| Khôi phục file mà MinIO đã xóa hẳn | 422 "This file is no longer kept and cannot be restored" |
| Khôi phục file đã quá 30 ngày | 404 |

## API

Tất cả nằm dưới `/api/admin/media/`. Mỗi API là một quyền trong nhóm **Files** của ma trận quyền.

| API | Làm gì | Quyền | Chỉ xem |
|---|---|---|---|
| `GET list?folder=/Docs&name=…` | Con trực tiếp của thư mục (mặc định `/`), thư mục đứng trước. Mỗi file có ô `preview`: `image`, `pdf`, `video`, `audio`, `text` hoặc null (chỉ tải về); `attached_count` (số liên kết với bằng chứng, thư mục đếm file bên trong), `used_by` (`[{id, title}]` các bằng chứng dùng file) | `media.list` | Được |
| `POST store-folder` `{folder, name}` | Tạo thư mục | `media.store-folder` | Không |
| `POST upload-url` `{folder, name, size, mime_type}` | Xin link PUT cho file ≤ 100 MB; trả `{id, url, method, headers, expires_at}` | `media.upload-url` | Không |
| `POST complete/{id}` | Báo đã gửi xong; trả thông tin file | `media.complete` | Không |
| `POST multipart/start` `{folder, name, size, mime_type}` | Bắt đầu upload file lớn (tới 10 GB); trả `{id, part_size, part_count}` | `media.multipart-start` | Không |
| `POST multipart/part-urls/{id}` `{part_numbers}` | Link PUT cho tối đa 100 phần | `media.multipart-part-urls` | Không |
| `POST multipart/complete/{id}` `{parts: [{part_number, etag}]}` | Báo đã gửi đủ; trả trạng thái việc nền (`type = media.process`, `subject_id` = id file) | `media.multipart-complete` | Không |
| `POST multipart/abort/{id}` | Hủy upload, xóa các phần đã gửi | `media.multipart-abort` | Không |
| `GET multipart/unfinished` | Các upload file lớn của mình còn dở: `{id, name, folder, size, mime_type, uploaded_bytes, updated_at}` | `media.multipart-unfinished` | Được (luôn rỗng vì không upload được) |
| `POST multipart/resume/{id}` | Tiếp tục: trả `{id, part_size, part_count, parts: [{part_number, etag, size}]}` là các phần MinIO đang giữ; tính lại 24 giờ trước khi bị dọn | `media.multipart-resume` | Không |
| `GET download-url/{id}` | Xin link tải; trả `{url, expires_at}`. Thêm `?inline=1` để lấy link xem trước (chỉ khi ô `preview` của file khác null, nếu không 422 "This file type cannot be previewed") | `media.download-url` | Được |
| `PUT update/{id}` `{name?, folder?}` | Đổi tên và/hoặc chuyển thư mục; thư mục mang theo mọi thứ bên trong | `media.update` | Không |
| `POST delete` `{ids}` | Xóa file, thư mục (file vào thùng rác) | `media.delete` | Không |
| `GET trash/list?name=…` | File trong thùng rác, mới xóa đứng trước: `{id, name, path, size, mime_type, deleted_at, deleted_by, expires_at}` | `media.trash-list` | Được |
| `POST trash/restore/{id}` | Khôi phục; trả thông tin file (đường dẫn mới nếu phải đổi tên) | `media.trash-restore` | Không |
| `POST trash/delete` `{ids}` | Xóa vĩnh viễn các file trong thùng rác; trả số file đã xóa | `media.trash-delete` | Không |
| `POST trash/empty` | Dọn sạch thùng rác; trả số file đã xóa | `media.trash-empty` | Không |

File có thể được [đính kèm vào bằng chứng](skill-ledger.md#file-đính-kèm-bằng-chứng) của Skill Ledger. Xóa file đang đính kèm thì bằng chứng vẫn giữ và đánh dấu "Deleted"; khôi phục từ thùng rác là hiện lại; xóa vĩnh viễn thì tự gỡ khỏi bằng chứng.

Mọi thay đổi (tạo thư mục, upload xong, đổi tên, chuyển, xóa) ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md) với loại `media`.

## Nơi lưu

| Nơi | Dùng cho |
|---|---|
| Kho `media-official` | File chính thức, tên trong kho là `năm/tháng/uuid.đuôi`; bật lưu phiên bản, phiên bản cũ (file đã xóa) mất sau 30 ngày |
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
| Dọn dòng đã xóa quá 30 ngày | `app/Console/Commands/PruneTrashCommand.php`, `MediaTrashService::pruneExpired()`; test `tests/Feature/Media/PruneTrashCommandTest.php` |
| Truy vấn | `app/Repositories/Media/MediaRepository.php` |
| Test | `tests/Feature/Media/{MediaApiTest,MultipartUploadApiTest}.php` (MinIO giả trong bộ nhớ: `tests/Fakes/FakeObjectStorage.php`) |
| Bộ upload phía trình duyệt | `nextjs-fe/src/features/media/lib/uploader.ts` (`uploadFile(file, {folder, onProgress, signal})`) |
| Màn hình | `nextjs-fe/src/app/admin/files/page.tsx`, `src/features/media/components/` (trình duyệt file, khung upload, hộp thoại), `src/features/media/hooks/` (danh sách, hàng chờ upload) |
| Test e2e | `e2e/tests/files.spec.ts` (thư mục, file nhỏ, file 120 MB qua việc nền, đổi tên, tải về, xóa) |
| nginx | nhánh `/media-official/` trong `docker/nginx/default.conf` |
| MinIO | `docker/minio/create-buckets.sh` (kho, quyền, tắt đọc công khai) |

## Các bước làm

| Việc | Trạng thái |
|---|---|
| Backend: kết nối MinIO, thư mục, upload file nhỏ, tải về, xóa | Xong |
| [Việc nền và thông báo tức thì](viec-nen-va-thong-bao.md) (dùng chung) | Xong |
| Upload file lớn chia phần, xử lý nền, dọn upload treo | Xong |
| Giao diện quản lý file | Xong |
| Tiếp tục upload dở sau khi tải lại trang | Xong |
| Xem trước ảnh, PDF, video, nhạc, file chữ | Xong |
| Thùng rác 30 ngày, khôi phục, xóa vĩnh viễn, dọn hằng ngày | Xong |
| Gắn file vào bằng chứng của [Skill Ledger](skill-ledger.md), cột Evidence ở màn hình Files | Xong |
| Bằng chứng chỉ có file, không cần link | Xong |
| Bấm số ở cột Evidence để mở các bằng chứng dùng file | Xong |
