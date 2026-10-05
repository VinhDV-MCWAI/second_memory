# My Life Management - Docker Environment

Môi trường Docker được tối ưu hóa cho PHP (Laravel), Next.js, PostgreSQL, Nginx và Redis trên WSL Windows.

## 📋 Mục Lục

- [Yêu Cầu Hệ Thống](#yêu-cầu-hệ-thống)
- [Tech Stack](#tech-stack)
- [Kiến Trúc](#kiến-trúc)
- [Cài Đặt Nhanh](#cài-đặt-nhanh)
- [Cấu Hình Chi Tiết](#cấu-hình-chi-tiết)
- [Các Lệnh Thường Dùng](#các-lệnh-thường-dùng)
- [Troubleshooting](#troubleshooting)
- [Performance Tuning](#performance-tuning)

## Yêu Cầu Hệ Thống

### Windows (WSL2)

- Windows 10/11 với WSL2 enabled
- Docker Desktop for Windows (latest version)
- WSL2 Ubuntu 20.04+ hoặc Debian
- Tối thiểu 8GB RAM (khuyến nghị 16GB)
- 20GB disk space trống

### WSL2 Configuration

Tạo hoặc chỉnh sửa file `.wslconfig` trong thư mục Windows user (`C:\Users\<YourUsername>\.wslconfig`):

```ini
[wsl2]
memory=8GB
processors=4
swap=2GB
localhostForwarding=true
```

Sau đó restart WSL:

```powershell
wsl --shutdown
```

## Tech Stack

| Service        | Version        | Mô Tả                                |
| -------------- | -------------- | ------------------------------------ |
| **PHP**        | 8.3-fpm-alpine | Laravel API với OPcache optimization |
| **Composer**   | 2.7            | PHP dependency manager               |
| **Node.js**    | 22 LTS Alpine  | Next.js frontend                     |
| **pnpm**       | Latest         | Node package manager                 |
| **PostgreSQL** | 16 Alpine      | Main database                        |
| **Redis**      | 7 Alpine       | Cache & session storage              |
| **Nginx**      | 1.25 Alpine    | Reverse proxy & web server           |

## Kiến Trúc

```
┌─────────────────────────────────────────────────────────┐
│                    WSL2 Windows Host                     │
│                                                          │
│  ┌────────────────────────────────────────────────────┐ │
│  │              Docker Network (Bridge)                │ │
│  │                                                     │ │
│  │  ┌──────────┐  ┌──────────┐  ┌──────────────────┐ │ │
│  │  │  Nginx   │  │   Redis  │  │   PostgreSQL     │ │ │
│  │  │  :80/443 │  │   :6379  │  │      :5432       │ │ │
│  │  └────┬─────┘  └─────┬────┘  └────────┬─────────┘ │ │
│  │       │              │                 │           │ │
│  │  ┌────┴─────┐   ┌────┴────┐           │           │ │
│  │  │ Next.js  │   │PHP-FPM  │───────────┘           │ │
│  │  │  :6543   │   │ :9000   │                       │ │
│  │  └──────────┘   └─────────┘                       │ │
│  │                                                     │ │
│  │  Named Volumes:                                    │ │
│  │  • postgres_data  • redis_data                     │ │
│  └────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────┘
     │                    │                    │
     └─── Port 81 ────────┴──── Port 5502 ─────┴── Port 6601
        (Nginx)            (PostgreSQL)         (Redis)
```

## Cài Đặt Nhanh

### 1. Clone Repository (nếu chưa có)

```bash
cd /home/vinhdv/projects/my_life_management
```

### 2. Cấu Hình Environment Variables

```bash
cd docker
cp .env.example .env
# Chỉnh sửa .env theo nhu cầu
nano .env
```

### 3. Build và Start Services

```bash
# Build tất cả images (lần đầu hoặc khi có thay đổi Dockerfile)
docker compose build

# Start all services
docker compose up -d

# Xem logs
docker compose logs -f
```

### 4. Kiểm Tra Health Status

```bash
docker compose ps
```

Tất cả services phải có status `healthy` hoặc `running (healthy)`.

### 5. Truy Cập Ứng Dụng

- **Frontend (Next.js)**: http://localhost:81
- **API (Laravel)**: http://localhost:81/api
- **PostgreSQL**: localhost:5502
- **Redis**: localhost:6601

## Cấu Hình Chi Tiết

### PostgreSQL

**Connection từ host machine:**

```bash
psql -h localhost -p 5502 -U ml_pg_user -d ml_pg_db
```

**Connection từ Laravel (.env):**

```env
DB_CONNECTION=pgsql
DB_HOST=ml-postgres
DB_PORT=5432
DB_DATABASE=ml_pg_db
DB_USERNAME=ml_pg_user
DB_PASSWORD=ml_pg_password
```

**Backup database:**

```bash
docker compose exec ml-postgres pg_dump -U ml_pg_user ml_pg_db > backup.sql
```

**Restore database:**

```bash
docker compose exec -T ml-postgres psql -U ml_pg_user ml_pg_db < backup.sql
```

### Redis

**Connection từ host:**

```bash
redis-cli -h localhost -p 6601 -a ml_redis_password
```

**Connection từ Laravel (.env):**

```env
REDIS_HOST=ml-redis
REDIS_PORT=6379
REDIS_PASSWORD=ml_redis_password
CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis
```

**Monitor Redis:**

```bash
docker compose exec ml-redis redis-cli -a ml_redis_password MONITOR
```

### PHP/Laravel

**Chạy Composer commands:**

```bash
docker compose exec ml-php composer install
docker compose exec ml-php composer update
```

**Laravel Artisan commands:**

```bash
docker compose exec ml-php php artisan migrate
docker compose exec ml-php php artisan cache:clear
docker compose exec ml-php php artisan config:cache
```

**Kiểm tra PHP info:**

```bash
docker compose exec ml-php php -i
```

**OPcache status:**

```bash
docker compose exec ml-php php -r "echo json_encode(opcache_get_status(), JSON_PRETTY_PRINT);"
```

### Next.js

**Rebuild Next.js:**

```bash
docker compose build ml-nextjs
docker compose up -d ml-nextjs
```

**Xem logs:**

```bash
docker compose logs -f ml-nextjs
```

## Các Lệnh Thường Dùng

### Quản Lý Services

```bash
# Start all services
docker compose up -d

# Stop all services
docker compose down

# Restart một service
docker compose restart ml-php

# Stop một service
docker compose stop ml-nextjs

# Start một service
docker compose start ml-nextjs

# Rebuild và restart
docker compose up -d --build

# Xem status
docker compose ps

# Xem logs
docker compose logs -f [service-name]

# Xem resource usage
docker stats
```

### Vào Container Shell

```bash
# PHP container
docker compose exec ml-php sh

# PostgreSQL container
docker compose exec ml-postgres psql -U ml_pg_user -d ml_pg_db

# Redis container
docker compose exec ml-redis redis-cli -a ml_redis_password

# Next.js container
docker compose exec ml-nextjs sh

# Nginx container
docker compose exec ml-nginx sh
```

### Volume Management

```bash
# List volumes
docker volume ls

# Inspect volume
docker volume inspect ml_postgres_data

# Backup volume
docker run --rm -v ml_postgres_data:/data -v $(pwd):/backup alpine tar czf /backup/postgres_backup.tar.gz -C /data .

# Restore volume
docker run --rm -v ml_postgres_data:/data -v $(pwd):/backup alpine tar xzf /backup/postgres_backup.tar.gz -C /data

# Remove volumes (CAUTION: This deletes data!)
docker compose down -v
```

### Clean Up

```bash
# Remove stopped containers
docker compose down

# Remove containers and volumes
docker compose down -v

# Remove all unused images, containers, networks
docker system prune -a

# Remove build cache
docker builder prune -a
```

## Troubleshooting

### Service không start được

```bash
# Check logs
docker compose logs [service-name]

# Check health status
docker compose ps

# Restart service
docker compose restart [service-name]

# Force recreate
docker compose up -d --force-recreate [service-name]
```

### PostgreSQL connection refused

1. Kiểm tra service đã healthy chưa:

   ```bash
   docker compose ps ml-postgres
   ```

2. Kiểm tra logs:

   ```bash
   docker compose logs ml-postgres
   ```

3. Test connection:
   ```bash
   docker compose exec ml-postgres pg_isready -U ml_pg_user
   ```

### Redis connection issues

```bash
# Test Redis
docker compose exec ml-redis redis-cli -a ml_redis_password ping

# Check logs
docker compose logs ml-redis
```

### Nginx 502 Bad Gateway

1. Kiểm tra backend services (PHP, Next.js) đang chạy:

   ```bash
   docker compose ps
   ```

2. Kiểm tra Nginx logs:

   ```bash
   docker compose logs ml-nginx
   ```

3. Test upstream manually:

   ```bash
   # Test PHP-FPM
   docker compose exec ml-nginx wget -O- http://ml-php:9000

   # Test Next.js
   docker compose exec ml-nginx wget -O- http://ml-nextjs:6543
   ```

### Port already in use

Nếu port 81, 5502, hoặc 6601 đã được sử dụng, chỉnh sửa file `.env`:

```bash
# Đổi các port OUTSIDE_ENV
NGINX_PORT_OUTSIDE_ENV=8081
POSTGRES_PORT_OUTSIDE_ENV=5503
REDIS_PORT_OUTSIDE_ENV=6602
```

Sau đó:

```bash
docker compose down
docker compose up -d
```

### WSL2 Performance Issues

1. Kiểm tra `.wslconfig` đã set memory đủ chưa
2. Restart WSL: `wsl --shutdown` (từ PowerShell)
3. Restart Docker Desktop
4. Check disk space: `df -h`

### Permission errors trong Laravel

```bash
# Fix permissions
docker compose exec ml-php chmod -R 775 storage bootstrap/cache
docker compose exec ml-php chown -R laravel:laravel storage bootstrap/cache
```

## Performance Tuning

### PostgreSQL Tuning

Edit `docker-compose.yml` PostgreSQL command section để adjust shared_buffers, work_mem, etc.

### Redis Memory

Edit `redis/redis.conf`:

```conf
maxmemory 1gb
maxmemory-policy allkeys-lru
```

### PHP OPcache

Edit `laravel/php.ini` để tune OPcache settings.

### Nginx Caching

Uncomment caching directives trong `nginx/default.conf` để enable static file caching.

## Security Best Practices

1. **Đổi passwords mặc định** trong `.env`
2. **Never commit** `.env` file
3. **Enable SSL/TLS** cho production (uncomment SSL block trong nginx/default.conf)
4. **Regular backups** của PostgreSQL và Redis data
5. **Update images** thường xuyên:
   ```bash
   docker compose pull
   docker compose up -d
   ```

## 📝 Tham Khảo

- [Docker Documentation](https://docs.docker.com/)
- [Docker Compose Documentation](https://docs.docker.com/compose/)
- [PostgreSQL Docker Hub](https://hub.docker.com/_/postgres)
- [Redis Docker Hub](https://hub.docker.com/_/redis)
- [Nginx Docker Hub](https://hub.docker.com/_/nginx)
- [PHP Docker Hub](https://hub.docker.com/_/php)
