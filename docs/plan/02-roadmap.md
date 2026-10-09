# Lộ trình

Cập nhật: 2026-10-09. Mục tiêu và cách làm việc: [01-goals-and-workflow.md](01-goals-and-workflow.md). Danh sách việc chi tiết: [03-backlog.md](03-backlog.md).

```
1. Dọn nền ─► 2. Phân quyền động ─► 3. Media MinIO + upload ─► 4. Người dùng cuối, phòng ban ─► 5. CI/CD gộp chung (rồi tắt) ─► 6. Staging, production, giám sát
   (dev)          (dev)                   (dev)                          (dev)                          (dev)                            (sau cùng)
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
| Cách làm | Tham khảo code ở `v1.0.0` và ghi chú `docs/archive/learning/minio-media-pipeline-notes.md`, viết lại cho khớp nền hiện tại; dùng lại bảng `media_mgmt` |
| Gồm | Rà lại cấu hình MinIO (bucket, quyền, đọc công khai, tự xóa file tạm); xử lý nền cho file lớn; dọn upload bị treo; màn hình quản lý file |
| Xong khi | Upload được file vài KB và file vài GB, tải lại được, xóa được, có test |

## 4. Người dùng cuối, phòng ban, chính sách

| | |
|---|---|
| Mục tiêu | Lấy lại quản lý người dùng cuối, phòng ban, chính sách phòng ban |
| Gồm | API + màn hình cho từng phần, gán admin vào phòng ban, nhật ký thay đổi, quyền theo giai đoạn 2 |
| Xong khi | Thêm / sửa / xóa / tìm được trên giao diện, có test |

## 5. CI/CD gộp chung, rồi tắt

| | |
|---|---|
| Mục tiêu | Một bộ kiểm tra / build / deploy chạy được trên máy dev, GitHub và nền tảng khác (ví dụ GitLab) |
| Gồm | Gộp `ci-cd/` và logic trong `.github/workflows/` vào một thư mục `ci/`; mỗi nền tảng chỉ còn file cấu hình mỏng gọi vào `ci/`; test trên GitHub bằng chạy tay; **để tắt** tự động khi mở pull request |
| Xong khi | `make ci` chạy giống hệt trên GitHub; có hướng dẫn bật lại bằng một thay đổi nhỏ |

## 6. Sau cùng (chi tiết hóa khi tới)

Staging và production (Terraform trong `infra/`), bật lại CI/CD tự động, giám sát (log, chỉ số, cảnh báo), đo tải, rà bảo mật, sao lưu định kỳ.
