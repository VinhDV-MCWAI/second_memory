# 03. Lựa Chọn Công Nghệ - Technology Stack

> Chi tiết về các công nghệ được sử dụng, lý do lựa chọn và các phương án thay thế

---

## 📋 Bảng Tổng hợp Tech Stack

### Frontend Technologies

| Công nghệ | Phiên bản | Lý do chọn | Phương án thay thế | Lý do KHÔNG chọn thay thế |
|:----------|:----------|:-----------|:-------------------|:--------------------------|
| **Next.js** | 16.0.1 | - SSR/SSG out of the box<br/>- App Router mới nhất<br/>- Performance optimization tự động<br/>- Developer experience tốt | **Remix**, **Nuxt.js** | - Remix: Ecosystem nhỏ hơn<br/>- Nuxt: Yêu cầu Vue (đã quen React) |
| **React** | 19.2.0 | - Ecosystem lớn nhất<br/>- Component reusability<br/>- Concurrent features<br/>- Kinh nghiệm nhiều năm | **Vue 3**, **Svelte** | - Vue: Learning curve mới<br/>- Svelte: Ecosystem còn nhỏ, ít thư viện |
| **TypeScript** | 5.x | - Type safety<br/>- Better IntelliSense<br/>- Catch bugs sớm<br/>- Chuẩn mực industry | **JavaScript** | - JS: Dễ bugs runtime, khó maintain code lớn |
| **TailwindCSS** | 4.x | - Utility-first, rapid development<br/>- Consistency dễ dàng<br/>- Tree-shaking tốt<br/>- Customization linh hoạt | **Material-UI**, **Ant Design**, **Bootstrap** | - MUI/Ant: Bundle size lớn, customize khó<br/>- Bootstrap: Thiết kế generic |
| **Tiptap** | 3.20.1 | - Modern WYSIWYG editor<br/>- Dựa trên ProseMirror (robust)<br/>- Highly customizable<br/>- TypeScript support | **Slate**, **Draft.js**, **CKEditor** | - Slate: API phức tạp<br/>- Draft: Facebook deprecated<br/>- CKEditor: Commercial license |
| **Radix UI** | Latest | - Headless components<br/>- Accessibility built-in<br/>- Unstyled (tự style bằng Tailwind)<br/>- Keyboard navigation | **Headless UI**, **React Aria** | - Headless UI: Ít component hơn<br/>- React Aria: Phức tạp hơn |
| **Redux Toolkit** | 2.10.1 | - Global state management<br/>- DevTools mạnh mẽ<br/>- RTK Query (optional) | **Zustand**, **Jotai**, **Recoil** | - Đã quen Redux, đủ mạnh cho project này |
| **React Query** | 5.90.10 | - Server state management<br/>- Auto caching, refetching<br/>- Optimistic updates<br/>- Request deduplication | **SWR**, **Apollo Client** | - SWR: Ít feature hơn<br/>- Apollo: Chỉ tốt cho GraphQL |
| **Axios** | 1.13.2 | - Interceptors (auth token)<br/>- Request/response transformation<br/>- Cancel requests<br/>- Kinh nghiệm sử dụng lâu | **Fetch API**, **ky** | - Fetch: Không có interceptors<br/>- ky: Ít được sử dụng |
| **next-intl** | 4.7.0 | - i18n cho Next.js App Router<br/>- Server & client components<br/>- Type-safe translations | **next-i18next**, **react-intl** | - next-i18next: Chưa support App Router tốt<br/>- react-intl: Không tối ưu cho Next.js |
| **Vitest** | 1.0.4 | - Vite-powered, rất nhanh<br/>- Jest-compatible API<br/>- ESM support native | **Jest** | - Jest: Chậm hơn, config phức tạp với ESM |

### Backend Technologies

