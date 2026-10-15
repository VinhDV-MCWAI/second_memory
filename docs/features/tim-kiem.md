# Tìm kiếm

Một ô tìm kiếm trên thanh đầu trang quản trị, tìm cùng lúc trong kỹ năng, mục tiêu học và bằng chứng. Chạy bằng PostgreSQL, không dùng search engine riêng ([ADR-0009](../adr/0009-postgres-search.md)).

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| Ô tìm kiếm ở thanh đầu trang | Gõ rồi Enter, chuyển tới trang kết quả |
| http://localhost:81/admin/search?q=... | Kết quả chia ba nhóm: kỹ năng, mục tiêu, bằng chứng. Mỗi kết quả có tiêu đề và một đoạn trích. Nếu kết quả là đoán gần đúng thì hiện dòng "No exact match — showing similar results" |

## API

| API | Làm gì | Ai dùng |
|---|---|---|
| `GET /api/admin/search?q=...` | Trả `match` (`exact` hoặc `fuzzy`) và ba danh sách `skills`, `goals`, `evidence` | Mọi quản trị viên |

## Cách tìm

| Bước | Chi tiết |
|---|---|
| 1. Tách từ | Chỉ giữ chữ và số; ký tự đặc biệt bị bỏ. Từ khóa dài 2–100 ký tự |
| 2. Tìm chính xác | Tìm toàn văn, từ cuối được hiểu là đầu từ (gõ "ky nan" ra "kỹ năng"). Bỏ dấu tiếng Việt cả hai phía, nên gõ không dấu vẫn khớp |
| 3. Tìm gần đúng | Chỉ chạy khi bước 2 không ra gì: so độ giống chữ (trigram), gõ sai một hai chữ vẫn ra |
| Xếp hạng | Khớp ở tên / tiêu đề xếp trên khớp ở tóm tắt, tóm tắt trên mô tả |
| Giới hạn | Tối đa 10 kết quả mỗi nhóm, đoạn trích 160 ký tự |

Mục tiêu học hiện dạng "Tên kỹ năng → Cấp độ đích", ví dụ "PostgreSQL → Independent".

## Dữ liệu

| Bảng | Dùng để |
|---|---|
| `skill`, `evidence` | Cột `search_tsv` lưu sẵn văn bản đã bỏ dấu, có chỉ mục GIN (toàn văn) và GiST (trigram) |
| `learning_goal` | Tìm trong ghi chú + tên kỹ năng |

Thời gian phản hồi đo được: 95% request dưới 123–144 ms (mục tiêu dưới 300 ms).

## Code

| Phần | File |
|---|---|
| Xử lý | `laravel-api/app/Services/Ledger/SearchService.php`, `laravel-api/app/Repositories/Ledger/SearchRepository.php` |
| Giao diện | `nextjs-fe/src/app/admin/search/page.tsx`, `nextjs-fe/src/features/ledger/components/search-results.tsx` |
| Test | `make test f=SearchApi` |
