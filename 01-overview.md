# 01. Tổng Quan Hệ Thống - Second Memory (PKMS)

> **Personal Knowledge Management System** - Hệ thống Quản trị Tri thức và Di sản Số Cá nhân

---

## 📖 Giới thiệu

**Second Memory** là một hệ thống quản lý tri thức cá nhân (PKMS - Personal Knowledge Management System) được xây dựng nhằm mục đích lưu trữ, tổ chức và khai thác toàn bộ kiến thức, kinh nghiệm, sở thích và suy nghĩ cá nhân một cách có hệ thống.

### Tầm nhìn (Vision)

Tạo ra một "Di sản số cá nhân" - một nền tảng hợp nhất để số hóa mọi mặt về bản thân, từ kinh nghiệm kỹ thuật đến các sở thích văn hóa, nghệ thuật. Hệ thống không chỉ là nơi lưu trữ mà còn là công cụ để suy nghĩ, chiêm nghiệm và tái sử dụng tri thức cho các dự án tương lai.

### Sứ mệnh (Mission)

- **Chống phân mảnh**: Tập trung toàn bộ tri thức từ nhiều nguồn khác nhau vào một nơi duy nhất
- **Bảo toàn tri thức**: Đảm bảo dữ liệu được lưu trữ an toàn, có khả năng phục hồi cao
- **Tái sử dụng kiến thức**: Giúp tìm kiếm nhanh chóng và áp dụng kinh nghiệm cũ cho các vấn đề mới
- **Tự động hóa**: Ứng dụng AI để hỗ trợ quản lý và khai thác tri thức hiệu quả

---

## 🎯 Vấn đề & Giải pháp

### Vấn đề hiện tại

1. **Sự phân mảnh dữ liệu**: Tài liệu rải rác trên nhiều nền tảng (Google Drive, Notion, Evernote, thiết bị cá nhân...)
2. **Thiếu chuẩn hóa**: Không có quy chuẩn thống nhất trong cách tổ chức và lưu trữ
3. **Khó tìm kiếm**: Mất nhiều thời gian để tìm lại thông tin đã lưu
4. **Không có version control**: Khó theo dõi sự thay đổi và lịch sử của tài liệu
5. **Phụ thuộc nền tảng**: Các công cụ thương mại có giới hạn, không linh hoạt theo nhu cầu cá nhân

### Giải pháp

Second Memory cung cấp một nền tảng tự quản lý (self-hosted) với các đặc điểm:

- ✅ **Tập trung hóa**: Một nguồn dữ liệu duy nhất (Single Source of Truth)
- ✅ **Linh hoạt**: Tùy chỉnh hoàn toàn theo nhu cầu cá nhân
- ✅ **An toàn**: Kiểm soát hoàn toàn dữ liệu, backup tự động
- ✅ **Có cấu trúc**: Hệ thống phân cấp rõ ràng với Category → Entry → Entry Description
- ✅ **Mạnh mẽ**: Sử dụng công nghệ hiện đại, khả năng mở rộng cao

---

## 🏗️ Kiến trúc Tổng quan (High-level)

Hệ thống được thiết kế theo mô hình 3 tầng (3-tier architecture):

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
                    │   PHP 8.3 + FPM   │
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

### Các thành phần chính

| Thành phần | Công nghệ | Port | Mô tả |
|:-----------|:----------|:-----|:------|
| **Dashboard** | Next.js 16 | 3000 | Giao diện quản trị cho Admin quản lý nội dung |
| **Documentation** | Next.js 16 | 3457 | Giao diện hiển thị tài liệu cho người dùng cuối |
| **API Backend** | Laravel 11 (PHP 8.3) | 9000 (FPM) | Xử lý logic nghiệp vụ, API endpoints |
| **Database** | PostgreSQL 16 | 5555 | Lưu trữ dữ liệu có cấu trúc |
| **Cache** | Redis 7 | 6379 | Cache và session management |
| **Object Storage** | MinIO | 9001 | Lưu trữ file, media (tương thích S3) |
| **WebSocket** | Laravel Reverb | 8080 | Real-time communication |
| **Queue Worker** | Laravel Queue | - | Xử lý background jobs |

---

## 📊 Dữ liệu & Cấu trúc

### Mô hình dữ liệu 3 tầng

```
Category (Danh mục)
  ├── name, slug, description
  ├── layout_structure (JSON) → quản lý Entry
  └── Entry (Menu/Đầu mục)
       ├── name, slug
       ├── layout_structure (JSON) → quản lý Entry Description
       └── Entry Description (Nội dung)
            ├── title, summary
            └── article (Tiptap JSON format)
```

