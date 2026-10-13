# Các tính năng

Mỗi tính năng một file: nó làm gì, màn hình nào, API nào, bảng nào, ai được dùng. Code là sự thật: tài liệu lệch với code thì sửa tài liệu, trong cùng commit sửa code.

| Tính năng | File | Tóm tắt |
|---|---|---|
| Đăng nhập | [dang-nhap.md](dang-nhap.md) | Đăng nhập bằng tên đăng nhập + mật khẩu, giữ phiên bằng cookie |
| Quản trị viên | [quan-tri-vien.md](quan-tri-vien.md) | Thêm, sửa, xóa tài khoản quản trị; hai vai trò chủ và chỉ xem |
| Skill Ledger | [skill-ledger.md](skill-ledger.md) | Kỹ năng, lịch sử cấp độ, bằng chứng, mục tiêu học, nhãn, trang tổng quan |
| Tìm kiếm | [tim-kiem.md](tim-kiem.md) | Một ô tìm kiếm cho kỹ năng, mục tiêu và bằng chứng; gõ không dấu, gõ sai vẫn ra |
| Nhật ký thay đổi | [nhat-ky-thay-doi.md](nhat-ky-thay-doi.md) | Ghi lại ai đổi gì, lúc nào, và mọi lần đăng nhập |
| Trang public | [trang-public.md](trang-public.md) | Trang `/skills` ai cũng xem được: kỹ năng và bằng chứng công khai |
| Nhập từ Obsidian | [nhap-tu-obsidian.md](nhap-tu-obsidian.md) | Đồng bộ ghi chú Obsidian có `publish: true` thành bằng chứng |

## Quyền dùng chung cho mọi tính năng quản trị

Hiện có hai vai trò, cố định trong code. Giai đoạn "Phân quyền động" sẽ thay bằng vai trò và quyền cấu hình được.

| Vai trò | Xem | Thêm / sửa / xóa |
|---|---|---|
| Chủ (`owner`) | Được | Được |
| Chỉ xem (`viewer`) | Được | Không: API trả 403. Giao diện chưa ẩn nút, bấm vào thì báo lỗi |

| Mã lỗi | Nghĩa |
|---|---|
| 401 | Chưa đăng nhập hoặc phiên hết hạn |
| 403 | Đã đăng nhập nhưng không được làm việc này |
| 422 | Dữ liệu gửi lên sai; `error.messages` ghi lỗi theo từng ô |

Mọi API trả cùng một khung: `{ "data": …, "error": { "status", "code", "messages" } }`. Danh sách API đầy đủ: `laravel-api/openapi.json` (sinh bằng `make openapi`).
