# 05. Cấu Hình Hệ Thống - System Configuration

> Chi tiết về cấu hình môi trường, Docker, biến môi trường và các file config quan trọng

---

## 📁 Cấu trúc File Cấu hình

```
second-memory/
├── .env.example                     # Template cho environment variables
├── docker/
│   ├── docker-compose.yml          # ⭐ Main orchestration file
│   ├── laravel/
│   │   ├── Dockerfile              # PHP-FPM image
│   │   ├── docker-entrypoint.sh    # Laravel startup script
│   │   └── php.ini                 # PHP configuration
│   ├── nextjs/
│   │   ├── Dockerfile              # Next.js image (production)
│   │   └── docker-entrypoint.sh
│   ├── nginx/
│   │   ├── nginx.conf              # Nginx main config
│   │   └── default.conf            # Virtual host config
│   ├── postgres/
│   │   ├── postgresql.conf         # PostgreSQL tuning
│   │   └── pg_hba.conf             # Authentication rules
│   ├── redis/
│   │   └── redis.conf              # Redis configuration
│   └── minio/
│       ├── create-buckets.sh       # MinIO initialization
│       ├── ilm-temp.json           # Lifecycle policy (temp files)
│       └── ilm-history.json        # Lifecycle policy (history)
│
├── laravel-api/
│   ├── config/                     # Laravel config files
│   │   ├── app.php
│   │   ├── database.php
│   │   ├── filesystems.php
│   │   ├── cache.php
│   │   ├── queue.php
│   │   └── ...
│   └── .env                        # Laravel environment variables
│
├── nextjs-fe/
│   ├── next.config.ts              # Next.js config (Dashboard)
│   ├── .env.local                  # Next.js environment variables
│   └── tsconfig.json
│
└── nextjs-docs/
    ├── next.config.ts              # Next.js config (Docs)
    └── .env.local
```

---

## 🐳 Docker Compose Configuration

### File: `docker/docker-compose.yml`

#### Services Overview

```yaml
name: my_LM

services:
  ml-postgres:      # PostgreSQL 16
  ml-redis:         # Redis 7
  ml-minio:         # MinIO object storage
  ml-minio-init:    # MinIO bucket initialization
  ml-php:           # Laravel API (PHP-FPM)
  ml-reverb:        # Laravel Reverb (WebSocket)
  ml-queue:         # Laravel Queue Worker
  # ml-nextjs:      # Next.js Dashboard (commented out - run via pnpm)
  # ml-nextjs-docs: # Next.js Docs (commented out - run via pnpm)

networks:
  app-network:      # Internal Docker network

volumes:
  postgres_data:    # PostgreSQL persistent data
  redis_data:       # Redis persistent data
  minio_data:       # MinIO persistent data
```

### Service Details

#### 1. PostgreSQL Service

```yaml
ml-postgres:
  container_name: ml-postgres
  image: postgres:16-alpine
  restart: unless-stopped
  environment:
    POSTGRES_DB: ${POSTGRES_DB}
    POSTGRES_USER: ${POSTGRES_USER}
    POSTGRES_PASSWORD: ${POSTGRES_PASSWORD}
    PGDATA: /var/lib/postgresql/data/pgdata
  volumes:
    - postgres_data:/var/lib/postgresql/data
    - ./postgres/pg_hba.conf:/etc/postgresql/pg_hba.conf:ro
  ports:
    - "${POSTGRES_PORT_OUTSIDE_ENV}:${POSTGRES_PORT_INSIDE_ENV}"  # 5555:5432
  command: >
    postgres
    -c shared_buffers=256MB
    -c max_connections=200
    -c effective_cache_size=512MB
    -c maintenance_work_mem=64MB
    -c checkpoint_completion_target=0.9
    -c wal_buffers=16MB
    -c default_statistics_target=100
    -c random_page_cost=1.1
    -c effective_io_concurrency=200
    -c work_mem=4MB
    -c min_wal_size=1GB
    -c max_wal_size=4GB
    -c hba_file=/etc/postgresql/pg_hba.conf
  healthcheck:
    test: ["CMD-SHELL", "pg_isready -U ${POSTGRES_USER} -d ${POSTGRES_DB}"]
    interval: 10s
    timeout: 5s
    retries: 5
  networks:
    - app-network
  deploy:
    resources:
      limits:
        memory: 1G
      reservations:
        memory: 512M
```

**Key Configuration**:
- **Image**: `postgres:16-alpine` (lightweight)
- **Port**: `5555:5432` (external:internal)
- **Performance tuning**: Via command-line args
- **Healthcheck**: `pg_isready` command
- **Resource limits**: 1GB max, 512MB reserved

---

#### 2. Redis Service

