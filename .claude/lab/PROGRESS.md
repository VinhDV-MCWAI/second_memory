# Nhật ký tiến độ

Đọc file này đầu tiên khi bắt đầu một phiên làm việc mới. Nhật ký giai đoạn trước (Engineering Lab): [docs/archive/engineering-lab/lab/PROGRESS.md](../../docs/archive/engineering-lab/lab/PROGRESS.md).

## Trạng thái hiện tại

| | |
|---|---|
| Giai đoạn đang làm | 6. Nâng cấp trên dev (bản demo prod-like để sau) |
| Branch | `feature/p3-ledger-api` (đổi branch khi chủ dự án yêu cầu) |
| Môi trường | Chỉ dev (`make up`, http://localhost:81). Bản demo prod-like đã tắt container ngày 2026-10-15 (không việc nào dùng); staging, production thật, đo tải: tạm dừng |
| CI/CD | Chỉ chạy trên máy: `make ci`, `make cd`. **Trên GitHub/GitLab đang TẮT chạy tự động** (push không chạy gì); cách bật lại: [ci/README.md](../../ci/README.md) |
| Việc tiếp theo | Bảng việc không còn việc nhận được (cập nhật 2026-10-17). Đã xong theo kế hoạch duyệt 2026-10-15: Màn hình Phiên đăng nhập, Ghi nhớ đăng nhập, Phòng ban và khóa thư mục Files theo phòng ban (USER-03, DEPT-01…05). Chờ chủ dự án đưa danh sách việc mới |
| Tạm dừng | Mọi việc **mới** về đăng nhập và phân quyền (các mục "Nâng cấp để dành" trong [dang-nhap.md](../../docs/features/dang-nhap.md), loại quyền mới, người dùng cuối, mở rộng khóa phòng ban sang chỗ khác ngoài Files): không đề xuất, không nhận, **chỉ làm khi chủ dự án yêu cầu** (quyết 2026-10-14, thêm phân quyền 2026-10-15). Các việc đã duyệt 2026-10-15 đều đã xong |
| Dọn dẹp | Mỗi việc xong phải dọn theo mục "Dọn dẹp khi xong việc" trong [01-goals-and-workflow.md](../../docs/plan/01-goals-and-workflow.md) (`make clean` + xóa code chết, dữ liệu thử) |

## Việc chủ dự án phải tự làm

Claude không có quyền làm những việc này:

1. Push branch và tag lên GitHub, tạo pull request.
2. Chạy `make tf-apply` lần đầu (Terraform), nhưng việc này đang tạm dừng.
3. Cài rclone và lên lịch sao lưu định kỳ, xem [backup-restore.md](../../docs/runbooks/backup-restore.md).

## Việc tồn đọng

Cập nhật 2026-10-17. Mỗi dòng là thứ còn mở, chưa thành việc trên bảng.

| # | Việc | Chi tiết | Ai làm |
|---|---|---|---|
| 1 | Push code | Chủ dự án đã push và merge tới LEDGER-02 (2026-10-15). Branch `feature/p3-ledger-api` trên GitHub đã bị xóa (sau merge); các commit từ `a6c9b363` (sửa tf-destroy) qua MEDIA-11, AUTH-04, AUTH-05 tới DEPT-05 (ngày 2026-10-17) chỉ có trên máy, push lại sẽ tạo lại branch | Chủ dự án |
| 6 | Tiếp tục upload dở từ máy khác không kiểm tra được "đúng file" | Trình duyệt chỉ nhớ thời điểm sửa file trên máy đã bắt đầu upload; máy khác chỉ so tên + dung lượng | Chấp nhận, ghi trong [quan-ly-file.md](../../docs/features/quan-ly-file.md) |
| 8 | Việc để sau theo quyết định chủ dự án | Người dùng cuối (USER-02, ADR-0015); dữ liệu mẫu + HTTPS cho bản demo (DEMO-01, DEMO-02). Phòng ban đã làm xong (ADR-0017) | Chờ chủ dự án |
| 9 | Gợi ý nhỏ chưa đề xuất | Hiện ảnh nhỏ (thumbnail) trên trang public và trong thùng rác. Khóa phòng ban hiện chỉ áp dụng cho Files; bằng chứng, kỹ năng chưa bị chặn theo phòng ban (đúng phạm vi đã duyệt) | Chờ chủ dự án |

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

- 2026-10-15 — [lane main] LEDGER-01 done: 6b0b4517, ae3c23df, 6fd32a3d, 48991162 — bằng chứng gắn tới 10 file từ Files (chọn hoặc upload trong hộp thoại, đổi thứ tự, file đã xóa vẫn giữ và đánh dấu); trang public hiện file, link mã ngẫu nhiên xin link tạm mỗi lần bấm; make verify pass (227 backend, 126 giao diện, 63 importer), thử thật trên dev

- 2026-10-15 — [lane main] MEDIA-08 done: aa3d7fea, 35048bd0 — media:prune-trash chạy 0 giờ mỗi ngày, xóa hẳn dòng đã xóa quá 30 ngày (file, thư mục, upload hỏng) và liên kết với bằng chứng, ghi purged; 12 test pass, Larastan sạch

- 2026-10-15 — [lane main] MEDIA-09 done: ca08a0c0, 17718479 — cột Evidence ở Files (số liên kết, tên bằng chứng; thư mục đếm file bên trong), cảnh báo khi xóa; 36 test backend, 127 test giao diện pass

- 2026-10-15 — [lane main] API-01 done: cb058a5c — parts của media/multipart/resume giờ là danh sách không null trong openapi.json, bỏ đoạn vá ?? [] ở bộ upload; 9 test backend, 22 test giao diện pass

- 2026-10-15 — [lane main] LEDGER-02 done: 8abe344c, 01b15e55, 71bdeb7b — bằng chứng cần link hoặc ít nhất một file; không link thì tiêu đề là chữ thường ở quản trị và trang public; nhập Obsidian không đổi; make verify pass (231 backend, 128 giao diện, 63 importer), thử thật trên dev

- 2026-10-15 — [lane main] DOCS-01 done: 95f65960 — bảng Các bước làm của Quản lý file thêm upload dở, xem trước, thùng rác, gắn vào bằng chứng

- 2026-10-15 — [lane main] E2E-01 done: 6412d943, b5272edd — test trình duyệt gắn file vào bằng chứng (upload trong form, đổi thứ tự, không link, mở từ trang public, xóa thì hiện Deleted); sửa test luồng chính hỏng sau LEDGER-02 (nhãn ô Link đổi); make e2e 8/8 pass

- 2026-10-15 — [lane main] LEDGER-03 done: db9b76a0, 5d1347b5, 99199f24 — bấm số ở cột Evidence của Files mở danh sách bằng chứng chỉ gồm các bằng chứng dùng file đó (API lọc media_id, nút Show all evidence); make verify pass (232 backend, 130 giao diện, 63 importer), make e2e 8/8

- 2026-10-15 — [lane main] MEDIA-10 done: 60c5de4c, a7dcce58 — ảnh (jpg, png, gif, webp, avif, bmp) có rộng × cao sau khi upload, hiện ở Files và xem trước; lệnh media:read-image-sizes chạy bù; sửa test e2e upload dở chập chờn; make verify pass (234 backend, 130 giao diện, 63 importer), make e2e 8/8

- 2026-10-15 — [lane main] MEDIA-11 done: e2a66080, d2fb118a — ffmpeg trong image PHP; ảnh nhỏ 320 px cho ảnh và video, thời lượng video và nhạc, tạo ngầm sau upload, hiện ở Files, hộp chọn file, form bằng chứng; chặn file độc (giới hạn 60 giây, chỉ định dạng thông thường); lệnh media:read-media-info; make verify pass (238 backend, 133 giao diện, 63 importer), make e2e 8/8, thử 13 loại file trên MinIO thật

- 2026-10-15 — [lane main] AUTH-04 done: 157f5afd, 8768876f, 74499eaf, 0388a668 — màn hình Signed-in devices: xem thiết bị đang đăng nhập (trình duyệt, IP, lần dùng cuối), đăng xuất một thiết bị hoặc mọi thiết bị khác; phiên vẫn trong Redis + bảng admin_session; đổi mật khẩu, khóa, xóa tài khoản cũng xóa thiết bị; make verify pass (252 backend, 144 giao diện, 63 importer), make e2e 8/8, thử thật trên dev

- 2026-10-15 — [lane main] AUTH-05 done: 07473408, 8e4bd368, b375bb00 — ô ghi nhớ đăng nhập: thiết bị tự đăng nhập lại 30 ngày, ghi nhớ riêng từng thiết bị (đá một máy chỉ máy đó bị quên), đăng xuất, đổi mật khẩu, khóa tài khoản đều xóa ghi nhớ; giao diện tự gửi lại khi gặp 419; make verify pass (260 backend, 147 giao diện, 63 importer), make e2e 8/8, thử thật trên dev

- 2026-10-17 — [lane main] USER-03 done: b6db0804 — chủ dự án duyệt ADR-0017 cả 5 đề xuất: admin chưa có phòng ban không thấy thư mục khóa, nhiều phòng ban, khóa lồng xét cả cha lẫn con, cấp ở màn hình Files, trang public giữ như nay

- 2026-10-17 — [lane main] DEPT-01 done: 64a207eb, 6d967876 — API phòng ban (thêm, sửa, xóa, tìm; mã không trùng không phân biệt hoa thường; xóa khi còn người chỉ bỏ liên kết), admin thuộc nhiều phòng ban qua department_ids, quyền department.* (Chỉ xem được xem), nhật ký; make verify pass (267 backend, 147 giao diện, 63 importer)

- 2026-10-17 — [lane main] DEPT-02 done: 9cc61353, 97aa921f, d738c743 — màn hình Phòng ban (menu Master Data: bảng, lọc, thêm/sửa/xóa, lịch sử), ô chọn nhiều phòng ban trong form Quản trị viên, cột + bộ lọc phòng ban ở danh sách Quản trị viên; make verify pass (267 backend, 150 giao diện, 63 importer), thử thật trên dev

- 2026-10-17 — [lane main] DEPT-03 done: 41415b87, 59153f21, ab7e507e, 650a51e8, e932a5f2 — khóa thư mục Files theo phòng ban: API media/access/{id} (quyền media.access), chặn danh sách, tải, xem trước, upload, tạo thư mục, đổi tên, chuyển, xóa, thùng rác, upload dở, gắn file vào bằng chứng (file đã gắn hiện locked); khóa lồng xét cả cha lẫn con, admin chưa có phòng ban không thấy; sửa test e2e ảnh nhỏ chập chờn; make verify pass (274 backend, 150 giao diện, 63 importer), make e2e 8/8

- 2026-10-17 — [lane main] DEPT-04 done: e3299ce4, 3a2a4411 — màn hình Files: nút chìa khóa Access chọn phòng ban cho thư mục (bỏ tick hết là mở khóa), ổ khóa vàng cạnh thư mục khóa; form bằng chứng hiện nhãn Locked, không mở được file bị khóa; make verify pass (274 backend, 153 giao diện, 63 importer), thử thật trên dev

- 2026-10-17 — [lane main] DEPT-05 done: 086aab2b, 3c2247b7 — test trình duyệt: Chủ thêm phòng ban trên màn hình, khóa thư mục cho phòng ban của e2e_member; e2e_member thấy, e2e_outsider không thấy; run.sh tạo/xóa tài khoản phụ và phòng ban E2E; make e2e 9/9; tài liệu phong-ban.md hoàn chỉnh

- 2026-10-17 — [lane main] MEDIA-12 done: 7ca95382, 27078a80, b57d6b7f, 350d3f1a — ảnh nhỏ trong thùng rác Files và cạnh file đính kèm trên trang public (link ký tạm 1 giờ có sẵn trong API, không tốn thêm request); make verify pass (276 backend, 154 giao diện, 63 importer), thử thật trên dev

- 2026-10-17 — [lane main] SEARCH-01 done: 882d7662, b5337d0d, 360559c1, 5de5b280 — ô tìm kiếm chung có nhóm Files: tìm tên file và thư mục (không dấu, mọi từ, gõ sai vẫn ra), bỏ thùng rác, upload dở và thư mục khóa theo phòng ban, cần quyền media.list; chỉ mục trigram media_mgmt_name_trgm; make verify pass (280 backend, 155 giao diện, 63 importer), make e2e 9/9, thử thật trên dev

## Next step

Cập nhật 2026-10-17, cuối phiên làm Phòng ban và khóa thư mục.

1. Đầu phiên: đọc file này, rồi `scripts/lane.sh status`. Bảng việc hiện **không còn việc nhận được**: chỉ còn USER-02 (người dùng cuối), DEMO-01, DEMO-02, đều đang chặn "để sau" theo quyết định chủ dự án.
2. Nhắc chủ dự án push branch `feature/p3-ledger-api` nếu vẫn chưa push ("Việc tồn đọng" mục 1).
3. Chủ dự án sẽ đưa danh sách việc mới. Với mỗi việc: thêm vào bảng bằng `scripts/lane.sh add main <mã> "<việc>" "<tài liệu>" "<phụ thuộc>"` (lock `docs/plan/03-backlog.md` nếu thêm dòng vào backlog), việc lớn thì viết đề xuất theo mẫu trong [01-goals-and-workflow.md](../../docs/plan/01-goals-and-workflow.md) và chờ duyệt, rồi `claim` → làm → test nhanh → dọn dẹp (`make clean`, xóa dữ liệu thử) → `done`. Kỹ năng `/lab-task` đi qua từng bước.
4. Đã có sau phiên 2026-10-17 (đọc trước khi đụng tới):
   - Phòng ban: [docs/features/phong-ban.md](../../docs/features/phong-ban.md), [ADR-0017](../../docs/adr/0017-departments-folder-policies.md). Màn hình Master Data → Departments; admin chọn nhiều phòng ban trong form Quản trị viên.
   - Khóa thư mục Files: nút chìa khóa **Access** trên thư mục. Mọi API Files đi qua `App\Services\Media\FolderAccess` (ghi chú trong [laravel-api/CLAUDE.md](../../laravel-api/CLAUDE.md)). Thêm API Files mới thì phải chặn qua lớp này và thêm test vào `FolderPolicyApiTest`.
   - `e2e/run.sh` tạo thêm hai tài khoản phụ `e2e_member`, `e2e_outsider` và phòng ban `E2E …` cho test trình duyệt, chạy xong tự xóa / tắt.
5. Sau khi đổi Dockerfile PHP: `make up` hoặc `docker compose -f docker/docker-compose.yml up -d --build ml-php ml-queue ml-scheduler ml-reverb` (tải ffmpeg lần đầu mất khoảng 20 phút trên mạng chậm). Sau khi đổi code việc nền: `make workers-restart` (worker giữ code cũ trong bộ nhớ).
6. Kết quả gần nhất (2026-10-17): `make verify` pass (274 test backend, 153 giao diện, 63 importer); `make e2e` 9/9 pass. Cây làm việc sạch, bảng việc khớp backlog.
