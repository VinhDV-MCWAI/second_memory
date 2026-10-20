# Hướng dẫn môi trường dev: build nhanh, sửa lỗi nhanh

Tài liệu này dành cho việc chạy dự án trên máy cá nhân. Staging và production chưa làm ở giai đoạn này (xem [ADR-0012](adr/0012-restore-features-dev-first.md)).

Máy không cần cài PHP, Composer, Node hay pnpm. Mọi thứ chạy trong Docker, mọi lệnh gõ ở thư mục gốc của repo qua `make`. Gõ `make help` để xem toàn bộ lệnh.

## 1. Chạy lần đầu (khoảng 5–10 phút)

```bash
make up                                                        # tạo file env nếu chưa có, build và chạy toàn bộ container
docker exec ml-php php artisan migrate --force                 # tạo bảng trong DB dev
make seed                                                      # tạo tài khoản và dữ liệu mẫu
```

Mở trình duyệt vào **http://localhost:81/admin** và đăng nhập:

| Ô | Giá trị |
|---|---|
| Tài khoản | Tên đăng nhập | Mật khẩu | Được làm gì |
|---|---|---|---|
| Chủ | `root` (không phải email `root@gmail.com`) | `12345678` | Toàn quyền |
| Chỉ xem | `viewer` | `12345678` | Xem mọi trang, sửa gì cũng bị báo lỗi 403 |
| Người dùng trang public | `demo` | `12345678` | Chỉ đăng nhập được ở http://localhost:81/account/login (yêu thích, xem sau); không vào được trang quản trị. Tạo bởi `make seed` |

`make seed` còn thêm dữ liệu mẫu cho Skill Ledger để có cái mà thử:

| Loại | Số lượng | Ghi chú |
|---|---|---|
| Skill | 5 | PostgreSQL, Laravel, Docker công khai; React, Terraform riêng tư. Mỗi skill có lịch sử cấp độ |
| Nhãn | 5 | database, backend, devops, frontend, performance |
| Bằng chứng | 6 | Pull request, quyết định thiết kế, sự cố, và 2 ghi chú như được nhập từ Obsidian |
| Mục tiêu học | 4 | 2 đang theo, 1 đã đạt, 1 đã bỏ |

Chạy lại `make seed` bao nhiêu lần cũng được: dòng đã có thì giữ nguyên (kể cả khi bạn đã sửa), không tạo trùng, không xóa gì. Muốn làm lại từ đầu thì `make fresh` (xóa sạch DB dev, hỏi trước). Seeder dữ liệu mẫu (`DevSampleSeeder`) từ chối chạy trên production.

## 2. Các địa chỉ

Luôn vào qua cổng **81**. nginx ở cổng này chia request cho từng phần; đăng nhập (cookie, CSRF) chỉ hoạt động đúng khi đi qua đây. Cổng 3456 (trang quản trị) và 3002 (trang public) chỉ để gỡ lỗi container: mở trang quản trị ở 3456 thì đăng nhập báo 401, mở 3002 thì không có API.

```
Trình duyệt → http://localhost:81 (nginx)
  ├─ /api/...           → Laravel API
  ├─ /media-official/… → MinIO (chỉ link ký tạm do API cấp; quản lý file)
  ├─ /app/…            → Reverb (WebSocket, thông báo tức thì)
  ├─ /admin…, /_next/… → trang quản trị (Next.js): đăng nhập ở /admin/login
  ├─ /login, /docs…    → chuyển hướng tới /admin/login, /
  └─ mọi đường dẫn khác → trang public (Astro, ứng dụng public-site): /, /skills, /guides/…, /me
```

