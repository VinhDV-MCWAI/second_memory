# Lộ trình

Cập nhật: 2026-10-20. Mục tiêu và cách làm việc: [01-goals-and-workflow.md](01-goals-and-workflow.md). Việc còn mở: [03-backlog.md](03-backlog.md). Việc đã xong: [05-done.md](05-done.md).

```
Tính năng:     1. Dọn nền ─► 2. Phân quyền động ─► 3. Media MinIO ─► 4. Phòng ban ─► 5. CI/CD (rồi tắt) ─► 6. Nâng cấp trên dev ─► 9. Trang cho người dùng
                  (xong)         (xong)                 (xong)            (xong)          (xong, đang tắt)        (xong)                 (xong)
Luyện quy trình:                                                                                                8. Đóng gói → sổ tay → vòng đời đầy đủ (đang làm: nghiệm thu v2.3.0)
Sau cùng:      7. Triển khai, vận hành, sự cố, hiệu năng & bảo mật, portfolio; CI/CD chỉ khi chủ dự án yêu cầu
```

Mỗi giai đoạn bắt đầu bằng **một đề xuất thiết kế** để chủ dự án xác nhận, rồi mới làm.

## 1. Dọn nền

| | |
|---|---|
| Mục tiêu | Mở dự án ra là biết chạy gì, sửa ở đâu, mỗi thư mục để làm gì |
| Gồm | Tài liệu môi trường dev, kế hoạch mới, dọn file ở thư mục gốc, rút gọn `docs/`, dọn `.claude/`, dữ liệu mẫu cho dev, mỗi tính năng hiện có một file mô tả |
| Xong khi | Người mới đọc `README.md` + `docs/dev-guide.md` là chạy được và đăng nhập được; không còn thư mục nào không rõ mục đích; trang quản trị có sẵn dữ liệu mẫu |

## 2. Phân quyền động

| | |
|---|---|
| Mục tiêu | Cấu hình quyền trong DB qua màn hình quản trị: vai trò → chức năng → API, không phải sửa code |
| Cách làm | Thư viện `spatie/laravel-permission` (đã chốt trong ADR-0012) |
| Gồm | Bảng vai trò và quyền; tự đăng ký mỗi API thành một quyền; gom quyền theo chức năng; kiểm tra quyền ở mỗi request; màn hình vai trò, ma trận quyền, gán vai trò cho admin; ẩn menu và nút theo quyền |
| Xong khi | Tạo được vai trò mới trên giao diện, tick quyền, gán cho admin; admin đó chỉ gọi được đúng các API được phép (có test) |

## 3. Media MinIO + upload

| | |
|---|---|
| Mục tiêu | Lấy lại quản lý file: thư mục, upload file nhỏ, upload file lớn chia phần, file tạm, thanh tiến trình |
| Cách làm | Theo [ADR-0014](../adr/0014-media-minio-upload.md): trình duyệt gửi file thẳng lên kho chính MinIO bằng link ký tạm; việc nền xử lý sau upload và Reverb báo kết quả (dùng chung cho mọi tính năng chạy ngầm); dùng lại bảng `media_mgmt`. Tham khảo code `v1.0.0`, viết lại cho khớp nền hiện tại |
| Gồm | Sửa cấu hình MinIO (tắt đọc công khai, thêm quyền cho tài khoản backend); việc nền và thông báo tức thì (hàng đợi, Reverb, việc hẹn giờ); upload file lớn chia phần; dọn upload bị treo; màn hình quản lý file |
| Xong khi | Upload được file vài KB và file vài GB, tải lại được, xóa được, có test |

## 4. Người dùng cuối, phòng ban, chính sách

> **Để sau** (2026-10-14, [ADR-0015](../adr/0015-defer-end-users-departments.md)): phạm vi hiện tại chỉ cần phân quyền theo vai trò. ADR ghi lại những gì đã tìm hiểu về bản `v1.0.0` và các câu hỏi còn mở, để làm lại sau.
>
> **Làm lại phòng ban và chính sách** (chủ dự án quyết 2026-10-15): chính sách chặn thật, bắt đầu từ thư mục trong Files; làm sau Phiên đăng nhập và Ghi nhớ đăng nhập. Người dùng cuối vẫn để sau, phòng ban không cần nó.
>
> **Phòng ban và khóa thư mục Files: xong 2026-10-17** ([ADR-0017](../adr/0017-departments-folder-policies.md), [phong-ban.md](../features/phong-ban.md), việc USER-03, DEPT-01…05). Người dùng cuối: thay bằng tài khoản người dùng trên trang public, xong 2026-10-19 (giai đoạn 9, [ADR-0020](../adr/0020-end-user-accounts.md)). Giai đoạn này đã xong.

