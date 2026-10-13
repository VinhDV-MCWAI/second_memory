# Đăng nhập

Quản trị viên đăng nhập bằng **tên đăng nhập** và mật khẩu. Phiên đăng nhập giữ bằng cookie (Laravel Sanctum, [ADR-0004](../adr/0004-sanctum-spa-cookie-auth.md)); trình duyệt không cầm token nào.

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| http://localhost:81/login | Form đăng nhập. Đăng nhập xong quay lại trang đang mở dở (nếu có), không thì tới trang tổng quan `/admin` |
| Nút đăng xuất ở góc trên | Kết thúc phiên, quay về `/login` |
| Mọi trang `/admin/...` | Chưa đăng nhập thì tự chuyển tới `/login` |

Tài khoản dev: chủ `root` / `12345678` và chỉ xem `viewer` / `12345678` (tạo bằng `make seed`, xem [dev-guide.md](../dev-guide.md)).

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

## So với cách tự viết trước đây

Trước ngày 2026-10-07, đăng nhập dùng cặp JWT tự viết: token truy cập sống 5 phút, token làm mới sống 3 ngày và được đổi mới mỗi lần dùng. Thiết kế cũ và các lỗi của nó được ghi trong [hướng dẫn xác thực tự viết](../archive/learning/AUTH-GUIDE.md). Lý do thay: [ADR-0004](../adr/0004-sanctum-spa-cookie-auth.md).

| Mặt | Cách cũ (JWT tự viết) | Hiện tại (phiên Sanctum) |
|---|---|---|
| Phần mã hoá | Tự viết bộ ký và giải mã, kiểm tra sai thứ tự | Phần có sẵn của Laravel: cookie mã hoá bằng `APP_KEY` |
| Lộ dữ liệu Redis | Token thô làm tên khoá, đọc được Redis là có token dùng ngay | Redis chỉ có mã phiên, muốn tạo cookie hợp lệ còn phải có `APP_KEY` |
| Chống giả mạo request (CSRF) | Không có | Có, thiếu header `X-XSRF-TOKEN` thì trả 419 |
| Sai mật khẩu nhiều lần | Khoá vĩnh viễn, thông báo lỗi để lộ tên đăng nhập có tồn tại | Khoá 15 phút theo tên + IP, mọi lỗi dùng chung một thông báo |
| Khoá hoặc xoá admin | Vẫn lấy được token mới tới 3 ngày | Bị chặn ngay request kế tiếp |
| Đổi quyền | Phải đăng nhập lại mới có hiệu lực | API áp ngay request kế tiếp |
| Thiếu quyền | Trả 401, giao diện đi làm mới token vô ích | Trả 403 |
| Đăng xuất | Hỏng khi token truy cập đã hết hạn | Luôn thành công |
| Làm mới đăng nhập | Giao diện hẹn giờ làm mới mỗi 5 phút | Không cần, phiên tự gia hạn khi dùng |
| Nhiều tab | Các tab tranh nhau làm mới token, phải có khoá | Các tab dùng chung cookie, đăng nhập / đăng xuất đồng bộ qua `BroadcastChannel` |
| Nhiều thiết bị | Mỗi máy một cặp token, bảng `token_mst` có lưu thiết bị và IP nhưng không có màn hình xem | Mỗi máy một phiên riêng, đăng xuất máy này không ảnh hưởng máy khác. Chưa có màn hình xem |
| Mỗi request tốn gì | Kiểm tra chữ ký + 2 lần đọc Redis | 1 lần đọc Redis + 1–2 truy vấn DB theo khoá chính. Chậm hơn chút, đổi lại thu hồi được ngay |
| `config:cache` | Hỏng (khoá bí mật đọc bằng `env()`) | Chạy bình thường |

## Nâng cấp để dành

Ghi ngày 2026-10-13, **chưa làm**. Mục tiêu: đăng nhập chạy tốt dù ít hay nhiều người dùng. Khi bắt đầu, mỗi mục đi qua bước đề xuất và chờ chủ dự án xác nhận như mọi việc khác. Mục nào thêm bảng, đổi DB hoặc đổi cách đăng nhập thì bắt buộc phải xác nhận.