| Công nghệ | Phiên bản | Lý do chọn | Phương án thay thế | Lý do KHÔNG chọn thay thế |
|:----------|:----------|:-----------|:-------------------|:--------------------------|
| **PHP** | 8.3 | - Modern PHP (enum, attributes, JIT)<br/>- Performance tốt<br/>- Kinh nghiệm nhiều năm<br/>- Ecosystem trưởng thành | **Node.js**, **Python**, **Go** | - Node: Callback hell (nếu không cẩn thận)<br/>- Python: Chậm hơn PHP 8.x<br/>- Go: Learning curve cao |
| **Laravel** | 11.34.2 | - Full-featured framework<br/>- Eloquent ORM mạnh mẽ<br/>- Middleware, Events, Jobs đầy đủ<br/>- Best practices built-in<br/>- Documentation xuất sắc | **Symfony**, **CodeIgniter**, **Slim** | - Symfony: Phức tạp hơn, overkill<br/>- CodeIgniter: Ít feature<br/>- Slim: Micro framework, phải tự build nhiều |
| **Composer** | Latest | - Dependency management chuẩn PHP<br/>- Autoloading PSR-4<br/>- Lock file đảm bảo consistency | **PEAR** (cũ) | - PEAR: Deprecated, không ai dùng |
| **Laravel Reverb** | Beta | - Official WebSocket server (Laravel 11+)<br/>- Native integration<br/>- Scaling dễ dàng<br/>- Redis pub/sub support | **Pusher**, **Socket.io**, **Laravel WebSockets** | - Pusher: Commercial, expensive<br/>- Socket.io: Cần Node.js server riêng<br/>- Laravel WebSockets: Không còn maintain tích cực |
| **JWT (Firebase)** | 6.10.2 | - Stateless authentication<br/>- Cross-domain support<br/>- Mobile app friendly | **Laravel Sanctum**, **Passport** | - Sanctum: Session-based (cần cookie)<br/>- Passport: OAuth2, overkill cho use case này |

### Database & Storage

| Công nghệ | Phiên bản | Lý do chọn | Phương án thay thế | Lý do KHÔNG chọn thay thế |
|:----------|:----------|:-----------|:-------------------|:--------------------------|
| **PostgreSQL** | 16 Alpine | - ACID compliance<br/>- JSON/JSONB support (flexible)<br/>- Full-text search built-in<br/>- Advanced indexing (GIN, GiST)<br/>- Free & open-source | **MySQL**, **MariaDB**, **MongoDB** | - MySQL: Ít feature hơn (ví dụ: JSONB)<br/>- MariaDB: Tương tự MySQL<br/>- MongoDB: NoSQL, khó query phức tạp |
| **Redis** | 7 Alpine | - In-memory = super fast<br/>- Data structures (hash, list, set)<br/>- Pub/Sub cho real-time<br/>- Session & cache tốt | **Memcached**, **Valkey** | - Memcached: Chỉ key-value đơn giản<br/>- Valkey: Fork mới, ít được test |
| **MinIO** | Latest | - S3-compatible API<br/>- Self-hosted (data sovereignty)<br/>- Versioning, lifecycle policies<br/>- Performance cao | **AWS S3**, **Local filesystem**, **Cloudinary** | - S3: Commercial, phụ thuộc cloud<br/>- Filesystem: Không scalable, khó backup<br/>- Cloudinary: Expensive, vendor lock-in |

### DevOps & Infrastructure

| Công nghệ | Phiên bản | Lý do chọn | Phương án thay thế | Lý do KHÔNG chọn thay thế |
|:----------|:----------|:-----------|:-------------------|:--------------------------|
| **Docker** | Latest | - Container standard<br/>- Reproducible environments<br/>- Isolation tốt<br/>- Dễ deploy anywhere | **Podman**, **LXC** | - Podman: Ít được hỗ trợ<br/>- LXC: Low-level, config phức tạp |
| **Docker Compose** | v2 | - Multi-container orchestration<br/>- YAML config đơn giản<br/>- Development-friendly<br/>- Health checks support | **Kubernetes**, **Docker Swarm** | - K8s: Overkill cho single-server<br/>- Swarm: Ít được sử dụng hơn K8s |
| **pnpm** | Latest | - Disk space efficient (symlinks)<br/>- Workspaces support<br/>- Faster than npm/yarn<br/>- Strict dependency resolution | **npm**, **yarn**, **bun** | - npm: Chậm, disk space tốn<br/>- yarn: Berry version phức tạp<br/>- bun: Quá mới, chưa stable |
| **Nginx** | Alpine | - High performance<br/>- Reverse proxy tốt<br/>- SSL/TLS termination<br/>- Low resource usage | **Apache**, **Caddy**, **Traefik** | - Apache: Chậm hơn<br/>- Caddy: Auto SSL tốt nhưng ít được dùng hơn<br/>- Traefik: Phù hợp K8s hơn |
| **Rclone** | Latest | - Multi-cloud sync<br/>- Incremental backup<br/>- Encryption support<br/>- Bandwidth control | **rsync**, **Restic**, **Duplicati** | - rsync: Chỉ local/SSH<br/>- Restic: Khó config<br/>- Duplicati: GUI-based, không phù hợp automation |

### Build Tools & Package Managers

