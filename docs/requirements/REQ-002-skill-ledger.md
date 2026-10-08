# REQ-002 — Skill Ledger

> 🇻🇳 Yêu cầu Skill Ledger: theo dõi kỹ năng theo thời gian, có bằng chứng, có trang public chỉ đọc. Đây là domain cốt lõi của Phase 3.

| | |
|---|---|
| Status | Ready |
| Requested by | PO (Lan, simulated) on 2026-10-08 |
| Priority | prio:p1 · Must (P3) |
| Size | large → RFC-002 + ADRs |
| Issue | – (GitHub board not set up yet, P0-06) |
| Backlog | P3-01 … P3-18 |

## 0. Original request

> "Now that the app is lean, let's build the thing we actually wanted: one place that shows what you can do and how you got there. I want to see your skills, how they grew, and the proof behind them — your PRs, decisions, incidents, notes. Recruiters should be able to look at it too, but keep it simple and don't show anything that's just for us. Your notes are in Obsidian anyway, so connect it somehow instead of typing everything twice. Searching should be fast. Let's aim to have it out at the end of this phase."

**Triage:** not a duplicate; size *large* (new domain, new public surface, an importer, search); fuzzy words to clarify: "how they grew", "proof", "anything that's just for us", "connect it somehow", "fast", "simple".

> 🇻🇳 Phân loại: quy mô lớn (domain mới, bề mặt public mới, importer, search). Từ mơ hồ cần làm rõ: "phát triển thế nào", "bằng chứng", "thứ chỉ dành cho nội bộ", "kết nối bằng cách nào đó", "nhanh", "đơn giản".

## 1. Problem and goal

There is no single, trustworthy record of what the engineer can do. Skills live in a CV that is rewritten by hand, the proof (PRs, ADRs, incidents, notes) is scattered across GitHub, this repo and an Obsidian vault, and progress over time is not visible. **Goal:** a Skill Ledger where every skill has a level history and links to evidence, a private planning view (learning goals) for the owner, and a public read-only page for recruiters. Observable outcome: the public page lists the published skills, each with its current level and at least one evidence link, and nothing private appears on it.

> 🇻🇳 Hiện không có nơi nào ghi lại đáng tin cậy những gì kỹ sư làm được: kỹ năng nằm trong CV viết tay, bằng chứng rải rác ở GitHub, repo và Obsidian, không thấy được tiến bộ theo thời gian. Mục tiêu: mỗi kỹ năng có lịch sử cấp độ và link bằng chứng, owner có mục tiêu học riêng tư, recruiter xem trang public chỉ đọc. Kết quả quan sát được: trang public liệt kê kỹ năng đã công khai, mỗi kỹ năng có cấp độ hiện tại và ít nhất một bằng chứng, không lộ gì riêng tư.

## 2. Scope

**In scope:** skills (name, category, tags, visibility), level history on a fixed 4-step scale, learning goals, evidence as links, admin pages for all of them, a public read-only page in `nextjs-docs`, one search box in the admin, a Python CLI that imports published Obsidian notes as evidence, the dashboard showing real counts.
**Out of scope:** file uploads for evidence (links only, Q3); full note bodies in the app (Q6); public search; endorsements or comments from other people; multi-user ledgers (one engineer only); recruiter accounts (the public page needs no login; the read-only admin login is the existing `viewer` role); automatic import on a schedule (the CLI is run by hand).

> 🇻🇳 Trong phạm vi: kỹ năng, lịch sử cấp độ (thang 4 bậc), mục tiêu học, bằng chứng dạng link, trang admin, trang public chỉ đọc, ô tìm kiếm trong admin, CLI Python import note Obsidian, dashboard số liệu thật. Ngoài phạm vi: upload file, nội dung đầy đủ của note, tìm kiếm public, nhận xét từ người khác, nhiều người dùng, tài khoản recruiter, import tự động theo lịch.

## 3. Clarification log

