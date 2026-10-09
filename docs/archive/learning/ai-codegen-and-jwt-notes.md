> **Learning note (archived 2026-10-09, HK-01; was `laravel-api/docs/synthetic.md`).** Notes on generating CRUD modules with AI prompts from schema metadata, and a review of the hand-written JWT login. The generator scripts no longer exist; auth is Sanctum ([ADR-0004](../../adr/0004-sanctum-spa-cookie-auth.md)); AI-assisted work now follows the [handbook](../../handbook/README.md) and `.claude/`. Kept unchanged otherwise.
>
> 🇻🇳 **Ghi chú học tập, đã lưu trữ (2026-10-09).** Ghi chú sinh module CRUD bằng prompt AI từ metadata schema và nhận xét về login JWT tự viết. Script generator không còn; auth đã là Sanctum (ADR-0004); làm việc với AI theo handbook và `.claude/`. Nội dung còn lại giữ nguyên.

- CodingConvention.md (chuẩn kiến trúc + naming + convention rõ ràng)
- Agent (mô tả mục tiêu, rule, behavior, workflow)
- Prompt mẫu (cách bạn feed input: scope, table, schema, FK, status)
- Một vài module mẫu đã hoàn chỉnh (để AI và Copilot “học” cách triển khai thực tế).
- Migration meta data dạng yaml/json. Có thể tạo từ migrate, file .sql, ERD,... mô tả table, column, fk, status.

**************************
Đề xuất Biểu Mẫu Prompt Chuẩn:

Persona: <Nhập vai trò kỹ thuật cụ thể> <Bạn là một Kỹ sư Backend cao cấp>

Context: <Đường dẫn tới coding convention, các tệp liên quan>

Task Breakdown (Chain-of-Thought): <Liệt kê các bước chi tiết để tạo module>

Schema Input: <JSON string của một bảng cụ thể>

Relationship Mapping: <Hướng dẫn rõ ràng về cách xử lý các foreign_keys, ví dụ: "Bảng 'A' có quan hệ một-một với bảng 'B' qua trường 'B_id', hãy nhúng đối tượng 'B' vào đối tượng 'A'.">

Constraints & Requirements: <Các yêu cầu cụ thể khác, ví dụ: xử lý lỗi, bảo mật, v.v.>

+++
- Trước mỗi lần enter prompt
+ Đính kèm codingConvention và metadata vào context
+ Chọn agent
+ paste propmt + enter
parse schema + convention → Generate prompt → Feed vào agent -> enter prompt

*************************************************************************************************************************************
GIAI ĐOẠN

+++ Giai đoạn 1 (Hiện tại): Triển khai quy trình tạo mã API CRUD. Tập trung vào việc xây dựng nền tảng vững chắc, bao gồm xác thực schema, tự động hóa prompt và tích hợp kiểm tra chất lượng cơ bản.

Giai đoạn 2 (Trung hạn): Mở rộng quy trình để tự động hóa việc tạo ORM (Object-Relational Mapping) và các migration files dựa trên schema. Điều này sẽ giúp đồng bộ hóa giữa lược đồ cơ sở dữ liệu và mã nguồn ứng dụng một cách tự động.   

Giai đoạn 3 (Dài hạn): Tích hợp AI sâu hơn vào quy trình, không chỉ để tạo mã mà còn để viết tài liệu , tái cấu trúc mã  và tự động gỡ lỗi. Mục tiêu là biến AI trở thành một thành viên tích cực của đội ngũ phát triển, không chỉ là một công cụ tạo mã đơn thuần.   

**********************************************************************************************************************
- Hiện tại, t mở rộng quy trình tự động hóa ORM. KHi có sự thay đổi database và muốn nhanh chóng apply cho toàn hệ thống: collect DDL -> convert json object (Run shell script file: auto_script/dataSchema/convert_ddl_to_json.py trong container docker) -> run shell script file auto_script/run_generator.sh (file này sẽ run các shell script trong folder auto_gen chứa các shell script tự động generate các module theo json object)