| Công nghệ | Phiên bản | Lý do chọn | Phương án thay thế | Lý do KHÔNG chọn thay thế |
|:----------|:----------|:-----------|:-------------------|:--------------------------|
| **Webpack** | via Next.js | - Mature, stable<br/>- Plugin ecosystem lớn<br/>- Next.js built-in | **Vite**, **Turbopack** | - Vite: Chưa default trong Next.js<br/>- Turbopack: Beta, chưa stable |
| **PostCSS** | 4.x | - TailwindCSS requirement<br/>- Autoprefixer<br/>- CSS optimization | **Sass**, **Less** | - Sass/Less: Không cần với Tailwind |
| **ESLint** | 9.x | - Code quality<br/>- Catch bugs sớm<br/>- Team consistency | **TSLint** (deprecated) | - TSLint: Merged vào ESLint |
| **Prettier** | 3.6.2 | - Code formatting tự động<br/>- Consistency 100%<br/>- IDE integration | **Beautify** | - Beautify: Ít feature, ít được maintain |

---

## 🎯 Phân tích Chi tiết theo Category

### 1. Frontend Framework: Next.js + React

**Tại sao chọn Next.js thay vì SPA thuần?**

| Yếu tố | SPA (Create React App) | Next.js | Winner |
|:-------|:----------------------|:--------|:-------|
| **SEO** | ❌ Client-side only | ✅ SSR/SSG | Next.js |
| **Initial Load** | ❌ Chậm (large bundle) | ✅ Nhanh (code splitting) | Next.js |
| **Routing** | ⚠️ React Router (thủ công) | ✅ File-based (tự động) | Next.js |
| **API Routes** | ❌ Cần backend riêng | ✅ Built-in | Next.js |
| **Image Optimization** | ❌ Manual | ✅ Next/Image component | Next.js |
| **TypeScript** | ⚠️ Manual setup | ✅ Zero-config | Next.js |
| **Deployment** | ⚠️ Static hosting only | ✅ Vercel/Docker đều được | Next.js |

**Kết luận**: Next.js thắng áp đảo, đặc biệt với App Router mới.

---

### 2. Backend Framework: Laravel vs Express.js vs FastAPI

| Yếu tố | Laravel (PHP) | Express.js (Node) | FastAPI (Python) |
|:-------|:--------------|:------------------|:-----------------|
| **ORM** | ✅ Eloquent (tuyệt vời) | ⚠️ Sequelize/Prisma | ✅ SQLAlchemy |
| **Auth** | ✅ Built-in (Sanctum, Passport, JWT) | ❌ Phải tự build | ⚠️ Dependency injection |
| **Validation** | ✅ Form Requests | ❌ Manual (Joi, Yup) | ✅ Pydantic |
| **Queue/Jobs** | ✅ Laravel Queue | ⚠️ Bull/BullMQ | ⚠️ Celery (setup phức tạp) |
| **WebSocket** | ✅ Reverb (native) | ✅ Socket.io | ❌ Cần thư viện khác |
| **Admin Panel** | ✅ Nova, Voyager | ❌ Phải build | ❌ Phải build |
| **Learning Curve** | ⚠️ Trung bình | ✅ Dễ (JS) | ⚠️ Trung bình |
| **Performance** | ✅ PHP 8.3 JIT rất nhanh | ✅ Non-blocking I/O | ✅✅ Nhanh nhất |
| **Ecosystem** | ✅✅ Lớn nhất PHP | ✅✅✅ NPM khổng lồ | ⚠️ Nhỏ hơn |
| **Documentation** | ✅✅✅ Xuất sắc | ⚠️ Rải rác | ✅ Tốt |

**Kết luận**: Laravel thắng về **batteries-included** (đủ tools), phù hợp project cần nhanh chóng MVP.

---

### 3. Database: PostgreSQL vs MySQL vs MongoDB

| Yếu tố | PostgreSQL | MySQL | MongoDB |
|:-------|:-----------|:------|:--------|
| **ACID** | ✅✅ Hoàn hảo | ✅ Tốt | ❌ EventualConsistency |
| **JSON Support** | ✅✅ JSONB (indexable) | ⚠️ JSON (không index tốt) | ✅✅ Native |
| **Full-text Search** | ✅ Built-in (GIN index) | ⚠️ Cơ bản | ✅ Atlas Search |
| **Geospatial** | ✅ PostGIS | ⚠️ Limited | ✅ Geospatial index |
| **Performance** | ✅ Tốt (complex queries) | ✅✅ Nhanh (simple queries) | ✅ Nhanh (read-heavy) |
| **Scalability** | ⚠️ Vertical (chủ yếu) | ⚠️ Vertical | ✅✅ Horizontal tốt |
| **Data Integrity** | ✅✅ Foreign keys, constraints | ✅ Tốt | ❌ Không có (schema-less) |
| **Use Case Fit** | ✅✅ CMS, structured data | ✅ Web apps | ⚠️ Analytics, logs |

**Kết luận**: PostgreSQL thắng cho **structured CMS** với JSON flexibility.

---

### 4. Caching: Redis vs Memcached

