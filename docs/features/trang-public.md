# Trang public

Trang ai cũng xem được, không cần đăng nhập, nằm ở **gốc** `http://localhost:81/`. Từ 2026-10-22 trang đọc như một trang tài liệu (kiểu Next.js docs, Laravel docs) nhưng xếp theo kiểu diễn đàn: nhóm → danh mục → bài viết ([ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md)). Nội dung là [danh mục và bài viết](bai-viet.md). Chạy trong ứng dụng riêng `public-site` (Astro, [ADR-0019](../adr/0019-public-user-frontend.md)): HTML dựng sẵn ở server, đọc không cần JavaScript. Người xem có thể đăng nhập để lưu yêu thích và xem sau ([người dùng cuối](nguoi-dung-cuoi.md)); phần đó dùng một script nhỏ (~3 KB), khách chưa đăng nhập không gọi API nào thêm. Trang đọc (danh mục, bài) có thêm công cụ chuyển trang và script đọc: tổng **25,8 KB** chưa nén (10,1 KB nén), dưới giới hạn 30 KB; trang chủ và tìm kiếm 2,6 KB (đo trên bản build ngày 2026-10-26).

## Màn hình

| Địa chỉ | Hiện gì |
|---|---|
| http://localhost:81/ | Trang chủ tóm tắt (từ 2026-10-25, [REQ-006](../requirements/REQ-006-trang-chu-va-trang-doc.md)): ô tìm kiếm lớn, thẻ danh mục, tab bài viết ([bảng dưới](#trang-chủ)) |
| http://localhost:81/categories/{slug} | [Trang đọc](#trang-đọc-4-cột); cột giữa là nhóm hoặc danh mục: đường dẫn ngược (Home › nhóm), tên + nút ☆, mô tả, đính kèm (link, file), danh mục con (khi là nhóm), danh sách bài |
| http://localhost:81/articles/{slug} | [Trang đọc](#trang-đọc-4-cột); cột giữa là một bài. `?in={danh mục}` chọn danh mục hiện ở cột 2 (không có hoặc sai thì lấy danh mục đầu tiên của bài) |
| http://localhost:81/search?q=… | [Tìm kiếm](tim-kiem.md) danh mục, bài viết, mục trong bài và nội dung bài: một danh sách, mỗi dòng có nhãn và từ khóa tô đậm; ô tìm nằm ở đầu mọi trang |
| http://localhost:81/files/{mã} | Mở một file đính kèm hoặc file nhúng: xin API link tạm rồi chuyển tới MinIO. Ảnh, PDF, video, nhạc, file chữ mở trong trình duyệt; loại khác tải về |
| `/account/login`, `/account/register`, `/me` | Tài khoản người dùng ([người dùng cuối](nguoi-dung-cuoi.md)) |

### Trang chủ

| Phần | Cách dùng |
|---|---|
| Ô tìm kiếm lớn | Gửi tới trang [tìm kiếm](tim-kiem.md) |
| **Categories**: tab **In order** | Mọi nhóm theo thứ tự admin đặt; dưới mỗi nhóm là thẻ của mọi danh mục trong nhóm (kể cả danh mục cấp dưới) kèm số bài |
| **Categories**: tab **Most viewed** / **Most favorited** | 24 thẻ danh mục có nhiều lượt xem / nhiều người ☆ nhất (không tính nhóm), bằng nhau thì theo thứ tự admin đặt; thẻ hiện con số đó |
| **Articles**: tab **Most read** / **Most favorited** / **Newest** | 10 bài đọc nhiều nhất, nhiều người ☆ nhất, mới nhất; mỗi bài hiện tóm tắt và số lượt đọc / số ☆ / ngày viết |
| **See more →** | Ở danh mục: mở danh mục đầu tiên của tab đang chọn. Ở bài viết: mở bài đầu tiên của tab đang chọn. Khi có trang đọc 4 cột (READ-04) thì các link này mở trang đó |

Đổi tab là link thường (`?categories=most_viewed&articles=newest`), chạy không cần JavaScript, trang tải lại và cuộn về đúng phần vừa đổi. Giá trị sửa tay sai thì dùng tab đầu tiên. Trang chủ không có JavaScript riêng.

### Danh sách bài của một danh mục

| Phần | Cách dùng |
|---|---|
| Tab **Newest** / **Recently updated** / **Most read** / **Most saved** | Xếp theo ngày viết, ngày sửa, lượt xem hoặc số người đã lưu (bằng nhau thì bài mới trước). Mỗi bài hiện ngày, số lượt xem và số lần lưu. Trên điện thoại các tab nằm một hàng, vuốt ngang |
| Ô **Words of the title** | Tìm theo chữ trong tiêu đề, không phân biệt dấu và hoa thường ("hang doi" tìm được "Hàng đợi") |
| Ô **Also in …** | Chỉ giữ bài cũng thuộc một danh mục khác (ví dụ Laravel + Thực hiện) |
| **Filter**, **Clear** | Form thường (GET): chạy không cần JavaScript, địa chỉ giữ cả lựa chọn nên gửi link được |
| Chia trang | 20 bài một trang, nút ← Previous / Next → |