**************************************************************
- Bộ 3: ERD schema, DDL, json schema
    + ERD và DDL có thể sử dụng đồng thời ở dbeaver
    + Convert DDL -> json schema (để apply các tính năng mapping trong code):
        cài đặt package python simple-ddl-parser để convert, coding file, đọc nội dung file DDL, convert qua json schema
**************************************************************

tôi đang sử dụng laravel 11 làm api, hiện tại tôi đã có 1 middleware để generate response api. Tôi cần làm gì để setting api bảo mật, Như: http only, sercure, cookie, same-site, allow method, allow header, allow origin, type authorzation beaver, content type application json, content-length, host, connection,... hãy nghiên cứu phân tích và hướng dẫn tôi thực hiện bảo mật cho api của mình thật chuẩn và tối ưu.
tôi muốn set thủ công tất cả các thành phần để hiểu chúng hơn và dễ tùy chỉnh

*************************************************************
LOGIN APPLY JWT TOKEN
### Trả lời các câu hỏi cụ thể

#### 1. Vì mỗi lần gửi request đều gửi cả 2 token, vậy có nên cấp lại token khi access token hết hạn không? Hay cần return error về cho user để user call api để refresh lại token?

Trong thiết kế của bạn, việc gửi cả access token và refresh token trong mỗi request (thường qua header) là một cách tiếp cận, nhưng nó không phải là chuẩn mực nhất vì refresh token nên được sử dụng một cách hạn chế để tránh rò rỉ (nó có TTL dài hơn, như 7 ngày). Thay vào đó, mô hình chuẩn là:

- Client chỉ gửi access token trong header cho các request thông thường.
- Khi access token hết hạn (server trả về lỗi 401 Unauthorized), client sẽ tự động gọi một API riêng biệt (/refresh-token) để refresh, gửi refresh token lên (thường qua body hoặc header riêng).
- Server verify refresh token, cấp access token mới (và có thể cấp refresh token mới nếu cần rotation), rồi client dùng access token mới cho các request tiếp theo.

Lý do không nên tự động cấp lại token khi access token hết hạn trong request thông thường:
- **Bảo mật**: Nếu bạn tự động refresh trong bất kỳ request nào, kẻ tấn công có thể khai thác refresh token dễ dàng hơn vì nó được gửi thường xuyên.
- **Hiệu suất**: Không phải request nào cũng cần refresh; chỉ refresh khi cần thiết để giảm tải server.
- **Tiêu chuẩn**: Các thư viện như OAuth2 khuyến nghị cách này để client xử lý lỗi 401 và tự refresh.

Giải pháp: Return error 401 khi access token hết hạn, và để client gọi API refresh riêng. Nếu bạn muốn giữ việc gửi cả hai token, hãy chỉ sử dụng refresh token khi access token invalid (nhưng vẫn khuyến nghị tách biệt).

#### 2. Khi tôi nghi ngờ 1 user nào đó có hành vi gian lận, tôi muốn thu hồi truy cập của user đó mà không ảnh hưởng tới user khác thì làm sao?

JWT là stateless, nên không thể thu hồi token ngay lập tức mà không có cơ chế bổ sung. Trong thiết kế của bạn, bạn đã có blacklist cho access token (và có thể cho refresh token), đây là cách tốt để revoke.

Cách thực hiện revoke cho một user cụ thể:
- **Blacklist toàn bộ token của user**: Khi phát hiện gian lận, query tất cả các session/active token liên quan đến user đó (nếu bạn lưu trữ chúng ở DB/Redis, ví dụ: lưu user_id -> list of active tokens khi cấp token). Sau đó, thêm tất cả vào blacklist (Redis set hoặc list với TTL bằng TTL của token).
- **Revoke per device/session**: Nếu bạn muốn revoke chỉ một thiết bị, lưu session_id hoặc device_id trong JWT payload, và blacklist theo session_id thay vì toàn user.
- **Cập nhật permission cache**: Xóa tất cả permission cache liên quan đến user (keyed by access_token hoặc user_id).
- **Không ảnh hưởng user khác**: Vì blacklist và cache là per-token hoặc per-user, user khác không bị ảnh hưởng miễn là bạn không blacklist global.

