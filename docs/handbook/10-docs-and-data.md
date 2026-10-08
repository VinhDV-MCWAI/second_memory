# 10 — Documentation and data

> 🇻🇳 Tài liệu và dữ liệu.

## Which document for which situation

> 🇻🇳 Loại tài liệu nào cho tình huống nào.

| Situation | Record | Location | Template |
|---|---|---|---|
| "We need X" | `REQ-nnn` requirement | `docs/requirements/` (+ issue) | [requirement.md](../templates/requirement.md) |
| "How should we build X?" | `RFC-nnn` design doc | `docs/design/` | [design-doc.md](../templates/design-doc.md) |
| "We chose A over B" | `ADR-nnnn` | `docs/adr/` | [adr.md](../templates/adr.md) |
| "Something is wrong or risky" (not an outage) | `PRB-nnn` problem record | `docs/problems/` | [problem-record.md](../templates/problem-record.md) |
| "Production is broken now" | `INC-nnn` + postmortem | `docs/incidents/` | [incident.md](../templates/incident.md), [postmortem.md](../templates/postmortem.md) |
| "How do I do Y safely?" | Runbook | `docs/runbooks/` | [runbook.md](../templates/runbook.md) |
| What shipped | Release notes | `docs/releases/` | – |
| How work went | Daily / weekly / retro | `docs/reports/` | [daily](../templates/daily-report.md), [weekly](../templates/weekly-report.md), [retro](../templates/retro.md) |

Conventions (bilingual marker, permanent IDs, immutable ADRs): [ADR-0002](../adr/0002-documentation-conventions.md) and [docs/README.md](../README.md).

## Writing so a newcomer understands

> 🇻🇳 Viết sao cho người mới hiểu.

1. **Start with context**: what was the situation, in one paragraph, without assuming the reader knows the code.
2. **Chain cause and effect**: origin → consequence → solution → what appeared next → improvement. Link the previous and next record so the chain can be walked both ways.
3. **Prefer concrete over abstract**: real error messages, numbers, commands, file paths.
4. **Date everything** and say what was true at that date; never silently rewrite history — add an update section.
5. **Explain terms once**, the first time they appear (e.g. "RTO — how long recovery may take").

> 🇻🇳 Bắt đầu bằng bối cảnh; nối chuỗi nguyên nhân–hậu quả và link record trước/sau; dùng ví dụ cụ thể; ghi ngày và không âm thầm sửa lịch sử; giải thích thuật ngữ ở lần đầu xuất hiện.

## Data rules

> 🇻🇳 Quy tắc về dữ liệu.

| Topic | Rule |
|---|---|
| Dev data | Created by seeders/factories (`make fresh` on the dev DB only); reproducible from scratch |
| Test data | Each test builds its own data with factories; tests run on the separate `testing` DB |
| Personal data | Never real personal data in the repo, seeds, screenshots, logs or reports (the repo is public) |
| Secrets | Only in gitignored env files (`setup-env.sh` generates them); never in docs, commits or issues |
| Schema changes | Reversible migrations or expand/contract ([07-release.md](07-release.md#database-migrations)) |
| Backups | Database + object storage + config, same point in time; restore is tested, not assumed (P7) |
| Retention | Backups kept per `backup/README.md`; logs kept short locally (they may contain IDs) |
| Data dictionary | Generated from the schema in P2, with a one-line meaning per table and column |
| Imports | Idempotent: running an import twice must not duplicate data (Obsidian importer, P3) |

> 🇻🇳 Dữ liệu dev tạo bằng seeder/factory; test dùng DB `testing` riêng; tuyệt đối không có dữ liệu cá nhân thật hay secret trong repo; migration đảo ngược được; backup gồm DB + object storage + cấu hình và phải kiểm thử restore; import phải chạy lại được mà không nhân đôi dữ liệu.
