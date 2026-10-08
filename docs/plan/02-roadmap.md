# 02 — Roadmap

> 🇻🇳 Lộ trình các giai đoạn.

| | |
|---|---|
| Status | Accepted (2026-10-07) |
| Date | 2026-10-07 |
| Assumption | 8–10 hours/week, one person playing every role (with AI-simulated stakeholders) |
| Tasks | [03-backlog.md](03-backlog.md) |

## Principles

> 🇻🇳 Nguyên tắc.

1. **One active phase.** Finish (or explicitly cut) a phase before starting the next. Recurring habits (daily report, weekly review) run alongside.
   > 🇻🇳 Mỗi lúc chỉ một giai đoạn. Thói quen định kỳ chạy song song.
2. **Every phase ends with a release.** A git tag, release notes, a short retrospective, and one portfolio entry.
   > 🇻🇳 Mỗi giai đoạn kết thúc bằng một release: tag git, release notes, retro ngắn, một mục portfolio.
3. **Problem first, technology second.** A new tool or language enters only through an ADR that names the problem it solves.
   > 🇻🇳 Vấn đề trước, công nghệ sau. Công cụ/ngôn ngữ mới phải có ADR nêu vấn đề nó giải quyết.
4. **Proportional process.** Full ceremony for features and incidents; a short path for small changes.
   > 🇻🇳 Quy trình tương xứng với quy mô thay đổi.
5. **Free and local.** No paid service is required at any step.
   > 🇻🇳 Miễn phí và chạy local.

## Language map

> 🇻🇳 Mỗi ngôn ngữ được dùng ở đâu và vì sao — bạn muốn tập trung backend (PHP, Go, Python) và một ít frontend.

| Language | Where | Why this language here |
|---|---|---|
| PHP (Laravel) | Core API, auth, Skill Ledger domain | Existing codebase; your strongest stack |
| Python | Obsidian vault importer (Phase 3), test/report tooling, load-test analysis | Best ecosystem for text/Markdown parsing and data scripts |
| Go | Request Replay CLI and Media Downloader worker (Phase 9) | Single static binary, cheap concurrency (goroutines) for parallel requests/segments |
| TypeScript / Next.js | Admin UI, portfolio site, browser extension (Phase 9) | Existing FE stack; extensions are JS/TS |
| HCL (Terraform), Bash, YAML | Infrastructure, scripts, CI | Industry standard for IaC and pipelines |

## Phase overview

> 🇻🇳 Tổng quan các giai đoạn. Thời lượng tính theo 8–10 giờ/tuần.

```text
P0 Baseline ─► P1 Handbook ─► P2 Slim down ─► P3 Skill Ledger ─► P4 IaC ─► P5 CI/CD
   1–2 wk        2–3 wk         3–4 wk           4–6 wk           3–4 wk     2–3 wk
                                                                              │
P10 Portfolio ◄─ P9 Dev tools ◄─ P8 Perf & Security ◄─ P7 Reliability ◄─ P6 Observability
   ongoing          6–8 wk            3–4 wk                3–4 wk             3 wk
                                                    (P11 Kubernetes: optional, only on a real need)
```

| Phase | Name | Release | Main evidence for the CV |
|---|---|---|---|
| P0 | Baseline & close-out | `v1.0.0` legacy | As-is architecture, clean handover of the old refactor |
| P1 | Ways of working (Handbook) | `v1.1.0` | A complete team handbook and templates used in practice |
| P2 | Slim down (first full lifecycle) | `v2.0.0` | Change request → impact analysis → expand/contract migration → release; before/after metrics; auth replacement story |
| P3 | Skill Ledger (new core domain) | `v2.1.0` | Requirement → ERD → OpenAPI-first → test pyramid → release; Python importer |
| P4 | Infrastructure as Code | `v2.2.0` | Whole environment created/destroyed with Terraform; dev/staging/prod-like locally |
| P5 | CI/CD & release engineering | `v2.3.0` | Quality + security gates, SemVer releases, rollback, DORA metrics |
| P6 | Observability | `v2.4.0` | Logs/metrics/traces with OpenTelemetry, dashboards, SLOs, alerts, runbooks |
| P7 | Reliability & incidents | `v2.5.0` | ≥ 8 simulated incidents with postmortems, DR drill with measured RTO/RPO |
| P8 | Performance & security | `v2.6.0` | Load-test numbers before/after, threat model, ZAP/Trivy reports fixed |
| P9 | Developer tools | `v3.0.0` | Browser extension, Go replay CLI, Go media worker |
| P10 | Portfolio | continuous | Case studies, public demo, CV bullets |

