# 06. Build & Deployment - Quy trình Build và Triển khai

> Quy trình build, CI/CD (planned), và deployment strategy

---

## 🏗️ Build Process

### 1. Build Laravel API (Backend)

#### Development Build

```bash
cd laravel-api

# Install dependencies
composer install

# Generate app key
php artisan key:generate

# Generate JWT secret
php artisan jwt:secret

# Run migrations
php artisan migrate

# (Optional) Seed database
php artisan db:seed

# Link storage
php artisan storage:link

# Clear cache
php artisan optimize:clear
```

#### Production Build

```bash
# Install dependencies (no dev)
composer install --no-dev --optimize-autoloader

# Cache config
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Optimize
php artisan optimize
```

**Output**: Vendor dependencies + optimized autoloader

---

### 2. Build Next.js Frontend

#### Dashboard (nextjs-fe)

```bash
cd nextjs-fe

# Install dependencies
pnpm install

# Build for production
pnpm build

# Start production server
pnpm start
```

**Output**: `.next/` folder với static + server bundles

#### Documentation (nextjs-docs)

```bash
cd nextjs-docs

# Install dependencies
pnpm install

# Build for production
pnpm build

# Start production server
pnpm start -p 3457
```

---

### 3. Docker Build

#### Build Images

```bash
cd docker

# Build all services
docker compose build

# Build specific service
docker compose build ml-php

# Build with no cache
docker compose build --no-cache ml-php
```

#### Multi-stage Laravel Dockerfile

```dockerfile
# Stage 1: Development
FROM php:8.3-fpm-alpine AS development

RUN apk add --no-cache postgresql-dev redis \
    && docker-php-ext-install pdo pdo_pgsql

WORKDIR /var/www/laravel-api

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

COPY . .

RUN composer install

EXPOSE 9000

CMD ["php-fpm"]

# Stage 2: Production
FROM php:8.3-fpm-alpine AS production

RUN apk add --no-cache postgresql-dev \
    && docker-php-ext-install pdo pdo_pgsql opcache

WORKDIR /var/www/laravel-api

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
COPY . .

RUN composer install --no-dev --optimize-autoloader \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache

EXPOSE 9000

CMD ["php-fpm"]
```

---

## 🚀 Deployment Strategy

### Current: Single Server (Development)

```
Development Machine
└── Docker Compose
    ├── PostgreSQL
    ├── Redis
    ├── MinIO
    ├── Laravel (PHP-FPM)
    ├── Laravel Reverb
    └── Queue Worker

+ Local pnpm workspace
    ├── nextjs-fe (dev server)
    └── nextjs-docs (dev server)
```

**Deployment Steps**:

1. **Start infrastructure**:
   ```bash
   cd docker
   docker compose up -d ml-postgres ml-redis ml-minio ml-minio-init
   ```

2. **Start Laravel services**:
   ```bash
   docker compose up -d ml-php ml-reverb ml-queue
   ```

3. **Start frontend (local)**:
   ```bash
   pnpm dev:fe    # Dashboard :3000
   pnpm dev:docs  # Documentation :3457
   ```

4. **Access**:
   - Dashboard: http://localhost:3000
   - Documentation: http://localhost:3457
   - API: http://localhost:8000/api
   - MinIO Console: http://localhost:9001

---

### Planned: Production Deployment

#### Option 1: Docker Swarm (Single/Multiple Servers)

```yaml
version: '3.8'
services:
  ml-php:
    deploy:
      replicas: 3              # 3 instances
      update_config:
        parallelism: 1
        delay: 10s
      restart_policy:
        condition: on-failure
```

**Pros**:
- Native Docker, simple
- Built-in load balancing
- Rolling updates

**Cons**:
- Less features than Kubernetes
- Smaller community

---