Lưu ý: Để revoke hiệu quả, cần lưu trữ active sessions (ví dụ: khi login, lưu user_id -> device_info -> tokens ở Redis). Khi revoke, xóa session và thêm tokens vào blacklist. Nếu không lưu session, bạn chỉ có thể chờ token hết hạn tự nhiên (không lý tưởng cho gian lận).

#### 3. Các vấn đề về JWT token và cách giải quyết nó?

JWT có nhiều ưu điểm (stateless, dễ scale), nhưng cũng có vấn đề. Dưới đây là các vấn đề phổ biến và giải pháp:

- **Vấn đề 1: Không thể revoke ngay lập tức (stateless)**  
  Giải pháp: Sử dụng blacklist như bạn đang làm (Redis để lưu invalid tokens với TTL). Hoặc dùng reference token (lưu token ở DB và check mỗi request, nhưng mất stateless).

- **Vấn đề 2: Token bị đánh cắp (replay attack)**  
  Giải pháp: Sử dụng HTTPS everywhere. Thêm nonce hoặc jti (JWT ID) unique vào payload và check duplicate. Blacklist khi phát hiện. Rotate refresh token (cấp refresh mới mỗi lần refresh).

- **Vấn đề 3: Token quá lớn (payload chứa nhiều data)**  
  Giải pháp: Giữ payload nhỏ (chỉ user_id, roles, exp). Lưu permission ở cache/server thay vì trong token.

- **Vấn đề 4: Clock skew (đồng bộ thời gian server-client)**  
  Giải pháp: Sử dụng iat (issued at) và nbf (not before) với grace period (ví dụ: cho phép 5 giây lệch).

- **Vấn đề 5: Algorithm downgrade attack**  
  Giải pháp: Chỉ dùng HS256/RS256, không cho client chọn alg. Validate alg trước khi verify.

- **Vấn đề 6: Refresh token bị lạm dụng**  
  Giải pháp: Lưu refresh token ở DB (one-time use hoặc rotate). Giới hạn số lần refresh. Blacklist như access token.

- **Vấn đề 7: Multi-device support**  
  Giải pháp: Cache per access_token như bạn, để mỗi device độc lập.

- **Vấn đề 8: CSRF/XSS khi lưu cookie**  
  Giải pháp: Set HttpOnly, Secure, SameSite=Strict cho cookie. Không lưu token ở localStorage (dễ XSS).

#### 4. Tôi đang cache permission theo access_token, giả sử người dùng đó dùng trên nhiều thiết bị thì logic xóa cache permission của access_token đó sẽ không ảnh hưởng tới các token khác. Mặc dù login cùng account nhưng sử dụng trên nhiều thiết bị. Mỗi thiết bị có thể có thời điểm truy cập khác nhau, login, refresh token, logout, timeout,... như vậy có được không?

Có, cách này hoàn toàn khả thi và là best practice cho multi-device. Lý do:
- **Độc lập per session/device**: Mỗi login trên device khác nhau sẽ cấp access_token riêng (và refresh_token riêng). Cache key là access_token, nên xóa cache của một token chỉ ảnh hưởng device đó, không ảnh hưởng các device khác của cùng user.
- **Lợi ích**: Hỗ trợ revoke per device (ví dụ: logout từ một thiết bị không logout các thiết bị khác). Permission có thể được refresh độc lập dựa trên thời gian truy cập.
- **Rủi ro tiềm ẩn**: Nếu permission thay đổi (ví dụ: admin revoke role của user), cache per token có thể không đồng bộ ngay. Giải pháp: Khi permission thay đổi, xóa tất cả cache liên quan đến user_id (scan Redis keys hoặc lưu thêm set user_id -> list of active tokens).
- **Hiệu suất**: Redis nhanh, nhưng nếu user có nhiều device, cần quản lý key tốt (sử dụng pattern như "perm:{access_token}").
- **TTL**: Set TTL cache = TTL access_token để tự động expire.

