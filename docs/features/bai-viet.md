# Danh mục và bài viết

Nơi lưu kinh nghiệm của chủ dự án và hiện ra ở [trang public](trang-public.md). Chỉ có hai khái niệm:

- **Danh mục**, tối đa **5 cấp** (từ 2026-10-25, [ADR-0023](../adr/0023-danh-muc-nhieu-cap.md)): cấp trên cùng gọi là **nhóm** (ví dụ "Kỹ năng", "Quy trình làm phần mềm"), bên trong là danh mục và danh mục con (ví dụ Kỹ năng → Backend → Laravel). Danh mục có mô tả Markdown và **đính kèm** (link hoặc file) làm bằng chứng.
- **Bài viết**, viết bằng Markdown, thuộc **nhiều danh mục**. Một bài có thể là **bài con** của bài khác, nên một cuốn sách là một cây bài viết (tối đa 10 cấp).

Yêu cầu: [REQ-005](../requirements/REQ-005-trang-tai-lieu-kinh-nghiem.md). Thiết kế: [ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md) (thay một phần [ADR-0021](../adr/0021-public-home-categories-articles.md)).

Trạng thái: **xong**, ra bản [`v3.0.0`](../releases/v3.0.0.md) (2026-10-25): bỏ Skill Ledger, mô hình mới, màn hình quản trị, nhúng bài và file, xem trước Markdown (2026-10-20); trang public kiểu trang tài liệu; lượt xem, xếp hạng Đọc nhiều / Được lưu nhiều, [tìm kiếm](tim-kiem.md), xếp hạng trên trang tổng quan, [nhập từ Obsidian](nhap-tu-obsidian.md) (2026-10-22); test trình duyệt (2026-10-25). Nội dung hiện tại là **bài mẫu** (`make seed`).

## Màn hình trong trang quản trị

Menu **Public content**:

| Màn hình | Làm gì | Quyền |
|---|---|---|
| Categories (`/admin/content-categories`) | Cây nhóm → danh mục, mỗi cấp thụt vào một nấc. Bấm mũi tên cạnh tên để thu gọn / mở rộng một nhánh, hoặc **Expand all** / **Collapse all**. Ô tìm kiếm không dấu hiện các danh mục khớp kèm các cấp cha của chúng. **Kéo thả** bằng nút cầm ở đầu dòng: thả vào mép trên / dưới một dòng để đặt trước / sau nó, thả vào giữa dòng để đưa vào bên trong (thành danh mục con, nằm cuối); chỗ thả không hợp lệ (vào chính nhánh của nó, quá 5 cấp) không hiện vạch đánh dấu. Đang tìm kiếm thì không kéo được. Nút **↑ ↓** vẫn đổi thứ tự trong cùng cha. Form: tên, danh mục cha (ô chọn dạng cây có tìm kiếm không dấu; "None" để thành nhóm; chính nó, nhánh của nó và chỗ làm cây sâu quá 5 cấp bị làm mờ), mô tả Markdown, đính kèm (thêm link, gắn file từ Files, ↑ ↓, xóa), công khai | `content-category.list/store/update/delete/move` |
| Articles (`/admin/articles`) | Danh sách mới sửa nhất đứng đầu, hiện danh mục, bài cha, số bài con; lọc theo tiêu đề, danh mục, công khai. Form: tiêu đề, danh mục (ô chọn dạng cây: tìm kiếm không dấu theo cả đường dẫn, tick nhiều, **Select all shown** / **Clear shown** áp dụng cho các mục đang hiện), bài cha (chọn từ danh sách), tóm tắt, nội dung, công khai; phần **Sub-pages** liệt kê bài con theo thứ tự trong sách với nút ↑ ↓ | `article.list/store/update/delete/move` |

Vai trò Chỉ xem có hai quyền `list`. Không còn ô gõ số thứ tự: mục mới luôn nằm cuối, đổi chỗ bằng ↑ ↓.

## Trang public

