# Hướng dẫn: nhập ghi chú Obsidian thành bài viết

Chạy tay mỗi khi muốn trang tài liệu khớp với vault; chạy lại bao nhiêu lần cũng được. Quy tắc đồng bộ và cú pháp ghi chú: [nhap-tu-obsidian.md](../features/nhap-tu-obsidian.md).

| | |
|---|---|
| Dùng khi | Vừa đăng, sửa hoặc bỏ đăng ghi chú trong vault |
| Rủi ro | Trung bình: chỉ thêm hoặc sửa theo đường dẫn, không bao giờ xóa, nhưng mọi bài đã nhập mà vắng mặt trong vault sẽ bị **ẩn**. Chỉ sai đường dẫn vault là ẩn hết; bài quay lại nhưng riêng tư, phải bật công khai lại từng bài. Luôn đọc số `hidden` ở bước chạy thử (bước 2) trước |
| Thời gian | Khoảng 1 phút (lần đầu phải build ảnh Docker) |
| Thử lần cuối | 2026-10-22 trên dev, vault thử `TMP` 2 ghi chú: chạy thử, chạy thật, chạy lại ra `unchanged 2`; bài con nằm dưới bài cha, danh mục khớp không phân biệt hoa thường, link đổi sang slug, link tới ghi chú không đăng chỉ còn chữ |

## Điều kiện

- Stack đang chạy (`make start` hoặc `make up`) và DB có tài khoản **chủ** đang hoạt động (`root` trên dev).
- Ghi chú cần đăng có `publish: true`.
- Danh mục ghi trong `categories` đã có trong trang quản trị (tên lạ được báo lại, không tự tạo).

## Các bước

1. Tạo token cho tài khoản chủ. Token chỉ in ra một lần (máy chủ chỉ giữ bản băm). Giữ trong shell hoặc file nằm cạnh vault được git bỏ qua, không bao giờ để trong repo:
   ```bash
   export LEDGER_API_TOKEN="$(docker exec ml-php php artisan ledger:import-token root | head -1)"
   ```
   Hạn mặc định 90 ngày (`--days=N` để đổi).
2. Chạy thử: kiểm tra vault và hỏi máy chủ sẽ thay đổi gì, không ghi gì:
   ```bash
   make import vault=/đường/dẫn/vault dry=1
   ```
   Xem `hidden`: phải bằng số ghi chú bạn vừa bỏ đăng, không phải cả vault. Mã thoát `1` kèm danh sách ghi chú lỗi (`đường dẫn: lý do`): sửa rồi chạy lại. Còn một ghi chú lỗi là không gửi gì.
3. Chạy thật:
   ```bash
   make import vault=/đường/dẫn/vault
   ```
4. Thu hồi token khi xong (hoặc giữ tới khi hết hạn nếu nhập thường xuyên):
   ```bash
   docker exec ml-php php artisan ledger:import-token root --revoke
   ```

`LEDGER_API_URL` mặc định `http://ml-nginx:8080` (mạng Docker; nginx nghe cổng 8080 bên trong). Chỉ đặt khi cần gọi API khác.

## Kiểm tra

- Chạy lại bước 3: phải ra `created 0`, `updated 0`, `hidden 0`.
- Trang quản trị → Articles: bài nhập xuất hiện, bài con nằm dưới bài cha. Mở http://localhost:81/articles/{slug} để đọc.
- `Unknown categories (…)`: thêm danh mục trong trang quản trị hoặc sửa ghi chú, rồi chạy lại.
- `Links shown as plain text (…)`: các link không tìm thấy ghi chú được đăng hay bài viết nào. Đăng ghi chú đó hoặc sửa link nếu muốn có link.

## Hoàn tác

- Bài bị ẩn nhầm (sai đường dẫn vault, chuyển ghi chú): chạy lại với đúng vault; bài quay lại (`updated`) nhưng **vẫn riêng tư** cho tới khi bật công khai trong trang quản trị.
- Lần nhập không bao giờ xóa bài; muốn xóa thì xóa trong trang quản trị (bài còn bài con phải xóa bài con trước).
- Token bị lộ: `ledger:import-token <tên đăng nhập> --revoke`. Token chỉ gọi được `POST admin/article/import` (403 ở mọi API khác).

## Khi gặp lỗi

| Thấy | Nghĩa là | Làm gì |
|---|---|---|
| Mã thoát 3, `401` | Token hết hạn hoặc đã thu hồi (token cũ trước 2026-10-22 cũng vậy) | Tạo token mới (bước 1) |
| Mã thoát 3, `403` | Tài khoản không phải chủ đang hoạt động | Dùng tài khoản chủ |
| Mã thoát 3, `429` | Chạy quá 10 lần trong một phút | Đợi một phút |
| Mã thoát 3, `422` kèm `notes.N.parent` | Ghi chú cha không được đăng, cha của nhau thành vòng, hoặc cây sâu quá 10 cấp | Sửa `parent` của ghi chú thứ N (đếm từ 0, theo thứ tự đường dẫn) |
| Mã thoát 3, `422` khác | Máy chủ từ chối ghi chú mà công cụ đã chấp nhận: lỗi hợp đồng giữa hai bên | Ghi lại thông báo, báo lỗi |
| Mã thoát 1, quá 2.000 ghi chú hoặc 45 MB | Một lần chạy không nhận nhiều hơn | Cần quyết định mới (chia lô) |
