# ADR-0019 — Trang cho người dùng: thay `nextjs-docs` bằng công nghệ nhẹ hơn

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-17, chủ dự án chọn phương án C: Astro; đồng ý chuyển hướng `/docs` → `/skills`; người dùng xem kỹ năng, bằng chứng và file đính kèm như hiện tại) |
| Ngày | 2026-10-17 |
| Người quyết định | Chủ dự án |
| Liên quan | [ADR-0015](0015-defer-end-users-departments.md) (người dùng cuối để sau), [ADR-0018](0018-lifecycle-practice-track.md) (làm theo vòng đời đầy đủ), [trang public](../features/trang-public.md) |

## Bối cảnh

Dự án có hai ứng dụng giao diện:

| | `nextjs-fe` (trang quản trị) | `nextjs-docs` (trang public) |
|---|---|---|
| Dành cho | Admin, các phòng ban | Người xem, không cần đăng nhập |
| Code | ~16,8 nghìn dòng | **408 dòng**: danh sách kỹ năng, chi tiết kỹ năng, link mở file, trang "nội dung đã chuyển" cho link `/docs` cũ |
| RAM khi chạy dev (đo 2026-10-17) | 944 MB | **499 MB** |
| Image production (đo 2026-10-09) | 307 MB | 300 MB |
| JavaScript gửi xuống trình duyệt | Nhiều (trang tương tác) | Vẫn tải bộ chạy React dù trang chỉ để đọc |

Ý định ban đầu của chủ dự án về trang cho người dùng:
- ai cũng xem được thông tin công khai mà không cần đăng nhập;
- người dùng **có thể** đăng nhập để dùng vài thứ rất đơn giản: lịch sử đã xem, yêu thích, lưu xem sau;
- mục tiêu duy nhất là xem cho tiện.

Next.js quá nặng cho việc chỉ để đọc: một container 500 MB RAM ở dev để phục vụ 408 dòng code. Nội dung CMS cũ của `nextjs-docs` cũng không còn ý nghĩa.

## Các phương án

| | A. Gộp vào `nextjs-fe` | B. Giữ Next.js riêng, chỉ dọn | **C. Astro (khuyên)** | D. Laravel Blade + Alpine.js | E. Vite + React, file tĩnh |
|---|---|---|---|---|---|
| Cách làm | Thêm nhóm trang public vào trang quản trị | Bỏ `/docs`, giữ nguyên còn lại | Ứng dụng riêng, HTML render ở server, React chỉ cho vài nút (yêu thích, xem sau) | Trang render ngay trong `laravel-api`, không còn ứng dụng giao diện thứ hai | Trang chạy hoàn toàn trong trình duyệt, nginx phục vụ file tĩnh |
| Container thêm | Bớt 1 | Giữ 1 | Giữ 1, nhẹ hơn | **Bớt 1** | Bớt 1 (chỉ cần nginx) |
| RAM dev (ước tính, đo lại khi thử) | 0 thêm, nhưng trang quản trị nặng thêm | ~500 MB | ~150–250 MB | 0 thêm | 0 lúc chạy |
| JS xuống trình duyệt | Nhiều | Bộ chạy React | **Gần 0** cho trang đọc; chỉ các nút tương tác mới có | Rất ít | Toàn bộ ứng dụng |
| Google tìm thấy trang (SEO) | Được | Được | **Tốt** | Tốt | Kém (trang trống tới khi JS chạy) |
| Dùng lại kiến thức đang có | React, Tailwind, kiểu từ OpenAPI | Như hiện tại | **TypeScript, Tailwind, React cho nút tương tác, kiểu từ OpenAPI** | PHP, Laravel | React, Tailwind |
| Tách biệt admin / người dùng | Kém: chung một ứng dụng, chung bản phát hành | Tốt | **Tốt** | Trung bình: API và trang chung một dự án | Tốt |
| Thêm thư viện | Không | Không | `astro` + adapter Node | Alpine.js (nhỏ) | Vite (đã có trong hệ sinh thái) |
| Công làm lại phần hiện có | ~1 ngày | ~2 giờ | ~1–2 ngày | ~1–2 ngày | ~1–2 ngày |
| Rủi ro chính | Trang quản trị phình to; lỗi admin ảnh hưởng người xem | Không giải quyết chuyện nặng | Thêm một công nghệ mới phải học | `laravel-api` không còn là API thuần, tách ra sau sẽ khó | SEO kém, lần mở đầu chậm |

### Vì sao khuyên phương án C (Astro)