| Địa chỉ | Hiện gì |
|---|---|
| http://localhost:81/ | Kiểu diễn đàn: mỗi nhóm một bảng, mỗi danh mục một dòng có số bài, bài mới nhất và ngày viết |
| http://localhost:81/categories/{danh mục} | Mô tả, đính kèm, danh sách bài (20 bài một trang) xếp **Mới nhất** hoặc **Cập nhật gần đây**, lọc thêm "cũng thuộc danh mục…", tìm theo tiêu đề. Trang có thêm các danh mục ngay bên trong và liệt kê bài của mọi danh mục con cháu; phía trên trang hiện đủ các cấp cha |
| http://localhost:81/articles/{bài} | Bố cục trang tài liệu 3 cột: cây sách (hoặc bài cùng danh mục), nội dung, mục lục; bài trước / sau, nút ☆ 🔖. Chi tiết ở [trang public](trang-public.md) |
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

Cú pháp riêng theo Obsidian (ngoài khối code ``` ```, và ngoài `code` trong dòng):

| Viết | Ra |
|---|---|
| `[[slug]]`, `[[slug\|chữ hiện]]`, `[[slug#Tiêu đề]]` | Link tới bài (và tới một mục của bài). Bài không có hoặc riêng tư: chỉ hiện chữ, không link |
| `![[slug]]` **đứng riêng một dòng** | Chèn toàn bộ nội dung bài đó vào chỗ này, trong khung có link về bài gốc |
| `![[slug#Tiêu đề]]` đứng riêng một dòng | Chỉ chèn một mục: từ tiêu đề đó tới tiêu đề cùng cấp tiếp theo |
| `![[file:{id}]]`, `![[file:{id}\|chú thích]]` đứng riêng một dòng | File trong Files: ảnh hiện ngay, video và nhạc có trình phát, loại khác là link mở / tải về |

Trong form, nút **Insert file** (chọn từ Files) và **Insert article** (tìm theo tiêu đề, chọn **Link** hoặc **Embed**) tự viết cú pháp vào chỗ con trỏ. Thẻ **Preview** hiện đúng như trang public (máy chủ dựng HTML), kèm dòng ghi chú cho thứ trang public sẽ giấu.

| Tình huống khi nhúng | Kết quả |
|---|---|
| Bài được nhúng riêng tư hoặc không có | Trang public không hiện gì (không để lộ bài có tồn tại); Preview hiện ghi chú |
| Nhúng vòng (A nhúng B, B nhúng A) | Dừng ở lần lặp, hiện một dòng ghi chú |
| Lồng quá 3 lớp | Không chèn lớp thứ 4, hiện một dòng ghi chú |
| File bị xóa trong Files | Trang public không hiện; Preview hiện ghi chú |
| Gắn file mới không dùng được (chưa upload xong, đã xóa, thư mục bị khóa) | Lưu bị chặn, báo ở ô nội dung |

