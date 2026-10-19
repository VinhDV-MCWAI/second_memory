# Danh mục và bài viết

Trang chủ của [trang public](trang-public.md) hiện các **danh mục** (ví dụ các bước làm phần mềm: nhận yêu cầu, làm rõ, thiết kế, thực hiện, kiểm thử, QA, triển khai, bàn giao, vận hành). Mỗi danh mục có các **bài viết**. Chủ dự án viết và sửa trên trang quản trị. Ai cũng đọc được, không cần đăng nhập. Yêu cầu: [REQ-004](../requirements/REQ-004-trang-chu-danh-muc.md). Thiết kế: [ADR-0021](../adr/0021-public-home-categories-articles.md).

Trạng thái: **xong** (2026-10-19). Nội dung hiện tại là **bài mẫu** (`make seed`), chủ dự án thay bằng nội dung thật.

## Màn hình trong trang quản trị

Menu **Public content**:

| Màn hình | Làm gì | Quyền |
|---|---|---|
| Categories (`/admin/content-categories`) | Thêm, sửa tên, mô tả, thứ tự (số nhỏ đứng trước), bật / tắt công khai, xóa. Xem lịch sử thay đổi, mở trang public | `content-category.list/store/update/delete` |
| Articles (`/admin/articles`) | Thêm, sửa bài: danh mục, tiêu đề, tóm tắt, nội dung (Markdown), thứ tự, công khai. Mới sửa nhất đứng đầu | `article.list/store/update/delete` |

Vai trò Chỉ xem có hai quyền `list`.

## Trang public

| Địa chỉ | Hiện gì |
|---|---|
| http://localhost:81/ | Thẻ Skills, rồi các danh mục công khai theo thứ tự, mỗi thẻ có số bài viết công khai |
| http://localhost:81/guides/{danh mục} | Bài viết công khai của danh mục, theo thứ tự rồi theo tiêu đề |
| http://localhost:81/guides/{danh mục}/{bài} | Toàn bài; nút ☆ yêu thích, 🔖 xem sau (cần đăng nhập, xem [người dùng cuối](nguoi-dung-cuoi.md)) |

## Viết nội dung

Nội dung viết bằng Markdown:

| Viết | Ra |
|---|---|
| `## Tiêu đề` | Tiêu đề mục |
| `**đậm**`, `*nghiêng*`, `` `code` `` | Chữ đậm, nghiêng, code |
| `- mục` hoặc `1. mục` | Danh sách |
| `| A | B |` rồi `|---|---|` | Bảng |
| `[chữ](https://…)` | Link |
| `> trích` | Đoạn trích |

HTML viết thẳng vào bài được hiện thành chữ, không chạy; link `javascript:` bị bỏ. Đây là chủ đích, để bài viết không chèn được mã độc vào trang.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Đường dẫn | Tạo từ tên khi thêm ("Thiết kế" → `thiet-ke`), sau đó **không đổi** khi sửa tên, để link cũ vẫn chạy. Trùng thì thêm số (`thiet-ke-2`). Đường dẫn bài viết chỉ cần không trùng trong cùng danh mục |
| Tên danh mục | Không trùng (không phân biệt hoa thường) |
| Công khai | Bài viết hiện khi bài **và** danh mục đều công khai. Tắt danh mục là ẩn cả các bài trong đó, kể cả trong danh sách đã lưu của người dùng |
| Xóa danh mục | Bị chặn khi còn bài viết: chuyển bài sang danh mục khác hoặc xóa bài trước |
| Chuyển bài sang danh mục khác | Đường dẫn bài giữ nguyên, trừ khi danh mục mới đã có bài cùng đường dẫn thì thêm số |
| Lưu đệm | Trang public chậm tối đa 60 giây so với trang quản trị |
| Nhật ký | Mọi thêm, sửa, xóa ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md), loại `content_category`, `article` |

## API

| API | Làm gì |
|---|---|
| `GET /api/admin/content-category/list`, `POST store`, `PUT update/{id}`, `POST delete` | Quản trị danh mục (lọc `name`, `is_public`) |
| `GET /api/admin/article/list`, `POST store`, `PUT update/{id}`, `POST delete` | Quản trị bài viết (lọc `title`, `content_category_id`, `is_public`) |
| `GET /api/public/categories` | Danh mục công khai: `name`, `slug`, `description`, `article_count` |
| `GET /api/public/categories/{slug}` | Một danh mục và bài viết công khai của nó (`title`, `slug`, `summary`, `updated_at`) |
| `GET /api/public/articles/{danh mục}/{bài}` | Một bài: `title`, `key` (mã để lưu), `summary`, `body_html`, `updated_at`, `category` |

API public không cần đăng nhập, riêng tư hay không tồn tại đều trả 404.

## Dữ liệu

| Bảng | Dùng cho |
|---|---|
| `content_category` | Tên (duy nhất), `slug`, mô tả, `sort_order`, `is_public` |
| `article` | Danh mục (không xóa được danh mục còn bài), tiêu đề, `slug` (duy nhất trong danh mục), tóm tắt, nội dung Markdown, `sort_order`, `is_public`, `public_key` (mã ngẫu nhiên để người dùng lưu bài) |
| `end_user_saved.article_id` | Bài viết người dùng đã lưu |

## Code

| Phần | File |
|---|---|
| API | `laravel-api/app/{Http/Controllers,Services,Repositories,Http/Requests}/Content/`, `app/Models/Content/`, `routes/api/content.php`, `app/Services/Public/PublicContentService.php` |
| Dữ liệu mẫu | `laravel-api/database/seeders/DevContentSeeder.php` (chỉ dev, chạy lại không nhân đôi, không ghi đè bài đã sửa) |
| Trang quản trị | `nextjs-fe/src/app/admin/(panel)/{content-categories,articles}/`, `src/features/content/` |
| Trang public | `public-site/src/pages/index.astro`, `src/pages/guides/` |
| Test | `make test f=ContentCategoryApi`, `f=ArticleApi`, `f=PublicContentApi`, `f=SavedApi`; `pnpm test article-form`; `make e2e` (`content.spec.ts`) |
