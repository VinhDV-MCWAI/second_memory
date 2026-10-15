# Đăng nhập

Quản trị viên đăng nhập bằng **tên đăng nhập** và mật khẩu. Phiên đăng nhập giữ bằng cookie (Laravel Sanctum, [ADR-0004](../adr/0004-sanctum-spa-cookie-auth.md)); trình duyệt không cầm token nào.

## Màn hình

| Địa chỉ | Làm gì |
|---|---|
| http://localhost:81/login | Form đăng nhập. Đăng nhập xong quay lại trang đang mở dở (nếu có), không thì tới trang tổng quan `/admin`. Ô **Keep me signed in on this device for 30 days** để ghi nhớ đăng nhập, xem mục "Ghi nhớ đăng nhập" |
| Nút đăng xuất ở góc trên | Kết thúc phiên, quay về `/login` |
| Mọi trang `/admin/...` | Chưa đăng nhập thì tự chuyển tới `/login` |
| http://localhost:81/admin/sessions (menu tài khoản ở góc trên → **Signed-in devices**) | Xem tài khoản đang đăng nhập ở những thiết bị nào, đăng xuất một thiết bị hoặc mọi thiết bị khác. Xem mục "Thiết bị đang đăng nhập" bên dưới |

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
| `POST /api/admin/credential/login` | Đăng nhập, trả thông tin tài khoản. Gửi thêm `remember: true` để ghi nhớ thiết bị 30 ngày | Không |
| `POST /api/admin/credential/logout` | Đăng xuất. Gọi khi không có phiên cũng thành công | Không |
| `GET /api/admin/credential/me` | Tài khoản đang đăng nhập. Giao diện dùng để kiểm tra còn phiên không | Có |
| `GET /api/admin/admin-session/list` | Các thiết bị của chính mình đang đăng nhập, dùng gần nhất trước. Không trả mã phiên | Có |
| `POST /api/admin/admin-session/delete` | Đăng xuất các thiết bị đã chọn (`ids`), trả số thiết bị đã đăng xuất. Thiết bị đang dùng thì báo lỗi 422, muốn thoát thì bấm đăng xuất | Có |
| `POST /api/admin/admin-session/delete-others` | Đăng xuất mọi thiết bị khác, giữ thiết bị đang dùng | Có |

Ba API thiết bị ai đăng nhập cũng dùng được (không phải quyền trong ma trận quyền), và chỉ đụng tới phiên của chính mình.

## Quy tắc

| Quy tắc | Chi tiết |
|---|---|
| Không lộ tài khoản có tồn tại hay không | Sai tên, sai mật khẩu, tài khoản bị khóa: cùng một lỗi 401 |
| Khóa tạm khi sai nhiều lần | Hai bộ đếm, mỗi bộ khóa 15 phút. **Theo tên đăng nhập + IP**: sai 5 lần thì khóa tên đó trên IP đó; đăng nhập đúng thì xóa bộ đếm này. **Chỉ theo IP**: một IP sai tổng cộng 30 lần (với bất kỳ tên nào) thì cả IP bị khóa, chặn kiểu thử hàng loạt tên đăng nhập; đăng nhập đúng **không** xóa bộ đếm này, để kẻ tấn công không dùng tài khoản của chính mình để được thử tiếp. Bị khóa thì trả 429 kèm `Retry-After`. Ngưỡng 30 đặt cao để cả văn phòng dùng chung một IP ít khi bị chặn nhầm |
| Đổi mã phiên sau khi đăng nhập | Chống chiếm phiên cũ |
| Đổi mật khẩu thì đăng xuất mọi thiết bị khác | Đổi mật khẩu của chính mình: các thiết bị khác bị đăng xuất ở request kế tiếp (401), thiết bị đang dùng vẫn giữ phiên. Chủ đổi mật khẩu của người khác: mọi phiên của người đó bị đăng xuất. Lưu form mà để trống mật khẩu thì không ai bị đăng xuất. Cách làm: phiên giữ một mã của mật khẩu và so lại mỗi request (`AuthenticateSession` của Sanctum) |
| Giao diện tự cập nhật quyền | Quyền (menu, nút) lấy lúc mở trang, rồi tự lấy lại khi quay lại tab và khi một API trả 403. Lấy lại tối đa mỗi 5 giây một lần. Chủ đổi vai trò của ai thì người đó thấy menu và nút đổi theo mà không phải tải lại trang. API luôn chặn đúng theo quyền mới ngay từ request kế tiếp |
| Ghi nhật ký | Đăng nhập, đăng xuất, đăng nhập sai đều có một dòng trong [nhật ký thay đổi](nhat-ky-thay-doi.md). Dòng đăng nhập sai chỉ ghi tên đã gõ, không ghi mã tài khoản |
| Chỉ đi qua cổng 81 | Cookie và CSRF chỉ đúng khi trình duyệt vào qua nginx `http://localhost:81` |