#### Option 2: Kubernetes (Scalable)

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: laravel-api
spec:
  replicas: 3
  selector:
    matchLabels:
      app: laravel
  template:
    metadata:
      labels:
        app: laravel
    spec:
      containers:
      - name: php-fpm
        image: secondmemory/laravel-api:latest
        ports:
        - containerPort: 9000
        env:
        - name: DB_HOST
          value: postgres-service
```

**Pros**:
- Industry standard
- Auto-scaling
- Self-healing
- Huge ecosystem

**Cons**:
- Complex setup
- Overkill for small projects

---

#### Option 3: VPS + Docker Compose (Recommended)

Đơn giản nhất cho MVP:

```
VPS (Ubuntu 22.04)
├── Nginx (reverse proxy, SSL)
├── Docker Compose
│   ├── PostgreSQL + Redis + MinIO
│   ├── Laravel services
│   └── Next.js (production build)
├── Rclone (backup to cloud)
└── Systemd (auto-restart services)
```

**Setup Steps**:

1. **Provision VPS** (DigitalOcean, Linode, AWS EC2...)

2. **Install Docker**:
   ```bash
   curl -fsSL https://get.docker.com -o get-docker.sh
   sh get-docker.sh
   ```

3. **Clone repository**:
   ```bash
   git clone <repo_url>
   cd second-memory
   ```

4. **Configure environment**:
   ```bash
   cp .env.example .env
   # Edit .env với production values
   ```

5. **Build và start**:
   ```bash
   cd docker
   docker compose -f docker-compose.prod.yml up -d --build
   ```

6. **Setup Nginx** (reverse proxy):
   ```nginx
   server {
       listen 80;
       server_name yourdomain.com;

       location / {
           proxy_pass http://localhost:3000;
           # Next.js Dashboard
       }

       location /api {
           proxy_pass http://localhost:8000;
           # Laravel API
       }
   }
   ```

7. **Setup SSL** (Let's Encrypt):
   ```bash
   sudo certbot --nginx -d yourdomain.com
   ```

8. **Setup auto-backup** (cronjob):
   ```bash
   crontab -e
   # Add: 0 2 * * * /path/to/backup.sh
   ```

---

## 🔄 CI/CD Pipeline (Planned)

### GitHub Actions Workflow

File: `.github/workflows/deploy.yml`

```yaml
name: Deploy to Production

on:
  push:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
      
      - name: Install dependencies
        run: composer install
        working-directory: ./laravel-api
      
      - name: Run tests
        run: php artisan test
        working-directory: ./laravel-api
  
  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Build Docker images
        run: |
          docker build -t secondmemory/laravel-api:latest ./laravel-api
          docker build -t secondmemory/nextjs-fe:latest ./nextjs-fe
      
      - name: Push to Docker Hub
        run: |
          echo ${{ secrets.DOCKER_PASSWORD }} | docker login -u ${{ secrets.DOCKER_USERNAME }} --password-stdin
          docker push secondmemory/laravel-api:latest
          docker push secondmemory/nextjs-fe:latest
  
  deploy:
    needs: build
    runs-on: ubuntu-latest
    steps:
      - name: Deploy to VPS
        uses: appleboy/ssh-action@master
        with:
          host: ${{ secrets.VPS_HOST }}
          username: ${{ secrets.VPS_USERNAME }}
          key: ${{ secrets.VPS_SSH_KEY }}
          script: |
            cd /var/www/second-memory
            docker compose pull
            docker compose up -d --force-recreate
            docker system prune -f
```

**Stages**:
1. **Test**: Run PHPUnit tests
2. **Build**: Build Docker images
3. **Push**: Push to Docker Hub
4. **Deploy**: SSH to VPS, pull images, restart containers

---

## 📦 Release Management

### Versioning Strategy

Sử dụng **Semantic Versioning** (SemVer):

```
v<MAJOR>.<MINOR>.<PATCH>
v1.0.0
```

- **MAJOR**: Breaking changes (v1 → v2)
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes

### Git Branching Strategy

```
main (production)
  └── develop (staging)
       ├── feature/user-auth
       ├── feature/search
       └── bugfix/cache-issue
