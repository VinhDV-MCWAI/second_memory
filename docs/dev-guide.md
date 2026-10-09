# Hướng dẫn môi trường dev: build nhanh, sửa lỗi nhanh

Tài liệu này dành cho việc chạy dự án trên máy cá nhân. Staging và production chưa làm ở giai đoạn này (xem [ADR-0012](adr/0012-restore-features-dev-first.md)).

Máy không cần cài PHP, Composer, Node hay pnpm. Mọi thứ chạy trong Docker, mọi lệnh gõ ở thư mục gốc của repo qua `make`. Gõ `make help` để xem toàn bộ lệnh.

## 1. Chạy lần đầu (khoảng 5–10 phút)

```bash
make up                                                        # tạo file env nếu chưa có, build và chạy toàn bộ container
docker exec ml-php php artisan migrate --force                 # tạo bảng trong DB dev
docker exec ml-php php artisan db:seed --class=RootAccountSeeder   # tạo tài khoản quản trị
```

Mở trình duyệt vào **http://localhost:81/admin** và đăng nhập:

| Ô | Giá trị |
|---|---|
| Tên đăng nhập | `root` (không phải email `root@gmail.com`) |
| Mật khẩu | `12345678` |

Đây là tài khoản chủ, có toàn quyền. Seeder chạy lại nhiều lần cũng không tạo trùng.

## 2. Các địa chỉ

Luôn vào qua cổng **81**. nginx ở cổng này chia request cho từng phần; đăng nhập (cookie, CSRF) chỉ hoạt động đúng khi đi qua đây.

```
Trình duyệt → http://localhost:81 (nginx)
  ├─ /api/...           → Laravel API
  ├─ /skills, /docs     → trang public
  └─ mọi đường dẫn khác → trang quản trị (Next.js): /login, /admin, ...
```

| Dịch vụ | Địa chỉ từ máy | Dùng để |
|---|---|---|
| Trang quản trị + API | http://localhost:81 | Dùng hằng ngày |
| Danh sách API (OpenAPI) | file `laravel-api/openapi.json` (sinh bằng `make openapi`) | Xem các API hiện có |
| MinIO console | http://localhost:9102 | Xem bucket và file |
| PostgreSQL | `localhost:5502` | Mở DB bằng DBeaver / TablePlus |
| Redis | `localhost:6601` | Xem session, cache |

Tên đăng nhập và mật khẩu của DB, Redis, MinIO nằm trong `docker/.env`. File này chứa bí mật nên không đưa lên git, không dán vào chat.

## 3. Sửa code thì cần làm gì để thấy thay đổi

Thư mục code được gắn thẳng vào container, nên phần lớn thay đổi có hiệu lực ngay.

| Bạn sửa | Cần làm |
|---|---|
| Code PHP (`laravel-api/`) | Không cần làm gì, reload trang là thấy |
| Code giao diện (`nextjs-fe/`, `nextjs-docs/`) | Không cần làm gì, trình duyệt tự cập nhật |
| Thêm migration | `docker exec ml-php php artisan migrate --force` |
| Thêm hoặc xóa class PHP | `docker exec ml-php composer dump-autoload` |
| Đổi route hoặc request/response của API | `make openapi`, để sinh lại kiểu dữ liệu cho giao diện |
| Thêm thư viện PHP | `docker exec ml-php composer require <tên gói>` |
| Thêm thư viện JS (sửa `pnpm-lock.yaml`) | `docker compose -f docker/docker-compose.yml up -d --build ml-nextjs ml-nextjs-docs` |
| Sửa `docker/.env` hoặc Dockerfile | `make restart` |

## 4. Test nhanh

Chỉ chạy phần liên quan tới thứ vừa sửa. Bộ đầy đủ chỉ chạy trước khi merge.