## Thiết bị đang đăng nhập

Làm ngày 2026-10-15. Phiên vẫn giữ trong Redis như trước (nhanh khi đông người dùng). Mỗi phiên có thêm một dòng trong bảng `admin_session` để liệt kê ra màn hình.

| Việc | Cách chạy |
|---|---|
| Đăng nhập | Thêm một dòng: IP, trình duyệt (chuỗi User-Agent), giờ đăng nhập |
| Dùng tiếp | Cập nhật "lần dùng cuối" và IP, **tối đa 5 phút một lần**, không ghi DB ở mọi request |
| Đăng xuất | Xóa dòng của phiên đó |
| Đăng xuất một thiết bị từ máy khác | Xóa phiên trong Redis và xóa dòng. Thiết bị kia bị 401 ngay ở request kế tiếp. Nếu thiết bị đó đang có một request chạy dở và ghi phiên lại vào Redis, thì lần cập nhật 5 phút tiếp theo thấy dòng đã mất và đăng xuất nó |
| Đổi mật khẩu | Như trước, các thiết bị khác bị đăng xuất; giờ chúng cũng biến khỏi danh sách ngay |
| Khóa hoặc xóa tài khoản | Mọi thiết bị của tài khoản đó bị đăng xuất và biến khỏi danh sách |
| Phiên hết hạn (120 phút không dùng) | Redis tự xóa phiên. Dòng còn lại bị bỏ khi mở màn hình, và lệnh `auth:prune-sessions` chạy 0 giờ mỗi ngày dọn hết |
| Nhật ký | Đăng xuất thiết bị khác ghi một dòng "logged out" kèm số thiết bị đã đăng xuất |

Tên trình duyệt và hệ điều hành (ví dụ "Chrome on Windows") do giao diện đọc từ chuỗi User-Agent; chuỗi đầy đủ hiện khi rê chuột vào tên. Đăng nhập bằng token API (công cụ nhập từ Obsidian) không có phiên nên không hiện ở đây.

## Ghi nhớ đăng nhập

Làm ngày 2026-10-15. Bình thường phiên hết sau 120 phút không dùng. Tick ô ghi nhớ khi đăng nhập thì thiết bị đó tự đăng nhập lại trong **30 ngày** kể từ lúc đăng nhập (hết 30 ngày phải đăng nhập lại, dùng nhiều cũng không kéo dài thêm).

| Việc | Cách chạy |
|---|---|
| Đăng nhập có tick ô | Thiết bị nhận cookie `remember_device` sống 30 ngày, chứa mã dòng trong `admin_session` và một mã ngẫu nhiên. Bảng chỉ giữ bản băm (sha256) của mã, đọc được DB cũng không dùng được |
| Phiên hết hạn, thiết bị gọi API | Trước khi kiểm tra đăng nhập, máy chủ thấy cookie còn đúng thì đăng nhập lại thiết bị đó, tạo phiên mới, giữ nguyên dòng (màn hình thiết bị vẫn hiện giờ đăng nhập đầu tiên). Nhật ký ghi "logged in" kèm `via: remembered_device` |
| Đăng xuất một thiết bị từ máy khác, đổi mật khẩu, khóa hoặc xóa tài khoản | Dòng bị xóa nên cookie của thiết bị đó hết tác dụng. Mỗi thiết bị ghi nhớ riêng: đăng xuất máy này không làm máy khác quên |
| Bấm đăng xuất | Xóa dòng và cookie |
| Màn hình thiết bị | Cột "Kept signed in until" cho biết còn bao lâu thiết bị phải đăng nhập lại. Thiết bị được ghi nhớ vẫn hiện dù phiên đã hết, lệnh dọn hằng ngày không xóa nó |
| Trang `/admin/...` mở lại sau nhiều giờ | Giao diện cho vào nếu còn cookie phiên hoặc cookie ghi nhớ. Nếu một thao tác ghi bị lỗi 419 (mã chống giả mạo cũ sau khi đổi phiên), giao diện tự lấy mã mới và gửi lại một lần |