Tổng thể, cách này tốt cho scalability và user experience, miễn là bạn xử lý đồng bộ permission khi có thay đổi global.

### Đánh giá tổng thể cách áp dụng JWT token vào giải quyết bài toán login API

Cách bạn thiết kế là **đúng về cơ bản** và tuân thủ các nguyên tắc JWT + refresh token + cache permission. Nó giải quyết tốt vấn đề authentication, authorization, và multi-device. Ưu điểm:
- Sử dụng hash password và limit_access để chống brute-force.
- Cache permission ở Redis để giảm query DB.
- Blacklist để revoke token.
- Multi-device độc lập qua cache per token.
- Rotate token khi refresh.

Tuy nhiên, cần **sửa chữa/bổ sung** một số điểm để an toàn và hiệu quả hơn:
- **Sửa: Không gửi cả hai token trong mọi request**. Chỉ gửi access token. Khi hết hạn, client gọi /refresh với refresh token. Điều này giảm rủi ro lộ refresh token.
- **Sửa: Lưu refresh token ở DB hoặc rotate**. Để tránh reuse, lưu refresh ở DB (one-use) hoặc cấp mới mỗi refresh, invalidate cũ.
- **Bổ sung: Lưu active sessions**. Để revoke dễ dàng (user_id -> devices -> tokens). Giúp xử lý gian lận.
- **Bổ sung: Permission check chi tiết hơn**. Không chỉ group by method (GET/POST/...), mà check exact route + method. Sử dụng RBAC (Role-Based Access Control) nếu có.
- **Bổ sung: Error handling**. Throw lỗi cụ thể (401 cho unauthorized, 403 cho forbidden). Log errors cho audit.
- **Bổ sung: Token payload**. Thêm user_id, session_id, roles vào access token để verify nhanh mà không query DB.
- **Lưu ý**: 
  - Sử dụng secret key mạnh, rotate định kỳ.
  - Validate tất cả input (username, password) chống SQL injection.
  - Test với tools như jwt.io để debug.
  - Scale: Redis cluster nếu traffic cao.
  - Compliance: Nếu dữ liệu nhạy cảm, tuân thủ GDPR (cho phép revoke all sessions).

Các thắc mắc của bạn đã được giải quyết ở trên. Nếu áp dụng các bổ sung, hệ thống sẽ vững chắc hơn.

### Tài liệu tổng hợp: Logic hệ thống Authentication với JWT

Dưới đây là bộ tài liệu tổng hợp tất cả logic bạn mô tả, kết hợp với các sửa chữa/bổ sung/gợi ý từ đánh giá. Tôi cấu trúc rõ ràng để dễ lưu trữ.

# 🔐 Authentication & Authorization Logic Specification

---

## 1. **Login Process**

### **Input**
- `username`, `password` (qua body request).

### **Backend Steps**

1. **Xác thực thông tin đăng nhập**
   - `SELECT * FROM admin WHERE username = ?`
   - Nếu không tồn tại → `401 Unauthorized: Invalid credentials`.
   - Nếu `limit_access >= 5` → `403 Forbidden: Account locked`.

2. **Kiểm tra mật khẩu**
   - So sánh `hash(password_input)` với `password_hash` trong DB.
   - Nếu **sai**:
     - Tăng `limit_access += 1`, lưu lại DB.
     - Trả lỗi `401 Unauthorized`.
   - Nếu **đúng**:
     - Reset `limit_access = 0`, lưu lại DB.

3. **Giới hạn số lượng đăng nhập đồng thời**
   - Đếm số lượng token đang tồn tại (từ cache và DB).
   - Nếu > 5 → `403 Forbidden: Maximum concurrent sessions reached`.

