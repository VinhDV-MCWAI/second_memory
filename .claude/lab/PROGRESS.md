# Nhật ký tiến độ

Đọc file này đầu tiên khi bắt đầu một phiên làm việc mới. Việc đã xong gom theo tính năng: [docs/plan/05-done.md](../../docs/plan/05-done.md). Nhật ký đầy đủ trước khi rút gọn (2026-10-17): `git log -p -- .claude/lab/PROGRESS.md`.

## Trạng thái hiện tại

| | |
|---|---|
| Giai đoạn đang làm | **11. Trang chủ tóm tắt và trang đọc 4 cột** ([REQ-006](../../docs/requirements/REQ-006-trang-chu-va-trang-doc.md), [ADR-0024](../../docs/adr/0024-trang-doc-chuyen-trang-khong-tai-lai.md), READ-01…READ-06): làm xong 2026-10-26, gộp vào `v3.0.0`. **10. Trang tài liệu kinh nghiệm** ([REQ-005](../../docs/requirements/REQ-005-trang-tai-lieu-kinh-nghiem.md), KB-01…KB-08): làm xong 2026-10-25, ghi chú phát hành [v3.0.0.md](../../docs/releases/v3.0.0.md); **chờ chủ dự án thử trên trình duyệt** rồi gắn tag `v3.0.0`. 8. Luyện quy trình: `v2.2.0`, `v2.3.0` đã gắn tag (v2.3.0 nghiệm thu bằng kiểm tra tự động, phần thử trên trình duyệt gộp vào v3.0.0) |
| Branch | `feature/p3-ledger-api` (đổi branch khi chủ dự án yêu cầu) |
| Môi trường | Chỉ dev (`make up`): trang public http://localhost:81/, trang quản trị http://localhost:81/admin (từ 2026-10-19). Bản demo prod-like không còn (Terraform state rỗng, kiểm tra 2026-10-17); staging, production thật, đo tải: tạm dừng |
| CI/CD | Chỉ chạy trên máy: `make ci`, `make cd`. **Trên GitHub/GitLab đang TẮT chạy tự động**; cách bật lại: [ci/README.md](../../ci/README.md) |
| Việc tiếp theo | **Chờ chủ dự án thử 13 bước trên trình duyệt** trong mục "Nghiệm thu" của [v3.0.0.md](../../docs/releases/v3.0.0.md) (KB-08 đang chặn ở bước này). Xong thì ghi kết quả vào bảng đó, `git tag -a v3.0.0`, `scripts/lane.sh release KB-08` → `claim` → `done`. Sau đó: chủ dự án viết nội dung thật; các mục trong bảng "Sau cùng" của [02-roadmap.md](../../docs/plan/02-roadmap.md), mỗi mục bắt đầu bằng đề xuất. Chủ dự án sẽ tự gom commit và merge. CI/CD tự động: chỉ khi chủ dự án yêu cầu |
| Tạm dừng | Mọi việc **mới** về đăng nhập và phân quyền (các mục "Nâng cấp để dành" trong [dang-nhap.md](../../docs/features/dang-nhap.md), loại quyền mới, khóa phòng ban ngoài Files): chỉ làm khi chủ dự án yêu cầu. Tài khoản người dùng cuối đã làm theo yêu cầu (ADR-0020) |
| Dọn dẹp | Mỗi việc xong phải dọn theo [.claude/rules/cleanup.md](../rules/cleanup.md) và [docs/runbooks/don-dep.md](../../docs/runbooks/don-dep.md). Rác trong bảng "Được xóa không cần hỏi" của don-dep.md: xóa luôn, không hỏi |

## Việc chủ dự án phải tự làm

Claude không có quyền làm những việc này:

1. Push branch và tag lên GitHub, tạo pull request.
2. Cài rclone và lên lịch sao lưu định kỳ, xem [backup-restore.md](../../docs/runbooks/backup-restore.md).

## Việc tồn đọng

Cập nhật 2026-10-19. Mỗi dòng là thứ còn mở, chưa thành việc trên bảng.

