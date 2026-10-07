# PRB-001 — Hand-written JWT auth costs more than it gives

> 🇻🇳 Hồ sơ vấn đề: phần xác thực JWT tự viết tốn công hơn giá trị nó mang lại.

| | |
|---|---|
| Status | Investigating (solution chosen in ADR-0004, implementation is backlog P2-11) |
| Found | 2026-10-05 (AUTH-GUIDE review), recorded 2026-10-07, by TL |
| Area | auth |
| Related | [REQ-001](../requirements/REQ-001-slim-down.md), [RFC-001](../design/RFC-001-slim-down.md) slice 7, [ADR-0004](../adr/0004-sanctum-spa-cookie-auth.md), [AUTH-GUIDE (learning record)](../archive/learning/AUTH-GUIDE.md) |

## 1. Context

The admin dashboard (`nextjs-fe`) and the API (`laravel-api`) are served from the same site through nginx. Admins log in with a user name and password. The owner wrote the whole authentication layer by hand to learn how tokens work: a JWT encoder/decoder (`JsonWebToken`), a login/refresh/logout service (`CredentialService`), a middleware that checks the token and the admin's permissions on every request (`AdminMiddleware`), a copy of it for WebSocket channel auth (`BroadcastingAuthMiddleware`), a refresh-token table (`token_mst`) and a per-admin permission table cached in Redis, loaded at login from the DB view `admin_permission_view`.

> 🇻🇳 Bối cảnh: dashboard admin và API chạy cùng một site sau nginx. Owner tự viết toàn bộ phần xác thực để học: bộ mã hoá/giải mã JWT, service login/refresh/logout, middleware kiểm tra token và quyền mỗi request, một bản sao middleware cho xác thực kênh WebSocket, bảng refresh token `token_mst`, và bảng quyền theo từng admin cache trong Redis (nạp lúc login từ view `admin_permission_view`).

The front end mirrors this: `auth-provider.tsx` schedules a token refresh before the 5-minute access token expires, coordinates tabs with a `BroadcastChannel`, and the axios client retries a request once after a 401 by calling the refresh endpoint behind a lock.

> 🇻🇳 Phía FE làm tương ứng: `auth-provider.tsx` hẹn giờ refresh trước khi access token 5 phút hết hạn, đồng bộ các tab bằng `BroadcastChannel`; axios client gặp 401 thì gọi refresh (có khoá) rồi gọi lại request.

The learning goal was reached: the AUTH-GUIDE written on 2026-10-05 explains the design, what it does well and a 13-point list of defects.

> 🇻🇳 Mục tiêu học đã đạt: AUTH-GUIDE (2026-10-05) giải thích thiết kế, điểm làm tốt và 13 thiếu sót.

## 2. Symptom

- Four refresh-token tests fail and are excluded from CI (`AUTH_TODO` in the `Makefile`): `RefreshTokenApiTest` t004 (malformed token → **500** instead of 401), t005 (revoked session can still refresh), t006 (missing permission cache), t019 (**a deleted admin can still get new tokens**).
- Every request reads the JWT secret with `env()` at runtime; with `php artisan config:cache` (required in production) `env()` returns `null` and every admin request fails.
- Five wrong passwords lock an account forever, and the login errors reveal which user names exist.
- Missing permission returns 401 instead of 403, so the FE tries a useless token refresh.
- Logout fails when the access token has already expired, leaving the refresh token alive.

> 🇻🇳 Triệu chứng: 4 test refresh token đỏ và bị loại khỏi CI (một test cho thấy admin đã bị xoá vẫn lấy được token mới); secret đọc bằng `env()` lúc chạy nên hỏng khi `config:cache`; sai mật khẩu 5 lần là khoá vĩnh viễn và thông báo lỗi để lộ username tồn tại; thiếu quyền trả 401 thay vì 403; logout lỗi khi access token đã hết hạn.

Full list with file and line references: AUTH-GUIDE §4 (A1–A13).

> 🇻🇳 Danh sách đầy đủ kèm vị trí trong code: AUTH-GUIDE mục 4 (A1–A13).

