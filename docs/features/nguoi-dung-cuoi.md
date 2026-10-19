# Người dùng cuối (tài khoản trên trang public)

Người xem trang public **có thể** tạo tài khoản để đánh dấu **yêu thích** và **lưu xem sau** kỹ năng hoặc bằng chứng. Không có tài khoản vẫn xem được đầy đủ. Yêu cầu: [REQ-003](../requirements/REQ-003-tai-khoan-nguoi-dung.md). Thiết kế: [ADR-0020](../adr/0020-end-user-accounts.md).

Trạng thái: **xong** (2026-10-19). Không làm lịch sử đã xem (chủ dự án bỏ).

## Màn hình trên trang public

| Địa chỉ | Làm gì |
|---|---|
| Góc trên mọi trang | "Sign in", hoặc tên người dùng → My lists |
| http://localhost:81/skills/{slug} | Nút ☆ (yêu thích) và 🔖 (xem sau) cạnh tên kỹ năng và cạnh từng bằng chứng. Chưa đăng nhập mà bấm thì chuyển sang trang đăng nhập, xong thì quay lại đúng trang đó |
| http://localhost:81/skills/account/login | Đăng nhập bằng email + mật khẩu |
| http://localhost:81/skills/account/register | Đăng ký: email, tên hiển thị, mật khẩu ≥ 8 ký tự. Admin tắt đăng ký thì trang báo "Registration is closed for now" |
| http://localhost:81/skills/me | Hai danh sách Favorites, Read later (mới nhất trước); đăng xuất; đổi mật khẩu; xóa tài khoản (nhập lại mật khẩu) |

## Màn hình trong trang quản trị

Menu **Master Data → End users** (`/admin/end-users`, quyền `end-user.list`).

| Thao tác | Cách làm | Quyền |
|---|---|---|
| Xem, tìm, lọc | Bảng: email, tên hiển thị, số mục đã lưu, trạng thái, lần đăng nhập cuối, ngày tạo | `end-user.list` |
| Khóa / mở khóa | Nút sửa → bỏ tick "Can sign in". Người dùng bị đăng xuất ở lần gọi tiếp theo | `end-user.update` |
| Đặt lại mật khẩu | Nút sửa → ô "New password" (để trống là giữ mật khẩu cũ). Không có email "quên mật khẩu" | `end-user.update` |
| Xóa | Nút thùng rác; mọi thứ đã lưu bị xóa theo | `end-user.delete` |
| Bật / tắt đăng ký | Ô tick phía trên bảng. Tắt thì tài khoản cũ vẫn dùng được | Xem: `end-user.registration`; đổi: `end-user.registration-update` |

Không có nút Thêm: người dùng tự đăng ký. Vai trò Chỉ xem có hai quyền xem. Mọi thay đổi của admin (khóa, đặt lại mật khẩu, xóa, bật / tắt đăng ký) ghi vào [nhật ký thay đổi](nhat-ky-thay-doi.md), loại `end_user` và `app_setting`; mật khẩu không bao giờ được ghi.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Tách khỏi admin | Bảng riêng `end_user`, bộ đăng nhập riêng `user`, API riêng `/api/user/*`. Người dùng không gọi được API quản trị, admin không gọi được API người dùng (có test) |
| Chung cookie phiên | Cùng trình duyệt có thể vừa đăng nhập admin vừa đăng nhập người dùng. Người dùng đăng xuất không làm admin bị đăng xuất. Admin đăng xuất thì cả phiên bị hủy, người dùng cũng phải đăng nhập lại |
| Email | Không phân biệt hoa thường, lưu dạng chữ thường, không trùng |
| Đăng nhập sai | Cùng một thông báo cho email lạ, sai mật khẩu, tài khoản bị khóa. Sai 5 lần theo email + IP hoặc 30 lần theo IP thì chặn 15 phút |
| Giới hạn | Đăng ký 5 lần / giờ / IP; mọi API người dùng 60 request / phút / IP |
| Chỉ lưu thứ đang công khai | Kỹ năng phải công khai; bằng chứng phải công khai, chưa bị ẩn, thuộc ít nhất một kỹ năng công khai. Thứ khác trả 404. Thứ đã lưu mà sau đó bị ẩn thì không hiện trong danh sách (không lộ tiêu đề) |
| Xóa | Xóa kỹ năng, bằng chứng hay tài khoản thì các dòng đã lưu tự mất (khóa ngoại) |
| Nhẹ | Trang vẫn đọc được khi tắt JavaScript. Script chỉ 2,9 KB trên trang kỹ năng (1,6 KB khi nén); khách chưa đăng nhập không gọi API nào thêm (trình duyệt chỉ gọi khi có cookie đánh dấu `sm_user`) |
| Đổi mật khẩu | Không đăng xuất các thiết bị khác (khác admin) |

## API

| API | Làm gì |
|---|---|
| `GET /api/user/registration` | `{open}`: có cho đăng ký không |
| `POST /api/user/register` | Đăng ký và đăng nhập luôn (403 khi đóng đăng ký) |
| `POST /api/user/login`, `POST /api/user/logout` | Đăng nhập, đăng xuất |
| `GET /api/user/me` | `{email, display_name}` |
| `PUT /api/user/password` | `current_password`, `password`, `password_confirmation` |
| `POST /api/user/delete-account` | `password` |
| `GET /api/user/saved/list` | Danh sách đã lưu (`kind` để lọc): `kind`, `target` (`skill` / `evidence`), `key`, `title`, `skill_slug`, `skill_name`, `saved_at` |
| `POST /api/user/saved/store`, `POST /api/user/saved/delete` | `kind` (`favorite` / `read_later`) + `skill` (slug) **hoặc** `evidence` (mã `key` của bằng chứng trong API public). Lưu hai lần hay bỏ thứ chưa lưu đều không lỗi |
| `GET /api/admin/end-user/list`, `PUT update/{id}`, `POST delete` | Quản trị người dùng |
| `GET /api/admin/end-user/registration`, `PUT registration-update` | Xem / đổi công tắc đăng ký |

Gọi `GET /api/sanctum/csrf-cookie` trước mọi lệnh ghi, như trang quản trị.

## Dữ liệu

| Bảng | Dùng cho |
|---|---|
| `end_user` | Email (duy nhất theo chữ thường), tên hiển thị, mật khẩu băm, đang dùng, lần đăng nhập cuối |
| `end_user_saved` | Người dùng, loại (`favorite` / `read_later`), **hoặc** `skill_id` **hoặc** `evidence_id` (ràng buộc CHECK), không trùng |
| `app_setting` | Công tắc `end_user_registration_open` (không có dòng = đang mở) |
| `evidence.public_key` | Mã ngẫu nhiên của bằng chứng cho trang public |

## Code

| Phần | File |
|---|---|
| API người dùng | `laravel-api/app/Http/Controllers/User/`, `app/Services/User/`, `app/Repositories/User/`, `app/Models/User/` |
| API quản trị | `app/Http/Controllers/Master/EndUserController.php`, `app/Services/Master/EndUserService.php`, `app/Services/System/RegistrationSetting.php` |
| Trang public | `public-site/src/pages/account/`, `src/pages/me.astro`, `src/components/SaveButtons.astro`, `src/scripts/` |
| Trang quản trị | `nextjs-fe/src/app/admin/end-users/`, `src/features/master/components/{end-user-form,registration-switch}.tsx` |
| Test | `make test f=EndUser`, `f=SavedApi`; `pnpm test end-user-form`; `make e2e` (`end-users.spec.ts`) |