| # | Việc | Chi tiết | Ai làm |
|---|---|---|---|
| 1 | Push code và tag | Đã push và merge tới bằng chứng chỉ có file (2026-10-15). Branch trên GitHub đã bị xóa sau merge; hơn 100 commit từ đó tới nay và tag `v2.2.0` chỉ có trên máy. Chủ dự án sẽ gom commit và merge sau (2026-10-19) | Chủ dự án |
| 3 | Tiếp tục upload dở từ máy khác không kiểm tra được "đúng file" | Máy khác chỉ so tên + dung lượng | Chấp nhận, ghi trong [quan-ly-file.md](../../docs/features/quan-ly-file.md) |
| 4 | Việc để sau | Dữ liệu mẫu + HTTPS cho bản demo (DEMO-01, DEMO-02) | Chờ chủ dự án |
| 5 | Gợi ý chưa đề xuất | Khóa phòng ban mới áp dụng cho Files; bằng chứng, kỹ năng chưa bị chặn theo phòng ban | Chờ chủ dự án |

## Lưu ý môi trường

Xem [docs/dev-guide.md](../../docs/dev-guide.md) mục 6 "Các lỗi hay gặp". Thêm lỗi mới vào đó, không ghi ở đây.

- Sau khi đổi Dockerfile PHP: `make up` (tải ffmpeg lần đầu mất khoảng 20 phút trên mạng chậm). Sau khi đổi code việc nền: `make workers-restart`.
- Thêm API Files mới thì phải chặn qua `App\Services\Media\FolderAccess` và thêm test vào `FolderPolicyApiTest`.
- `e2e/run.sh` tạo tài khoản phụ `e2e_member`, `e2e_outsider`, phòng ban `E2E …` người dùng cuối `e2e-…` và danh mục `E2E …` (kèm bài viết), chạy xong tự xóa / tắt.
- Sau khi máy hoặc Docker Desktop khởi động lại, nhiều container có thể dừng với mã 127: `docker ps -a` để thấy, `docker compose -f docker/docker-compose.yml up -d` để bật lại (2026-10-19).

## Log

Mỗi dòng do `scripts/lane.sh done` tự thêm. Không sửa tay. Các dòng trước 2026-10-17 đã gom theo giai đoạn:

- 2026-10-09…13 — Dọn nền: hướng dẫn dev, kế hoạch mới (ADR-0012), dọn thư mục gốc và `docs/`, tài liệu tính năng, bỏ vai giả lập, `make seed`
- 2026-10-13…14 — Phân quyền động (ADR-0013): quyền theo API, vai trò trong DB, màn hình Vai trò, giao diện tự cập nhật quyền
- 2026-10-13…17 — Quản lý file (ADR-0014) và việc nền: MinIO, upload lớn, upload dở, xem trước, thùng rác, ảnh nhỏ, thời lượng; Reverb, hàng đợi, việc hẹn giờ
- 2026-10-14 — CI/CD gộp vào `ci/` (ADR-0016), `make ci`, `make cd`, tắt chạy tự động trên GitHub/GitLab; người dùng cuối để sau (ADR-0015)
- 2026-10-14…15 — Đăng nhập: đổi mật khẩu đăng xuất nơi khác, chặn IP, thiết bị đang đăng nhập, ghi nhớ đăng nhập; màn hình Nhật ký thay đổi
- 2026-10-15 — Skill Ledger: file đính kèm bằng chứng, bằng chứng chỉ có file, lọc bằng chứng theo file, test trình duyệt
- 2026-10-17 — Phòng ban và khóa thư mục Files (ADR-0017); ảnh nhỏ trên trang public và thùng rác; tìm file trong ô tìm kiếm chung. `make verify` pass (280 backend, 155 giao diện, 63 importer), `make e2e` 9/9

- 2026-10-17 — [lane main] CLEAN-01 done: 85f81197…79ab6c53 — README tiếng Anh, kiến trúc viết lại, bỏ 3 tài liệu lỗi thời, make clean xóa thêm 15 ảnh Docker không dùng, quy tắc + hướng dẫn dọn dẹp, bảng việc gom vào 05-done.md; dữ liệu thử trên DB dev chờ chủ dự án (không có quyền xóa vĩnh viễn)

- 2026-10-17 — [lane main] PUB-01 done: 5e8fb014 — chọn Astro; đo: RAM production 76 → 35 MB, dev 499 → 133 MB, image 301 → 248 MB, JS xuống trình duyệt 579 KB → 0

- 2026-10-17 — [lane main] PUB-02 done: 5ef7f391, fd743a67, c16bf04b — trang public chạy trên Astro (public-site), /docs chuyển hướng 301 về /skills, xóa nextjs-docs; make verify pass (280 backend, 155 giao diện, 63 importer, astro check 0 lỗi), make e2e 9/9; dọn: container và 3 image cũ, file tạm terraform init

