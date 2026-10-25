# Second Memory

A self-hosted personal knowledge base: write down what you learn as Markdown articles in categories and books, keep your files in your own object storage, and publish it all as a documentation-style public site.

The project is also a practice ground for professional software work: every feature goes through request → proposal → confirmation → implementation → tests → documentation, with the decisions written down in [docs/adr/](docs/adr/).

## Features

| Area | What you can do | Details (Vietnamese) |
|---|---|---|
| Skill Ledger | Removed on 2026-10-20 (ADR-0022): experience lives in categories and articles | [skill-ledger.md](docs/features/skill-ledger.md) |
| Files | Folders; upload small files and large files up to 10 GB (chunked, resumable after a reload); preview images, PDF, video, audio, text; thumbnails, image size, media duration; 30-day trash | [quan-ly-file.md](docs/features/quan-ly-file.md) |
| Departments | Departments, admins in several departments, folders in Files locked to departments | [phong-ban.md](docs/features/phong-ban.md) |
| Dynamic permissions | Every admin API is a permission; roles with a permission matrix in the UI; menus and buttons follow the permissions | [phan-quyen-dong.md](docs/features/phan-quyen-dong.md) |
| Sign-in | Session cookie (Laravel Sanctum), signed-in devices with remote sign-out, "remember me" for 30 days, rate limits per user and per IP | [dang-nhap.md](docs/features/dang-nhap.md) |
| Search | Articles and categories (admin: also private articles, files and folders), on the public site and in the admin app: Vietnamese without diacritics, typo tolerant | [tim-kiem.md](docs/features/tim-kiem.md) |
| Audit log | Every change and sign-in attempt, with a filterable screen | [nhat-ky-thay-doi.md](docs/features/nhat-ky-thay-doi.md) |
| Background jobs | Queue, scheduler and live notifications (Reverb WebSocket) shared by every long-running feature | [viec-nen-va-thong-bao.md](docs/features/viec-nen-va-thong-bao.md) |
| Public page | The site root, read like a documentation site: a forum-style home page (groups → categories), category pages with Latest / Updated / Most read / Most saved, article pages with a book menu, contents and previous / next; ~3 KB of client JavaScript; old `/docs`, `/skills`, `/guides` links redirect | [trang-public.md](docs/features/trang-public.md) |
| Categories and articles | Groups and categories with attachments; Markdown articles in several categories, books up to 10 levels deep, Obsidian-style `[[links]]` and `![[embeds]]` of articles, sections and files, preview in the admin app; view counts | [bai-viet.md](docs/features/bai-viet.md) |
| End users | Visitors may sign up on the public page to keep favorites and a read-later list; admins lock accounts and open / close sign-up | [nguoi-dung-cuoi.md](docs/features/nguoi-dung-cuoi.md) |
| Obsidian import | Notes marked `publish: true` become articles, with their content, parents and links (Python CLI) | [nhap-tu-obsidian.md](docs/features/nhap-tu-obsidian.md) |

## Architecture

```text
browser ──► ml-nginx :81 ─┬─ /api/*              ─► ml-php (Laravel API) ──┬─► ml-postgres (data, search)
                          ├─ /app/* (WebSocket)  ─► ml-reverb               ├─► ml-redis (sessions, cache, queue)
                          ├─ /media-official/*   ─► ml-minio (signed links) └─► ml-minio (files)
                          ├─ /admin, /_next/*    ─► ml-nextjs (admin dashboard)
                          └─ everything else     ─► ml-public-site (public site, Astro)
                                                    ml-queue, ml-scheduler: background jobs (same image as ml-php)
```

| Component | Technology | Container | Host port (dev) |
|---|---|---|---|
| Reverse proxy | nginx 1.25 (unprivileged) | `ml-nginx` | **81** (the entry point) |
| API | Laravel 13, PHP 8.5, Sanctum, spatie/laravel-permission, Scramble (OpenAPI), ffmpeg | `ml-php` | – |
| Background jobs | Laravel queue worker, scheduler, Reverb | `ml-queue`, `ml-scheduler`, `ml-reverb` | – |
| Admin dashboard | Next.js 16, React 19, TanStack Query, react-hook-form + zod, next-intl, shadcn/ui, Tailwind 4 | `ml-nextjs` | 3456 |
| Public site | Astro 7 (server rendering, no client JavaScript), Tailwind 4 | `ml-public-site` | 3002 |
| Database | PostgreSQL 16 (full-text + trigram search) | `ml-postgres` | 5502 |
| Cache, sessions, queue | Redis 7.4 | `ml-redis` | 6601 |
| Object storage | MinIO (S3), private buckets, browser uploads through signed URLs | `ml-minio` | 9100, console 9102 |