```

**Flow**:
1. Create feature branch: `git checkout -b feature/ai-integration`
2. Develop & commit
3. Merge to `develop`: `git merge feature/ai-integration`
4. Test on staging
5. Merge to `main`: `git merge develop`
6. Tag release: `git tag v1.1.0`
7. Deploy to production

---

## 🔧 Build Optimization

### Laravel Production Optimizations

```bash
# Optimize autoloader
composer install --optimize-autoloader --no-dev

# Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Enable OPcache (php.ini)
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0  # No revalidation in production
```

### Next.js Production Optimizations

```typescript
// next.config.ts
const nextConfig = {
  // Generate standalone output (smaller)
  output: 'standalone',
  
  // Compress images
  images: {
    formats: ['image/avif', 'image/webp'],
    minimumCacheTTL: 60,
  },
  
  // Enable SWC minification
  swcMinify: true,
  
  // Disable source maps in production
  productionBrowserSourceMaps: false,
};
```

---

## 🌐 Zero-Downtime Deployment

### Blue-Green Deployment Strategy

```
┌─────────────────────────────────────────┐
│         Load Balancer (Nginx)           │
└─────────────────────────────────────────┘
              │
      ┌───────┴───────┐
      │               │
┌─────▼─────┐   ┌─────▼─────┐
│  Blue Env │   │ Green Env │
│ (Current) │   │   (New)   │
└───────────┘   └───────────┘
```

**Steps**:

1. Deploy new version to Green environment
2. Test Green environment
3. Switch load balancer từ Blue → Green
4. Monitor for issues
5. If OK: Decommission Blue
6. If Error: Rollback to Blue

**Implementation**:

```nginx
# Nginx upstream
upstream backend {
    server green-app:9000;  # Switch này để cutover
    # server blue-app:9000;
}

server {
    location / {
        proxy_pass http://backend;
    }
}
```

---

## 📊 Monitoring & Health Checks

### Health Check Endpoints

#### Laravel

```php
// routes/web.php
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
        'redis' => Redis::ping() ? 'connected' : 'disconnected',
        'timestamp' => now(),
    ]);
});
```

#### Docker Healthchecks

```yaml
healthcheck:
  test: ["CMD", "curl", "-f", "http://localhost/health"]
  interval: 30s
  timeout: 10s
  retries: 3
  start_period: 40s
```

---

## 🔐 Security Checklist (Pre-deployment)

- [ ] Change all default passwords
- [ ] Generate strong `APP_KEY` và `JWT_SECRET`
- [ ] Set `APP_DEBUG=false` in production
- [ ] Enable HTTPS/SSL
- [ ] Configure firewall (only expose ports 80, 443)
- [ ] Set up fail2ban (brute force protection)
- [ ] Regular security updates (`apt update && apt upgrade`)
- [ ] Backup encryption keys securely
- [ ] Configure CORS properly
- [ ] Rate limiting on API endpoints
- [ ] SQL injection protection (Eloquent ORM)
- [ ] XSS protection (Laravel blade escaping)

---

## 📚 Deployment Checklist

### Pre-deployment

- [ ] Run all tests locally
- [ ] Review code changes
- [ ] Update CHANGELOG.md
- [ ] Create git tag (version)
- [ ] Backup current database
- [ ] Notify team về deployment window

### During Deployment

- [ ] Enable maintenance mode: `php artisan down`
- [ ] Pull latest code / Pull Docker images
- [ ] Run migrations: `php artisan migrate --force`
- [ ] Clear caches
- [ ] Restart services
- [ ] Run smoke tests
- [ ] Disable maintenance mode: `php artisan up`

### Post-deployment

- [ ] Monitor error logs
- [ ] Check health endpoints
- [ ] Verify critical features work
- [ ] Monitor performance metrics
- [ ] Update documentation (if needed)
- [ ] Notify team thành công/rollback

---

**Cập nhật lần cuối**: 2026-04-09
