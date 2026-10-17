# ADR-0020 — Tài khoản người dùng trên trang public

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-17, chủ dự án trả lời [REQ-003](../requirements/REQ-003-tai-khoan-nguoi-dung.md): bỏ lịch sử đã xem, còn lại theo phương án khuyên, đăng nhập phương án A) |
| Ngày | 2026-10-17 |
| Người quyết định | Chủ dự án |
| Liên quan | [ADR-0004](0004-sanctum-spa-cookie-auth.md) (đăng nhập admin bằng phiên Sanctum), [ADR-0015](0015-defer-end-users-departments.md) (người dùng cuối để sau, nay làm theo hướng mới), [ADR-0019](0019-public-user-frontend.md) (trang public Astro) |

## Bối cảnh

Người xem trang public muốn **tùy chọn** có tài khoản để đánh dấu yêu thích và lưu xem sau kỹ năng hoặc bằng chứng. Yêu cầu, câu hỏi làm rõ, tiêu chí chấp nhận: [REQ-003](../requirements/REQ-003-tai-khoan-nguoi-dung.md).

Bảng `user_mgmt` của bản `v1.0.0` đã xóa từ 2026-10-07 và không có chỗ đăng nhập, nên không dùng lại.

## Quyết định

| Phần | Quyết định |
|---|---|
| Đăng nhập | Phiên cookie Sanctum như trang quản trị, nhưng **bộ đăng nhập riêng `user`** trên bảng riêng `end_user`. Chỉ tài khoản đang dùng mới đăng nhập được hoặc giữ phiên (khóa là bị đăng xuất ở lần gọi kế tiếp) |
| Tách khỏi admin | API riêng `/api/user/*` chỉ nhận bộ đăng nhập `user`; API quản trị chỉ nhận `admin` (`auth:sanctum` chỉ xét guard `admin`). Có test chứng minh người dùng không gọi được API quản trị |
| Phiên chung trình duyệt | Hai bộ đăng nhập dùng chung cookie phiên của trình duyệt. Người dùng đăng xuất thì chỉ bỏ đăng nhập `user`, không hủy cả phiên (admin cùng trình duyệt vẫn đăng nhập). Admin đăng xuất hủy cả phiên, nên người dùng cùng trình duyệt cũng phải đăng nhập lại |
| Đăng ký | Tự đăng ký bằng email (duy nhất, không phân biệt hoa thường), tên hiển thị, mật khẩu ≥ 8 ký tự. Admin bật / tắt đăng ký trong trang quản trị (bảng `app_setting`, mặc định bật). Tắt thì `register` trả 403 |
| Email | Không gửi email: không xác thực email, không có "quên mật khẩu". Admin đặt lại mật khẩu trong trang quản trị |
| Giới hạn | Đăng nhập sai: 5 lần theo email + IP, 30 lần theo IP, khóa 15 phút (như admin). Đăng ký: 5 lần / giờ / IP. Mọi API người dùng: 60 request / phút / IP |
| Đã lưu | Bảng `end_user_saved`: (người dùng, loại `favorite` / `read_later`, **hoặc** kỹ năng **hoặc** bằng chứng). Xóa kỹ năng, bằng chứng hay tài khoản thì dòng đã lưu tự mất theo (khóa ngoại) |
| Mã public của bằng chứng | Thêm cột `evidence.public_key` (UUID ngẫu nhiên). API public trả `key` cho từng bằng chứng; không lộ id |
| Thứ đã ẩn | Danh sách đã lưu chỉ trả kỹ năng còn công khai và bằng chứng còn hiện trên trang public (công khai, chưa bị ẩn, thuộc ít nhất một kỹ năng công khai). Lưu thứ không công khai: 404 như không tồn tại |
| Tự xóa tài khoản | Cần nhập lại mật khẩu; xóa hẳn tài khoản và mọi thứ đã lưu |
| Quản trị | `end-user/list`, `update/{id}` (khóa / mở, đặt lại mật khẩu), `delete`; `end-user/registration` (xem), `registration-update` (bật / tắt). Mỗi API là một quyền; vai trò Chỉ xem có hai quyền xem. Ghi nhật ký thay đổi loại `end_user` và `app_setting`; không bao giờ ghi mật khẩu |
| Trang public | Một script nhỏ của Astro (không React). Trình duyệt chỉ gọi API khi có cookie đánh dấu `sm_user` (không chứa bí mật, script đặt khi đăng nhập, xóa khi đăng xuất hoặc gặp 401), nên khách chưa đăng nhập không gọi thêm gì. Trang chính vẫn lưu đệm 60 giây; trạng thái ☆ / 🔖 lấy riêng cho từng người |

## Hệ quả

- Thêm 3 bảng, 1 cột, 1 bộ đăng nhập, 9 API người dùng, 5 API quản trị, 3 trang public, 1 màn hình quản trị.
- Đổi mật khẩu không đăng xuất các thiết bị khác của người dùng (khác admin). Nếu cần thì làm sau.
- Tài liệu: [nguoi-dung-cuoi.md](../features/nguoi-dung-cuoi.md).
