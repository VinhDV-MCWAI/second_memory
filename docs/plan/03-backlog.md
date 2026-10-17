# Danh sách việc

Cập nhật: 2026-10-17. Lộ trình: [02-roadmap.md](02-roadmap.md). Việc đã xong: [05-done.md](05-done.md) (bản đầy đủ trước khi rút gọn: `git log -p -- docs/plan/03-backlog.md`).

Mã việc (cột đầu) chỉ để script chia việc `scripts/lane.sh` nhận diện; khi nói chuyện và viết tài liệu thì dùng tên việc. Trạng thái: `todo` → `doing` → `done`. Không sửa cột trạng thái bằng tay. Việc mới: thêm dòng vào bảng của giai đoạn tương ứng, rồi `scripts/lane.sh add main <mã> …` để đưa lên bảng việc.

## Giai đoạn 1–3, 5, 6

Đã xong hết (dọn nền, phân quyền động, quản lý file, CI/CD, nâng cấp trên dev). Xem [05-done.md](05-done.md).

## 4. Người dùng cuối, phòng ban, chính sách

Phòng ban và khóa thư mục Files: xong 2026-10-17.

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| USER-02 | Quản lý người dùng cuối: API + giao diện + test | Màn hình người dùng cuối | blocked: để sau (ADR-0015) |

## 7. Sau cùng

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| DEMO-01 | **[XÁC NHẬN]** Dữ liệu mẫu cho bản demo prod-like (tài khoản Chỉ xem + Skill Ledger mẫu) | Bản demo mở ra có dữ liệu | blocked: để sau (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| DEMO-02 | **[XÁC NHẬN]** HTTPS cho bản demo prod-like | Bản demo chạy HTTPS | blocked: để sau (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
