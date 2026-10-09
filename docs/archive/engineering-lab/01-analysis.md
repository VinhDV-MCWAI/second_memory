# 01 — Analysis: from CMS to Engineering Lab

> 🇻🇳 Phân tích: chuyển từ CMS sang Engineering Lab.

| | |
|---|---|
| Status | Accepted (2026-10-07) |
| Date | 2026-10-07 |
| Inputs | `docs/note_issue` (owner's notes + an earlier AI review), the source code, `.claude/refactor/PLAN.md` + `PROGRESS.md`, root `01–10-*.md` docs |
| Next | [02-roadmap.md](02-roadmap.md), [03-backlog.md](03-backlog.md) |

## 1. TL;DR

- **Keep the repository, change its purpose.** The app stops being a growing CMS and becomes an *Engineering Lab*: a small but real product, run like a professional team runs a product, with every step (request → design → build → test → deploy → monitor → incident → postmortem) written down as evidence.
  > 🇻🇳 **Giữ repo, đổi mục đích.** Ứng dụng không còn là CMS phình to mà là *Engineering Lab*: một sản phẩm nhỏ nhưng thật, vận hành như team chuyên nghiệp, mọi bước đều có tài liệu làm bằng chứng.
- **The product needs a real user, and that user is you.** Replace the CMS domain with a *Skill Ledger*: track what you learn, plan to learn and have applied, linked to evidence (PRs, ADRs, incidents) and to notes in your Obsidian vault. This is the original goal from your notes ("quản lý lịch sử kỹ năng cá nhân"), and it gives every later phase something real to work on.
  > 🇻🇳 **Sản phẩm phải có người dùng thật — chính là bạn.** Thay domain CMS bằng *Skill Ledger*: theo dõi kỹ năng đã học / sẽ học / đã áp dụng, gắn với bằng chứng (PR, ADR, sự cố) và ghi chú trong Obsidian. Đây đúng là mục tiêu gốc của bạn, và nó cho mọi giai đoạn sau một thứ thật để làm.
- **Slimming down is itself the first project.** Removing obsolete features is done as a full simulated lifecycle (change request, impact analysis, migration, release, retrospective). It produces CV evidence on day one instead of being "cleanup".
  > 🇻🇳 **Việc cắt giảm chính là dự án đầu tiên**, làm theo đủ vòng đời (yêu cầu thay đổi, phân tích ảnh hưởng, migration, release, retro) — tạo bằng chứng CV ngay từ đầu chứ không chỉ là "dọn dẹp".
- **Everything runs locally and costs 0.** Docker Compose, Terraform (Docker + MinIO providers), Grafana LGTM, k6, GitHub Free (public repo). No cloud account needed.
  > 🇻🇳 **Mọi thứ chạy local, chi phí 0.**

## 2. Current state (facts from the code, 2026-10-07)

> 🇻🇳 Hiện trạng — số liệu lấy từ code.

| Area | Facts |
|---|---|
| Age | First commit 2023-07-23, 340 commits |
| Backend | Laravel 13 / PHP 8.5, ~20.6k lines in `app/`, ~21.1k lines of tests (598 passing), 52 migrations, 26 route definitions over 4 route files, Larastan level 6, OpenAPI via Scramble |
| Data model | 13 `*_mst` / `*_mgmt` entity tables + **14 `*_hist` history tables**, DB triggers (`after_api_insert`, `after_policy_department_insert`) and views (`admin_permission_view`, `admin_policy_view`) |
| Auth | Hand-written JWT + refresh tokens (`CredentialService`, `JsonWebToken`), custom RBAC (role → feature → API mapping, departments, policies). Known bug: a deleted admin can still refresh tokens (AUTH-GUIDE A13) |
| Admin FE | Next.js 16.3, ~30k lines; feature folders: media 5.3k, content 2.1k, roles 1.5k, master 1.4k, management 1.1k lines; 90 Vitest tests |
| Docs site | `nextjs-docs`, ~2k lines, renders Tiptap content from the API |
| Infra | 10 Compose services (`ml-postgres`, `ml-redis`, `ml-php`, `ml-reverb`, `ml-queue`, `ml-nextjs`, `ml-nextjs-docs`, `ml-nginx`, `ml-minio`, `ml-minio-init`), GitHub Actions CI + CD (self-hosted, blue-green), rclone multi-cloud backup |
| Refactor | P0–P4 mostly done (see `.claude/refactor/PROGRESS.md`). FE6 in progress with **uncommitted changes** on `refactor/fe6-features`. Open: FE6, D1, P5 (I1–I5), FE3 (blocked on auth) |

**Reading:** the code quality is now good (tests, static analysis, CI). The problem is not quality, it is *purpose*: ~70% of the code (content CMS, page decoration, enterprise RBAC, file manager) serves needs you no longer have, because Obsidian took over writing and storing documents.

> 🇻🇳 **Nhận định:** chất lượng code giờ đã tốt. Vấn đề không phải chất lượng mà là *mục đích*: khoảng 70% code (CMS, trang trí trang, RBAC kiểu doanh nghiệp, file manager) phục vụ nhu cầu bạn không còn nữa vì Obsidian đã thay phần viết và lưu tài liệu.

## 3. Review of the ideas in `note_issue`

> 🇻🇳 Nhận xét các ý tưởng trong `note_issue`.

### 3.1 What is right and kept

> 🇻🇳 Những điểm đúng, giữ nguyên.

| Idea | Verdict |
|---|---|
| Turn the project into an "Engineering Lab" instead of abandoning it | ✅ Core direction ([ADR-0001](../adr/0001-engineering-lab-direction.md)) |
| Full lifecycle per feature: requirement → … → postmortem | ✅ Becomes the backbone of the handbook (Phase 1) |
| Simulated incidents with RCA and postmortems | ✅ Phase 7, the strongest CV material |
| Docker → Compose → Terraform → CI/CD → Observability → (Kubernetes) | ✅ Same order in the roadmap; Kubernetes is optional and last |
| Obsidian = knowledge, repo = code + evidence | ✅ Plus: the app *reads* the vault (Skill Ledger import) instead of replacing it |
| Test by risk, not by coverage % | ✅ Test strategy in the handbook |
| Work in "seasons" with clear endings | ✅ Phases with exit criteria and a release tag each |
| "Every technology must solve a concrete problem" | ✅ Enforced by rule: a new tool needs an ADR that names the problem |

### 3.2 What needs correcting

> 🇻🇳 Những điểm cần điều chỉnh.

1. **"Remove testing because it is not needed" → no.** Tests are what make the slimming down safe and are themselves CV evidence. What changes: tests of removed features are deleted *with* the feature; new work follows a risk-based test strategy.
   > 🇻🇳 **Không bỏ testing.** Test là thứ giúp việc cắt giảm an toàn và cũng là bằng chứng CV. Test của tính năng bị xóa thì xóa cùng tính năng; phần mới theo chiến lược test dựa trên rủi ro.
2. **LocalStack Community no longer exists.** Since 2026-03-23 LocalStack needs an account and auth token for every image; a free *Hobby* plan remains for non-commercial use ([LocalStack licensing](https://docs.localstack.cloud/aws/licensing/)). The plan therefore builds IaC on things that are free and local: Terraform with the Docker and MinIO providers. AWS emulation (LocalStack Hobby or an open-source emulator such as MiniStack/Floci) is an optional add-on, not a dependency.
   > 🇻🇳 **LocalStack Community đã ngừng** từ 23/03/2026; còn gói Hobby miễn phí cho mục đích phi thương mại nhưng cần tài khoản. Kế hoạch dùng Terraform với provider Docker + MinIO (miễn phí, chạy local). Giả lập AWS chỉ là tuỳ chọn thêm.
3. **The media downloader must not bypass access control.** Skipping passwords, ad gates or share-to-unlock walls breaks the sites' terms and possibly the law, and it is a bad thing to show an employer. The technical value (HLS/DASH parsing, concurrent segment download, retry, resume, checksum, queue) is fully kept by limiting it to media you are allowed to download: your own files, public-domain / open-license content, and test streams you host yourself.
   > 🇻🇳 **Downloader không được vượt qua cơ chế kiểm soát truy cập** (mật khẩu, quảng cáo, chia sẻ link). Vi phạm điều khoản/pháp luật và không nên đưa vào CV. Giá trị kỹ thuật vẫn giữ nguyên khi giới hạn ở nội dung bạn được phép tải: file của bạn, nội dung public-domain / giấy phép mở, stream test tự host.
4. **One phase at a time.** The notes list ~15 tracks. Running them in parallel ends in "100 technologies, 0 finished products". Rule: one active phase, plus small recurring habits (daily report, weekly review).
   > 🇻🇳 **Mỗi lúc một giai đoạn.** Ghi chú có khoảng 15 hướng; làm song song sẽ thành "100 công nghệ, 0 sản phẩm". Quy tắc: một giai đoạn đang làm + vài thói quen nhỏ lặp lại.
5. **Process must be proportional, or it becomes theatre.** A one-person team that fills 10 forms per change will stop after two weeks. Every template has a "small change" path (a one-line PR description is enough for a typo fix). Full ceremony is for real features and incidents.
   > 🇻🇳 **Quy trình phải tương xứng, nếu không sẽ thành diễn kịch.** Làm một mình mà mỗi thay đổi phải điền 10 form thì 2 tuần là bỏ. Template nào cũng có "đường tắt" cho thay đổi nhỏ; quy trình đầy đủ chỉ dùng cho tính năng thật và sự cố.
6. **The repo is public.** Daily reports, incident notes and seed data must not contain secrets, real personal data or employer information.
   > 🇻🇳 **Repo là public.** Báo cáo hằng ngày, ghi chú sự cố, dữ liệu seed không được chứa secret, dữ liệu cá nhân thật hay thông tin công ty.

### 3.3 Additions you did not ask for (and why they matter)

> 🇻🇳 Những thứ bổ sung ngoài yêu cầu, và lý do chúng quan trọng.

| Addition | Why it matters in real teams | Phase |
|---|---|---|
| **AI-simulated stakeholders** — Claude skills that play PO, QA, Ops, Security and a "chaos master" | You asked to simulate other departments. A PO that writes vague requests forces you to practise clarification; a chaos master that injects a *hidden* fault forces real investigation instead of fixing what you already know | 1, 7 |
| **Definition of Ready / Definition of Done** | The most common source of rework is starting unclear work and "finishing" untested work | 1 |
| **RACI matrix** | Answers "who do I report this to / who approves this" for every activity | 1 |
| **Design doc (RFC) before big changes** | Seniors are judged on written design; it is how ideas get confirmed before code | 1 |
| **ADR (Architecture Decision Record)** | Keeps the *why* of decisions; exactly your question "after a long time, understand where the problem came from" | 0 |
| **User stories + acceptance criteria (Given/When/Then)** | Makes requirements testable; QA writes tests from them | 1, 3 |
| **Expand / contract database migrations** | How real teams change schemas without downtime or data loss; needed to remove 14 history tables safely | 2 |
| **Feature flags** | Ship code dark, turn on later, roll back without redeploy | 5 |
| **SemVer + automated changelog** | Every phase ends with a tagged release and readable release notes | 0, 5 |
| **DORA metrics** (deploy frequency, lead time, change failure rate, time to restore) | Industry-standard way to measure a delivery process; numbers for your CV | 5 |
| **SLI / SLO / error budget** | How SRE teams decide whether to ship features or fix reliability | 6 |
| **Runbooks + game days** | Incidents are handled by following written steps, which are rehearsed | 6, 7 |
| **Blameless postmortem** | Industry norm; shows maturity in interviews | 7 |
| **Threat model (STRIDE) + OWASP ASVS checklist** | Structured security instead of ad-hoc; free tools (ZAP, Trivy, gitleaks) | 8 |
| **SBOM + image scanning + secret scanning in CI** | Supply-chain security is now expected in CI | 5 |
| **Contract tests from OpenAPI** | The FE/BE contract already exists (Scramble → openapi-typescript); tests keep it honest | 3 |
| **Test-data management** (factories, seeders, anonymised fixtures) | Answers your "data" question: reproducible data for dev, tests and incidents | 2, 3 |
| **Case-study format for the portfolio** | Recruiters read 1-page stories (problem → action → result with numbers), not repos | 10 |

## 4. Keep / simplify / remove

> 🇻🇳 Bảng giữ / đơn giản hoá / loại bỏ. Đây là **đề xuất** — quyết định cuối cùng ghi trong ADR ở Phase 2.

| Module | Today | Recommendation | Reason |
|---|---|---|---|
| Custom JWT auth (`CredentialService`, `JsonWebToken`, `token_mst`) | Hand-written, with a known refresh bug | **Replace** with Laravel Sanctum SPA cookie auth | Learning goal reached; a security component should use a maintained library. The transition is documented (ADR + problem record) as a CV story: "why I replaced my own auth" |
| RBAC: roles, features, APIs, API↔role mapping, DB triggers and views | Enterprise-grade, single real user | **Simplify** to Laravel Gates/Policies with 2 roles: `owner`, `viewer` (read-only demo account for recruiters) | Over-engineering for one user (the 2023 lessons doc says the same); triggers/views hide logic from the code |
| Departments, admin↔department, policy↔department, department management | Org structure | **Remove** | No organisation to model |
| Content CMS: category → entry → entry description, `layout_structure`, Tiptap editor | Document authoring | **Remove** authoring; content moves to Obsidian | Obsidian replaced it (your decision) |
| Sliders, banners, setting links, socials | Public-site decoration | **Remove** | Not related to the new purpose |
| `user_mgmt` (end-user accounts with profile fields) | Public user management | **Remove** | No public users |
| 14 `*_hist` tables + history services | One history table per entity | **Replace** with one generic `audit_log` table (or `owen-it/laravel-auditing`, decided by ADR) | Same feature, 1 table instead of 14; good expand/contract exercise |
| File manager (folders, multipart upload, temp uploads, Reverb progress) | 5.3k FE lines, the biggest feature | **Simplify**: keep MinIO + upload API + queue; drop the folder-tree UI | MinIO/S3 + queues are still needed (backups, downloader output, evidence attachments); the explorer UI is not |
| Reverb (WebSocket) | Upload progress events | **Keep for now**, re-decide in Phase 9 | The downloader needs live progress; decide when there is a real consumer |
| Queue worker (`ml-queue`) | Large file jobs | **Keep** | Needed by importer and downloader |
| `nextjs-docs` | Renders CMS content | **Repurpose** into the public portfolio site: Skill Ledger (read-only) + case studies + handbook | Turns the second app into your public showcase |
| Backup/restore (rclone multi-cloud) | Works, untested restore | **Keep & harden**: restore drills with measured RTO/RPO | DR is strong evidence |
| CI/CD (GitHub Actions, self-hosted blue-green) | Works, never fully run on GitHub | **Keep**, extend in Phase 5 | Already valuable |
| Root `01–10-*.md` docs | Vietnamese, outdated | **Archive** to `docs/archive/` | History of the old design; new docs replace them |
| `docs/search.md` | Research note on search | **Keep** as input for a Phase 3 spike (Postgres full-text search) | Real need once the Skill Ledger has data |

## 5. The auth transition (why and how it will be told)

> 🇻🇳 Câu chuyện chuyển đổi auth — vì sao và kể lại thế nào.

Writing your own JWT auth was a deliberate learning exercise and it worked: you understand tokens, refresh, hashing and middleware. Keeping it now carries real risk (a known refresh bug, no security review, maintenance burden) and no new learning. The professional move is to replace it and keep the story:

> 🇻🇳 Tự viết JWT auth là bài tập có chủ đích và đã đạt mục tiêu: bạn hiểu token, refresh, hash, middleware. Giữ nó tiếp thì rủi ro thật (bug refresh, chưa được review bảo mật, tốn công bảo trì) mà không học thêm được gì. Cách làm chuyên nghiệp là thay thế và giữ lại câu chuyện:

1. `PRB-001` problem record: what the custom auth does, its known defects, the risk.
2. `ADR-0004`: options (fix custom JWT / `firebase/php-jwt` / Sanctum SPA cookie) → decision: Sanctum.
3. Migration with tests first (auth feature tests stay green), then removal of the old code.
4. The existing `laravel-api/docs/auth/AUTH-GUIDE.md` is archived as "what I learned building auth by hand".

> 🇻🇳 Trong phỏng vấn, câu "tôi đã tự viết auth để hiểu cơ chế, sau đó chủ động thay bằng thư viện chuẩn vì lý do X, Y, Z" được đánh giá cao hơn cả việc chỉ tự viết hoặc chỉ dùng thư viện.

## 6. Your question about documenting problems and data

> 🇻🇳 Câu hỏi của bạn về việc viết tài liệu cho vấn đề và dữ liệu.

**Documenting problems.** Every non-trivial problem gets a *problem record* (`docs/problems/PRB-xxx.md`) with a fixed structure: context → symptom → root cause → impact → options considered → solution → how it was implemented and verified → new problems it created → follow-up improvements → links. When a follow-up becomes its own problem, it gets a new record linked back, so you can walk the chain from today's code back to the original cause. Template: [templates/problem-record.md](../templates/problem-record.md).

> 🇻🇳 **Ghi lại vấn đề.** Mỗi vấn đề đáng kể có một *problem record* với cấu trúc cố định: bối cảnh → triệu chứng → nguyên nhân gốc → hậu quả → các phương án → giải pháp → triển khai & kiểm chứng → vấn đề mới phát sinh → cải tiến tiếp theo → liên kết. Vấn đề phát sinh thành record mới có link ngược lại, nên có thể lần theo chuỗi từ code hôm nay về nguyên nhân ban đầu.

Which document for which situation:

> 🇻🇳 Loại tài liệu nào cho tình huống nào:

| Situation | Document |
|---|---|
| "We need X" (new need) | `REQ-xxx` requirement |
| "How should we build X?" (before coding) | Design doc / RFC |
| "We chose A over B" (lasting decision) | `ADR-xxxx` |
| "Something is wrong / risky" (not an outage) | `PRB-xxx` problem record |
| "Production is broken now" | `INC-xxx` incident + postmortem |
| "How do I do Y safely?" (repeatable steps) | Runbook |

**Data management** is a handbook chapter (Phase 2–3): seeders and factories for reproducible dev data, never real personal data in the repo, migrations always reversible or expand/contract, backups encrypted, restore tested, and a data dictionary generated from the schema.

> 🇻🇳 **Quản lý dữ liệu** là một chương trong handbook: seeder/factory cho dữ liệu dev tái tạo được, không đưa dữ liệu cá nhân thật vào repo, migration luôn rollback được hoặc theo expand/contract, backup mã hoá, restore có kiểm thử, data dictionary sinh từ schema.

## 7. Impact on the running refactor (`.claude/refactor/PLAN.md`)

> 🇻🇳 Ảnh hưởng tới đợt refactor đang chạy.

| Item | Recommendation |
|---|---|
| FE6 (in progress, uncommitted) | Commit what is done. **Stop splitting files of features that will be removed** (file manager, role wizard, content forms, user form, layout editor) — that work would be thrown away. Close FE6 as "done for kept code". |
| D1 (docs site caching) | Superseded by the portfolio repurpose in Phase 3/10 |
| I1, I4, I5 (Docker hardening, backup, CD) | Moved into Phase 4, 5, 7 |
| I3 (MinIO choice) | Keep pinned `pgsty/minio`; revisit in Phase 4 ADR |
| S6 / FE3 (auth) | Phase 2 (Sanctum) |
| FE7, I2 | Dropped |

After Phase 0 the old `PLAN.md` is frozen and the new roadmap becomes the single plan.

> 🇻🇳 Sau Phase 0, `PLAN.md` cũ được đóng băng; roadmap mới là kế hoạch duy nhất.

## 8. Risks

> 🇻🇳 Rủi ro.

| Risk | Mitigation |
|---|---|
| Scope explosion (too many tools) | One active phase; new tool needs an ADR naming the problem |
| Process theatre (forms nobody uses) | Small-change path in every template; retro each phase drops unused steps |
| Motivation drop in long phases | Each phase ≤ 6 weeks, ends with a release tag and a portfolio entry |
| Deleting something still needed | Phase 0 tags `v1.0.0` (full legacy version recoverable); removal through expand/contract with backups |
| Local machine limits (RAM) when adding observability and tools | Compose profiles: start only what the current task needs |
| Public repo leaks | gitleaks in CI (Phase 5), writing rule in the handbook |

## 9. Open questions for the owner

> 🇻🇳 Câu hỏi cần bạn trả lời (không chặn việc bắt đầu Phase 0).

The owner asked to start implementing without answering these, so the defaults below are in force until changed: 8–10 h/week; target role backend developer (order of Phases 6–9 unchanged); remove list in §4 accepted as written; Obsidian vault is private (importer reads only `publish: true` notes).

> 🇻🇳 Bạn yêu cầu triển khai luôn nên áp dụng mặc định sau cho tới khi bạn đổi: 8–10 giờ/tuần; vị trí backend developer; danh sách xoá ở §4 được chấp nhận; vault Obsidian riêng tư (chỉ đọc note `publish: true`).

1. Hours per week you can spend? The roadmap assumes **8–10 h/week**.
   > 🇻🇳 Mỗi tuần dành được bao nhiêu giờ? Roadmap giả định 8–10 giờ.
2. Target role and date (e.g. backend developer, interviews in 6 months)? It changes the order of Phases 6–9.
   > 🇻🇳 Vị trí nhắm tới và thời điểm? Ảnh hưởng thứ tự Phase 6–9.
3. Confirm the remove list in §4, especially the content CMS and `user_mgmt`.
   > 🇻🇳 Xác nhận danh sách loại bỏ ở mục 4, nhất là CMS nội dung và `user_mgmt`.
4. Is the Obsidian vault private? (The importer only reads notes you mark for publishing, e.g. frontmatter `publish: true`.)
   > 🇻🇳 Vault Obsidian có riêng tư không? Importer chỉ đọc note được đánh dấu công khai.
