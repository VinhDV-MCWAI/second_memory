# Xác thực (Authentication) — Hướng dẫn làm thủ công

> Tài liệu học tập cho việc tự refactor phần xác thực của `laravel-api`.
> Mục tiêu: hiểu cơ chế từ gốc, biết hệ thống hiện tại đang **đúng gì / thiếu gì**, và có lộ trình từng bước để tự sửa.
> Cập nhật: 2026-10-05. Mã nguồn tham chiếu: nhánh `refactor/p0-p1-foundation`.

---

## 1. Cách dùng tài liệu này

1. Đọc mục 2 để nắm luồng hiện tại (vẽ lại trên giấy nếu được).
2. Đọc mục 3 để hiểu "thủ công vs package" — vì sao người ta dùng package và bạn đang tự làm phần nào.
3. Mục 4 là **danh sách lỗi/thiếu sót** của code hiện tại, xếp theo mức độ.
4. Mục 5 là **lộ trình bài tập**: làm lần lượt, mỗi bước có tiêu chí "xong khi" và test cần viết.
5. Mục 6 cho biết làm thủ công xong bạn hiểu tới đâu và cần học thêm gì.

Khi làm xong một bước, có thể nhờ Claude review: *"review bước A3 trong AUTH-GUIDE"*.

---

## 2. Kiến trúc hiện tại

### 2.1 Thành phần

| Thành phần | File | Vai trò |
|---|---|---|
| JWT tự viết | [`app/Utilities/JsonWebToken.php`](../../app/Utilities/JsonWebToken.php) | encode/decode HS256, kiểm tra `exp`, `iat` |
| Service | [`app/Services/Custom/CredentialService.php`](../../app/Services/Custom/CredentialService.php) | login, refresh, logout, me; ghi Redis + `token_mst` |
| Controller | [`app/Http/Controllers/Custom/CredentialController.php`](../../app/Http/Controllers/Custom/CredentialController.php) | mỏng, gọi service |
| Middleware xác thực + phân quyền | [`app/Http/Middleware/AdminMiddleware.php`](../../app/Http/Middleware/AdminMiddleware.php) | đọc cookie, verify JWT, kiểm tra Redis, kiểm tra quyền theo route |
| Middleware websocket | [`app/Http/Middleware/BroadcastingAuthMiddleware.php`](../../app/Http/Middleware/BroadcastingAuthMiddleware.php) | bản sao rút gọn của AdminMiddleware cho `/broadcasting/auth` |
| Bảng refresh token | `token_mst` (migration `..._000018`) | lưu `md5(refresh_token)`, thiết bị, IP, hạn |
| View phân quyền | `admin_permission_view` (migration `..._000048`) | admin → (method, path) được phép |
| Cấu hình TTL | [`app/Constants/CommonVal.php`](../../app/Constants/CommonVal.php) | access 5 phút, refresh 3 ngày, khóa sau 5 lần sai |
| FE interceptor | `nextjs-fe/src/shared/api/client/client.ts` | gặp 401 → gọi refresh một lần (có lock) → gọi lại request |

### 2.2 Luồng

```
LOGIN  POST /api/admin/credential/login {user_name, password}
  ├─ tìm admin theo user_name → không có → 401
  ├─ limit_access >= 5 → 401 (E0610 "tài khoản bị khóa")
  ├─ sai mật khẩu → limit_access++ → LoginFailedException (transaction COMMIT để lưu bộ đếm)
  └─ đúng → limit_access = 0
       ├─ tạo access JWT (secret A, 5') + refresh JWT (secret B, 3 ngày), cùng iat
       ├─ Redis:  admin:{id}:{access_token}          (TTL 5')     ← "danh sách token còn sống"
       ├─ Redis:  admin:{id}:admin_permission (hash)  method → [paths] (TTL theo token)
       ├─ DB:     token_mst(md5(refresh), id, UA, IP, expired_at)
       └─ Set-Cookie: access_token (path /api/admin), refresh_token (path /api/admin/credential/trust), HttpOnly

REQUEST bất kỳ /api/admin/*  (AdminMiddleware)
  ├─ cookie access_token → decode + verify chữ ký + exp
  ├─ type == "admin"
  ├─ Redis có key admin:{id}:{token}? (thu hồi được token trước hạn)
  └─ route URI có trong permission hash theo method? → cho qua, gắn current_admin_id

REFRESH POST /api/admin/credential/trust/refresh-token
  ├─ cookie refresh_token → verify (secret B)
  ├─ tìm md5(token) trong token_mst → xóa (rotation: token cũ chỉ dùng 1 lần)
  └─ phát cặp token mới như login

LOGOUT POST /api/admin/credential/trust/logout → xóa key Redis + xóa dòng token_mst + cookie hết hạn
```

