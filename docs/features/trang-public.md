# Trang public

Trang ai cũng xem được, không cần đăng nhập: danh sách kỹ năng công khai và chi tiết từng kỹ năng. Chạy trong ứng dụng riêng `nextjs-docs`, chỉ đọc.

## Màn hình

| Địa chỉ | Hiện gì |
|---|---|
| http://localhost:81/skills | Kỹ năng công khai, nhóm theo nhóm kỹ năng, kèm cấp độ và nhãn |
| http://localhost:81/skills/{slug} | Một kỹ năng: cấp độ hiện tại, lịch sử cấp độ (ngày), bằng chứng công khai mới nhất trước |
| http://localhost:81/docs và mọi đường dẫn `/docs/...` | Trang "Nội dung đã chuyển đi" (nội dung CMS cũ đã bỏ). Giữ lại để link cũ không bị 404 |
| `/` của ứng dụng public | Chuyển tới `/docs` |

## API

| API | Làm gì |
|---|---|
| `GET /api/public/skills` | Danh sách kỹ năng công khai, xếp theo tên |
| `GET /api/public/skills/{slug}` | Chi tiết một kỹ năng công khai |

Không cần đăng nhập, chỉ có GET, giới hạn 60 request / phút / IP.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Chỉ hiện thứ đã bật công khai | Kỹ năng phải bật "công khai" trong trang quản trị. Bằng chứng phải bật "công khai" và chưa bị ẩn bởi lần [nhập từ Obsidian](nhap-tu-obsidian.md) |
| Kỹ năng riêng tư trả 404 | Giống hệt kỹ năng không tồn tại, để không lộ có kỹ năng đó |
| Lưu đệm 60 giây | Server của trang public gọi API qua nginx và giữ kết quả 60 giây; sửa trong trang quản trị có thể mất tới 1 phút mới thấy |

## Dữ liệu

Đọc các bảng của [Skill Ledger](skill-ledger.md): `skill`, `skill_level`, `evidence`, `tag` và các bảng liên kết. Không có bảng riêng.

## Code

| Phần | File |
|---|---|
| API | `laravel-api/app/Services/Public/PublicSkillService.php`, `laravel-api/app/Http/Resources/Public/` |
| Trang | `nextjs-docs/src/app/skills/`, `nextjs-docs/src/app/docs/` |
| Gọi API | `nextjs-docs/src/lib/ledger-api.ts` (kiểu dữ liệu phải khớp `openapi.json`, sửa cùng lúc) |
| Test | `make test f=PublicSkillApi` |
