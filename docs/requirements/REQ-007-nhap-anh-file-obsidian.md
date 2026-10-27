# REQ-007 — Nhập ảnh và file trong vault Obsidian vào Files khi `make import`

| | |
|---|---|
| Người yêu cầu | Chủ dự án |
| Ngày nhận | 2026-10-27 |
| Cỡ việc | Vừa: thêm API cho công cụ nhập, sửa dịch vụ nhập bài, sửa công cụ Python; không đổi cấu trúc DB, không thêm loại quyền |
| Trạng thái | Chủ dự án duyệt thiết kế (phương án A) ngày 2026-10-27; **hoãn làm** tới khi vault có bài thật nhúng ảnh / file (lúc duyệt: 0 ảnh, 0 ghi chú `publish: true`) |
| Tài liệu liên quan | [nhap-tu-obsidian.md](../features/nhap-tu-obsidian.md), [ledger-import.md](../runbooks/ledger-import.md), [quan-ly-file.md](../features/quan-ly-file.md) |

## 1. Vì sao

Ghi chú Obsidian nhúng ảnh và file bằng `![[hinh.png]]`, nhưng `make import` hiện biến chúng thành chữ, nên bài trên trang public mất ảnh. Chủ dự án muốn viết trong Obsidian là đủ, không phải upload tay vào Files rồi sửa bài.

## 2. Thay đổi

- `make import` tìm ảnh / file mà các ghi chú được đăng nhúng (`![[hinh.png]]`, `![[tai-lieu.pdf]]`), gửi lên máy chủ trước khi gửi ghi chú.
- Máy chủ lưu chúng vào Files, trong thư mục `/Obsidian/` theo đúng đường dẫn trong vault. File không đổi thì không gửi lại; file đổi nội dung thì thay nội dung, giữ nguyên mã file.
- Trong nội dung bài, `![[hinh.png]]` thành `![[file:{id}]]` (cú pháp nhúng file đã có), nên ảnh hiện trên trang public và cột **Used in** của Files thấy bài đang dùng.
- File không còn ghi chú nào nhúng thì **giữ lại**, chỉ liệt kê trong kết quả; không tự xóa.
- Chạy thử (`dry=1`) báo sẽ gửi / thay / bỏ qua file nào, không ghi gì.

Không làm: file trên 100 MB; tự xóa file không còn dùng; đồng bộ ngược từ Files về vault; cú pháp ảnh Markdown `![](duong/dan.png)` (Obsidian mặc định dùng `![[…]]`, thêm sau nếu vault thật cần).

## 3. Hiện trạng

- Công cụ nhập (`tools/ledger-importer/`, Python) đọc vault, gửi **một** yêu cầu `POST /api/admin/article/import` chứa mọi ghi chú (tối đa 2.000 ghi chú, 45 MB). Máy chủ (`ArticleImportService`) tự giải link `[[…]]` / `![[…]]`; link tới ảnh hiện thành chữ.
- Token nhập chỉ có khóa `article:import`. API Files dùng link ký tạm để trình duyệt PUT thẳng lên MinIO qua nginx; PHP không nhận nội dung file.
- Bài nhúng file bằng dòng `![[file:{id}]]`; bảng `article_media` theo dõi file nào bài đang nhúng. Bài sửa trong trang quản trị cập nhật bảng này; đường nhập hiện **không** cập nhật.
- `media_mgmt` có sẵn đường dẫn ảo, chặn tên trùng trong thư mục, cột `metadata`.

## 4. Thiết kế

### 4.1. Cách đưa file lên máy chủ — chọn A

