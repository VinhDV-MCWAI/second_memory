# Trang public

Trang ai cũng xem được, không cần đăng nhập: danh sách kỹ năng công khai và chi tiết từng kỹ năng. Chạy trong ứng dụng riêng `nextjs-docs`, chỉ đọc.

## Màn hình

| Địa chỉ | Hiện gì |
|---|---|
| http://localhost:81/skills | Kỹ năng công khai, nhóm theo nhóm kỹ năng, kèm cấp độ và nhãn |
| http://localhost:81/skills/{slug} | Một kỹ năng: cấp độ hiện tại, lịch sử cấp độ (ngày), bằng chứng công khai mới nhất trước, mỗi bằng chứng kèm [file đính kèm](skill-ledger.md#file-đính-kèm-bằng-chứng) (tên, dung lượng) |
| http://localhost:81/skills/files/{mã} | Mở một file đính kèm: xin API link tạm rồi chuyển tới MinIO. Ảnh, PDF, video, nhạc, file chữ mở trong trình duyệt; loại khác tải về |
| http://localhost:81/docs và mọi đường dẫn `/docs/...` | Trang "Nội dung đã chuyển đi" (nội dung CMS cũ đã bỏ). Giữ lại để link cũ không bị 404 |
| `/` của ứng dụng public | Chuyển tới `/docs` |

## API

| API | Làm gì |
|---|---|
| `GET /api/public/skills` | Danh sách kỹ năng công khai, xếp theo tên |
| `GET /api/public/skills/{slug}` | Chi tiết một kỹ năng công khai; mỗi bằng chứng có `files: [{key, name, size, mime_type}]`; `url` là null khi bằng chứng chỉ có file |
| `GET /api/public/files/{key}` | Link tạm `{url, expires_at}` của một file đính kèm (5 phút để tải, 1 giờ để xem trong trình duyệt); `key` lấy từ `files[].key` |

Không cần đăng nhập, chỉ có GET, giới hạn 60 request / phút / IP.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Chỉ hiện thứ đã bật công khai | Kỹ năng phải bật "công khai" trong trang quản trị. Bằng chứng phải bật "công khai" và chưa bị ẩn bởi lần [nhập từ Obsidian](nhap-tu-obsidian.md) |
| Kỹ năng riêng tư trả 404 | Giống hệt kỹ năng không tồn tại, để không lộ có kỹ năng đó |
| File chỉ mở được khi đang hiện trên trang | File phải gắn vào bằng chứng công khai (chưa bị ẩn) của ít nhất một kỹ năng công khai, chưa bị xóa trong Files. Mọi trường hợp khác, kể cả mã sai, trả 404 như nhau |
| Không lộ số thứ tự nội bộ | Trang và API public không có `id`, không có đường dẫn thư mục của file. File dùng mã ngẫu nhiên (UUID) riêng cho mỗi lần gắn; gỡ file khỏi bằng chứng là mã đó hết dùng được |
| Link file luôn mới | Trang chỉ chứa `/skills/files/{mã}`; mỗi lần bấm mới xin link ký tạm, nên trang lưu đệm bao lâu cũng không bị link hết hạn |
| Lưu đệm 60 giây | Server của trang public gọi API qua nginx và giữ kết quả 60 giây; sửa trong trang quản trị có thể mất tới 1 phút mới thấy |

## Dữ liệu

Đọc các bảng của [Skill Ledger](skill-ledger.md): `skill`, `skill_level`, `evidence`, `tag`, các bảng liên kết và `evidence_media` + `media_mgmt` cho file. Không có bảng riêng.

## Code

| Phần | File |
|---|---|
| API | `laravel-api/app/Services/Public/{PublicSkillService,PublicFileService}.php`, `laravel-api/app/Http/Resources/Public/` |
| Trang | `nextjs-docs/src/app/skills/` (link file: `skills/files/[key]/route.ts`; danh sách file: `src/components/skills/evidence-files.tsx`), `nextjs-docs/src/app/docs/` |
| Gọi API | `nextjs-docs/src/lib/ledger-api.ts` (kiểu dữ liệu phải khớp `openapi.json`, sửa cùng lúc) |
| Test | `make test f=PublicSkillApi`, `f=PublicFileApi` |
