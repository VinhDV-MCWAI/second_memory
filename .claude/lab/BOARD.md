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
| NEN-03 | Dọn thư mục gốc: xóa `start.sh`, chuyển `setup-env.sh` vào `scripts/`, sửa Makefile, README, tài liệu nhắc tới hai file đó | [backlog §1](../../docs/plan/03-backlog.md) | NEN-02 | done 2026-10-13: 42ce9580, 774c59da — setup-env.sh chuyển vào scripts/, xóa start.sh, sửa mọi chỗ nhắc tới |
| NEN-04 | Rút gọn `docs/` và viết lại `docs/README.md` bằng tiếng Việt. **[XÁC NHẬN]** danh sách file giữ / chuyển vào archive trước khi chuyển | [backlog §1](../../docs/plan/03-backlog.md) | NEN-03 | done 2026-10-13: 78462665, e4743898 — docs/ còn plan, adr, releases, runbooks, architecture; tài liệu Engineering Lab vào archive; mục lục tiếng Việt |
| NEN-05 | Mỗi tính năng hiện có một file trong `docs/features/` | [backlog §1](../../docs/plan/03-backlog.md) | NEN-04 | done 2026-10-13: 982773ee — docs/features/: 7 file mô tả tính năng + mục lục; docs/README.md trỏ tới |
| NEN-06 | Dọn `.claude/`: bỏ các vai giả lập, skill làm việc theo luồng mới, cập nhật rules và CLAUDE.md. **[XÁC NHẬN]** danh sách file xóa | [backlog §1](../../docs/plan/03-backlog.md) | NEN-04 | done 2026-10-13: d0e0895f — bỏ 3 vai giả lập và skill lane (gộp vào lab-task), rules + CLAUDE.md theo cách làm một branch |
| NEN-07 | Seeder dữ liệu mẫu cho dev | [backlog §1](../../docs/plan/03-backlog.md) | NEN-03 | done 2026-10-13: 31dfab61, bb6fe637, 300cd787 — make seed: tài khoản chỉ xem viewer + 5 skill, 6 bằng chứng, 4 mục tiêu, 5 nhãn; chạy lại không trùng |
| QUYEN-01 | **[XÁC NHẬN]** Đề xuất thiết kế phân quyền động bằng spatie/laravel-permission | [roadmap §2](../../docs/plan/02-roadmap.md) | NEN-07 | done 2026-10-13: 4dc16a4b — ADR-0013: phân quyền động bằng spatie, chọn phương án A (gom theo tên quyền, Chủ toàn quyền, nhiều vai trò) |
| QUYEN-02 | Phân quyền động: backend, kiểm tra quyền mỗi request, đồng bộ API → quyền, test | [roadmap §2](../../docs/plan/02-roadmap.md) | QUYEN-01 | done 2026-10-13: 82e2290a, df35bb8a, b8df0fda, bed9905a — mỗi API quản trị là một quyền, kiểm tra mỗi request, vai trò Chủ/Chỉ xem chuyển vào DB, make permissions; 176 test pass |
| QUYEN-03 | Phân quyền động: API quản lý vai trò, quyền, gán vai trò | [roadmap §2](../../docs/plan/02-roadmap.md) | QUYEN-02 | done 2026-10-13: 8e6bb0fd — API vai trò (thêm/sửa/xóa, quyền), danh sách quyền theo chức năng, admin nhận role_ids, bỏ cột role, me trả permissions; make verify pass |
| QUYEN-04 | Phân quyền động: giao diện vai trò, ma trận quyền, ẩn menu và nút theo quyền | [roadmap §2](../../docs/plan/02-roadmap.md) | QUYEN-03 | done 2026-10-13: 83e45637, 8dc53eb3 — màn hình Vai trò + ma trận quyền, menu và nút ẩn theo quyền; 79 test FE, 2 e2e, make verify pass |
| MEDIA-01 | **[XÁC NHẬN]** Đề xuất thiết kế media MinIO + upload, rà cấu hình MinIO | [roadmap §3](../../docs/plan/02-roadmap.md) | QUYEN-04 | done 2026-10-13: ee2a34b6, 072dbf3f — ADR-0014: upload thẳng trình duyệt → kho chính MinIO, việc nền + Reverb báo kết quả (dùng chung), tắt đọc công khai; thêm việc NEN-08 |
| MEDIA-02 | Media: kết nối MinIO, thư mục, upload file nhỏ, tải về, xóa, test | [roadmap §3](../../docs/plan/02-roadmap.md) | MEDIA-01 | done 2026-10-13: e511b8b9, 0c0a42a7, 5a247218 — API quản lý file: thư mục, upload ≤100 MB bằng link ký tạm qua nginx, tải về, đổi tên/di chuyển, xóa; tắt đọc công khai MinIO; make verify pass, chạy thật qua localhost:81 |
| MEDIA-03 | Media: upload file lớn chia phần, xử lý sau upload bằng việc nền, dọn upload treo | [ADR-0014](../../docs/adr/0014-media-minio-upload.md) | NEN-08 | done 2026-10-13: 63219762, b724bd5f, 8e548c11 — upload file lớn chia phần (tới 10 GB, 4 phần song song, thử lại), việc nền ghép + kiểm tra, báo kết quả tức thì, dọn upload treo mỗi giờ; thử thật file 3 GiB khớp SHA-256; make verify pass |
| MEDIA-04 | Media: giao diện quản lý file | [roadmap §3](../../docs/plan/02-roadmap.md) | MEDIA-03 | done 2026-10-14: 6db22c3b, 7e261a3a, 73213bd9 — màn hình Files: thư mục, kéo-thả upload có %, hủy, tải về, đổi tên, di chuyển, xóa, tự làm mới khi việc nền báo xong; make verify + 4 e2e pass (file 120 MB) |
| USER-01 | **[XÁC NHẬN]** Đề xuất thiết kế người dùng cuối, phòng ban, chính sách | [roadmap §4](../../docs/plan/02-roadmap.md) | MEDIA-04 | done 2026-10-14: a9f776c2 — chủ dự án quyết để sau cả người dùng cuối, phòng ban, chính sách (ADR-0015); phạm vi hiện tại chỉ phân quyền theo vai trò |
| USER-02 | Quản lý người dùng cuối: API + giao diện + test | [roadmap §4](../../docs/plan/02-roadmap.md) | USER-01 | blocked: để sau theo ADR-0015 (chủ dự án 2026-10-14) |
| USER-03 | Phòng ban, chính sách, gán admin vào phòng ban: API + giao diện + test (để sau, [ADR-0015](../../docs/adr/0015-defer-end-users-departments.md)) | [roadmap §4](../../docs/plan/02-roadmap.md) | USER-02 | todo |
| CI-01 | **[XÁC NHẬN]** Đề xuất CI/CD gộp chung trong thư mục `ci/` | [roadmap §5](../../docs/plan/02-roadmap.md) | USER-01 | done 2026-10-14: 031c489f — ADR-0016: CI gộp vào ci/, container riêng, make ci song song make verify, bỏ deploy blue-green hỏng, build image ở ci/build.sh |
| CI-02 | CI/CD: làm và test trên máy và GitHub | [roadmap §5](../../docs/plan/02-roadmap.md) | CI-01 | done 2026-10-14: 54776ac9…0a6f319a — make ci chạy trên máy: 203 test backend, 105 test giao diện, 63 test importer, build 3 image; chạy trên GitHub để sau (chủ dự án quyết 2026-10-14) |
| CI-03 | CI/CD: tắt chạy tự động, hướng dẫn bật lại | [roadmap §5](../../docs/plan/02-roadmap.md) | CI-02 | done 2026-10-14: 044bd903 — GitHub/GitLab không chạy gì khi push/PR (chỉ chạy tay), hướng dẫn bật lại trong ci/README.md; cleanup-branch và Dependabot giữ nguyên |
| NEN-08 | Việc nền và thông báo tức thì (dùng chung): hàng đợi, Reverb, việc hẹn giờ, kênh riêng mỗi người, test | [ADR-0014](../../docs/adr/0014-media-minio-upload.md) | MEDIA-02 | done 2026-10-13: b306b727, 028c0b64, fe24c599, 14fc3604 — hàng đợi Redis + 3 container (việc nền, hẹn giờ, Reverb), kênh riêng mỗi người, sự kiện dùng chung, chống lỡ thông báo, nút thử ở Dashboard; make verify + 3 e2e pass |
| CD-01 | CD trên máy: demo prod-like bằng Terraform (MinIO, việc nền, Reverb, đăng nhập), make cd = kiểm tra → build → deploy → kiểm tra sức khỏe | [ADR-0016](../../docs/adr/0016-ci-shared-folder.md), [infra/README.md](../../infra/README.md) | CI-02 | done 2026-10-14: 39e7c75d…044bd903 — make cd: kiểm tra → build 4 image → Terraform prod-like (MinIO, việc nền, Reverb, root) → 4 test trình duyệt pass trên http://localhost:9443; sửa tên cookie phiên |
| DEMO-01 | **[XÁC NHẬN]** Dữ liệu mẫu cho bản demo prod-like (tài khoản Chỉ xem + Skill Ledger mẫu) | [infra/README.md](../../infra/README.md) | CD-01 | blocked: để sau tới giai đoạn 6 (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| DEMO-02 | **[XÁC NHẬN]** HTTPS cho bản demo prod-like | [infra/README.md](../../infra/README.md) | CD-01 | blocked: để sau tới giai đoạn 6 (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| AUTH-01 | Đổi mật khẩu thì đăng xuất mọi thiết bị khác: test chứng minh, thiếu thì sửa | [dang-nhap.md mục 2](../../docs/features/dang-nhap.md) | CI-03 | done 2026-10-14: bdff0fd3, 4eed314b — đổi mật khẩu thì thiết bị khác bị đăng xuất, thiết bị đang dùng giữ phiên (sửa lỗi tự đăng xuất chính mình + lỗi 500 khi mật khẩu null); 86 test + chạy thật trên dev |
| AUTH-02 | Giao diện tự cập nhật quyền: hỏi lại quyền khi quay lại tab và khi gặp 403 | [dang-nhap.md mục 3](../../docs/features/dang-nhap.md) | AUTH-01 | done 2026-10-14: 52599bf8 — quyền trên giao diện tự lấy lại khi quay lại tab và khi gặp 403 (tối đa 5 giây/lần); 109 test giao diện, lint, typecheck pass |
| LOG-01 | Màn hình Nhật ký thay đổi: danh sách, lọc, phân trang (API đã có) | [nhat-ky-thay-doi.md](../../docs/features/nhat-ky-thay-doi.md) | AUTH-02 | done 2026-10-14: 7a2f444f, c68bf280 — màn hình Nhật ký thay đổi (lọc loại, việc, người làm, ngày; phân trang; xem ô cũ → mới); sửa lọc ngày đọc nhầm tháng/ngày (500 hoặc lọc sai); 207 test backend, 112 test giao diện |
| AUTH-03 | Giới hạn đăng nhập sai theo IP (ngoài giới hạn theo tên + IP) | [dang-nhap.md mục 4](../../docs/features/dang-nhap.md) | LOG-01 | done 2026-10-14: 26990056, 5a1df46a — một IP sai 30 lần (mọi tên) bị khóa 15 phút, đăng nhập đúng không xóa bộ đếm IP; 209 test backend, Pint, Larastan pass |
| AUTH-04 | **[XÁC NHẬN]** Màn hình Phiên đăng nhập: xem thiết bị, đăng xuất từ xa (thêm bảng DB) | [dang-nhap.md mục 1](../../docs/features/dang-nhap.md) | AUTH-03 | todo |
| AUTH-05 | **[XÁC NHẬN]** Ghi nhớ đăng nhập | [dang-nhap.md mục 5](../../docs/features/dang-nhap.md) | AUTH-04 | todo |
| MEDIA-05 | Upload dở tiếp tục được sau khi tải lại trang | [ADR-0014](../../docs/adr/0014-media-minio-upload.md) | AUTH-05 | todo |
