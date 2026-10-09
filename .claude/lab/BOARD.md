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

Phạm vi: cả repo. Mỗi lúc chỉ làm một việc.

| ID | Task | Docs | Depends | Status |
|---|---|---|---|---|
| NEN-01 | Tài liệu môi trường dev | [dev-guide.md](../../docs/dev-guide.md) | – | done 2026-10-09: docs/dev-guide.md |
| NEN-02 | Kế hoạch mới, quy ước tài liệu, quyết định đổi hướng, chuyển kế hoạch cũ vào archive | [ADR-0012](../../docs/adr/0012-restore-features-dev-first.md) | – | done 2026-10-09: docs/plan/, ADR-0012 |
| NEN-03 | Dọn thư mục gốc: xóa `start.sh`, chuyển `setup-env.sh` vào `scripts/`, sửa Makefile, README, tài liệu nhắc tới hai file đó | [backlog §1](../../docs/plan/03-backlog.md) | NEN-02 | todo |
| NEN-04 | Rút gọn `docs/` và viết lại `docs/README.md` bằng tiếng Việt. **[XÁC NHẬN]** danh sách file giữ / chuyển vào archive trước khi chuyển | [backlog §1](../../docs/plan/03-backlog.md) | NEN-03 | todo |
| NEN-05 | Mỗi tính năng hiện có một file trong `docs/features/` | [backlog §1](../../docs/plan/03-backlog.md) | NEN-04 | todo |
| NEN-06 | Dọn `.claude/`: bỏ các vai giả lập, skill làm việc theo luồng mới, cập nhật rules và CLAUDE.md. **[XÁC NHẬN]** danh sách file xóa | [backlog §1](../../docs/plan/03-backlog.md) | NEN-04 | todo |
| NEN-07 | Seeder dữ liệu mẫu cho dev | [backlog §1](../../docs/plan/03-backlog.md) | NEN-03 | todo |
| QUYEN-01 | **[XÁC NHẬN]** Đề xuất thiết kế phân quyền động bằng spatie/laravel-permission | [roadmap §2](../../docs/plan/02-roadmap.md) | NEN-07 | todo |
| QUYEN-02 | Phân quyền động: backend, kiểm tra quyền mỗi request, đồng bộ API → quyền, test | [roadmap §2](../../docs/plan/02-roadmap.md) | QUYEN-01 | todo |
| QUYEN-03 | Phân quyền động: API quản lý vai trò, quyền, gán vai trò | [roadmap §2](../../docs/plan/02-roadmap.md) | QUYEN-02 | todo |
| QUYEN-04 | Phân quyền động: giao diện vai trò, ma trận quyền, ẩn menu và nút theo quyền | [roadmap §2](../../docs/plan/02-roadmap.md) | QUYEN-03 | todo |
| MEDIA-01 | **[XÁC NHẬN]** Đề xuất thiết kế media MinIO + upload, rà cấu hình MinIO | [roadmap §3](../../docs/plan/02-roadmap.md) | QUYEN-04 | todo |
| MEDIA-02 | Media: kết nối MinIO, thư mục, upload file nhỏ, tải về, xóa, test | [roadmap §3](../../docs/plan/02-roadmap.md) | MEDIA-01 | todo |
| MEDIA-03 | Media: upload file lớn chia phần, xử lý nền, dọn upload treo | [roadmap §3](../../docs/plan/02-roadmap.md) | MEDIA-02 | todo |
| MEDIA-04 | Media: giao diện quản lý file | [roadmap §3](../../docs/plan/02-roadmap.md) | MEDIA-03 | todo |
| USER-01 | **[XÁC NHẬN]** Đề xuất thiết kế người dùng cuối, phòng ban, chính sách | [roadmap §4](../../docs/plan/02-roadmap.md) | MEDIA-04 | todo |
| USER-02 | Quản lý người dùng cuối: API + giao diện + test | [roadmap §4](../../docs/plan/02-roadmap.md) | USER-01 | todo |
| USER-03 | Phòng ban, chính sách, gán admin vào phòng ban: API + giao diện + test | [roadmap §4](../../docs/plan/02-roadmap.md) | USER-02 | todo |
| CI-01 | **[XÁC NHẬN]** Đề xuất CI/CD gộp chung trong thư mục `ci/` | [roadmap §5](../../docs/plan/02-roadmap.md) | USER-03 | todo |
| CI-02 | CI/CD: làm và test trên máy và GitHub | [roadmap §5](../../docs/plan/02-roadmap.md) | CI-01 | todo |
| CI-03 | CI/CD: tắt chạy tự động, hướng dẫn bật lại | [roadmap §5](../../docs/plan/02-roadmap.md) | CI-02 | todo |