| Yếu tố | Redis | Memcached |
|:-------|:------|:----------|
| **Data Types** | ✅✅ String, Hash, List, Set, Sorted Set | ❌ Chỉ String |
| **Persistence** | ✅ RDB/AOF snapshot | ❌ In-memory only |
| **Pub/Sub** | ✅ Native | ❌ Không có |
| **Transactions** | ✅ MULTI/EXEC | ❌ Không có |
| **Lua Scripting** | ✅ Có | ❌ Không |
| **Performance** | ✅ ~90k ops/sec | ✅✅ ~100k ops/sec |
| **Use Cases** | ✅✅ Cache, Session, Queue, Pub/Sub | ⚠️ Chỉ cache |

**Kết luận**: Redis thắng vì **multi-purpose** (cache + session + queue + real-time).

---

### 5. Object Storage: MinIO vs AWS S3 vs Local Filesystem

| Yếu tố | MinIO | AWS S3 | Local Filesystem |
|:-------|:------|:-------|:-----------------|
| **Cost** | ✅✅ Free (self-host) | ❌ Pay per GB/request | ✅✅ Free |
| **Scalability** | ✅ Horizontal | ✅✅✅ Infinite | ❌ Limited |
| **API** | ✅✅ S3-compatible | ✅✅✅ S3 standard | ❌ File I/O |
| **Durability** | ⚠️ Depends on setup | ✅✅✅ 99.999999999% | ❌ Single point failure |
| **Data Sovereignty** | ✅✅ Full control | ❌ AWS owns infra | ✅✅ Full control |
| **Backup** | ✅ Versioning, lifecycle | ✅✅ Auto backup | ❌ Manual rsync |
| **Performance** | ✅✅ Fast (local) | ✅ Good (network) | ✅✅✅ Fastest |

**Kết luận**: MinIO **cân bằng** giữa self-hosted và S3 API compatibility.

---

## 🔮 Future Tech Considerations

### AI/ML Stack (Planned)

| Công nghệ | Purpose | Status |
|:----------|:--------|:-------|
| **OpenAI API** | GPT-4 for LLM tasks | 🔴 Planned |
| **LangChain** | AI workflow orchestration | 🔴 Planned |
| **LangGraph** | Advanced AI workflows | 🔴 Planned |
| **ChromaDB** | Vector database (embeddings) | 🟡 Research |
| **FAISS** | Fast similarity search | 🟡 Research |
| **Tavily** | Web search for AI | 🔴 Planned |

### Search Stack (Planned)

| Công nghệ | Purpose | Status |
|:----------|:--------|:-------|
| **Elasticsearch** | Full-text search engine | 🟡 Research |
| **Meilisearch** | Lightweight search | 🟡 Alternative |
| **Algolia** | Hosted search (expensive) | ⚪ Not chosen |

---

## 📊 Version Matrix (Đang sử dụng)

```yaml
Frontend:
  Next.js: 16.0.1
  React: 19.2.0
  TypeScript: 5.x
  TailwindCSS: 4.x
  Tiptap: 3.20.1
  Redux Toolkit: 2.10.1
  React Query: 5.90.10
  Axios: 1.13.2

Backend:
  PHP: 8.3
  Laravel: 11.34.2
  PostgreSQL: 16
  Redis: 7
  MinIO: latest

DevOps:
  Docker: latest
  Docker Compose: v2
  pnpm: latest
  Nginx: alpine
  Rclone: latest
```

---

## 🎓 Bài học về Lựa chọn Công nghệ

### Nguyên tắc đã áp dụng

1. **Boring Technology Principle**: Chọn công nghệ đã proven, tránh bleeding-edge
2. **Ecosystem over Performance**: TypeScript chậm hơn JS nhưng ecosystem tốt hơn nhiều
3. **Developer Experience**: Prettier, ESLint tốn time setup nhưng tiết kiệm time maintaining
4. **Future-proof**: Docker, PostgreSQL, Redis sẽ tồn tại lâu dài
5. **Data Sovereignty**: Self-hosted MinIO thay vì AWS S3 để kiểm soát dữ liệu

### Quyết định đúng đắn

✅ **Next.js**: SSR/SSG built-in, không phải lo optimization  
✅ **PostgreSQL**: JSONB support cứu project (flexible structure)  
✅ **Tiptap**: Rich text editor mạnh nhất  
✅ **Docker**: Deploy anywhere, reproducible  
✅ **TypeScript**: Catch bugs sớm, IntelliSense tốt  

### Quyết định cần tái xem xét

⚠️ **Webpack (Next.js default)**: Có thể migrate sang Turbopack khi stable  
⚠️ **Redux Toolkit**: Đang ít dùng, có thể migrate sang Zustand (lighter)  
⚠️ **MinIO**: Cần monitor performance khi data lớn lên  

---

**Cập nhật lần cuối**: 2026-04-09
