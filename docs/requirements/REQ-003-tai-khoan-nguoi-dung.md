# REQ-003 — Tài khoản người dùng trên trang public

| | |
|---|---|
| Trạng thái | **Đang làm rõ**: chờ chủ dự án trả lời mục 3 (2026-10-17) |
| Người yêu cầu | Chủ dự án, 2026-10-17 |
| Độ ưu tiên | Cao: việc tiếp theo của trang cho người dùng ([ADR-0019](../adr/0019-public-user-frontend.md)) |
| Cỡ | Lớn: thêm bảng, đăng nhập mới, API mới, màn hình mới. Cần ADR sau khi chốt câu trả lời |
| Việc trên bảng | PUB-03 (tài khoản), PUB-04 (lịch sử, yêu thích, xem sau) |

Đây là bài luyện **vòng đời đầy đủ** đầu tiên ([ADR-0018](../adr/0018-lifecycle-practice-track.md)): yêu cầu → làm rõ → phân tích → thiết kế → làm → kế hoạch test → QA → phát hành → bàn giao → nhìn lại.

## 1. Vấn đề và mục tiêu

Hôm nay ai cũng xem được trang public (`/skills`) nhưng không lưu được gì. Người xem thường quay lại không nhớ đã xem gì, không đánh dấu được thứ mình thích hay muốn đọc lại.

**Mục tiêu:** người xem **có thể** (không bắt buộc) tạo tài khoản để:
- xem lại lịch sử đã xem;
- đánh dấu yêu thích;
- lưu để xem sau.

Mọi thứ phải **cực kỳ đơn giản**. Trang vẫn xem được đầy đủ khi không đăng nhập.

**Đo thế nào là xong:** một người mới đăng ký, đăng nhập, yêu thích một kỹ năng, lưu một bằng chứng để xem sau, rồi mở trang "Của tôi" thấy đủ ba danh sách, trong dưới 2 phút, không cần hướng dẫn.

## 2. Phạm vi

| Trong phạm vi | Ngoài phạm vi (để sau) |
|---|---|
| Đăng ký, đăng nhập, đăng xuất, đổi mật khẩu, tự xóa tài khoản | Đăng nhập bằng Google / GitHub |
| Lịch sử đã xem, yêu thích, xem sau | Bình luận, đánh giá, chia sẻ, thông báo |
| Trang "Của tôi" có ba danh sách | Nội dung chỉ người đăng nhập mới xem được |
| Trang quản trị: xem danh sách người dùng, khóa / mở khóa, xóa | Phân quyền cho người dùng cuối, nhóm người dùng |
| | Gửi email (xác thực, quên mật khẩu): cần máy chủ mail, xem câu hỏi 2 |

## 3. Câu hỏi làm rõ

Mỗi câu có phương án Claude khuyên chọn (in đậm). Chủ dự án trả lời "đồng ý hết" cũng được.

| # | Câu hỏi | Phương án | Khuyên | Lý do |
|---|---|---|---|---|
| 1 | Ai tạo tài khoản? | (a) Người xem tự đăng ký · (b) Chỉ admin tạo · (c) Tự đăng ký nhưng admin bật / tắt được việc đăng ký | **(c)** | Đúng ý "người dùng tự dùng", vẫn tắt được ngay khi bị spam |
| 2 | Có xác thực email, quên mật khẩu qua email không? | (a) Có ngay: thêm máy chủ mail (Mailpit trên dev) · (b) Chưa: quên mật khẩu thì admin đặt lại trong trang quản trị | **(b)** | Bớt một container và một luồng; làm sau khi có production |
| 3 | Lịch sử đã xem ghi những gì, giữ bao lâu? | Ghi mỗi lần mở trang một kỹ năng; giữ **100 mục gần nhất**, mở lại thì đưa lên đầu; người dùng tự xóa được | **Như bên trái** | Bằng chứng không có trang riêng nên chỉ ghi kỹ năng |
| 4 | Yêu thích và xem sau áp dụng cho gì? | (a) Chỉ kỹ năng · (b) **Kỹ năng và bằng chứng** | **(b)** | Bằng chứng (chứng chỉ, PDF) là thứ hay muốn đọc lại |
| 5 | Chưa đăng nhập thì có lưu tạm trong trình duyệt không? | (a) Có · (b) Không: nút yêu thích mời đăng nhập | **(b)** | Đơn giản, không phải gộp dữ liệu khi đăng nhập |
| 6 | Đường dẫn các trang tài khoản? | (a) Dưới `/skills`: `/skills/account/login`, `/skills/me` · (b) Đổi cả trang public sang tiền tố chung, ví dụ `/p/...` | **(a)** | Không đổi link đang có; đổi tiền tố để sau, khi trang public có thêm nội dung khác ngoài kỹ năng |
| 7 | Người dùng tự xóa tài khoản thì sao? | Xóa ngay tài khoản và toàn bộ lịch sử, yêu thích, xem sau; không giữ bản sao | **Như bên trái** | Tôn trọng dữ liệu cá nhân, đơn giản |
| 8 | Đăng nhập người dùng và admin có liên quan không? | **Tách hẳn**: bảng riêng, bộ đăng nhập riêng; tài khoản người dùng không bao giờ gọi được API quản trị | **Như bên trái** | An toàn: lỗi bên người dùng không mở được cửa quản trị |

