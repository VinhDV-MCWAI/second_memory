# Diễn tập sự cố 2026-10-20

Mục "Sự cố" trong giai đoạn Sau cùng của [lộ trình](../plan/02-roadmap.md). Chủ dự án duyệt ngày 2026-10-20: khôi phục vào DB tạm và bucket tạm trên dev, cho dừng tạm hàng đợi và Redis, làm cả ba tình huống. Trước khi diễn tập còn gặp một sự cố thật: [cả trang không vào được sau khi máy khởi động lại](2026-10-20-trang-sap-sau-khoi-dong-lai.md).

## Tóm tắt

| Tình huống | Người dùng thấy gì | Bật lại mất | Mất dữ liệu | Phát hiện mới |
|---|---|---|---|---|
| 1. Mất dữ liệu, khôi phục từ bản sao lưu | (giả lập, không ảnh hưởng ai) | Sao lưu dưới 1 giây, khôi phục 5 giây (dữ liệu dev 212 KB) | Không, trừ file trong thùng rác | **File trong thùng rác không được sao lưu** |
| 2. Hàng đợi việc nền dừng | File upload xong nằm ở "đang xử lý" mãi, không báo lỗi | 2 giây sau khi bật lại | Không (dừng dưới 24 giờ) | Không có gì báo động; dừng quá 24 giờ thì file lớn đang chờ sẽ hỏng |
| 3. Redis dừng | Trang public bình thường; trang quản trị mọi thao tác lỗi 500, không đăng nhập được | 2 giây, admin vẫn giữ đăng nhập | Không | Trên dev, lỗi hiện chi tiết bên trong (tên máy, cổng) vì bật debug |

## Tình huống 1: mất dữ liệu, khôi phục từ bản sao lưu

**Cách làm:** theo [hướng dẫn sao lưu và khôi phục](../runbooks/backup-restore.md). Sao lưu `--local-only` (chưa cài rclone), khôi phục vào DB `restore_check` và bucket `restore-check`, không đụng dữ liệu dev.

| Bước | Kết quả |
|---|---|
| Sao lưu | 212 KB, dưới 1 giây |
| Khôi phục thử | 5 giây, 32 bảng, 93 migration |
| So DB chính và DB khôi phục | **32/32 bảng giống hệt từng dòng** (số dòng + mã băm toàn bộ nội dung) |
| So file MinIO | 2/2 file (ảnh và ảnh nhỏ của nó) giống hệt từng byte |
| Ứng dụng chạy trên bản khôi phục | Một container ứng dụng tạm trỏ vào DB tạm đọc đúng số dòng (5 admin, 5 kỹ năng, 6 bằng chứng, 20 bài viết, 10 danh mục, 1 người dùng, 2 dòng file). Không đọc được file trong bucket tạm, vì tài khoản MinIO của ứng dụng chỉ được quyền trên bucket chính; khôi phục thật dùng bucket chính nên không gặp chuyện này |

**Phát hiện: file trong thùng rác không được sao lưu.** Thùng rác dựa vào tính năng lưu phiên bản của MinIO: xóa chỉ đặt "dấu đã xóa", file thật còn ở phiên bản cũ. Script sao lưu dùng `mc mirror`, chỉ chép phiên bản mới nhất. Ví dụ trên dev: `Do_An_Tot_Nghiep.docx` (11 MB, xóa ngày 2026-10-15) còn trong thùng rác nhưng không có trong bản sao lưu. Sau một lần khôi phục thật, màn hình Thùng rác vẫn hiện tên file nhưng bấm khôi phục sẽ lỗi.

## Tình huống 2: hàng đợi việc nền dừng

**Cách làm:** upload một file 1 MB qua API, đúng đường của file lớn (ghép các phần trong việc nền), so khi hàng đợi chạy và khi `ml-queue` bị dừng.

