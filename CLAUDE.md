# CLAUDE.md

**Second Memory** — self-hosted Personal Knowledge Management System (pnpm monorepo).

| Path | Stack | Role | Details |
|---|---|---|---|
| `laravel-api/` | PHP, Laravel, PostgreSQL, Redis, Reverb, MinIO (S3) | REST API | [laravel-api/CLAUDE.md](laravel-api/CLAUDE.md) |
| `nextjs-fe/` | Next.js App Router, React, TanStack Query, RHF + zod, next-intl, shadcn/ui, Tailwind 4 | Admin dashboard (:3000) | [nextjs-fe/CLAUDE.md](nextjs-fe/CLAUDE.md) |
| `nextjs-docs/` | Next.js App Router | Public docs site, now a "content moved" page (:3457) | [nextjs-docs/CLAUDE.md](nextjs-docs/CLAUDE.md) |
| `docker/`, `ci-cd/`, `.github/`, `backup/` | Docker Compose, GitHub Actions (self-hosted deploy), shell | Runtime & ops | [docker/CLAUDE.md](docker/CLAUDE.md) |

All docs live in `docs/` (start at [docs/README.md](docs/README.md)). The old Vietnamese architecture docs are archived in `docs/archive/legacy-architecture/` (outdated — code wins).

## Running things

PHP, Composer, Node and pnpm are **not installed on the host**. Everything runs in Docker (containers prefixed `ml-`). The root `Makefile` wraps the common commands (`make help` lists them):

```bash
make up          # generate env if missing, then build + start the stack (./start.sh does the same)
make test        # backend tests (make test f=AdminMst); make test-ci runs them as CI does
make lint        # Pint + ESLint + Prettier checks, backend and both FE apps
make verify      # everything CI runs
make fresh       # DEV ONLY: wipe, re-migrate and seed the dev DB (asks first)
```

Use the `/verify` skill to run the full check suite before declaring work done.

## Engineering Lab roadmap

The project is being repurposed into an Engineering Lab ([ADR-0001](docs/adr/0001-engineering-lab-direction.md)). The single plan is [docs/plan/](docs/plan/) (analysis, roadmap, backlog); the handoff log for resuming work is [.claude/lab/PROGRESS.md](.claude/lab/PROGRESS.md) — read it first and update it after every task. Work items with the `/lab-task` skill. Docs conventions (bilingual EN + `> 🇻🇳` VI lines, IDs, templates): [docs/README.md](docs/README.md).

The 2026-10 refactor ([.claude/refactor/PLAN.md](.claude/refactor/PLAN.md)) is **frozen**; its open items moved into the roadmap.

## Global rules

Detailed, path-scoped conventions live in `.claude/rules/`. The essentials:

- Code, identifiers and comments **in English**. Chat, docs and commit bodies may be Vietnamese.
- No magic values: use Enums / Constants / message keys.
- One Node lockfile at the repo root (pnpm workspace). Never add per-package lockfiles.
- Scripts (env, build, DB, backup) must be idempotent.
- Simple over clever. Don't add abstractions that aren't used at least twice.
- Never read, print or commit secrets or `.env` files.
- Git flow: `feature/*` or `refactor/*` → PR to `developer` → PR to `main`. Conventional Commits.
- Commit messages never carry a `Co-Authored-By: Claude ...` trailer.