### 2.3 Những điểm hiện tại đã làm tốt

- Token nằm trong **cookie HttpOnly** → JavaScript (và XSS) không đọc được token.
- **Access token ngắn (5')** + **refresh token dài** + **rotation** (refresh token dùng một lần).
- Có **danh sách token còn sống trong Redis** → logout/thu hồi có hiệu lực ngay, khắc phục điểm yếu "JWT không thu hồi được".
- So sánh chữ ký bằng `hash_equals` (chống timing attack).
- Hai secret khác nhau cho access/refresh.
- Phân quyền theo route được cache vào Redis → không query DB mỗi request.
- Cookie refresh giới hạn `path` → không bị gửi kèm mọi request.
- FE có lock để nhiều request 401 cùng lúc chỉ gọi refresh một lần.

Đây là nền tảng đúng hướng. Phần dưới là những gì còn thiếu.

---

## 3. Thủ công hay dùng package?

### 3.1 Các lựa chọn

| Cách | Bạn tự làm | Package lo | Hợp khi |
|---|---|---|---|
| **Tự viết toàn bộ** (hiện tại) | Mọi thứ: định dạng JWT, base64url, ký, verify, claims, luồng, lưu trữ | — | Học cơ chế |
| **`firebase/php-jwt`** (đã có trong `composer.json` nhưng chưa dùng) | Luồng login/refresh/revoke, cookie, Redis, phân quyền | encode/decode, base64url, kiểm tra `alg`, `exp`/`nbf`/`iat` + leeway, nhiều thuật toán (HS/RS/ES/EdDSA), `kid`/JWKS | Muốn giữ luồng tự thiết kế nhưng **không tự viết phần mật mã** |
| **`lcobucci/jwt`** | Như trên | Như trên, API chặt chẽ hơn (validator constraints) | Như trên, thích kiểu strict |
| **`php-open-source-saver/jwt-auth`** (fork còn bảo trì của tymon) | Cấu hình | Guard Laravel (`auth:api`), blacklist, refresh | Muốn JWT kiểu "cắm là chạy" |
| **Laravel Sanctum (SPA cookie)** (đã cài) | Gần như không | Session cookie + CSRF, đăng nhập/đăng xuất, thu hồi; không cần JWT | SPA cùng domain gốc — **lựa chọn chuẩn của Laravel cho trường hợp này** |
| **Laravel Passport** | Cấu hình OAuth client | OAuth2 server đầy đủ | Cho bên thứ ba truy cập API — quá nặng với dự án này |

### 3.2 Lợi ích khi dùng package

- **Đúng chuẩn mật mã**: các lỗi kinh điển (alg confusion, base64url sai, so sánh không constant-time, thiếu kiểm tra claim) đã được cộng đồng phát hiện và vá.
- **Được vá bảo mật**: có CVE thì cập nhật version là xong.
- **Ít code phải bảo trì và test** hơn.
- **Tính năng sẵn**: leeway cho lệch giờ, `nbf`, `kid` để xoay khóa, thuật toán bất đối xứng.
- **Người mới đọc code hiểu ngay** vì là pattern quen thuộc.

### 3.3 Lợi ích khi tự làm

- Hiểu JWT ở mức byte: header/payload/signature, base64url, HMAC.
- Hiểu rõ trade-off giữa **stateless token** và **stateful session** (dự án này thực chất đã là *hybrid*: JWT + Redis).
- Biết chính xác vì sao các package làm những việc "thừa thãi" (kiểm tra `alg`, leeway, `jti`...).

### 3.4 Khuyến nghị

> **Học bằng tự viết → vận hành bằng thư viện cho phần mật mã.**
> Giữ luồng (login/refresh/revoke/phân quyền) do bạn thiết kế, nhưng sau khi hiểu, thay `JsonWebToken` bằng `firebase/php-jwt` (bước C1 ở mục 5). Quy tắc nghề nghiệp: *don't roll your own crypto in production*.
> Về lâu dài, với một SPA admin cùng domain, **Sanctum SPA** là lựa chọn đơn giản và chuẩn nhất — cân nhắc khi bạn đã hiểu hết phần JWT.

---

## 4. Đánh giá chi tiết: vấn đề và thiếu sót

Mức độ: 🔴 nghiêm trọng · 🟠 cao · 🟡 trung bình · 🔵 thấp/chất lượng code.

### 🔴 A1. Gọi `env()` lúc runtime
- **Ở đâu:** `CredentialService.php:130,137,248,349`, `AdminMiddleware.php:34`, `BroadcastingAuthMiddleware.php:49`; test `LogoutApiTest.php:148`, `RefreshTokenApiTest.php:271,274`.
- **Vì sao sai:** khi chạy `php artisan config:cache` (bắt buộc ở production), Laravel **không đọc `.env` nữa** → `env()` trả `null` → `decode(string $key)` nhận null → `TypeError` → **toàn bộ admin trả 500**. Tệ hơn, nếu secret rỗng (`""`) thì HMAC vẫn chạy với khóa rỗng → ai cũng ký được token.
- **Hướng sửa:** tạo `config/auth_token.php` (hoặc dùng lại `config/jwt.php` — file này hiện là bản copy bị comment của tymon, nên xóa và viết mới) đọc `env()` **chỉ ở đó**; code dùng `config('auth_token.access_secret')`. Thêm kiểm tra **fail-fast**: secret rỗng hoặc ngắn hơn 32 byte → throw khi boot (ví dụ trong `AppServiceProvider::boot`).

### 🔴 A2. Khóa tài khoản vĩnh viễn + lộ tài khoản tồn tại
- **Ở đâu:** `CredentialService.php:37-44`.
- **Vấn đề 1 (DoS):** sai 5 lần thì `limit_access >= 5` mãi mãi, chỉ reset khi login *đúng*, mà login đúng lại bị chặn trước. Kẻ xấu chỉ cần biết `user_name` là khóa được admin vĩnh viễn.
- **Vấn đề 2 (enumeration):** user không tồn tại → E0401; user bị khóa → E0610. Hai thông báo khác nhau cho phép dò xem username nào có thật.
- **Vấn đề 3 (timing):** user không tồn tại thì bỏ qua `Hash::check` → response nhanh hơn rõ rệt → cũng dò được.
- **Vấn đề 4:** không giới hạn theo IP → brute-force dàn trải nhiều username không bị cản.
- **Hướng sửa:** dùng `Illuminate\Support\Facades\RateLimiter` (hoặc middleware `throttle:`) với key `login:{user_name}|{ip}`, khóa **có thời hạn** (ví dụ 15 phút, tăng dần). Luôn chạy `Hash::check` với một hash giả khi user không tồn tại. Trả **cùng một thông báo** cho mọi lỗi đăng nhập. Khi đó không cần `LoginFailedException` + commit trong transaction nữa.

### 🟠 A3. Lưu nguyên token làm key Redis
- **Ở đâu:** `CredentialService.php:78,275`, `AdminMiddleware.php:49`, `BroadcastingAuthMiddleware.php:65`.
- **Vì sao:** ai có quyền đọc Redis (`KEYS admin:*`, backup, monitoring) là có **token dùng được ngay**.
- **Hướng sửa:** thêm claim `jti` (random 128-bit) vào payload; key Redis là `admin:{id}:at:{jti}`. Không bao giờ lưu token thô.

### 🟠 A4. Thứ tự verify trong `decode()` sai và thiếu kiểm tra
- **Ở đâu:** `JsonWebToken.php:98-160`.
- **Vấn đề:**
  1. Đọc và tin payload (`exp`, `iat`) **trước** khi verify chữ ký. Nguyên tắc: *verify chữ ký trước, rồi mới đọc claims*.
  2. Không kiểm tra header `alg`/`typ` → nên **bắt buộc** `alg === 'HS256'` (RFC 8725, chống alg confusion khi sau này thêm thuật toán khác).
  3. Giải mã base64**url** bằng `base64_decode` thường. Hiện chạy được nhờ chế độ non-strict *bỏ qua* ký tự `-`/`_` (thử 100.000 payload dạng hiện tại không lỗi), nhưng đó là may mắn chứ không đúng. Dòng `str_replace(['-','_',''], ['+','/','='])` thay chuỗi rỗng bằng `=` là vô nghĩa. Cần hàm `base64UrlDecode` đúng: đổi `-_` → `+/`, thêm padding, `base64_decode($s, true)` (strict). Larastan cũng bắt được hệ quả: ở dòng ~143, `if (false === $sig)` **không bao giờ đúng** vì `base64_decode` non-strict không trả `false` → mã lỗi `E0605` là code chết (lỗi này đang nằm trong `phpstan-baseline.neon`; sửa xong thì xóa dòng tương ứng khỏi baseline).
  4. Truy cập `$payload['exp']`, `$payload['iat']` không kiểm tra tồn tại/kiểu.
  5. `iat === exp - TTL` buộc chặt token với hằng số TTL: đổi TTL là toàn bộ token đang sống bị từ chối. Nên kiểm tra `exp > now - leeway`, `iat <= now + leeway`, `nbf` nếu có.
  6. Access và refresh token chỉ phân biệt bằng secret. Thêm claim `token_use: "access" | "refresh"` (hoặc `typ`) và kiểm tra nó, phòng khi hai secret vô tình trùng nhau.
- **Bài học:** đây chính là danh sách việc mà `firebase/php-jwt` làm sẵn (mục 3.2).

### 🟠 A5. Không có chiến lược CSRF rõ ràng
- **Bối cảnh:** auth bằng cookie thì trình duyệt **tự gửi cookie** → nguy cơ CSRF. Hiện tại đang dựa vào `SameSite` mặc định (`lax` từ `config/session.php`) và CORS `supports_credentials`.
- **Phân tích:** `SameSite=Lax` chặn cookie trong POST cross-site, nhưng **"same-site" khác "same-origin"**: một subdomain bị chiếm (hoặc cổng khác trên `localhost`) vẫn được coi là same-site.
- **Hướng sửa (chọn một):** đặt `sameSite: 'strict'` cho cookie auth; **và/hoặc** kiểm tra header `Origin` nằm trong allowlist cho các method ghi; **hoặc** dùng double-submit token (giống Sanctum: cookie `XSRF-TOKEN` + header `X-XSRF-TOKEN`).

### 🟠 A6. Không phát hiện refresh token bị đánh cắp (reuse detection)
- **Ở đâu:** `revokeToken()` (`CredentialService.php:239-280`).
- **Vấn đề:** rotation có rồi, nhưng nếu token cũ (đã xoay) bị dùng lại thì chỉ trả 401. Theo khuyến nghị OAuth 2.0 Security BCP (RFC 9700), dùng lại refresh token đã xoay là **dấu hiệu bị trộm** → nên thu hồi **cả họ token** (cả kẻ trộm lẫn người dùng thật phải đăng nhập lại).
- **Hướng sửa:** thêm `family_id` vào `token_mst` (và vào claim), khi xoay thì đánh dấu `revoked_at` thay vì xóa; gặp token đã revoked → xóa mọi token cùng `family_id` + xóa key Redis của admin.

### 🟡 A7. Cache phân quyền không bị xóa khi quyền thay đổi
- **Ở đâu:** `storeAccessTokenAndSetPermission()` (`CredentialService.php:75-106`).
- **Vấn đề:** đổi role/department của admin thì hash `admin:{id}:admin_permission` vẫn giữ quyền cũ tới khi hết TTL; và chỉ được nạp lại khi key không tồn tại.
- **Hướng sửa:** khi cập nhật role/department/policy của admin (các service Master) → xóa key permission của các admin liên quan (event + listener, hoặc observer). Viết test: đổi quyền → request kế tiếp bị chặn/cho qua đúng.

### 🟡 A8. Mã HTTP sai ngữ nghĩa
- `AdminMiddleware.php:63` ném `NotFoundHttpException` với code 401 khi không có quyền cho method; dòng 70 dùng 401 khi không có quyền cho route.
- **Chuẩn:** **401** = chưa xác thực / token sai; **403** = đã xác thực nhưng không đủ quyền. FE nhờ đó phân biệt được "cần refresh/login lại" và "không có quyền" (hiện tại FE sẽ gọi refresh vô ích khi gặp 401 do thiếu quyền).

### 🟡 A9. Logout thất bại khi access token đã hết hạn
- **Ở đâu:** `logout()` (`CredentialService.php:289-305`) gọi `revokeToken($accessToken)` trước → token hết hạn thì ném lỗi → **không xóa được refresh token và cookie**.
- **Hướng sửa:** logout phải **best-effort và idempotent**: cố thu hồi được gì thì thu hồi, luôn trả cookie hết hạn.

### 🟡 A10. Trùng lặp logic giữa hai middleware và `me()`
- `AdminMiddleware`, `BroadcastingAuthMiddleware`, `CredentialService::me()` đều tự decode token.
- **Hướng sửa:** tách một class `AccessTokenAuthenticator` (hoặc một **custom Guard** của Laravel — bài học rất đáng làm, xem C2) trả về admin đã xác thực; middleware chỉ gọi nó. `me()` dùng `current_admin_id` đã được middleware gắn.

### 🟡 A11. Ghi log quá nhiều ở broadcasting
- `BroadcastingAuthMiddleware.php:34,84` ghi `Log::info` cho **mỗi** lần auth websocket → log phình to. Hạ xuống `debug` hoặc bỏ.

### 🔵 A12. Những điểm nhỏ
- `md5()` cho hash refresh token: không sai về bảo mật (token có entropy cao) nhưng nên dùng `hash('sha256', ...)` cho chuẩn và để reviewer khỏi phải suy nghĩ.
- `token_mst` không có job dọn các dòng hết hạn → thêm lệnh schedule `tokens:prune`.
- Cookie `secure` chỉ bật ở `production` → nên lấy từ config (`SESSION_SECURE_COOKIE`) để staging HTTPS cũng bật.
- `login()` dùng `$request->only()` thay vì `$request->validated()`.
- Không có "đăng xuất mọi thiết bị" và không thu hồi token khi đổi mật khẩu.
- Không có audit log cho đăng nhập thành công/thất bại (ai, IP, lúc nào).

---

## 5. Lộ trình làm thủ công (bài tập theo thứ tự)

Mỗi bước: tạo nhánh `feature/auth-<bước>` từ `developer`, viết test **trước** (test đỏ), sửa code, test xanh. Chạy test: `docker exec ml-php php artisan test --filter=Auth`.

### Giai đoạn A — Sửa lỗi nghiêm trọng (giữ nguyên kiến trúc)

| Bước | Việc | Xong khi | Test cần có |
|---|---|---|---|
| **A1** | `env()` → `config()` + fail-fast khi secret rỗng/ngắn | `grep -rn "env(" app/ tests/` không còn kết quả; `php artisan config:cache && php artisan test` xanh | Boot app với secret rỗng → exception rõ ràng |
| **A2** | Rate limit đăng nhập có thời hạn, thông báo lỗi thống nhất, chống timing | Sai 5 lần → bị chặn N phút rồi tự mở; mọi lỗi login cùng message | (1) 5 lần sai → 429; (2) sau `travel(16)->minutes()` login đúng được; (3) user không tồn tại và mật khẩu sai trả cùng body |
| **A8** | 401 vs 403 trong middleware | Thiếu quyền → 403; token sai → 401 | Hai test tương ứng; FE không gọi refresh khi 403 |
| **A9** | Logout best-effort | Logout với access token hết hạn vẫn xóa refresh token + cookie | Test logout sau khi `travel` quá 5' |

### Giai đoạn B — Làm cứng JWT tự viết (học sâu)

| Bước | Việc | Xong khi | Test cần có |
|---|---|---|---|
| **B1** | Viết `base64UrlEncode/Decode` đúng chuẩn (strict) | Decode không còn dựa vào non-strict | Unit test với chuỗi chứa `-`, `_`, thiếu padding, ký tự rác → lỗi |
| **B2** | Thứ tự: tách 3 phần → verify `alg` header → verify chữ ký → mới parse claims | Token sửa payload nhưng giữ chữ ký → bị từ chối *trước* khi đọc `exp` | Unit test: header `alg: none`, `alg: HS512`, chữ ký sai, payload thiếu `exp` |
| **B3** | Claims chuẩn: `jti`, `token_use`, `iss`, `aud`, `nbf`, leeway 30s; bỏ ràng buộc `iat === exp - TTL` | Đổi TTL không làm hỏng token đang sống | Refresh token dùng làm access token → 401 |
| **B4** | Redis key theo `jti` (A3) | `KEYS admin:*` không còn chứa token | Test đọc Redis sau login |

### Giai đoạn C — Kiến trúc chuẩn Laravel

| Bước | Việc | Xong khi |
|---|---|---|
| **C1** | Thay ruột `JsonWebToken` bằng `firebase/php-jwt` (giữ interface `encode/decode` để code gọi không đổi). Nâng lên **`firebase/php-jwt:^7.0`** trước: bản 6.10.2 đang pin có advisory CVE-2025-45769 (chấp nhận khóa HMAC quá ngắn — 7.0 bắt buộc khóa ≥ độ dài hash), `composer audit` sẽ hết báo khi nâng | Toàn bộ test giai đoạn B vẫn xanh → chứng minh thư viện làm đúng những gì bạn đã tự làm |
| **C2** | Viết **custom Guard** (`Auth::extend('admin-jwt', ...)`) + `AccessTokenAuthenticator` (A10); route dùng `auth:admin`; `request()->user()` trả `AdminMst` thật | Hai middleware cũ chỉ còn phần phân quyền, hoặc được thay hẳn |
| **C3** | Phân quyền: chuyển kiểm tra route sang **Gate/Policy** hoặc middleware `can:`; xóa cache permission khi đổi role (A7) | Đổi role → có hiệu lực ngay ở request kế tiếp |
| **C4** | Reuse detection theo `family_id` (A6) + lệnh prune token hết hạn | Dùng lại refresh token cũ → cả họ token bị thu hồi |
| **C5** | CSRF: `SameSite=Strict` + kiểm tra `Origin` (A5) | Request ghi từ origin lạ → 403 |

### Giai đoạn D — Mở rộng (tùy chọn)

- Đăng xuất mọi thiết bị; trang "phiên đăng nhập" liệt kê `token_mst` (thiết bị, IP).
- Thu hồi mọi token khi đổi mật khẩu.
- 2FA bằng TOTP (`pragmarx/google2fa`) hoặc passkey/WebAuthn.
- Audit log đăng nhập.
- Thử so sánh: làm lại toàn bộ bằng **Sanctum SPA** trên một nhánh riêng, đếm số dòng code và số test cần → tự rút ra kết luận nên dùng cách nào lâu dài.

---

## 6. Làm thủ công thì hiểu được đến đâu?

### Sẽ hiểu sau khi làm xong A → C
- Cấu trúc JWT, base64url, HMAC-SHA256, vì sao so sánh constant-time.
- Claims chuẩn (RFC 7519) và các quy tắc verify (RFC 8725).
- Access/refresh token, rotation, reuse detection, thu hồi bằng Redis.
- Stateless vs stateful, vì sao hệ thống thực tế thường là hybrid.
- Bảo mật cookie: `HttpOnly`, `Secure`, `SameSite`, `Path`, CSRF.
- Rate limiting, user enumeration, timing attack.
- Cách Laravel tổ chức auth: Guard, UserProvider, Gate/Policy, middleware.

### Chưa chạm tới — cần học thêm khi cần
| Chủ đề | Khi nào cần | Gợi ý học |
|---|---|---|
| Ký bất đối xứng (RS256/ES256/EdDSA), JWKS, xoay khóa bằng `kid` | Nhiều service cùng verify token | RFC 7517, thư viện `firebase/php-jwt` `JWK::parseKeySet` |
| OAuth 2.0 / OpenID Connect | Đăng nhập bằng Google/GitHub, cấp quyền cho app bên thứ ba | RFC 6749, RFC 9700 (Security BCP), Laravel Socialite / Passport |
| Passkeys / WebAuthn | Đăng nhập không mật khẩu | webauthn.guide |
| Quản lý secret (Vault, Docker secrets) | Production nhiều người vận hành | Docker secrets, HashiCorp Vault |
| Chuẩn kiểm tra bảo mật | Tự đánh giá toàn diện | OWASP ASVS v4 chương 2 (Authentication) và 3 (Session) |

---

## 7. Tham khảo

- RFC 7519 — JSON Web Token
- RFC 8725 — JWT Best Current Practices
- RFC 9700 — OAuth 2.0 Security Best Current Practice (refresh token rotation & reuse detection)
- OWASP Cheat Sheets: Authentication, Session Management, JSON Web Token for Java (phần nguyên lý dùng chung), Cross-Site Request Forgery Prevention
- Laravel docs: Authentication (custom guards), Authorization (Gates/Policies), Rate Limiting, Sanctum (SPA authentication)