| Dịch vụ | Địa chỉ từ máy | Dùng để |
|---|---|---|
| Trang public | http://localhost:81/ | Trang chủ danh mục, kỹ năng, bài viết |
| Trang quản trị + API | http://localhost:81/admin | Dùng hằng ngày (`root` / `12345678`) |
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
| Code PHP chạy trong việc nền, việc hẹn giờ hoặc Reverb (job, event, `routes/console.php`, `routes/channels.php`) | `make workers-restart`: ba container này giữ code cũ trong bộ nhớ, giống server thật |
| Code giao diện (`nextjs-fe/`, `public-site/`) | Không cần làm gì, trình duyệt tự cập nhật |
| Thêm migration | `docker exec ml-php php artisan migrate --force` |
| Thêm hoặc xóa class PHP | `docker exec ml-php composer dump-autoload` |
| Đổi route hoặc request/response của API | `make openapi`, để sinh lại kiểu dữ liệu cho giao diện |
| Thêm API quản trị mới | Đặt tên route (`->name('skill.list')`), rồi `make permissions` để tạo quyền ([phân quyền động](features/phan-quyen-dong.md)) |
| Thêm thư viện PHP | `docker exec ml-php composer require <tên gói>` |
| Thêm thư viện JS (sửa `pnpm-lock.yaml`) | `make up` (dựng lại image và tạo mới thư mục `node_modules` trong container; thiếu bước tạo mới thì báo "Cannot find module") |
| Sửa `docker/.env` hoặc Dockerfile | `make restart` |
| Thiếu hoặc hỏng file env của Laravel / giao diện | `make setup` (chạy `scripts/setup-env.sh`: giữ bí mật đã có trong `docker/.env`, tạo lại `laravel-api/.env`, `nextjs-fe/.env`), rồi `make restart` |

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
| Mọi thứ, gồm cả file chưa commit | `make verify` | vài phút |
| Giống hệt GitHub: code đã commit, container sạch ([ci/README.md](../ci/README.md)) | `scripts/lane.sh run make ci` | khoảng 4–5 phút |
| Deploy bản demo chạy image production trên máy rồi test trình duyệt ([ci/README.md](../ci/README.md)) | `make cd`, mở http://localhost:9443 | khoảng 10–15 phút |

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
| Đăng nhập ở trang public (`/account/login`) báo 401 với `root` | `root`, `viewer` là tài khoản quản trị, tách riêng khỏi tài khoản người dùng trang public ([ADR-0020](adr/0020-end-user-accounts.md)) | Dùng `demo` / `12345678` (`make seed`) hoặc tự đăng ký; `root` đăng nhập ở `/admin/login` |
| Đăng nhập báo 401 dù mật khẩu đúng | Nhập email thay vì tên đăng nhập; hoặc mở trang quản trị ở cổng 3456 thay vì qua cổng 81 (gặp ngày 2026-10-19) | Nhập `root`; mở http://localhost:81/admin/login |
| Đăng nhập báo 429 | Sai quá nhiều lần, bị khóa tạm 15 phút (5 lần sai cùng một tên, hoặc 30 lần sai từ cùng một IP với mọi tên) | Đợi theo số giây trong header `Retry-After`. Trên dev muốn mở ngay: `docker exec ml-php php artisan cache:clear` |
| Trang báo lỗi kết nối, `docker ps -a` thấy `ml-php`, `ml-postgres`, `ml-redis`, `ml-nginx`… `Exited (127)` | Máy hoặc WSL khởi động lại; các container gắn từng file lẻ từ máy vào thì không tự chạy lại (gặp 2026-10-15, 2026-10-19, 2026-10-20; [báo cáo sự cố](incidents/2026-10-20-trang-sap-sau-khoi-dong-lai.md)) | `docker compose -f docker/docker-compose.yml up -d` (hoặc `make up`). Migration chạy lại tự động khi `ml-php` khởi động |
| `make up` báo `ml-nextjs` unhealthy, `ml-nginx` không bật | Bài kiểm tra sức khỏe của app quản trị gọi đường dẫn không còn (đã sửa sang `/admin/login` ngày 2026-10-20) | Kiểm `docker inspect ml-nextjs -f '{{json .State.Health}}'`; đường dẫn kiểm tra trong `docker/docker-compose.yml` phải trả 200 |
| `GET /api/admin/credential/me` trả 401 trong console | Bình thường khi chưa đăng nhập: trang dùng nó để kiểm tra rồi chuyển tới `/admin/login` | Không cần sửa |
| Cảnh báo "Encountered a script tag" trong console | Script chống nháy màu của thư viện chọn giao diện sáng/tối bị render trên trình duyệt (đã sửa trong `nextjs-fe/src/app/providers.tsx`) | Nếu còn thấy: tải lại trang không dùng cache (Ctrl+Shift+R) |
| 419 "CSRF token mismatch" khi gọi API bằng curl | Thiếu cookie CSRF | Gọi `GET /api/sanctum/csrf-cookie` trước, gửi kèm header `Referer: http://localhost:81/...`, rồi gửi giá trị cookie `XSRF-TOKEN` trong header `X-XSRF-TOKEN` |
| `make up` báo "ml-redis is unhealthy" | Redis đang nạp lại dữ liệu | Đợi một lúc rồi `make up` lại |
| `Permission denied` / `EACCES` trên `vendor/`, `storage/`, `.next/` | File do container chạy quyền root tạo ra trước đây | `docker run --rm -v "$PWD":/r alpine:3.24 chown -R 1000:1000 /r/<đường dẫn>` |
| Tạo admin trên dev báo "The email must be a valid email address." dù email đúng | Email admin phải thuộc tên miền có thật: máy hỏi DNS trên Internet, mạng chập chờn thì hỏi không được. Khi chạy test thì phần kiểm tra này tắt (`EMAIL_DNS_CHECK=false` trong `laravel-api/phpunit.xml`), nên test không cần mạng | Kiểm tra mạng rồi thử lại |
| Bảng không tồn tại khi chạy test có lọc (`f=...`) | Test có lọc không tự tạo bảng trong DB `testing` | `docker exec -e DB_DATABASE=testing ml-php php artisan migrate --force` |
| API quản trị mới trả 403 cho mọi người trừ Chủ | Route chưa có tên, hoặc quyền chưa được tạo, hoặc chưa vai trò nào có quyền đó | Đặt tên route, `make permissions`, rồi gán quyền cho vai trò |
| `DynamicPermissionTest` báo "run make permissions" | DB `testing` chưa có quyền cho route mới | `make permissions` |
| `make openapi` thoát với mã 137 ngay sau khi khởi động | Container chưa sẵn sàng | Chạy lại |
| Class PHP đã xóa vẫn báo lỗi `include(...)` | Bộ nạp class còn nhớ file cũ | `docker exec ml-php composer dump-autoload` |
| Upload hoặc tải file báo 403 `SignatureDoesNotMatch` | Link ký cho một địa chỉ khác địa chỉ trình duyệt đang dùng (ví dụ mở bằng `127.0.0.1` thay vì `localhost:81`), hoặc nginx không giữ nguyên header `Host` | Mở trang bằng http://localhost:81; kiểm tra `AWS_PUBLIC_ENDPOINT` (sinh bởi `make setup`) và nhánh `/media-official/` trong `docker/nginx/default.conf` |
| Link file mở thẳng (không có chữ ký) báo 403 | Đúng thiết kế: kho file không cho đọc công khai | Xin link tải qua API `media/download-url/{id}` |
| Bấm "Run test job" mà không thấy thông báo | Container `ml-queue` hoặc `ml-reverb` không chạy, hoặc giao diện chưa có khóa Reverb (`NEXT_PUBLIC_REVERB_APP_KEY`) | `make ps`; xem `make logs s=ml-queue`, `make logs s=ml-reverb`; thiếu khóa thì `make setup` rồi `make restart` |
| Upload file lớn dở dang vẫn chiếm chỗ | Đóng trình duyệt giữa chừng; lệnh dọn chạy mỗi giờ cho upload quá 24 giờ | Chờ, hoặc chạy ngay `docker exec ml-php php artisan media:clean-stuck-uploads` (chỉ dọn upload quá 24 giờ) |
| Việc nền lỗi, cần xem lý do | Việc lỗi sau mọi lần thử được lưu lại | `docker exec ml-php php artisan queue:failed`; chạy lại: `queue:retry <id>` |
| Sửa code job mà việc nền vẫn chạy code cũ | Worker giữ code trong bộ nhớ | `make workers-restart` |
| `make tf-destroy` / `make tf-apply`: gõ `yes` mà vẫn báo "Destroy cancelled" / "Apply cancelled" | Bộ gõ tiếng Việt (Unikey, EVKey, kiểu Telex) coi `s` là dấu sắc, tự xóa rồi gõ lại chữ. Màn hình hiện `yes` nhưng Terraform nhận thêm ký tự xóa (`yes\bs`) | Tắt bộ gõ (chuyển sang E) trước khi gõ `yes` |
| `ml-public-site` khởi động lại liên tục, log báo "Another astro dev server is already running" | Astro ghi file khóa `.astro/dev.json` vào volume còn giữ sau khi container khởi động lại (gặp ngày 2026-10-19) | Đã sửa: `pnpm dev` chạy `astro dev --ignore-lock`. Nếu vẫn gặp: `docker run --rm --volumes-from ml-public-site alpine:3.24 rm /repo/public-site/.astro/dev.json` rồi `docker restart ml-public-site` |
| Đổi `docker/minio/create-buckets.sh` mà MinIO không đổi | Script chỉ chạy khi khởi động | `cd docker && docker compose up ml-minio-init` |

## 7. Làm lại từ đầu

| Muốn | Lệnh | Mất gì |
|---|---|---|
| Khởi động lại container | `make restart` | Không mất gì |
| Tắt hết | `make down` | Không mất gì, dữ liệu vẫn còn |
| Thêm lại tài khoản và dữ liệu mẫu bị thiếu | `make seed` | Không mất gì |
| Dọn rác Docker, ảnh không dùng và kết quả test cũ (làm sau mỗi việc, xem [hướng dẫn dọn dẹp](runbooks/don-dep.md)) | `make clean` | Không mất dữ liệu dev; lần build sau có thể chậm hơn một chút |
| Xóa sạch DB dev rồi tạo lại (kèm tài khoản và dữ liệu mẫu) | `make fresh` (sẽ hỏi trước khi chạy) | **Mất toàn bộ dữ liệu dev** |

Không dùng `migrate:rollback` trên DB dev. Muốn thử rollback một migration thì làm trên DB `testing`:
`docker exec -e DB_DATABASE=testing ml-php php artisan migrate:rollback --step=1 --force`.