| # | Question (TL → PO) | Answer (PO) | Date |
|---|---|---|---|
| 1 | "How they grew": how should a level be expressed — a free number, stars, or a fixed scale with names? And can a past level be edited? | A fixed scale of four: 1 Learning, 2 Can use with help, 3 Independent, 4 Can teach others. Every change is a dated entry with a short reason. Never edit the past; if you made a mistake, add a new entry. | 2026-10-08 |
| 2 | Besides levels, do you want to plan learning (target level, target date)? Who sees the plan? | Yes: a goal is a skill, a target level, an optional target date and a status (open, achieved, dropped). Goals are private. And please — when a level change reaches the target, mark the goal achieved by itself; I always forget. | 2026-10-08 |
| 3 | "Proof": is evidence always a link, or do you need to upload files (screenshots, PDFs)? This decides whether we keep the media API. | Links are enough: type (PR, ADR, incident, note, other), title, date, an optional short summary. No uploads. | 2026-10-08 |
| 4 | Can one piece of evidence support several skills? | Yes, a PR often shows two or three skills. | 2026-10-08 |
| 5 | "Anything that's just for us": what exactly may appear on the public page? | Only skills I mark public: name, category, current level and the dates of level changes. Evidence only if the evidence itself is marked public — private repo links and private notes must never show. Goals never. Reasons on level entries stay private. | 2026-10-08 |
| 6 | "Connect Obsidian": which notes, and how much of a note goes into the app? | Only notes with `publish: true` in the frontmatter. Bring in the title, a summary (the `summary` field, or the first paragraph cut to 300 characters), tags, date and the skills listed in `skills: [...]`. Not the full text; Obsidian is where I read notes. | 2026-10-08 |
| 7 | If I edit an imported item in the admin and run the importer again, which side wins? What if a note is unpublished or deleted in the vault? | The note wins for the fields it brings in; the importer must never touch levels or anything else typed in the admin. An unpublished or deleted note becomes hidden in the app, not deleted. Running it twice must change nothing. | 2026-10-08 |
| 8 | "Searching should be fast": what do you search for, where, and how fast is fast? | One box in the admin over skills, goals and evidence. It must find Vietnamese without accents: typing "ky nang" finds "Kỹ năng". Under about a third of a second. Typos would be nice, not required. No search on the public page for now. | 2026-10-08 |
| 9 | Rough volume, so we size search and the page? | Around 100 skills, 1,000 evidence items and 500 published notes after a few years. | 2026-10-08 |
| 10 | "Keep it simple" and "end of this phase": what is the smallest version that is already useful, if we run short of time? | Skills, levels, evidence links and the public page. Importer and search are next; goals can come last. | 2026-10-08 |
| 11 | Who may change data in the admin? | Same as now: only the owner writes; the viewer login can read everything in the admin, private items included. | 2026-10-08 |

> 🇻🇳 Nhật ký làm rõ: câu 3 trả lời quyết định mở về media API (không cần upload → RFC-002 đề xuất bỏ); câu 5 và 7 lộ ra hai ràng buộc ẩn quan trọng (bằng chứng có quyền hiển thị riêng; importer không bao giờ ghi đè dữ liệu nhập tay, note bị gỡ thì ẩn chứ không xoá); câu 2 lộ ra yêu cầu tự đánh dấu mục tiêu đạt; câu 8 cho tiêu chí đo được cho "nhanh" và tìm tiếng Việt không dấu.

## 4. User stories and acceptance criteria

**US-1 (Must)** As the owner, I want to record skills with a level history, so that I can see how each skill grew.

```text
Scenario: level history is append-only
  Given a skill "PostgreSQL" created at level 1 "Learning" on 2026-10-01
  When  I change its level to 2 on 2026-11-01 and to 3 on 2026-12-01, each with a reason
  Then  its history shows 3 dated entries, newest first, with the reasons
  And   its current level is 3 "Independent"
  And   no API or screen allows editing or deleting a past entry

Scenario: invalid level
  When  I set a level outside 1–4
  Then  the change is rejected with a validation error and the history is unchanged

Scenario: unique name
  Given a skill "PostgreSQL" exists
  When  I create another skill "postgresql"
  Then  it is rejected as a duplicate (names are unique, case-insensitive)
```

