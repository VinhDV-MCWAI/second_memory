# Nhật ký tiến độ

Đọc file này đầu tiên khi bắt đầu một phiên làm việc mới. Việc đã xong gom theo tính năng: [docs/plan/05-done.md](../../docs/plan/05-done.md). Nhật ký đầy đủ trước khi rút gọn (2026-10-17): `git log -p -- .claude/lab/PROGRESS.md`.

## Trạng thái hiện tại

| | |
|---|---|
| Giai đoạn đang làm | 8. Luyện quy trình ([ADR-0018](../../docs/adr/0018-lifecycle-practice-track.md), không gồm CI/CD) và 9. Trang cho người dùng ([ADR-0019](../../docs/adr/0019-public-user-frontend.md), chờ chọn phương án) |
| Branch | `feature/p3-ledger-api` (đổi branch khi chủ dự án yêu cầu) |
| Môi trường | Chỉ dev (`make up`, http://localhost:81). Bản demo prod-like không còn (Terraform state rỗng, kiểm tra 2026-10-17); staging, production thật, đo tải: tạm dừng |
| CI/CD | Chỉ chạy trên máy: `make ci`, `make cd`. **Trên GitHub/GitLab đang TẮT chạy tự động**; cách bật lại: [ci/README.md](../../ci/README.md) |
| Việc tiếp theo | Nhận được ngay: đóng gói bản `v2.2.0` (REL-01); chọn công nghệ trang cho người dùng (PUB-01, chờ chủ dự án trả lời ADR-0019). CI/CD tự động: chỉ khi chủ dự án yêu cầu. Việc để sau: bảng "Sau cùng" trong [02-roadmap.md](../../docs/plan/02-roadmap.md) |
| Tạm dừng | Mọi việc **mới** về đăng nhập và phân quyền (các mục "Nâng cấp để dành" trong [dang-nhap.md](../../docs/features/dang-nhap.md), loại quyền mới, người dùng cuối, khóa phòng ban ngoài Files): chỉ làm khi chủ dự án yêu cầu |
| Dọn dẹp | Mỗi việc xong phải dọn theo [.claude/rules/cleanup.md](../rules/cleanup.md) và [docs/runbooks/don-dep.md](../../docs/runbooks/don-dep.md). Rác trong bảng "Được xóa không cần hỏi" của don-dep.md: xóa luôn, không hỏi |

## Việc chủ dự án phải tự làm

Claude không có quyền làm những việc này:

1. Push branch và tag lên GitHub, tạo pull request.
2. Cài rclone và lên lịch sao lưu định kỳ, xem [backup-restore.md](../../docs/runbooks/backup-restore.md).

## Việc tồn đọng

Cập nhật 2026-10-17. Mỗi dòng là thứ còn mở, chưa thành việc trên bảng.

| # | Việc | Chi tiết | Ai làm |
|---|---|---|---|
| 1 | Push code | Đã push và merge tới bằng chứng chỉ có file (2026-10-15). Branch trên GitHub đã bị xóa sau merge; mọi commit từ đó tới nay chỉ có trên máy | Chủ dự án |
| 2 | Dữ liệu thử còn trên DB dev | Chủ dự án đã cho xóa vĩnh viễn (2026-10-17), nhưng hệ thống quyền của Claude Code chặn lệnh. Lệnh soạn sẵn đã đưa chủ dự án tự chạy: xóa vai trò `test full`, tài khoản `tmp_auth01`, `lab_viewer`, hai file `big-….bin` 3 GB, `smoke-….txt`, thư mục `Smoke locked`, `Hợp đồng thử`. Giữ `Do_An_Tot_Nghiep.docx`. Kiểm tra lại bằng câu SQL trong [don-dep.md](../../docs/runbooks/don-dep.md) mục 3 | Chủ dự án |
| 3 | Tiếp tục upload dở từ máy khác không kiểm tra được "đúng file" | Máy khác chỉ so tên + dung lượng | Chấp nhận, ghi trong [quan-ly-file.md](../../docs/features/quan-ly-file.md) |
| 4 | Việc để sau | Người dùng cuối (ADR-0015); dữ liệu mẫu + HTTPS cho bản demo | Chờ chủ dự án |
| 5 | Gợi ý chưa đề xuất | Khóa phòng ban mới áp dụng cho Files; bằng chứng, kỹ năng chưa bị chặn theo phòng ban | Chờ chủ dự án |

## Lưu ý môi trường

Xem [docs/dev-guide.md](../../docs/dev-guide.md) mục 6 "Các lỗi hay gặp". Thêm lỗi mới vào đó, không ghi ở đây.

- Sau khi đổi Dockerfile PHP: `make up` (tải ffmpeg lần đầu mất khoảng 20 phút trên mạng chậm). Sau khi đổi code việc nền: `make workers-restart`.
- Thêm API Files mới thì phải chặn qua `App\Services\Media\FolderAccess` và thêm test vào `FolderPolicyApiTest`.
- `e2e/run.sh` tạo tài khoản phụ `e2e_member`, `e2e_outsider` và phòng ban `E2E …`, chạy xong tự xóa / tắt.

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

## Next step

1. Đầu phiên: đọc file này, rồi `scripts/lane.sh status`. Bảng việc chỉ còn các việc "để sau" theo quyết định chủ dự án.
2. Nhắc chủ dự án push branch nếu chưa push, và kiểm tra dữ liệu thử đã xóa chưa ("Việc tồn đọng" mục 2).
3. Việc nhận được: REL-01 (đóng gói `v2.2.0`), PUB-01 (đề xuất ADR-0019 đã viết, chờ chủ dự án chọn phương án rồi mới thử nhanh). Mỗi việc: `claim` → làm → test nhanh → dọn dẹp → `done`; kỹ năng `/lab-task` đi qua từng bước.