| | |
|---|---|
| Mục tiêu | Lấy lại quản lý người dùng cuối, phòng ban, chính sách phòng ban |
| Gồm | API + màn hình cho từng phần, gán admin vào phòng ban, nhật ký thay đổi, quyền theo giai đoạn 2 |
| Xong khi | Thêm / sửa / xóa / tìm được trên giao diện, có test |

## 5. CI/CD gộp chung, rồi tắt

| | |
|---|---|
| Mục tiêu | Một bộ kiểm tra / build / deploy chạy được trên máy dev, GitHub và nền tảng khác (ví dụ GitLab) |
| Cách làm | Theo [ADR-0016](../adr/0016-ci-shared-folder.md): kiểm tra chạy trong container riêng cho CI, `make ci` giữ song song với `make verify`, bỏ deploy blue-green đã hỏng, build image chung ở `ci/build.sh` |
| Gồm | Gộp `ci-cd/` và logic trong `.github/workflows/` vào một thư mục `ci/`; mỗi nền tảng chỉ còn file cấu hình mỏng gọi vào `ci/`; test trên GitHub bằng chạy tay; **để tắt** tự động khi mở pull request |
| Xong khi | `make ci` chạy giống hệt trên GitHub; có hướng dẫn bật lại bằng một thay đổi nhỏ |

## 6. Nâng cấp trên dev

| | |
|---|---|
| Mục tiêu | Làm nốt các mục "chưa làm" đã ghi trong tài liệu tính năng, chỉ trên dev |
| Gồm | Đổi mật khẩu thì đăng xuất thiết bị khác; giao diện tự cập nhật quyền; màn hình Nhật ký thay đổi; giới hạn đăng nhập sai theo IP; màn hình Phiên đăng nhập; ghi nhớ đăng nhập (hai việc đăng nhập này tạm dừng từ 2026-10-14); upload dở tiếp tục sau khi tải lại trang; xem trước file; thùng rác file; đính kèm file vào bằng chứng. Thêm 2026-10-15: tự xóa hẳn file quá 30 ngày trong thùng rác; Files cho biết file gắn vào bằng chứng nào và lọc bằng chứng theo file; bằng chứng chỉ có file; kích thước ảnh, thời lượng video và nhạc, ảnh nhỏ |
| Tình trạng | 2026-10-17: xong mọi việc đã duyệt, kể cả Màn hình Phiên đăng nhập, Ghi nhớ đăng nhập, phòng ban, ảnh nhỏ trên trang public, tìm file trong ô tìm kiếm chung |
| Xong khi | Mỗi việc có test và mô tả trong `docs/features/` |

## 7. Sau cùng (chi tiết hóa khi tới)

Ghi lại để làm sau, theo thứ tự dự kiến. Mỗi mục bắt đầu bằng một đề xuất.

