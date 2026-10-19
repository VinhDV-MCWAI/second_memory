# ADR-0021 — Trang public ở gốc, danh mục và bài viết, đăng nhập người dùng bằng tên đăng nhập

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-19, chủ dự án chọn phương án khuyên cho cả 3 câu hỏi trong [REQ-004](../requirements/REQ-004-trang-chu-danh-muc.md)) |
| Ngày | 2026-10-19 |
| Người quyết định | Chủ dự án |
| Liên quan | [ADR-0019](0019-public-user-frontend.md) (trang public Astro), [ADR-0020](0020-end-user-accounts.md) (tài khoản người dùng; mục "Đăng ký" và "Đã lưu" được sửa bởi ADR này) |

## Bối cảnh

Trang public chỉ có Skill Ledger dưới `/skills`, trang chủ `/` thuộc về trang quản trị. Chủ dự án muốn trang chủ hiện mọi danh mục (Kỹ năng và các bước quy trình làm phần mềm), nội dung sửa được trên màn hình quản trị, người dùng đăng nhập bằng tên đăng nhập.

## Quyết định

| Phần | Quyết định |
|---|---|
| Đường dẫn | Trang public ở gốc `/` (Astro `base: '/'`). Trang quản trị (Next.js) chỉ còn dưới `/admin`: đăng nhập `/admin/login`, trang lỗi `/admin/401`, `/admin/500`. nginx: `/admin`, `/_next/`, `/favicon.ico` → Next.js; mọi đường dẫn khác → Astro. `/login` → 301 `/admin/login`; `/skills/account/*`, `/skills/me` → 301 bản mới |
| Kỹ năng | Giữ nguyên ở `/skills`, `/skills/{slug}`; là thẻ đầu tiên của trang chủ, không phải một dòng trong bảng danh mục |
| Danh mục | Bảng `content_category`: tên, `slug` (duy nhất), mô tả ngắn, `sort_order`, `is_public` |
| Bài viết | Bảng `article`: danh mục (khóa ngoại, **chặn xóa** danh mục còn bài), tiêu đề, `slug` (duy nhất trong danh mục), tóm tắt, nội dung Markdown, `sort_order`, `is_public`. Trang public: `/guides/{danh mục}`, `/guides/{danh mục}/{bài}` |
| Markdown | Laravel chuyển Markdown sang HTML (`Str::markdown`, kiểu GitHub, có bảng) với `html_input: escape`, `allow_unsafe_links: false`. Không thêm thư viện: `league/commonmark` đã đi kèm Laravel |
| Công khai | Bài viết hiện khi bài **và** danh mục của nó đều công khai |
| Đã lưu | `end_user_saved` thêm `article_id`; ràng buộc: đúng một trong `skill_id`, `evidence_id`, `article_id`. Bài viết bị ẩn thì không hiện trong danh sách đã lưu |
| Tên đăng nhập | `end_user.email` đổi thành `username`: 3–30 ký tự `a-z 0-9 . _ -`, lưu chữ thường, duy nhất. Tài khoản cũ: phần trước `@` của email, chuẩn hóa về các ký tự hợp lệ, thêm số nếu trùng. Bỏ hẳn email (chưa có tính năng gửi email nào) |
| Quyền | `content-category.list/store/update/delete`, `article.list/store/update/delete`; vai trò Chỉ xem có hai quyền `list`. Ghi nhật ký thay đổi loại `content_category`, `article` |
| Dữ liệu mẫu | 10 danh mục quy trình (nhận yêu cầu → vận hành) và 2 bài mẫu mỗi danh mục trong `DevSampleSeeder` (`make seed`, chỉ dev, chạy lại không nhân đôi) |

## Các phương án đã xét

| Câu hỏi | Đã chọn | Không chọn, vì sao |
|---|---|---|
| Nơi sửa nội dung | Màn hình quản trị | File Markdown trong repo: nhanh hơn nhưng không sửa được trên giao diện, không yêu thích được bài viết |
| Vị trí trang chủ | Gốc `/` | Giữ dưới `/skills`: ít đổi nginx hơn nhưng tên đường dẫn không khớp nội dung |
| Email | Bỏ | Giữ email không bắt buộc: chưa có tính năng nào dùng email |

## Hệ quả

- Thêm 2 bảng, 1 cột; đổi 1 cột. Thêm 8 API quản trị, 3 API public; 2 màn hình quản trị; 3 trang public.
- Ai đánh dấu sẵn `/login` hay `/skills/me` vẫn vào được nhờ chuyển hướng.
- Cổng 3456 (Next.js) và 3002 (Astro) chỉ để gỡ lỗi; đăng nhập chỉ chạy qua cổng 81.
