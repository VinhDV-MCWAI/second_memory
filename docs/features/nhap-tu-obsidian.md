# Nhập từ Obsidian

Đồng bộ các ghi chú Obsidian có `publish: true` thành [bài viết](bai-viet.md), gửi **cả nội dung** ghi chú. Chạy tay bằng `make import`, chạy lại bao nhiêu lần cũng được. Công cụ viết bằng Python, nằm ở `tools/ledger-importer/` ([ADR-0010](../adr/0010-python-importer-tooling.md), [ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md)).

Các bước chạy chi tiết, cách kiểm tra và hoàn tác: [hướng dẫn nhập từ Obsidian](../runbooks/ledger-import.md).

## Cách chạy (tóm tắt)

```bash
export LEDGER_API_TOKEN="$(docker exec ml-php php artisan ledger:import-token root | head -1)"   # tạo token
make import vault=/đường/dẫn/vault dry=1        # chạy thử, không ghi gì
make import vault=/đường/dẫn/vault              # chạy thật
docker exec ml-php php artisan ledger:import-token root --revoke                             # thu hồi token
```

Kết quả in ra: số bài `created` (mới), `updated` (đổi), `unchanged` (không đổi), `hidden` (bị ẩn), tên danh mục không tìm thấy và các link chỉ còn là chữ.

## Ghi chú nào được nhập

| Frontmatter | Bắt buộc | Ghi chú |
|---|---|---|
| `publish: true` | Có | Không có thì bỏ qua. Chỉ đúng giá trị `true` của YAML; `"true"`, `yes`, `1` không tính |
| `title` | Không | Không có thì lấy tên file |
| `summary` | Không | Không có thì lấy đoạn văn đầu tiên (bỏ tiêu đề, khối code), cắt còn 300 ký tự |
| `categories` | Không | Tên danh mục (một tên hoặc danh sách), không phân biệt hoa thường. Phải có sẵn trong trang quản trị; tên lạ được báo lại, không tự tạo |
| `parent` | Không | Ghi chú cha: đường dẫn (`sach/Laravel.md`, có hoặc không `.md`), tên file (`Laravel`) hoặc link `"[[Laravel]]"` (phải trong ngoặc kép). Ghi chú cha cũng phải `publish: true` |

Nội dung là toàn bộ phần sau frontmatter, tối đa 100.000 ký tự.

Chỉ cần một ghi chú `publish: true` sai là công cụ dừng và không gửi gì, vì máy chủ sẽ ẩn mọi bài vắng mặt trong danh sách. Máy chủ cũng từ chối cả lần nhập (không ghi gì) khi ghi chú cha không có trong danh sách, các ghi chú làm cha của nhau thành vòng, hoặc cây sâu quá 10 cấp.

## Link giữa các ghi chú

Máy chủ tìm ghi chú được nhắc tới giống Obsidian: đúng đường dẫn trước, rồi tới tên file; nhiều ghi chú trùng tên thì ưu tiên ghi chú cùng thư mục, rồi đường dẫn ngắn nhất.

| Trong ghi chú | Trên trang public |
|---|---|
| `[[Ghi chú]]`, `[[Ghi chú#Mục\|chữ]]` tới ghi chú được đăng | Link tới bài viết đó (`[[slug]]`, xem [bài viết](bai-viet.md)) |
| `![[Ghi chú]]`, `![[Ghi chú#Mục]]` đứng riêng một dòng | Nhúng nội dung bài (hoặc một mục) |
| `[[slug]]` của bài viết tạo trong trang quản trị | Giữ nguyên, vẫn là link |
| Link tới ghi chú không đăng, ảnh trong vault (`![[hinh.png]]`), `![[file:…]]` | Chỉ còn chữ (chữ hiện, hoặc tên ghi chú). Được liệt kê trong kết quả |
| Link nằm trong khối code hoặc `code` | Giữ nguyên |

Ảnh và file trong vault chưa được nhập vào Files (ngoài phạm vi, [REQ-005](../requirements/REQ-005-trang-tai-lieu-kinh-nghiem.md)).

## Quy tắc đồng bộ

| Trường hợp | Kết quả |
|---|---|
| Ghi chú mới | Tạo bài viết, **công khai ngay**. Slug lấy từ tiêu đề, trùng thì thêm số (`-2`) |
| Ghi chú đổi nội dung | Cập nhật tiêu đề, tóm tắt, nội dung, bài cha, danh mục. Slug giữ nguyên |
| Đổi bài cha | Bài nằm cuối trong các bài con của cha mới |
| Nhiều bài con mới cùng một cha | Xếp theo đường dẫn trong vault, **số so như số** giống cây file của Obsidian: `Chương 2` trước `Chương 10` (từ 2026-10-26). Thứ tự đã có thì giữ, đổi bằng ↑ ↓ trong trang quản trị |
| Ghi chú không đổi | Không ghi gì vào DB |
| Ghi chú bị bỏ `publish` hoặc bị xóa khỏi vault | Bài bị **ẩn** (chuyển riêng tư), không bị xóa |
| Ghi chú bị ẩn rồi xuất hiện lại | Nội dung cập nhật nhưng vẫn riêng tư, phải bật công khai lại bằng tay |
| Đổi công khai / riêng tư, thứ tự ↑ ↓ trong trang quản trị | Lần nhập sau giữ nguyên |
| Sửa nội dung, bài cha, danh mục của bài đã nhập trong trang quản trị | **Lần nhập sau ghi đè** theo ghi chú. Sửa trong Obsidian thay vì trang quản trị |
| Bài viết tạo trong trang quản trị | Không bao giờ bị đụng tới |

Mỗi ghi chú nhận diện bằng đường dẫn trong vault (`external_key`): đổi tên hoặc chuyển thư mục một ghi chú thì bài cũ bị ẩn và một bài mới được tạo. Mỗi lần chạy tối đa 2.000 ghi chú và 45 MB.

## API và token

| Phần | Chi tiết |
|---|---|
| API | `POST /api/admin/article/import`, gửi danh sách ghi chú và `dry_run` |
| Token | Tạo bằng `php artisan ledger:import-token <tên đăng nhập>`, hạn 90 ngày (`--days=N` để đổi). Phải là tài khoản chủ đang hoạt động. Token tạo trước 2026-10-22 (khóa `evidence:import`) không còn dùng được: tạo token mới |
| Phạm vi token | Chỉ mở được API nhập (khóa `article:import`); gọi API khác bằng token này bị 403 |
| Giới hạn | 10 lần chạy / phút |
| Nhật ký | Mọi thay đổi có dòng trong [nhật ký thay đổi](nhat-ky-thay-doi.md), đánh dấu `via = importer` |

## Dữ liệu

| Bảng | Dùng để |
|---|---|
| `article` | `external_key` (đường dẫn trong vault, chỉ bài nhập mới có) |
| `article_category` | Danh mục của bài |
| `personal_access_tokens` | Token của công cụ nhập (chỉ lưu bản băm) |

## Code

| Phần | File |
|---|---|
| Công cụ dòng lệnh | `tools/ledger-importer/` |
| API | `laravel-api/app/Services/Content/ArticleImportService.php` |
| Lệnh tạo token | `laravel-api/app/Console/Commands/ImportTokenCommand.php` |
| Test | `make test f=ArticleImportApi`, `make test f=ImportToken`, `make importer-test`, `make importer-lint` |