| Phương án | Cách | Lợi | Hại |
|---|---|---|---|
| **A. API nhận file riêng cho công cụ nhập** (đã chọn) | `POST /api/admin/article/import-files`, multipart, mỗi lần một file; cùng token `article:import`. PHP ghi vào MinIO bằng `ObjectStorage::putObject` có sẵn | Không mở API Files cho token; công cụ chỉ cần một kiểu gọi; chạy thử dễ (máy chủ so mã băm, trả "sẽ tạo / thay / không đổi") | File đi qua PHP: phải nâng giới hạn kích thước request cho đúng route này (100 MB) |
| B. Cho token dùng API Files | Thêm khóa `media.*` vào token, công cụ đi đúng luồng link ký tạm của trình duyệt | Không có đường upload mới | Token nhập mạnh hơn nhiều (đọc / xóa được mọi file), chạm phân quyền (đang dừng); công cụ phải làm 3 bước mỗi file |
| C. Gói file vào chính yêu cầu nhập | base64 trong JSON | Một yêu cầu | Phình 33%, vướng giới hạn 45 MB ngay với vài ảnh |

### 4.2. Định danh file

Đường dẫn ảo `/Obsidian/<đường dẫn trong vault>` là định danh (đã duy nhất trong Files). Mã băm SHA-256 của nội dung lưu trong `metadata.sha256`. Công cụ gửi trước danh sách `{đường dẫn, kích thước, sha256}`; máy chủ trả file nào cần gửi, nên lần chạy lại không gửi lại file không đổi.

Đổi tên / chuyển file trong vault = đường dẫn mới → file mới; file cũ còn lại, nằm trong danh sách "không còn dùng" (giống cách bài viết bị ẩn khi đổi tên ghi chú).

### 4.3. Thay nội dung file đã có

Ghi đè đối tượng trong MinIO theo khóa cũ (kho có lưu phiên bản nên bản cũ khôi phục được 30 ngày), cập nhật dung lượng, mã băm, rồi đẩy lại việc đọc kích thước ảnh / ảnh nhỏ (`ReadMediaInfoJob`). Mã file giữ nguyên nên bài không phải sửa.

### 4.4. Thư mục `/Obsidian/` bị khóa theo phòng ban

Nếu chủ dự án khóa thư mục này, bài công khai không nhúng được file trong đó (luật `ensureEmbeddable`). Máy chủ từ chối cả lần nhập với thông báo rõ thay vì nhập bài thiếu ảnh.

### 4.5. Rủi ro

| Rủi ro | Cách xử lý |
|---|---|
| File lớn đi qua PHP chiếm bộ nhớ | Đọc theo luồng từ file tạm của request lên MinIO; giới hạn 100 MB chỉ cho route này trong nginx và PHP |
| Chủ dự án sửa / xóa file trong `/Obsidian/` bằng tay | Lần nhập sau tạo lại file đã xóa (nếu ghi chú còn nhúng) và ghi đè nội dung đã sửa; tài liệu ghi rõ "sửa trong Obsidian, không sửa trong Files", như với bài viết |
| Nhập dở giữa chừng: file đã lên, ghi chú lỗi | File đã lên vẫn đúng và lần sau "không đổi"; không cần quay lui |
| Ảnh nhỏ / kích thước đọc chậm | Như upload thường, chạy ở nền |

Quay lui: bỏ route mới và phần công cụ; file đã nhập nằm trong `/Obsidian/`, xóa qua Files như file thường. Không có dữ liệu cũ phải chuyển.

## 5. Tiêu chí nghiệm thu

