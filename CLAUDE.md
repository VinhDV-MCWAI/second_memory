# CLAUDE.md

**Second Memory** — self-hosted Personal Knowledge Management System (pnpm monorepo).

| Path | Stack | Role | Details |
|---|---|---|---|
| `laravel-api/` | PHP, Laravel, Sanctum, PostgreSQL, Redis | REST API (:81/api via nginx) | [laravel-api/CLAUDE.md](laravel-api/CLAUDE.md) |
| `nextjs-fe/` | Next.js App Router, React, TanStack Query, RHF + zod, next-intl, shadcn/ui, Tailwind 4 | Admin dashboard (:3000) | [nextjs-fe/CLAUDE.md](nextjs-fe/CLAUDE.md) |
| `nextjs-docs/` | Next.js App Router (server components) | Public site: Skill Ledger at `/skills`, old `/docs` links show "content moved" (:3457) | [nextjs-docs/CLAUDE.md](nextjs-docs/CLAUDE.md) |
| `docker/`, `ci-cd/`, `.github/`, `backup/` | Docker Compose, GitHub Actions (self-hosted deploy), shell | Runtime & ops | [docker/CLAUDE.md](docker/CLAUDE.md) |
| `infra/` | Terraform (Docker provider) in a pinned tools container | Production-like environments, production images (ADR-0011) | [infra/README.md](infra/README.md) |
| `tools/ledger-importer/` | Python, uv, ruff, mypy, pytest | Obsidian → evidence importer (`make import`) | [tools/ledger-importer/README.md](tools/ledger-importer/README.md) |
| `e2e/`, `perf/` | Playwright; k6, SQL | End-to-end journey (`make e2e`); load tests and query plans | [handbook testing](docs/handbook/06-testing-qa.md), [perf reports](docs/reports/perf/) |

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

## Plan and way of working (since 2026-10-09)

The slim-down "Engineering Lab" direction is reversed ([ADR-0012](docs/adr/0012-restore-features-dev-first.md)): upgrade from the current code and bring back dynamic permissions (spatie/laravel-permission), MinIO media + uploads, end-user / department / policy management. **Dev environment only**; staging, production, Terraform (`infra/`), load tests (`perf/`) and automatic CI/CD are paused.

- Start every session with [.claude/lab/PROGRESS.md](.claude/lab/PROGRESS.md), then the plan in [docs/plan/](docs/plan/) (goals and workflow, roadmap, backlog) and [docs/dev-guide.md](docs/dev-guide.md) (build, test, fix on dev).
- Workflow: request → proposal with benefits and harms against the owner's goals → owner confirms → implement → quick test. Tasks marked **[XÁC NHẬN]** on the board stop after the proposal. Never remove or replace a feature, delete or move folders, add a dependency, or change auth / permissions / DB structure without the owner's explicit yes.
- Track work with `scripts/lane.sh` on [.claude/lab/BOARD.md](.claude/lab/BOARD.md) (claim → work → `scripts/lane.sh commit` → `scripts/lane.sh done`); rules in [.claude/rules/parallel-lanes.md](.claude/rules/parallel-lanes.md). The `/lab-task` skill walks through one task.
- Docs: **Vietnamese only**, plain language, full feature names instead of IDs (conventions in [docs/plan/01-goals-and-workflow.md](docs/plan/01-goals-and-workflow.md)). Older English + `> 🇻🇳` docs are rewritten when touched. The old plans are in [docs/archive/engineering-lab/](docs/archive/engineering-lab/).

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
