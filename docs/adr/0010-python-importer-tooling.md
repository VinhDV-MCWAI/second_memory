# ADR-0010 — Python tooling for the Obsidian importer, and how the CLI authenticates

> 🇻🇳 Công cụ Python cho importer Obsidian (phiên bản, đóng gói, chạy trong Docker), hợp đồng giữa note và API, và cách CLI xác thực.

| | |
|---|---|
| Status | Accepted |
| Date | 2026-10-08 |
| Deciders | TL |
| Related | [REQ-002](../requirements/REQ-002-skill-ledger.md) US-4, Q6, Q7; [RFC-002](../design/RFC-002-skill-ledger.md) §4.5, §9 Q3; [ADR-0004](0004-sanctum-spa-cookie-auth.md); [ADR-0005](0005-owner-viewer-roles.md); [ADR-0008](0008-code-first-openapi-contract.md) contract row `evidence/import`; backlog P3-13, P3-14 |

## Context

REQ-002 US-4: a CLI run by hand imports the Obsidian notes marked `publish: true` as `note`-type evidence; running it twice changes nothing; a removed note is hidden, never deleted; unknown skills are reported, not created. RFC-002 §4.5 already fixes the server side: one owner-only endpoint `POST /api/admin/evidence/import` receives the **complete** list of published notes, upserts by `external_key` in one transaction and hides imported rows missing from the list. Three things are still open:

1. **Tooling.** Python is the roadmap's language for the importer, but this is the first Python code in the repo. Nothing runs on the host (no PHP, Node or Python installed); everything runs in Docker.
2. **Note → API contract.** Which frontmatter fields are read, how a note becomes the request item of the ADR-0008 contract row, and what the stable `external_key` is. One detail the earlier records did not settle: `evidence.url` is required and must be http(s), but a note has no such URL by itself (`obsidian://` links fail the rule on purpose).
3. **Authentication.** ADR-0004 gives the API one way in: the SPA session cookie, which needs a browser-style flow (`csrf-cookie`, `Referer` from a stateful domain, `X-XSRF-TOKEN`). ADR-0004 itself says a non-browser client would need Sanctum API tokens, as a separate decision. `laravel/sanctum` is installed; its `personal_access_tokens` table is not migrated.

> 🇻🇳 Bối cảnh: REQ-002 US-4 cần CLI chạy tay để import note `publish: true`, chạy hai lần không đổi gì, note bị gỡ thì ẩn, kỹ năng lạ chỉ báo cáo. Phía server đã chốt ở RFC-002 §4.5 (một endpoint owner-only nhận toàn bộ danh sách note). Còn mở ba việc: (1) công cụ Python — lần đầu repo có Python, máy host không cài gì, mọi thứ chạy trong Docker; (2) hợp đồng giữa note và API — đọc trường nào, `external_key` là gì, và `url` bắt buộc là http(s) trong khi note không có URL như vậy; (3) xác thực — ADR-0004 chỉ có cookie session cho trình duyệt, chính ADR-0004 nói client không phải trình duyệt cần Sanctum API token và phải quyết định riêng.

## Options

### Authentication

1. **Log in like the SPA** (cookie jar, CSRF cookie, `Referer`, password in an env var). No server change. The CLI copies a browser flow it does not need, keeps the owner's password on disk and must pretend to be a stateful domain; the audit cannot tell an import from a click in the admin.
2. **Static shared secret** in `laravel-api/.env`, checked by a custom middleware. Tiny. No actor (the audit log needs one, REQ-002 NFR), no expiry, rotation means editing env files on both sides, and a hand-written token check is exactly what PRB-001 removed.
3. **Sanctum personal access token with one ability.** The framework's own answer for first-party non-browser clients, already installed: tokens are random, stored as SHA-256 hashes, belong to an admin (so the audit actor is real), can expire and be revoked one by one, and carry abilities that limit what they may call.
4. **Passport / OAuth client credentials.** Standard machine-to-machine flow; a new dependency, keys and an OAuth server for one CLI run by one person.

### Tooling

5. **Plain `pip` + `requirements.txt`.** Familiar; no lock of transitive versions unless hashes are maintained by hand, no single place for tool config.
6. **`uv` + `pyproject.toml` + `uv.lock`.** One file for metadata, dependencies and tool config (PEP 621), a cross-platform lock, fast installs in the image. Newer tool, but the de-facto standard by 2026.
7. **Poetry.** Mature, lock file, but its own non-standard config sections and a slower resolver; nothing it gives that option 6 lacks here.

