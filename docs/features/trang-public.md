# Trang public

Trang ai cũng xem được, không cần đăng nhập, nằm ở **gốc** `http://localhost:81/`. Từ 2026-10-22 trang đọc như một trang tài liệu (kiểu Next.js docs, Laravel docs) nhưng xếp theo kiểu diễn đàn: nhóm → danh mục → bài viết ([ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md)). Nội dung là [danh mục và bài viết](bai-viet.md). Chạy trong ứng dụng riêng `public-site` (Astro, [ADR-0019](../adr/0019-public-user-frontend.md)): HTML dựng sẵn ở server, đọc không cần JavaScript. Người xem có thể đăng nhập để lưu yêu thích và xem sau ([người dùng cuối](nguoi-dung-cuoi.md)); phần đó dùng một script nhỏ (~3 KB), khách chưa đăng nhập không gọi API nào thêm.

## Màn hình

| Địa chỉ | Hiện gì |
|---|---|
| http://localhost:81/ | Trang chủ kiểu diễn đàn: mỗi nhóm công khai một bảng; mỗi danh mục một dòng có số bài, bài mới nhất và ngày viết; theo thứ tự admin đặt |
| http://localhost:81/categories/{slug} | Một nhóm hoặc danh mục: đường dẫn ngược (Home › nhóm), mô tả, đính kèm (link, file), danh mục con (khi là nhóm), danh sách bài |
| http://localhost:81/articles/{slug} | Một bài, bố cục 3 cột (bảng dưới). Mỗi lần mở được tính lượt xem (cùng người trong 30 phút tính 1, [chi tiết](bai-viet.md#quy-tắc)) |
| http://localhost:81/search?q=… | [Tìm kiếm](tim-kiem.md) bài viết và danh mục; ô tìm nằm ở đầu mọi trang |
| http://localhost:81/files/{mã} | Mở một file đính kèm hoặc file nhúng: xin API link tạm rồi chuyển tới MinIO. Ảnh, PDF, video, nhạc, file chữ mở trong trình duyệt; loại khác tải về |
| `/account/login`, `/account/register`, `/me` | Tài khoản người dùng ([người dùng cuối](nguoi-dung-cuoi.md)) |

### Danh sách bài của một danh mục

| Phần | Cách dùng |
|---|---|
| Tab **Newest** / **Recently updated** / **Most read** / **Most saved** | Xếp theo ngày viết, ngày sửa, lượt xem hoặc số người đã lưu (bằng nhau thì bài mới trước). Mỗi bài hiện ngày, số lượt xem và số lần lưu. Trên điện thoại các tab nằm một hàng, vuốt ngang |
| Ô **Words of the title** | Tìm theo chữ trong tiêu đề, không phân biệt dấu và hoa thường ("hang doi" tìm được "Hàng đợi") |
| Ô **Also in …** | Chỉ giữ bài cũng thuộc một danh mục khác (ví dụ Laravel + Thực hiện) |
| **Filter**, **Clear** | Form thường (GET): chạy không cần JavaScript, địa chỉ giữ cả lựa chọn nên gửi link được |
| Chia trang | 20 bài một trang, nút ← Previous / Next → |

Trang của một **nhóm** liệt kê bài của cả nhóm (bài gắn vào nhóm hoặc vào danh mục công khai trong nhóm). Địa chỉ sửa tay sai (`?sort=abc`, `?page=x`) thì bị bỏ qua, không báo lỗi.

### Trang bài viết

| Cột | Hiện gì |
|---|---|
| Trái | Bài nằm trong sách (có bài cha hoặc bài con): cây mục của cả sách, mục đang đọc được tô. Bài đơn: tối đa 10 bài mới nhất cùng danh mục |
| Giữa | Đường dẫn ngược (Home › sách › chương), danh mục của bài, tiêu đề, nút ☆ / 🔖, nội dung; cuối bài là **bài trước / bài sau** theo thứ tự đọc của cây (đọc hết một mục rồi tới mục con của nó, rồi mục kế tiếp) |
| Phải | **On this page**: các tiêu đề cấp 2, 3 của bài, bấm để nhảy tới |

Trên điện thoại cột trái thành nút **Menu** mở ra đóng lại (thẻ `details` của HTML, không cần JavaScript); cột phải chỉ hiện trên màn hình rộng.

Khối code trong bài và trong mô tả danh mục được **tô màu** ở server (Shiki đi kèm Astro, theme GitHub sáng / tối theo cài đặt máy người xem). Ngôn ngữ không biết thì hiện chữ thường.

