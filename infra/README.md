# infra: môi trường dựng bằng Terraform

Quyết định và bố cục: [ADR-0011](../docs/adr/0011-infrastructure-as-code.md). Dev vẫn chạy bằng Compose (`make up`). Terraform mô tả môi trường `prod-like`: chạy **image production** trên cùng Docker của máy, với tên riêng (`sm-prod-like-<dịch vụ>`), network, volume riêng và nhãn `sm.env=prod-like`. Hiện `prod-like` là **bản demo chạy trên máy** (CD-01).

## Dùng

```bash
make cd                        # cách thường dùng: kiểm tra → build image → deploy → test trình duyệt (ci/deploy.sh)
make cd SKIP_CHECK=1           # bỏ bước kiểm tra khi commit đã qua make ci
make tf-images                 # chỉ build image sm-api / sm-nextjs-fe / sm-public-site / sm-minio:<short sha>
make tf-plan                   # xem Terraform sẽ đổi gì (tag=<image tag> để chạy image khác)
make tf-apply                  # deploy, hỏi trước khi làm; chờ mọi container khỏe
make tf-destroy                # xóa container, network và volume (MẤT dữ liệu demo)
make e2e env=prod-like         # chạy lại test trình duyệt trên bản demo
infra/tf.sh prod-like output   # lệnh Terraform khác
```

Mở **http://localhost:9443**, đăng nhập `root` / `12345678`. Máy không cần cài gì: Terraform chạy trong image công cụ (`infra/Dockerfile`, Terraform 1.16.3), gắn Docker socket, chạy bằng user của máy. Các lệnh `make tf-*` và `make cd` đi qua `scripts/lane.sh run` nên không chạy chồng lên test của cuộc trò chuyện khác.

## Có gì ở đâu

| Đường dẫn | Nội dung |
|---|---|
| `Dockerfile` | Image công cụ (Terraform) |
| `tf.sh` | Chạy Terraform cho một môi trường trong image công cụ; file state được đặt quyền 600 |
| `modules/stack/` | Mọi thứ của một môi trường: network, volume, các container bên dưới |
| `live/prod-like/stack/` | Gốc của môi trường: `terraform.tfvars` (không có secret), `.terraform.lock.hcl` (commit), state cục bộ (không commit) |

| Container | Làm gì |
|---|---|
| `postgres`, `redis` | DB và cache, hàng đợi, session |
| `minio` + `minio-init` | Kho file (tính năng Files). `minio-init` chạy một lần: tạo bucket, luật dọn file, user riêng cho backend (dùng chung `docker/minio/create-buckets.sh` với dev) |
| `migrate` | Chạy một lần trước PHP: migration → đăng ký quyền cho mọi API quản trị → tạo tài khoản `root` (chỉ khi `seed_root = true`, đang bật cho demo) |
| `php` | API (php-fpm) |
| `queue`, `scheduler`, `reverb` | Việc nền, việc hẹn giờ (dọn upload treo), WebSocket báo kết quả tức thì; cùng image API, khác lệnh |
| `nextjs`, `public-site` | Giao diện quản trị, trang public (Astro) |
| `proxy` | nginx, cổng duy nhất mở ra máy (9443) |

- **Cấu hình dùng chung với Compose:** `docker/nginx/*.conf`, `docker/postgres/pg_hba.conf`, `docker/redis/redis.conf`, `docker/minio/*` được đọc bằng `file()` rồi đưa vào container; tên `ml-*` trong cấu hình nginx được đổi thành `sm-prod-like-*`.
- **Secret** (mật khẩu DB, Redis, MinIO, secret Reverb, `APP_KEY`) do provider `random` sinh và nằm trong state. **State là secret:** không commit, không sao lưu ra ngoài.
- **Mất state:** `docker rm -f $(docker ps -aq --filter label=sm.env=prod-like)`, làm tương tự với `docker network` và `docker volume`, rồi `make cd`.
- **RAM:** khoảng 2 GB khi chạy đủ. Chạy cùng stack dev thì máy còn khá ít RAM trống; nếu chậm, tắt dev (`make down`) trong lúc demo.

## Image production

`ci/build.sh` build từ code đã commit (`git archive`), từ target `production` của `docker/laravel/Dockerfile` và `docker/nextjs/Dockerfile`, cùng `docker/minio/Dockerfile`:

- **API:** entrypoint chạy `php artisan optimize` **lúc container khởi động**, theo biến môi trường của chính container đó, nên cache luôn khớp môi trường. Composer không có trong image cuối.
- **Giao diện quản trị và trang công khai:** nhúng sẵn `NEXT_PUBLIC_API_URL=/api` (cùng địa chỉ với trang) và khóa công khai của Reverb (`NEXT_PUBLIC_REVERB_APP_KEY=second-memory`, trùng biến `reverb_app_key` của module), nên một tag chạy được ở mọi môi trường. Trang công khai gọi API phía server qua `API_INTERNAL_URL`.

| Image | Dung lượng (2026-10-14) |
|---|---|
| `sm-api` | 332 MB |
| `sm-nextjs-fe` | 307 MB |
| `sm-public-site` | 248 MB |

## Giới hạn hiện tại

| Chưa có | Hậu quả |
|---|---|
| HTTPS, tên miền riêng | Chỉ chạy `http://localhost:9443` trên máy này |
| Mật khẩu `root` riêng | Demo dùng mật khẩu dev `12345678`; production thật phải tắt `seed_root` |
| Dữ liệu mẫu | Bộ dữ liệu mẫu của dev (`make seed`) không chạy ở môi trường production, nên demo bắt đầu trống |
| Sao lưu | Volume `sm-prod-like-*` chưa có lịch sao lưu |