**US-2 (Must)** As the owner, I want to attach evidence links to one or more skills, so that every claim has proof.

```text
Scenario: one evidence, several skills
  Given skills "Laravel" and "PostgreSQL"
  When  I add evidence type PR, title "Audit log expand/contract", URL https://github.com/…/pull/12,
        date 2026-10-07, linked to both skills
  Then  the evidence appears on both skills

Scenario: evidence needs a valid link
  When  I add evidence without a URL, or with a URL that is not http(s)
  Then  it is rejected with a validation error
```

**US-3 (Must)** As a recruiter, I want a public read-only page of the published skills, so that I can judge what the engineer can do without logging in.

```text
Scenario: only public data is shown
  Given public skill "Laravel" at level 3 with one public and one private evidence item
  And   private skill "Rust" and an open goal for "Laravel"
  When  an anonymous visitor opens the public skills page
  Then  "Laravel" is listed with its category, current level and the dates of its level changes
  And   only the public evidence item is shown
  And   "Rust", the goal and the level-change reasons appear nowhere (page or public API)

Scenario: unknown or private skill
  When  an anonymous visitor opens the URL of a private or non-existent skill
  Then  the response is 404 (a private skill is indistinguishable from a missing one)
```

**US-4 (Should)** As the owner, I want published Obsidian notes imported as evidence, so that I don't type them twice.

```text
Scenario: import published notes only
  Given a vault with note A (publish: true, skills: [Laravel], summary "…") and note B (no publish field)
  When  I run the importer
  Then  A becomes a note-type evidence item linked to "Laravel" with its title, summary, tags and date
  And   B is not imported, and no note body is stored

Scenario: idempotent
  When  I run the importer a second time on the same vault
  Then  it reports 0 created, 0 updated, 0 hidden, and the data is unchanged

Scenario: admin data is never overwritten
  Given I changed the level of "Laravel" in the admin
  When  I run the importer again
  Then  the level and level history of "Laravel" are unchanged

Scenario: unpublished note
  Given note A was imported
  When  A loses publish: true (or is deleted) and I run the importer
  Then  its evidence item is hidden (not public, marked as unpublished), not deleted

Scenario: unknown skill in a note
  Given note C lists skills: [Kotlin] and no skill "Kotlin" exists
  When  I run the importer
  Then  C is imported without that link and the run report lists "Kotlin" as unknown (no skill is created)
```

**US-5 (Should)** As the owner, I want one search box over skills, goals and evidence, so that I can find things fast.

```text
Scenario: Vietnamese without accents
  Given evidence titled "Kỹ năng thiết kế database"
  When  I search "ky nang"
  Then  that evidence is in the results, grouped by type, best match first

Scenario: empty result
  When  I search a term that matches nothing
  Then  I see an empty state, not an error
```

**US-6 (Could)** As the owner, I want learning goals that complete themselves, so that my plan stays current.

```text
Scenario: auto-achieve
  Given an open goal "PostgreSQL → level 3 by 2026-12-31" and the skill at level 2
  When  I change the level of "PostgreSQL" to 3
  Then  the goal status becomes "achieved" with that date

Scenario: dropped goals stay
  When  I drop a goal
  Then  it stays in the list with status "dropped" and is not auto-achieved later
```

**US-7 (Should)** As the owner, I want the admin dashboard to show real numbers, so that it is not misleading.

```text
Scenario: real counts
  Given 5 skills (3 public), 12 evidence items and 2 open goals
  When  I open the admin dashboard
  Then  it shows those numbers, and none of the old hard-coded sample values
```

All admin writes follow ADR-0005: the owner may write; a viewer gets 403 on any write and can read everything in the admin. Every admin write is recorded in the audit log (ADR-0006).