> 🇻🇳 Phương án xác thực: (1) đăng nhập như SPA — không đổi server nhưng phải giả lập trình duyệt, lưu mật khẩu, audit không phân biệt được; (2) secret dùng chung trong `.env` — đơn giản nhưng không có người thực hiện cho audit, không hết hạn, tự viết kiểm tra token đúng kiểu PRB-001 đã bỏ; (3) **Sanctum personal access token một ability** — cách chuẩn của framework, đã cài, token lưu dạng hash, gắn với admin, có hạn và thu hồi từng cái; (4) Passport/OAuth — quá nặng. Phương án công cụ: (5) `pip` + `requirements.txt` — không khoá phụ thuộc bắc cầu; (6) **`uv` + `pyproject.toml` + `uv.lock`** — một file cấu hình, có lock, cài nhanh; (7) Poetry — không thêm được gì so với (6).

## Decision

We choose **option 3** for authentication and **option 6** for tooling.

> 🇻🇳 Chọn **phương án 3** cho xác thực và **phương án 6** cho công cụ.

### Authentication (implemented in P3-14)

- Migrate Sanctum's `personal_access_tokens` table; the admin model uses `HasApiTokens`.
- The import route is `POST /api/admin/evidence/import` with `auth:sanctum`, the `abilities:evidence:import` middleware and the existing owner gate (ADR-0005). A session login from the SPA also passes (Sanctum's transient token allows every ability), so the owner could call it from the browser; a viewer never can.
- Tokens are minted only by an artisan command run by whoever has shell access, never by an HTTP route: `php artisan ledger:import-token <login_id> [--days=90]` prints the plain token once (Sanctum stores the hash). The admin must be an active owner. Default lifetime 90 days (`expires_at`; Sanctum rejects expired tokens). `--revoke` deletes that admin's import tokens. Ability and token name are constants, not literals.
- Every write by the import is audited with the token's admin as the actor and the request marked as coming from the importer (ADR-0006 `action` / context, decided with the code).
- Throttle the route (`throttle:10,1`) — one run per manual invocation is the expected rate.
- The CLI reads the token from `LEDGER_API_TOKEN` and sends `Authorization: Bearer …`; it never prints it. The token goes in a git-ignored env file next to the vault or in the shell, never in the repo.

> 🇻🇳 Xác thực (làm ở P3-14): migrate bảng `personal_access_tokens`, model admin dùng `HasApiTokens`. Route import dùng `auth:sanctum` + ability `evidence:import` + gate owner; session SPA của owner cũng gọi được, viewer thì không. Token chỉ được tạo bằng lệnh artisan `ledger:import-token <login_id> [--days=90]` (in token một lần, server chỉ lưu hash; admin phải là owner đang hoạt động; `--revoke` để thu hồi). Ghi audit với người thực hiện là chủ token. Giới hạn 10 lần/phút. CLI đọc token từ `LEDGER_API_TOKEN`, không bao giờ in ra hay commit.

### Tooling

- **Python 3.14** (current stable line with the longest support window left), image `python:3.14-slim`; digest pinning comes with the image policy in P5.
- **Location:** `tools/ledger-importer/`, `src/` layout (`src/ledger_importer/`), its own `pyproject.toml` and `uv.lock`. It is not part of the pnpm workspace.
- **Dependencies, kept minimal:** `httpx` (HTTP client; its `MockTransport` makes API tests need no extra library) and `PyYAML` (frontmatter; the `---` block is split by hand, no frontmatter package). CLI arguments with the standard library `argparse`.
- **Quality gates:** `ruff check` + `ruff format --check` (lint and style), `mypy --strict` (types — the code is small, so strict costs little and catches contract drift), `pytest`. All run in the image; Make targets `importer-lint`, `importer-test` join `make lint` / `make verify`.
- **Running it:** a `Dockerfile` in the tool folder (uv installs from the lock with `--frozen`). Not a service in Docker Compose; it runs on demand: `make import vault=<path> [dry=1]` builds the image, mounts the vault **read-only** at `/vault`, joins the Compose network and calls the API through nginx (`LEDGER_API_URL`, default the internal nginx URL).

> 🇻🇳 Công cụ: Python 3.14, image `python:3.14-slim`; thư mục `tools/ledger-importer/` theo layout `src/`, có `pyproject.toml` và `uv.lock` riêng, không thuộc workspace pnpm. Phụ thuộc tối thiểu: `httpx`, `PyYAML`; tham số dòng lệnh dùng `argparse` có sẵn. Kiểm tra chất lượng: `ruff`, `mypy --strict`, `pytest`, chạy trong image và gắn vào `make lint` / `make verify`. Chạy: có `Dockerfile` riêng, không phải service trong Compose; `make import vault=<đường dẫn> [dry=1]` mount vault chỉ đọc vào `/vault` và gọi API qua nginx.

### Importer contract: note → request item

A note is a `.md` file under the vault root; folders starting with `.` (`.obsidian`, `.trash`, `.git`) are skipped. Frontmatter is the YAML block between the first two `---` lines.

| Frontmatter | Rule | Request field |
|---|---|---|
| `publish` | Only the YAML boolean `true` imports the note; anything else (missing, `"true"` string, `yes`) means "not published" | – |
| – | Vault-relative path, `/` separators, Unicode NFC (macOS writes NFD) | `external_key` |
| `title` | Optional; default the file name without `.md`; ≤ 200 chars | `title` |
| `summary` | Optional; default the first paragraph of the body that is not a heading, with Markdown links reduced to their text (`[[a\|b]]` → `b`, `[t](u)` → `t`); either way cut to 300 chars at a word boundary with `…` | `summary` |
| `date` | Required, `YYYY-MM-DD`, not in the future (API rule) | `occurred_on` |
| `url` | Optional http(s) URL of the published note. Default: `LEDGER_NOTE_BASE_URL` + the path without `.md`, each segment URL-encoded. Neither set → the note is invalid | `url` |
| `tags` | Optional list (or one string); a leading `#` is removed; nested tags kept as written (`lab/p3`) | `tags[]` (matched by name ignoring case; missing tags are created — tags are labels, not admin data) |
| `skills` | Optional list of skill names | `skills[]` (matched ignoring case; unknown names are returned in `unknown_skills[]`, no skill is created, REQ-002 US-4) |

- `type` is always `note`; a `type` key in frontmatter is ignored. `is_public` is not sent: the server decides it (RFC-002 §4.5).
- An **invalid** published note (no date, bad URL, unparsable YAML) is listed in the CLI report and **not sent**. Because the server hides imported rows missing from the list, a broken note would be hidden by accident, so the CLI sends nothing and exits non-zero when any published note is invalid; `--dry-run` shows the same report without calling the write path.
- The note body never leaves the machine except for the derived summary.
- Renaming or moving a note changes its `external_key`: the old row is hidden and a new row is created. Accepted: renames are rare and nothing is lost (the hidden row stays visible in the admin).
- At most 2,000 notes per run (ADR-0008 row; REQ-002 Q9 expects ~500). Above that the CLI stops with a clear message; batching would break "the list is complete" and needs a new decision.
- Exit codes: `0` success, `1` invalid notes (nothing sent), `2` usage or configuration error, `3` API error (auth, validation, network). The run report prints `created`, `updated`, `unchanged`, `hidden`, `unknown_skills` from the response.

> 🇻🇳 Hợp đồng note → API: chỉ file `.md`, bỏ qua thư mục bắt đầu bằng `.`. Chỉ import khi `publish` là boolean `true` thật. `external_key` = đường dẫn tương đối trong vault, dấu `/`, chuẩn Unicode NFC. `title` mặc định là tên file; `summary` mặc định là đoạn đầu tiên không phải tiêu đề, bỏ cú pháp link, cắt 300 ký tự. `date` bắt buộc. `url` lấy từ frontmatter, nếu không có thì ghép `LEDGER_NOTE_BASE_URL` với đường dẫn; thiếu cả hai là note lỗi. Tag thiếu thì server tạo; kỹ năng lạ chỉ báo cáo. Loại luôn là `note`, không gửi `is_public`. Nếu có note publish bị lỗi, CLI không gửi gì và thoát mã khác 0 — vì server sẽ ẩn mọi dòng không có trong danh sách, gửi thiếu là ẩn nhầm. Nội dung note không rời máy. Đổi tên note = ẩn dòng cũ, tạo dòng mới (chấp nhận). Tối đa 2.000 note mỗi lần. Mã thoát: 0 thành công, 1 note lỗi, 2 cấu hình sai, 3 lỗi API.

### Implementation notes (P3-14a, 2026-10-08)

Decided with the code, within the decision above:

- **Ability check in `AdminMiddleware`, not Sanctum's `abilities` middleware.** Sanctum's middleware only guards the route it is put on; every other admin route would still accept the importer token, because `auth:sanctum` accepts any valid token. `AdminMiddleware` (already on every admin route) now takes the abilities as parameters, `auth.admin:evidence:import`: a personal access token gets 403 on any admin route that does not name its ability, reads included, so a leaked token opens only the import route. `credential/me` moved behind `auth.admin` for the same reason. A session login passes as before (transient token). The OpenAPI spec documents 403 on every admin route.
- **Disabled admins.** Sanctum loads the token's admin without the `active-admins` provider, so `AppServiceProvider` adds `Sanctum::authenticateAccessTokensUsing`: the token of a deleted or disabled admin gives 401.
- **Audit marker.** Every audit row written by the import has `new_values.via = "importer"`; the actor is the token's admin. Tags created by the import are audited the same way (`auditable_type = tag`).
- **Response.** `created`, `updated`, `unchanged`, `hidden`, `unknown_skills[]` (first spelling of each unknown name). A row hidden earlier is not counted again; a republished note counts as `updated` and stays private.

> 🇻🇳 Ghi chú triển khai (P3-14a): (1) Kiểm tra ability nằm trong `AdminMiddleware` (`auth.admin:evidence:import`) thay vì middleware `abilities` của Sanctum, vì middleware đó chỉ chặn route nó được gắn, các route admin khác vẫn nhận token. Giờ token chỉ mở được route import, mọi route admin khác (kể cả đọc và `credential/me`) trả 403; session đăng nhập vẫn như cũ. (2) Token của admin bị xoá hoặc bị khoá trả 401. (3) Mọi dòng audit do import ghi có `new_values.via = "importer"`, người thực hiện là chủ token; tag do import tạo cũng được audit. (4) Dòng đã ẩn không bị đếm lại; note publish lại tính là `updated` và vẫn ở trạng thái riêng tư.

### Implementation notes (P3-14b, 2026-10-08)

- **YAML 1.2 booleans.** PyYAML follows YAML 1.1, where unquoted `yes` / `on` are booleans, which would publish `publish: yes` against the rule above. The CLI parses frontmatter with a `SafeLoader` whose only booleans are `true` / `false` (any case), as Obsidian writes them.
- **Broken frontmatter is an invalid note.** A note whose `---` block does not close, is not YAML or is not a mapping cannot say whether it is published; skipping it could hide its row, so it stops the run like any invalid published note.
- **`--dry-run` asks the API.** P3-14a gave the endpoint `dry_run: true` (count only, no write), so `--dry-run` validates locally and then sends the list with `dry_run: true`: the report shows the real `created` / `updated` / `unchanged` / `hidden` numbers. It needs the token like a real run.
- **More than 2,000 published notes** exits `1` (nothing sent), like invalid notes: the vault content, not the configuration, is the problem.
- **Checked against the real API** on the throwaway perf database (not dev): dry run → run → second run `unchanged` only → a removed note `hidden 1` → invalid note exit 1 → wrong token exit 3 (`401`). Make targets and the run against the dev stack are P3-14c.

> 🇻🇳 Ghi chú triển khai (P3-14b): (1) PyYAML theo YAML 1.1 nên `yes`/`on` là boolean — CLI dùng loader chỉ coi `true`/`false` là boolean, để `publish: yes` không publish. (2) Frontmatter hỏng (không đóng, sai YAML, không phải mapping) bị coi là note lỗi và chặn cả lần chạy, vì không biết note có publish không. (3) `--dry-run` gửi `dry_run: true` lên API để có số liệu thật, nên vẫn cần token. (4) Hơn 2.000 note publish → mã thoát 1, không gửi gì. (5) Đã chạy thử với API thật trên DB perf tạm: chạy lần hai không đổi gì, xoá note thì ẩn 1, note lỗi thoát 1, sai token thoát 3. Target Make và chạy trên stack dev thuộc P3-14c.

## Consequences

- **Easier:** no browser emulation or stored password; the audit shows who imported; a leaked token can only import evidence, expires and is revoked with one command; the importer is tested in isolation (pytest with `MockTransport`) while idempotency stays tested in PHP next to the data (RFC-002 §7).
- **Harder:** a second auth path to keep in mind — `personal_access_tokens` is a new table and the auth tests must cover a token without the ability (403), an expired token (401) and a viewer's token (403). A third language in `make verify` adds an image build to the check time. Notes need a `date` (and a URL source) before they can be published — the CLI report says which ones.
- **Next (P3-14):** migration + `HasApiTokens` + token command + import endpoint with feature tests (twice → zero changes, unknown skill, hidden on removal, ability checks); the CLI with pytest for parsing, the summary rule, the `publish` filter, the invalid-note stop and dry-run; Make targets; runbook `docs/runbooks/ledger-import.md` (mint token, dry run, run, revoke); `laravel-api/CLAUDE.md` gains the token auth note.

> 🇻🇳 Hệ quả: **dễ hơn** — không giả lập trình duyệt, không lưu mật khẩu, audit biết ai import, token lộ chỉ import được bằng chứng và thu hồi bằng một lệnh, importer test độc lập còn tính idempotent test ở PHP. **Khó hơn** — thêm một đường xác thực và một bảng, cần test token thiếu ability, hết hạn, của viewer; ngôn ngữ thứ ba làm `make verify` lâu hơn; note phải có `date` và nguồn URL. **Việc tiếp theo (P3-14):** migration, lệnh tạo token, endpoint import kèm feature test, CLI kèm pytest, target Make, runbook `ledger-import.md`, cập nhật `laravel-api/CLAUDE.md`.
