# Second Memory - Personal Knowledge Management System (PKMS)

> Hệ thống Quản trị Tri thức và Di sản Số Cá nhân

[![PHP](https://img.shields.io/badge/PHP-8.3-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel&logoColor=white)](https://laravel.com/)
[![Next.js](https://img.shields.io/badge/Next.js-16-000000?logo=next.js&logoColor=white)](https://nextjs.org/)
[![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=white)](https://reactjs.org/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)](https://www.docker.com/)

---

## 📖 Giới thiệu

**Second Memory** là một hệ thống quản lý tri thức cá nhân (PKMS - Personal Knowledge Management System) được xây dựng nhằm mục đích lưu trữ, tổ chức và khai thác toàn bộ kiến thức, kinh nghiệm, sở thích và suy nghĩ cá nhân một cách có hệ thống.

> [!NOTE]
> Test update to verify GitHub Actions Runner functionality.

### 🎯 Tầm nhìn

Tạo ra một "Di sản số cá nhân" - một nền tảng hợp nhất để:
- ✅ **Chống phân mảnh tri thức**: Tập trung toàn bộ kiến thức từ nhiều nguồn vào một nơi duy nhất
- ✅ **Bảo toàn tri thức**: Lưu trữ an toàn, có khả năng phục hồi cao
- ✅ **Tái sử dụng kiến thức**: Tìm kiếm nhanh chóng và áp dụng kinh nghiệm cũ
- ✅ **Tự động hóa**: Ứng dụng AI để hỗ trợ quản lý tri thức hiệu quả

---

## 🏗️ Kiến trúc Hệ thống

### Mô hình Tổng quan

```
┌─────────────────────────────────────────────────────────────┐
│                      Users / Clients                        │
└─────────────────────────────────────────────────────────────┘
                              │
              ┌───────────────┴───────────────┐
              │                               │
    ┌─────────▼─────────┐         ┌──────────▼──────────┐
    │  Dashboard (FE)   │         │  Documentation (FE)  │
    │   Next.js :3000   │         │    Next.js :3457     │
    │   (Admin Panel)   │         │   (Public Docs)      │
    └─────────┬─────────┘         └──────────┬──────────┘
              │                               │
              └───────────────┬───────────────┘
                              │
                    ┌─────────▼─────────┐
                    │   Laravel API     │
                    │   PHP 8.5 + FPM   │
                    │   (Business Logic)│
                    └─────────┬─────────┘
                              │
              ┌───────────────┼───────────────┐
              │               │               │
    ┌─────────▼─────┐  ┌──────▼──────┐  ┌────▼─────┐
    │  PostgreSQL   │  │    Redis    │  │  MinIO   │
    │   (Database)  │  │   (Cache)   │  │ (S3 Obj) │
    └───────────────┘  └─────────────┘  └──────────┘
```

### Các Thành phần Chính

| Thành phần | Công nghệ | Port | Mô tả |
|:-----------|:----------|:-----|:------|
| **Dashboard** | Next.js 16 + React 19 | 3000 | Giao diện quản trị (Admin Panel) |
| **Documentation** | Next.js 16 + React 19 | 3457 | Giao diện hiển thị tài liệu công khai |
| **API Backend** | Laravel 13 (PHP 8.5) | 9000 | RESTful API, Business Logic |
| **Database** | PostgreSQL 16 Alpine | 5555 | Lưu trữ dữ liệu có cấu trúc |
| **Cache** | Redis 7 Alpine | 6379 | Cache và Session Management |
| **Object Storage** | MinIO | 9001 | S3-compatible File/Media Storage |
| **WebSocket** | Laravel Reverb | 8080 | Real-time Communication |
| **Proxy** | Nginx Alpine | 80/443 | Reverse Proxy, SSL Termination |

---

## 🚀 Tech Stack

### Frontend

- **Framework**: Next.js 16 (App Router) + React 19
- **Language**: TypeScript 5.x
- **Styling**: TailwindCSS 4.x
- **UI Components**: Radix UI (Headless)
- **Rich Text Editor**: Tiptap 3.x (ProseMirror)
- **State Management**: Redux Toolkit 2.10 + React Query 5.90
- **HTTP Client**: Axios 1.13
- **i18n**: next-intl 4.7
- **Testing**: Vitest 1.0

### Backend

- **Framework**: Laravel 13
- **Language**: PHP 8.5
- **Database ORM**: Eloquent
- **Authentication**: Laravel Sanctum (SPA session cookie + CSRF)
- **Queue**: Laravel Queue (Redis driver)
- **WebSocket**: Laravel Reverb
- **Real-time Broadcasting**: Reverb + Redis

### Database & Storage

- **RDBMS**: PostgreSQL 16 Alpine
- **Cache**: Redis 7 Alpine
- **Object Storage**: MinIO (S3-compatible)
- **Backup**: Rclone (multi-cloud sync)

### DevOps

- **Containerization**: Docker + Docker Compose v2
- **Web Server**: Nginx Alpine
- **Package Manager**: pnpm (monorepo workspace)
- **Build Tool**: Webpack (via Next.js), Vite (optional)
- **CI/CD**: GitHub Actions
- **Deployment Strategy**: Blue-Green Deployment
- **Infrastructure**: Self-hosted GitHub Runner on Ubuntu VM
- **Traffic Management**: Nginx Reverse Proxy (Dynamic Upstreams)

---

## 🚀 CI/CD & Deployment

Dự án sử dụng quy trình CI/CD hiện đại để đảm bảo tính ổn định và khả năng mở rộng:

### 1. CI Pipeline (Continuous Integration)
- **Trigger**: Khi có Pull Request merge vào branch `dev`.
- **Nhiệm vụ**:
    - Linting & Code Style check.
    - Type-checking (TypeScript).
    - Security Audit (Dependencies).
    - Unit & Integration Testing.

### 2. CD Pipeline (Continuous Deployment)
- **Trigger**: Khi code được push/merge thành công vào branch `dev`.
- **Quy trình**:
    - Build Docker images trên GitHub hosted runner.
    - Push images lên GitHub Container Registry (GHCR).
    - Kích hoạt deployment trên **Self-hosted Runner** (Ubuntu VM).

### 3. Chiến lược Blue-Green Deployment
Hệ thống sử dụng chiến lược Blue-Green để đạt được zero-downtime:
- **Môi trường song song**: Duy trì hai môi trường `blue` và `green` độc lập.
- **Traffic Switching**: Sử dụng Nginx để chuyển đổi lưu lượng giữa hai môi trường.
- **Health Check**: Tự động kiểm tra trạng thái dịch vụ trước khi switch traffic. Nếu không đạt yêu cầu, hệ thống sẽ giữ nguyên version cũ (Auto Rollback).
- **Cleanup**: Tự động dọn dẹp tài nguyên của version cũ sau khi deploy thành công để tối ưu hóa tài nguyên máy chủ.

---

## 📁 Cấu trúc Thư mục

```
second-memory/
├── docker/                    # Docker configuration & scripts
│   ├── docker-compose.yml     # Main orchestration file
│   ├── laravel/               # Laravel Dockerfile & config
│   ├── nextjs/                # Next.js Dockerfile & config
│   ├── nginx/                 # Nginx configuration
│   ├── postgres/              # PostgreSQL configuration
│   ├── redis/                 # Redis configuration
│   └── minio/                 # MinIO configuration & scripts
│
├── laravel-api/               # Backend API (Laravel 13)
│   ├── app/                   # Application code
│   │   ├── Http/Controllers   # API Controllers
│   │   ├── Models/            # Eloquent Models
│   │   ├── Services/          # Business Logic
│   │   ├── Repositories/      # Data Access Layer
│   │   └── ...
│   ├── config/                # Configuration files
│   ├── database/              # Migrations, Seeders
│   ├── routes/                # API routes
│   ├── tests/                 # Unit & Feature tests
│   └── composer.json          # PHP dependencies
│
├── nextjs-fe/                 # Frontend Dashboard (Next.js)
│   ├── src/
│   │   ├── app/               # App Router pages
│   │   ├── components/        # React Components
│   │   ├── lib/               # Utilities, API clients
│   │   └── store/             # Redux store
│   ├── public/                # Static assets
│   └── package.json           # Node dependencies
│
├── nextjs-docs/               # Documentation Site (Next.js)
│   ├── src/
│   │   ├── app/               # Documentation pages
│   │   └── components/        # Doc-specific components
│   └── package.json
│
├── backup/                    # Backup scripts & documentation
│   ├── backup.sh              # Automated backup script
│   └── restore.sh             # Restore script
│
├── docs/                      # Engineering Lab docs: plan, ADRs, handbook, archive (see docs/README.md)
│
├── pnpm-workspace.yaml        # pnpm monorepo config
├── package.json               # Root package.json
├── setup-env.sh               # Environment setup script
└── start.sh                   # Quick start script
```

---

## ⚡ Quick Start

### Yêu cầu hệ thống

- **Docker**: 20.x+ và Docker Compose v2
- **pnpm**: 8.x+ (cho frontend development)
- **Memory**: Tối thiểu 8GB RAM (khuyến nghị 16GB)
- **Disk**: Tối thiểu 20GB trống

### 1. Clone Repository

```bash
git clone <repository-url>
cd second-memory
```

### 2. Setup Environment

```bash
# Chạy script setup tự động
bash setup-env.sh
```

Hoặc setup thủ công:

```bash
# Tạo file .env cho Laravel
cd laravel-api
cp .env.example .env
# Chỉnh sửa .env theo môi trường

# Tạo file .env cho Next.js
cd ../nextjs-fe
cp .env.example .env.local
# Chỉnh sửa .env.local

cd ../nextjs-docs
cp .env.example .env.local
```

### 3. Build & Start Services

```bash
# Quay về root directory
cd ..

# Tạo env (lần đầu), build và start tất cả services
make up
```

`bash start.sh` vẫn dùng được (gọi `make up`). Xem toàn bộ lệnh bằng `make help`.

### 4. Initialize Database

```bash
# Chạy migrations
make migrate

# (Optional) Seed dữ liệu mẫu
docker exec ml-php php artisan db:seed

# Hoặc xóa toàn bộ dev DB, migrate lại và seed (hỏi xác nhận trước)
make fresh
```

### 5. Access Applications

| Service | URL | Credentials |
|:--------|:----|:------------|
| **Dashboard** | http://localhost:3000 | Admin panel |
| **Documentation** | http://localhost:3457 | Public docs |
| **API** | http://localhost/api | - |
| **MinIO Console** | http://localhost:9001 | Set in `.env` |
| **PostgreSQL** | localhost:5555 | Set in `.env` |
| **Redis** | localhost:6379 | - |

---

## 🛠️ Development

### Frontend Development

```bash
# Dashboard (nextjs-fe)
pnpm --filter nextjs-fe dev        # http://localhost:3000

# Documentation (nextjs-docs)
pnpm --filter nextjs-docs dev      # http://localhost:3457
```

### Backend Development

```bash
# Chạy Laravel development server (nếu không dùng Docker)
cd laravel-api
php artisan serve

# Watch queue jobs
php artisan queue:work

# Run tests (trong Docker)
make test
```

### Quality checks

```bash
make lint      # Pint + ESLint + Prettier
make analyse   # Larastan
make verify    # toàn bộ những gì CI chạy
```

### Database Migrations

```bash
# Tạo migration mới
php artisan make:migration create_example_table

# Chạy migrations
php artisan migrate

# Rollback
php artisan migrate:rollback

# Fresh migrate (xóa toàn bộ và chạy lại)
php artisan migrate:fresh --seed
```

---

## 🎯 Features

### ✅ Đã hoàn thành

- ✅ **Content Management System**
  - CRUD Category, Entry, Entry Description
  - Hierarchical structure (Category → Entry → Description)
  - Rich text editor (Tiptap) với markdown support
  
- ✅ **Authentication & Authorization**
  - Session authentication (Laravel Sanctum SPA)
  - Two roles: `owner` (read + write) and `viewer` (read-only), see ADR-0005
  - Admin, Editor, Viewer roles
  
- ✅ **Media Management**
  - Upload files to MinIO (S3-compatible)
  - Image optimization
  - File organization & categorization
  
- ✅ **Backup & Restore**
  - Automated backup scripts
  - Multi-cloud sync (Rclone)
  - Database + file backup
  
- ✅ **Real-time Features**
  - WebSocket với Laravel Reverb
  - Real-time notifications (basic)

### 🚧 Đang phát triển

- 🚧 **Full-text Search**
  - PostgreSQL Full-text Search
  - Autocomplete suggestions
  - Advanced search filters

### 📋 Roadmap

- 📋 **AI Integration**
  - RAG (Retrieval-Augmented Generation)
  - LLM integration cho knowledge extraction
  - Smart tagging & categorization
  
- 📋 **Mobile App**
  - React Native app
  - Offline-first architecture
  
- 📋 **Advanced Analytics**
  - Knowledge graph visualization
  - Usage statistics
  - Content recommendations

---

## 📚 Documentation

Start at [docs/README.md](docs/README.md): plan, roadmap, ADRs and templates. The original architecture docs (Vietnamese, partly outdated) are archived in [docs/archive/legacy-architecture/](docs/archive/legacy-architecture/); the current snapshot is [docs/architecture/as-is.md](docs/architecture/as-is.md).

---

## 🧪 Testing

### Frontend Tests

```bash
# Run Vitest
pnpm --filter nextjs-fe test

# Watch mode
pnpm --filter nextjs-fe test:watch

# Coverage
pnpm --filter nextjs-fe test:coverage
```

### Backend Tests

```bash
cd laravel-api

# Run all tests
php artisan test

# Run specific test suite
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage
php artisan test --coverage
```

---

## 🔒 Security

- ✅ Session authentication (Sanctum SPA cookie + CSRF), login rate limiting
- ✅ CORS configuration
- ✅ Rate limiting (Laravel)
- ✅ SQL injection protection (Eloquent ORM)
- ✅ XSS protection (Laravel Blade escaping)
- ✅ CSRF protection
- ✅ Environment variables cho sensitive data
- ✅ Docker isolation

---

## 📦 Backup & Restore

### Automated Backup

```bash
# Chạy backup script
bash backup/backup.sh
```

Script sẽ backup:
- PostgreSQL database dump
- MinIO object storage
- Application files
- Sync to cloud storage (Google Drive, S3, etc.)

### Restore

```bash
# Restore từ backup
bash backup/restore.sh <backup-date>
```

---

## 🐛 Troubleshooting

### Container không start

```bash
# Check logs
docker-compose logs <service-name>

# Restart service
docker-compose restart <service-name>

# Rebuild
docker-compose up -d --build --force-recreate
```

### Database connection error

```bash
# Check PostgreSQL đang chạy
docker-compose ps postgres

# Test connection
docker exec -it postgres psql -U <username> -d <database>
```

### Frontend không kết nối API

- Kiểm tra `NEXT_PUBLIC_API_URL` trong `.env.local`
- Verify CORS configuration trong Laravel `config/cors.php`
- Check network trong Docker Compose

---

## 🤝 Contributing

Dự án hiện tại là personal project, chưa mở cho public contribution.

---

## 📝 License

Proprietary - Personal Use Only

---

## 👤 Author

**Vinh DV**

- GitHub: [@vinhdv](https://github.com/vinhdv)

---

## 📞 Support

Nếu gặp vấn đề, vui lòng:
1. Check [Documentation](docs/)
2. Review [Troubleshooting](#troubleshooting)
3. Check Docker logs

---

## 🙏 Acknowledgments

- Laravel Team - Amazing PHP framework
- Next.js Team - Best React framework
- Tiptap - Excellent rich text editor
- Open Source Community

---

<p align="center">Made with ❤️ by Vinh DV</p>