- **Sinh ra cho trang chủ yếu để đọc.** Trang trả về HTML sẵn, không gửi bộ chạy React xuống trình duyệt. Chỉ các "đảo" tương tác (nút yêu thích, lưu xem sau) mới tải JS, viết bằng React như trang quản trị.
- **Nhẹ hơn hẳn Next.js** cả khi dev lẫn khi chạy thật, mà vẫn là ứng dụng riêng: người dùng và admin tách biệt, đúng ý định ban đầu.
- **Dùng lại gần hết kiến thức đang có:** TypeScript, Tailwind 4, kiểu dữ liệu sinh từ `openapi.json`, gọi cùng API qua nginx.
- **Đăng nhập người dùng cuối vẫn đơn giản:** cùng tên miền (nginx :81), dùng cookie phiên của Laravel giống trang quản trị, với một bộ đăng nhập riêng cho người dùng.

Nếu chủ dự án muốn **ít container nhất** và chấp nhận trộn trang vào API, chọn **D**.

## Kết quả đo (2026-10-17, thử nhanh)

Cùng trang chi tiết một kỹ năng, cùng dữ liệu, bản production của mỗi bên chạy trong container trên cùng máy, sau 30 lần mở trang:

| | Next.js (`nextjs-docs`) | Astro (`public-site`) |
|---|---|---|
| RAM khi chạy production | 76 MB | **35 MB** |
| RAM khi chạy dev | 499 MB | **133 MB** |
| Image production | 301 MB | **248 MB** (gần hết là image Node gốc; mọi thư viện được gói vào `dist/`, 1,4 MB) |
| HTML của trang | 14 KB | **2,7 KB** |
| JavaScript gửi xuống trình duyệt | 579 KB, 11 file | **0** |

Lần build đầu image Astro nặng 446 MB vì mang theo cả `node_modules`. Gói mọi thư viện vào bản build (`ssr.noExternal` trong `astro.config.mjs`) đưa về 248 MB.

## Kế hoạch nếu chọn C

| # | Việc | Kết quả |
|---|---|---|
| 1 | Thử nhanh: dựng Astro chạy trang danh sách kỹ năng, **đo RAM, image, JS thật** so với Next.js | Số liệu thật thay cho ước tính ở trên; số không như kỳ vọng thì dừng lại hỏi |
| 2 | Làm lại `/skills`, `/skills/{slug}`, `/skills/files/{mã}` trong ứng dụng mới; chuyển test trình duyệt; nginx trỏ sang; xóa `nextjs-docs` (compose, Dockerfile, Makefile, `ci/`, workspace pnpm, tài liệu) | Trang public như cũ, nhẹ hơn |
| 3 | **[XÁC NHẬN]** Thiết kế tài khoản người dùng cuối: bảng, đăng ký hay chỉ admin tạo, xác thực email, bộ đăng nhập riêng tách khỏi admin | Đề xuất được duyệt (thay việc quản lý người dùng cuối đang để sau theo ADR-0015) |
| 4 | API + màn hình: đăng nhập người dùng, lịch sử đã xem, yêu thích, lưu xem sau; admin xem và khóa tài khoản người dùng trong trang quản trị | Người dùng đăng nhập dùng được 3 tính năng |

Việc 3–4 làm theo vòng đời đầy đủ của [ADR-0018](0018-lifecycle-practice-track.md): hồ sơ yêu cầu, câu hỏi làm rõ, phân tích ảnh hưởng, thiết kế, kế hoạch test, QA, ghi chú phát hành, bàn giao.

## Nội dung cũ của `nextjs-docs`

| Nội dung | Xử lý |
|---|---|
| Trang "nội dung đã chuyển" cho `/docs/...` | Bỏ. nginx chuyển hướng `/docs…` → `/skills` (301), link cũ vẫn không lỗi |
| Quy tắc thiết kế trong `nextjs-docs/CLAUDE.md` và `README.md` (lưu đệm 60 giây, link file luôn mới, kỹ năng riêng tư trả 404 như không tồn tại) | Đã có trong [trang-public.md](../features/trang-public.md); giữ ở đó làm yêu cầu cho ứng dụng mới |
| Kiểu dữ liệu tự viết trong `ledger-api.ts` | Thay bằng kiểu sinh từ `openapi.json` |

## Cần chủ dự án quyết

1. Chọn phương án: **C** (khuyên), D, A, B hay E?
2. Có đồng ý bỏ trang "nội dung đã chuyển" và chuyển hướng `/docs` → `/skills` không?
3. Ngoài kỹ năng và bằng chứng, người dùng còn được xem gì: file công khai trong Files, mục tiêu học?
4. Người dùng tự đăng ký, hay chỉ admin tạo tài khoản? (Quyết ở việc 3, chưa cần trả lời ngay.)
