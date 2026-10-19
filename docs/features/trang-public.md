# Trang public

Trang ai cũng xem được, không cần đăng nhập. Từ 2026-10-19 trang nằm ở **gốc** `http://localhost:81/`: trang chủ hiện mọi danh mục, gồm Kỹ năng và các danh mục bài viết ([ADR-0021](../adr/0021-public-home-categories-articles.md)). Chạy trong ứng dụng riêng `public-site` (Astro, [ADR-0019](../adr/0019-public-user-frontend.md)): HTML render sẵn ở server, chỉ đọc. Người xem có thể đăng nhập để lưu yêu thích và xem sau ([người dùng cuối](nguoi-dung-cuoi.md)); phần đó dùng một script nhỏ (~3 KB), khách chưa đăng nhập không gọi API nào thêm.

## Màn hình

| Địa chỉ | Hiện gì |
|---|---|
| http://localhost:81/ | Trang chủ: thẻ **Skills** trước, rồi mỗi danh mục công khai một thẻ (tên, mô tả, số bài viết), theo thứ tự admin đặt |
| http://localhost:81/skills | Kỹ năng công khai, nhóm theo nhóm kỹ năng, kèm cấp độ và nhãn |
| http://localhost:81/skills/{slug} | Một kỹ năng: cấp độ hiện tại, lịch sử cấp độ (ngày), bằng chứng công khai mới nhất trước, mỗi bằng chứng kèm [file đính kèm](skill-ledger.md#file-đính-kèm-bằng-chứng) (ảnh nhỏ nếu là ảnh hoặc video, tên, dung lượng) |
| http://localhost:81/guides/{danh mục} | Bài viết công khai của một danh mục: tiêu đề, tóm tắt ([danh mục và bài viết](bai-viet.md)) |
| http://localhost:81/guides/{danh mục}/{bài} | Một bài viết, nút ☆ / 🔖 |
| http://localhost:81/files/{mã} | Mở một file đính kèm: xin API link tạm rồi chuyển tới MinIO. Ảnh, PDF, video, nhạc, file chữ mở trong trình duyệt; loại khác tải về |
| `/account/login`, `/account/register`, `/me` | Tài khoản người dùng ([người dùng cuối](nguoi-dung-cuoi.md)) |

Đường dẫn cũ vẫn chạy (chuyển hướng 301): `/docs…` → `/`; `/skills/me` → `/me`; `/skills/account/login`, `/skills/account/register` → `/account/…`; `/skills/files/{mã}` → `/files/{mã}`; `/login` → `/admin/login` (trang quản trị).

## API

| API | Làm gì |
|---|---|
| `GET /api/public/skills` | Danh sách kỹ năng công khai, xếp theo tên |
| `GET /api/public/skills/{slug}` | Chi tiết một kỹ năng công khai; mỗi bằng chứng có `files: [{key, name, size, mime_type, thumbnail_url}]` (`thumbnail_url`: link ký tạm 1 giờ của ảnh nhỏ, null khi không có); `url` là null khi bằng chứng chỉ có file |
| `GET /api/public/files/{key}` | Link tạm `{url, expires_at}` của một file đính kèm (5 phút để tải, 1 giờ để xem trong trình duyệt); `key` lấy từ `files[].key` |
| `GET /api/public/categories`, `…/categories/{slug}`, `…/articles/{danh mục}/{bài}` | Danh mục và bài viết, xem [bai-viet.md](bai-viet.md) |

Không cần đăng nhập, chỉ có GET, giới hạn 60 request / phút / IP.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Chỉ hiện thứ đã bật công khai | Kỹ năng phải bật "công khai" trong trang quản trị. Bằng chứng phải bật "công khai" và chưa bị ẩn bởi lần [nhập từ Obsidian](nhap-tu-obsidian.md). Bài viết: cả bài và danh mục của nó phải công khai |
| Thứ riêng tư trả 404 | Giống hệt thứ không tồn tại, để không lộ có thứ đó |
| File chỉ mở được khi đang hiện trên trang | File phải gắn vào bằng chứng công khai (chưa bị ẩn) của ít nhất một kỹ năng công khai, chưa bị xóa trong Files. Mọi trường hợp khác, kể cả mã sai, trả 404 như nhau |
| Không lộ số thứ tự nội bộ | Trang và API public không có `id`, không có đường dẫn thư mục của file. File dùng mã ngẫu nhiên (UUID) riêng cho mỗi lần gắn; gỡ file khỏi bằng chứng là mã đó hết dùng được |
| Link file luôn mới | Trang chỉ chứa `/files/{mã}`; mỗi lần bấm mới xin link ký tạm, nên trang lưu đệm bao lâu cũng không bị link hết hạn |
| Ảnh nhỏ | Link ảnh nhỏ nằm sẵn trong câu trả lời của API (ký tạm 1 giờ), không xin riêng từng ảnh, để không tốn lượt trong giới hạn 60 request / phút. Ảnh nhỏ là bản JPEG thu nhỏ, không lộ file gốc |
| Lưu đệm 60 giây | Server của trang public gọi API qua nginx và giữ kết quả 60 giây trong bộ nhớ; sửa trong trang quản trị có thể mất tới 1 phút mới thấy. Hết 60 giây thì lần mở tiếp theo lấy bản mới, nên link ảnh nhỏ (1 giờ) không bao giờ hết hạn trên trang |
| Chia đường dẫn | nginx: `/admin…`, `/_next/…`, `/favicon.ico` → trang quản trị; `/api/` → Laravel; mọi đường dẫn khác → trang public |

## Dữ liệu

Đọc các bảng của [Skill Ledger](skill-ledger.md) (`skill`, `skill_level`, `evidence`, `tag`, các bảng liên kết, `evidence_media` + `media_mgmt` cho file) và của [danh mục, bài viết](bai-viet.md) (`content_category`, `article`).

## Code

| Phần | File |
|---|---|
| API | `laravel-api/app/Services/Public/`, `laravel-api/app/Http/Resources/Public/` |
| Trang | `public-site/src/pages/`: `index.astro` (trang chủ), `skills/`, `guides/[category]/`, `files/[key].ts`, `account/`, `me.astro` |
| Đường dẫn | `public-site/src/lib/routes.ts` (mọi đường dẫn trang); chuyển hướng cũ trong `public-site/astro.config.mjs` và `docker/nginx/default.conf` |
| Gọi API | `public-site/src/lib/public-api.ts`; kiểu dữ liệu sinh từ `openapi.json` vào `src/types/openapi.d.ts` bằng `make openapi` |
| Test | `make test f=PublicSkillApi`, `f=PublicFileApi`, `f=PublicContentApi`; `make e2e` (trang chủ → danh mục → bài viết, trang kỹ năng, đường dẫn cũ) |
