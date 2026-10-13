# Skill Ledger

Sổ ghi kỹ năng: mỗi kỹ năng có cấp độ hiện tại, lịch sử lên cấp, các bằng chứng (pull request, quyết định kỹ thuật, sự cố, ghi chú…), mục tiêu học và nhãn. Thiết kế gốc: [RFC-002](../archive/engineering-lab/design/RFC-002-skill-ledger.md).

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
| `/admin/evidence` | Bằng chứng: thêm, sửa, xóa, gắn kỹ năng và nhãn |
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

`{loại}` là `skill`, `tag`, `evidence` hoặc `learning-goal`. Thêm:

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
| Bằng chứng | Loại: pull request, quyết định kỹ thuật, sự cố, ghi chú, khác. Có nguồn: gõ tay hoặc nhập từ Obsidian. Bằng chứng nhập từ Obsidian không cho sửa tiêu đề, đường dẫn, ngày, tóm tắt, kỹ năng, nhãn (lỗi 422), vì lần nhập sau sẽ ghi đè; vẫn đổi được công khai / riêng tư |
| Xóa | Xóa thật. Xóa kỹ năng thì mất luôn lịch sử cấp độ, mục tiêu và liên kết với bằng chứng, nhãn |
| Nhật ký | Mọi thêm, sửa, xóa đều có dòng trong [nhật ký thay đổi](nhat-ky-thay-doi.md); form sửa có phần lịch sử |

## Giới hạn độ dài

| Ô | Tối đa |
|---|---|
| Tên kỹ năng / nhóm / mô tả | 100 / 50 / 2000 ký tự |
| Lý do đổi cấp độ | 500 |
| Tên nhãn | 50 |
| Tiêu đề / đường dẫn / tóm tắt bằng chứng | 200 / 2048 / 1000 |
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

## Code

| Phần | File |
|---|---|
| Route | `laravel-api/routes/api/ledger.php` |
| Xử lý | `laravel-api/app/Services/Ledger/` |
| Hằng số, giới hạn | `laravel-api/app/Constants/LedgerConst.php`, `laravel-api/app/Enums/` |
| Giao diện | `nextjs-fe/src/app/admin/{skills,evidence,goals,tags}/page.tsx`, `nextjs-fe/src/features/ledger/` |
| Test | `make test f=SkillApi`, `f=SkillLevelApi`, `f=EvidenceApi`, `f=LearningGoalApi`, `f=TagApi`, `f=DashboardApi` |