## 3. Root cause

1. Why are there security bugs? Because the code re-implements token cryptography, verification order, revocation, rotation, CSRF and rate limiting that frameworks already ship, and each of those has well-known traps.
2. Why was it written by hand? It was a learning exercise, and it was never replaced after the learning was done.
3. Why does it matter now? The project is being repurposed as an Engineering Lab; the admin panel has one real user (the owner) and soon a read-only demo user. A token system built for many clients is not needed.
4. Why not just fix the 13 defects? Fixing them by hand (AUTH-GUIDE stages A–C) is several weeks of work that ends with something Laravel Sanctum already provides, and every future change to auth would still need the same care.

**Actionable cause:** a stateless-token design was chosen for a client (a same-site SPA) that does not need tokens, and it is maintained by hand.

> 🇻🇳 Nguyên nhân gốc: (1) code tự làm lại phần mật mã, thứ tự verify, thu hồi, xoay vòng, CSRF, giới hạn đăng nhập — những thứ framework đã có và đều có bẫy kinh điển; (2) viết tay để học và chưa thay sau khi học xong; (3) giờ dự án là Engineering Lab với một người dùng thật và một tài khoản demo chỉ đọc, không cần hệ token cho nhiều client; (4) tự sửa 13 lỗi tốn nhiều tuần và cuối cùng cũng chỉ ra thứ Sanctum đã có. **Nguyên nhân xử lý được:** chọn thiết kế token stateless cho một SPA cùng site vốn không cần token, và tự bảo trì nó.

## 4. Impact

- **Security:** a deleted admin keeps access until the refresh token expires (3 days); raw tokens are Redis keys; permanent account lockout is a cheap denial of service. Only the dev stack runs today, so no real data was exposed.
- **Operations:** production cannot use `config:cache` safely.
- **Delivery:** four tests are excluded from CI, so the auth area has a known-red zone that hides new failures; RFC-001 slice 8 (roles) cannot drop the RBAC tables while login reads `admin_permission_view`.
- **Cost:** a JWT utility, a service, two middlewares, token CRUD, an auth provider with a refresh scheduler, a refresh interceptor and their tests — all maintained for one user. The exact line count removed is measured in P2-11.

If nothing is done, every later phase (observability, security review in P8, demo account in P10) builds on an auth layer with known holes.

> 🇻🇳 Hậu quả: bảo mật (admin bị xoá vẫn truy cập được tới 3 ngày, token thô làm key Redis, khoá tài khoản vĩnh viễn = DoS rẻ; hiện chỉ có môi trường dev nên chưa lộ dữ liệu thật); vận hành (không dùng được `config:cache`); tiến độ (4 test bị loại khỏi CI, slice 8 bị chặn vì login còn đọc `admin_permission_view`); chi phí (cả một bộ JWT, service, middleware, refresh ở FE và test chỉ cho một người dùng; số dòng xoá được đo ở P2-11). Không xử lý thì mọi giai đoạn sau xây trên một lớp auth có lỗ hổng đã biết.

## 5. Options considered

| Option | Pros | Cons | Effort |
|---|---|---|---|
| A. Fix the hand-written code (AUTH-GUIDE stages A–C) | Deepest learning; same design | Weeks of work; still custom code to secure and test forever | High |
| B. Keep the flow, swap the crypto for `firebase/php-jwt` (AUTH-GUIDE C1) | Removes the riskiest part (crypto) | Lockout, revocation, CSRF, 401/403, refresh bugs remain; pinned 6.10.2 has CVE-2025-45769 | Medium |
| C. Packaged JWT guard (`php-open-source-saver/jwt-auth`) | Standard guard, blacklist, refresh | Still tokens + refresh dance for a same-site SPA; new dependency | Medium |
| D. **Laravel Sanctum SPA cookie auth** (session cookie + CSRF) | Laravel's recommended setup for a same-site SPA; already in `composer.json`; no tokens, no refresh endpoint, revocation = session delete; deleted/disabled admin is rejected on the next request | Session state in Redis; CSRF cookie round-trip; FE auth provider rewritten; Reverb channel auth must move to the session guard | Medium, mostly deletion |
| E. Passport / external IdP (Keycloak) | Full OAuth2 / OIDC | Far too heavy for one admin user | High |