| Lúc | Kết quả |
|---|---|
| Hàng đợi chạy (đối chứng) | Việc nền xong sau khoảng 1 giây, file ở trạng thái "xong" |
| Dừng `ml-queue`, upload | Sau 1 phút: việc nền vẫn "đang chờ", file vẫn "đang xử lý", việc nằm yên trong Redis. Không có thông báo lỗi hay cảnh báo nào |
| Bật lại `ml-queue` | Việc chạy xong sau 2 giây, không mất việc nào |

**Phát hiện:**
- Không có gì báo khi hàng đợi dừng. Người dùng chỉ thấy file "đang xử lý" mãi.
- Lệnh dọn upload treo (mỗi giờ) chỉ dọn upload chưa xong, không đụng file đang xử lý, nên không gây hại. Nhưng MinIO tự xóa các phần upload chưa ghép sau 24 giờ: hàng đợi dừng quá 24 giờ thì file lớn đang chờ sẽ hỏng, người dùng phải upload lại.
- `ml-queue` tự bật lại nếu bị sập (`restart: unless-stopped`), nhưng không tự bật nếu bị dừng bằng tay hoặc hỏng như sự cố khởi động lại.

## Tình huống 3: Redis dừng

**Cách làm:** đo trang public, trang quản trị và API trước, trong và sau khi dừng `ml-redis`. Trên dev, phiên đăng nhập, hàng đợi và giới hạn số lần gọi đều nằm trên Redis.

| Kiểm tra | Bình thường | Redis dừng |
|---|---|---|
| Trang chủ public, trang Skills | 200 | 200 |
| API danh mục bài viết (public) | 200 | 200 |
| Trang đăng nhập quản trị | 200 | 200 (trang tĩnh) |
| Admin đã đăng nhập: thông tin tài khoản, danh sách kỹ năng | 200 | **500** |
| Đăng nhập mới | được | **500**, mỗi lần chờ 3 giây |

Bật lại Redis: sau 2 giây mọi thứ chạy lại, admin **vẫn giữ đăng nhập** (Redis lưu phiên xuống đĩa).

**Phát hiện:**
- Phần public tách khỏi Redis: tốt.
- Trên dev, lỗi 500 trả về chi tiết bên trong ("getaddrinfo for ml-redis failed … tcp://ml-redis:6379") vì dev bật chế độ debug. Đọc code (`laravel-api/bootstrap/app.php`): khi tắt debug, loại lỗi này rơi vào nhánh "lỗi nội bộ" và chỉ trả thông báo chung. Chưa chạy thử với debug tắt.

## Việc tiếp theo

| Việc | Vì sao | Trạng thái |
|---|---|---|
| Sao lưu cả file trong thùng rác (phiên bản cũ trong MinIO) và trả lại khi khôi phục | Khôi phục thật đang làm mất file trong thùng rác | Xong cùng ngày: khôi phục thử trả file về thùng rác, giống hệt từng byte ([hướng dẫn](../runbooks/backup-restore.md)) |
| Container tự bật lại sau khi WSL khởi động lại; mật khẩu Redis ra khỏi dòng lệnh | [Sự cố khởi động lại](2026-10-20-trang-sap-sau-khoi-dong-lai.md) lặp lại lần thứ ba | Trên bảng việc, chờ duyệt |
| Cảnh báo cơ bản: container dừng hoặc không khỏe, việc nền chờ quá lâu | Cả ba tình huống và sự cố thật đều không có gì báo động | Trên bảng việc (mục Vận hành), chờ duyệt |
| Khôi phục thật đầu-cuối trên một môi trường tạm, đo thời gian | Lần này chỉ khôi phục thử | Để sau, cùng mục Triển khai |

## Dọn dẹp

Đã xóa: DB `restore_check`, bucket `restore-check`, file sao lưu (có chứa mật khẩu), 2 file thử `TMP drill …` (xóa rồi xóa vĩnh viễn qua thùng rác, MinIO bỏ cả file thật), container tạm (`--rm`). Còn lại một dòng upload đã hủy `TMP drill probe.bin` (không có file thật): ứng dụng giữ loại dòng này và lệnh `media:prune-trash` tự xóa sau 30 ngày; quy tắc dự án không cho xóa tay trong bảng file.
