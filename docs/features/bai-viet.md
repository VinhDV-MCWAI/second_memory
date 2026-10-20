# Danh mục và bài viết

Nơi lưu kinh nghiệm của chủ dự án và hiện ra ở [trang public](trang-public.md). Chỉ có hai khái niệm:

- **Danh mục**, hai cấp: **nhóm** (ví dụ "Kỹ năng", "Quy trình làm phần mềm") và **danh mục** trong nhóm (ví dụ "Laravel", "Thiết kế"). Danh mục có mô tả Markdown và **đính kèm** (link hoặc file) làm bằng chứng.
- **Bài viết**, viết bằng Markdown, thuộc **nhiều danh mục**. Một bài có thể là **bài con** của bài khác, nên một cuốn sách là một cây bài viết (tối đa 10 cấp).

Yêu cầu: [REQ-005](../requirements/REQ-005-trang-tai-lieu-kinh-nghiem.md). Thiết kế: [ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md) (thay một phần [ADR-0021](../adr/0021-public-home-categories-articles.md)).

Trạng thái: đang làm theo các bước KB-02…KB-08. **Xong** (2026-10-20): bỏ Skill Ledger, mô hình mới, màn hình quản trị, trang public tối thiểu. Còn: nhúng bài và file, xem trước Markdown (KB-04), giao diện kiểu trang tài liệu (KB-05), lượt xem và xếp hạng (KB-06), nhập từ Obsidian (KB-07). Nội dung hiện tại là **bài mẫu** (`make seed`).

## Màn hình trong trang quản trị

Menu **Public content**:

| Màn hình | Làm gì | Quyền |
|---|---|---|
| Categories (`/admin/content-categories`) | Cây nhóm → danh mục (danh mục thụt vào dưới nhóm). Nút **↑ ↓** đổi thứ tự trong cùng nhóm. Form: tên, nhóm (chọn từ danh sách, hoặc "không có" để thành nhóm), mô tả Markdown, đính kèm (thêm link, gắn file từ Files, ↑ ↓, xóa), công khai | `content-category.list/store/update/delete/move` |
| Articles (`/admin/articles`) | Danh sách mới sửa nhất đứng đầu, hiện danh mục, bài cha, số bài con; lọc theo tiêu đề, danh mục, công khai. Form: tiêu đề, danh mục (tick nhiều), bài cha (chọn từ danh sách), tóm tắt, nội dung, công khai; phần **Sub-pages** liệt kê bài con theo thứ tự trong sách với nút ↑ ↓ | `article.list/store/update/delete/move` |

Vai trò Chỉ xem có hai quyền `list`. Không còn ô gõ số thứ tự: mục mới luôn nằm cuối, đổi chỗ bằng ↑ ↓.

## Trang public

| Địa chỉ | Hiện gì |
|---|---|
| http://localhost:81/ | Từng nhóm, dưới mỗi nhóm là các danh mục kèm số bài công khai |
| http://localhost:81/categories/{danh mục} | Mô tả, đính kèm (link, file mở trong trình duyệt hoặc tải về), bài viết công khai mới sửa trước |
| http://localhost:81/articles/{bài} | Toàn bài, các danh mục của bài, nút ☆ yêu thích, 🔖 xem sau ([người dùng cuối](nguoi-dung-cuoi.md)) |
| `/guides/{danh mục}`, `/guides/{danh mục}/{bài}`, `/skills`, `/skills/{slug}` | Đường dẫn cũ, chuyển hướng 301 sang đường dẫn mới |

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

HTML viết thẳng vào bài được hiện thành chữ, không chạy; link `javascript:` bị bỏ, để bài viết không chèn được mã độc vào trang.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Hai cấp danh mục | Nhóm không có cha. Danh mục chỉ nằm trong một nhóm. Nhóm đang có danh mục con không thể chuyển vào nhóm khác (báo lỗi ở ô nhóm) |
| Cây bài viết | Bài cha phải là bài khác, không phải chính nó hay bài con của nó. Cả cây tối đa 10 cấp, tính cả các bài con đi theo khi chuyển một nhánh |
| Thứ tự | ↑ ↓ đổi chỗ với bài / danh mục kế bên trong cùng cha. Đổi thứ tự không tính là sửa nội dung (ngày sửa giữ nguyên) nhưng có ghi nhật ký. Chuyển sang cha khác thì nằm cuối ở đó |
| Đường dẫn | Tạo từ tên khi thêm ("Thiết kế" → `thiet-ke`), **không đổi** khi sửa tên. Đường dẫn bài viết **không trùng trên cả trang** (để sau này `[[đường-dẫn]]` luôn chỉ đúng một bài); trùng thì thêm số |
| Tên danh mục | Không trùng (không phân biệt hoa thường) |
| Đính kèm | Mỗi đính kèm cần **link hoặc file** (hoặc cả hai), tối đa 20. File phải đã upload xong, chưa xóa và ở thư mục admin được dùng; file đã gắn vẫn giữ khi bị đưa vào thùng rác (hiện "Deleted in Files"), xóa vĩnh viễn thì đính kèm mất theo |
| Công khai | Danh mục hiện khi nó và nhóm của nó công khai. Bài hiện khi nó và **mọi bài cha** công khai; danh mục chỉ quyết định bài có trong danh sách của danh mục hay không |
| Xóa | Nhóm còn danh mục con: bị chặn (trừ khi xóa cùng lúc). Bài còn bài con: bị chặn (trừ khi xóa cùng lúc). Xóa danh mục thì bài viết chỉ mất liên kết với danh mục đó |
| Lưu đệm | Trang public chậm tối đa 60 giây so với trang quản trị |
| Nhật ký | Mọi thêm, sửa, xóa, đổi thứ tự ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md), loại `content_category`, `article`; ảnh chụp có cả danh sách đính kèm và danh mục |