Đường dẫn cũ vẫn chạy (chuyển hướng 301): `/skills` → `/`; `/skills/{slug}` → `/categories/{slug}`; `/guides/{danh mục}` → `/categories/{danh mục}`; `/guides/{danh mục}/{bài}` → `/articles/{bài}`; `/docs…` → `/`; `/skills/me` → `/me`; `/skills/account/…` → `/account/…`; `/skills/files/{mã}` → `/files/{mã}`; `/login` → `/admin/login` (trang quản trị).

## API

| API | Làm gì |
|---|---|
| `GET /api/public/home` | Trang chủ: nhóm, danh mục, số bài, bài mới nhất |
| `GET /api/public/categories/{slug}` | Một nhóm hoặc danh mục và một trang bài; tham số `sort`, `also`, `q`, `page` |
| `GET /api/public/articles/{slug}` | Một bài: HTML, mục lục, danh mục, cây sách, đường dẫn ngược, bài trước / sau, bài cùng danh mục |
| `GET /api/public/files/{key}` | Link tạm `{url, expires_at}` của một file (5 phút để tải, 1 giờ để xem trong trình duyệt) |
| `GET /api/public/search?q=` | Tìm bài và danh mục đang hiện |
| `POST /api/public/articles/{slug}/view` | Máy chủ trang public báo một lượt xem (không chờ kết quả, API lỗi thì chỉ mất lượt đó, trang vẫn hiện) |

Chi tiết từng trường: [bai-viet.md](bai-viet.md#api). Không cần đăng nhập, giới hạn 60 request / phút / IP; riêng báo lượt xem giới hạn 600 / phút.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Chỉ hiện thứ đã bật công khai | Danh mục: nó và nhóm của nó công khai. Bài viết: nó và **mọi bài cha** công khai. Trong cây sách, một mục riêng tư ẩn luôn các mục con của nó |
| Thứ riêng tư trả 404 | Giống hệt thứ không tồn tại, để không lộ có thứ đó |
| File chỉ mở được khi đang hiện trên trang | File phải là đính kèm của danh mục đang hiện hoặc nhúng trong bài đang hiện, chưa bị xóa trong Files. Mọi trường hợp khác, kể cả mã sai, trả 404 như nhau |
| Không lộ số thứ tự nội bộ | Trang và API public không có `id`, không có đường dẫn thư mục của file. File dùng mã ngẫu nhiên (UUID) riêng cho mỗi lần gắn |
| HTML an toàn | Laravel dựng HTML từ Markdown, chặn HTML thô và link `javascript:`. Trang public chỉ tách khối code ra để tô màu, phần còn lại in nguyên |
| Link file luôn mới | Trang chỉ chứa `/files/{mã}`; mỗi lần bấm mới xin link ký tạm, nên trang lưu đệm bao lâu cũng không bị link hết hạn |
| Lưu đệm 60 giây | Server của trang public gọi API qua nginx và giữ kết quả 60 giây trong bộ nhớ (theo từng địa chỉ, kể cả tham số lọc); sửa trong trang quản trị có thể mất tới 1 phút mới thấy |
| Chia đường dẫn | nginx: `/admin…`, `/_next/…`, `/favicon.ico` → trang quản trị; `/api/` → Laravel; mọi đường dẫn khác → trang public |

## Dữ liệu

Đọc các bảng của [danh mục và bài viết](bai-viet.md): `content_category`, `category_attachment`, `article`, `article_category`, `article_media`, và `media_mgmt` cho file.

## Code

| Phần | File |
|---|---|
| API | `laravel-api/app/Services/Public/PublicContentService.php` (cây sách, bài trước / sau), `app/Http/Resources/Public/`, `app/Http/Requests/Public/PublicCategoryRequest.php`, `app/Enums/ArticleSort.php` |
| Trang | `public-site/src/pages/`: `index.astro`, `categories/[slug].astro`, `articles/[slug].astro` (báo lượt xem), `search.astro`, `files/[key].ts`, `account/`, `me.astro` |
| Thành phần | `public-site/src/components/`: `RichHtml.astro` (in HTML, tô màu code), `ArticleMenu.astro` + `BookTree.astro` (cột trái), `Attachments.astro`, `SaveButtons.astro` |
| Đường dẫn | `public-site/src/lib/routes.ts` (mọi đường dẫn trang), `src/lib/category-query.ts` (tham số lọc trong địa chỉ); chuyển hướng cũ trong `public-site/astro.config.mjs` và `docker/nginx/default.conf` |
| Gọi API | `public-site/src/lib/public-api.ts`; kiểu dữ liệu sinh từ `openapi.json` vào `src/types/openapi.d.ts` bằng `make openapi` |
| Test | `make test f=PublicContentApi`, `f=PublicFileApi`, `f=PublicViewApi`, `f=PublicSearchApi`; `docker exec ml-public-site pnpm typecheck`; test trình duyệt viết lại ở bước KB-08 |
