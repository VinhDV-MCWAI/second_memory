# Lộ trình

Cập nhật: 2026-10-15. Mục tiêu và cách làm việc: [01-goals-and-workflow.md](01-goals-and-workflow.md). Danh sách việc chi tiết: [03-backlog.md](03-backlog.md).

```
1. Dọn nền ─► 2. Phân quyền động ─► 3. Media MinIO + upload ─► 4. Người dùng cuối, phòng ban ─► 5. CI/CD gộp chung (rồi tắt) ─► 6. Nâng cấp trên dev ─► 7. Staging, production, giám sát
   (dev)          (dev)                   (dev)                     (để sau)                        (dev)                           (dev)                   (sau cùng)
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
| Tình trạng | 2026-10-15: xong mọi việc nhận được. Còn Màn hình Phiên đăng nhập và Ghi nhớ đăng nhập, chờ chủ dự án yêu cầu |
| Xong khi | Mỗi việc có test và mô tả trong `docs/features/` |

## 7. Sau cùng (chi tiết hóa khi tới)

Bản demo prod-like (dữ liệu mẫu, HTTPS; chủ dự án quyết để sau ngày 2026-10-14), staging và production (Terraform trong `infra/`), bật lại CI/CD tự động, giám sát (log, chỉ số, cảnh báo), đo tải, rà bảo mật, sao lưu định kỳ.
