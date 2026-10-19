# Danh sách việc

Cập nhật: 2026-10-17. Lộ trình: [02-roadmap.md](02-roadmap.md). Việc đã xong: [05-done.md](05-done.md) (bản đầy đủ trước khi rút gọn: `git log -p -- docs/plan/03-backlog.md`).

Mã việc (cột đầu) chỉ để script chia việc `scripts/lane.sh` nhận diện; khi nói chuyện và viết tài liệu thì dùng tên việc. Trạng thái: `todo` → `doing` → `done`. Không sửa cột trạng thái bằng tay. Việc mới: thêm dòng vào bảng của giai đoạn tương ứng, rồi `scripts/lane.sh add main <mã> …` để đưa lên bảng việc.

## Giai đoạn 1–3, 5, 6

Đã xong hết (dọn nền, phân quyền động, quản lý file, CI/CD, nâng cấp trên dev). Xem [05-done.md](05-done.md).

## 4. Người dùng cuối, phòng ban, chính sách

Phòng ban và khóa thư mục Files: xong 2026-10-17.

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| USER-02 | Quản lý người dùng cuối: API + giao diện + test. Sẽ được thay bằng tài khoản người dùng cuối của trang cho người dùng (PUB-03, PUB-04) | Màn hình người dùng cuối | done (thay bằng tài khoản người dùng trên trang public (PUB-03, PUB-04, ADR-0020)) |

## 7. Sau cùng

