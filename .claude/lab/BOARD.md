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
| USER-02 | Quản lý người dùng cuối: API + giao diện + test | [roadmap §4](../../docs/plan/02-roadmap.md) | – | done 2026-10-19: thay bằng tài khoản người dùng trên trang public (PUB-03, PUB-04, ADR-0020) |
| DEMO-01 | **[XÁC NHẬN]** Dữ liệu mẫu cho bản demo prod-like (tài khoản Chỉ xem + Skill Ledger mẫu) | [infra/README.md](../../infra/README.md) | – | blocked: để sau tới giai đoạn 6 (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| DEMO-02 | **[XÁC NHẬN]** HTTPS cho bản demo prod-like | [infra/README.md](../../infra/README.md) | – | blocked: để sau tới giai đoạn 6 (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| CLEAN-01 | Dọn dẹp: tài liệu lỗi thời, dữ liệu thử trên dev, rác Docker; README tiếng Anh; quy tắc dọn dẹp sau mỗi việc; rút gọn bảng việc đã xong | [01-goals-and-workflow.md](../../docs/plan/01-goals-and-workflow.md) | – | done 2026-10-17: 85f81197…79ab6c53 — README tiếng Anh, kiến trúc viết lại, bỏ 3 tài liệu lỗi thời, make clean xóa thêm 15 ảnh Docker không dùng, quy tắc + hướng dẫn dọn dẹp, bảng việc gom vào 05-done.md; dữ liệu thử trên DB dev chờ chủ dự án (không có quyền xóa vĩnh viễn) |
| REL-01 | Đóng gói và bàn giao bản v2.2.0: ghi chú phát hành, biên bản nghiệm thu, tài liệu bàn giao, nhìn lại, tag | [ADR-0018](../../docs/adr/0018-lifecycle-practice-track.md) | – | done 2026-10-17: 67075790 — ghi chú phát hành v2.2.0 (nổi bật, phá vỡ tương thích, nâng cấp, trước/sau, nghiệm thu 10 bước, bàn giao, lỗi đã biết, nhìn lại); tag v2.2.0 trên máy, chủ dự án push |
| PROC-01 | **[XÁC NHẬN]** Sổ tay quy trình bản mới; có khôi phục vai giả lập để tạo đầu vào không | [ADR-0018](../../docs/adr/0018-lifecycle-practice-track.md) | REL-01 | todo |
| PUB-01 | **[XÁC NHẬN]** Chọn công nghệ thay nextjs-docs, thử nhanh và đo | [ADR-0019](../../docs/adr/0019-public-user-frontend.md) | – | done 2026-10-17: 5e8fb014 — chọn Astro; đo: RAM production 76 → 35 MB, dev 499 → 133 MB, image 301 → 248 MB, JS xuống trình duyệt 579 KB → 0 |
| PUB-02 | Làm lại trang public trên công nghệ đã chọn, xóa nextjs-docs | [ADR-0019](../../docs/adr/0019-public-user-frontend.md) | PUB-01 | done 2026-10-17: 5ef7f391, fd743a67, c16bf04b — trang public chạy trên Astro (public-site), /docs chuyển hướng 301 về /skills, xóa nextjs-docs; make verify pass (280 backend, 155 giao diện, 63 importer, astro check 0 lỗi), make e2e 9/9; dọn: container và 3 image cũ, file tạm terraform init |
| PUB-03 | **[XÁC NHẬN]** Tài khoản người dùng cuối (vòng đời đầy đủ): yêu cầu, thiết kế, API, test | [ADR-0019](../../docs/adr/0019-public-user-frontend.md) | PUB-02 | done 2026-10-19: f93409cf, 83b25a64, 6129dce8, 136688ac, 54b77fe1, bd551842 — tài khoản người dùng trên trang public: đăng ký, đăng nhập (bộ đăng nhập riêng, không gọi được API quản trị), đổi mật khẩu, tự xóa tài khoản; ADR-0020; make verify pass (299 backend, 158 giao diện, 63 importer), make e2e 10/10 |
| PUB-04 | Lịch sử đã xem, yêu thích, lưu xem sau; admin khóa tài khoản người dùng | [ADR-0019](../../docs/adr/0019-public-user-frontend.md) | PUB-03 | done 2026-10-19: 83b25a64, 6129dce8, 136688ac, 54b77fe1 — yêu thích, xem sau (nút ☆ 🔖, trang My lists, ~3 KB JS), màn hình End users (khóa, đặt lại mật khẩu, xóa, bật/tắt đăng ký); bỏ lịch sử đã xem theo chủ dự án; dọn: dist build thử, e2e tự xóa tài khoản thử |