| # | Tình huống | Kết quả mong đợi |
|---|---|---|
| 1 | Ghi chú được đăng có `![[anh/so-do.png]]`, Files chưa có file này | Files có `/Obsidian/anh/so-do.png` đúng nội dung; kết quả in `files created: 1` |
| 2 | File chỉ được ghi chú không `publish: true` nhúng | Không gửi lên máy chủ |
| 3 | `sach/Chuong 1.md` nhúng `![[so-do.png]]`, vault có `sach/so-do.png` và `khac/so-do.png` | Bài dùng `sach/so-do.png` (đúng đường dẫn trước, rồi tên file; trùng tên thì cùng thư mục, rồi đường dẫn ngắn nhất) |
| 4 | Nhúng `![[khong-co.png]]` mà vault không có | Dòng đó còn chữ như hiện nay, tên được liệt kê trong kết quả |
| 5 | Nhập ghi chú công khai có `![[so-do.png\|Sơ đồ]]` | Trang public hiện ảnh với chú thích "Sơ đồ"; Files ghi bài ở cột Used in |
| 6 | Chạy `make import` hai lần liền, không sửa vault | Lần hai in `files unchanged: N`, không file mới, không ghi gì vào MinIO |
| 7 | Thay nội dung `so-do.png` rồi chạy lại | Nội dung mới, cùng mã file, in `files updated: 1` |
| 8 | Xóa dòng nhúng khỏi ghi chú rồi chạy lại | File vẫn còn trong Files, được liệt kê ở mục "không còn dùng" |
| 9 | `make import vault=… dry=1` với một ảnh mới | In `files created: 1`, Files không đổi |
| 10 | Ghi chú được đăng nhúng video 300 MB, hoặc file loại chạy được | Công cụ dừng trước khi gửi, báo tên file và lý do; không bài nào thay đổi |
| 11 | Token nhập gọi `GET /api/admin/media/list` | 403 như hiện nay |

## 6. Các bước khi làm

1. **Máy chủ nhận file**
   1. Route `POST /api/admin/article/import-files/plan` (danh sách `{path, size, sha256}` → file nào tạo / thay / không đổi, file `/Obsidian/` không còn dùng) và `POST /api/admin/article/import-files` (một file, multipart), chỉ token `article:import`; feature test: token nhập gọi được, token thường và token nhập gọi API Files bị 403.
   2. Lưu vào MinIO bằng `ObjectStorage::putObject`, tạo / cập nhật dòng `media_mgmt` trong `/Obsidian/…` (tạo thư mục cha), lưu `metadata.sha256`, đẩy `ReadMediaInfoJob`; test với `FakeObjectStorage`: tạo mới, thay nội dung giữ mã file, không đổi thì không ghi.
   3. Từ chối file > 100 MB và loại chạy được (dùng lại luật của Files); nâng giới hạn request chỉ cho route này trong nginx dev; test 422 và một lần gửi thật file 50 MB qua `localhost:81`.
   4. Ghi nhật ký thay đổi (`via = importer`) cho file tạo / thay.
   5. `make openapi`; cập nhật `quan-ly-file.md` (thư mục `/Obsidian/`, sửa trong Obsidian thay vì Files).
2. **Máy chủ nhúng file trong bài nhập**
   1. `ArticleImportService` nhận thêm bảng `{đường dẫn vault → mã file}`, đổi `![[anh.png|chú thích]]` thành `![[file:{id}|chú thích]]` theo luật tìm của Obsidian; test các tình huống 3, 4.
   2. Cập nhật `article_media` cho bài nhập như khi sửa bài trong trang quản trị; từ chối cả lần nhập khi `/Obsidian/` bị khóa theo phòng ban.
   3. Cập nhật `nhap-tu-obsidian.md` (bảng link, quy tắc đồng bộ).
3. **Công cụ nhập (Python)**
   1. Tìm file được ghi chú `publish: true` nhúng, tính SHA-256, kiểm 100 MB / loại file trước khi gửi gì; pytest trên vault mẫu.
   2. Gọi `plan`, gửi từng file cần gửi, rồi gửi ghi chú kèm bảng mã file; `dry=1` chỉ gọi `plan`; in `files created / updated / unchanged` và file không còn dùng; pytest với máy chủ giả, `make importer-test`, `make importer-lint`.
   3. Cập nhật `ledger-import.md` (cách chạy, kết quả mới, hoàn tác).
4. **Kiểm tra cả luồng**
   1. Bản chép vault có 1 ảnh, 1 PDF: `make import dry=1` rồi chạy thật hai lần (lần hai toàn "unchanged"); xem ảnh trên trang public, Used in trong Files; dọn: xóa file thử qua Files (xóa → thùng rác → xóa vĩnh viễn), ẩn / xóa bài thử, thu hồi token.
   2. `scripts/lane.sh run make verify`.