Không dùng "remember me" có sẵn của Laravel vì nó dùng chung một mã cho mọi thiết bị của tài khoản: đá một máy là mọi máy cùng bị quên.

Token API (dùng cho [nhập từ Obsidian](nhap-tu-obsidian.md)) là cách đăng nhập thứ hai, chỉ mở được đúng một API import.

## Dữ liệu

| Bảng / nơi lưu | Dùng để |
|---|---|
| `admin_mst` | Tài khoản, mật khẩu đã băm, vai trò, trạng thái |
| Redis | Phiên đăng nhập |
| `admin_session` | Danh sách thiết bị đang đăng nhập: tài khoản, mã phiên, IP, trình duyệt, giờ đăng nhập, lần dùng cuối; thiết bị ghi nhớ có thêm bản băm của mã ghi nhớ và hạn ghi nhớ |
| `audit_log` | Lịch sử đăng nhập |

## Code

| Phần | File |
|---|---|
| Route | `laravel-api/routes/api.php` |
| Xử lý | `laravel-api/app/Services/Custom/CredentialService.php` |
| Kiểm tra vai trò mỗi request | `laravel-api/app/Http/Middleware/AdminMiddleware.php` |
| Thiết bị đang đăng nhập, ghi nhớ đăng nhập | `laravel-api/app/Services/Master/AdminSessionService.php`, `laravel-api/app/Http/Middleware/TrackAdminSession.php`, `laravel-api/app/Http/Middleware/RestoreRememberedSession.php`, `nextjs-fe/src/features/master/components/session-list.tsx` |
| Trang đăng nhập | `nextjs-fe/src/app/login/page.tsx` |
| Test | `make test f=LoginApi`, `make test f=LogoutApi`, `make test f=MeApi`, `make test f=PasswordChangeSession`, `make test f=AdminSessionTest`, `make test f=RememberMeTest` |