Trang của một **nhóm** liệt kê bài của cả nhóm (bài gắn vào nhóm hoặc vào danh mục công khai trong nhóm). Địa chỉ sửa tay sai (`?sort=abc`, `?page=x`) thì bị bỏ qua, không báo lỗi.

### Trang đọc 4 cột

Từ 2026-10-25 ([REQ-006](../requirements/REQ-006-trang-chu-va-trang-doc.md), [ADR-0024](../adr/0024-trang-doc-chuyen-trang-khong-tai-lai.md)), trang danh mục và trang bài dùng chung một bố cục kiểu trang tài liệu của Next.js.

| Cột | Hiện gì |
|---|---|
| 1. **Categories** | Cây mọi danh mục đang hiện kèm số bài; bấm ▸ để mở nhánh, bấm tên để mở danh mục. Nhánh của danh mục đang xem mở sẵn, danh mục đó được tô |
| 2. Tên danh mục | Bài của danh mục đang chọn (tối đa 100, nhiều hơn thì có link **All N articles →**), bài đang đọc được tô. Bài nằm trong sách thì cây mục của sách ở trên danh sách. Bài không thuộc danh mục nào: cây sách hoặc bài liên quan |
| 3. Nội dung | Bài: đường dẫn `Home › nhóm › danh mục › (chương của sách) › bài`, danh mục của bài, tiêu đề, nút ☆ / 🔖, nội dung, **bài trước / bài sau** theo thứ tự đọc của sách. Danh mục: như trước (tab, lọc, chia trang) |
| 4. **On this page** | Tiêu đề cấp 2, 3 của bài; bấm để cuộn tới; tiêu đề của phần đang đọc sáng lên khi cuộn |