## 4. Câu chuyện người dùng và tiêu chí chấp nhận

```text
Là người xem, tôi muốn tạo tài khoản bằng email và mật khẩu, để lưu những gì tôi quan tâm.

Kịch bản: đăng ký
  Biết rằng việc đăng ký đang bật và email chưa dùng
  Khi tôi điền email, tên hiển thị, mật khẩu (ít nhất 8 ký tự) và bấm Đăng ký
  Thì tôi được đăng nhập ngay và về trang tôi đang xem

Kịch bản: đăng ký bị tắt
  Biết rằng admin đã tắt đăng ký
  Khi tôi mở trang đăng ký
  Thì tôi thấy "Đăng ký đang tạm đóng" và API trả 403

Kịch bản: tài khoản bị khóa
  Biết rằng admin đã khóa tài khoản của tôi
  Khi tôi đăng nhập
  Thì đăng nhập thất bại với cùng thông báo như sai mật khẩu, và mọi phiên đang mở của tôi bị đăng xuất
```

```text
Là người dùng đã đăng nhập, tôi muốn yêu thích và lưu xem sau một kỹ năng hoặc bằng chứng, để tìm lại nhanh.

Kịch bản: yêu thích
  Biết rằng tôi đang xem trang một kỹ năng công khai
  Khi tôi bấm ☆ cạnh tên kỹ năng
  Thì nút thành ★ không cần tải lại trang, và kỹ năng hiện trong "Yêu thích" ở trang Của tôi

Kịch bản: thứ đã lưu bị ẩn
  Biết rằng tôi đã lưu một bằng chứng, rồi admin tắt công khai bằng chứng đó
  Khi tôi mở trang Của tôi
  Thì bằng chứng đó không hiện, và không lộ tiêu đề của nó

Kịch bản: chưa đăng nhập
  Khi tôi bấm ☆ mà chưa đăng nhập
  Thì tôi được mời đăng nhập, xong thì quay lại đúng trang đó
```

```text
Là người dùng đã đăng nhập, tôi muốn xem lại các kỹ năng đã mở gần đây.

Kịch bản: lịch sử
  Khi tôi mở trang kỹ năng A rồi B rồi A
  Thì "Đã xem" ở trang Của tôi có A trên cùng, rồi B, không trùng
```

```text
Là admin, tôi muốn xem và khóa tài khoản người dùng, để xử lý tài khoản spam.

Kịch bản: khóa
  Biết rằng tôi có quyền end-user.update
  Khi tôi khóa một người dùng trong màn hình Người dùng
  Thì người đó bị đăng xuất ngay, và việc khóa được ghi vào nhật ký thay đổi
```

## 5. Yêu cầu phi chức năng

| Mặt | Yêu cầu |
|---|---|
| Nhẹ | Trang đọc vẫn không có JavaScript khi chưa đăng nhập. Nút yêu thích / xem sau dùng một đoạn script nhỏ của Astro, không thêm React. Dưới 10 KB JS |
| Lưu đệm | Trang công khai vẫn giữ lưu đệm 60 giây. Trạng thái ★ của từng người lấy riêng qua API, không nằm trong trang được lưu đệm |
| Bảo mật | Mật khẩu băm (bcrypt như admin); giới hạn đăng nhập sai theo email + IP và theo IP; giới hạn đăng ký theo IP; cookie phiên chung tên miền, CSRF như trang quản trị |
| Tách biệt | Bảng `end_user` riêng, bộ đăng nhập riêng (`user`), API riêng `/api/user/*`. Không có quyền nào của trang quản trị áp dụng cho người dùng |
| Dữ liệu cá nhân | Chỉ lưu email, tên hiển thị, mật khẩu băm, thời gian. Xóa tài khoản là xóa hết |
| Nhật ký | Việc của admin trên tài khoản người dùng (khóa, xóa, đặt lại mật khẩu) ghi vào nhật ký thay đổi. Việc yêu thích, xem sau, lịch sử của người dùng thì không ghi (quá nhiều, không cần) |