---

## P0 — Baseline & close-out (1–2 weeks)

> 🇻🇳 Chốt hiện trạng, đóng đợt refactor cũ.

**Goal.** Freeze a recoverable starting point and close the old refactor cleanly, so nothing is lost when features are removed.
> 🇻🇳 Đóng băng một điểm khởi đầu có thể khôi phục và kết thúc đợt refactor cũ gọn gàng, để không mất gì khi xoá tính năng.

**Scope.** Commit and finish FE6 only for code that will stay; merge pending branches; tag `v1.0.0`; write the *as-is* architecture (one page + diagram); archive the root `01–10` docs; ADR-0001 and ADR-0002 (documentation conventions); set up GitHub Issues + Projects board and labels.
> 🇻🇳 Commit và kết thúc FE6 chỉ cho phần code được giữ; merge các branch đang chờ; tag `v1.0.0`; viết kiến trúc hiện tại; lưu trữ tài liệu `01–10`; ADR-0001, ADR-0002; tạo GitHub Issues + Projects và label.

**Done when.** `v1.0.0` exists on `main`; old `PLAN.md` is marked frozen; the board shows the P1 backlog.
> 🇻🇳 Hoàn thành khi: có tag `v1.0.0` trên `main`; `PLAN.md` cũ đánh dấu đóng băng; board hiển thị backlog P1.

## P1 — Ways of working: the Handbook (2–3 weeks)

> 🇻🇳 Bộ quy trình làm việc chuẩn.

**Goal.** A written, used, lightweight operating model for a small product team, covering every topic in your notes.
> 🇻🇳 Bộ quy trình vận hành team nhỏ, gọn, được dùng thật, bao phủ mọi chủ đề trong ghi chú của bạn.

**Scope (chapters in `docs/handbook/`).**

| Chapter | Content |
|---|---|
| Team model | Roles (PO, Tech Lead, Dev, QA, DevOps/SRE, Security, Support), RACI matrix, who you report to for what |
| Work intake | How a request arrives, the clarification question bank, Definition of Ready, estimating, prioritising |
| Design | When a design doc / RFC is needed, how to present and confirm an idea, ADR rules, diagrams (ERD, sequence, C4) |
| Git & code review | Branch strategy (`feature/*` → `developer` → `main`, what is protected, what is deleted after merge), Conventional Commits, PR template, review checklist, resolving conflicts, CODEOWNERS |
| Coding standards | PHP / TS / Go / Python conventions and the tools that enforce them, "no magic values", error handling, logging rules |
| Testing & QA | Test pyramid by risk, Definition of Done, QA flow (test plan → test cases → bug report → regression), bug severity vs priority |
| Release & deploy | SemVer, changelog, environments, deploy and rollback steps, migration safety |
| Operations | Monitoring basics, on-call (simulated), incident severity matrix, incident roles, postmortem rules |
| Reporting | Daily report (yesterday / today / blockers), weekly report, time log, how and to whom to escalate an issue |
| Documentation | When to write REQ / RFC / ADR / PRB / INC / runbook (from analysis §6) |
| Data | Seed/test data, personal-data rules, migrations, backups |

Plus: templates for each document type, GitHub issue/PR templates, and **AI stakeholder skills** in `.claude/skills/` (`simulate-po`, `simulate-qa`, `simulate-ops`, later `chaos-master`) that produce realistic, sometimes vague, inputs.
> 🇻🇳 Kèm theo: template cho từng loại tài liệu, template issue/PR trên GitHub, và **các skill giả lập phòng ban** để Claude đóng vai PO/QA/Ops tạo yêu cầu thực tế (đôi khi mơ hồ) cho bạn xử lý.