> 🇻🇳 So sánh 5 phương án: A tự sửa (học sâu nhưng tốn nhiều tuần, vẫn là code tự bảo trì); B thay phần mật mã bằng `firebase/php-jwt` (bớt rủi ro mật mã nhưng các lỗi khác còn nguyên, bản đang pin có CVE); C dùng package JWT guard (vẫn phải refresh token cho một SPA cùng site); D **Sanctum SPA cookie** (cách Laravel khuyên dùng, đã có trong `composer.json`, không token, không refresh, thu hồi = xoá session); E Passport / Keycloak (quá nặng).

## 6. Solution

Option D, recorded as [ADR-0004](../adr/0004-sanctum-spa-cookie-auth.md). The hand-written design stays documented as a learning record ([AUTH-GUIDE](../archive/learning/AUTH-GUIDE.md)), so the knowledge is kept even though the code goes.

> 🇻🇳 Chọn phương án D, ghi trong ADR-0004. Thiết kế tự viết được giữ lại dưới dạng tài liệu học tập (AUTH-GUIDE) — kiến thức còn, code thì xoá.

## 7. Implementation and verification

To be filled in by backlog P2-11 (RFC-001 slice 7). Planned checks:

- New feature tests: login success / wrong password / throttled, `me` 200 and 401, logout, protected route 401 without a session, 403 for a disabled admin, CSRF rejected without the `X-XSRF-TOKEN` header.
- The `AUTH_TODO` exclusion is removed from the `Makefile` and CI; `make verify` green.
- Manual check through nginx: log in, reload (session survives), log out in one tab (other tab is sent to login on its next request).

> 🇻🇳 Phần này điền khi làm P2-11. Kiểm tra dự kiến: test mới cho login/me/logout/401/403/CSRF; bỏ loại trừ `AUTH_TODO`; `make verify` xanh; thử tay qua nginx.

## 8. New problems that appeared

- Login still loads the Redis permission table from `admin_permission_view`. Slice 7 keeps permission checks working; slice 8 (P2-12, roles `owner` / `viewer`) replaces them with Gates/Policies and drops `token_mst` and the RBAC tables.
- The API still broadcasts media upload progress over Reverb, but since RFC-001 slice 6 no screen listens. Channel auth only needs to keep working, not to be redesigned.

> 🇻🇳 Vấn đề phát sinh: login vẫn nạp bảng quyền từ `admin_permission_view` — slice 7 giữ kiểm tra quyền, slice 8 thay bằng Gate/Policy và xoá `token_mst` cùng các bảng RBAC. API vẫn broadcast tiến độ upload qua Reverb nhưng không còn màn hình nào nghe; chỉ cần giữ cho channel auth chạy được.

## 9. Follow-up improvements

- Login throttling with Laravel's `RateLimiter` (time-limited, same message for every failure) — part of P2-11.
- Login audit events (who, IP, when) go into the `audit_log` of P2-08.
- P8 security review checks session cookie flags (`Secure`, `HttpOnly`, `SameSite`) against OWASP ASVS chapter 3.

> 🇻🇳 Cải tiến tiếp theo: giới hạn đăng nhập bằng `RateLimiter` (P2-11); ghi sự kiện đăng nhập vào `audit_log` (P2-08); P8 kiểm tra cờ cookie session theo OWASP ASVS chương 3.

## 10. Lessons learned

Build it by hand to learn it, then replace it with the standard tool once you understand what the tool does for you. The learning lives in the write-up, not in the production code.

> 🇻🇳 Tự viết để hiểu, rồi thay bằng công cụ chuẩn khi đã hiểu công cụ làm gì cho mình. Kiến thức nằm trong tài liệu, không cần nằm trong code production.