| Tính năng | Cách chạy |
|---|---|
| Chuyển trang không tải lại | Công cụ chuyển trang có sẵn của Astro (`ClientRouter`), chỉ bật ở trang đọc. Bấm link giữa các trang đọc thì chỉ thay nội dung, thanh địa chỉ đổi theo, nút Back quay lại bài trước. Cột 1 giữ nguyên chỗ cuộn và nhánh đang mở. Link sang trang chủ, tìm kiếm, tài khoản thì tải cả trang như thường |
| Cột 2 giữ danh mục | Link trong trang đọc mang theo `?in={danh mục}`, nên đi từ bài này sang bài khác cột 2 không đổi. Trang có thẻ `canonical` trỏ về địa chỉ không có `?in=` cho máy tìm kiếm |
| Ô lọc ở mỗi cột 1, 2, 4 | Lọc ngay khi gõ, không phân biệt dấu và hoa thường. Lọc cây thì giữ cả danh mục cha của kết quả và mở nhánh |
| Thu gọn cột 1, 2, 4 | Nút « / » ở đầu cột; trình duyệt nhớ cột nào đang thu gọn (`localStorage`), mở lại vẫn thế |
| Màn hình | Rộng từ 1280px: đủ 4 cột. Từ 1024px: không có cột 4. Điện thoại: nội dung trước, link **Categories and articles ↓** nhảy xuống hai menu ở dưới |
| Không có JavaScript | Vẫn đọc được: link tải lại trang, mở nhánh cây và bấm mục lục vẫn chạy; ô lọc và nút thu gọn được ẩn |
| Lượt đọc thật | Xem mục [Quy tắc](#quy-tắc) |
| Nhớ chỗ đang đọc | Với bài đã bấm 🔖: khi đổi sang phần (`##`) khác thì gửi phần đó cho API, tối đa 1 lần / 10 giây, và gửi lần cuối khi rời trang. Danh sách **Read later** ở `/me` mở bài đúng phần đó; phần không còn (bài đã sửa) thì mở từ đầu kèm dòng báo "The section you read last time is no longer in this article" |

Khối code trong bài và trong mô tả danh mục được **tô màu** ở server (Shiki đi kèm Astro, theme GitHub sáng / tối theo cài đặt máy người xem). Ngôn ngữ không biết thì hiện chữ thường.

Đường dẫn cũ vẫn chạy (chuyển hướng 301): `/skills` → `/`; `/skills/{slug}` → `/categories/{slug}`; `/guides/{danh mục}` → `/categories/{danh mục}`; `/guides/{danh mục}/{bài}` → `/articles/{bài}`; `/docs…` → `/`; `/skills/me` → `/me`; `/skills/account/…` → `/account/…`; `/skills/files/{mã}` → `/files/{mã}`; `/login` → `/admin/login` (trang quản trị).

## API

| API | Làm gì |
|---|---|
| `GET /api/public/home` | Trang chủ: nhóm, danh mục, số bài, bài mới nhất |
| `GET /api/public/categories` | Cây danh mục đang hiện (cha trước con, theo thứ tự của chủ dự án): tên, slug, slug danh mục cha, cấp, số bài (gồm cả bài của danh mục con, mỗi bài tính 1 lần), lượt xem, số người yêu thích. Dùng cho trang chủ tóm tắt và cột 1 của trang đọc ([ADR-0024](../adr/0024-trang-doc-chuyen-trang-khong-tai-lai.md)) |
| `GET /api/public/articles` | Mọi bài đang hiện theo một cách xếp (`sort`: `newest`, `updated`, `most_read`, `most_saved`, `most_favorited`), `page`, `per_page` tối đa 100: các tab bài viết ở trang chủ |
| `GET /api/public/categories/{slug}` | Một nhóm hoặc danh mục (kèm lượt xem, số người yêu thích) và một trang bài; tham số `sort`, `also`, `q`, `page`, `per_page` (tối đa 100, để cột 2 của trang đọc lấy cả danh mục) |
| `GET /api/public/articles/{slug}` | Một bài: HTML, mục lục, danh mục, cây sách, đường dẫn ngược, bài trước / sau, bài cùng danh mục, lượt xem, số người yêu thích |
| `GET /api/public/files/{key}` | Link tạm `{url, expires_at}` của một file (5 phút để tải, 1 giờ để xem trong trình duyệt) |
| `GET /api/public/search?q=` | Tìm danh mục, bài, mục trong bài đang hiện; kèm đoạn trích có từ khóa |
| `POST /api/public/articles/{slug}/view` | Máy chủ trang public báo một lượt đọc thật, khi trình duyệt báo về `POST /views/articles/{slug}` (đường dẫn của trang public, không phải API; API lỗi thì chỉ mất lượt đó) |
| `POST /api/public/categories/{slug}/view` | Như trên cho trang danh mục; đếm riêng, đọc bài không cộng vào danh mục |

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
| Lượt đọc thật của bài | Tab đang hiện đủ `min(15 giây, 30% thời gian đọc ước tính 200 chữ/phút)` **và** đã cuộn 25% chiều dài bài (bài ngắn chỉ cần cuộn hết trang). Trình duyệt gửi `POST /views/articles/{slug}` (`sendBeacon`), máy chủ trang public tính mã băm IP + trình duyệt rồi báo API. Mở trang mà không đọc, bot không chạy JavaScript, người tắt JavaScript: không tính. Cùng người trong 30 phút tính 1 |
| Lượt xem danh mục | Tab đang hiện trang danh mục đủ 10 giây → `POST /views/categories/{slug}`. Đếm riêng, đọc bài không cộng vào danh mục |

## Dữ liệu

Đọc các bảng của [danh mục và bài viết](bai-viet.md): `content_category`, `category_attachment`, `article`, `article_category`, `article_media`, và `media_mgmt` cho file.

## Code

| Phần | File |
|---|---|
| API | `laravel-api/app/Services/Public/PublicContentService.php` (cây sách, bài trước / sau), `app/Http/Resources/Public/`, `app/Http/Requests/Public/PublicCategoryRequest.php`, `app/Enums/ArticleSort.php` |
| Trang | `public-site/src/pages/`: `index.astro`, `categories/[slug].astro`, `articles/[slug].astro`, `views/[kind]/[slug].ts` (nhận lượt đọc thật), `search.astro`, `files/[key].ts`, `account/`, `me.astro` |
| Trang đọc | `public-site/src/layouts/Reader.astro` (4 cột, `ClientRouter`), `src/scripts/reader.ts` (lọc, thu gọn, mục lục sáng, nhớ chỗ đọc, lượt đọc thật), `src/lib/category-tree.ts`, `src/lib/reader.ts`, CSS `.reader` trong `src/styles/global.css` |
| Thành phần | `public-site/src/components/`: `RichHtml.astro` (in HTML, tô màu code), `CategoryTree.astro` (cột 1), `ArticleMenu.astro` + `BookTree.astro` (cây sách ở cột 2), `Attachments.astro`, `SaveButtons.astro` (☆ 🔖 của bài, ☆ của danh mục), `CategoryBadge.astro` |
| Đường dẫn | `public-site/src/lib/routes.ts` (mọi đường dẫn trang), `src/lib/category-query.ts` (tham số lọc trong địa chỉ); chuyển hướng cũ trong `public-site/astro.config.mjs` và `docker/nginx/default.conf` |
| Gọi API | `public-site/src/lib/public-api.ts`; kiểu dữ liệu sinh từ `openapi.json` vào `src/types/openapi.d.ts` bằng `make openapi` |
| Test | `make test f=PublicContentApi`, `f=PublicFileApi`, `f=PublicViewApi`, `f=PublicSearchApi`; `docker exec ml-public-site pnpm typecheck`; `make e2e` (`critical-journey.spec.ts`: trang chủ → nhóm → danh mục → bài, link và nhúng, mục lục, tìm kiếm có nhãn và mở đúng mục, bài riêng tư 404, đường dẫn cũ; tab Newest và See more, chuyển bài không tải lại kể cả nút Back, mục lục sáng theo chỗ đọc; `end-users.spec.ts`: 🔖 nhớ mục đang đọc; `content.spec.ts`: danh mục bị ẩn thì 404) |