More detail (Vietnamese): [docs/architecture/current.md](docs/architecture/current.md).

## Quick start

The host needs only **Docker** (Docker Desktop on WSL2 works) and **make**. PHP, Composer, Node and pnpm run inside the containers.

```bash
make up        # first run: generates docker/.env and the app env files, builds and starts the stack;
               # an empty database is migrated and seeded on start
make ps        # every container should be healthy
make seed      # optional: sample categories, articles and a book, a read-only "viewer" admin and a "demo" end user
```

Open <http://localhost:81/> (public site) and <http://localhost:81/admin> (admin). The seed creates a local-only owner account, `root` / `12345678`. Never use it outside dev.

## Everyday commands

`make help` lists every target.

| Command | What it does |
|---|---|
| `make test` / `make test f=ArticleApi` | backend tests (PHPUnit, on the separate `testing` database) |
| `make fe-test` | admin dashboard tests (Vitest) |
| `make lint` · `make format` | Pint + ESLint + Prettier + ruff checks · fix formatting |
| `make verify` | every quality gate in the dev containers (lint, Larastan, OpenAPI drift, type checks, all tests) |
| `make e2e` | Playwright journeys through nginx |
| `make openapi` | regenerate `laravel-api/openapi.json` and the admin types after a route or payload change |
| `make permissions` | register new admin API routes as permissions |
| `make clean` | remove leftovers after a task: Docker garbage, unused images, test output (keeps dev data); see [cleanup guide](docs/runbooks/don-dep.md) |
| `make workers-restart` | reload code in the queue, scheduler and Reverb containers |
| `make import vault=<path> [dry=1]` | import published Obsidian notes |
| `make backup` · `make restore` | PostgreSQL + MinIO backup / restore ([runbook](docs/runbooks/backup-restore.md)) |
| `make ci` · `make cd` | CI gates exactly as GitHub / GitLab run them · local CD to a production-like demo (paused) |
| `make fresh` | **dev only**: drop, re-migrate and seed the dev database (asks first) |

## Repository layout

| Path | Content |
|---|---|
| [laravel-api/](laravel-api/) | REST API |
| [nextjs-fe/](nextjs-fe/) | admin dashboard |
| [public-site/](public-site/) | public site |
| [tools/ledger-importer/](tools/ledger-importer/) | Python Obsidian importer |
| [e2e/](e2e/) | Playwright end-to-end tests |
| [docker/](docker/) | Compose stack, Dockerfiles, nginx / Postgres / Redis / MinIO config |
| [ci/](ci/) | CI and build scripts shared by GitHub and GitLab (automatic runs are off) |
| [infra/](infra/), [perf/](perf/) | Terraform environments, load tests (both paused until the product is complete) |
| [backup/](backup/) | backup and restore scripts |
| [scripts/](scripts/) | env setup, cleanup, the task board script `lane.sh` |
| [docs/](docs/) | documentation, in Vietnamese: start at [docs/README.md](docs/README.md) |
| `.claude/` | instructions for Claude Code: rules, skills, the task board and progress log |

## Status

- Development happens on the dev environment only; staging, production, automatic CI/CD and load tests are paused ([ADR-0012](docs/adr/0012-restore-features-dev-first.md)).
- Plan and progress (Vietnamese): [roadmap](docs/plan/02-roadmap.md), [what has been done](docs/plan/05-done.md), [current state](.claude/lab/PROGRESS.md).
- Latest full check (2026-10-19): 299 backend, 158 admin dashboard and 63 importer tests pass; 10/10 browser journeys pass.
- Git flow: one working branch → pull request to `developer` → `main`, Conventional Commits.

## License

Personal project, all rights reserved; not open for outside contributions.
