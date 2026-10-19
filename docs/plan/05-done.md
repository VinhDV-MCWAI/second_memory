# Những việc đã làm

Cập nhật: 2026-10-17. Tóm tắt các việc đã xong từ khi đổi hướng ngày 2026-10-09 ([ADR-0012](../adr/0012-restore-features-dev-first.md)), gom theo tính năng. Cách dùng từng tính năng: file tương ứng trong [docs/features/](../features/). Chi tiết từng commit: `git log`. Việc trước 2026-10-09 (thu gọn, Skill Ledger, Terraform): [docs/releases/](../releases/) và [archive/engineering-lab/](../archive/engineering-lab/).

## Theo tính năng

| Tính năng | Đã làm | Ngày xong | Tài liệu |
|---|---|---|---|
| Nền dự án | Hướng dẫn dev; kế hoạch mới; dọn thư mục gốc; rút gọn `docs/`; mỗi tính năng một file; bỏ các vai giả lập (PO, QA, Ops do AI đóng); dữ liệu mẫu `make seed` | 10-13 | [dev-guide.md](../dev-guide.md) |
| Phân quyền động | Mỗi API quản trị là một quyền, kiểm tra ở mỗi request; vai trò lưu trong DB; màn hình Vai trò với ma trận quyền; menu và nút ẩn theo quyền; giao diện tự cập nhật quyền khi quay lại tab hoặc gặp 403 | 10-14 | [phan-quyen-dong.md](../features/phan-quyen-dong.md), [ADR-0013](../adr/0013-dynamic-permissions.md) |
| Việc nền và thông báo tức thì | Hàng đợi Redis, container việc nền, việc hẹn giờ, Reverb; kênh riêng mỗi người; không lỡ thông báo khi tải lại trang | 10-13 | [viec-nen-va-thong-bao.md](../features/viec-nen-va-thong-bao.md), [ADR-0014](../adr/0014-media-minio-upload.md) |
| Quản lý file | Thư mục; upload thẳng lên MinIO bằng link ký tạm, file lớn tới 10 GB chia phần (thử thật 3 GB); tiếp tục upload dở; xem trước; thùng rác 30 ngày và tự xóa hẳn khi quá hạn; kích thước ảnh, thời lượng video và nhạc, ảnh nhỏ (ffmpeg); cột cho biết file đang gắn vào bằng chứng nào | 10-17 | [quan-ly-file.md](../features/quan-ly-file.md) |
| Skill Ledger | Gắn tới 10 file vào bằng chứng; bằng chứng chỉ cần link hoặc file; lọc bằng chứng theo file | 10-15 | [skill-ledger.md](../features/skill-ledger.md) |
| Trang public | Hiện file đính kèm của bằng chứng công khai, có ảnh nhỏ, link tạm mỗi lần bấm; viết lại bằng Astro thay Next.js (RAM production 76 → 35 MB, không gửi JavaScript), `/docs` cũ chuyển hướng về `/skills` | 10-17 | [trang-public.md](../features/trang-public.md), [ADR-0019](../adr/0019-public-user-frontend.md) |
| Đăng nhập | Đổi mật khẩu thì đăng xuất thiết bị khác; chặn một IP đăng nhập sai 30 lần; màn hình thiết bị đang đăng nhập, đăng xuất từ xa; ghi nhớ đăng nhập 30 ngày | 10-15 | [dang-nhap.md](../features/dang-nhap.md) |
| Phòng ban | Thêm / sửa / xóa / tìm phòng ban; admin thuộc nhiều phòng ban; khóa thư mục Files theo phòng ban (chặn mọi API Files, khóa lồng nhau, ổ khóa trên giao diện) | 10-17 | [phong-ban.md](../features/phong-ban.md), [ADR-0017](../adr/0017-departments-folder-policies.md) |
| Nhật ký thay đổi | Màn hình nhật ký: lọc theo loại, việc, người làm, ngày; xem giá trị cũ → mới | 10-14 | [nhat-ky-thay-doi.md](../features/nhat-ky-thay-doi.md) |
| Tìm kiếm | Ô tìm kiếm chung có thêm file và thư mục (không dấu, gõ sai vẫn ra, tôn trọng khóa phòng ban) | 10-17 | [tim-kiem.md](../features/tim-kiem.md) |
| CI/CD | Gộp vào `ci/` dùng chung cho GitHub và GitLab; `make ci` trên máy; `make cd` dựng bản demo prod-like bằng Terraform; tắt chạy tự động | 10-14 | [ci/README.md](../../ci/README.md), [ADR-0016](../adr/0016-ci-shared-folder.md) |
| Người dùng cuối | Người xem trang public tự đăng ký (email, mật khẩu), lưu yêu thích và xem sau kỹ năng, bằng chứng; trang My lists; admin khóa / mở, đặt lại mật khẩu, bật / tắt đăng ký ở màn hình End users; bộ đăng nhập riêng, không gọi được API quản trị | 10-19 | [nguoi-dung-cuoi.md](../features/nguoi-dung-cuoi.md), [ADR-0020](../adr/0020-end-user-accounts.md) |
| Đóng gói `v2.2.0` | Ghi chú phát hành, nghiệm thu, bàn giao, nhìn lại; tag trên máy | 10-17 | [v2.2.0.md](../releases/v2.2.0.md) |
| Dọn dẹp | Bỏ tài liệu lỗi thời; viết lại README (tiếng Anh) và tài liệu kiến trúc; `make clean` xóa thêm ảnh Docker không dùng; quy tắc và hướng dẫn dọn dẹp sau mỗi việc; rút gọn bảng việc | 10-17 | [don-dep.md](../runbooks/don-dep.md) |

## Quyết định để sau

| Việc | Lý do | Tài liệu |
|---|---|---|
| Dữ liệu mẫu và HTTPS cho bản demo prod-like | Chỉ làm trên dev tới giai đoạn cuối | [infra/README.md](../../infra/README.md) |

## Kết quả kiểm tra gần nhất

2026-10-19: `make verify` pass (299 test backend, 158 test giao diện, 63 test công cụ nhập Obsidian, `astro check` 0 lỗi), `make e2e` 10/10 pass.
