# Skill Ledger (đã bỏ)

> **Đã bỏ từ 2026-10-20** ([ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md)): kỹ năng, cấp độ, mục tiêu học, bằng chứng, nhãn không còn trong code. Kinh nghiệm nằm trong danh mục và bài viết ([bai-viet.md](bai-viet.md)). File này giữ để tra lịch sử.

Sổ ghi kỹ năng: mỗi kỹ năng có cấp độ hiện tại, lịch sử lên cấp, các bằng chứng (pull request, quyết định kỹ thuật, sự cố, ghi chú…) có thể đính kèm file, mục tiêu học và nhãn. Thiết kế gốc: [RFC-002](../archive/engineering-lab/design/RFC-002-skill-ledger.md).

## Thang cấp độ

| Số | Tên |
|---|---|
| 1 | Đang học (Learning) |
| 2 | Dùng được khi có hỗ trợ (Can use with help) |
| 3 | Tự làm được (Independent) |
| 4 | Dạy được người khác (Can teach others) |

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| http://localhost:81/admin | Trang tổng quan: số kỹ năng, số kỹ năng công khai, số bằng chứng, số bằng chứng công khai, số mục tiêu đang mở; biểu đồ số kỹ năng theo cấp độ; 10 thay đổi gần nhất |
| `/admin/skills` | Kỹ năng: thêm, sửa, xóa; ghi cấp độ mới; xem dòng thời gian cấp độ và lịch sử thay đổi |
| `/admin/evidence` | Bằng chứng: thêm, sửa, xóa, gắn kỹ năng, nhãn và [file đính kèm](#file-đính-kèm-bằng-chứng); bằng chứng có file hiện biểu tượng kẹp giấy + số file cạnh tiêu đề. Mở từ cột Evidence của màn hình Files (`/admin/evidence?media_id=…`) thì chỉ hiện các bằng chứng đang gắn file đó, có dòng "Evidence using the file …" và nút **Show all evidence** để bỏ lọc |
| `/admin/goals` | Mục tiêu học: kỹ năng nào, cấp độ muốn đạt, hạn |
| `/admin/tags` | Nhãn dùng chung cho kỹ năng và bằng chứng |

## API

Kỹ năng, nhãn, bằng chứng, mục tiêu học dùng chung một kiểu bốn API:

| API | Làm gì | Ai dùng |
|---|---|---|
| `GET /api/admin/{loại}/list` | Danh sách, lọc, phân trang (15 dòng / trang) | Mọi quản trị viên |
| `POST /api/admin/{loại}/store` | Thêm | Chủ |
| `PUT /api/admin/{loại}/update/{id}` | Sửa | Chủ |
| `POST /api/admin/{loại}/delete` | Xóa nhiều dòng (`ids`) | Chủ |

`{loại}` là `skill`, `tag`, `evidence` hoặc `learning-goal`. Danh sách bằng chứng lọc được theo tiêu đề, loại, nguồn, công khai, kỹ năng (`skill_id`), nhãn (`tag_id`) và file đính kèm (`media_id`, từ 2026-10-15; file đang ở thùng rác vẫn tính). Thêm:

| API | Làm gì | Ai dùng |
|---|---|---|
| `GET /api/admin/skill-level/list` | Lịch sử cấp độ của kỹ năng | Mọi quản trị viên |
| `POST /api/admin/skill-level/store` | Ghi một cấp độ mới | Chủ |
| `GET /api/admin/dashboard/summary` | Số liệu trang tổng quan | Mọi quản trị viên |

## Quy tắc

| Phần | Quy tắc |
|---|---|
| Kỹ năng | Tạo kỹ năng là ghi luôn cấp độ đầu tiên. Đường dẫn công khai (`slug`) tự sinh từ tên, không trùng. Chỉ kỹ năng bật "công khai" mới hiện ở [trang public](trang-public.md) |
| Lịch sử cấp độ | Chỉ thêm, không sửa, không xóa. Mỗi lần thêm: cập nhật cấp độ hiện tại của kỹ năng theo dòng mới nhất, và tự đánh dấu **đã đạt** các mục tiêu đang mở có cấp độ đích ≤ cấp độ mới |
| Mục tiêu học | Trạng thái: đang mở / đã đạt / bỏ. Cấp độ đích phải cao hơn cấp độ hiện tại. Chỉ ghi cấp độ mới mới làm mục tiêu thành "đã đạt"; mục tiêu đã đạt không đổi được đích hay trạng thái |
| Bằng chứng | Cần **link hoặc ít nhất một file** (cả hai cũng được; từ 2026-10-15). Không có cả hai: 422 "Give a link or attach at least one file", kể cả khi sửa mà gỡ hết file của bằng chứng không có link. Bằng chứng không có link hiện tiêu đề dạng chữ thường (không bấm được) ở trang quản trị và trang public. Loại: pull request, quyết định kỹ thuật, sự cố, ghi chú, khác. Có nguồn: gõ tay hoặc nhập từ Obsidian. Bằng chứng nhập từ Obsidian không cho sửa tiêu đề, đường dẫn, ngày, tóm tắt, kỹ năng, nhãn (lỗi 422), vì lần nhập sau sẽ ghi đè; vẫn đổi được công khai / riêng tư |
| Xóa | Xóa thật. Xóa kỹ năng thì mất luôn lịch sử cấp độ, mục tiêu và liên kết với bằng chứng, nhãn |
| Nhật ký | Mọi thêm, sửa, xóa đều có dòng trong [nhật ký thay đổi](nhat-ky-thay-doi.md); form sửa có phần lịch sử |

## File đính kèm bằng chứng

Từ 2026-10-15. Mỗi bằng chứng gắn được **tối đa 10 file** lấy từ [quản lý file](quan-ly-file.md). File không bị chép ra chỗ khác: bằng chứng chỉ trỏ tới file trong màn hình Files.

| Thao tác (form bằng chứng, ô **Files**) | Kết quả | Quyền cần |
|---|---|---|
| **Attach files** | Mở hộp thoại duyệt thư mục của Files, lọc theo tên, tick nhiều file rồi **Attach (n)**. File đã gắn hiện sẵn dấu tick, không chọn lại được. Thư mục và file đang upload dở không chọn được | `media.list` |
| **Upload** trong hộp thoại | Upload thẳng vào thư mục đang mở (giống màn hình Files, kể cả file lớn); xong thì tick để gắn | `media.upload-url` + `media.multipart-start` |
| Mũi tên lên / xuống | Đổi thứ tự hiện file | — |
| Nút X | Gỡ file khỏi bằng chứng. File vẫn còn trong Files | — |
| Con mắt / nút tải | Xem trước hoặc tải về (như màn hình Files) | `media.download-url` |
| Lưu form | Gửi `media_ids` theo thứ tự hiện | `evidence.store` / `evidence.update` |

| Tình huống | Kết quả |
|---|---|
| Gắn file không tồn tại, thư mục, file đang upload, file đã xóa | 422 "Only uploaded files of the file manager can be attached" |
| Gắn quá 10 file | 422 |
| File đã gắn bị xóa trong Files | Vẫn gắn, hiện gạch ngang + nhãn **Deleted**, không xem / tải được, trang public không hiện. Khôi phục từ thùng rác là hiện lại như cũ. Lưu lại form vẫn được (file đã gắn từ trước được giữ) |
| File bị xóa vĩnh viễn (**Delete forever**, hoặc lệnh dọn hằng ngày khi đã xóa quá 30 ngày) | Tự gỡ khỏi bằng chứng |
| Xóa bằng chứng | Chỉ mất liên kết, file vẫn trong Files |
| Bằng chứng nhập từ Obsidian | Vẫn gắn / gỡ file được (file không do Obsidian quản lý) |
| Sửa bằng chứng mà không gửi `media_ids` | File đính kèm giữ nguyên |
| Nhật ký thay đổi | Danh sách file nằm trong ảnh chụp bằng chứng, nên thêm / gỡ / đổi thứ tự file đều hiện trong lịch sử |

Trên [trang public](trang-public.md): bằng chứng công khai của kỹ năng công khai hiện danh sách file (tên, dung lượng). Bấm vào thì ảnh, PDF, video, nhạc, file chữ mở ngay trong trình duyệt, loại khác tải về.

## Giới hạn độ dài

| Ô | Tối đa |
|---|---|
| Tên kỹ năng / nhóm / mô tả | 100 / 50 / 2000 ký tự |
| Lý do đổi cấp độ | 500 |
| Tên nhãn | 50 |
| Tiêu đề / đường dẫn / tóm tắt bằng chứng | 200 / 2048 / 1000 |
| File đính kèm một bằng chứng | 10 file |
| Ghi chú mục tiêu | 1000 |

Ngày luôn ở dạng `YYYY-MM-DD`.

## Dữ liệu

| Bảng | Dùng để |
|---|---|
| `skill` | Kỹ năng, cấp độ hiện tại, công khai hay không |
| `skill_level` | Lịch sử cấp độ, ai ghi |
| `learning_goal` | Mục tiêu học |
| `evidence` | Bằng chứng, nguồn, khóa ngoài từ Obsidian |
| `tag` | Nhãn |
| `skill_tag`, `evidence_skill`, `evidence_tag` | Liên kết nhiều–nhiều |
| `evidence_media` | File đính kèm: bằng chứng, file (`media_mgmt`), thứ tự (`position`), mã ngẫu nhiên cho link công khai (`public_id`, giữ nguyên khi sửa bằng chứng; gỡ file thì link chết) |

## Code

| Phần | File |
|---|---|
| Route | `laravel-api/routes/api/ledger.php` |
| Xử lý | `laravel-api/app/Services/Ledger/` |
| Hằng số, giới hạn | `laravel-api/app/Constants/LedgerConst.php`, `laravel-api/app/Enums/` |
| Giao diện | `nextjs-fe/src/app/admin/{skills,evidence,goals,tags}/page.tsx`, `nextjs-fe/src/features/ledger/` (ô file: `evidence-files-field.tsx`; hộp thoại chọn file: `nextjs-fe/src/features/media/components/file-picker-dialog.tsx`) |
| Test | `make test f=SkillApi`, `f=SkillLevelApi`, `f=EvidenceApi`, `f=EvidenceFilesApi`, `f=LearningGoalApi`, `f=TagApi`, `f=DashboardApi`; giao diện `evidence-files-field.test.tsx`; trình duyệt (`make e2e`) `e2e/tests/critical-journey.spec.ts` (tạo kỹ năng, bằng chứng, tìm kiếm, trang public) và `e2e/tests/evidence-files.spec.ts` (upload và gắn file trong form, đổi thứ tự, bằng chứng không link, mở file từ trang public, mở bằng chứng từ cột Evidence của Files, xóa file trong Files thì hiện Deleted) |