- 2026-10-17 — [lane main] REL-01 done: 67075790 — ghi chú phát hành v2.2.0 (nổi bật, phá vỡ tương thích, nâng cấp, trước/sau, nghiệm thu 10 bước, bàn giao, lỗi đã biết, nhìn lại); tag v2.2.0 trên máy, chủ dự án push

- 2026-10-19 — [lane main] PUB-03 done: f93409cf, 83b25a64, 6129dce8, 136688ac, 54b77fe1, bd551842 — tài khoản người dùng trên trang public: đăng ký, đăng nhập (bộ đăng nhập riêng, không gọi được API quản trị), đổi mật khẩu, tự xóa tài khoản; ADR-0020; make verify pass (299 backend, 158 giao diện, 63 importer), make e2e 10/10

- 2026-10-19 — [lane main] PUB-04 done: 83b25a64, 6129dce8, 136688ac, 54b77fe1 — yêu thích, xem sau (nút ☆ 🔖, trang My lists, ~3 KB JS), màn hình End users (khóa, đặt lại mật khẩu, xóa, bật/tắt đăng ký); bỏ lịch sử đã xem theo chủ dự án; dọn: dist build thử, e2e tự xóa tài khoản thử

- 2026-10-19 — [lane main] USER-02 done: thay bằng tài khoản người dùng trên trang public (PUB-03, PUB-04, ADR-0020)

- 2026-10-19 — [lane main] PROC-01 done: 8c1e1daf — sổ tay quy trình tiếng Việt 7 chương trong docs/handbook/, mỗi chương có mục kinh nghiệm làm việc với từng vai; không khôi phục vai giả lập (chủ dự án quyết); dùng thật ở REL-02; dọn: không tạo dữ liệu hay container

- 2026-10-19 — [lane main] PUB-05 done: b92a70fc — đăng ký, đăng nhập bằng tên đăng nhập; tài khoản cũ lấy phần trước @ của email; 20 test người dùng pass; ghi chú: migration đã chạy trên DB dev (không có tài khoản người dùng nào)

- 2026-10-19 — [lane main] CMS-01 done: 0b9a2f21 — bảng danh mục + bài viết, 8 API quản trị, 3 API public (Markdown → HTML an toàn), lưu bài viết, 10 danh mục mẫu × 2 bài trên dev; 311 test backend pass

- 2026-10-19 — [lane main] CMS-02 done: 2ecf677e — menu Public content → Categories, Articles: thêm, sửa, ẩn/hiện, thứ tự, xóa, lịch sử thay đổi, link mở trang public; 160 test giao diện pass

- 2026-10-19 — [lane main] PUB-06 done: 74f1bd47 — trang public ở gốc / (trang chủ 11 thẻ: Skills + 10 danh mục), /guides/{danh mục}/{bài}, quản trị dưới /admin (/admin/login), chuyển hướng đường dẫn cũ, Astro --ignore-lock; make verify pass (311 backend, 160 giao diện, 63 importer), make e2e 11/11

- 2026-10-19 — [lane main] TEST-01 done: cacffc59 — nguyên nhân: kiểm tra email admin hỏi DNS thật, mạng chập chờn thì 3 test lỗi; tắt khi chạy test (EMAIL_DNS_CHECK), thêm test cho lúc bật; tái hiện bằng cắt DNS: trước 3 lỗi, sau pass; 312 backend pass; dọn: make clean, container tạm --rm

- 2026-10-19 — [lane main] I18N-01 done: c136ff73 — test tự quét trang quản trị, thiếu câu chữ dịch thì make fe-test / make verify / make ci báo lỗi kèm tên file; đặt trong test thay vì make lint (không phải thêm script, sửa Makefile); thử lại lỗi ô ảnh đại diện: test bắt đúng; 161 test giao diện pass; dọn: make clean

- 2026-10-20 — [lane main] OPS-01 done: 9830eb8f + docs/incidents — diễn tập 3 tình huống trên dev: khôi phục thử 32/32 bảng và file giống hệt (5 giây), hàng đợi dừng không mất việc, Redis dừng chỉ hỏng trang quản trị; gặp sự cố thật (cả trang sập sau khởi động lại), sửa bài kiểm tra sức khỏe app quản trị; phát hiện file trong thùng rác không được sao lưu (OPS-02); dọn: DB, bucket tạm, file sao lưu, file TMP, make clean

