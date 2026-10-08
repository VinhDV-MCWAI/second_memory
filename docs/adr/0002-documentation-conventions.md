# ADR-0002 — Documentation as code, bilingual, with permanent IDs

| | |
|---|---|
| Status | Accepted |
| Date | 2026-10-07 |
| Deciders | Owner |
| Related | [ADR-0001](0001-engineering-lab-direction.md), [docs/README.md](../README.md) |

## Context

Documentation is now a main deliverable: it is the evidence of how the work is done. Before this decision, docs were scattered (10 Vietnamese files at the repo root, notes in `docs/`, a guide in `laravel-api/docs/`, handoff logs in `.claude/`), partly outdated, and had no fixed structure for explaining *why* something happened. The owner is learning English and wants to drop Vietnamese later, and wants every problem to be understandable by a newcomer and by the owner years later.

> 🇻🇳 Tài liệu giờ là sản phẩm chính — bằng chứng về cách làm việc. Trước đây tài liệu nằm rải rác, một phần đã lỗi thời và không có cấu trúc cố định để giải thích *vì sao*. Bạn đang học tiếng Anh và sẽ bỏ tiếng Việt sau, và muốn mọi vấn đề đều dễ hiểu cho người mới lẫn chính mình nhiều năm sau.

## Options

1. **Wiki outside the repo** (GitHub Wiki, Notion, Obsidian vault). Easy to write; not reviewed in PRs, drifts from code, not part of the repo history.
2. **Separate files per language** (`x.md` + `x.vi.md`). Clean single-language files; two copies drift apart, double maintenance.
3. **Markdown in `docs/`, one file per topic, English followed by Vietnamese lines marked `> 🇻🇳`.** Reviewed with the code, one source, Vietnamese removable by one command.

> 🇻🇳 1. Wiki ngoài repo. 2. Mỗi ngôn ngữ một file. 3. Markdown trong `docs/`, mỗi chủ đề một file, tiếng Anh rồi tới dòng tiếng Việt đánh dấu `> 🇻🇳`.

## Decision

Option 3, with these rules:

- One home: `docs/` (folder map in `docs/README.md`). Package-specific technical notes may stay next to their code (`laravel-api/docs/…`) but are linked from `docs/README.md`.
- Every Vietnamese line starts with `> 🇻🇳` so it can be stripped with `sed`.
- Records have permanent IDs: `REQ-nnn`, `RFC-nnn`, `ADR-nnnn`, `PRB-nnn`, `INC-nnn`; never reused or renumbered.
- ADRs are immutable once Accepted; a change of mind is a new ADR that supersedes the old one.
- Problems and incidents follow the templates (context → cause → impact → options → solution → new problems → improvements).
- Outdated documents are moved to `docs/archive/` with a banner, not deleted.

> 🇻🇳 Chọn phương án 3 với các quy tắc trên: một nơi duy nhất là `docs/`; dòng tiếng Việt luôn bắt đầu bằng `> 🇻🇳`; ID vĩnh viễn; ADR không sửa sau khi Accepted; vấn đề và sự cố theo template; tài liệu lỗi thời chuyển vào `docs/archive/` kèm ghi chú, không xoá.

## Consequences

- Docs changes go through the same PR review as code.
- Writing takes longer while bilingual; the cost disappears when Vietnamese is removed.
- The root `01–10-*.md` files moved to `docs/archive/legacy-architecture/`.

> 🇻🇳 Tài liệu được review qua PR như code. Viết song ngữ tốn thời gian hơn cho tới khi bỏ tiếng Việt. Mười file `01–10` ở thư mục gốc đã chuyển vào archive.