> 🇻🇳 US-1 lịch sử cấp độ chỉ thêm, không sửa; US-2 bằng chứng dạng link gắn nhiều kỹ năng; US-3 trang public chỉ hiện dữ liệu công khai, kỹ năng riêng tư trả 404; US-4 importer chỉ lấy note `publish: true`, chạy hai lần không đổi gì, không ghi đè dữ liệu admin, note bị gỡ thì ẩn; US-5 tìm tiếng Việt không dấu; US-6 mục tiêu tự đạt; US-7 dashboard số liệu thật. Mọi thao tác ghi theo ADR-0005 và được ghi audit log.

## 5. Non-functional requirements

- **Performance:** admin search p95 < 300 ms and public page API p95 < 300 ms on the local stack with the stated volume (100 skills, 1,000 evidence, 500 notes); measured by the k6 baseline (P3-16).
- **Security / privacy:** public endpoints are read-only, unauthenticated, rate-limited, and return only public fields (no reasons, no goals, no private evidence, no admin data); private and missing skills both return 404. Evidence URLs must be `http`/`https`.
- **Audit:** every admin create / update / delete and every level change is in the audit log; importer writes are recorded with the importer as the actor.
- **Data:** level history is append-only; nothing imported is ever hard-deleted by the importer.
- **Language:** text may be Vietnamese or English; search ignores diacritics and case.

> 🇻🇳 Hiệu năng: p95 < 300 ms. Bảo mật: API public chỉ đọc, không cần đăng nhập, có giới hạn tần suất, chỉ trả trường công khai; kỹ năng riêng tư và không tồn tại đều 404. Audit mọi thao tác ghi. Lịch sử cấp độ chỉ thêm. Tìm kiếm bỏ qua dấu và hoa thường.

## 6. Definition of Ready

- [x] Goal and out-of-scope stated
- [x] Acceptance criteria testable (QA check in P3-17; each scenario names concrete data)
- [x] Open questions answered or parked — parked for RFC-002: the media API (no longer needed by this feature, Q3 → proposal: remove it, keep the MinIO objects per REQ-001 Q3); how the importer CLI authenticates (P3-13)
- [x] Dependencies available (Sanctum auth, owner/viewer roles, audit log — all in `v2.0.0`)
- [x] Estimate ≤ 6 sessions per task (split into P3-02 … P3-18)
- [x] RFC planned: RFC-002 (P3-02)

## 7. Estimate and plan

Sessions of ~2 hours. Total ≈ 30–36 sessions, which fits the roadmap's 4–6 weeks at 8–10 h/week only at the upper end, so the cut line is set now: if time runs short, US-6 (goals) moves to P4 first, then the typo tolerance in search. The release must contain US-1, US-2, US-3.

| Backlog | Covers | Sessions |
|---|---|---|
| P3-02 RFC-002 design | all | 2 |
| P3-03 contract ADR + spec | all | 2 |
| P3-04 search spike + ADR | US-5 | 2 |
| P3-05 schema slice | US-1, 2, 6 | 2 |
| P3-06 API skills, tags, levels | US-1 | 2–3 |
| P3-07 API goals, evidence | US-2, US-6 | 2–3 |
| P3-08 API search + public | US-3, US-5 | 2 |
| P3-09 contract tests in CI | all | 1–2 |
| P3-10 / P3-11 admin UI, dashboard | US-1, 2, 5, 6, 7 | 4–5 |
| P3-12 public page | US-3 | 2 |
| P3-13 / P3-14 Python ADR + importer | US-4 | 3–4 |
| P3-15 E2E, P3-16 k6 | US-1–3, NFR | 3 |
| P3-17 QA + PO acceptance, P3-18 release + retro | all | 2–3 |

> 🇻🇳 Tổng khoảng 30–36 buổi, vừa khít giới hạn trên của 4–6 tuần. Đã chốt trước đường cắt: thiếu thời gian thì US-6 (mục tiêu) dời sang P4 trước, sau đó tới khả năng chịu lỗi chính tả của search. Release bắt buộc có US-1, US-2, US-3.
