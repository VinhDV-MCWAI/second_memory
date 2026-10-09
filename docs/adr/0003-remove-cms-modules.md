# ADR-0003 — Remove the CMS modules instead of keeping them dormant

| | |
|---|---|
| Status | Partly superseded by [ADR-0012](0012-restore-features-dev-first.md) (2026-10-09): end-user, department and policy management come back; the content CMS stays removed. Was: Accepted |
| Date | 2026-10-07 |
| Deciders | PO, TL |
| Related | [REQ-001](../requirements/REQ-001-slim-down.md), [RFC-001](../design/RFC-001-slim-down.md) |

## Context

Since authoring moved to Obsidian, the content CMS, page decoration modules (sliders, banners, setting links, socials), end-user management and the department/policy structure have no user. They still cost upgrade work, test time and attention, and they make the data model harder to understand for the next domain.

> 🇻🇳 Từ khi việc viết tài liệu chuyển sang Obsidian, các module CMS, trang trí trang, quản lý người dùng cuối và cấu trúc phòng ban không còn người dùng nhưng vẫn tốn công nâng cấp, thời gian test và làm mô hình dữ liệu khó hiểu.

## Options

1. **Keep them dormant** (hidden from the menu or behind feature flags). No migration risk; all maintenance cost remains.
2. **Delete the code, keep the tables.** Smaller code; dead tables and FKs remain.
3. **Delete code and tables**, after exporting the content that has value, with the legacy version recoverable from tag `v1.0.0` and backups.

## Decision

Option 3, following the slices in RFC-001. Existing content is exported to Markdown before its tables are dropped; files in object storage are kept.

> 🇻🇳 Chọn phương án 3 theo các lát cắt trong RFC-001. Nội dung cũ được xuất ra Markdown trước khi drop bảng; file trong object storage được giữ nguyên.

## Consequences

- The public docs API disappears (breaking change → `v2.0.0`); the docs site shows a "content moved" page until it becomes the portfolio site.
- Restoring a removed module means checking out `v1.0.0` code and restoring a backup; there is no in-app undo.
- About 35 of 43 tables (including history tables), 2 views and 2 triggers disappear over Phase 2.

> 🇻🇳 API docs public bị xoá (thay đổi phá vỡ tương thích → `v2.0.0`); site docs hiển thị trang "đã chuyển". Muốn khôi phục module phải lấy code `v1.0.0` và restore backup.