- 2026-10-20 — [lane main] OPS-02 done: 7956ef4e — sao lưu thêm file trong thùng rác (media-trash/), khôi phục đặt lại dấu đã xóa nên file về đúng thùng rác; thử thật: sao lưu, khôi phục thử vào DB và bucket tạm, file 11 MB giống hệt từng byte, gỡ dấu đã xóa tải về được; shellcheck sạch; dọn: DB, bucket tạm, file sao lưu, image shellcheck, make clean

- 2026-10-20 — [lane main] OPS-03 done: bebd5eea, 1b6945f1, f2e6ce13, d624e4ee, 8d6a611f, 85bd3ae1 — gắn thư mục thay file lẻ, mật khẩu Redis ra khỏi dòng lệnh; thử bật/tắt 20 lần: sửa Redis chết khi container khởi động lại, header Date trùng làm phình log nginx; khởi động nhanh hơn (down→up 72→40s, stop→start hỏng→24s), PHP build 1 lần, log tối đa 30 MB/container, tắt sạch (init), composer chỉ cài khi lock đổi, make start/stop, tự xóa volume rác; make verify pass (313 backend, 161 giao diện, 63 importer), make e2e 11/11; dọn: make clean, 3 image worker cũ; còn: chủ dự án thử wsl --shutdown

- 2026-10-20 — [lane main] OPS-04 done: 0b9b954f — cảnh báo cơ bản: thông báo Windows mỗi phút khi container dừng/không khỏe, việc nền chờ quá 5 phút, việc nền lỗi (make watch-on/off/status); thử thật cả 3 loại, báo một lần và báo khi hết; shellcheck sạch; dọn: hàng đợi giả trong Redis, thư mục trạng thái tạm, image shellcheck, make clean

- 2026-10-20 — [lane main] FIX-01 done: 40c05e20 — ô lọc: câu gợi ý hiện đúng tên ô (trước hiện 'filters.searchPlaceholder' và báo lỗi console), mục All trong ô chọn không còn giá trị rỗng; test mới dùng câu chữ thật và bắt đúng lỗi cũ; 163 test giao diện pass; dọn: file tạm trong scratchpad

- 2026-10-20 — [lane main] KB-01 done: 842f28b1 — REQ-005 + ADR-0022, giai đoạn 10 trong lộ trình và danh sách việc, KB-02…KB-08 lên bảng

- 2026-10-20 — [lane main] KB-02 done: 98a1322b — bỏ Skill Ledger: 9 bảng, 19 quyền, API, 4 màn hình quản trị, trang /skills (chuyển hướng về /), ☆ 🔖 chỉ còn bài viết; tìm kiếm còn tên file, trang tổng quan đếm danh mục và bài viết; file public tạm không có (quay lại ở KB-03); make verify xanh (219 backend, 146 giao diện, 63 importer); test trình duyệt viết lại ở KB-08; dọn: make clean, dữ liệu mẫu Skill Ledger trên DB dev đã xóa bằng migration

- 2026-10-20 — [lane main] KB-03 done: 645f7dbf — nhóm → danh mục có đính kèm (link/file), bài viết nhiều danh mục và cây sách tối đa 10 cấp (chặn vòng), ↑ ↓ thay ô số thứ tự, trang quản trị mới (cây danh mục, ô đính kèm, bài cha, bài con), trang public /categories, /articles, chuyển hướng /guides, /skills; dữ liệu mẫu mới (2 nhóm, 14 danh mục, sách 3 cấp); 231 backend, 151 giao diện pass; dọn: make clean