| Muốn kiểm tra | Lệnh | Thời gian |
|---|---|---|
| Test backend của một tính năng | `make test f=AdminMst` (thay bằng tên class hoặc tên test) | vài giây |
| Toàn bộ test backend | `make test` | khoảng 1 phút |
| Test giao diện | `make fe-test` | khoảng 30 giây |
| Lỗi kiểu TypeScript | `make fe-typecheck` | khoảng 30 giây |
| Tự sửa định dạng code | `make format` | vài giây |
| Luồng chính trên trình duyệt thật (Playwright) | `make e2e` | khoảng 30 giây |
| Mọi thứ (trước khi merge) | `make verify` | vài phút |

Test dùng một database riêng tên `testing`, không đụng dữ liệu dev.

## 5. Khi có lỗi: xem ở đâu

| Hiện tượng | Xem ở đâu |
|---|---|
| API trả 500 | `laravel-api/storage/logs/laravel.log` |
| Container không chạy hoặc bị khởi động lại liên tục | `make ps`, rồi `make logs s=ml-php` (đổi tên container) |
| Lỗi trên giao diện | Tab Console và Network của trình duyệt (F12) |
| Cần vào bên trong container | `make sh s=ml-php` |
| Cần xem dữ liệu | `docker exec -it ml-postgres psql -U <user> -d ml_pg_db` (user lấy trong `docker/.env`) |

## 6. Các lỗi hay gặp

| Lỗi | Nguyên nhân | Cách sửa |
|---|---|---|
| Đăng nhập báo 401 dù mật khẩu đúng | Nhập email thay vì tên đăng nhập | Nhập `root` |
| Đăng nhập báo 429 | Sai quá nhiều lần, bị khóa tạm | Đợi một phút rồi thử lại |
| `GET /api/admin/credential/me` trả 401 trong console | Bình thường khi chưa đăng nhập: trang dùng nó để kiểm tra rồi chuyển tới `/login` | Không cần sửa |
| Cảnh báo "Encountered a script tag" trong console | Thư viện chọn giao diện sáng/tối chèn script | Không ảnh hưởng, bỏ qua |
| 419 "CSRF token mismatch" khi gọi API bằng curl | Thiếu cookie CSRF | Gọi `GET /api/sanctum/csrf-cookie` trước, gửi kèm header `Referer: http://localhost:81/...`, rồi gửi giá trị cookie `XSRF-TOKEN` trong header `X-XSRF-TOKEN` |
| Container dừng với mã 127 sau khi khởi động lại Docker hoặc WSL | Đường dẫn gắn vào container bị cũ | `make up` |
| `make up` báo "ml-redis is unhealthy" | Redis đang nạp lại dữ liệu | Đợi một lúc rồi `make up` lại |
| `Permission denied` / `EACCES` trên `vendor/`, `storage/`, `.next/` | File do container chạy quyền root tạo ra trước đây | `docker run --rm -v "$PWD":/r alpine:3.24 chown -R 1000:1000 /r/<đường dẫn>` |
| Bảng không tồn tại khi chạy test có lọc (`f=...`) | Test có lọc không tự tạo bảng trong DB `testing` | `docker exec -e DB_DATABASE=testing ml-php php artisan migrate --force` |
| `make openapi` thoát với mã 137 ngay sau khi khởi động | Container chưa sẵn sàng | Chạy lại |
| Class PHP đã xóa vẫn báo lỗi `include(...)` | Bộ nạp class còn nhớ file cũ | `docker exec ml-php composer dump-autoload` |

## 7. Làm lại từ đầu

| Muốn | Lệnh | Mất gì |
|---|---|---|
| Khởi động lại container | `make restart` | Không mất gì |
| Tắt hết | `make down` | Không mất gì, dữ liệu vẫn còn |
| Xóa sạch DB dev rồi tạo lại | `make fresh` (sẽ hỏi trước khi chạy) | **Mất toàn bộ dữ liệu dev** |

Không dùng `migrate:rollback` trên DB dev. Muốn thử rollback một migration thì làm trên DB `testing`:
`docker exec -e DB_DATABASE=testing ml-php php artisan migrate:rollback --step=1 --force`.
