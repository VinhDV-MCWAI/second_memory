# REQ-004 — Trang chủ có danh mục, bài viết theo quy trình, đăng nhập bằng tên đăng nhập

| | |
|---|---|
| Người yêu cầu | Chủ dự án |
| Ngày nhận | 2026-10-19 |
| Cỡ việc | Lớn ([sổ tay chương 1](../handbook/01-tong-quan.md)): bảng mới, đổi đăng nhập, đổi đường dẫn |
| Trạng thái | Đã làm rõ, chủ dự án chọn phương án ngày 2026-10-19 |
| Thiết kế | [ADR-0021](../adr/0021-public-home-categories-articles.md) |

## 1. Yêu cầu gốc (tóm tắt lời chủ dự án)

1. Đăng nhập trang quản trị báo lỗi 401.
2. Người dùng trang public phải nhập email: đổi sang **tên đăng nhập + mật khẩu**.
3. Ai cũng xem được nội dung, không cần đăng nhập. Đăng nhập chỉ thêm yêu thích và xem sau.
4. Vào trang chủ thì bị 404, phải tự gõ `/skills`. Trang chủ phải hiện **đủ danh mục**; chọn danh mục nào thì hiện nội dung của danh mục đó (Kỹ năng là một danh mục).
5. Thiếu các danh mục theo quy trình: nhận yêu cầu, làm rõ, thiết kế, thực hiện, kiểm thử, triển khai, QA, bàn giao… Nội dung có thể làm mẫu, chủ dự án cập nhật sau.

## 2. Nguyên nhân hai lỗi

| Lỗi | Nguyên nhân | Xử lý |
|---|---|---|
| 401 khi đăng nhập quản trị | Mở trang quản trị ở `localhost:3456`: cổng riêng của container giao diện, không có trong danh sách địa chỉ được giữ phiên đăng nhập. Qua `localhost:81` thì đăng nhập được | Dùng `http://localhost:81`. Hướng dẫn dev ghi rõ hai cổng 3456, 3002 chỉ để gỡ lỗi |
| 404 ở trang chủ | Mở `localhost:3002/`: trang public hiện chỉ có đường dẫn bắt đầu bằng `/skills` | Trang public chuyển về gốc `/` (mục 4) |

## 3. Câu hỏi làm rõ và trả lời

| # | Câu hỏi | Trả lời (2026-10-19) |
|---|---|---|
| 1 | Nội dung các danh mục quy trình cập nhật bằng cách nào? | **Màn hình quản trị**: bảng Danh mục và Bài viết; người dùng yêu thích, xem sau được cả bài viết; có bài viết mẫu |
| 2 | Trang chủ danh mục ở đâu? | **Gốc `/`**. Trang quản trị chuyển hết vào `/admin`, đăng nhập ở `/admin/login`. Link `/skills` cũ vẫn chạy |
| 3 | Bỏ email của người dùng không? | **Bỏ**. Đăng ký: tên đăng nhập, tên hiển thị, mật khẩu. Tài khoản đang có lấy phần trước `@` của email làm tên đăng nhập |

## 4. Câu chuyện người dùng và tiêu chí chấp nhận

```text
Là người xem, tôi muốn thấy mọi danh mục ngay ở trang chủ, để chọn nội dung cần đọc.

Tình huống: trang chủ
  Khi tôi mở http://localhost:81/
  Thì tôi thấy thẻ "Kỹ năng" và một thẻ cho mỗi danh mục đang công khai, theo thứ tự admin đặt,
      mỗi thẻ có tên, mô tả ngắn, số bài viết

Tình huống: mở danh mục
  Khi tôi bấm một danh mục quy trình, ví dụ "Thiết kế"
  Thì tôi thấy danh sách bài viết công khai của danh mục đó; bấm một bài thì đọc được toàn bài

Tình huống: thứ không công khai
  Biết rằng admin ẩn một danh mục hoặc một bài viết
  Thì nó không hiện ở trang chủ, danh sách, và mở thẳng đường dẫn thì báo 404
```

```text
Là người dùng, tôi muốn đăng nhập bằng tên đăng nhập và mật khẩu.

Tình huống: đăng ký
  Khi tôi nhập tên đăng nhập "an-nguyen", tên hiển thị, mật khẩu ≥ 8 ký tự
  Thì tôi được đăng nhập ngay. Tên đăng nhập trùng (không phân biệt hoa thường) thì báo lỗi

Tình huống: tài khoản cũ
  Biết rằng tôi đã đăng ký bằng email an@example.com trước bản này
  Thì tôi đăng nhập bằng tên "an" với mật khẩu cũ
```

