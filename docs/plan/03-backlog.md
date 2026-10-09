# 03 — Backlog

> 🇻🇳 Danh sách công việc.

Rolling-wave planning: the current and next phase are broken into tasks of 1–3 sessions; later phases stay coarse and are refined when their turn comes (that refinement is itself task `Px-00`).

> 🇻🇳 Lập kế hoạch cuốn chiếu: giai đoạn hiện tại và kế tiếp được chia thành task 1–3 buổi; các giai đoạn sau để ở mức thô và được chi tiết hoá khi tới lượt (việc đó là task `Px-00`).

Status: `todo` → `doing` → `done` | `cut` (with reason). When GitHub Projects is set up (P0-06), each task becomes an issue and this file links to it.

> 🇻🇳 Khi đã có GitHub Projects, mỗi task thành một issue và file này link tới issue.

## P0 — Baseline & close-out

| ID | Task | Output | Status |
|---|---|---|---|
| P0-01 | Close the old refactor: gather `fix/security-deps` + `refactor/fe6-features` into `chore/p0-baseline`, check the FE6 WIP, split no more files of features listed for removal | Branch `chore/p0-baseline`; tsc ✓, lint 0 errors, Vitest 106 ✓ | done |
| P0-02 | Verify (`make verify`), PR `chore/p0-baseline` → `developer` → `main` (owner pushes) | Merged PRs | doing: `make verify` ✓ (598 BE, 106 FE); now part of `refactor/p2-slim-down` (stacked branch deleted, boundary = tag `v1.0.0`); push + PRs by owner |
| P0-03 | Tag `v1.0.0` on `main` with release notes "legacy CMS baseline" | GitHub release | done locally (tag `v1.0.0`, notes in `docs/releases/v1.0.0.md`); owner pushes tag + creates release |
| P0-04 | Write `docs/architecture/as-is.md`: containers, data model summary, request flow, auth flow (one diagram each) | As-is doc | done |
| P0-05 | Move root `01–10-*.md` to `docs/archive/legacy-architecture/`, add a note on top of each, fix links in CLAUDE.md files | Archive | done |
| P0-06 | GitHub Issues + Projects board (columns: Backlog, Ready, Doing, Review, Done), labels (`type:req/bug/task/spike/incident/docs`, `prio:p1–p4`, `phase:Px`) | Board | owner: steps in `docs/plan/github-setup.md` |
| P0-07 | Mark `.claude/refactor/PLAN.md` frozen, map open items per [analysis §7](01-analysis.md#7-impact-on-the-running-refactor-clauderefactorplanmd) | Frozen plan | done |
| P0-08 | ADR-0002 documentation conventions (bilingual marker, IDs, folders) | ADR | done |
| P0-09 | Remote branch cleanup (old `feature/*`, `staging`) per PROGRESS.md | Clean remote | owner: approved 2026-10-07, command in `.claude/lab/PROGRESS.md` → "Needs the owner" item 4 (no credentials in Claude's environment); local branches already cleaned |

## P1 — Handbook

| ID | Task | Output | Status |
|---|---|---|---|
| P1-01 | Team model + RACI matrix | `handbook/01-team.md` | done |
| P1-02 | Work intake: request flow, clarification question bank, Definition of Ready, estimation, prioritisation (MoSCoW / RICE) | `handbook/02-intake.md` | done |
| P1-03 | Design: when to write RFC/ADR, how to present and get approval, diagram types (C4, ERD, sequence) | `handbook/03-design.md` | done |
| P1-04 | Git & review: branch model and protection, Conventional Commits, PR size, review checklist, conflict resolution, branch deletion | `handbook/04-git.md` | done |
| P1-05 | Coding standards PHP / TS (+ placeholders for Go / Python), naming, errors, logging, "no magic values" | `handbook/05-coding.md` | done |
| P1-06 | Testing & QA: test pyramid by risk, Definition of Done, QA flow, bug template, severity vs priority | `handbook/06-testing-qa.md` | done |
| P1-07 | Release & deploy, environments, rollback, migration safety | `handbook/07-release.md` | done |
| P1-08 | Operations: severity matrix, incident roles, communication, postmortem rules | `handbook/08-operations.md` | done |
| P1-09 | Reporting: daily / weekly / time log, escalation path | `handbook/09-reporting.md` | done |
| P1-10 | Documentation map (REQ / RFC / ADR / PRB / INC / runbook) + data rules | `handbook/10-docs-and-data.md` | done |
| P1-11 | Templates: requirement, design doc, ADR ✓, problem record ✓, incident, postmortem, runbook, daily, weekly, retro | `docs/templates/` | done |
| P1-12 | GitHub templates: issue forms (REQ, BUG, TASK, SPIKE, INC), PR template, CODEOWNERS | `.github/` | done |
| P1-13 | AI stakeholder skills: `simulate-po`, `simulate-qa`, `simulate-ops` (role, tone, what they ask, what they never reveal) | `.claude/skills/` | done |
| P1-14 | Dry run: one simulated request through intake to "Ready"; first daily + weekly report; P1 retro | REQ-001 Ready, reports, retro, `v1.1.0` notes | done |

## P2 — Slim down

| ID | Task | Output | Status |
|---|---|---|---|
| P2-01 | `REQ-001` from simulated PO: "the CMS is replaced by Obsidian; remove what is no longer needed" + clarification log | [REQ-001](../requirements/REQ-001-slim-down.md) | done (in P1-14 dry run) |
| P2-02 | Impact analysis: per module → tables, routes, FE pages, jobs, tests, data to keep | Impact doc | done (RFC-001) |
| P2-03 | ERD as-is / to-be (generated from the schema, then edited) | Diagrams | done (RFC-001) |
| P2-04 | Design doc + ADR-0003 (what is removed, order, rollback plan) | RFC + ADR | done (RFC-001) |
| P2-05 | Before-metrics snapshot (LOC, tables, endpoints, tests, test time, image size, startup time) | Metrics table | done (RFC-001) |
| P2-05b | Artisan command exporting categories/entries/descriptions to Markdown (frontmatter, idempotent), tested with factories; run on deployed data before the drop (REQ-001 US-2) | Command + test | done (RFC-001) |
| P2-06 | Remove FE: sliders, banners, setting links, socials, users, departments, content CMS, file-manager tree | PRs | done (RFC-001 slices 2–6) |
| P2-07 | Remove API + tests for the same modules | PRs | done (RFC-001 slices 2–5; media API kept, see RFC-001 §3 correction) |
| P2-08 | Expand/contract: create `audit_log`, dual-write, backfill, switch reads, drop `*_hist` tables | Migrations + [ADR-0006](../adr/0006-audit-log.md) | done (RFC-001 slice 9: expand, dual-write, backfill, switch reads, contract, login events) |
| P2-09 | Drop removed tables, triggers and views (after backup; down-migration tested) | Migrations | done (RFC-001 slices 2–5; slice 6 drops nothing) |
| P2-10 | PRB-001 + ADR-0004: replace custom JWT with Sanctum SPA cookies; archive AUTH-GUIDE as learning record | [PRB-001](../problems/PRB-001-custom-jwt-auth.md), [ADR-0004](../adr/0004-sanctum-spa-cookie-auth.md), [learning record](../archive/learning/AUTH-GUIDE.md) | done |
| P2-11 | Implement Sanctum, migrate FE auth provider, delete custom auth code; unblock old FE3 (route guard) | PRs | done (RFC-001 slice 7; [PRB-001 §7](../problems/PRB-001-custom-jwt-auth.md#7-implementation-and-verification); side finding [PRB-002](../problems/PRB-002-tests-used-dev-database.md)) |
| P2-12 | RBAC → Gates/Policies with `owner` / `viewer` | PR | done (RFC-001 slice 8, [ADR-0005](../adr/0005-owner-viewer-roles.md)) |
| P2-13 | Regression run, after-metrics, release `v2.0.0` with notes, retro | Release | done ([v2.0.0](../releases/v2.0.0.md), [v1.2.0](../releases/v1.2.0.md), [retro](../reports/retro/P2.md); local tag `v2.0.0`) |

## P3 — Skill Ledger

Refined in P3-00 (2026-10-08). Same lifecycle as P2: requirement → design → small vertical slices → QA / PO acceptance → release. Inputs carried over from P2: the open media API decision (RFC-001 §3), the unused framework tables `users` / `password_reset_tokens` and the fake dashboard numbers ([v2.0.0 known issues](../releases/v2.0.0.md#known-issues)), and the research note [docs/search.md](../search.md).

> 🇻🇳 Đã chi tiết hoá ở P3-00. Cùng vòng đời với P2: yêu cầu → thiết kế → các lát dọc nhỏ → QA / PO nghiệm thu → release. Việc tồn từ P2: quyết định media API, bảng mặc định không dùng, số liệu giả trên dashboard, ghi chú nghiên cứu search.

| ID | Task | Output | Status |
|---|---|---|---|
| P3-00 | Refine this phase into tasks of 1–3 sessions | This table | done |
| P3-01 | `REQ-002` from the simulated PO (`/simulate-po`): stories, Given/When/Then acceptance criteria, clarification log, Definition of Ready. Ask about evidence files (→ media API decision), what is public, what the importer may overwrite | [REQ-002](../requirements/REQ-002-skill-ledger.md) Ready | done (11 questions; evidence = links only → RFC-002 proposes removing the media API) |
| P3-02 | RFC-002 design: domain model, to-be ERD, endpoint list, public vs admin access, audit, slices, test strategy per layer, risks. Decide the media API (reuse for evidence files or remove — ADR if kept) and drop `users` / `password_reset_tokens` | [RFC-002](../design/RFC-002-skill-ledger.md), [ADR-0007](../adr/0007-remove-media-api.md) | done (media API removed in P3-05b, files kept; one idempotent import endpoint; `sessions` table stays) |
| P3-03 | ADR: OpenAPI-first vs today's code-first Scramble export (CI only checks drift). Write the contract for the Skill Ledger endpoints and review it before code | [ADR-0008](../adr/0008-code-first-openapi-contract.md) (contract table inside) | done (code-first spec + reviewed contract + response validation in P3-09; `make verify` now runs the drift check) |
| P3-04 | Search spike + ADR: Postgres full-text (`tsvector`, `unaccent` for Vietnamese, `ts_rank`) vs `pg_trgm` for typos, measured on seeded data; answer the open questions in `docs/search.md` that matter here | [Spike](../reports/spikes/search-2026-10-08/README.md) + [ADR-0009](../adr/0009-postgres-search.md) | done (full-text p95 0.64 ms, trigram 20 ms at REQ volume; FTS first, trigram fallback) |
| P3-05 | Schema slice: migrations, models, enums, factories for `Skill`, `SkillLevel` history, `LearningGoal`, `Evidence`, `Tag` (RFC-002 §4.2, slice 2); unit tests | Migrations + tests | done (`752af6a`; 8 tables, 1–4 CHECKs, case-insensitive unique names; search columns follow in P3-08) |
| P3-05b | RFC-002 slice 1: remove the media API code per ADR-0007 (keep `media_mgmt` rows and MinIO objects); drop `users` / `password_reset_tokens` | PR | done (`02af8d4`, `8bb6012`, `c61cd63`; OpenAPI 17 → 9 paths) |
| P3-06 | API slice: skills + tags CRUD, level changes as append-only history, audit log entries, owner / viewer rules; integration tests | PR | done (`e546b65`; 10 routes, 19 feature tests, goal auto-achieve rule included) |
| P3-07 | API slice: learning goals + evidence (links to PR / ADR / INC / note, no files — ADR-0007); integration tests | PR | done (`b275b5a`; 8 routes, 19 feature tests; goal target must be above the current level, imported evidence: only `type` / `is_public` editable) |
| P3-08 | API slice: search endpoint (per P3-04 ADR) + public read-only endpoints (published items only, no auth, rate-limited) | PR | done (`e17d093`; search migration, `admin/search`, `public/skills[/{slug}]`, 11 feature tests) |
| P3-09 | Contract tests: backend responses validated against the spec; FE types generated from it, the check fails on drift. Runs in `make verify` while CI is paused (since 2026-10-08); CI job added when CI is re-enabled | Tests + `make` target | done (`0ca9b88`; every feature-test response validated against `openapi.json` with `opis/json-schema`, spec describes the real envelope; runs in `make verify`) |
| P3-10 | Admin UI: skills list / detail / form, level history timeline, tags | PR | done (`d6a51b4`; skills + tags pages, skill form with first level, level timeline + audit history in the edit dialog) |
| P3-11 | Admin UI: goals, evidence, search box; dashboard shows real counts instead of the hard-coded sample numbers | PR | done (`3f4ad1e` API `dashboard/summary`, `e44dec3` FE: evidence + goals pages, header search → `/admin/search`, dashboard from real counts + latest audit entries) |
| P3-12 | Public read-only view in `nextjs-docs` (first piece of the portfolio): skill list + detail; `/docs` "content moved" page stays | PR | todo |
| P3-13 | ADR for Python tooling (version, packaging, runs in Docker — no Python on the host) + importer contract: frontmatter schema, stable external key, how the CLI authenticates (ADR-0004 has session auth only) | [ADR-0010](../adr/0010-python-importer-tooling.md) | done (Python 3.14 + uv, ruff, mypy, pytest, runs in Docker; Sanctum token with ability `evidence:import`; note → request mapping; invalid published note → nothing sent) |
| P3-14 | Python Obsidian importer: only `publish: true` notes, upsert by external key, `--dry-run`, pytest; idempotency test (second run changes nothing) | CLI + tests | done (last part P3-14c: 1ba8649, 7219d35 — make import vault=… [dry=1] (runtime image, vault read-only, ml_network), importer-lint in make lint, importer-test in make verify; runbook docs/runbooks/ledger-import.md; make import run twice against the real API on the perf DB: 2nd run unchanged 2 / 0 created-updated-hidden; make verify exit 0 (backend 160, FE 70, importer 63). Not run on the dev stack: dev DB has no owner account (runbook precondition, owner step)) |
| P3-15 | E2E (Playwright), critical journey only: login → create skill → add evidence → find it by search → see it on the public page; a `make` target (CI job when CI is re-enabled) | E2E + `make` target | todo |
| P3-16 | First k6 baseline: smoke + load on read / search / public endpoints; p50 / p95 / p99, RPS, error rate recorded | `docs/reports/perf/` baseline | done (2af8d88, d10b7c9 — k6 smoke + load on the REQ-002 seed: 0 % errors; at 10 users p95 search 292–295 ms (target 300, just), others 207–238 ms; ~66–69 req/s capped by ~28 ms per-request overhead outside the queries (PERF-01). Report docs/reports/perf/2026-10-08-baseline.md) |
| P3-17 | QA sign-off (`/simulate-qa`) and PO acceptance (`/simulate-po`) against REQ-002; bugs filed and fixed | Sign-off | todo |
| P3-18 | Release `v2.1.0` with notes, before/after numbers, retro | Release + retro | todo |

## P4 — Infrastructure as Code

Refined in P4-00 (2026-10-08). Goal ([roadmap](02-roadmap.md#p4--infrastructure-as-code-34-weeks)): recreate the whole environment from zero with one command and prove it with a timed destroy / apply / restore. Inputs carried over: Docker hardening and the MinIO choice from the frozen refactor (I1, I3), the docs container healthcheck port and the 1.8 GB API image ([v2.0.0 known issues](../releases/v2.0.0.md#known-issues)), idle `ml-queue` / `ml-reverb` (nothing queues or broadcasts since ADR-0007), Laravel caches in non-dev images (OPS-02, [request profile](../reports/perf/2026-10-08-request-profile.md)), the full restore OPS-01 could not run without a scratch environment ([runbook](../runbooks/backup-restore.md)), and k6 on production images ([baseline](../reports/perf/2026-10-08-baseline.md)). Order: decide → harden the images → Terraform → environments → TLS and secrets → drill → measure. Dev keeps hot reload; how dev and Terraform relate is the ADR's call.

> 🇻🇳 Đã chi tiết hoá ở P4-00. Mục tiêu: dựng lại toàn bộ môi trường từ số 0 bằng một lệnh và chứng minh bằng một lần destroy / apply / restore có đo thời gian. Việc tồn được gom vào: hardening Docker và lựa chọn MinIO (I1, I3), healthcheck của container docs, image API 1,8 GB, `ml-queue` / `ml-reverb` không còn dùng, cache Laravel trong image không phải dev (OPS-02), restore toàn phần mà OPS-01 chưa làm được, k6 trên image production. Thứ tự: quyết định → hardening image → Terraform → môi trường → TLS và secret → diễn tập → đo lại.

| ID | Task | Output | Status |
|---|---|---|---|
| P4-00 | Refine this phase into tasks of 1–3 sessions | This table + board rows | done (9fbc585 — P4 refined into P4-01 … P4-12 in the backlog (ADR first, then hardening, production images, Terraform Docker/MinIO, 3 envs, TLS, SOPS, timed destroy/apply/restore drill, k6 on prod-like, close); board rows added in infra (P4-01…P4-10), api (API-05: drop queue/broadcast wiring before P4-04), perf (P4-11), release (P4-12); OPS-02 folded into P4-03) |
| P4-01 | ADR-0011 IaC approach: Terraform (Docker + MinIO providers) vs Compose only vs both (Compose for dev hot reload, Terraform for `staging` / `prod-like`); state location and locking for one person, module layout under `infra/`, what differs between environments (names, ports, volumes, images), how secrets enter (SOPS + age, decided here, built in P4-09); revisit the MinIO choice (I3: pinned `pgsty/minio` vs another S3 server) | ADR-0011 | done (66bb503, 6a64d77 — ADR-0011: Compose stays dev (ml-* names, hot reload); Terraform (OpenTofu-compatible) owns staging + prod-like (sm-<env>-*, HTTPS-only *.sm.localhost:8443/9443, ≤1.5 GB each, label sm.env); infra/modules/{stack,storage} + live/<env>/{stack,storage} applied in order, local state (secrets inside, never in git/backup), SOPS+age via sops exec-env; keep pinned pgsty/minio (app has no S3 caller) with revisit triggers; P4-03 must make FE images runtime-configured (same-origin /api); backlog P4-03/07/09 updated) |
| P4-02 | Docker hardening (I1): pin every image to a version (digests with P5), non-root in every service, healthchecks + `depends_on: service_healthy` everywhere, fix the `ml-nextjs-docs` healthcheck / port mismatch, tight `.dockerignore`; `docker compose up` healthy from a clean checkout | PR + before/after `docker compose ps` | todo |
| P4-03 | Production images: API image size down from ~1.8 GB (target set in the PR, measured with `scripts/metrics.sh --images`), Laravel caches built at container start outside dev (`php artisan optimize`, folds OPS-02), docs production image builds and serves the P3-12 public pages; per-environment values at runtime, not build time (same-origin `/api` instead of a baked `NEXT_PUBLIC_API_URL`, ADR-0011), so one tag runs in `staging` and `prod-like` | PR + image size table | todo |
| P4-04 | Remove idle `ml-queue` and `ml-reverb` (and the Reverb / broadcasting wiring the API no longer uses — API part in lane `api`); compose, nginx, env generation, docs | PRs | todo |
| P4-05 | Terraform, Docker provider: network, volumes and the app containers (postgres, redis, php, nginx, nextjs, docs) for one environment; `make tf-plan` / `tf-apply` in a pinned Terraform container (nothing on the host) | `infra/` module + Make targets | todo |
| P4-06 | Terraform, MinIO provider: buckets, lifecycle, policies and the app user, replacing `docker/minio/create-buckets.sh` for Terraform-managed environments | Module + check that the app reads / writes | todo |
| P4-07 | Three environments side by side on one host without name or port clashes: `staging` and `prod-like` from one Terraform module set with per-environment variables, next to Compose `dev` ([ADR-0011](../adr/0011-infrastructure-as-code.md)); memory measured; Compose ↔ Terraform service-list check | Variables per env + runbook section | todo |
| P4-08 | Reverse proxy with local TLS (`mkcert`) for `staging` / `prod-like`: HTTPS only, Sanctum stateful domains and `SESSION_SECURE_COOKIE` per environment; login checked over HTTPS | Proxy config + check | todo |
| P4-09 | Secrets with SOPS + age instead of plain `.env` for `staging` / `prod-like`: encrypted files in git, key kept outside, Make targets run Terraform through `sops exec-env` (no decrypted file; dev keeps `setup-env.sh`, [ADR-0011](../adr/0011-infrastructure-as-code.md)); key rotation and loss in a runbook | Encrypted env files + runbook | todo |
| P4-10 | Drill: `terraform destroy && terraform apply` + `restore.sh` brings `prod-like` back with data; time each step vs RTO 30 min; the full end-to-end restore OPS-01 left open | Runbook `docs/runbooks/rebuild-environment.md` with measured times | todo |
| P4-11 | k6 baseline on `prod-like` (production images, php-fpm behind nginx, TLS) against the 2026-10-08 numbers | `docs/reports/perf/` report | todo |
| P4-12 | Phase close: as-is architecture update, release notes, retro | Release + retro | todo |

Optional, only with time left: AWS-style practice with LocalStack or an open-source emulator (roadmap); not planned as a task.

> 🇻🇳 Tuỳ chọn khi còn thời gian: luyện kiểu AWS với LocalStack hoặc emulator mã nguồn mở; không lập thành task.

## P5–P11 (coarse)

| ID | Phase | Key tasks |
|---|---|---|
| P5-xx | CI/CD | Re-enable `ci.yml` (PR trigger) and `cd.yml` (push to `developer`), paused on 2026-10-08 — manual runs only until then; rulesets, commitlint, release-please, SBOM/Trivy/gitleaks, SHA images, staged deploys, auto rollback, Pennant flags, DORA report |
| P6-xx | Observability | otel-lgtm, OTel instrumentation, JSON logs, RED/USE dashboards, SLOs, alerts, runbooks |
| P7-xx | Reliability | `chaos-master` skill, Toxiproxy, 8+ incidents + postmortems, DR drill |
| P8-xx | Perf & security | k6 suites, bottleneck fixes with numbers, STRIDE, ASVS L1, ZAP |
| P9-xx | Dev tools | 9a extension, 9b Go replay CLI, 9c Go media worker |
| P10-xx | Portfolio | Site, case studies, README story, demo account, CV bullets |
| P11-xx | Kubernetes | Only with an ADR |
