# Danh sách việc

Cập nhật: 2026-10-14. Lộ trình: [02-roadmap.md](02-roadmap.md).

Mã việc (cột đầu) chỉ để script chia việc `scripts/lane.sh` nhận diện; khi nói chuyện và viết tài liệu thì dùng tên việc. Trạng thái: `todo` → `doing` → `done`. Không sửa cột trạng thái bằng tay.

Các giai đoạn 2–6 mới ghi ở mức việc lớn; khi bắt đầu giai đoạn, bước đề xuất thiết kế sẽ chia nhỏ tiếp.

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
| MEDIA-01 | Đề xuất thiết kế: thư mục, upload nhỏ, upload lớn chia phần, file tạm, xử lý nền, cách hiện tiến trình; rà lại cấu hình MinIO | Đề xuất được xác nhận | done (ee2a34b6, 072dbf3f — ADR-0014: upload thẳng trình duyệt → kho chính MinIO, việc nền + Reverb báo kết quả (dùng chung), tắt đọc công khai; thêm việc NEN-08) |
| MEDIA-02 | Backend: kết nối MinIO, thư mục, upload file nhỏ, tải về, xóa, test | Upload file nhỏ chạy được | done (e511b8b9, 0c0a42a7, 5a247218 — API quản lý file: thư mục, upload ≤100 MB bằng link ký tạm qua nginx, tải về, đổi tên/di chuyển, xóa; tắt đọc công khai MinIO; make verify pass, chạy thật qua localhost:81) |
| NEN-08 | Việc nền và thông báo tức thì, dùng chung cho mọi tính năng: hàng đợi Redis, container việc nền, Reverb, việc hẹn giờ, kênh riêng mỗi người, sự kiện "việc nền đã xong", test | Một việc nền thử báo kết quả lên màn hình | done (b306b727, 028c0b64, fe24c599, 14fc3604 — hàng đợi Redis + 3 container (việc nền, hẹn giờ, Reverb), kênh riêng mỗi người, sự kiện dùng chung, chống lỡ thông báo, nút thử ở Dashboard; make verify + 3 e2e pass) |
| MEDIA-03 | Upload file lớn chia phần, xử lý sau upload bằng việc nền, dọn upload treo | Upload file vài GB chạy được | done (63219762, b724bd5f, 8e548c11 — upload file lớn chia phần (tới 10 GB, 4 phần song song, thử lại), việc nền ghép + kiểm tra, báo kết quả tức thì, dọn upload treo mỗi giờ; thử thật file 3 GiB khớp SHA-256; make verify pass) |
| MEDIA-04 | Giao diện quản lý file | Màn hình quản lý file | done (6db22c3b, 7e261a3a, 73213bd9 — màn hình Files: thư mục, kéo-thả upload có %, hủy, tải về, đổi tên, di chuyển, xóa, tự làm mới khi việc nền báo xong; make verify + 4 e2e pass (file 120 MB)) |

## 4. Người dùng cuối, phòng ban, chính sách

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| USER-01 | Đề xuất thiết kế dựa trên bản `v1.0.0` | Đề xuất được xác nhận | done (a9f776c2 — chủ dự án quyết để sau cả người dùng cuối, phòng ban, chính sách (ADR-0015); phạm vi hiện tại chỉ phân quyền theo vai trò) |
| USER-02 | Quản lý người dùng cuối: API + giao diện + test | Màn hình người dùng cuối | blocked: để sau (ADR-0015) |
| USER-03 | Phòng ban, chính sách phòng ban, gán admin vào phòng ban: API + giao diện + test | Màn hình phòng ban, chính sách | todo (để sau, ADR-0015) |

