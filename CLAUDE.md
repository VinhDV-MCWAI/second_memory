# CLAUDE.md

**Second Memory** — self-hosted Personal Knowledge Management System (pnpm monorepo).

| Path | Stack | Role | Details |
|---|---|---|---|
| `laravel-api/` | PHP, Laravel, PostgreSQL, Redis, Reverb, MinIO (S3) | REST API | [laravel-api/CLAUDE.md](laravel-api/CLAUDE.md) |
| `nextjs-fe/` | Next.js App Router, React, TanStack Query, RHF + zod, next-intl, shadcn/ui, Tailwind 4, Tiptap 3 | Admin dashboard (:3000) | [nextjs-fe/CLAUDE.md](nextjs-fe/CLAUDE.md) |
| `nextjs-docs/` | Next.js App Router, Tiptap renderer | Public docs site (:3457) | [nextjs-docs/CLAUDE.md](nextjs-docs/CLAUDE.md) |
| `docker/`, `ci-cd/`, `.github/`, `backup/` | Docker Compose, GitHub Actions (self-hosted deploy), shell | Runtime & ops | [docker/CLAUDE.md](docker/CLAUDE.md) |

Root `0X-*.md` files are architecture docs (Vietnamese, may be outdated — code wins).

## Running things

PHP, Composer, Node and pnpm are **not installed on the host**. Everything runs in Docker (containers prefixed `ml-`). The root `Makefile` wraps the common commands (`make help` lists them):

```bash
make up          # generate env if missing, then build + start the stack (./start.sh does the same)
make test        # backend tests (make test f=CategoryMgmt); make test-ci skips the known auth failures
make lint        # Pint + ESLint + Prettier checks, backend and both FE apps
make verify      # everything CI runs
make fresh       # DEV ONLY: wipe, re-migrate and seed the dev DB (asks first)
```

Use the `/verify` skill to run the full check suite before declaring work done.

## Ongoing refactor

A staged refactor is tracked in [.claude/refactor/PLAN.md](.claude/refactor/PLAN.md). Before working on any item, read the plan, follow the `/refactor-item` skill, and update the item's status when done. Do not start items the user has not approved.

## Global rules

Detailed, path-scoped conventions live in `.claude/rules/`. The essentials:

- Code, identifiers and comments **in English**. Chat, docs and commit bodies may be Vietnamese.
- No magic values: use Enums / Constants / message keys.
- One Node lockfile at the repo root (pnpm workspace). Never add per-package lockfiles.
- Scripts (env, build, DB, backup) must be idempotent.
- Simple over clever. Don't add abstractions that aren't used at least twice.
- Never read, print or commit secrets or `.env` files.
- Git flow: `feature/*` or `refactor/*` → PR to `developer` → PR to `main`. Conventional Commits.
