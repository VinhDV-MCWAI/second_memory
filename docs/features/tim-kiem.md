# Tìm kiếm

Một ô tìm kiếm trên thanh đầu trang quản trị, tìm cùng lúc trong kỹ năng, mục tiêu học, bằng chứng và tên file, thư mục trong [Files](quan-ly-file.md). Chạy bằng PostgreSQL, không dùng search engine riêng ([ADR-0009](../adr/0009-postgres-search.md)).

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| Ô tìm kiếm ở thanh đầu trang | Gõ rồi Enter, chuyển tới trang kết quả |
| http://localhost:81/admin/search?q=... | Kết quả chia bốn nhóm: kỹ năng, mục tiêu, bằng chứng, Files. Mỗi kết quả có tiêu đề và một đoạn trích; nhóm Files hiện ảnh nhỏ (hoặc biểu tượng thư mục), tên, thư mục chứa và dung lượng, bấm vào thì mở Files đúng thư mục đó. Nếu kết quả là đoán gần đúng thì hiện dòng "No exact match — showing similar results" |

## API

| API | Làm gì | Ai dùng |
|---|---|---|
| `GET /api/admin/search?q=...` | Trả `match` (`exact` hoặc `fuzzy`) và bốn danh sách `skills`, `goals`, `evidence`, `files` (`{id, is_file, name, path, size, thumbnail_url}`) | Quyền `search.query`; nhóm `files` cần thêm quyền `media.list` |

## Cách tìm

| Bước | Chi tiết |
|---|---|
| 1. Tách từ | Chỉ giữ chữ và số; ký tự đặc biệt bị bỏ. Từ khóa dài 2–100 ký tự |
| 2. Tìm chính xác | Tìm toàn văn, từ cuối được hiểu là đầu từ (gõ "ky nan" ra "kỹ năng"). Bỏ dấu tiếng Việt cả hai phía, nên gõ không dấu vẫn khớp |
| 3. Tìm gần đúng | Chỉ chạy khi bước 2 không ra gì: so độ giống chữ (trigram), gõ sai một hai chữ vẫn ra |
| Xếp hạng | Khớp ở tên / tiêu đề xếp trên khớp ở tóm tắt, tóm tắt trên mô tả |
| Giới hạn | Tối đa 10 kết quả mỗi nhóm, đoạn trích 160 ký tự |

## Tìm file và thư mục

Từ 2026-10-17. Chủ dự án chọn: thêm chỉ mục vào bảng file, tìm cả thư mục, không tìm trong thùng rác, ô "Lọc theo tên" trong Files giữ nguyên.

| Quy tắc | Chi tiết |
|---|---|
| Tìm chính xác | Tên chứa **mọi** từ đã gõ, thứ tự nào cũng được, bỏ dấu và không phân biệt hoa thường (gõ "thue nha" ra `hợp-đồng-thuê-nhà.pdf`). Tên ngắn xếp trước |
| Tìm gần đúng | Như các nhóm khác: chỉ chạy khi cả bốn nhóm đều không ra gì, so độ giống chữ |
| Không hiện | File và thư mục trong thùng rác; file đang upload dở hoặc upload hỏng; thư mục khóa theo [phòng ban](phong-ban.md) mà admin không được dùng, cùng mọi thứ bên trong |
| Không có quyền Files | Admin không có quyền `media.list` thì nhóm Files luôn trống |
| Chỉ mục | `media_mgmt_name_trgm` (trigram, chỉ các dòng chưa xóa) phục vụ cả hai bước tìm; không thêm cột |

Mục tiêu học hiện dạng "Tên kỹ năng → Cấp độ đích", ví dụ "PostgreSQL → Independent".

## Dữ liệu

| Bảng | Dùng để |
|---|---|
| `media_mgmt` | Tên file / thư mục, chỉ mục `media_mgmt_name_trgm` |
| `skill`, `evidence` | Cột `search_tsv` lưu sẵn văn bản đã bỏ dấu, có chỉ mục GIN (toàn văn) và GiST (trigram) |
| `learning_goal` | Tìm trong ghi chú + tên kỹ năng |

Thời gian phản hồi đo được: 95% request dưới 123–144 ms (mục tiêu dưới 300 ms).

## Code

| Phần | File |
|---|---|
| Xử lý | `laravel-api/app/Services/Ledger/SearchService.php`, `laravel-api/app/Repositories/Ledger/SearchRepository.php` |
| Giao diện | `nextjs-fe/src/app/admin/search/page.tsx`, `nextjs-fe/src/features/ledger/components/search-results.tsx` |
| Test | `make test f=SearchApi` (cả `SearchFilesApiTest`), khóa thư mục: `make test f=FolderPolicyApi` |
