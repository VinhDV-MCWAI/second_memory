# REQ-001 — Remove CMS features replaced by Obsidian

> 🇻🇳 Yêu cầu loại bỏ các tính năng CMS đã được Obsidian thay thế. Đây là lần chạy thử đầu tiên của quy trình tiếp nhận (P1-14); việc thực hiện là Phase 2.

| | |
|---|---|
| Status | Ready |
| Requested by | PO (Lan, simulated) on 2026-10-07 |
| Priority | prio:p2 · Must (P2) |
| Size | large → RFC-001 + ADRs |
| Backlog | P2-01 … P2-13 |

## 0. Original request

> "As you know we've moved all our writing to Obsidian, so most of the old CMS in the admin is dead weight now. Please remove what we no longer need so the app is lean before we start the Skill Ledger. Keep it simple — I don't want a big project out of this, but I also don't want us to lose anything important. Ideally this ships as the next major version by the end of the phase. The site must keep working for anyone who visits it."

**Triage:** not a duplicate; size *large* (removes modules, tables and an API contract); fuzzy words to clarify: "what we no longer need", "simple", "anything important", "keep working".

> 🇻🇳 Phân loại: quy mô lớn (xoá module, bảng, hợp đồng API); các từ mơ hồ cần làm rõ: "những gì không cần", "đơn giản", "thứ gì quan trọng", "vẫn chạy".

## 1. Problem and goal

The admin and the database carry ~70% features nobody uses since authoring moved to Obsidian. They cost maintenance, upgrades and test time, and make the next domain (Skill Ledger) harder to build. **Goal:** a smaller codebase with no loss of valuable data, measured by a before/after table (lines of code, tables, endpoints, tests, test time, image size).

> 🇻🇳 Khoảng 70% tính năng không còn ai dùng nhưng vẫn tốn công bảo trì, nâng cấp, thời gian test. Mục tiêu: codebase nhỏ hơn, không mất dữ liệu có giá trị, đo bằng bảng số liệu trước/sau.

## 2. Scope

**In scope:** content CMS (categories, entries, entry descriptions, layout editor), sliders, banners, setting links, socials, end-user management, departments and policies, the 14 per-entity history tables (replaced by one audit log), the file-manager folder UI; public docs content API.
**Out of scope:** new Skill Ledger features (REQ-002); replacing auth with Sanctum and simplifying roles are done in the same phase but tracked as their own decisions (PRB-001, ADR-0004, ADR-0005).

> 🇻🇳 Trong phạm vi: các module CMS liệt kê ở trên. Ngoài phạm vi: tính năng Skill Ledger; thay auth và đơn giản hoá phân quyền làm cùng giai đoạn nhưng theo dõi bằng quyết định riêng.

## 3. Clarification log

| # | Question (TL → PO) | Answer (PO) | Date |
|---|---|---|---|
| 1 | Which features do you still use weekly in the admin? | Only logging in and managing admins/roles. Everything for writing or decorating pages can go. | 2026-10-07 |
| 2 | There are existing categories and entries in the database. Do we keep that content anywhere before removing the tables? | Yes — good catch. Export them once to Markdown, one file per entry, with title, slug, category and dates in the frontmatter, so I can drop them into Obsidian. Export before anything is deleted. | 2026-10-07 |
| 3 | Uploaded images and files live in object storage. Can they be deleted with the file manager? | No. Keep the files; we only remove the folder-browsing screens. | 2026-10-07 |
| 4 | The public docs site reads CMS content. What should a visitor see after the removal? | Not an error page. A simple page saying the content moved is fine until the portfolio exists. | 2026-10-07 |
| 5 | Do we need the change history of removed entities? | No, the v1.0.0 backup is enough. | 2026-10-07 |
| 6 | "Keep it simple" — is a multi-step database removal (expand/contract, several PRs) acceptable if it is safer? | Yes, as long as each step is small and nothing breaks between steps. Safer wins. | 2026-10-07 |
| 7 | Who needs to log in after this change? | Just you for now. A read-only demo login for recruiters would be nice later, not a blocker. | 2026-10-07 |

> 🇻🇳 Nhật ký làm rõ: câu 2, 3, 4 làm lộ ra ba yêu cầu ẩn quan trọng (xuất nội dung ra Markdown, giữ file trong MinIO, trang "đã chuyển" cho site public). Đây chính là lý do phải hỏi trước khi làm.

## 4. User stories and acceptance criteria

**US-1** As the PO, I want unused CMS modules removed, so that the product is cheaper to maintain.

```text
Scenario: removed modules are gone everywhere
  Given release v2.0.0 is deployed
  When  I open the admin menu and the API route list
  Then  no sliders, banners, setting links, socials, users, departments, policies,
        categories, entries or entry descriptions appear
  And   their tables, history tables, triggers and views no longer exist

Scenario: what stays still works
  Given release v2.0.0 is deployed
  When  I log in and manage admins and roles
  Then  it works as in v1.0.0 and all automated checks pass
```

**US-2** As the PO, I want existing content exported to Markdown, so that nothing written before is lost.

```text
Scenario: export before removal
  Given the database still contains categories and entries
  When  the export command runs
  Then  one Markdown file per entry exists with frontmatter title, slug, category, created/updated dates
  And   running it twice produces the same files (no duplicates)
  And   the export was run and archived before the tables are dropped
```

**US-3** As the PO, I want uploaded files kept, so that images referenced from old content remain available.

```text
Scenario: object storage untouched
  Given files exist in the MinIO buckets
  When  v2.0.0 is deployed
  Then  the object count per bucket is unchanged
```

**US-4** As a visitor, I want a clear page instead of an error, so that the public site never looks broken.

```text
Scenario: docs site after removal
  When  I open /docs or any former docs URL
  Then  I see a "content moved" page with HTTP 200 (or 410 for old entry URLs), not 404/500
```

**US-5** As the PO, I want proof that the app got leaner.

```text
Scenario: before/after metrics
  Then  the v2.0.0 release notes contain lines of code, tables, endpoints, tests,
        test run time and image size before and after
```

## 5. Non-functional requirements

- Each removal step is a separate, revertible PR; `make verify` green after every step.
- A full backup is taken before any destructive migration.

> 🇻🇳 Mỗi bước xoá là một PR riêng, đảo ngược được; `make verify` xanh sau mỗi bước; backup đầy đủ trước mọi migration phá huỷ.

## 6. Definition of Ready

- [x] Goal and out-of-scope stated
- [x] Acceptance criteria testable (QA: export idempotency, object count, HTTP status are checkable)
- [x] Open questions answered (demo login parked → P2-12)
- [x] Dependencies available (v1.0.0 tag, backup script)
- [x] Large → RFC-001 planned (P2-04)
- [x] Split into tasks ≤ 6 sessions (P2-01 … P2-13)

## 7. Plan

See [03-backlog.md](../plan/03-backlog.md#p2--slim-down). New task from clarification: **P2-05b content export to Markdown** (before P2-08/P2-09).

Note: the local dev database has no content rows, so the export is tested with factory data; the owner runs it on the deployed environment before upgrading it to v2.0.0.

> 🇻🇳 DB dev không có nội dung nên lệnh export được test bằng dữ liệu factory; bạn chạy lệnh này trên môi trường đã deploy trước khi nâng lên v2.0.0.
