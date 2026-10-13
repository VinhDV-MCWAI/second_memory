# Danh sách việc

Cập nhật: 2026-10-09. Lộ trình: [02-roadmap.md](02-roadmap.md).

Mã việc (cột đầu) chỉ để script chia việc `scripts/lane.sh` nhận diện; khi nói chuyện và viết tài liệu thì dùng tên việc. Trạng thái: `todo` → `doing` → `done`. Không sửa cột trạng thái bằng tay.

Các giai đoạn 2–5 mới ghi ở mức việc lớn; khi bắt đầu giai đoạn, bước đề xuất thiết kế sẽ chia nhỏ tiếp.

## 1. Dọn nền

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| NEN-01 | Tài liệu môi trường dev: chạy lần đầu, các địa chỉ, sửa code thì làm gì, test nhanh, xem lỗi ở đâu, lỗi hay gặp | `docs/dev-guide.md` | done (2026-10-09) |
| NEN-02 | Kế hoạch mới, quy ước tài liệu tiếng Việt, quyết định đổi hướng; chuyển kế hoạch cũ vào archive | `docs/plan/`, ADR-0012 | done (2026-10-09) |
| NEN-03 | Dọn thư mục gốc: xóa `start.sh` (chỉ gọi `make up`), chuyển `setup-env.sh` vào `scripts/`; sửa Makefile và README | Thư mục gốc chỉ còn file cấu hình | done (42ce9580, 774c59da — setup-env.sh chuyển vào scripts/, xóa start.sh, sửa mọi chỗ nhắc tới) |
| NEN-04 | Rút gọn `docs/`: giữ hướng dẫn dev, kế hoạch, quyết định, ghi chú phát hành, hướng dẫn vận hành; chuyển sổ tay quy trình, mẫu tài liệu, báo cáo, hồ sơ yêu cầu và thiết kế của giai đoạn cũ vào archive; viết lại `docs/README.md` | `docs/` gọn, có mục lục tiếng Việt | done (78462665, e4743898 — docs/ còn plan, adr, releases, runbooks, architecture; tài liệu Engineering Lab vào archive; mục lục tiếng Việt) |
| NEN-05 | Mỗi tính năng hiện có một file mô tả trong `docs/features/`: đăng nhập, quản trị viên, Skill Ledger, tìm kiếm, nhật ký thay đổi, trang public, nhập từ Obsidian | `docs/features/*.md` | done (982773ee — docs/features/: 7 file mô tả tính năng + mục lục; docs/README.md trỏ tới) |
| NEN-06 | Dọn `.claude/`: bỏ các vai giả lập (PO, QA, Ops), thay skill làm việc theo luồng mới (đề xuất → xác nhận → làm → test), cập nhật quy tắc tài liệu trong các file rules và CLAUDE.md | `.claude/` chỉ còn thứ đang dùng | done (d0e0895f — bỏ 3 vai giả lập và skill lane (gộp vào lab-task), rules + CLAUDE.md theo cách làm một branch) |
| NEN-07 | Seeder dữ liệu mẫu cho dev: vài skill, bằng chứng, mục tiêu, nhãn, một tài khoản chỉ xem | Mở trang quản trị là có dữ liệu để thử | done (31dfab61, bb6fe637, 300cd787 — make seed: tài khoản chỉ xem viewer + 5 skill, 6 bằng chứng, 4 mục tiêu, 5 nhãn; chạy lại không trùng) |

## 2. Phân quyền động

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| QUYEN-01 | Đề xuất thiết kế: vai trò, quyền, chức năng, cách tự đăng ký API thành quyền, chuyển vai trò owner/viewer hiện tại sang | Đề xuất được xác nhận | done (4dc16a4b — ADR-0013: phân quyền động bằng spatie, chọn phương án A (gom theo tên quyền, Chủ toàn quyền, nhiều vai trò)) |
| QUYEN-02 | Backend: cài thư viện, bảng, kiểm tra quyền mỗi request, lệnh đồng bộ API → quyền, vai trò mặc định, test | API bị chặn đúng theo quyền | done (82e2290a, df35bb8a, b8df0fda, bed9905a — mỗi API quản trị là một quyền, kiểm tra mỗi request, vai trò Chủ/Chỉ xem chuyển vào DB, make permissions; 176 test pass) |
| QUYEN-03 | API quản lý vai trò, quyền, gán vai trò cho admin | Các API mới trong OpenAPI | done (8e6bb0fd — API vai trò (thêm/sửa/xóa, quyền), danh sách quyền theo chức năng, admin nhận role_ids, bỏ cột role, me trả permissions; make verify pass) |
| QUYEN-04 | Giao diện: màn hình vai trò, ma trận quyền theo chức năng, gán vai trò; ẩn menu và nút theo quyền | Cấu hình quyền không cần sửa code | done (83e45637, 8dc53eb3 — màn hình Vai trò + ma trận quyền, menu và nút ẩn theo quyền; 79 test FE, 2 e2e, make verify pass) |

## 3. Media MinIO + upload

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| MEDIA-01 | Đề xuất thiết kế: thư mục, upload nhỏ, upload lớn chia phần, file tạm, xử lý nền, cách hiện tiến trình; rà lại cấu hình MinIO | Đề xuất được xác nhận | doing |
| MEDIA-02 | Backend: kết nối MinIO, thư mục, upload file nhỏ, tải về, xóa, test | Upload file nhỏ chạy được | todo |
| NEN-08 | Việc nền và thông báo tức thì, dùng chung cho mọi tính năng: hàng đợi Redis, container việc nền, Reverb, việc hẹn giờ, kênh riêng mỗi người, sự kiện "việc nền đã xong", test | Một việc nền thử báo kết quả lên màn hình | todo |
| MEDIA-03 | Upload file lớn chia phần, xử lý sau upload bằng việc nền, dọn upload treo | Upload file vài GB chạy được | todo |
| MEDIA-04 | Giao diện quản lý file | Màn hình quản lý file | todo |

## 4. Người dùng cuối, phòng ban, chính sách

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| USER-01 | Đề xuất thiết kế dựa trên bản `v1.0.0` | Đề xuất được xác nhận | todo |
| USER-02 | Quản lý người dùng cuối: API + giao diện + test | Màn hình người dùng cuối | todo |
| USER-03 | Phòng ban, chính sách phòng ban, gán admin vào phòng ban: API + giao diện + test | Màn hình phòng ban, chính sách | todo |

## 5. CI/CD gộp chung, rồi tắt

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| CI-01 | Đề xuất: thư mục `ci/` chung, các script kiểm tra / build / deploy, file cấu hình mỏng cho GitHub và GitLab | Đề xuất được xác nhận | todo |
| CI-02 | Làm và test: `make ci` trên máy, chạy tay trên GitHub | Hai nơi cho kết quả giống nhau | todo |
| CI-03 | Tắt chạy tự động, ghi hướng dẫn bật lại | Merge pull request không bị chặn | todo |