## 6. Phân tích ảnh hưởng

| Phần | Thay đổi |
|---|---|
| DB (thêm, không sửa bảng cũ) | `end_user` (email duy nhất, tên hiển thị, mật khẩu, đang dùng, lần đăng nhập cuối); `end_user_view` (người dùng, kỹ năng, lúc xem); `end_user_saved` (người dùng, loại: yêu thích / xem sau, đối tượng: kỹ năng / bằng chứng, mã) |
| Cài đặt | Một cờ "cho phép đăng ký" (bảng cài đặt nhỏ hoặc biến môi trường, chốt ở ADR) |
| API người dùng (mới) | `user/register`, `login`, `logout`, `me`, `password`, `delete-account`; `user/history` (xem, xóa); `user/saved` (thêm, bỏ, danh sách). Ghi lịch sử khi gọi `public/skills/{slug}` có kèm phiên người dùng |
| API quản trị (mới) | `admin/end-user/list`, `update` (khóa / mở, đặt lại mật khẩu), `delete`; mỗi API là một quyền; vai trò Chỉ xem được `list` |
| Trang public | `/skills/account/login`, `/skills/account/register`, `/skills/me` (3 thẻ); nút ☆ và 🔖 trên trang kỹ năng và cạnh từng bằng chứng; góc trên: "Đăng nhập" hoặc tên người dùng |
| Trang quản trị | Menu Master Data → **End users**: bảng, tìm, khóa, xóa, đặt lại mật khẩu; ô bật / tắt đăng ký |
| Bằng chứng | API public cần trả thêm một mã ổn định cho từng bằng chứng (hôm nay không có, để không lộ id). Đề xuất: mã ngẫu nhiên như mã file đính kèm |
| Test | Test backend cho mọi API mới (cả các trường hợp bị khóa, thứ đã ẩn, đăng ký bị tắt); test trình duyệt: đăng ký → yêu thích → Của tôi; admin khóa |
| Tài liệu | File tính năng mới `docs/features/nguoi-dung-cuoi.md`; sửa `trang-public.md`, `dang-nhap.md`; ADR-0020 cho thiết kế đăng nhập người dùng |
| Rủi ro | Đụng tới đăng nhập (đang tạm dừng, nay chủ dự án yêu cầu theo ADR-0019). Lỗi cấu hình bộ đăng nhập có thể làm người dùng gọi được API admin → có test riêng chứng minh không gọi được |

## 7. Phương án đăng nhập

| | **A. Phiên cookie Sanctum, bộ đăng nhập riêng `user` (khuyên)** | B. Token lưu trong trình duyệt | C. Đăng nhập bằng Google |
|---|---|---|---|
| Cách làm | Giống trang quản trị: cookie phiên, CSRF; bộ đăng nhập `user` dùng bảng `end_user` | Token trong `localStorage`, gửi theo header | Chuyển sang Google, nhận lại tài khoản |
| Ưu | Dùng lại đúng cơ chế đang chạy và đã có test; chống lộ token qua script | Không phụ thuộc cookie | Không cần mật khẩu |
| Nhược | Cần cẩn thận để hai bộ đăng nhập không lẫn nhau | Token dễ bị script lấy trộm; khác hẳn phần còn lại | Cần đăng ký ứng dụng với Google, cần tên miền thật; không chạy được trên dev thuần |

## 8. Sẵn sàng để làm khi

- [x] Mục tiêu và ngoài phạm vi đã ghi
- [x] Tiêu chí chấp nhận kiểm tra được
- [ ] Chủ dự án trả lời 8 câu ở mục 3 và chọn phương án ở mục 7
- [x] Phụ thuộc đã có: trang public Astro (PUB-02)
- [ ] Viết ADR-0020 theo câu trả lời

## 9. Ước tính và chia việc

| Việc | Gồm | Ước tính |
|---|---|---|
| PUB-03 | ADR-0020; bảng, bộ đăng nhập `user`, API đăng ký / đăng nhập / đổi mật khẩu / xóa tài khoản; trang đăng nhập, đăng ký; test | 2 phiên |
| PUB-04 | Lịch sử, yêu thích, xem sau (API + nút + trang Của tôi); màn hình End users trong trang quản trị; test trình duyệt; kế hoạch test và QA; ghi chú phát hành, bàn giao, nhìn lại | 2–3 phiên |