```yaml
ml-redis:
  container_name: ml-redis
  image: redis:7-alpine
  restart: unless-stopped
  volumes:
    - redis_data:/data
    - ./redis/redis.conf:/usr/local/etc/redis/redis.conf:ro
  ports:
    - "${REDIS_PORT_OUTSIDE_ENV}:${REDIS_PORT_INSIDE_ENV}"  # 6379:6379
  command: ["redis-server", "/usr/local/etc/redis/redis.conf"]
  healthcheck:
    test: ["CMD", "redis-cli", "--raw", "incr", "ping"]
    interval: 10s
    timeout: 3s
    retries: 5
  networks:
    - app-network
  deploy:
    resources:
      limits:
        memory: 512M
      reservations:
        memory: 128M
```

**Key Configuration**:
- **Persistence**: RDB + AOF (configured in redis.conf)
- **Max memory**: 512MB
- **Eviction policy**: `allkeys-lru` (Least Recently Used)

---

#### 3. MinIO Service

```yaml
ml-minio:
  container_name: ml-minio
  image: minio/minio:latest
  restart: unless-stopped
  environment:
    MINIO_ROOT_USER: ${MINIO_ROOT_USER}
    MINIO_ROOT_PASSWORD: ${MINIO_ROOT_PASSWORD}
  volumes:
    - minio_data:/data
  ports:
    - "${MINIO_PORT_OUTSIDE_ENV}:${MINIO_PORT_INSIDE_ENV}"        # 9000:9000 (API)
    - "${MINIO_CONSOLE_PORT_OUTSIDE_ENV}:${MINIO_CONSOLE_PORT_INSIDE_ENV}"  # 9001:9001 (Console)
  command: server /data --console-address ":9001"
  healthcheck:
    test: ["CMD", "curl", "-f", "http://localhost:9000/minio/health/live"]
    interval: 30s
    timeout: 20s
    retries: 3
  networks:
    - app-network
```

**Buckets** (created by ml-minio-init):
- `second-memory`: Main bucket for all files
- `second-memory-history`: Historical snapshots

---

#### 4. Laravel PHP-FPM Service

```yaml
ml-php:
  container_name: ml-php
  build:
    context: ../laravel-api
    dockerfile: ../docker/laravel/Dockerfile
    target: development
    args:
      PHP_VERSION: "8.3"
  restart: unless-stopped
  working_dir: /var/www/laravel-api
  volumes:
    - ../laravel-api:/var/www/laravel-api
    - ./laravel/php.ini:/usr/local/etc/php/conf.d/custom.ini:ro
  environment:
    DB_CONNECTION: pgsql
    DB_HOST: ${POSTGRES_HOST_INSIDE_ENV}
    DB_PORT: ${POSTGRES_PORT_INSIDE_ENV}
    DB_DATABASE: ${POSTGRES_DB}
    DB_USERNAME: ${POSTGRES_USER}
    DB_PASSWORD: ${POSTGRES_PASSWORD}
    REDIS_HOST: ${REDIS_HOST_INSIDE_ENV}
    REDIS_PORT: ${REDIS_PORT_INSIDE_ENV}
    AWS_ENDPOINT: ${AWS_ENDPOINT}
    AWS_BUCKET: ${AWS_BUCKET}
    # ... more env vars
  depends_on:
    ml-postgres:
      condition: service_healthy
    ml-redis:
      condition: service_healthy
    ml-minio-init:
      condition: service_completed_successfully
  healthcheck:
    test: ["CMD-SHELL", "php-fpm -t || exit 1"]
    interval: 30s
    timeout: 10s
    retries: 3
  networks:
    - app-network
  deploy:
    resources:
      limits:
        memory: 2G
        cpus: "6"
      reservations:
        memory: 512M
        cpus: "2"
```

**Key Points**:
- **Multi-stage build**: Development vs Production
- **Volume mounting**: Code từ host (hot reload)
- **Dependencies**: Wait for postgres + redis healthy
- **Entrypoint**: Run migrations + seeders (nếu `SEED_DB=true`)

---

#### 5. Laravel Reverb (WebSocket)

```yaml
ml-reverb:
  container_name: ml-reverb
  build:
    # Same as ml-php
  command: php artisan reverb:start --debug
  environment:
    # Same DB/Redis config
    REVERB_APP_ID: ${REVERB_APP_ID}
    REVERB_APP_KEY: ${REVERB_APP_KEY}
    REVERB_APP_SECRET: ${REVERB_APP_SECRET}
    REVERB_HOST: ${REVERB_HOST}
    REVERB_PORT: ${REVERB_PORT}
    REVERB_SCHEME: ${REVERB_SCHEME}
  ports:
    - "${REVERB_PORT_OUTSIDE_ENV}:${REVERB_PORT}"  # 8080:8080
  depends_on:
    ml-php:
      condition: service_healthy
  networks:
    - app-network
```