### Đặc điểm kiến trúc dữ liệu

- **No Foreign Key**: Sử dụng JSON `layout_structure` thay vì Foreign Key truyền thống
- **Many-to-Many**: Một Entry có thể xuất hiện ở nhiều Category với cấu trúc khác nhau
- **Flexible Structure**: Hỗ trợ cấu trúc phẳng (flat) và phân cấp (nested) tối đa 5 cấp
- **History Tracking**: Mỗi bảng chính đều có bảng `_hist` tương ứng để lưu lịch sử thay đổi

---

## 🎨 Các tính năng chính

### 1. Quản lý Nội dung (Content Management)
- Tạo, sửa, xóa Category, Entry, Entry Description
- Rich text editor (Tiptap) với đầy đủ định dạng
- Upload và quản lý media files
- Drag & drop để sắp xếp thứ tự

### 2. Tìm kiếm (Search)
- Full-text search với inverted index
- Search theo ngữ nghĩa (semantic search)
- Autocomplete/typeahead suggestions
- Ranking algorithm (TF-IDF, BM25)

### 3. Backup & Restore
- Tự động backup định kỳ (PostgreSQL + MinIO + .env)
- Đồng bộ lên multi-cloud (Google Drive, OneDrive, AWS S3)
- Restore point với atomic consistency
- Zero-downtime deployment strategy

### 4. Phân quyền (Authorization)
- Role-based access control (RBAC)
- Admin panel riêng biệt
- JWT authentication
- Policy & Permission management

### 5. Real-time Features
- WebSocket với Laravel Reverb
- Live updates
- Collaborative editing (planned)

---

## 📈 Trạng thái Dự án

### ✅ Đã hoàn thành

- [x] Thiết kế kiến trúc hệ thống
- [x] Setup môi trường Docker hoàn chỉnh
- [x] Backend API với Laravel
- [x] Frontend Dashboard (Admin Panel)
- [x] Frontend Documentation (Public Docs)
- [x] Database schema & migrations
- [x] Backup & Restore automation
- [x] Basic CRUD operations
- [x] File upload & management

### 🚧 Đang thực hiện

- [ ] Tích hợp tìm kiếm nâng cao
- [ ] AI-powered features (RAG, LLM integration)
- [ ] Bơm dữ liệu từ các nguồn hiện có
- [ ] Performance optimization
- [ ] Testing & Documentation

### 🔮 Kế hoạch tương lai

- [ ] Mobile app (React Native)
- [ ] Browser extension
- [ ] API public cho third-party integration
- [ ] AI assistant được huấn luyện trên dữ liệu cá nhân
- [ ] Export CV/Portfolio tự động
- [ ] Graph visualization cho knowledge map

---

## 🛠️ Tech Stack Summary

| Layer | Technologies |
|:------|:-------------|
| **Frontend** | Next.js 16, React 19, TypeScript, TailwindCSS, Tiptap, Radix UI |
| **Backend** | Laravel 11, PHP 8.3, Composer |
| **Database** | PostgreSQL 16 |
| **Cache** | Redis 7 |
| **Storage** | MinIO (S3-compatible) |
| **Queue** | Laravel Queue + Redis |
| **WebSocket** | Laravel Reverb |
| **DevOps** | Docker, Docker Compose, pnpm workspace |
| **Backup** | Bash scripts, Rclone (multi-cloud sync) |

---

## 📚 Tài liệu liên quan

Bộ tài liệu này được tổ chức thành các phần riêng biệt:

- [02. Kiến trúc Hệ thống](./02-architecture.md)
- [03. Lựa chọn Công nghệ](./03-tech-stack.md)
- [04. Thiết kế Database](./04-database.md)
- [05. Cấu hình Hệ thống](./05-system-config.md)
- [06. Build & Deployment](./06-build-deployment.md)
- [07. Tính năng & Nghiệp vụ](./07-features.md)
- [08. Tổ chức Components](./08-component-organization.md)
- [09. Bảo mật & Backup](./09-security-backup.md)
- [10. Roadmap & Bài học](./10-roadmap-lessons.md)

---

## 👤 Thông tin

- **Tác giả**: vinhdv
- **Bắt đầu**: Giữa năm 2023
- **Trạng thái**: Active Development
- **License**: Private (Personal Use)

---

**Cập nhật lần cuối**: 2026-04-09