4. **Tạo JWT**
   - **Access Token**: `{ id: user_id, type: admin, iat: now(), exp: 5 mins }`
   - **Refresh Token**: `{ id: user_id, type: admin, iat: now(), exp: 3 days }`

5. **Tải quyền truy cập (Permissions)**
   - Query bảng `user_roles`, `role_permissions` để lấy danh sách routes user được phép truy cập.
   - Xử lý:
     - Loại bỏ route trùng (`unique`).
     - Gom nhóm theo HTTP method:
       ```json
       {
         "GET": ["route_1", "route_2"],
         "POST": ["route_3"],
         "DELETE": [...]
       }
       ```
   - Xóa cache cũ của user (nếu tồn tại).

6. **Lưu trữ token và permission**

   #### Access Token (cache)
   - Key: `perm:{type}:{user_id}:{access_token}`
   - Value: `{ last_access_at, ... }`
   - TTL: 5 phút
   - Do nhiều token của cùng user có cùng permission → lưu thêm key:
     - `perm:{type}:{user_id}:{permission}`
     - Value: `{ method: [uri list] }`
   - Mỗi khi có access token mới:
     - Kiểm tra không tồn tại thì tạo mới `perm:{type}:{user_id}:{permission}`.
     - Cập nhật TTL cho `perm:{type}:{user_id}` = TTL token mới nhất.
     - Token cũ hết hạn tự động xóa.
     - Token cuối cùng hết hạn → key parent `perm:{type}:{user_id}` cũng bị xóa.
   - ✅ **Ưu điểm**:
     - Nhiều token có cùng account dùng chung 1 permisson duy nhất. Tiết kiệm lưu trữ
     - Parent key có TTL = token mới nhất, đảm bảo tự động dọn dẹp khi hết hạn, làm sạch không gian lưu trữ
     - Các access token set TTL riêng, khi hết hạn tự dọn dẹp không ảnh hưởng token khác
     - Gom nhóm dữ liệu → giảm query → tăng tốc truy xuất → TTL đồng bộ.
     - Dễ thu hồi hoặc cập nhật permission.
     - Cho phép mỗi account login nhiều nơi.

   #### Refresh Token (database)
   - Trường lưu:
     - `token_hash`
     - `user_id`
     - `device_name`
     - `ip_address`
     - `expired_at`
   - Hash token trước khi lưu để tránh lộ dữ liệu khi DB bị xâm nhập.

7. **Set Cookie (Response Header)**
   - Tên: `refresh_token`
   - TTL: 3 ngày
   - `HttpOnly=true`, `Secure=true`, `SameSite=Strict`
   - `path='/auth/'` → Cho phép tự động đính kèm cho path này.

8. **Response**
   ```json
   {
     "data": { "access_token": "..." },
     "status": 200
   }
   ```

9. **Frontend**
   - Lưu `access_token` trong memory (RAM).
   - Mất khi reload hoặc đóng trang → cần gọi `/auth/refresh` để lấy lại token mới.
   - Cookie `refresh_token` được gửi tự động nếu cùng domain và đúng `path` như set-cookie trả về.

10. **DB**
    - Tạo cron job xóa refresh token có column token_mst.expired_at < now() định kỳ mỗi ngày
    - Chạy độc lập không ảnh hưởng transaction chính

---

## 2. **Request Validation (Mỗi request thông thường)**

### **Input**
- Header: `Authorization: Bearer <access_token>`

### **Backend Steps**
1. Kiểm tra access token tồn tại → nếu không → `401 Unauthorized`.
2. Giải mã JWT, kiểm tra chữ ký và `exp` → lỗi → `401 Unauthorized`.
3. Kiểm tra token có tồn tại trong cache (`perm:{type}:{user_id}:{access_token}`) → nếu không → `401 Unauthorized`.
4. Lấy `method` và `route` của request.
5. Kiểm tra quyền:
   - Lấy Redis key: `perm:{type}:{user_id}:{permission}:{method}`
   - Nếu route không có trong danh sách → `403 Forbidden`.