- 2026-10-20 — [lane main] KB-04 done: fd38a913 — [[bài]], ![[bài]], ![[bài#mục]], ![[file:id]] (bỏ qua code, chặn vòng, tối đa 3 lớp, bài riêng tư không lộ), neo tiêu đề + mục lục, file nhúng có mã riêng (article_media), API xem trước; ô soạn Viết / Xem trước, Chèn file, Chèn bài cho bài viết và mô tả danh mục; thử quay lui migration trên DB testing; 239 backend, 153 giao diện pass; dọn: make clean

- 2026-10-22 — [lane main] KB-05 done: ccc4c326, 84a51c4d — trang chủ kiểu diễn đàn (nhóm → danh mục, số bài, bài mới nhất), trang danh mục có tab Mới nhất / Cập nhật, tìm tiêu đề không dấu, lọc thêm danh mục, chia trang (trang nhóm gồm bài của cả nhóm), trang bài 3 cột: cây sách / bài cùng danh mục, mục lục, bài trước / sau, Menu trên điện thoại không cần JavaScript; tô màu code bằng Code có sẵn của Astro (không thêm thư viện); make verify pass (243 backend, 153 giao diện, 63 importer); test trình duyệt để KB-08; dọn: make clean, bài TMP code, file tạm; ghi chú: đầu phiên Postgres, Redis dừng mã 127 sau khi máy khởi động lại, make start chạy lại được

- 2026-10-22 — [lane main] KB-06 done: f57082a5, 14352c31, 671cb151, c57624c5 — lượt xem (một người 30 phút tính 1, chỉ lưu con số, khóa Redis, giới hạn riêng 600/phút), tab Đọc nhiều / Được lưu nhiều kèm số lượt xem và lưu, tìm kiếm bài + danh mục (quản trị có cả bài riêng tư và file; trang public /search và ô tìm đầu trang; gõ không dấu, gõ sai vẫn ra), trang tổng quan: tổng lượt xem, 5 bài đọc nhiều, 5 bài lưu nhiều, Files: cột Used in; migration thử quay lui trên DB testing; make verify pass (251 backend, 155 giao diện, 63 importer); dọn: make clean, lượt xem thử đưa về 0, ảnh chụp và file tạm

- 2026-10-22 — [lane main] KB-07 done: 6bf0b36a, d36458e2, 787d95ca, e39cb796 — make import tạo bài viết từ ghi chú Obsidian: gửi cả nội dung, bài cha (đường dẫn, tên file hoặc [[ ]]), danh mục theo tên (tên lạ được báo, không tự tạo), [[link]] và ![[nhúng]] đổi sang slug, link tới ghi chú không đăng chỉ còn chữ; ghi chú bị bỏ thì bài bị ẩn; token mới khóa article:import; thử thật trên dev với vault TMP 2 ghi chú; make verify pass (269 backend, 155 giao diện, 58 importer, astro check 0 lỗi); dọn: 2 bài TMP xóa qua service, token đã thu hồi, vault thử và file tạm, make clean

- 2026-10-25 — [lane main] REL-02 done: ba47a50d + tag v2.3.0 ở 58c40531 — nghiệm thu bằng kiểm tra tự động; thử trên trình duyệt gộp vào v3.0.0 (chủ dự án chọn, Skill Ledger đã bỏ nên 9 bước cũ không còn thử được); dọn: không tạo dữ liệu

- 2026-10-25 — [lane main] UX-01 done: cd76e65c…e1ada060 — 10 góp ý xong: lỗi đính kèm hiện trên màn hình, link phải có http(s)://, email tên miền không có thật báo riêng, sắp xếp có tiêu chí phụ + mũi tên ↑↓ + về trang 1, vai trò tiêu đề quyền cố định, Files chọn thư mục đích bằng cây, danh mục 5 cấp, màn hình danh mục là cây thu gọn/mở rộng + tìm + kéo thả (@dnd-kit), ô chọn danh mục dạng cây có tìm/chọn tất cả/bỏ chọn, mọi form kiểm tra khi nhập + khóa nút lưu + quy tắc khớp máy chủ (quản trị và trang public); make verify pass (272 backend, 175 giao diện, 58 importer), make e2e 10/10; dọn: make clean, dữ liệu E2E do e2e/run.sh xóa, file theo dõi góp ý đã xóa, 2 khóa cũ của phiên trước

- 2026-10-25 — [lane main] UX-02 done: 79996d5a — ô Bài cha của bài viết là cây theo sách, tìm không dấu theo cả đường dẫn, bài đang sửa và bài con của nó không chọn được; lint, typecheck, 176 test giao diện pass; dọn: make clean, không tạo dữ liệu

- 2026-10-25 — [lane main] UX-03 done: 87108857, 7d1c5566 — ô lọc Danh mục ở màn hình bài viết là cây có ô tick và tìm không dấu, hiện bài thuộc ít nhất một danh mục đã tick (không tính danh mục con); API nhận thêm category_ids[], category_id cũ vẫn dùng được; make verify pass (273 backend, 177 giao diện, 58 importer); dọn: make clean, không tạo dữ liệu

- 2026-10-25 — [lane main] READ-01 done: c210f7f3 — REQ-006 + ADR-0024; thử ClientRouter của Astro trên bản build: +16,4 KB JavaScript (5,6 KB nén), HTML máy chủ vẫn đủ nội dung (SEO giữ nguyên); READ-02…READ-06 lên bảng; dọn: bản build thử và máy chủ thử trong container đã xóa, không tạo dữ liệu

- 2026-10-25 — [lane main] READ-02 done: a9101091, fc6d8d81 — DB: danh mục có lượt xem, ☆ danh mục, 🔖 nhớ mục đang đọc (migration thử quay lui trên DB testing, đã chạy trên DB dev); API: cây danh mục kèm số bài/lượt xem/yêu thích, danh sách bài theo tab (thêm Yêu thích nhiều), báo lượt xem danh mục, lưu mục đang đọc, tìm kiếm có found_in + đoạn trích tô đậm + mục trong bài; trang Của tôi mở danh mục / đúng mục đã đọc; make verify pass (280 backend, 177 giao diện, 58 importer, astro check 0 lỗi); dọn: make clean, file tạm, không tạo dữ liệu TMP

- 2026-10-25 — [lane main] READ-03 done: c02fd6bb — trang chủ tóm tắt: ô tìm lớn, thẻ danh mục (In order theo nhóm / Most viewed / Most favorited, 24 thẻ), tab bài Most read / Most favorited / Newest (10 bài), See more mở mục đầu tiên của tab; đổi tab bằng link, không thêm JavaScript; astro check 0 lỗi, make lint pass, make e2e 10/10; dọn: make clean, file tạm, không tạo dữ liệu TMP (dữ liệu E2E đã được run.sh xóa)

- 2026-10-25 — [lane main] READ-04 done: c5fc99b1, 77ee06cb — trang đọc 4 cột cho /categories và /articles: cây danh mục giữ chỗ cuộn, bài của danh mục (?in=), nội dung + đường dẫn, mục lục sáng theo chỗ cuộn; lọc không dấu và thu gọn từng cột (nhớ trong trình duyệt); chuyển trang không tải lại bằng ClientRouter (chỉ ở trang đọc); ☆ danh mục; lượt đọc thật và lượt xem danh mục do trình duyệt báo qua /views; nhớ mục đang đọc của bài 🔖; sửa nginx chặn script dev của Astro (/.pnpm/); thử trên trình duyệt 25/25 bước, astro check 0 lỗi, make lint pass, make e2e 10/10; dọn: bài TMP đã xóa, lượt xem thử của danh mục Thực hiện về 0, tài khoản thử đã xóa, bộ đếm đăng ký của IP container kiểm thử đã xóa, make clean

- 2026-10-26 — [lane main] READ-05 done: 2d4ef6cd — trang tìm kiếm một danh sách có nhãn Category / Article / Section / Content, từ khóa tô đậm (tiêu đề tô ở trang, đoạn trích từ API), bấm Section mở bài và cuộn tới đúng mục; thử trên dữ liệu dev đủ 4 loại; astro check 0 lỗi, make lint pass, make e2e 10/10 (thêm bước tìm kiếm); phát sinh OPS-05 (postgres/redis Exited 127 sau khởi động lại); dọn: make clean, dữ liệu E2E đã được run.sh xóa, không tạo dữ liệu TMP

- 2026-10-26 — [lane main] READ-06 done: b7772abb, f3baded8, 697f127c, e0d7b128 — test trình duyệt cho trang đọc (tab Newest + See more, chuyển bài không tải lại + Back, mục lục sáng, 🔖 nhớ mục); test bắt 2 lỗi, sửa riêng: nginx dev chặn file Vite (429) làm chuyển trang tải lại cả trang, chỗ đọc không lưu khi cuộn trước lúc danh sách đã lưu tải xong; JS trang đọc 25,8 KB (dưới 30 KB); ghi chú v3.0.0 gộp REQ-006, nghiệm thu trình duyệt 13 bước; make verify pass (280 backend, 177 giao diện, 58 importer, astro check 0 lỗi), make e2e 10/10; OPS-05: log nginx cũng báo thiếu nginx.conf lúc khởi động lại 10:31; dọn: make clean, bản build và máy chủ thử đã xóa, dữ liệu E2E do run.sh xóa, không tạo dữ liệu TMP

## Next step

1. Đầu phiên: đọc file này, rồi `scripts/lane.sh status`. Bảng việc chỉ còn các việc "để sau" theo quyết định chủ dự án.
2. Chủ dự án sẽ tự gom commit và merge ("Việc tồn đọng" mục 1): không nhắc lại.
3. Việc lớn đi theo [sổ tay quy trình](../../docs/handbook/README.md). KB-08 chờ chủ dự án thử trên trình duyệt, rồi gắn tag `v3.0.0`.
4. `.claude/settings.json` còn 2 dòng quyền cho `ml-nextjs-docs` (container đã xóa): vô hại, chủ dự án tự xóa nếu muốn.
