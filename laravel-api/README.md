# Laravel API - My Life Management

## 📑 Giới thiệu

Backend API được xây dựng bằng Laravel 8+, cung cấp các API RESTful cho hệ thống My Life Management, với tính năng quản lý phân quyền nâng cao và hệ thống tự động tạo API.

## 🛠️ Yêu cầu môi trường

- **Nginx**: Phiên bản x
- **PHP**: Phiên bản 8.2+
- **PostgreSQL**: Phiên bản 15+
- **Redis**: Phiên bản 5.0+
- **Composer**: Phiên bản 2.0+

## 🚀 Hướng dẫn cài đặt

### 1. Thiết lập môi trường

#### Trong Docker (Khuyến nghị)

```bash
# Toàn bộ cấu hình Docker (như Dockerfile, docker-entrypoint.sh, docker-compose.yml)
# được quản lý tập trung và duy nhất tại thư mục `docker/` ở gốc dự án (single source of truth).
# Bạn cần chạy Docker từ thư mục đó thay vì trong project này.
cd ../docker
docker compose up -d ml-php
```

#### Trên máy local

```bash
# Clone dự án (nếu chưa có)
git clone https://github.com/WAIMC/my_life_management.git
cd my_life_management/laravel-api

# Cài đặt dependencies
composer install

# Tạo file .env
cp .env.example .env
php artisan key:generate
```

### 2. Cấu hình cơ sở dữ liệu

Mở file `.env` và cập nhật thông tin kết nối:

```bash
# Kết nối trong Docker
DB_CONNECTION=pgsql
DB_HOST=ml-postgres
DB_PORT=5432
DB_DATABASE=ml_pg_db
DB_USERNAME=ml_pg_user
DB_PASSWORD=ml_pg_password

# Kết nối từ local vào Docker
# DB_CONNECTION=pgsql
# DB_HOST=localhost
# DB_PORT=5502
# DB_DATABASE=ml_pg_db
# DB_USERNAME=ml_pg_user
# DB_PASSWORD=ml_pg_password
```

### 3. Cấu hình Redis

```bash
# Kết nối Redis trong Docker
REDIS_CLIENT=predis
REDIS_HOST=ml-redis
REDIS_PORT=6379
REDIS_PASSWORD=ml_redis_password

# Kết nối Redis từ local vào Docker
# REDIS_CLIENT=predis
# REDIS_HOST=localhost
# REDIS_PORT=6601
# REDIS_PASSWORD=ml_redis_password
```

### 4. Cấu hình JWT Authentication

```bash
# Tạo khóa JWT
php -r 'echo base64_encode(random_bytes(32));' # Copy kết quả vào ACCESS_TOKEN_SECRET
php -r 'echo base64_encode(random_bytes(32));' # Copy kết quả vào REFRESH_TOKEN_SECRET
```

### 5. Chạy Migrations

```bash
# Di chuyển tất cả bảng
php artisan migrate

# Rollback tất cả nếu cần
php artisan migrate:rollback
```

### 6. Khởi tạo dữ liệu ban đầu

```bash
php artisan db:seed
```

## 📋 Quản lý quyền

Quản lý quyền dựa trên vai trò (Role-Based):

- Mỗi tài khoản được gán một hoặc nhiều vai trò
- Mỗi vai trò chịu trách nhiệm cho một số API cụ thể
- Các API được nhóm thành các tính năng (feature) để dễ quản lý

Lớp phân quyền theo phòng ban đã bị xoá (RFC-001 slice 4); RBAC sẽ được thay bằng hai vai trò `owner` / `viewer` ở slice 8.

## 📂 Cấu trúc thư mục

```
laravel-api/
├── app/                 # Logic chính của ứng dụng
│   ├── Console/         # Commands và tasks
│   ├── Constants/       # Các hằng số
│   ├── Enums/           # Enumerations
│   ├── Http/            # Controllers, Middlewares, Requests
│   ├── Interfaces/      # Interfaces
│   ├── Models/          # Eloquent models
│   ├── Providers/       # Service providers
│   ├── Repositories/    # Repository pattern
│   ├── Rules/           # Validation rules
│   ├── Services/        # Business logic
│   ├── Traits/          # Traits
│   └── Utilities/       # Helper utilities
├── auto_script/         # Auto-generator scripts
│   ├── run_generator.sh
│   ├── fix_permissions.sh
│   └── ...
├── bootstrap/           # Application bootstrap
├── config/              # Configuration files
├── database/            # Migrations, factories, seeders
│   ├── factories/
│   ├── migrations/
│   ├── schema/          # Schema definitions
│   └── seeders/
├── public/              # Publicly accessible files
├── resources/           # Views, assets, language files
├── routes/              # Route definitions
│   ├── api_generated.php # Auto-generated routes
│   ├── api.php          # API routes
│   └── web.php          # Web routes
├── storage/             # Logs, cache, uploads
└── tests/               # Unit and feature tests
```

## 🖥️ API Endpoints

Danh sách API endpoints được tự động quản lý và có thể được xem bằng:

```bash
php artisan route:list
```

## 🔧 Xử lý sự cố

### Vấn đề quyền truy cập trong Docker/WSL

```bash
# Chạy từ thư mục gốc dự án
./start.sh

# Hoặc thủ công
chmod -R 755 laravel-api/
chmod -R 777 laravel-api/storage laravel-api/bootstrap/cache
chmod -R 755 laravel-api/app/Providers/
```

### Lỗi kết nối cơ sở dữ liệu

Kiểm tra:
1. Docker containers đang chạy (`docker ps`)
2. Thông tin kết nối trong .env
3. Network giữa các containers

### Lỗi tạo API mới

Sau khi thêm API mới:
```bash
php artisan app:sync-api-permission
```

## 📝 Tham khảo

- [Laravel Documentation](https://laravel.com/docs)
- [PostgreSQL Documentation](https://www.postgresql.org/docs/)
- [Redis Documentation](https://redis.io/documentation)