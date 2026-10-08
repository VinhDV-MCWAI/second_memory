# ADR-0004 — Replace the hand-written JWT auth with Laravel Sanctum SPA cookie auth

> 🇻🇳 Thay xác thực JWT tự viết bằng Laravel Sanctum (SPA, cookie session).

| | |
|---|---|
| Status | Accepted |
| Date | 2026-10-07 |
| Deciders | TL (owner) |
| Related | [PRB-001](../problems/PRB-001-custom-jwt-auth.md), [RFC-001](../design/RFC-001-slim-down.md) slice 7, [REQ-001](../requirements/REQ-001-slim-down.md), backlog P2-10 / P2-11 |

## Context

PRB-001 describes the problem: the admin panel authenticates with a hand-written JWT pair (5-minute access token, 3-day rotating refresh token) stored in HttpOnly cookies, plus Redis lists of live tokens and per-admin permission tables. It was built to learn and has known defects, four of them excluded from CI. The only client is the admin SPA, served from the same site as the API through nginx; there are no mobile apps and no third-party API consumers. `laravel/sanctum` is already a dependency (`composer.json`, `config/sanctum.php`) but unused.

> 🇻🇳 Bối cảnh: theo PRB-001, admin đăng nhập bằng cặp JWT tự viết lưu trong cookie HttpOnly, kèm danh sách token và bảng quyền trong Redis; có lỗi đã biết, 4 test bị loại khỏi CI. Client duy nhất là SPA admin chạy cùng site với API qua nginx, không có app mobile hay bên thứ ba. `laravel/sanctum` đã được cài nhưng chưa dùng.

## Options

1. **Fix the hand-written JWT** (AUTH-GUIDE stages A–C). Keeps the design; weeks of work; custom security code forever.
2. **Keep the flow, use `firebase/php-jwt` for the crypto.** Smaller risk in token parsing; every flow defect (lockout, refresh, logout, 401/403) remains.
3. **Packaged JWT guard** (`php-open-source-saver/jwt-auth`). Standard guard, but still tokens and a refresh cycle the SPA does not need; new dependency.
4. **Sanctum SPA authentication.** Laravel's session guard behind Sanctum's `EnsureFrontendRequestsAreStateful`: the SPA calls `GET /sanctum/csrf-cookie`, then logs in; the browser keeps an encrypted, HttpOnly session cookie; writes carry the `X-XSRF-TOKEN` header. Sessions live in Redis. No token, no refresh endpoint.
5. **Passport or an external identity provider.** OAuth2/OIDC; far beyond the need.

> 🇻🇳 Các phương án: (1) tự sửa JWT; (2) giữ luồng, dùng `firebase/php-jwt`; (3) package JWT guard; (4) **Sanctum SPA**: SPA gọi `/sanctum/csrf-cookie` rồi đăng nhập, trình duyệt giữ cookie session HttpOnly đã mã hoá, request ghi gửi kèm header `X-XSRF-TOKEN`, session lưu trong Redis, không token, không refresh; (5) Passport / IdP ngoài — quá nặng.

## Decision

We choose **option 4, Sanctum SPA cookie authentication**, because it is the framework's recommended setup for exactly this shape (a first-party SPA on the same site), it is already installed, and it removes whole classes of code instead of fixing them: token crypto, refresh rotation, the refresh scheduler in the FE, and the token table.

> 🇻🇳 Chọn **phương án 4** vì đây là cách Laravel khuyến nghị cho đúng trường hợp này (SPA của chính mình, cùng site), đã được cài sẵn, và nó xoá hẳn cả nhóm code thay vì phải sửa: mật mã token, xoay refresh token, bộ hẹn giờ refresh ở FE, bảng token.

Rules for the implementation (P2-11):

- Guard `web` (session) through `auth:sanctum`; session driver `redis`; session cookie `HttpOnly`, `SameSite=Lax`, `Secure` from config (`SESSION_SECURE_COOKIE`), never from `APP_ENV`.
- Login regenerates the session ID; logout invalidates the session and regenerates the CSRF token, and is idempotent (it succeeds without a valid session).
- Login is throttled with Laravel's `RateLimiter` per user name + IP, with a time-limited lock and one message for every failure (no user enumeration).
- A disabled or deleted admin is rejected on the next request, not when a token expires.
- 401 = no valid session, 403 = signed in but not allowed. The FE only redirects to login on 401.
- The JSON envelope (`{ data, error }`) stays. Route paths may change; the FE and OpenAPI types change in the same commit.
- Until slice 8, route permissions keep working (loaded per admin, as today) so behavior does not widen; slice 8 replaces them with Gates/Policies for `owner` / `viewer`.

> 🇻🇳 Quy tắc khi triển khai (P2-11): guard session qua `auth:sanctum`, session lưu Redis, cookie HttpOnly + SameSite=Lax + Secure lấy từ config; login tạo lại session ID, logout huỷ session và luôn thành công; giới hạn đăng nhập bằng `RateLimiter` theo username + IP, khoá có thời hạn, một thông báo chung; admin bị khoá/xoá bị từ chối ngay request kế tiếp; 401 = chưa đăng nhập, 403 = không đủ quyền; giữ envelope; kiểm tra quyền theo route vẫn chạy cho tới slice 8.

## Consequences

- **Easier:** no token lifetimes to tune, no refresh race between tabs, revocation is "delete the session", `config:cache` works, the four excluded tests disappear with the code they tested.
- **Harder / different:** the SPA must fetch the CSRF cookie before login, and the API must list the admin's host in `SANCTUM_STATEFUL_DOMAINS`; a future non-browser client (e.g. the Go replay CLI in P9) would need Sanctum API tokens — a separate decision if it comes.
- **Must do next:**
  - Rewrite `nextjs-fe/src/providers/auth-provider.tsx` (no `expires_at`, no refresh timer; keep the `BroadcastChannel` logout sync) and drop the refresh interceptor in `src/shared/api/client`.
  - Move WebSocket channel auth (`/broadcasting/auth`) to the session guard and delete `BroadcastingAuthMiddleware`.
  - Delete `JsonWebToken`, `CredentialService` token code, the `ACCESS_TOKEN_SECRET` / `REFRESH_TOKEN_SECRET` settings and `firebase/php-jwt` (unused, pinned to a version with CVE-2025-45769).
  - Slice 8 drops `token_mst` (+ history), its CRUD and FE page together with the RBAC tables.
- **Learning record:** the hand-written design is kept as [docs/archive/learning/AUTH-GUIDE.md](../archive/learning/AUTH-GUIDE.md); code history is in tag `v1.0.0`.

> 🇻🇳 Hệ quả: **dễ hơn** — không phải chỉnh thời hạn token, không tranh chấp refresh giữa các tab, thu hồi = xoá session, `config:cache` chạy được, 4 test bị loại biến mất cùng code. **Khác đi** — SPA phải lấy cookie CSRF trước khi login, API phải khai báo host trong `SANCTUM_STATEFUL_DOMAINS`; client không phải trình duyệt sau này (CLI Go ở P9) sẽ cần Sanctum API token — quyết định riêng khi tới. **Việc tiếp theo** — viết lại auth provider FE, bỏ interceptor refresh, chuyển xác thực kênh WebSocket sang session, xoá code JWT, secret và `firebase/php-jwt`; slice 8 xoá `token_mst` cùng các bảng RBAC. Thiết kế tự viết được lưu lại làm tài liệu học tập.
