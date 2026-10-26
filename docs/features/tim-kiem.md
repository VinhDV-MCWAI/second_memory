# Tìm kiếm

Hai ô tìm kiếm dùng chung một cách tìm, chạy bằng PostgreSQL, không dùng search engine riêng ([ADR-0009](../adr/0009-postgres-search.md)):

| Ô tìm | Tìm trong |
|---|---|
| Thanh đầu **trang quản trị** | Bài viết, danh mục (cả thứ riêng tư), tên file và thư mục trong [Files](quan-ly-file.md) |
| Giữa header **trang public** (ô duy nhất của trang, có ở mọi trang) | Bài viết và danh mục đang hiện trên trang public; không tìm file |

Từ 2026-10-22 tìm bài viết và danh mục thay cho kỹ năng, mục tiêu học, bằng chứng đã bỏ ([ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md)).

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| http://localhost:81/admin/search?q=... | Ba nhóm: **Articles** (tiêu đề, đoạn tóm tắt, nhãn **Private** cho bài riêng tư), **Categories** (tên, nhóm, nhãn Private), **Files** (ảnh nhỏ hoặc biểu tượng thư mục, tên, thư mục chứa, dung lượng). Bài và danh mục công khai mở trang public ở tab mới; file mở Files đúng thư mục đó. Nhóm trống thì ẩn |
| http://localhost:81/search?q=... | Trang public (từ 2026-10-26, giống Obsidian): **một danh sách**, mỗi dòng có nhãn cho biết là gì, xếp theo thứ tự **Category** (tên, nhóm chứa), **Article** (từ khóa ở tiêu đề hoặc tóm tắt; hiện tóm tắt), **Section** (tiêu đề mục trong bài, kèm tên bài; bấm thì mở bài và cuộn tới đúng mục), **Content** (từ khóa chỉ có trong nội dung; hiện đoạn chứa từ khóa). Từ khóa được tô đậm ở mọi dòng, không dấu vẫn khớp. Trên đầu ghi số kết quả, kèm câu báo khi chỉ có kết quả gần đúng |

### Ô tìm ở header trang public

Từ 2026-10-26 (READ-07, [REQ-006](../requirements/REQ-006-trang-chu-va-trang-doc.md)).

| Khi | Ô tìm làm gì |
|---|---|
| Ngừng gõ 0,35 giây | Tự tìm, không cần Enter. Ở trang khác: hiện danh sách kết quả ngay dưới ô (giống hệt trang `/search`), cuối danh sách có link **All results →**. Ở trang `/search`: thay danh sách trong trang, thanh địa chỉ đổi theo từ khóa |
| Gõ tiếp khi đang tìm | Bỏ lần tìm cũ, chỉ hiện kết quả của chữ mới nhất |
| Enter | Mở trang `/search?q=…` (tải cả trang) |
| Esc, bấm ra ngoài | Đóng danh sách; bấm lại vào ô thì hiện lại |
| Chưa đủ 2 chữ hoặc số | Không gọi gì |
| Tắt JavaScript | Ô là form thường: Enter mở trang tìm kiếm |

Kết quả lấy từ chính trang `/search` (HTML dựng ở server), nên danh sách dưới ô và trang tìm kiếm luôn giống nhau. Script: `public-site/src/scripts/header-search.ts`; giới hạn độ dài từ khóa dùng chung ở `public-site/src/lib/search-query.ts`.

Kết quả là đoán gần đúng thì cả hai hiện dòng "No exact match — showing similar results".

## API

| API | Làm gì | Ai dùng |
|---|---|---|
| `GET /api/admin/search?q=...` | `match` (`exact` hoặc `fuzzy`), `articles` (`{id, title, slug, snippet, is_public}`), `categories` (`{id, name, slug, group, is_public}`), `files` (`{id, is_file, name, path, size, thumbnail_url}`) | Quyền `search.query`; nhóm `files` cần thêm quyền `media.list` |
| `GET /api/public/search?q=...` | `match`, `articles` (`{title, slug, summary, found_in, snippet}`, tối đa 20), `categories` (`{name, slug, group}`), `sections` (`{article_title, article_slug, heading, anchor}`, tối đa 10). `found_in`: `title` (từ khóa ở tiêu đề / tóm tắt) hoặc `content` (chỉ ở nội dung); `snippet`: đoạn nội dung quanh từ khóa (HTML an toàn, từ khóa trong `<mark>`, không dấu vẫn khớp, giữ nguyên dấu khi hiện); `sections`: tiêu đề mục (cấp 2, 3) của các bài tìm được có đủ từ khóa, kèm mã neo để mở đúng mục ([ADR-0024](../adr/0024-trang-doc-chuyen-trang-khong-tai-lai.md)) | Ai cũng gọi được, giới hạn 60 request / phút / IP như các API public khác |

