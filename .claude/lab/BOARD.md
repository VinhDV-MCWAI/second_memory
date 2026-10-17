# Bảng việc

Bảng này để `scripts/lane.sh` nhận việc, đánh dấu tiến độ và chặn hai người làm cùng một việc. Kế hoạch: [docs/plan/](../../docs/plan/). Luồng làm việc: [01-goals-and-workflow.md](../../docs/plan/01-goals-and-workflow.md).

**Không sửa cột Trạng thái bằng tay.** Dùng các lệnh:

| Lệnh | Tác dụng |
|---|---|
| `scripts/lane.sh status` | Xem toàn bộ việc và việc nào nhận được ngay |
| `scripts/lane.sh claim <mã>` | Nhận việc: `todo` → `doing` |
| `scripts/lane.sh done <mã> "<commit> — <kết quả một dòng>"` | Xong việc: đánh dấu ở đây và trong `docs/plan/03-backlog.md`, ghi nhật ký vào `PROGRESS.md`, tự commit ba file đó |
| `scripts/lane.sh block <mã> "<lý do>"` | Bị chặn, chờ chủ dự án |
| `scripts/lane.sh add main <mã> "<việc>" "<tài liệu>" "<phụ thuộc>"` | Thêm việc mới phát sinh |

Cột "Phụ thuộc": các mã phải `done` trước thì việc này mới nhận được (`–` = không có). Việc có chữ **[XÁC NHẬN]** dừng lại sau bước đề xuất, chờ chủ dự án trả lời rồi mới làm tiếp.

## Lane `main` — Toàn bộ dự án

Phạm vi: cả repo. Mỗi lúc chỉ làm một việc. Bảng chỉ giữ việc còn mở; việc đã xong được gom vào [05-done.md](../../docs/plan/05-done.md) (rút gọn 2026-10-17, chi tiết cũ: `git log -p -- .claude/lab/BOARD.md`).

| ID | Task | Docs | Depends | Status |
|---|---|---|---|---|
| USER-02 | Quản lý người dùng cuối: API + giao diện + test | [roadmap §4](../../docs/plan/02-roadmap.md) | – | blocked: để sau theo ADR-0015 (chủ dự án 2026-10-14) |
| DEMO-01 | **[XÁC NHẬN]** Dữ liệu mẫu cho bản demo prod-like (tài khoản Chỉ xem + Skill Ledger mẫu) | [infra/README.md](../../infra/README.md) | – | blocked: để sau tới giai đoạn 6 (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| DEMO-02 | **[XÁC NHẬN]** HTTPS cho bản demo prod-like | [infra/README.md](../../infra/README.md) | – | blocked: để sau tới giai đoạn 6 (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| CLEAN-01 | Dọn dẹp: tài liệu lỗi thời, dữ liệu thử trên dev, rác Docker; README tiếng Anh; quy tắc dọn dẹp sau mỗi việc; rút gọn bảng việc đã xong | [01-goals-and-workflow.md](../../docs/plan/01-goals-and-workflow.md) | – | doing since 2026-10-17 16:01 |