6. Cho phép tiếp tục xử lý request.

---

## 3. **Refresh Token Process**

### **Trigger**
Client gọi khi nhận `401 Unauthorized` từ request thông thường.

### **Input**
Refresh token (tự động đính kèm qua cookie).

### **Backend Steps**
1. Verify refresh token (decode, check signature, exp).
2. Nếu lỗi → `401 Unauthorized`.
3. Query permission mới (từ DB).
4. Xóa refresh token trong DB.
5. Sinh cặp token mới (access + refresh).
6. Lưu cache permission mới tương ứng với access token mới (TTL=5 phút).
7. Set cookie refresh token mới như khi login.
8. Response JSON như login.

---

## 4. **Logout Process**

### **Input**
- Cookie chứa refresh token  
- Header chứa access token

### **Backend Steps**
1. Verify cả hai token.
2. Xóa refresh token trong DB.
3. Xóa access token trong cache.
4. Set cookie `refresh_token` hết hạn (`max-age=0`).
5. Response:
   ```json
   { "success": true, "message": "Logged out" }
   ```

---

## 5. **Revoke for Fraud (Admin Action)**

- Truy vấn tất cả token đang hoạt động trong cache hoặc DB của user.
- Xóa tất cả token liên quan → thu hồi quyền truy cập ngay lập tức.

---

## 6. **Cache Strategy**

| Thành phần | Key format | Lưu ở đâu | TTL | Mục đích |
|-------------|-------------|------------|------|-----------|
| Permission | `perm:{type}:{user_id}:{access_token}` | Redis (hash) | = access_token TTL | Xác thực nhanh |
| Access Token | `perm:{type}:{user_id}` | Redis | 5 phút | Dễ thu hồi, sync TTL |
| Refresh Token | DB | Theo `exp` | Lưu lâu, ít truy cập |

---

#### **Security Notes**
- HTTPS only.
- Rate limit API để chống DDoS.
- Audit logs: Log mọi login/refresh/failed attempt.
- Test: Unit test verify, integration test flows.

#### **Update permission**
- Khi update permission, sẽ thực hiện select user id có các role vừa thay đổi permision. Tìm kiếm các user id đó trong cache permission và delete. Để user đó login or refresh token nhằm tạo
lại permission cache mới. Những dữ liệu cần có trong cache permisson:
---

## 🔍 Phân Tích Chi Tiết Kiến Trúc Cache & Token

### **Permission**
- **Cấu trúc:** Theo cấu trúc verify permission của user để tối ưu. Ví dụ hiện tại check permission theo route, nên phân loại theo method (GET, POST, PUT, DELETE) hoặc theo group path.
- **Lợi ích:** Khi truy vấn sẽ query theo loại, dữ liệu được gom nhóm, khiến lượng data ít hơn → tốc độ xử lý nhanh hơn.
- **Lý do lưu cache:** 
  - Thời lượng user truy cập ngắn, truy cập liên tục để verify mỗi request nên cần verify nhanh → cache hợp lý hơn DB.
  - Giảm truy cập READ DB, giúp phân tán xử lý, giảm latency và tải cho DB.
  - Permission cache theo access token phù hợp với mô hình 1 account đăng nhập nhiều thiết bị, mỗi thiết bị có thời gian truy cập/logout/refresh khác nhau.
- **Cấu trúc dữ liệu cần có trong cache permission:**  
  Key: `perm:{type}:{user_id}:{access_token}`  
  Value bao gồm: method, path, user_id (dùng khi update permission để select delete).

### **Access Token**
- **Mục đích:** Nhận dạng user trong mỗi request.
- **Đặc điểm:**
  - Tần suất verify lớn, thời gian truy cập ngắn.
  - Giảm thời gian hợp lệ để tăng bảo mật.
  - Tránh truy cập READ DB mỗi lần → phân tán xử lý, tăng tốc độ.
- **=>** Lưu trong cache là hợp lý nhất, không nên lưu trong DB.

