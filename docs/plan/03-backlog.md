# Danh sách việc

Cập nhật: 2026-10-17. Lộ trình: [02-roadmap.md](02-roadmap.md). Việc đã xong: [05-done.md](05-done.md) (bản đầy đủ trước khi rút gọn: `git log -p -- docs/plan/03-backlog.md`).

Mã việc (cột đầu) chỉ để script chia việc `scripts/lane.sh` nhận diện; khi nói chuyện và viết tài liệu thì dùng tên việc. Trạng thái: `todo` → `doing` → `done`. Không sửa cột trạng thái bằng tay. Việc mới: thêm dòng vào bảng của giai đoạn tương ứng, rồi `scripts/lane.sh add main <mã> …` để đưa lên bảng việc.

## Giai đoạn 1–3, 5, 6

Đã xong hết (dọn nền, phân quyền động, quản lý file, CI/CD, nâng cấp trên dev). Xem [05-done.md](05-done.md).

## 4. Người dùng cuối, phòng ban, chính sách

Phòng ban và khóa thư mục Files: xong 2026-10-17.

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| USER-02 | Quản lý người dùng cuối: API + giao diện + test. Sẽ được thay bằng tài khoản người dùng cuối của trang cho người dùng (PUB-03, PUB-04) | Màn hình người dùng cuối | blocked: để sau (ADR-0015) |

## 7. Sau cùng

Các mục còn lại của giai đoạn này (triển khai, vận hành, sự cố, hiệu năng, portfolio, CI/CD chỉ khi chủ dự án yêu cầu): bảng "Sau cùng" trong [02-roadmap.md](02-roadmap.md#7-sau-cùng-chi-tiết-hóa-khi-tới).

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| DEMO-01 | **[XÁC NHẬN]** Dữ liệu mẫu cho bản demo prod-like (tài khoản Chỉ xem + Skill Ledger mẫu) | Bản demo mở ra có dữ liệu | blocked: để sau (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| DEMO-02 | **[XÁC NHẬN]** HTTPS cho bản demo prod-like | Bản demo chạy HTTPS | blocked: để sau (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |

## 8. Luyện quy trình làm phần mềm kiểu doanh nghiệp

[ADR-0018](../adr/0018-lifecycle-practice-track.md). Không gồm CI/CD.

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| REL-01 | Đóng gói và bàn giao bản `v2.2.0`: ghi chú phát hành cho mọi việc từ `v2.1.0`, biên bản nghiệm thu (chạy kiểm tra, thử trên trình duyệt), tài liệu bàn giao, nhìn lại giai đoạn, tag trên máy (chủ dự án push) | `docs/releases/v2.2.0.md` + tag | todo |
| PROC-01 | **[XÁC NHẬN]** Sổ tay quy trình bản mới (tiếng Việt, gọn, hai tốc độ) từ sổ tay cũ; trả lời câu hỏi có khôi phục vai giả lập PO, QA, Ops để tạo đầu vào hay không | `docs/handbook/` được dùng thật | todo |

## 9. Trang cho người dùng

[ADR-0019](../adr/0019-public-user-frontend.md). Làm theo vòng đời đầy đủ của giai đoạn 8.

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| PUB-01 | **[XÁC NHẬN]** Chọn công nghệ thay `nextjs-docs` (ADR-0019); nếu chọn ứng dụng mới thì thử nhanh và đo RAM, image, JS so với Next.js | Phương án được chọn, có số đo | done (5e8fb014 — chọn Astro; đo: RAM production 76 → 35 MB, dev 499 → 133 MB, image 301 → 248 MB, JS xuống trình duyệt 579 KB → 0) |
| PUB-02 | Làm lại trang public trên công nghệ đã chọn (danh sách, chi tiết kỹ năng, mở file), chuyển test trình duyệt, nginx, xóa `nextjs-docs` và mọi chỗ nhắc tới | Trang public nhẹ hơn, như cũ về tính năng | done (5ef7f391, fd743a67, c16bf04b — trang public chạy trên Astro (public-site), /docs chuyển hướng 301 về /skills, xóa nextjs-docs; make verify pass (280 backend, 155 giao diện, 63 importer, astro check 0 lỗi), make e2e 9/9; dọn: container và 3 image cũ, file tạm terraform init) |
| PUB-03 | **[XÁC NHẬN]** Tài khoản người dùng cuối: hồ sơ yêu cầu, câu hỏi làm rõ, thiết kế (bảng, đăng ký hay admin tạo, đăng nhập riêng tách khỏi admin), API, test | Người dùng đăng nhập được trên trang cho người dùng | todo |
| PUB-04 | Lịch sử đã xem, yêu thích, lưu xem sau (API + giao diện + test); admin xem và khóa tài khoản người dùng | 3 tính năng cho người dùng đã đăng nhập | todo |
