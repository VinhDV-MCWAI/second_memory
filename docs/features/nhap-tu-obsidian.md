# Nhập từ Obsidian

Đồng bộ các ghi chú Obsidian có `publish: true` thành bằng chứng loại "ghi chú" trong [Skill Ledger](skill-ledger.md). Chạy tay bằng `make import`, chạy lại bao nhiêu lần cũng được. Công cụ viết bằng Python, nằm ở `tools/ledger-importer/` ([ADR-0010](../adr/0010-python-importer-tooling.md)).

Các bước chạy chi tiết: [runbook nhập từ Obsidian](../runbooks/ledger-import.md).

## Cách chạy (tóm tắt)

```bash
export LEDGER_API_TOKEN="$(docker exec ml-php php artisan ledger:import-token root | head -1)"   # tạo token
make import vault=/đường/dẫn/vault dry=1        # chạy thử, không ghi gì
make import vault=/đường/dẫn/vault              # chạy thật
docker exec ml-php php artisan ledger:import-token root --revoke                             # thu hồi token
```

Kết quả in ra: số ghi chú `created` (mới), `updated` (đổi), `unchanged` (không đổi), `hidden` (bị ẩn), và các tên kỹ năng không tìm thấy.

## Ghi chú nào được nhập

| Frontmatter | Bắt buộc | Ghi chú |
|---|---|---|
| `publish: true` | Có | Không có thì bỏ qua |
| `date: YYYY-MM-DD` | Có | Ngày của bằng chứng |
| `url` | Có, hoặc đặt `LEDGER_NOTE_BASE_URL` cho cả vault | |
| `skills:` | Không | Tên kỹ năng phải có sẵn trong trang quản trị; tên lạ được báo lại, không tự tạo |
| `tags:` | Không | Nhãn chưa có thì tự tạo |

Chỉ cần một ghi chú `publish: true` sai là công cụ dừng và không gửi gì, vì API sẽ ẩn mọi ghi chú vắng mặt trong danh sách.

## Quy tắc đồng bộ

| Trường hợp | Kết quả |
|---|---|
| Ghi chú mới | Tạo bằng chứng, **công khai ngay** |
| Ghi chú đổi nội dung | Cập nhật tiêu đề, đường dẫn, ngày, tóm tắt, kỹ năng, nhãn |
| Ghi chú không đổi | Không ghi gì vào DB |
| Ghi chú bị bỏ `publish` hoặc bị xóa khỏi vault | Bằng chứng bị **ẩn** và chuyển riêng tư, không bị xóa |
| Ghi chú bị ẩn rồi xuất hiện lại | Hiện lại nhưng vẫn riêng tư, phải bật công khai lại bằng tay |
| Đổi công khai / riêng tư trong trang quản trị | Lần nhập sau giữ nguyên lựa chọn đó |
| Sửa nội dung bằng chứng đã nhập trong trang quản trị | Bị chặn (422), vì lần nhập sau sẽ ghi đè |

Mỗi ghi chú nhận diện bằng đường dẫn trong vault (`external_key`). Mỗi lần chạy tối đa 2.000 ghi chú.

## API và token

| Phần | Chi tiết |
|---|---|
| API | `POST /api/admin/evidence/import`, gửi danh sách ghi chú và `dry_run` |
| Token | Tạo bằng `php artisan ledger:import-token <tên đăng nhập>`, hạn 90 ngày (`--days=N` để đổi). Phải là tài khoản chủ đang hoạt động |
| Phạm vi token | Chỉ mở được API import; gọi API khác bằng token này bị 403 |
| Giới hạn | 10 lần chạy / phút |
| Nhật ký | Mọi thay đổi có dòng trong [nhật ký thay đổi](nhat-ky-thay-doi.md), đánh dấu `via = importer` |

## Dữ liệu

| Bảng | Dùng để |
|---|---|
| `evidence` | `source = obsidian`, `external_key`, `unpublished_at` (thời điểm bị ẩn) |
| `tag`, `evidence_skill`, `evidence_tag` | Nhãn và liên kết |
| `personal_access_tokens` | Token của công cụ nhập (chỉ lưu bản băm) |

## Code

| Phần | File |
|---|---|
| Công cụ dòng lệnh | `tools/ledger-importer/` |
| API | `laravel-api/app/Services/Ledger/EvidenceImportService.php` |
| Lệnh tạo token | `laravel-api/app/Console/Commands/ImportTokenCommand.php` |
| Test | `make test f=EvidenceImportApi`, `make test f=ImportToken`, `make importer-test`, `make importer-lint` |