| Mục | Gồm | Ghi chú |
|---|---|---|
| Triển khai | Bản demo prod-like có dữ liệu mẫu và HTTPS (DEMO-01, DEMO-02), quản lý bí mật, staging, diễn tập dựng lại từ đầu bằng Terraform | `infra/` |
| Vận hành | Log có mã request, chỉ số, cảnh báo, mục tiêu chất lượng dịch vụ, hướng dẫn xử lý từng cảnh báo | **Cảnh báo cơ bản xong 2026-10-20** ([hướng dẫn](../runbooks/canh-bao.md)): container dừng hoặc không khỏe, việc nền chờ quá 5 phút, việc nền lỗi, báo bằng thông báo Windows (`make watch-on`). Còn: log có mã request, chỉ số, mục tiêu chất lượng dịch vụ |
| Sự cố | Gây lỗi giả lập, báo cáo sự cố, nhìn lại không đổ lỗi, diễn tập khôi phục dữ liệu đo thời gian | **Diễn tập lần đầu xong 2026-10-20** trên dev ([biên bản](../incidents/2026-10-20-dien-tap-su-co.md)): khôi phục thử, hàng đợi dừng, Redis dừng, kèm một sự cố thật. Sao lưu cả file trong thùng rác: xong cùng ngày. Còn: khôi phục thật đầu-cuối trên môi trường tạm (cùng mục Triển khai) |
| Hiệu năng và bảo mật | Đo tải trước / sau, phân tích mối đe dọa, quét lỗ hổng | `perf/` |
| Portfolio | Case study, tài khoản demo chỉ xem | |
| Sao lưu định kỳ | Cài rclone, lên lịch | Chủ dự án tự làm |
| Gợi ý tính năng chưa đề xuất | Khóa phòng ban cho bằng chứng và kỹ năng; các mục "Nâng cấp để dành" trong [dang-nhap.md](../features/dang-nhap.md) | Chỉ làm khi chủ dự án yêu cầu |
| **CI/CD tự động** | Bật lại CI trên GitHub/GitLab, chặn merge khi lỗi, quét bảo mật, tự phát hành | **Chỉ làm khi chủ dự án yêu cầu** ([ADR-0018](../adr/0018-lifecycle-practice-track.md)): làm chậm việc làm nhanh tính năng |

## 8. Luyện quy trình làm phần mềm kiểu doanh nghiệp

| | |
|---|---|
| Mục tiêu | Đúc kết kinh nghiệm của một developer trong doanh nghiệp thật: nhận yêu cầu → làm rõ → phân tích → thiết kế → làm → test → QA → đóng gói → triển khai → bàn giao → vận hành ([ADR-0018](../adr/0018-lifecycle-practice-track.md)) |
| Cách làm | Luyện trên các tính năng thật, không thu gọn ứng dụng. Tham khảo [sổ tay cũ](../archive/engineering-lab/handbook/README.md) và [mẫu tài liệu](../archive/engineering-lab/templates/) |
| Gồm | (1) Đóng gói và bàn giao bản `v2.2.0` (xong 2026-10-17); (2) sổ tay quy trình bản mới (xong 2026-10-19, [docs/handbook/](../handbook/README.md)); (3) một vòng đời đầy đủ trên trang cho người dùng và tài khoản người dùng cuối (đã làm xong tính năng, còn đóng gói `v2.3.0` theo sổ tay mới) |
| Không gồm | CI/CD tự động (chỉ làm khi chủ dự án yêu cầu) |
| Xong khi | Có tag `v2.2.0` với ghi chú phát hành, biên bản nghiệm thu, tài liệu bàn giao; sổ tay mới được dùng thật; một tính năng có đủ hồ sơ từ yêu cầu tới nhìn lại |

## 9. Trang cho người dùng

| | |
|---|---|
| Mục tiêu | Người xem đọc thông tin công khai không cần đăng nhập; người dùng có thể đăng nhập để lưu yêu thích, xem sau. Rất đơn giản, nhẹ |
| Cách làm | Thay `nextjs-docs` bằng Astro ([ADR-0019](../adr/0019-public-user-frontend.md)); tài khoản người dùng dùng bộ đăng nhập riêng, tách khỏi admin ([ADR-0020](../adr/0020-end-user-accounts.md)) |
| Gồm | Thử nhanh và đo; làm lại trang public; tài khoản người dùng cuối (thay việc quản lý người dùng cuối của giai đoạn 4); yêu thích, xem sau; màn hình End users trong trang quản trị. Lịch sử đã xem: bỏ theo chủ dự án (2026-10-19) |
| Tình trạng | Xong 2026-10-19 (PUB-01…04). Thêm theo yêu cầu cùng ngày ([REQ-004](../requirements/REQ-004-trang-chu-danh-muc.md), [ADR-0021](../adr/0021-public-home-categories-articles.md)): trang public về gốc `/` với trang chủ danh mục, bài viết theo các bước làm phần mềm, đăng nhập người dùng bằng tên đăng nhập, trang quản trị vào `/admin` (PUB-05, PUB-06, CMS-01, CMS-02). Còn nghiệm thu và tag `v2.3.0` (REL-02, giai đoạn 8) |
| Xong khi | Trang public chạy trên công nghệ mới, nhẹ hơn (có số đo); người dùng đăng nhập dùng được yêu thích và xem sau; có test và tài liệu |