## 5. CI/CD gộp chung, rồi tắt

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| CI-01 | Đề xuất: thư mục `ci/` chung, các script kiểm tra / build / deploy, file cấu hình mỏng cho GitHub và GitLab | Đề xuất được xác nhận | done (031c489f — ADR-0016: CI gộp vào ci/, container riêng, make ci song song make verify, bỏ deploy blue-green hỏng, build image ở ci/build.sh) |
| CI-02 | Làm và test: `make ci` trên máy, chạy tay trên GitHub | Hai nơi cho kết quả giống nhau | done (54776ac9…0a6f319a — make ci chạy trên máy: 203 test backend, 105 test giao diện, 63 test importer, build 3 image; chạy trên GitHub để sau (chủ dự án quyết 2026-10-14)) |
| CD-01 | CD trên máy: bản demo prod-like bằng Terraform (MinIO, việc nền, Reverb, đăng nhập), `make cd` = kiểm tra → build → deploy → test trình duyệt | `make cd` xanh, đăng nhập và dùng được bản demo ở http://localhost:9443 | done (39e7c75d…044bd903 — make cd: kiểm tra → build 4 image → Terraform prod-like (MinIO, việc nền, Reverb, root) → 4 test trình duyệt pass trên http://localhost:9443; sửa tên cookie phiên) |
| CI-03 | Tắt chạy tự động, ghi hướng dẫn bật lại | Merge pull request không bị chặn | done (044bd903 — GitHub/GitLab không chạy gì khi push/PR (chỉ chạy tay), hướng dẫn bật lại trong ci/README.md; cleanup-branch và Dependabot giữ nguyên) |

## 6. Nâng cấp trên dev

Thêm ngày 2026-10-14, theo thứ tự làm. Lấy từ các mục "chưa làm" trong [đăng nhập](../features/dang-nhap.md), [nhật ký thay đổi](../features/nhat-ky-thay-doi.md) và [ADR-0014](../adr/0014-media-minio-upload.md).

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| AUTH-01 | Đổi mật khẩu thì đăng xuất mọi thiết bị khác: viết test chứng minh, thiếu thì sửa | Lộ mật khẩu thì đổi mật khẩu là cắt được người đang dùng | done (bdff0fd3, 4eed314b — đổi mật khẩu thì thiết bị khác bị đăng xuất, thiết bị đang dùng giữ phiên (sửa lỗi tự đăng xuất chính mình + lỗi 500 khi mật khẩu null); 86 test + chạy thật trên dev) |
| AUTH-02 | Giao diện tự cập nhật quyền: hỏi lại quyền khi quay lại tab và khi gặp lỗi 403 | Đổi quyền thì menu và nút đổi theo, không phải tải lại trang | done (52599bf8 — quyền trên giao diện tự lấy lại khi quay lại tab và khi gặp 403 (tối đa 5 giây/lần); 109 test giao diện, lint, typecheck pass) |
| LOG-01 | Màn hình Nhật ký thay đổi: danh sách, lọc theo loại dữ liệu, người làm, thời gian; phân trang | Xem được toàn bộ nhật ký, không chỉ 10 dòng ở trang tổng quan | done (7a2f444f, c68bf280 — màn hình Nhật ký thay đổi (lọc loại, việc, người làm, ngày; phân trang; xem ô cũ → mới); sửa lọc ngày đọc nhầm tháng/ngày (500 hoặc lọc sai); 207 test backend, 112 test giao diện) |
| AUTH-03 | Giới hạn đăng nhập sai theo IP, ngoài giới hạn theo tên + IP đang có | Một IP thử hàng loạt tên đăng nhập bị chặn | todo |
| AUTH-04 | **[XÁC NHẬN]** Màn hình Phiên đăng nhập: xem thiết bị đang đăng nhập, đăng xuất một thiết bị, đăng xuất mọi nơi khác (thêm bảng DB) | Đá được phiên từ xa | todo |
| AUTH-05 | **[XÁC NHẬN]** Ghi nhớ đăng nhập | Không phải đăng nhập lại sau 120 phút không dùng | todo |
| MEDIA-05 | Upload dở tiếp tục được sau khi tải lại trang | File lớn không phải gửi lại từ đầu | todo |

## 7. Sau cùng

| Mã | Việc | Kết quả | Trạng thái |
|---|---|---|---|
| DEMO-01 | **[XÁC NHẬN]** Dữ liệu mẫu cho bản demo prod-like (tài khoản Chỉ xem + Skill Ledger mẫu) | Bản demo mở ra có dữ liệu | blocked: để sau (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
| DEMO-02 | **[XÁC NHẬN]** HTTPS cho bản demo prod-like | Bản demo chạy HTTPS | blocked: để sau (chủ dự án quyết 2026-10-14: chỉ làm trên dev) |