### **Refresh Token**
- **Đặc điểm:**
  - Thời gian lưu trữ dài.
  - Tần suất sử dụng thấp (chỉ khi access token hết hạn).
  - Mỗi user login thời điểm khác nhau.
- **=>** Lưu trong DB hợp lý hơn cache (cache dùng cho dữ liệu nhỏ, truy xuất nhanh).

### **Lý do lưu trữ access token và refresh token**
- Tối ưu quản lý các user đang truy cập.
- Dễ thu hồi token riêng lẻ hoặc toàn bộ khi phát hiện gian lận.
- **So với blacklist:**
  - Blacklist phình to khi nhiều user refresh/logout → ảnh hưởng hiệu năng.
  - Blacklist cần lưu token invalid → tăng bộ nhớ.
  - Lưu trữ access + refresh token trực tiếp sẽ hiệu quả hơn:
    - Access token tự xóa nhanh (TTL ngắn).
    - Refresh token duy nhất mỗi user/device, TTL dài hơn → số lượng nhỏ hơn.
- **=>** Quản lý token hiệu quả, dễ revoke, ít phình bộ nhớ.

### **Xóa token**
- Khi refresh hoặc logout → xóa cả access & refresh token để tránh token cũ invalid vẫn sử dụng được.

### **Mô hình lưu cache**
#### 1️⃣ Gom nhóm Authen & Author
- Dùng 1 cache lưu access token + route name (kết quả query role → route → unique → group by method).  
**Ưu điểm:**
  - Phù hợp hệ thống vừa & nhỏ, xử lý nhanh, QPS cao.
  - Dữ liệu tập trung, ít query.
  - Đánh đổi merories -> lantecy
**Nhược điểm:**
  - Tốn bộ nhớ cache (RAM).
  - Dễ duplicate nếu nhiều user có role giống nhau.
  - Cập nhật permission phức tạp → thường phải delete và tạo lại.
  - Duplicate theo token.

#### 2️⃣ Phân chia Authen & Author
- Cache 1: access token + role.  
- Cache 2: role + route (tạo sẵn cho tất cả role).  
**Ưu điểm:**
  - Tối ưu bộ nhớ, dễ cập nhật permission.  
  - Tiết kiệm lưu trữ, giảm trùng lặp thay vì trùng lặp toàn bộ route khi 2 user có cùng role. Chỉ trùng lặp ít khi 2 role khác nhau có cơ số route giống nhau.  
**Nhược điểm:**
  - Tốn tài nguyên xử lý mỗi lần request do phải query nhiều cache.  
  - Cần process nhiều bước để gom & check permission (get all route -> unique -> group by method -> check route).

### **Lưu access token**
- Nếu hệ thống chỉ cần xác minh user (tất cả user đều có toàn quyền), ko có nhu cầu thu hồi token → có thể không lưu token như hệ thống website front-end sủ dụng.
- Nếu hệ thống phân quyền nhiều lớp → **nên lưu access token** để dễ thu hồi và quản lý bảo mật như hệ thống nội bộ công ty, tập đoàn, nhóm,...

### **Giải pháp nâng cao**
- **Distributed cache:** Redis cluster / Elasticache để phân tán bộ nhớ & scale ngang.
- **Eviction policies:** Dùng LRU hoặc LFU, TTL ngắn để tự dọn dẹp cache cũ.
- **Compression:** Chỉ lưu dữ liệu cần thiết.
- **Monitoring & Auto-scaling:** Giám sát cache hit rate, memory usage, latency.
- **Hybrid approach:** Xóa cache với user inactive lâu.
- **Giới hạn lưu trữ:** 
  - Giới hạn login đồng thời.
  - Giới hạn số refresh token per user/device.
  - Đặt dọn dẹp định kỳ, TTL ngắn cho dữ liệu tạm.

###########
- Cloudflare R2
- Supabase Storage free 1GB
- GitHub Releases / Git LFS
- gg drive api