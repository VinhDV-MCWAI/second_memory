# ADR-0022 — Trang tài liệu kinh nghiệm: chỉ còn danh mục và bài viết

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-20, chủ dự án đồng ý toàn bộ đề xuất trong [REQ-005](../requirements/REQ-005-trang-tai-lieu-kinh-nghiem.md)) |
| Ngày | 2026-10-20 |
| Người quyết định | Chủ dự án |
| Liên quan | Thay [ADR-0021](0021-public-home-categories-articles.md) ở các mục Kỹ năng, Danh mục, Bài viết, Công khai, Đã lưu, đường dẫn `/guides`; bỏ Skill Ledger ([RFC-002](../archive/engineering-lab/design/RFC-002-skill-ledger.md)); sửa hợp đồng nhập của [ADR-0010](0010-python-importer-tooling.md) |

## Bối cảnh

Skill Ledger (kỹ năng có cấp độ, mục tiêu học, bằng chứng dùng chung, nhãn) được làm cho hướng "hồ sơ năng lực". Chủ dự án xác định mục đích là **lưu kinh nghiệm**; thang tự chấm không có giá trị với người đọc. Nội dung cần đọc như một trang tài liệu (Next.js docs, Laravel docs) nhưng xếp theo kiểu diễn đàn.

## Quyết định

### Dữ liệu

| Bảng | Quyết định |
|---|---|
| Skill Ledger | Xóa `skill`, `skill_level`, `skill_tag`, `tag`, `evidence`, `evidence_skill`, `evidence_tag`, `evidence_media`, `learning_goal` và các enum, quyền, màn hình, API của chúng. Dữ liệu là mẫu, không chuyển đổi. Nhật ký thay đổi cũ giữ nguyên |
| `content_category` | Thêm `parent_id` (danh mục cha, **tối đa 2 cấp**: nhóm → danh mục; nhóm không có cha; **đổi thành tối đa 5 cấp từ 2026-10-25, [ADR-0023](0023-danh-muc-nhieu-cap.md)**). `description` thành Markdown dài (tối đa 20.000 ký tự). `sort_order` giữ, chỉ đổi bằng API `move` (↑ ↓) trong cùng cha; mục mới nằm cuối. Xóa bị chặn khi còn danh mục con; bài viết chỉ mất liên kết |
| `category_attachment` (mới) | Bằng chứng của danh mục: tiêu đề, `url` **hoặc** `media_id` (file trong Files), ghi chú ngắn, `position`, `public_id` (UUID cho `/files/{mã}`). File bị xóa hẳn khỏi Files thì đính kèm mất theo |
| `article` | Bỏ `content_category_id`. Thêm `parent_id` (bài cha, chặn vòng, **cây tối đa 10 cấp**, xóa bài còn bài con bị chặn), `sort_order` theo cha (↑ ↓), `view_count`. `slug` **duy nhất toàn hệ thống** (để `[[slug]]` luôn trỏ đúng một bài) |
| `article_category` (mới) | Bài ↔ danh mục, nhiều-nhiều. Bài con không bắt buộc có danh mục |
| `article_media` (mới) | File nhúng trong nội dung (`![[file:{id}]]`), đồng bộ mỗi lần lưu bài; có `public_id` như đính kèm |
| `end_user_saved` | Bỏ `skill_id`, `evidence_id`; chỉ còn `article_id` |
| Tìm kiếm | `article` và `content_category` có `search_text` / `search_tsv` như cách làm cũ của kỹ năng ([ADR-0009](0009-postgres-search.md)): bỏ dấu, trọng số tiêu đề trước |

### Công khai

| Thứ | Hiện ở trang public khi |
|---|---|
| Danh mục | Nó công khai và nhóm cha (nếu có) công khai |
| Bài viết | Nó công khai và **mọi bài cha** công khai. Danh mục chỉ quyết định bài có nằm trong danh sách của danh mục hay không |
| File | Gắn vào đính kèm của danh mục đang hiện, hoặc nhúng trong bài đang hiện; chưa bị xóa trong Files |

Thứ riêng tư trả 404 như thứ không tồn tại.

### Markdown

Laravel vẫn chuyển (league/commonmark đi kèm Laravel, kiểu GitHub, `html_input: escape`, `allow_unsafe_links: false`), thêm phần mở rộng có sẵn của thư viện để gắn neo cho tiêu đề. Cú pháp riêng theo Obsidian:

| Cú pháp | Kết quả |
|---|---|
| `[[slug]]`, `[[slug\|chữ hiện]]`, `[[slug#Tiêu đề]]` | Link tới bài (và mục). Bài không tồn tại hoặc riêng tư: chỉ hiện chữ, không link |
| `![[slug]]`, `![[slug#Tiêu đề]]` đứng riêng một dòng | Chèn nội dung bài (hoặc một mục: từ tiêu đề đó tới tiêu đề cùng cấp tiếp theo) trong khung có link về bài gốc. Tối đa 3 lớp nhúng; nhúng vòng dừng và hiện dòng báo; bài riêng tư không hiện gì |
| `![[file:{id}]]`, `![[file:{id}\|chữ hiện]]` đứng riêng một dòng | Ảnh hiện ngay; video, nhạc có trình phát; loại khác là thẻ tải về. Trang public dùng `public_id`, không lộ `id` |

Cách làm: thay cú pháp nhúng bằng chỗ giữ chỗ, chuyển Markdown sang HTML, rồi ghép HTML do máy chủ tự tạo vào chỗ giữ chỗ. Nội dung người viết không bao giờ thành HTML thô. Code trong khối ``` ``` không bị xử lý. Mục lục lấy từ tiêu đề cấp 2, 3 và trả kèm HTML.

### API

| Nhóm | API |
|---|---|
| Quản trị | `content-category/{list,store,update,delete,move}`, `article/{list,store,update,delete,move,preview,import}` (mỗi tên route là một quyền, [ADR-0013](0013-dynamic-permissions.md)); `dashboard/summary`, `search` đổi nội dung |
| Public | `public/home` (nhóm + danh mục + số bài + bài mới nhất), `public/categories/{slug}?sort=&category=&q=&page=`, `public/articles/{slug}` (HTML, mục lục, cây sách, bài trước / sau, danh mục), `public/search?q=`, `POST public/articles/{slug}/view`, `public/files/{key}` |

### Lượt xem

Máy chủ trang public (Astro) gọi `POST public/articles/{slug}/view` mỗi lần render trang bài viết, gửi mã băm của IP + trình duyệt người xem. Laravel giữ khóa Redis 30 phút cho mỗi (bài, mã băm): còn khóa thì không đếm. Không lưu ai xem.

### Trang public

| Đường dẫn | Trang |
|---|---|
| `/` | Trang chủ diễn đàn: mỗi nhóm một bảng, mỗi danh mục một dòng |
| `/categories/{slug}` | Mô tả, đính kèm, danh sách bài có xếp hạng (Mới nhất, Cập nhật, Đọc nhiều, Được lưu nhiều), lọc theo danh mục khác, tìm theo tiêu đề |
| `/articles/{slug}` | Bố cục tài liệu 3 cột: cây sách / bài cùng danh mục, nội dung, mục lục; bài trước / sau |
| `/search?q=` | Kết quả tìm bài và danh mục |
| Đường dẫn cũ | `/skills` → `/`; `/skills/{slug}` → `/categories/{slug}`; `/guides/{danh mục}` → `/categories/{danh mục}`; `/guides/{danh mục}/{bài}` → `/articles/{bài}` (301) |

Vẫn không chạy JavaScript để đọc. Màu cho code: làm ở máy chủ Astro bằng Shiki (đã có sẵn trong Astro, chỉ khai báo trực tiếp trong `package.json`).

### Nhập từ Obsidian

Công cụ nhập gửi **cả nội dung** note. API `article/import` (token có khóa `article:import`) tạo / cập nhật bài theo `external_key`, ẩn bài nhập trước đây không còn trong danh sách. Frontmatter: `title`, `summary`, `categories` (tên danh mục, không tạo mới), `parent` (đường dẫn note cha). `[[tên note]]` trong nội dung đổi sang slug của bài tương ứng.

## Phương án đã cân nhắc

| Phương án | Vì sao không chọn |
|---|---|
| Giữ Kỹ năng là bảng riêng, chỉ bỏ cấp độ và mục tiêu | Hai khái niệm (kỹ năng, danh mục) làm cùng một việc: gom bài viết theo chủ đề |
| Giữ Nhãn | Bài thuộc được nhiều danh mục nên nhãn thành thừa |
| Danh mục nhiều cấp | Diễn đàn và trang tài liệu chỉ cần nhóm → danh mục; chiều sâu nằm ở cây bài viết |
| Chuyển Markdown ở trang public (JavaScript) | Phải viết lại phần chặn HTML thô và kiểm tra bài riêng tư lần hai; Laravel đã làm được |
| Soạn thảo kiểu Word / Obsidian live preview | Thêm thư viện nặng, dễ đổi Markdown của người viết; để đề xuất riêng |

## Hệ quả

- Ra bản `v3.0.0` (phá vỡ tương thích: bỏ API, bảng, quyền, trang `/skills`).
- Chia làm 7 việc KB-02…KB-08 ([REQ-005](../requirements/REQ-005-trang-tai-lieu-kinh-nghiem.md) mục 9); mỗi việc để `make verify` xanh.
- Từ KB-02 tới KB-03, trang quản trị và trang public tạm không có kỹ năng và bằng chứng.