Các mục còn lại của giai đoạn này (triển khai, vận hành, sự cố, hiệu năng, portfolio, CI/CD chỉ khi chủ dự án yêu cầu): bảng "Sau cùng" trong [02-roadmap.md](02-roadmap.md#7-sau-cùng-chi-tiết-hóa-khi-tới).

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| DEMO-01 | **[XÁC NHẬN]** Dữ liệu mẫu cho bản demo prod-like (tài khoản Chỉ xem + Skill Ledger mẫu) | Bản demo mở ra có dữ liệu | blocked: để sau (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| DEMO-02 | **[XÁC NHẬN]** HTTPS cho bản demo prod-like | Bản demo chạy HTTPS | blocked: để sau (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| TEST-01 | Tìm và sửa nguyên nhân 3 test tạo admin thỉnh thoảng lỗi khi chạy cả bộ (lỗi đã biết của `v2.3.0`) | Test tạo admin pass ổn định | done (cacffc59 — nguyên nhân: kiểm tra email admin hỏi DNS thật, mạng chập chờn thì 3 test lỗi; tắt khi chạy test (EMAIL_DNS_CHECK), thêm test cho lúc bật; tái hiện bằng cắt DNS: trước 3 lỗi, sau pass; 312 backend pass; dọn: make clean, container tạm --rm) |
| I18N-01 | Kiểm tra tự động câu chữ dịch bị thiếu trong trang quản trị, chạy trong `make lint` (sau lỗi thiếu câu chữ ở ô tải ảnh đại diện 2026-10-19) | Thiếu câu chữ dịch thì `make lint` báo lỗi | done (c136ff73 — test tự quét trang quản trị, thiếu câu chữ dịch thì make fe-test / make verify / make ci báo lỗi kèm tên file; đặt trong test thay vì make lint (không phải thêm script, sửa Makefile); thử lại lỗi ô ảnh đại diện: test bắt đúng; 161 test giao diện pass; dọn: make clean) |
| OPS-01 | **[XÁC NHẬN]** Đề xuất mục Sự cố: gây lỗi giả lập, báo cáo sự cố, diễn tập khôi phục dữ liệu (cả file MinIO) có đo thời gian | Đề xuất được chủ dự án duyệt | todo |

## 8. Luyện quy trình làm phần mềm kiểu doanh nghiệp

[ADR-0018](../adr/0018-lifecycle-practice-track.md). Không gồm CI/CD.

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| REL-01 | Đóng gói và bàn giao bản `v2.2.0`: ghi chú phát hành cho mọi việc từ `v2.1.0`, biên bản nghiệm thu (chạy kiểm tra, thử trên trình duyệt), tài liệu bàn giao, nhìn lại giai đoạn, tag trên máy (chủ dự án push) | `docs/releases/v2.2.0.md` + tag | done (67075790 — ghi chú phát hành v2.2.0 (nổi bật, phá vỡ tương thích, nâng cấp, trước/sau, nghiệm thu 10 bước, bàn giao, lỗi đã biết, nhìn lại); tag v2.2.0 trên máy, chủ dự án push) |
| PROC-01 | **[XÁC NHẬN]** Sổ tay quy trình bản mới (tiếng Việt, gọn, hai tốc độ) từ sổ tay cũ; trả lời câu hỏi có khôi phục vai giả lập PO, QA, Ops để tạo đầu vào hay không | `docs/handbook/` được dùng thật | done (8c1e1daf — sổ tay quy trình tiếng Việt 7 chương trong docs/handbook/, mỗi chương có mục kinh nghiệm làm việc với từng vai; không khôi phục vai giả lập (chủ dự án quyết); dùng thật ở REL-02; dọn: không tạo dữ liệu hay container) |
| REL-02 | Đóng gói bản `v2.3.0` (tài khoản người dùng trên trang public, yêu thích, xem sau, màn hình End users) **theo sổ tay mới**: ghi chú phát hành, biên bản nghiệm thu, tài liệu bàn giao, nhìn lại vòng đời đầy đủ, tag trên máy (chủ dự án push). Phần cuối của [REQ-003](../requirements/REQ-003-tai-khoan-nguoi-dung.md) mục 9 | `docs/releases/v2.3.0.md` + tag | doing |

## 9. Trang cho người dùng

[ADR-0019](../adr/0019-public-user-frontend.md). Làm theo vòng đời đầy đủ của giai đoạn 8.

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| PUB-01 | **[XÁC NHẬN]** Chọn công nghệ thay `nextjs-docs` (ADR-0019); nếu chọn ứng dụng mới thì thử nhanh và đo RAM, image, JS so với Next.js | Phương án được chọn, có số đo | done (5e8fb014 — chọn Astro; đo: RAM production 76 → 35 MB, dev 499 → 133 MB, image 301 → 248 MB, JS xuống trình duyệt 579 KB → 0) |
| PUB-02 | Làm lại trang public trên công nghệ đã chọn (danh sách, chi tiết kỹ năng, mở file), chuyển test trình duyệt, nginx, xóa `nextjs-docs` và mọi chỗ nhắc tới | Trang public nhẹ hơn, như cũ về tính năng | done (5ef7f391, fd743a67, c16bf04b — trang public chạy trên Astro (public-site), /docs chuyển hướng 301 về /skills, xóa nextjs-docs; make verify pass (280 backend, 155 giao diện, 63 importer, astro check 0 lỗi), make e2e 9/9; dọn: container và 3 image cũ, file tạm terraform init) |
| PUB-03 | **[XÁC NHẬN]** Tài khoản người dùng cuối: hồ sơ yêu cầu, câu hỏi làm rõ, thiết kế (bảng, đăng ký hay admin tạo, đăng nhập riêng tách khỏi admin), API, test | Người dùng đăng nhập được trên trang cho người dùng | done (f93409cf, 83b25a64, 6129dce8, 136688ac, 54b77fe1, bd551842 — tài khoản người dùng trên trang public: đăng ký, đăng nhập (bộ đăng nhập riêng, không gọi được API quản trị), đổi mật khẩu, tự xóa tài khoản; ADR-0020; make verify pass (299 backend, 158 giao diện, 63 importer), make e2e 10/10) |
| PUB-04 | Yêu thích, lưu xem sau (API + giao diện + test); admin xem và khóa tài khoản người dùng, bật / tắt đăng ký (lịch sử đã xem: chủ dự án bỏ 2026-10-17) | 2 danh sách cho người dùng đã đăng nhập, màn hình End users | done (83b25a64, 6129dce8, 136688ac, 54b77fe1 — yêu thích, xem sau (nút ☆ 🔖, trang My lists, ~3 KB JS), màn hình End users (khóa, đặt lại mật khẩu, xóa, bật/tắt đăng ký); bỏ lịch sử đã xem theo chủ dự án; dọn: dist build thử, e2e tự xóa tài khoản thử) |
| PUB-05 | Đăng nhập người dùng bằng tên đăng nhập, bỏ email ([REQ-004](../requirements/REQ-004-trang-chu-danh-muc.md)) | Đăng ký, đăng nhập bằng tên đăng nhập; tài khoản cũ dùng phần trước `@` | done (b92a70fc — đăng ký, đăng nhập bằng tên đăng nhập; tài khoản cũ lấy phần trước @ của email; 20 test người dùng pass; ghi chú: migration đã chạy trên DB dev (không có tài khoản người dùng nào)) |
| CMS-01 | Danh mục và bài viết: bảng, API quản trị, API public, lưu bài viết, dữ liệu mẫu ([ADR-0021](../adr/0021-public-home-categories-articles.md)) | API đủ, có test | done (0b9a2f21 — bảng danh mục + bài viết, 8 API quản trị, 3 API public (Markdown → HTML an toàn), lưu bài viết, 10 danh mục mẫu × 2 bài trên dev; 311 test backend pass) |
| CMS-02 | Màn hình quản trị Categories, Articles | Thêm, sửa, ẩn / hiện, xóa trên giao diện | done (2ecf677e — menu Public content → Categories, Articles: thêm, sửa, ẩn/hiện, thứ tự, xóa, lịch sử thay đổi, link mở trang public; 160 test giao diện pass) |
| PUB-06 | Trang public về gốc `/`: trang chủ danh mục, trang danh mục, bài viết; trang quản trị vào `/admin`; nginx; chuyển hướng đường dẫn cũ | Mở http://localhost:81/ thấy đủ danh mục | done (74f1bd47 — trang public ở gốc / (trang chủ 11 thẻ: Skills + 10 danh mục), /guides/{danh mục}/{bài}, quản trị dưới /admin (/admin/login), chuyển hướng đường dẫn cũ, Astro --ignore-lock; make verify pass (311 backend, 160 giao diện, 63 importer), make e2e 11/11) |