| # | Việc | Vì sao | Cách làm gợi ý | Đánh đổi |
|---|---|---|---|---|
| 1 | **Màn hình "Phiên đăng nhập"**: xem đang đăng nhập ở thiết bị nào (trình duyệt, IP, lần dùng cuối), đăng xuất một thiết bị, đăng xuất mọi nơi khác | Hiện không xem được và không đá được phiên từ xa. Cả cách cũ lẫn cách mới đều thiếu | **A.** Giữ phiên trong Redis, thêm bảng `admin_sessions` (mã admin, mã phiên, trình duyệt, IP, lần dùng cuối). Ghi khi đăng nhập, xoá khi đăng xuất. Đá phiên thì xoá khoá Redis của phiên đó. **B.** Chuyển `SESSION_DRIVER=database`: bảng `sessions` có sẵn đủ cột, ít code nhất | A: thêm bảng và code, nhưng vẫn nhanh khi đông người dùng. B: mỗi request phải ghi DB một lần, ổn khi ít người, nặng khi nhiều. Nhắm tới nhiều người dùng thì chọn **A** |
| 2 | **Đổi mật khẩu thì đá mọi phiên khác** | Lộ mật khẩu thì đổi mật khẩu phải cắt được kẻ đang dùng | `config/sanctum.php` đã bật `AuthenticateSession` (so mật khẩu lưu trong phiên). Chỉ cần viết test chứng minh, thiếu thì gọi `Auth::logoutOtherDevices()` | Gần như không có |
| 3 | **Giao diện tự cập nhật quyền** | Quyền chỉ lấy một lần lúc mở trang. Đổi quyền thì API chặn đúng, nhưng nút và menu phải tải lại trang mới đổi theo | Gọi lại `credential/me` khi quay lại tab và khi gặp 403 | Thêm vài request nhỏ |
| 4 | **Giới hạn đăng nhập theo IP** (ngoài giới hạn theo tên + IP đang có) | Một IP thử hàng loạt tên đăng nhập khác nhau thì hiện không bị chặn | Thêm một khoá `RateLimiter` chỉ theo IP, ngưỡng cao hơn | Nhiều người dùng chung một IP (văn phòng) có thể bị chặn nhầm nếu đặt ngưỡng thấp |
| 5 | **"Ghi nhớ đăng nhập"** | Phiên hết sau 120 phút không dùng (`SESSION_LIFETIME`), có thể quá ngắn | Ô "Ghi nhớ" dùng `remember` của Laravel (cookie dài hạn riêng) | Cookie sống lâu thì lộ ra nguy hiểm hơn. Nên đi cùng mục 1 để còn đá phiên được |
| 6 | **Cài đặt cookie khi chạy thật** | Chặn lộ phiên trên đường truyền và trong Redis | `SESSION_SECURE_COOKIE=true` (bắt buộc khi có HTTPS), cân nhắc `SESSION_ENCRYPT=true` | Mã hoá phiên tốn thêm chút CPU |
| 7 | **Cache thông tin admin mỗi request** — chỉ làm khi tải DB thật sự cao | Mỗi request hiện đọc admin và vai trò từ DB để thu hồi được ngay | Cache vài giây theo mã admin, xoá cache khi admin bị sửa, khoá hoặc xoá | Thêm một chỗ phải nhớ xoá cache. Làm sai thì admin bị khoá vẫn dùng được trong vài giây |
| 8 | **Đá phiên theo thời gian thực** — tuỳ chọn | Hiện trình duyệt chỉ biết bị đá ở request kế tiếp | Đẩy sự kiện qua WebSocket (Reverb) tới các tab của admin bị đá | Nhiều việc. Chặn ở request kế tiếp đã đủ an toàn |
| 9 | **Client không phải trình duyệt** (app điện thoại, công cụ dòng lệnh, bên thứ ba) | Cookie phiên chỉ dùng được cho web cùng site | Token API của Sanctum, có giới hạn quyền và hạn dùng (đã dùng cho [nhập từ Obsidian](nhap-tu-obsidian.md)). Bên thứ ba cần đăng nhập kiểu OAuth thì quyết định riêng (Passport hoặc dịch vụ ngoài) | Token phải tự quản lý hạn dùng và thu hồi |

Khi nhiều người dùng, phần đăng nhập hiện tại không phải sửa gì lớn: nhiều container PHP vẫn dùng chung phiên trong Redis, Redis tự xoá phiên hết hạn, mỗi phiên chỉ vài KB. Hai chỗ cần để ý trước là truy vấn DB mỗi request (mục 7) và cách lưu danh sách phiên (mục 1, chọn A).

Một tài khoản dùng chung cho nhiều người thì không biết ai đã làm gì. Nên tạo tài khoản riêng cho mỗi người và gán vai trò bằng [phân quyền động](phan-quyen-dong.md), để nhật ký ghi đúng người.