Mỗi tiêu đề có neo (`#tiêu-đề`, di chuột lên tiêu đề thấy dấu #). API public trả kèm **mục lục** (`toc`): tiêu đề cấp 2, 3 của chính bài, không gồm tiêu đề của bài được nhúng.

HTML viết thẳng vào bài được hiện thành chữ, không chạy; link `javascript:` bị bỏ, để bài viết không chèn được mã độc vào trang. Phần HTML của cú pháp nhúng do máy chủ tự tạo.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Cấp danh mục | Tối đa 5 cấp. Chuyển một danh mục thì cả nhánh đi theo; không chuyển được vào chính nó hay danh mục con cháu của nó, không làm cây sâu quá 5 cấp (báo lỗi ở ô danh mục cha) |
| Cây bài viết | Bài cha phải là bài khác, không phải chính nó hay bài con của nó. Cả cây tối đa 10 cấp, tính cả các bài con đi theo khi chuyển một nhánh |
| Thứ tự | ↑ ↓ đổi chỗ với bài / danh mục kế bên trong cùng cha. Kéo thả danh mục đặt nó vào cha mới ở đúng vị trí, theo cùng quy tắc như ô danh mục cha (chuyển cha thì tính là sửa). Đổi thứ tự không tính là sửa nội dung (ngày sửa giữ nguyên) nhưng có ghi nhật ký. Chuyển sang cha khác thì nằm cuối ở đó |
| Đường dẫn | Tạo từ tên khi thêm ("Thiết kế" → `thiet-ke`), **không đổi** khi sửa tên. Đường dẫn bài viết **không trùng trên cả trang** (để sau này `[[đường-dẫn]]` luôn chỉ đúng một bài); trùng thì thêm số |
| Tên danh mục | Không trùng (không phân biệt hoa thường) |
| Đính kèm | Mỗi đính kèm cần **link hoặc file** (hoặc cả hai), tối đa 20. File phải đã upload xong, chưa xóa và ở thư mục admin được dùng; file đã gắn vẫn giữ khi bị đưa vào thùng rác (hiện "Deleted in Files"), xóa vĩnh viễn thì đính kèm mất theo |
| Công khai | Danh mục hiện khi nó và mọi cấp phía trên công khai. Bài hiện khi nó và **mọi bài cha** công khai; danh mục chỉ quyết định bài có trong danh sách của danh mục hay không |
| Xóa | Nhóm còn danh mục con: bị chặn (trừ khi xóa cùng lúc). Bài còn bài con: bị chặn (trừ khi xóa cùng lúc). Xóa danh mục thì bài viết chỉ mất liên kết với danh mục đó |
| Lưu đệm | Trang public chậm tối đa 60 giây so với trang quản trị |
| Lượt xem | Mỗi lần trang public hiện một bài, máy chủ trang public báo cho API kèm mã băm của IP + trình duyệt người xem. Cùng một người mở lại trong **30 phút** chỉ tính 1. Chỉ lưu con số, không lưu ai xem: mã băm chỉ nằm trong Redis 30 phút để nhận ra lần mở lại. Lượt xem không đổi ngày sửa của bài |
| Được lưu | Số người dùng trang public đã lưu bài (☆ yêu thích hoặc 🔖 xem sau; một người lưu cả hai vẫn tính 1) |
| Nhật ký | Mọi thêm, sửa, xóa, đổi thứ tự ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md), loại `content_category`, `article`; ảnh chụp có cả danh sách đính kèm và danh mục |

## API

| API | Làm gì |
|---|---|
| `GET /api/admin/content-category/list` | Cây danh mục theo thứ tự (cha trước, con ngay sau; lọc `name`, `parent_id`, `root`, `is_public`); mỗi dòng có `parent_name`, `depth` (cấp, 1 trên cùng), `children_count`, `article_count`, `attachments` |
| `POST content-category/store`, `PUT update/{id}`, `POST delete` | `parent_id`, `name`, `description`, `is_public`, `attachments: [{id?, title, url?, media_id?, note?}]` (thay cả danh sách theo thứ tự gửi; có `id` thì giữ đính kèm đó) |
| `PUT content-category/move/{id}` | `{direction: up\|down}` (một bước), hoặc `{parent_id, position}` khi kéo thả: đặt dưới `parent_id` (null là lên trên cùng) ở vị trí `position` trong các danh mục cùng cha, 0 là đầu |
| `GET /api/admin/article/list` | Lọc `title`, `category_id`, `parent_id`, `root`, `is_public`; lọc theo `parent_id` thì xếp theo thứ tự trong sách |
| `POST article/store`, `PUT update/{id}`, `POST delete`, `PUT move/{id}` | `parent_id`, `category_ids`, `title`, `summary`, `body`, `is_public` |
| `POST article/preview` | `{body}` → `{html, toc}` như trang public, file dùng link ký tạm; không lưu gì (quyền `article.preview`) |
| `GET /api/public/home` | Nhóm công khai theo thứ tự, mỗi nhóm có `categories: [{name, slug, article_count, latest}]` (`latest`: bài mới nhất hoặc null) |
| `GET /api/public/categories/{slug}?sort=&also=&q=&page=` | `ancestors` (các cấp cha từ trên xuống), `description_html`, `attachments` (`title`, `url`, `note`, `file: {key, name, size, mime_type, thumbnail_url}`), `categories` (danh mục ngay bên trong), `articles`, `page`, `last_page`, `total`. `sort`: `newest` (mặc định), `updated`, `most_read` hoặc `most_saved`, sai thì 422; mỗi bài có thêm `view_count`, `save_count`; `also`: slug danh mục thứ hai; `q`: chữ trong tiêu đề, không phân biệt dấu |
| `GET /api/public/articles/{slug}` | `title`, `key` (mã để lưu), `summary`, `body_html` (đã chèn bài và file), `toc`, `created_at`, `updated_at`, `categories`, `book` (cây sách từ gốc, chỉ phần đang hiện), `ancestors`, `previous`, `next`, `related` (bài cùng danh mục khi bài không nằm trong sách) |
| `GET /api/public/files/{key}` | Link tạm của file đính kèm danh mục hoặc file nhúng trong bài đang hiện |
| `POST /api/public/articles/{slug}/view` | `{visitor}` (sha256 dạng hex của IP + trình duyệt) → `{counted}`: `false` khi người này đã được tính trong 30 phút qua. Chỉ máy chủ trang public gọi; giới hạn riêng 600 lần / phút để không ăn vào giới hạn 60 lần / phút của các API đọc |
| `GET /api/public/search?q=` | Tìm bài và danh mục đang hiện, xem [tìm kiếm](tim-kiem.md) |
| `GET /api/admin/dashboard/summary` | Số danh mục, số bài, số bài công khai, `views` (tổng lượt xem), `most_read`, `most_saved` (5 bài mỗi loại, cả bài riêng tư: `{id, title, slug, is_public, count}`; bài chưa ai đọc / lưu không hiện) |