**Purpose**: Handle WebSocket connections for real-time features.

---

#### 6. Laravel Queue Worker

```yaml
ml-queue:
  container_name: ml-queue
  build:
    # Same as ml-php
  command: php artisan queue:work --verbose --tries=3 --timeout=900
  environment:
    # Same as ml-php
  depends_on:
    ml-php:
      condition: service_healthy
  networks:
    - app-network
```

**Purpose**: Process background jobs (email, file processing, etc.).

---

## 🌐 Environment Variables

### Root `.env` (Docker Compose level)

```bash
# PostgreSQL
POSTGRES_DB=second_memory
POSTGRES_USER=postgres
POSTGRES_PASSWORD=your_secure_password_here
POSTGRES_HOST_INSIDE_ENV=ml-postgres
POSTGRES_PORT_INSIDE_ENV=5432
POSTGRES_PORT_OUTSIDE_ENV=5555

# Redis
REDIS_HOST_INSIDE_ENV=ml-redis
REDIS_PORT_INSIDE_ENV=6379
REDIS_PORT_OUTSIDE_ENV=6379
REDIS_PASSWORD=

# MinIO
MINIO_ROOT_USER=minioadmin
MINIO_ROOT_PASSWORD=minioadmin_password
MINIO_PORT_INSIDE_ENV=9000
MINIO_PORT_OUTSIDE_ENV=9000
MINIO_CONSOLE_PORT_INSIDE_ENV=9001
MINIO_CONSOLE_PORT_OUTSIDE_ENV=9001

# Laravel
APP_ENV=local
APP_DEBUG=true
SEED_DB=false                       # Set true để auto seed khi container start

# AWS/S3 (MinIO)
AWS_ENDPOINT=http://ml-minio:9000
AWS_BUCKET=second-memory
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin_password
AWS_DEFAULT_REGION=us-east-1
AWS_USE_PATH_STYLE_ENDPOINT=true

# Laravel Reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_SERVER_HOST=ml-reverb
REVERB_PORT=8080
REVERB_SCHEME=http
BROADCAST_CONNECTION=reverb
```

---

### Laravel `.env` (`laravel-api/.env`)

```bash
APP_NAME="Second Memory"
APP_ENV=local
APP_KEY=base64:generated_key_here
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

# Database
DB_CONNECTION=pgsql
DB_HOST=ml-postgres
DB_PORT=5432
DB_DATABASE=second_memory
DB_USERNAME=postgres
DB_PASSWORD=your_password

# Redis
REDIS_CLIENT=phpredis
REDIS_HOST=ml-redis
REDIS_PASSWORD=null
REDIS_PORT=6379

# Cache
CACHE_STORE=redis
CACHE_PREFIX=second_memory_cache

# Session
SESSION_DRIVER=redis
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

# Queue
QUEUE_CONNECTION=redis

# Mail (nếu có)
MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="noreply@secondmemory.local"
MAIL_FROM_NAME="${APP_NAME}"

# AWS/S3 (MinIO)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=minioadmin
AWS_SECRET_ACCESS_KEY=minioadmin_password
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=second-memory
AWS_ENDPOINT=http://ml-minio:9000
AWS_USE_PATH_STYLE_ENDPOINT=true
AWS_URL=http://localhost:9000/second-memory

# JWT
JWT_SECRET=your_jwt_secret_here
JWT_TTL=1440                       # 24 hours in minutes
JWT_REFRESH_TTL=20160              # 2 weeks in minutes

# Laravel Reverb
REVERB_APP_ID=your_app_id
REVERB_APP_KEY=your_app_key
REVERB_APP_SECRET=your_app_secret
REVERB_HOST=localhost
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

### Next.js `.env.local` (`nextjs-fe/.env.local`)

```bash
# API URLs
NEXT_PUBLIC_API_URL=http://localhost:8000/api
NEXT_PUBLIC_WS_URL=http://localhost:8080

# App Config
NEXT_PUBLIC_APP_NAME="Second Memory Dashboard"
NEXT_PUBLIC_APP_URL=http://localhost:3000

# WebSocket (Reverb)
NEXT_PUBLIC_REVERB_APP_KEY=your_app_key
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
NEXT_PUBLIC_REVERB_SCHEME=http

# Feature Flags
NEXT_PUBLIC_ENABLE_ANALYTICS=false
NEXT_PUBLIC_ENABLE_DEBUG=true

# MinIO/S3 (Public URL)
NEXT_PUBLIC_STORAGE_URL=http://localhost:9000/second-memory
```

---

## ⚙️ Configuration Files

### 1. PHP Configuration (`docker/laravel/php.ini`)

```ini
[PHP]
; Performance
memory_limit = 512M
max_execution_time = 300
max_input_time = 300
post_max_size = 100M
upload_max_filesize = 100M