Lỗi hay gặp (401 khi nhập email, 429, 419 CSRF): [dev-guide.md mục 6](../dev-guide.md#6-các-lỗi-hay-gặp).

## So với cách tự viết trước đây

Trước ngày 2026-10-07, đăng nhập dùng cặp JWT tự viết: token truy cập sống 5 phút, token làm mới sống 3 ngày và được đổi mới mỗi lần dùng. Thiết kế cũ và các lỗi của nó được ghi trong [hướng dẫn xác thực tự viết](../archive/learning/AUTH-GUIDE.md). Lý do thay: [ADR-0004](../adr/0004-sanctum-spa-cookie-auth.md).

| Mặt | Cách cũ (JWT tự viết) | Hiện tại (phiên Sanctum) |
|---|---|---|
| Phần mã hoá | Tự viết bộ ký và giải mã, kiểm tra sai thứ tự | Phần có sẵn của Laravel: cookie mã hoá bằng `APP_KEY` |
| Lộ dữ liệu Redis | Token thô làm tên khoá, đọc được Redis là có token dùng ngay | Redis chỉ có mã phiên, muốn tạo cookie hợp lệ còn phải có `APP_KEY` |
| Chống giả mạo request (CSRF) | Không có | Có, thiếu header `X-XSRF-TOKEN` thì trả 419 |
| Sai mật khẩu nhiều lần | Khoá vĩnh viễn, thông báo lỗi để lộ tên đăng nhập có tồn tại | Khoá 15 phút theo tên + IP và theo IP, mọi lỗi dùng chung một thông báo |
| Khoá hoặc xoá admin | Vẫn lấy được token mới tới 3 ngày | Bị chặn ngay request kế tiếp |
| Đổi quyền | Phải đăng nhập lại mới có hiệu lực | API áp ngay request kế tiếp |
| Thiếu quyền | Trả 401, giao diện đi làm mới token vô ích | Trả 403 |
| Đăng xuất | Hỏng khi token truy cập đã hết hạn | Luôn thành công |
| Làm mới đăng nhập | Giao diện hẹn giờ làm mới mỗi 5 phút | Không cần, phiên tự gia hạn khi dùng |
| Nhiều tab | Các tab tranh nhau làm mới token, phải có khoá | Các tab dùng chung cookie, đăng nhập / đăng xuất đồng bộ qua `BroadcastChannel` |
| Nhiều thiết bị | Mỗi máy một cặp token, bảng `token_mst` có lưu thiết bị và IP nhưng không có màn hình xem | Mỗi máy một phiên riêng, đăng xuất máy này không ảnh hưởng máy khác. Màn hình **Signed-in devices** xem và đăng xuất từ xa |
| Mỗi request tốn gì | Kiểm tra chữ ký + 2 lần đọc Redis | 1 lần đọc Redis + 1–2 truy vấn DB theo khoá chính. Chậm hơn chút, đổi lại thu hồi được ngay |
| `config:cache` | Hỏng (khoá bí mật đọc bằng `env()`) | Chạy bình thường |

## Nâng cấp để dành

> **Tạm dừng (chủ dự án quyết 2026-10-14):** mọi mục còn lại dưới đây chỉ làm khi chủ dự án yêu cầu. Không tự đề xuất, kể cả việc mới về đăng nhập phát sinh sau này.

Ghi ngày 2026-10-13, **chưa làm**. Mục tiêu: đăng nhập chạy tốt dù ít hay nhiều người dùng. Khi bắt đầu, mỗi mục đi qua bước đề xuất và chờ chủ dự án xác nhận như mọi việc khác. Mục nào thêm bảng, đổi DB hoặc đổi cách đăng nhập thì bắt buộc phải xác nhận.

| # | Việc | Vì sao | Cách làm gợi ý | Đánh đổi |
|---|---|---|---|---|
| 1 | ~~**Màn hình "Phiên đăng nhập"**~~ **Đã làm 2026-10-15** theo cách A (phiên giữ trong Redis + bảng `admin_session`), xem mục "Thiết bị đang đăng nhập" ở trên | | | |
| 2 | ~~**Đổi mật khẩu thì đá mọi phiên khác**~~ **Đã làm 2026-10-14**, xem bảng Quy tắc ở trên | | | |
| 3 | ~~**Giao diện tự cập nhật quyền**~~ **Đã làm 2026-10-14**, xem bảng Quy tắc ở trên | | | |
| 4 | ~~**Giới hạn đăng nhập theo IP**~~ **Đã làm 2026-10-14**, xem bảng Quy tắc ở trên | | | |
| 5 | ~~**"Ghi nhớ đăng nhập"**~~ **Đã làm 2026-10-15**: ghi nhớ riêng từng thiết bị 30 ngày, đá được từ màn hình thiết bị, xem mục "Ghi nhớ đăng nhập" ở trên | | | |
| 6 | **Cài đặt cookie khi chạy thật** | Chặn lộ phiên trên đường truyền và trong Redis | `SESSION_SECURE_COOKIE=true` (bắt buộc khi có HTTPS), cân nhắc `SESSION_ENCRYPT=true` | Mã hoá phiên tốn thêm chút CPU |
| 7 | **Cache thông tin admin mỗi request** — chỉ làm khi tải DB thật sự cao | Mỗi request hiện đọc admin và vai trò từ DB để thu hồi được ngay | Cache vài giây theo mã admin, xoá cache khi admin bị sửa, khoá hoặc xoá | Thêm một chỗ phải nhớ xoá cache. Làm sai thì admin bị khoá vẫn dùng được trong vài giây |
| 8 | **Đá phiên theo thời gian thực** — tuỳ chọn | Hiện trình duyệt chỉ biết bị đá ở request kế tiếp | Đẩy sự kiện qua WebSocket (Reverb) tới các tab của admin bị đá | Nhiều việc. Chặn ở request kế tiếp đã đủ an toàn |
| 9 | **Client không phải trình duyệt** (app điện thoại, công cụ dòng lệnh, bên thứ ba) | Cookie phiên chỉ dùng được cho web cùng site | Token API của Sanctum, có giới hạn quyền và hạn dùng (đã dùng cho [nhập từ Obsidian](nhap-tu-obsidian.md)). Bên thứ ba cần đăng nhập kiểu OAuth thì quyết định riêng (Passport hoặc dịch vụ ngoài) | Token phải tự quản lý hạn dùng và thu hồi |

Khi nhiều người dùng, phần đăng nhập hiện tại không phải sửa gì lớn: nhiều container PHP vẫn dùng chung phiên trong Redis, Redis tự xoá phiên hết hạn, mỗi phiên chỉ vài KB. Hai chỗ cần để ý trước là truy vấn DB mỗi request (mục 7) và cách lưu danh sách phiên (mục 1, chọn A).

Một tài khoản dùng chung cho nhiều người thì không biết ai đã làm gì. Nên tạo tài khoản riêng cho mỗi người và gán vai trò bằng [phân quyền động](phan-quyen-dong.md), để nhật ký ghi đúng người.