## API

| API | Làm gì |
|---|---|
| `GET /api/admin/content-category/list` | Cây nhóm → danh mục (lọc `name`, `parent_id`, `root`, `is_public`); mỗi dòng có `parent_name`, `children_count`, `article_count`, `attachments` |
| `POST content-category/store`, `PUT update/{id}`, `POST delete` | `parent_id`, `name`, `description`, `is_public`, `attachments: [{id?, title, url?, media_id?, note?}]` (thay cả danh sách theo thứ tự gửi; có `id` thì giữ đính kèm đó) |
| `PUT content-category/move/{id}` | `{direction: up|down}` |
| `GET /api/admin/article/list` | Lọc `title`, `category_id`, `parent_id`, `root`, `is_public`; lọc theo `parent_id` thì xếp theo thứ tự trong sách |
| `POST article/store`, `PUT update/{id}`, `POST delete`, `PUT move/{id}` | `parent_id`, `category_ids`, `title`, `summary`, `body`, `is_public` |
| `GET /api/public/categories` | Nhóm và danh mục công khai theo thứ tự cây: `name`, `slug`, `group`, `article_count` |
| `GET /api/public/categories/{slug}` | `group`, `description_html`, `attachments` (`title`, `url`, `note`, `file: {key, name, size, mime_type, thumbnail_url}`), `articles` |
| `GET /api/public/articles/{slug}` | `title`, `key` (mã để lưu), `summary`, `body_html`, `updated_at`, `categories` |
| `GET /api/public/files/{key}` | Link tạm của file đính kèm (như trước) |

API public không cần đăng nhập, riêng tư hay không tồn tại đều trả 404.

## Dữ liệu

| Bảng | Dùng cho |
|---|---|
| `content_category` | `parent_id` (nhóm), tên (duy nhất), `slug`, mô tả Markdown (tối đa 20.000 ký tự), `sort_order`, `is_public` |
| `category_attachment` | Đính kèm: tiêu đề, `url`, `media_id` (file trong Files), ghi chú, `position`, `public_id` (mã của `/files/{mã}`) |
| `article` | `parent_id` (bài cha), tiêu đề, `slug` (duy nhất), tóm tắt, nội dung Markdown, `sort_order` (trong cùng cha), `is_public`, `public_key` |
| `article_category` | Bài ↔ danh mục |
| `end_user_saved.article_id` | Bài viết người dùng đã lưu |

## Code

| Phần | File |
|---|---|
| API | `laravel-api/app/{Http/Controllers,Services,Repositories,Http/Requests}/Content/`, `app/Models/Content/`, `app/Repositories/Concerns/MovesAmongSiblings.php`, `routes/api/content.php`, `app/Services/Public/` |
| Dữ liệu mẫu | `laravel-api/database/seeders/DevContentSeeder.php`: nhóm Quy trình (10 danh mục × 2 bài), nhóm Kỹ năng (4 danh mục, có đính kèm), sách "Laravel thực chiến" 3 cấp; chỉ dev, chạy lại không nhân đôi |
| Trang quản trị | `nextjs-fe/src/app/admin/(panel)/{content-categories,articles}/`, `src/features/content/` (`attachments-field`, `move-buttons`, `sub-pages`) |
| Trang public | `public-site/src/pages/index.astro`, `src/pages/{categories,articles}/`, `src/components/Attachments.astro` |
| Test | `make test f=ContentCategoryApi`, `f=ArticleApi`, `f=PublicContentApi`, `f=PublicFileApi`, `f=FolderPolicy`, `f=SavedApi`; `pnpm test src/features/content` |