**Done when.** Every chapter exists in short form; templates exist; the first daily and weekly reports are written; one simulated request has gone through intake.
> 🇻🇳 Hoàn thành khi: mọi chương có bản ngắn; có đủ template; đã viết báo cáo ngày/tuần đầu tiên; một yêu cầu giả lập đã đi qua quy trình tiếp nhận.

## P2 — Slim down: the first full lifecycle (3–4 weeks)

> 🇻🇳 Cắt giảm — vòng đời đầy đủ đầu tiên.

**Goal.** Remove obsolete features as if a client asked for it, practising every step of the handbook on a real change.
> 🇻🇳 Loại bỏ tính năng lỗi thời như thể khách hàng yêu cầu, thực hành mọi bước của handbook trên một thay đổi thật.

**Scope.** `REQ-001` (change request from the simulated PO) → impact analysis (tables, endpoints, FE pages, tests, data) → as-is / to-be ERD → design doc → removal in safe slices (FE first, then API, then DB via expand/contract with backup) → replace 14 history tables with one `audit_log` → replace custom JWT with Sanctum (PRB-001, ADR-0004) → simplify RBAC to `owner` / `viewer` → regression tests → release notes → retrospective.
> 🇻🇳 Từ yêu cầu thay đổi → phân tích ảnh hưởng → ERD trước/sau → design doc → xoá theo từng lát an toàn → gộp 14 bảng lịch sử thành `audit_log` → thay JWT tự viết bằng Sanctum → đơn giản hoá RBAC → test hồi quy → release notes → retro.

