# Nhật ký tiến độ

Đọc file này đầu tiên khi bắt đầu một phiên làm việc mới. Nhật ký giai đoạn trước (Engineering Lab): [docs/archive/engineering-lab/lab/PROGRESS.md](../../docs/archive/engineering-lab/lab/PROGRESS.md).

## Trạng thái hiện tại

| | |
|---|---|
| Giai đoạn đang làm | 6. Nâng cấp trên dev (bản demo prod-like để sau) |
| Branch | `feature/p3-ledger-api` (đổi branch khi chủ dự án yêu cầu) |
| Môi trường | Dev (`make up`, http://localhost:81) + bản demo prod-like trên máy (`make cd`, http://localhost:9443). Staging, production thật, đo tải: tạm dừng |
| CI/CD | Chỉ chạy trên máy: `make ci`, `make cd`. **Trên GitHub/GitLab đang TẮT chạy tự động** (push không chạy gì); cách bật lại: [ci/README.md](../../ci/README.md) |
| Việc tiếp theo | **LEDGER-01 — Đính kèm file vào bằng chứng** (có dấu [XÁC NHẬN], thêm bảng): viết đề xuất rồi dừng chờ chủ dự án. Xem mục "Next step" ở cuối file |
| Tạm dừng | Mọi việc về **đăng nhập** (AUTH-04, AUTH-05 và các mục "Nâng cấp để dành" trong [dang-nhap.md](../../docs/features/dang-nhap.md)): không đề xuất, không nhận, kể cả việc mới, cho tới khi chủ dự án yêu cầu (quyết ngày 2026-10-14) |

## Việc chủ dự án phải tự làm

Claude không có quyền làm những việc này:

1. Push branch và tag lên GitHub, tạo pull request.
2. Chạy `make tf-apply` lần đầu (Terraform), nhưng việc này đang tạm dừng.
3. Cài rclone và lên lịch sao lưu định kỳ, xem [backup-restore.md](../../docs/runbooks/backup-restore.md).

## Việc tồn đọng

Cập nhật 2026-10-15. Mỗi dòng là thứ còn mở, chưa thành việc trên bảng.

| # | Việc | Chi tiết | Ai làm |
|---|---|---|---|
| 1 | Push code | Branch `feature/p3-ledger-api` đang đi trước `origin` khoảng 110 commit (từ AUTH-03 tới MEDIA-07) | Chủ dự án |
| 2 | `.claude/settings.json` có thay đổi chưa commit | Thêm quyền `Write/Edit(.claude/**)`, `Edit(CLAUDE.md)`, `Bash(git rm:*)`. Không phải do các phiên Claude gần đây làm; Claude không động vào. Chủ dự án quyết commit hay bỏ | Chủ dự án |
| 3 | Chưa chạy `make verify` / `make ci` trọn bộ sau 4 việc gần nhất | Đã chạy riêng từng phần: test backend + Pint + Larastan (219 test), test giao diện + lint + typecheck + format (123 test), 7 test trình duyệt. Nên chạy `make verify` một lần trước khi mở pull request | Phiên sau |
| 4 | Bản demo prod-like chưa cập nhật | Luật MinIO 30 ngày và quyền phiên bản mới (`docker/minio/`) chỉ mới áp dụng cho dev. Terraform dùng chung file đó, nên lần `make cd` tới sẽ áp dụng; chưa thử | Khi chạy `make cd` |
| 5 | Dòng file đã xóa quá 30 ngày vẫn nằm trong bảng `media_mgmt` | MinIO đã xóa dữ liệu nhưng dòng trong DB còn (`is_delete = true`), không hiện ở đâu. Chưa có lệnh dọn. Nhỏ, có thể thêm vào lệnh hẹn giờ sau | Để sau |
| 6 | Tiếp tục upload dở từ máy khác không kiểm tra được "đúng file" | Trình duyệt chỉ nhớ thời điểm sửa file trên máy đã bắt đầu upload; máy khác chỉ so tên + dung lượng | Chấp nhận, ghi trong [quan-ly-file.md](../../docs/features/quan-ly-file.md) |
| 7 | Kiểu sinh tự động hơi lệch | `media/multipart/resume` khai `parts` có thể null (Scramble đoán sai); giao diện coi null là danh sách rỗng | Để sau |
| 8 | Việc để sau theo quyết định chủ dự án | Người dùng cuối, phòng ban (USER-02, USER-03, ADR-0015); dữ liệu mẫu + HTTPS cho bản demo (DEMO-01, DEMO-02) | Chờ chủ dự án |

## Lưu ý môi trường

Xem [docs/dev-guide.md](../../docs/dev-guide.md) mục 6 "Các lỗi hay gặp". Thêm lỗi mới vào đó, không ghi ở đây.

## Log

Mỗi dòng do `scripts/lane.sh done` tự thêm. Không sửa tay.

- 2026-10-09 — [lane main] NEN-01 done: docs/dev-guide.md
- 2026-10-09 — [lane main] NEN-02 done: kế hoạch mới trong docs/plan/, ADR-0012, kế hoạch cũ chuyển vào docs/archive/engineering-lab/

- 2026-10-13 — [lane main] NEN-03 done: 42ce9580, 774c59da — setup-env.sh chuyển vào scripts/, xóa start.sh, sửa mọi chỗ nhắc tới

- 2026-10-13 — [lane main] NEN-04 done: 78462665, e4743898 — docs/ còn plan, adr, releases, runbooks, architecture; tài liệu Engineering Lab vào archive; mục lục tiếng Việt

- 2026-10-13 — [lane main] NEN-05 done: 982773ee — docs/features/: 7 file mô tả tính năng + mục lục; docs/README.md trỏ tới

- 2026-10-13 — [lane main] NEN-06 done: d0e0895f — bỏ 3 vai giả lập và skill lane (gộp vào lab-task), rules + CLAUDE.md theo cách làm một branch

- 2026-10-13 — [lane main] NEN-07 done: 31dfab61, bb6fe637, 300cd787 — make seed: tài khoản chỉ xem viewer + 5 skill, 6 bằng chứng, 4 mục tiêu, 5 nhãn; chạy lại không trùng

- 2026-10-13 — [lane main] QUYEN-01 done: 4dc16a4b — ADR-0013: phân quyền động bằng spatie, chọn phương án A (gom theo tên quyền, Chủ toàn quyền, nhiều vai trò)

- 2026-10-13 — [lane main] QUYEN-02 done: 82e2290a, df35bb8a, b8df0fda, bed9905a — mỗi API quản trị là một quyền, kiểm tra mỗi request, vai trò Chủ/Chỉ xem chuyển vào DB, make permissions; 176 test pass

- 2026-10-13 — [lane main] QUYEN-03 done: 8e6bb0fd — API vai trò (thêm/sửa/xóa, quyền), danh sách quyền theo chức năng, admin nhận role_ids, bỏ cột role, me trả permissions; make verify pass

- 2026-10-13 — [lane main] QUYEN-04 done: 83e45637, 8dc53eb3 — màn hình Vai trò + ma trận quyền, menu và nút ẩn theo quyền; 79 test FE, 2 e2e, make verify pass

- 2026-10-13 — [lane main] MEDIA-01 done: ee2a34b6, 072dbf3f — ADR-0014: upload thẳng trình duyệt → kho chính MinIO, việc nền + Reverb báo kết quả (dùng chung), tắt đọc công khai; thêm việc NEN-08

- 2026-10-13 — [lane main] MEDIA-02 done: e511b8b9, 0c0a42a7, 5a247218 — API quản lý file: thư mục, upload ≤100 MB bằng link ký tạm qua nginx, tải về, đổi tên/di chuyển, xóa; tắt đọc công khai MinIO; make verify pass, chạy thật qua localhost:81

- 2026-10-13 — [lane main] NEN-08 done: b306b727, 028c0b64, fe24c599, 14fc3604 — hàng đợi Redis + 3 container (việc nền, hẹn giờ, Reverb), kênh riêng mỗi người, sự kiện dùng chung, chống lỡ thông báo, nút thử ở Dashboard; make verify + 3 e2e pass

- 2026-10-13 — [lane main] MEDIA-03 done: 63219762, b724bd5f, 8e548c11 — upload file lớn chia phần (tới 10 GB, 4 phần song song, thử lại), việc nền ghép + kiểm tra, báo kết quả tức thì, dọn upload treo mỗi giờ; thử thật file 3 GiB khớp SHA-256; make verify pass

- 2026-10-14 — [lane main] MEDIA-04 done: 6db22c3b, 7e261a3a, 73213bd9 — màn hình Files: thư mục, kéo-thả upload có %, hủy, tải về, đổi tên, di chuyển, xóa, tự làm mới khi việc nền báo xong; make verify + 4 e2e pass (file 120 MB)

- 2026-10-14 — [lane main] USER-01 done: a9f776c2 — chủ dự án quyết để sau cả người dùng cuối, phòng ban, chính sách (ADR-0015); phạm vi hiện tại chỉ phân quyền theo vai trò

- 2026-10-14 — [lane main] CI-01 done: 031c489f — ADR-0016: CI gộp vào ci/, container riêng, make ci song song make verify, bỏ deploy blue-green hỏng, build image ở ci/build.sh

- 2026-10-14 — [lane main] CI-02 done: 54776ac9…0a6f319a — make ci chạy trên máy: 203 test backend, 105 test giao diện, 63 test importer, build 3 image; chạy trên GitHub để sau (chủ dự án quyết 2026-10-14)

- 2026-10-14 — [lane main] CD-01 done: 39e7c75d…044bd903 — make cd: kiểm tra → build 4 image → Terraform prod-like (MinIO, việc nền, Reverb, root) → 4 test trình duyệt pass trên http://localhost:9443; sửa tên cookie phiên

- 2026-10-14 — [lane main] CI-03 done: 044bd903 — GitHub/GitLab không chạy gì khi push/PR (chỉ chạy tay), hướng dẫn bật lại trong ci/README.md; cleanup-branch và Dependabot giữ nguyên

- 2026-10-14 — [lane main] AUTH-01 done: bdff0fd3, 4eed314b — đổi mật khẩu thì thiết bị khác bị đăng xuất, thiết bị đang dùng giữ phiên (sửa lỗi tự đăng xuất chính mình + lỗi 500 khi mật khẩu null); 86 test + chạy thật trên dev

- 2026-10-14 — [lane main] AUTH-02 done: 52599bf8 — quyền trên giao diện tự lấy lại khi quay lại tab và khi gặp 403 (tối đa 5 giây/lần); 109 test giao diện, lint, typecheck pass

- 2026-10-14 — [lane main] LOG-01 done: 7a2f444f, c68bf280 — màn hình Nhật ký thay đổi (lọc loại, việc, người làm, ngày; phân trang; xem ô cũ → mới); sửa lọc ngày đọc nhầm tháng/ngày (500 hoặc lọc sai); 207 test backend, 112 test giao diện

- 2026-10-14 — [lane main] AUTH-03 done: 26990056, 5a1df46a — một IP sai 30 lần (mọi tên) bị khóa 15 phút, đăng nhập đúng không xóa bộ đếm IP; 209 test backend, Pint, Larastan pass

- 2026-10-14 — [lane main] MEDIA-05 done: ae7756ce, 2498e113, 8592643e — file lớn bỏ dở (tải lại trang, mất mạng) hiện ở khung Unfinished uploads, chọn lại đúng file thì chỉ gửi phần thiếu; 212 test backend, 117 test giao diện, 5 e2e pass (thử thật trên MinIO)

- 2026-10-14 — [lane main] MEDIA-06 done: c1576e84, 7223bbf9, d04cc1c3, db7901a7 — xem trước ảnh, PDF, video, nhạc, file chữ (không xem trước html/svg vì chạy được code); sửa lỗi upload hỏng một phần vẫn gửi tiếp phần khác; 213 test backend, 119 test giao diện, 6 e2e pass

- 2026-10-15 — [lane main] MEDIA-07 done: 4477b392, 6f33250f, a8358b0e, 74c7e76f, 3a40cd79 — thùng rác file 30 ngày: khôi phục (tự tạo lại thư mục, trùng tên thì thêm (restored)), xóa vĩnh viễn, dọn thùng rác; sửa luật MinIO giữ file đã xóa mãi mãi; 219 test backend, 123 test giao diện, 7 e2e pass

## Next step

Cập nhật 2026-10-15.

1. Chạy `scripts/lane.sh status`: việc nhận được là **LEDGER-01** (lane đang rảnh).
2. `scripts/lane.sh claim LEDGER-01` rồi viết đề xuất theo mẫu trong [01-goals-and-workflow.md](../../docs/plan/01-goals-and-workflow.md). Việc có thêm bảng nên **dừng chờ chủ dự án xác nhận**. Điểm cần hỏi: một bằng chứng gắn nhiều file hay một; file gắn vào bằng chứng bị xóa / vào thùng rác thì sao; trang public (`nextjs-docs`) hiện file thế nào (link tải ký tạm hay xem trước); chọn file từ màn hình Files hay upload thẳng trong form bằng chứng.
3. Code liên quan: [quan-ly-file.md](../../docs/features/quan-ly-file.md) (upload, xem trước, thùng rác), [skill-ledger.md](../../docs/features/skill-ledger.md), [trang-public.md](../../docs/features/trang-public.md).
4. Sau LEDGER-01 bảng việc hết việc nhận được: hỏi chủ dự án việc tiếp theo, đề xuất từ "Việc tồn đọng" ở trên. Không đề xuất việc đăng nhập.
