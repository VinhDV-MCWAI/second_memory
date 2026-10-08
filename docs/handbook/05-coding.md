# 05 — Coding standards

> 🇻🇳 Chuẩn viết code.

Standards that a tool can check are enforced by the tool, not by reviewers. The detailed per-stack rules live in `.claude/rules/` (read by both humans and the AI assistant); this chapter is the summary and the "why".

> 🇻🇳 Quy tắc nào máy kiểm được thì để máy kiểm, không để reviewer nhắc. Quy tắc chi tiết từng stack nằm ở `.claude/rules/`; chương này là bản tóm tắt và lý do.

## General rules (all languages)

> 🇻🇳 Quy tắc chung cho mọi ngôn ngữ.

| Rule | Why |
|---|---|
| Code, identifiers and comments in English | Readable by any team and by tools |
| No magic values: enums, constants, config, message keys | One place to change; meaning is named |
| Names say what, comments say why | Comments that repeat the code rot |
| Small functions with one job; early returns over nesting | Easier to test and review |
| Fail loudly at boundaries: validate input, never swallow exceptions | Silent failures become incidents |
| No dead code, no commented-out code, no `.bak` files | Git keeps history |
| Config from environment through one config layer | Same build runs in every environment |
| Log events with context (IDs), never secrets or personal data | Logs are read during incidents and may leak |
| Dependencies: add only with a reason in the PR | Every dependency is a security and upgrade cost |
| Simple over clever; an abstraction needs at least two users | Premature abstraction is the most common over-engineering |

## Per stack

> 🇻🇳 Theo từng stack.

| Stack | Formatter / linter | Static analysis | Tests | Detailed rules |
|---|---|---|---|---|
| PHP / Laravel | Pint (PSR-12, Laravel preset) | Larastan level 6, Rector | PHPUnit 12 | [.claude/rules/backend-laravel.md](../../.claude/rules/backend-laravel.md) |
| TypeScript / Next.js | Prettier, ESLint flat config | `tsc --strict` | Vitest + Testing Library + MSW | [.claude/rules/frontend-nextjs.md](../../.claude/rules/frontend-nextjs.md) |
| Shell, Docker, CI | shellcheck | – | Run the script | [.claude/rules/infra.md](../../.claude/rules/infra.md) |
| Go (from P9) | `gofmt`, `go vet` | `staticcheck` | `go test -race` | Added with the first Go ADR |
| Python (from P3) | `ruff format`, `ruff check` | `mypy --strict` | `pytest` | Added with the first Python ADR |

Commands: `make lint`, `make analyse`, `make test`, `make fe-test`, or everything with `make verify`.

> 🇻🇳 Lệnh: `make lint`, `make analyse`, `make test`, `make fe-test`, hoặc tất cả bằng `make verify`.

## Backend layering (Laravel)

> 🇻🇳 Phân tầng backend.

```text
Route → FormRequest (validate) → Controller (thin) → Service (business rules) → Repository (queries) → Model
                                         └→ Resource (JSON shape)        └→ Events / Jobs (async)
```

## Error handling and logging

> 🇻🇳 Xử lý lỗi và ghi log.

- HTTP status: 401 not authenticated, 403 not allowed, 404 not found, 409 conflict, 422 validation, 5xx = our fault (always logged).
- Response envelope: `{ data, error: { status, code, messages } }`; internal messages are hidden in production.
- Log levels: `error` = needs action, `warning` = unexpected but handled, `info` = business event, `debug` = development only.
- From Phase 6: structured JSON logs with `request_id` and `trace_id`.

## Code review as the last gate

Anything a linter cannot judge (naming quality, layering, test relevance) is checked with the review checklist in [04-git.md](04-git.md#code-review-checklist).

> 🇻🇳 Những gì linter không đánh giá được thì kiểm bằng checklist review.
