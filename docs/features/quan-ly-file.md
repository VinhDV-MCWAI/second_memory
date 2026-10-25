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
| Đổi tên / Di chuyển | Nút bút chì / nút chuyển thư mục; di chuyển bằng cách chọn thư mục đích trong cây thư mục (bấm ▸ để mở thư mục con; một thư mục không chuyển được vào chính nó hay thư mục con của nó) | `media.update` |
| Xóa | Nút thùng rác ở dòng, hoặc tick nhiều dòng rồi **Delete (n)**; hỏi lại trước khi xóa. File vào [thùng rác](#thùng-rác) 30 ngày | `media.delete` |
| Thùng rác | Nút **Trash** (`/admin/files/trash`) | `media.trash-list` |
| Khóa thư mục theo phòng ban | Gắn thư mục cho phòng ban: chỉ thành viên (và vai trò Chủ) thấy và dùng được thư mục cùng mọi thứ bên trong. Luật và các chỗ bị chặn ở [phòng ban](phong-ban.md#khóa-thư-mục-trong-files). Nút chìa khóa (**Access**) ở dòng thư mục để chọn phòng ban; thư mục khóa có ổ khóa màu vàng cạnh tên | `media.access` + `department.list` |
| Xem kích thước ảnh | Cột dung lượng và dòng dưới tên trong hộp xem trước hiện thêm rộng × cao (ví dụ `2.0 MB · 1920 × 1080`) với ảnh jpg, png, gif, webp, avif, bmp. Đọc từ 1 MB đầu của file ngay khi upload xong; ảnh hỏng không đọc được thì chỉ hiện dung lượng, upload vẫn thành công. Ảnh upload trước ngày 2026-10-15: chạy `docker exec ml-php php artisan media:read-image-sizes` (chạy lại bao nhiêu lần cũng được) | `media.list` |
| Xem ảnh nhỏ, thời lượng | Ảnh (jpg, png, gif, webp, avif, bmp) và video (mp4, m4v, webm, ogv) có ảnh nhỏ cạnh tên file; video và nhạc (mp3, m4a, wav, ogg, oga, flac) có thời lượng cạnh dung lượng, ví dụ `45 MB · 1280 × 720 · 3:12`. Có cả trong hộp chọn file (Chèn file của bài viết, đính kèm của danh mục). Tạo ngầm vài giây sau khi upload xong, màn hình tự hỏi lại tới khi có (tối đa 30 giây). Chi tiết ở mục [Ảnh nhỏ và thời lượng](#ảnh-nhỏ-và-thời-lượng) | `media.list` |
| Xem file đang dùng ở đâu | Cột **Used in**: các danh mục đính kèm file (**Category:** tên) và các bài viết nhúng file bằng `![[file:…]]` (**Article:** tên). Trống là chưa dùng ở đâu. Từ 2026-10-22 ([ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md)) | `media.list` |

Nút nào không có quyền thì không hiện (API vẫn chặn bằng 403). Vai trò Chỉ xem: xem và tải về.

Upload chạy lần lượt từng file (file lớn đã tự gửi 4 phần cùng lúc).

### Thùng rác

File đã xóa nằm trong thùng rác **30 ngày**, sau đó MinIO tự xóa hẳn và dòng đó biến khỏi thùng rác. Mỗi ngày lúc 0 giờ, lệnh `media:prune-trash` (container `ml-scheduler`) xóa hẳn khỏi bảng `media_mgmt` mọi dòng đã xóa quá 30 ngày (file, thư mục, upload hỏng), kèm đính kèm danh mục và liên kết nhúng trong bài viết của file đó, và ghi `purged` vào nhật ký. Chạy tay: `docker exec ml-php php artisan media:prune-trash`.

| Thao tác | Kết quả | Quyền |
|---|---|---|
| Xem | Ảnh nhỏ (nếu có), tên, thư mục cũ, dung lượng, ngày xóa, ai xóa, còn mấy ngày. Lọc theo tên | `media.trash-list` (vai trò Chỉ xem cũng có) |
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

### Ảnh nhỏ và thời lượng

Từ 2026-10-15. Upload xong thì một việc nền (container `ml-queue`, không hiện thông báo) dùng **ffmpeg** đọc file trên MinIO qua link ký tạm, chỉ tải những đoạn cần, không tải cả file:

| Loại | Kết quả |
|---|---|
| Video | Thời lượng, rộng × cao, ảnh nhỏ là khung hình ở giây thứ 1 (video ngắn hơn thì khung đầu) |
| Nhạc | Thời lượng |
| Ảnh | Ảnh nhỏ. Ảnh trên 50 triệu điểm ảnh thì bỏ qua để không tràn bộ nhớ của worker |

| Ghi chú | |
|---|---|
| Ảnh nhỏ | JPEG rộng tối đa 320 px, lưu trong kho chính tại `thumbnails/<tên file trong kho>.jpg`, tên ghi trong cột `metadata` |
| File ffmpeg không đọc được | Upload vẫn thành công, chỉ không có ảnh nhỏ và thời lượng. Lý do ghi vào log (`ffprobe could not read a file`) |
| An toàn | Mỗi lần chạy ffmpeg tối đa 60 giây. ffmpeg chỉ được đọc file qua mạng (http) và chỉ với các định dạng video, nhạc, ảnh thông thường: một file giả làm video nhưng bên trong là danh sách phát (trỏ tới file khác trên máy) bị từ chối |
| Xóa | Ảnh nhỏ ở lại cùng file (không bị đặt dấu đã xóa), nên thùng rác vẫn hiện được (từ 2026-10-17). Xóa vĩnh viễn hoặc lệnh dọn hằng ngày thì xóa luôn ảnh nhỏ |
| Trang public | File đính kèm danh mục công khai cũng hiện ảnh nhỏ, xem [Trang public](trang-public.md) |
| File upload trước ngày 2026-10-15 | `docker exec ml-php php artisan media:read-media-info`. Chạy lại bao nhiêu lần cũng được: file đã đọc (kể cả không đọc được) được bỏ qua |
| Image PHP | Có thêm ffmpeg nên image bản dev nặng 433 MB (trước là 252 MB) |

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
| `GET list?folder=/Docs&name=…` | Con trực tiếp của thư mục (mặc định `/`), thư mục đứng trước. Mỗi file có ô `preview`: `image`, `pdf`, `video`, `audio`, `text` hoặc null (chỉ tải về); `used_in` (`[{kind, id, title}]`: `kind` là `category` cho danh mục đính kèm file, `article` cho bài viết nhúng file) | `media.list` | Được |
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

File có thể được đính kèm vào danh mục hoặc nhúng trong bài viết ([danh mục và bài viết](bai-viet.md)). Xóa file vào thùng rác thì trang public thôi hiện nó; khôi phục là hiện lại; xóa vĩnh viễn thì tự gỡ khỏi đính kèm và khỏi danh sách file nhúng của bài.

Mọi thay đổi (tạo thư mục, upload xong, đổi tên, chuyển, xóa) ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md) với loại `media`.

## Nơi lưu

| Nơi | Dùng cho |
|---|---|
| Kho `media-official` | File chính thức, tên trong kho là `năm/tháng/uuid.đuôi`; bật lưu phiên bản, phiên bản cũ (file đã xóa) mất sau 30 ngày |
| Kho `media-temp` | Chưa dùng; tự xóa sau 1 ngày |
| Bảng `media_mgmt` | Tên, đường dẫn ảo (`/Docs/a.pdf`), dung lượng, rộng × cao, thời lượng, `metadata` (tên ảnh nhỏ), loại file, trạng thái (0 đang tải lên, 1 đang xử lý, 2 hoàn tất, 3 lỗi), mã upload chia phần (`upload_id`, xóa khi ghép xong), đã xóa hay chưa |

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
| Kích thước ảnh, ảnh nhỏ, thời lượng | `MediaService::imageSize()`, `app/Services/Media/MediaInfoService.php` (gọi ffprobe / ffmpeg), `app/Jobs/Media/ReadMediaInfoJob.php`, lệnh `media:read-image-sizes` và `media:read-media-info`; test `tests/Feature/Media/{ImageSizeTest,MediaInfoTest}.php` (ffmpeg giả bằng `Process::fake()`) |
| Truy vấn | `app/Repositories/Media/MediaRepository.php` |
| Test | `tests/Feature/Media/{MediaApiTest,MultipartUploadApiTest}.php` (MinIO giả trong bộ nhớ: `tests/Fakes/FakeObjectStorage.php`) |
| Bộ upload phía trình duyệt | `nextjs-fe/src/features/media/lib/uploader.ts` (`uploadFile(file, {folder, onProgress, signal})`) |
| Màn hình | `nextjs-fe/src/app/admin/files/page.tsx`, `src/features/media/components/` (trình duyệt file, khung upload, hộp thoại), `src/features/media/hooks/` (danh sách, hàng chờ upload) |
| Test e2e | `e2e/tests/files.spec.ts` (thư mục, file nhỏ, file 120 MB qua việc nền, đổi tên, tải về, xóa; xem trước; ảnh nhỏ và thời lượng của ảnh và video thật) |
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
| Gắn file vào bằng chứng của Skill Ledger, cột Evidence ở màn hình Files | Xong, bỏ cùng Skill Ledger (2026-10-20) |
| Cột Used in: danh mục đính kèm file, bài viết nhúng file | Xong (2026-10-22) |
| Kích thước ảnh (rộng × cao) | Xong |
| Thời lượng video, nhạc; ảnh nhỏ của ảnh và video | Xong |
