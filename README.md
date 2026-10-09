# Second Memory — Engineering Lab

> 🇻🇳 Second Memory — phòng thí nghiệm kỹ thuật: một sản phẩm nhỏ nhưng thật, dùng để luyện toàn bộ vòng đời phần mềm.

Second Memory started as a personal knowledge system. Since [ADR-0001](docs/adr/0001-engineering-lab-direction.md) it is an **Engineering Lab**: a small, real product used to practise the whole software lifecycle (requirements, design, code, tests, release, operations, incidents) with written records for each step. Notes and long-form writing live in Obsidian; this repository holds the product and the process around it.

> 🇻🇳 Ban đầu là hệ thống quản lý tri thức cá nhân. Từ ADR-0001 dự án thành Engineering Lab: sản phẩm nhỏ nhưng thật để luyện cả vòng đời phần mềm, mỗi bước đều có hồ sơ. Ghi chú nằm ở Obsidian; repo này chứa sản phẩm và quy trình.

## What it does

> 🇻🇳 Sản phẩm làm gì.

The product is the **Skill Ledger** ([REQ-002](docs/requirements/REQ-002-skill-ledger.md), [RFC-002](docs/design/RFC-002-skill-ledger.md)): skills, their level history, learning goals and evidence (links to PRs, ADRs, incidents, notes).

| Part | Who uses it | What it does |
|---|---|---|
| Admin dashboard (`nextjs-fe`) | the owner (role `owner`), a read-only demo account (role `viewer`) | manage skills, levels, goals, evidence and tags; one search box over all of them; audit log |
| Public site (`nextjs-docs`) | anyone | `/skills`: public skills, level history and public evidence; old `/docs` links show a "content moved" page |
| REST API (`laravel-api`) | both sites, the importer | cookie auth (Sanctum SPA), OpenAPI contract, PostgreSQL search (Vietnamese without diacritics, typo tolerant) |
| Importer (`tools/ledger-importer`) | the owner | imports Obsidian notes marked `publish: true` as evidence (Python CLI, API token) |

> 🇻🇳 Sản phẩm là Skill Ledger: kỹ năng, lịch sử cấp độ, mục tiêu học và bằng chứng. Bảng trên: trang quản trị, trang công khai `/skills`, REST API và công cụ import từ Obsidian.

## Architecture

> 🇻🇳 Kiến trúc.

```text
browser ──► ml-nginx :81 ─┬─ /api/*            ─► ml-php (Laravel, php-fpm) ─┬─► ml-postgres (data, search)
                          ├─ /skills, /docs    ─► ml-nextjs-docs (public)     ├─► ml-redis (sessions, cache)
                          └─ everything else   ─► ml-nextjs (admin)           └─  ml-minio (kept data, backups only)
```

| Component | Technology | Container | Host port (dev) |
|---|---|---|---|
| Reverse proxy | nginx 1.25 (unprivileged) | `ml-nginx` | **81** (the entry point) |
| API | Laravel 13, PHP 8.5 (php-fpm), Sanctum | `ml-php` | – (through nginx) |
| Admin dashboard | Next.js 16, React 19, TanStack Query, react-hook-form + zod, next-intl, shadcn/ui, Tailwind 4 | `ml-nextjs` | 3456 |
| Public site | Next.js 16 (server components) | `ml-nextjs-docs` | 3002 |
| Database | PostgreSQL 16 | `ml-postgres` | 5502 |
| Cache / sessions | Redis 7.4 | `ml-redis` | 6601 |
| Object storage | MinIO (S3) — holds the kept `media_mgmt` objects only ([ADR-0007](docs/adr/0007-remove-media-api.md)) | `ml-minio` | 9100, console 9102 |

> 🇻🇳 Mọi request đi qua nginx cổng 81. Ports trên là mặc định trong `docker/.env.example`. MinIO chỉ còn giữ dữ liệu cũ (ADR-0007), không code nào đọc/ghi.

Dev runs on Docker Compose (`docker/docker-compose.yml`). Terraform builds the production-like environments from production images ([ADR-0011](docs/adr/0011-infrastructure-as-code.md), [infra/README.md](infra/README.md)). Architecture before the Engineering Lab: [docs/architecture/as-is.md](docs/architecture/as-is.md).

> 🇻🇳 Môi trường dev chạy bằng Docker Compose; Terraform dựng các môi trường giống production (ADR-0011).

## Quick start

> 🇻🇳 Chạy nhanh.

