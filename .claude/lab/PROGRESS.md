# Nhật ký tiến độ

Đọc file này đầu tiên khi bắt đầu một phiên làm việc mới. Việc đã xong gom theo tính năng: [docs/plan/05-done.md](../../docs/plan/05-done.md). Nhật ký đầy đủ trước khi rút gọn (2026-10-17): `git log -p -- .claude/lab/PROGRESS.md`.

## Trạng thái hiện tại

| | |
|---|---|
| Giai đoạn đang làm | Chưa có. Đã xong 2026-10-26: **10. Trang tài liệu kinh nghiệm** ([REQ-005](../../docs/requirements/REQ-005-trang-tai-lieu-kinh-nghiem.md)) và **11. Trang chủ tóm tắt và trang đọc 4 cột** ([REQ-006](../../docs/requirements/REQ-006-trang-chu-va-trang-doc.md)): chủ dự án thử 13 bước trên trình duyệt, đạt; tag `v3.0.0` trên máy ([ghi chú phát hành](../../docs/releases/v3.0.0.md)). 8. Luyện quy trình: `v2.2.0`, `v2.3.0` đã gắn tag |
| Branch | `feature/p3-ledger-api` (đổi branch khi chủ dự án yêu cầu) |
| Môi trường | Chỉ dev (`make up`): trang public http://localhost:81/, trang quản trị http://localhost:81/admin (từ 2026-10-19). Bản demo prod-like không còn (Terraform state rỗng, kiểm tra 2026-10-17); staging, production thật, đo tải: tạm dừng |
| CI/CD | Chỉ chạy trên máy: `make ci`, `make cd`. **Trên GitHub/GitLab đang TẮT chạy tự động**; cách bật lại: [ci/README.md](../../ci/README.md) |
| Việc tiếp theo | Hướng **A: chủ dự án viết nội dung thật** (chủ dự án chọn 2026-10-26): Claude viết đề xuất, chủ dự án xác nhận rồi mới làm. Sau đó các mục trong bảng "Sau cùng" của [02-roadmap.md](../../docs/plan/02-roadmap.md), mỗi mục bắt đầu bằng đề xuất. CI/CD tự động: bỏ qua (chủ dự án nhắc lại 2026-10-26). Chủ dự án sẽ tự gom commit, push và merge |
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
| 1 | Push code và tag | Đã push và merge tới bằng chứng chỉ có file (2026-10-15). Branch trên GitHub đã bị xóa sau merge; hơn 100 commit từ đó tới nay và các tag `v2.2.0`, `v2.3.0`, `v3.0.0` chỉ có trên máy. Chủ dự án sẽ gom commit và merge sau (2026-10-19) | Chủ dự án |
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

Mỗi dòng do `scripts/lane.sh done` tự thêm; ghi chú khi xong việc tối đa 1–2 câu (commit, kết quả, đã dọn gì). Không sửa tay. Chỉ giữ khoảng 10 dòng gần nhất; dòng cũ hơn gom theo tính năng trong [05-done.md](../../docs/plan/05-done.md), bản đầy đủ: `git log -p -- .claude/lab/PROGRESS.md` (rút gọn lần cuối 2026-10-27).

- 2026-10-09…25 — Đã gom vào [05-done.md](../../docs/plan/05-done.md): dọn nền, phân quyền động, Files và việc nền, CI/CD trên máy, đăng nhập, phòng ban, trang public Astro, người dùng cuối, nội dung public, sổ tay quy trình, vận hành, giai đoạn 10 (trang tài liệu kinh nghiệm), nhập từ Obsidian, `v2.2.0`, `v2.3.0`
- 2026-10-25 — [lane main] READ-02 done: a9101091, fc6d8d81 — DB và API cho lượt xem, ☆ danh mục, 🔖 nhớ mục đang đọc
- 2026-10-25 — [lane main] READ-03 done: c02fd6bb — trang chủ tóm tắt: ô tìm, thẻ danh mục, tab bài
- 2026-10-25 — [lane main] READ-04 done: c5fc99b1, 77ee06cb — trang đọc 4 cột cho /categories và /articles
- 2026-10-26 — [lane main] READ-05 done: 2d4ef6cd — trang tìm kiếm một danh sách có nhãn, từ khóa tô đậm
- 2026-10-26 — [lane main] READ-06 done: b7772abb, f3baded8, 697f127c, e0d7b128 — test trình duyệt cho trang đọc
- 2026-10-26 — [lane main] READ-07 done: 5f8255ca, 4362d930 — header cố định một ô tìm, chỉnh cột và thanh cuộn theo góp ý
- 2026-10-26 — [lane main] KB-08 done: 3828de7f, ddd30644, tag v3.0.0 — chủ dự án thử 13 bước trên trình duyệt, đạt; tag trên máy
- 2026-10-26 — [lane main] OPS-05 done: 88bf8c5e, 65fa3957, 2605ce66 — Postgres, Redis hỏng sau khởi động lại vì gắn cấu hình từ máy; sửa: chép cấu hình vào image
- 2026-10-26 — [lane main] CONTENT-01 done: daf68edd — sao lưu, xóa dữ liệu thử qua service của ứng dụng; vault 84 ghi chú, chưa ghi chú nào `publish: true`

## Next step

1. Đầu phiên: đọc file này, rồi `scripts/lane.sh status`. Bảng việc chỉ còn các việc "để sau" theo quyết định chủ dự án.
2. Chủ dự án sẽ tự gom commit và merge ("Việc tồn đọng" mục 1): không nhắc lại.
3. Việc lớn đi theo [sổ tay quy trình](../../docs/handbook/README.md). Nhập ảnh / file Obsidian (CONTENT-02) đã duyệt thiết kế, chờ có bài thật nhúng ảnh ([REQ-007](../../docs/requirements/REQ-007-nhap-anh-file-obsidian.md)).
4. `.claude/settings.json` còn 2 dòng quyền cho `ml-nextjs-docs` (container đã xóa): vô hại, chủ dự án tự xóa nếu muốn.