API public không cần đăng nhập, riêng tư hay không tồn tại đều trả 404.

## Dữ liệu

| Bảng | Dùng cho |
|---|---|
| `content_category` | `parent_id` (danh mục cha, tối đa 5 cấp), tên (duy nhất), `slug`, mô tả Markdown (tối đa 20.000 ký tự), `sort_order`, `is_public` |
| `category_attachment` | Đính kèm: tiêu đề, `url`, `media_id` (file trong Files), ghi chú, `position`, `public_id` (mã của `/files/{mã}`) |
| `article` | `parent_id` (bài cha), tiêu đề, `slug` (duy nhất), tóm tắt, nội dung Markdown, `sort_order` (trong cùng cha), `is_public`, `public_key`, `view_count` (lượt xem), `search_text` / `search_tsv` (cột tự tính cho [tìm kiếm](tim-kiem.md)) |
| `article_category` | Bài ↔ danh mục |
| `article_media` | File nhúng trong nội dung (`![[file:{id}]]`), cập nhật mỗi lần lưu bài; `public_id` là mã của `/files/{mã}` |
| `end_user_saved.article_id` | Bài viết người dùng đã lưu |

## Code

| Phần | File |
|---|---|
| Markdown | `laravel-api/app/Services/Content/WikiSyntax.php` (đọc cú pháp), `MarkdownRenderer.php` (dựng HTML, mục lục) |
| API | `laravel-api/app/{Http/Controllers,Services,Repositories,Http/Requests}/Content/`, `app/Models/Content/`, `app/Repositories/Concerns/MovesAmongSiblings.php`, `routes/api/content.php`, `app/Services/Public/` |
| Dữ liệu mẫu | `laravel-api/database/seeders/DevContentSeeder.php`: nhóm Quy trình (10 danh mục × 2 bài), nhóm Kỹ năng (4 danh mục, có đính kèm), sách "Laravel thực chiến" 3 cấp; chỉ dev, chạy lại không nhân đôi |
| Trang quản trị | `nextjs-fe/src/app/admin/(panel)/{content-categories,articles}/`, `src/features/content/` (`markdown-editor`, `article-picker-dialog`, `attachments-field`, `move-buttons`, `sub-pages`) |
| Trang public | `public-site/src/pages/index.astro`, `src/pages/{categories,articles}/`, `src/components/` (`Attachments`, `RichHtml`, `ArticleMenu`, `BookTree`), `src/lib/category-query.ts`, `src/lib/code-blocks.ts` |
| Test | `make test f=ArticleEmbed`, `f=WikiSyntax`, `f=ContentCategoryApi`, `f=ArticleApi`, `f=PublicContentApi`, `f=PublicFileApi`, `f=FolderPolicy`, `f=SavedApi`; `pnpm test src/features/content`; `make e2e` (`critical-journey.spec.ts`, `content.spec.ts`) |