The host needs only **Docker** (Docker Desktop on WSL2 works) and **make**. PHP, Composer, Node and pnpm run inside the containers.

> 🇻🇳 Máy chỉ cần Docker và make; PHP, Composer, Node, pnpm đều chạy trong container.

```bash
make up        # first run: generates docker/.env and the app env files, builds and starts the stack;
               # an empty database is migrated and seeded on start
make ps        # every container should be healthy
make migrate   # later: apply new migrations to the dev database
```

Open <http://localhost:81> (admin) and <http://localhost:81/skills> (public site). The seed creates a local-only owner account, `root@gmail.com` / `12345678` (`RootAccountSeeder`); never use it outside dev.

> 🇻🇳 Mở `http://localhost:81` (quản trị) và `http://localhost:81/skills` (trang công khai). Lần đầu khởi động, DB trống được migrate và seed tài khoản owner chỉ dùng cho dev: `root@gmail.com` / `12345678`.

## Everyday commands

> 🇻🇳 Lệnh hằng ngày. `make help` liệt kê tất cả.

| Command | What it does |
|---|---|
| `make help` | list every target |
| `make test` / `make test f=Skill` | backend tests (PHPUnit, on the separate `testing` database) |
| `make lint` · `make format` | Pint + ESLint + Prettier checks · fix formatting |
| `make verify` | everything CI would run: lint, Larastan, OpenAPI drift check, type checks, backend + FE + importer tests |
| `make openapi` | regenerate `laravel-api/openapi.json` and the admin FE types |
| `make e2e` | Playwright journey through nginx (login → skill → evidence → search → public page) |
| `make import vault=<path> [dry=1]` | import published Obsidian notes ([runbook](docs/runbooks/ledger-import.md)) |
| `make backup` · `make restore` | PostgreSQL + MinIO backup / restore ([runbook](docs/runbooks/backup-restore.md)) |
| `make tf-images` · `make tf-plan` · `make tf-apply` | build production images, plan / apply the Terraform environment |
| `make fresh` | **dev only**: drop, re-migrate and seed the dev database (asks first) |

## Repository layout

> 🇻🇳 Cấu trúc thư mục.

| Path | Content |
|---|---|
| [laravel-api/](laravel-api/) | REST API |
| [nextjs-fe/](nextjs-fe/) | admin dashboard |
| [nextjs-docs/](nextjs-docs/) | public site |
| [tools/ledger-importer/](tools/ledger-importer/) | Python Obsidian importer |
| [e2e/](e2e/) | Playwright end-to-end test |
| [perf/](perf/) | k6 load tests, SQL plans, request profiling ([reports](docs/reports/perf/)) |
| [docker/](docker/) | Compose stack, Dockerfiles, nginx / Postgres / Redis config |
| [infra/](infra/) | Terraform modules and environments, production image build |
| [backup/](backup/) | backup and restore scripts |
| [docs/](docs/) | plan, handbook, ADRs, requirements, designs, runbooks, reports, releases |
| [scripts/](scripts/) | `lane.sh` (parallel work board), perf baseline, metrics |
| `.claude/` | instructions for Claude Code: rules, skills, the work board and handoff log |

## Documentation and status

> 🇻🇳 Tài liệu và tiến độ.

- Kế hoạch (tiếng Việt): [mục tiêu và cách làm việc](docs/plan/01-goals-and-workflow.md), [lộ trình](docs/plan/02-roadmap.md), [danh sách việc](docs/plan/03-backlog.md). Chạy và sửa lỗi trên máy dev: [docs/dev-guide.md](docs/dev-guide.md).
- Current state and the live task list: [.claude/lab/PROGRESS.md](.claude/lab/PROGRESS.md) and [.claude/lab/BOARD.md](.claude/lab/BOARD.md).
- Releases: [docs/releases/](docs/releases/) (`v1.0.0` baseline → `v2.0.0` slim-down; `v2.1.0` Skill Ledger in progress, then P4 infrastructure as code).
- Git flow: `feature/*` or `refactor/*` → PR to `developer` → PR to `main`, Conventional Commits ([handbook 04](docs/handbook/04-git.md)).

> 🇻🇳 Bắt đầu từ `docs/README.md`. Tiến độ hiện tại ở `PROGRESS.md` và `BOARD.md`. Release notes trong `docs/releases/`.

## License

Personal project, all rights reserved; not open for outside contributions.

> 🇻🇳 Dự án cá nhân, giữ toàn quyền; chưa nhận đóng góp từ bên ngoài.