Từ khóa dài 2–100 ký tự và phải có ít nhất một chữ hoặc số (gõ `!!` thì báo lỗi 422; trang public hiện câu nhắc, không gọi API).

## Cách tìm

| Bước | Chi tiết |
|---|---|
| 1. Tách từ | Chỉ giữ chữ và số; ký tự đặc biệt bị bỏ |
| 2. Tìm chính xác | Tìm toàn văn, từ cuối được hiểu là đầu từ (gõ "hang do" ra "hàng đợi"). Bỏ dấu tiếng Việt cả hai phía, nên gõ không dấu vẫn khớp. Bài viết tìm trong tiêu đề, tóm tắt **và nội dung**; danh mục tìm trong tên và mô tả |
| 3. Tìm gần đúng | Chỉ chạy khi bước 2 không ra gì ở mọi nhóm: so độ giống chữ (trigram) với tiêu đề + tóm tắt của bài, tên của danh mục, tên file; gõ sai một hai chữ vẫn ra ("larvel" ra "Laravel") |
| Xếp hạng | Khớp ở tiêu đề / tên xếp trên khớp ở tóm tắt, tóm tắt trên nội dung / mô tả |
| Giới hạn | Trang quản trị tối đa 10 kết quả mỗi nhóm, đoạn trích 160 ký tự. Trang public tối đa 20 bài, 10 danh mục |
| Trang public chỉ thấy thứ đang hiện | Bài riêng tư, bài nằm dưới bài riêng tư, danh mục riêng tư hoặc trong nhóm riêng tư không bao giờ ra |

## Tìm file và thư mục

Từ 2026-10-17. Chủ dự án chọn: thêm chỉ mục vào bảng file, tìm cả thư mục, không tìm trong thùng rác, ô "Lọc theo tên" trong Files giữ nguyên.

| Quy tắc | Chi tiết |
|---|---|
| Tìm chính xác | Tên chứa **mọi** từ đã gõ, thứ tự nào cũng được, bỏ dấu và không phân biệt hoa thường (gõ "thue nha" ra `hợp-đồng-thuê-nhà.pdf`). Tên ngắn xếp trước |
| Tìm gần đúng | Như các nhóm khác: chỉ chạy khi mọi nhóm đều không ra gì, so độ giống chữ |
| Không hiện | File và thư mục trong thùng rác; file đang upload dở hoặc upload hỏng; thư mục khóa theo [phòng ban](phong-ban.md) mà admin không được dùng, cùng mọi thứ bên trong |
| Không có quyền Files | Admin không có quyền `media.list` thì nhóm Files luôn trống |
| Chỉ mục | `media_mgmt_name_trgm` (trigram, chỉ các dòng chưa xóa) phục vụ cả hai bước tìm; không thêm cột |

## Dữ liệu

| Bảng | Dùng để |
|---|---|
| `article`, `content_category` | Cột tự tính `search_tsv` (văn bản đã bỏ dấu, có trọng số: tiêu đề / tên, tóm tắt, nội dung / mô tả; chỉ mục GIN) và `search_text` (tiêu đề + tóm tắt, hoặc tên; chỉ mục GiST trigram). Tạo bởi migration `2026_10_22_100001`, tự cập nhật khi lưu |
| `media_mgmt` | Tên file / thư mục, chỉ mục `media_mgmt_name_trgm` |

## Code

| Phần | File |
|---|---|
| Xử lý | `laravel-api/app/Services/Content/SearchService.php` (`search` cho trang quản trị, `publicSearch` cho trang public), `laravel-api/app/Repositories/Content/SearchRepository.php` |
| Giao diện | `nextjs-fe/src/app/admin/(panel)/search/page.tsx`, `nextjs-fe/src/features/content/components/search-results.tsx`; trang public `public-site/src/pages/search.astro` (tô đậm tiêu đề bằng `src/lib/highlight.ts` + `src/components/Marked.astro`, cùng cách so như API), ô tìm trong `src/layouts/Base.astro` |
| Test | `make test f=SearchContentApi`, `f=SearchFilesApi`, `f=PublicSearchApi`; khóa thư mục: `make test f=FolderPolicyApi`; `pnpm test src/features/content/components/search-results`; trang public: bước "search results say what they are" trong `e2e/tests/critical-journey.spec.ts` (`make e2e`) |