```text
Là người dùng đã đăng nhập, tôi muốn yêu thích và xem sau một bài viết.

Tình huống
  Khi tôi bấm ☆ hoặc 🔖 trên trang bài viết
  Thì bài viết hiện trong My lists, cạnh kỹ năng và bằng chứng đã lưu
```

```text
Là admin, tôi muốn quản lý danh mục và bài viết trên màn hình quản trị.

Tình huống
  Khi tôi thêm, sửa, ẩn / hiện, đổi thứ tự, xóa danh mục hoặc bài viết
  Thì trang public đổi theo (chậm tối đa 60 giây vì lưu đệm), và mọi thay đổi ghi vào nhật ký thay đổi
  Xóa danh mục còn bài viết thì bị chặn, báo phải chuyển hoặc xóa bài viết trước
```

## 5. Yêu cầu phi chức năng

| Mặt | Yêu cầu |
|---|---|
| Nhẹ | Trang chủ, danh mục, bài viết không cần JavaScript để đọc; chỉ thêm script ☆ / 🔖 đã có |
| An toàn | Nội dung bài viết viết bằng Markdown, máy chủ chuyển sang HTML và **chặn HTML thô, link `javascript:`** |
| Quyền | Mỗi API quản trị mới là một quyền (`content-category.*`, `article.*`); vai trò Chỉ xem có quyền xem |
| Dữ liệu mẫu | Danh mục quy trình và bài viết mẫu tạo bằng `make seed` (chỉ dev), chạy lại không nhân đôi |

## 6. Phân tích ảnh hưởng

| Phần | Thay đổi |
|---|---|
| DB | Bảng mới `content_category`, `article`; `end_user_saved` thêm `article_id`; `end_user` đổi `email` → `username` |
| API quản trị | `content-category/*`, `article/*` (list, store, update, delete) |
| API public | `public/categories`, `public/categories/{slug}`, `public/articles/{category}/{slug}`; `user/register`, `user/login` nhận `username`; `user/saved/*` nhận `article` |
| Trang public | Về gốc `/`: trang chủ, `/skills`, `/guides/{danh mục}`, `/guides/{danh mục}/{bài}`, `/account/*`, `/me`. Đường dẫn cũ `/skills/account/*`, `/skills/me` chuyển hướng |
| Trang quản trị | Đăng nhập ở `/admin/login`; `/login` cũ chuyển hướng. Menu mới: Content → Categories, Articles. End users hiện tên đăng nhập |
| nginx | `/admin`, `/_next` → trang quản trị; còn lại → trang public |
| Test | Backend cho mọi API mới và đổi đăng nhập; test giao diện; test trình duyệt sửa đường dẫn, thêm luồng danh mục → bài viết → yêu thích |
| Tài liệu | `docs/features/` (người dùng cuối, trang public, file mới bài viết), dev-guide, kiến trúc |

## 7. Sẵn sàng để làm khi

- [x] Mục tiêu và phạm vi đã ghi
- [x] Tiêu chí chấp nhận kiểm tra được
- [x] Chủ dự án trả lời mục 3
- [x] ADR-0021

## 8. Kế hoạch test

| Tiêu chí | Test |
|---|---|
| Trang chủ, danh mục, bài viết, thứ ẩn 404 | Test backend API public; test trình duyệt |
| Đăng ký / đăng nhập bằng tên đăng nhập, tài khoản cũ | Test backend (cả migration chuyển dữ liệu) |
| Yêu thích bài viết, bài bị ẩn không lộ tiêu đề | Test backend; test trình duyệt |
| Quản trị danh mục, bài viết, chặn xóa danh mục còn bài | Test backend; test giao diện |
| Markdown an toàn | Test backend: `<script>` và `javascript:` không ra HTML |
| Đường dẫn cũ chuyển hướng | Test trình duyệt |

## 9. Chia việc

| Việc | Gồm |
|---|---|
| PUB-05 | Đăng nhập người dùng bằng tên đăng nhập, bỏ email |
| CMS-01 | Danh mục, bài viết: bảng, API quản trị, API public, lưu bài viết, dữ liệu mẫu |
| CMS-02 | Màn hình quản trị Categories, Articles |
| PUB-06 | Trang public về gốc `/`: trang chủ, danh mục, bài viết; trang quản trị vào `/admin`; nginx; chuyển hướng đường dẫn cũ |
| REL-02 | Đóng gói `v2.3.0` gồm cả các việc trên |