; Error Reporting (Development)
display_errors = On
display_startup_errors = On
error_reporting = E_ALL
log_errors = On
error_log = /var/log/php_errors.log

; Opcache (Production)
opcache.enable = 1
opcache.enable_cli = 0
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 10000
opcache.revalidate_freq = 2
opcache.fast_shutdown = 1

; Session
session.save_handler = redis
session.save_path = "tcp://ml-redis:6379"

; Date
date.timezone = UTC
```

---

### 2. Redis Configuration (`docker/redis/redis.conf`)

```conf
# Network
bind 0.0.0.0
protected-mode no
port 6379

# Memory
maxmemory 512mb
maxmemory-policy allkeys-lru

# Persistence
save 900 1        # Save if 1 key changed in 900s
save 300 10       # Save if 10 keys changed in 300s
save 60 10000     # Save if 10000 keys changed in 60s
appendonly yes
appendfilename "appendonly.aof"

# Performance
tcp-backlog 511
timeout 0
tcp-keepalive 300
```

---

### 3. PostgreSQL Authentication (`docker/postgres/pg_hba.conf`)

```conf
# TYPE  DATABASE        USER            ADDRESS                 METHOD

# "local" is for Unix domain socket connections only
local   all             all                                     trust

# IPv4 local connections:
host    all             all             127.0.0.1/32            trust
host    all             all             0.0.0.0/0               md5

# IPv6 local connections:
host    all             all             ::1/128                 trust

# Docker network
host    all             all             172.16.0.0/12           trust
```

---

### 4. Nginx Configuration (`docker/nginx/default.conf`)

```nginx
server {
    listen 80;
    server_name localhost;

    # Dashboard (Next.js)
    location / {
        proxy_pass http://host.docker.internal:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # Documentation (Next.js)
    location /docs {
        proxy_pass http://host.docker.internal:3457;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_cache_bypass $http_upgrade;
    }

    # API (Laravel)
    location /api {
        proxy_pass http://ml-php:9000;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;

        # CORS
        add_header Access-Control-Allow-Origin *;
        add_header Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS";
        add_header Access-Control-Allow-Headers "Authorization, Content-Type";

        if ($request_method = 'OPTIONS') {
            return 204;
        }
    }

    # WebSocket (Reverb)
    location /ws {
        proxy_pass http://ml-reverb:8080;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

### 5. Next.js Configuration (`nextjs-fe/next.config.ts`)

```typescript
import type { NextConfig } from 'next';

const nextConfig: NextConfig = {
  reactStrictMode: true,
  
  // Webpack config
  webpack: (config, { isServer }) => {
    if (!isServer) {
      config.resolve.fallback = {
        ...config.resolve.fallback,
        fs: false,
      };
    }
    return config;
  },

  // Image optimization
  images: {
    remotePatterns: [
      {
        protocol: 'http',
        hostname: 'localhost',
        port: '9000',
        pathname: '/second-memory/**',
      },
    ],
  },

  // i18n
  i18n: {
    locales: ['en', 'vi'],
    defaultLocale: 'vi',
  },

  // Environment variables
  env: {
    API_URL: process.env.NEXT_PUBLIC_API_URL,
  },
};

export default nextConfig;
```

---

## 🚀 Scripts & Commands

### Docker Commands

```bash
# Start all services
cd docker && docker compose up -d

# Start specific service
docker compose up -d ml-postgres ml-redis

# Stop all services
docker compose down

# Stop and remove volumes (⚠️ Data loss)
docker compose down -v

# View logs
docker compose logs -f ml-php

# Restart service
docker compose restart ml-php

# Rebuild service
docker compose up -d --build ml-php
```

---

### Laravel Commands (trong container)

```bash
# Enter PHP container
docker exec -it ml-php bash

# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Generate app key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret
```

---

### Next.js Commands (local)

```bash
# Install dependencies
pnpm install

# Development
pnpm dev:fe        # Dashboard (port 3000)
pnpm dev:docs      # Documentation (port 3457)

# Build
pnpm build:all     # Build all Next.js apps

# Clean
pnpm clean         # Remove node_modules, .next, pnpm-lock.yaml
```

---

## 🔧 Troubleshooting

### Common Issues

#### 1. Port already in use

```bash
# Find process using port
lsof -i :5555

# Kill process
kill -9 <PID>
```

#### 2. Permission denied (Docker volumes)

```bash
# Fix ownership
sudo chown -R $USER:$USER laravel-api/storage
sudo chmod -R 775 laravel-api/storage
```

#### 3. Database connection refused

```bash
# Check if postgres is healthy
docker compose ps
docker compose logs ml-postgres

# Test connection
docker exec -it ml-postgres psql -U postgres -d second_memory
```

---

**Cập nhật lần cuối**: 2026-04-09