**Done when.** The list in [analysis §4](01-analysis.md#4-keep--simplify--remove) is executed (or each exception has an ADR); all checks green; a before/after table (lines of code, tables, endpoints, test run time, Docker image size, startup time) is published.
> 🇻🇳 Hoàn thành khi: danh sách §4 đã thực hiện (ngoại lệ có ADR); mọi kiểm tra xanh; có bảng số liệu trước/sau.

## P3 — Skill Ledger: the new core domain (4–6 weeks)

> 🇻🇳 Skill Ledger — domain cốt lõi mới.

**Goal.** Build one meaningful feature end to end, OpenAPI-first, with the complete test pyramid.
> 🇻🇳 Xây một tính năng có ý nghĩa từ đầu đến cuối, thiết kế API trước, đủ các tầng test.

**Scope.** Entities (draft): `Skill`, `SkillLevel` history, `LearningGoal`, `Evidence` (link to PR / ADR / INC / note), `Tag`. Python CLI imports Obsidian notes marked `publish: true` (frontmatter → API). Postgres full-text search (input: `docs/search.md`). Admin UI pages; the portfolio site shows a public read-only view. Tests: unit, integration (DB), contract (OpenAPI), E2E (Playwright, critical journey only), first k6 baseline.
> 🇻🇳 Thực thể dự kiến: Skill, lịch sử cấp độ, mục tiêu học, bằng chứng, tag. CLI Python import note Obsidian có `publish: true`. Tìm kiếm full-text bằng Postgres. Trang admin + trang public chỉ đọc. Đủ các loại test và một baseline k6.

**Done when.** Acceptance criteria of `REQ-002` pass in E2E; contract tests run in CI; importer is idempotent (running twice changes nothing).
> 🇻🇳 Hoàn thành khi: tiêu chí chấp nhận pass ở E2E; contract test chạy trong CI; importer chạy hai lần không thay đổi gì.

## P4 — Infrastructure as Code (3–4 weeks)

> 🇻🇳 Hạ tầng bằng code.

**Goal.** Recreate the whole environment from zero with one command and prove it.
> 🇻🇳 Dựng lại toàn bộ môi trường từ số 0 bằng một lệnh và chứng minh được.

**Scope.** Terraform (Docker provider for networks/volumes/containers, MinIO provider for buckets/policies/users); three local environments (`dev`, `staging`, `prod-like`) from one module set with different variables; reverse proxy with local TLS (`mkcert`); secrets with SOPS + age instead of plain `.env`; Docker hardening from old I1 (pinned images, non-root, healthchecks). Optional: AWS-style practice with LocalStack Hobby or an open-source emulator.
> 🇻🇳 Terraform với provider Docker và MinIO; ba môi trường local từ cùng một bộ module; reverse proxy có TLS local; secret quản lý bằng SOPS + age; hardening Docker. Tuỳ chọn: luyện kiểu AWS bằng LocalStack Hobby hoặc emulator mã nguồn mở.

**Done when.** `terraform destroy && terraform apply` + restore script brings `prod-like` back with data; time measured and recorded.
> 🇻🇳 Hoàn thành khi: destroy rồi apply + restore đưa `prod-like` trở lại kèm dữ liệu; thời gian được đo và ghi lại.

## P5 — CI/CD & release engineering (2–3 weeks)

> 🇻🇳 CI/CD và quy trình release.

**Scope.** Required checks and branch rulesets; commit linting; automated changelog + SemVer tags (release-please); SBOM + Trivy image scan + gitleaks; images tagged by SHA; deploy to `staging` on merge to `developer`, to `prod-like` on release; smoke test + automatic rollback; feature flags (Laravel Pennant); DORA metrics collected into a monthly report.
> 🇻🇳 Check bắt buộc + ruleset branch; lint commit; changelog + tag SemVer tự động; SBOM, quét image, quét secret; image tag theo SHA; deploy staging/prod-like; smoke test + rollback tự động; feature flag; số liệu DORA hằng tháng.

**Done when.** A broken build cannot reach `main`; a bad release is rolled back by the pipeline; one DORA report exists.
> 🇻🇳 Hoàn thành khi: build lỗi không vào được `main`; release lỗi được pipeline tự rollback; có một báo cáo DORA.

## P6 — Observability (3 weeks)

> 🇻🇳 Khả năng quan sát hệ thống.

**Scope.** `grafana/otel-lgtm` (Grafana + Prometheus + Loki + Tempo in one container, dev use); OpenTelemetry in Laravel and in the Go/Python services; structured JSON logs with request ID and trace ID; RED dashboards per service and USE dashboards for Postgres/Redis/host; SLIs/SLOs (availability, p95 latency) with error budget; alert rules routed to a free channel (Mailpit locally or ntfy/Telegram); first runbooks for each alert.
> 🇻🇳 Bộ LGTM một container; OpenTelemetry cho Laravel, Go, Python; log JSON có request ID và trace ID; dashboard RED/USE; SLO + error budget; cảnh báo gửi về kênh miễn phí; runbook cho từng cảnh báo.

**Done when.** One request can be followed from browser → nginx → API → DB → queue in a single trace; every alert links to a runbook.
> 🇻🇳 Hoàn thành khi: theo dõi được một request xuyên suốt trong một trace; mọi cảnh báo có link tới runbook.

## P7 — Reliability & incident simulation (3–4 weeks, then recurring)

> 🇻🇳 Độ tin cậy và mô phỏng sự cố.

**Scope.** Fault injection with free tools (Toxiproxy for latency/timeouts, `docker pause/stop`, disk fill, connection-pool exhaustion, slow query, queue backlog, memory leak in the worker, bad deploy, broken migration, expired certificate, silently failing backup cron). The `chaos-master` skill picks a scenario **without telling you** and injects it; you detect it through alerts, investigate, mitigate, and write `INC-xxx` + blameless postmortem with timeline. Quarterly DR drill: full restore, measured RTO/RPO against the targets in `backup/README.md`.
> 🇻🇳 Gây lỗi bằng công cụ miễn phí. Skill `chaos-master` chọn kịch bản **mà không báo cho bạn** rồi gây lỗi; bạn phát hiện qua cảnh báo, điều tra, khắc phục, viết báo cáo sự cố + postmortem có timeline. Diễn tập DR định kỳ, đo RTO/RPO.

**Done when.** ≥ 8 incidents documented, each with at least one preventive action implemented; one DR drill meets RTO < 30 min.
> 🇻🇳 Hoàn thành khi: ít nhất 8 sự cố có tài liệu, mỗi sự cố có ít nhất một hành động phòng ngừa đã thực hiện; một lần diễn tập DR đạt RTO < 30 phút.

## P8 — Performance & security (3–4 weeks)

> 🇻🇳 Hiệu năng và bảo mật.

**Scope.** k6 scenarios (smoke, load, stress, soak) with thresholds in CI; find and fix bottlenecks (N+1, indexes, caching, PHP-FPM/OPcache tuning) with before/after p50/p95/p99, RPS and error rate. Threat model (STRIDE) of the system; OWASP ASVS level 1 checklist; OWASP ZAP baseline scan; fix findings; security chapter of the handbook.
> 🇻🇳 Kịch bản k6 có ngưỡng trong CI; tìm và sửa nút thắt, có số liệu trước/sau. Threat model STRIDE; checklist OWASP ASVS cấp 1; quét ZAP; sửa lỗi phát hiện.

**Done when.** A performance report with numbers and a security report with all high findings closed.
> 🇻🇳 Hoàn thành khi: có báo cáo hiệu năng với số liệu và báo cáo bảo mật đã đóng mọi lỗi mức cao.

## P9 — Developer tools (6–8 weeks, three independent sub-projects)

> 🇻🇳 Công cụ cho developer — ba dự án con độc lập.

| Sub-project | Description | Stack |
|---|---|---|
| 9a Network Inspector | Browser extension (Manifest V3, DevTools panel): captures requests (method, status, headers, timing, size, initiator), groups by domain, flags slow/failed calls, exports HAR | TypeScript |
| 9b Request Replay | CLI that reads HAR, removes secrets (cookies, tokens), replays against a local environment and diffs responses; reused as an API regression tool | Go |
| 9c Media Downloader | Queue worker for **authorized media only**: parse HLS/DASH manifests, choose quality, download segments concurrently with retry/backoff, resume, checksum, merge (ffmpeg), store in MinIO, live progress | Go + Laravel API + MinIO |

> 🇻🇳 9a: extension trình duyệt bắt request, xuất HAR. 9b: CLI Go đọc HAR, xoá secret, replay vào môi trường local và so sánh response. 9c: worker tải media **được phép tải**: đọc manifest HLS/DASH, tải song song có retry, resume, checksum, ghép file, lưu MinIO.

**Done when (each).** Its own REQ, design doc, tests, release, and a README with a demo GIF.
> 🇻🇳 Mỗi dự án con có REQ, design doc, test, release và README có GIF demo.

## P10 — Portfolio (continuous, first pass 2 weeks after P5)

> 🇻🇳 Portfolio — liên tục.

**Scope.** Portfolio site (repurposed `nextjs-docs`) with case studies in the format *problem → what I did → result with numbers*; root README rewritten as a project story with links to evidence; a read-only `viewer` demo account; CV bullets drafted from each phase's release notes.
> 🇻🇳 Trang portfolio với các case study theo dạng vấn đề → việc đã làm → kết quả có số liệu; README gốc viết lại thành câu chuyện dự án; tài khoản demo chỉ đọc; gạch đầu dòng CV soạn từ release notes mỗi giai đoạn.

## P11 — Kubernetes (optional)

> 🇻🇳 Kubernetes — tuỳ chọn.

Only when a real need appears (e.g. scaling the downloader workers), using k3d/kind locally. Requires an ADR.
> 🇻🇳 Chỉ làm khi có nhu cầu thật (ví dụ scale worker downloader), dùng k3d/kind local. Cần có ADR.

## Recurring habits (from P1 onward)

> 🇻🇳 Thói quen định kỳ (từ P1).

| Habit | Frequency | Output |
|---|---|---|
| Daily report | Each working session | `docs/reports/daily/YYYY-MM-DD.md` (5 lines) |
| Weekly review | Weekly | `docs/reports/weekly/YYYY-Www.md`, board groomed |
| Retrospective | End of each phase | `docs/reports/retro/Px.md` |
| Dependency updates | Weekly (Dependabot) | Merged PRs |
| Game day | Monthly from P7 | One `INC-xxx` |
| DR drill | Quarterly from P7 | Drill report with RTO/RPO |
