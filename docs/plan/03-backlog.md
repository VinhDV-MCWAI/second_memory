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
| P0-02 | Verify (`make verify`), PR `chore/p0-baseline` → `developer` → `main` (owner pushes) | Merged PRs | todo |
| P0-03 | Tag `v1.0.0` on `main` with release notes "legacy CMS baseline" | GitHub release | todo |
| P0-04 | Write `docs/architecture/as-is.md`: containers, data model summary, request flow, auth flow (one diagram each) | As-is doc | todo |
| P0-05 | Move root `01–10-*.md` to `docs/archive/legacy-architecture/`, add a note on top of each, fix links in CLAUDE.md files | Archive | todo |
| P0-06 | GitHub Issues + Projects board (columns: Backlog, Ready, Doing, Review, Done), labels (`type:req/bug/task/spike/incident/docs`, `prio:p1–p4`, `phase:Px`) | Board | todo |
| P0-07 | Mark `.claude/refactor/PLAN.md` frozen, map open items per [analysis §7](01-analysis.md#7-impact-on-the-running-refactor-clauderefactorplanmd) | Frozen plan | todo |
| P0-08 | ADR-0002 documentation conventions (bilingual marker, IDs, folders) | ADR | todo |
| P0-09 | Remote branch cleanup (old `feature/*`, `staging`) per PROGRESS.md | Clean remote | todo |

## P1 — Handbook

| ID | Task | Output | Status |
|---|---|---|---|
| P1-01 | Team model + RACI matrix | `handbook/01-team.md` | todo |
| P1-02 | Work intake: request flow, clarification question bank, Definition of Ready, estimation, prioritisation (MoSCoW / RICE) | `handbook/02-intake.md` | todo |
| P1-03 | Design: when to write RFC/ADR, how to present and get approval, diagram types (C4, ERD, sequence) | `handbook/03-design.md` | todo |
| P1-04 | Git & review: branch model and protection, Conventional Commits, PR size, review checklist, conflict resolution, branch deletion | `handbook/04-git.md` | todo |
| P1-05 | Coding standards PHP / TS (+ placeholders for Go / Python), naming, errors, logging, "no magic values" | `handbook/05-coding.md` | todo |
| P1-06 | Testing & QA: test pyramid by risk, Definition of Done, QA flow, bug template, severity vs priority | `handbook/06-testing-qa.md` | todo |
| P1-07 | Release & deploy, environments, rollback, migration safety | `handbook/07-release.md` | todo |
| P1-08 | Operations: severity matrix, incident roles, communication, postmortem rules | `handbook/08-operations.md` | todo |
| P1-09 | Reporting: daily / weekly / time log, escalation path | `handbook/09-reporting.md` | todo |
| P1-10 | Documentation map (REQ / RFC / ADR / PRB / INC / runbook) + data rules | `handbook/10-docs-and-data.md` | todo |
| P1-11 | Templates: requirement, design doc, ADR ✓, problem record ✓, incident, postmortem, runbook, daily, weekly, retro | `docs/templates/` | doing |
| P1-12 | GitHub templates: issue forms (REQ, BUG, TASK, SPIKE, INC), PR template, CODEOWNERS | `.github/` | todo |
| P1-13 | AI stakeholder skills: `simulate-po`, `simulate-qa`, `simulate-ops` (role, tone, what they ask, what they never reveal) | `.claude/skills/` | todo |
| P1-14 | Dry run: one simulated request through intake to "Ready"; first daily + weekly report; P1 retro | Reports | todo |

## P2 — Slim down

| ID | Task | Output | Status |
|---|---|---|---|
| P2-01 | `REQ-001` from simulated PO: "the CMS is replaced by Obsidian; remove what is no longer needed" + clarification log | Requirement | todo |
| P2-02 | Impact analysis: per module → tables, routes, FE pages, jobs, tests, data to keep | Impact doc | todo |
| P2-03 | ERD as-is / to-be (generated from the schema, then edited) | Diagrams | todo |
| P2-04 | Design doc + ADR-0003 (what is removed, order, rollback plan) | RFC + ADR | todo |
| P2-05 | Before-metrics snapshot (LOC, tables, endpoints, tests, test time, image size, startup time) | Metrics table | todo |
| P2-06 | Remove FE: sliders, banners, setting links, socials, users, departments, content CMS, file-manager tree | PRs | todo |
| P2-07 | Remove API + tests for the same modules | PRs | todo |
| P2-08 | Expand/contract: create `audit_log`, dual-write, backfill, switch reads, drop `*_hist` tables | Migrations + PRB | todo |
| P2-09 | Drop removed tables, triggers and views (after backup; down-migration tested) | Migrations | todo |
| P2-10 | PRB-001 + ADR-0004: replace custom JWT with Sanctum SPA cookies; archive AUTH-GUIDE as learning record | Docs | todo |
| P2-11 | Implement Sanctum, migrate FE auth provider, delete custom auth code; unblock old FE3 (route guard) | PRs | todo |
| P2-12 | RBAC → Gates/Policies with `owner` / `viewer` | PR | todo |
| P2-13 | Regression run, after-metrics, release `v2.0.0` with notes, retro | Release | todo |

## P3 — Skill Ledger (to refine in P3-00)

| ID | Task | Status |
|---|---|---|
| P3-00 | Refine this phase into tasks | todo |
| P3-01 | `REQ-002` Skill Ledger: stories + Given/When/Then acceptance criteria | todo |
| P3-02 | ERD + OpenAPI contract first; ADR on search (Postgres FTS) | todo |
| P3-03 | API + unit/integration tests | todo |
| P3-04 | Contract tests in CI (FE types from OpenAPI) | todo |
| P3-05 | Admin UI pages | todo |
| P3-06 | Python Obsidian importer (idempotent, `publish: true` only) + ADR for Python | todo |
| P3-07 | E2E (Playwright) for the critical journey; first k6 baseline | todo |
| P3-08 | Release `v2.1.0`, retro | todo |

## P4–P11 (coarse)

| ID | Phase | Key tasks |
|---|---|---|
| P4-xx | IaC | Terraform Docker/MinIO modules, 3 environments, local TLS, SOPS+age, Docker hardening, destroy/apply drill |
| P5-xx | CI/CD | Rulesets, commitlint, release-please, SBOM/Trivy/gitleaks, SHA images, staged deploys, auto rollback, Pennant flags, DORA report |
| P6-xx | Observability | otel-lgtm, OTel instrumentation, JSON logs, RED/USE dashboards, SLOs, alerts, runbooks |
| P7-xx | Reliability | `chaos-master` skill, Toxiproxy, 8+ incidents + postmortems, DR drill |
| P8-xx | Perf & security | k6 suites, bottleneck fixes with numbers, STRIDE, ASVS L1, ZAP |
| P9-xx | Dev tools | 9a extension, 9b Go replay CLI, 9c Go media worker |
| P10-xx | Portfolio | Site, case studies, README story, demo account, CV bullets |
| P11-xx | Kubernetes | Only with an ADR |
