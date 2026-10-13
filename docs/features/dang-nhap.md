# Đăng nhập

Quản trị viên đăng nhập bằng **tên đăng nhập** và mật khẩu. Phiên đăng nhập giữ bằng cookie (Laravel Sanctum, [ADR-0004](../adr/0004-sanctum-spa-cookie-auth.md)); trình duyệt không cầm token nào.

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| http://localhost:81/login | Form đăng nhập. Đăng nhập xong quay lại trang đang mở dở (nếu có), không thì tới trang tổng quan `/admin` |
| Nút đăng xuất ở góc trên | Kết thúc phiên, quay về `/login` |
| Mọi trang `/admin/...` | Chưa đăng nhập thì tự chuyển tới `/login` |

Tài khoản dev: `root` / `12345678` (tạo bằng `RootAccountSeeder`, xem [dev-guide.md](../dev-guide.md)).

## Luồng

```
1. GET  /api/sanctum/csrf-cookie         → nhận cookie XSRF-TOKEN
2. POST /api/admin/credential/login       → gửi user_name, password + header X-XSRF-TOKEN
                                            → nhận cookie laravel_session (phiên lưu trong Redis)
3. Mọi API /api/admin/... gửi kèm cookie
4. POST /api/admin/credential/logout      → xóa phiên
```

## API

| API | Làm gì | Cần đăng nhập |
|---|---|---|
| `GET /api/sanctum/csrf-cookie` | Lấy cookie chống giả mạo request | Không |
| `POST /api/admin/credential/login` | Đăng nhập, trả thông tin tài khoản | Không |
| `POST /api/admin/credential/logout` | Đăng xuất. Gọi khi không có phiên cũng thành công | Không |
| `GET /api/admin/credential/me` | Tài khoản đang đăng nhập. Giao diện dùng để kiểm tra còn phiên không | Có |

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Không lộ tài khoản có tồn tại hay không | Sai tên, sai mật khẩu, tài khoản bị khóa: cùng một lỗi 401 |
| Khóa tạm khi sai nhiều lần | Đếm theo tên đăng nhập + IP; quá số lần thì trả 429 kèm `Retry-After`. Đăng nhập đúng thì xóa bộ đếm |
| Đổi mã phiên sau khi đăng nhập | Chống chiếm phiên cũ |
| Ghi nhật ký | Đăng nhập, đăng xuất, đăng nhập sai đều có một dòng trong [nhật ký thay đổi](nhat-ky-thay-doi.md). Dòng đăng nhập sai chỉ ghi tên đã gõ, không ghi mã tài khoản |
| Chỉ đi qua cổng 81 | Cookie và CSRF chỉ đúng khi trình duyệt vào qua nginx `http://localhost:81` |

Token API (dùng cho [nhập từ Obsidian](nhap-tu-obsidian.md)) là cách đăng nhập thứ hai, chỉ mở được đúng một API import.

## Dữ liệu

| Bảng / nơi lưu | Dùng để |
|---|---|
| `admin_mst` | Tài khoản, mật khẩu đã băm, vai trò, trạng thái |
| Redis | Phiên đăng nhập |
| `audit_log` | Lịch sử đăng nhập |

## Code

| Phần | File |
|---|---|
| Route | `laravel-api/routes/api.php` |
| Xử lý | `laravel-api/app/Services/Custom/CredentialService.php` |
| Kiểm tra vai trò mỗi request | `laravel-api/app/Http/Middleware/AdminMiddleware.php` |
| Trang đăng nhập | `nextjs-fe/src/app/login/page.tsx` |
| Test | `make test f=LoginApi`, `make test f=LogoutApi`, `make test f=MeApi` |

Lỗi hay gặp (401 khi nhập email, 429, 419 CSRF): [dev-guide.md mục 6](../dev-guide.md#6-các-lỗi-hay-gặp).
