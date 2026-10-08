# ADR-0009 — PostgreSQL full-text search on unaccented text, trigram fallback for typos

> 🇻🇳 Tìm kiếm bằng full-text của PostgreSQL trên văn bản đã bỏ dấu; trigram làm phương án dự phòng khi gõ sai chính tả.

| | |
|---|---|
| Status | Accepted |
| Date | 2026-10-08 |
| Deciders | TL |
| Related | [REQ-002](../requirements/REQ-002-skill-ledger.md) US-5, NFR; [RFC-002](../design/RFC-002-skill-ledger.md) §9 Q2; [spike](../reports/spikes/search-2026-10-08/README.md); research note [`docs/search.md`](../search.md); backlog P3-04, P3-08 |

## Context

REQ-002 US-5: one admin search box over skills, goals and evidence; Vietnamese typed without diacritics must match ("ky nang" → "Kỹ năng"); typo tolerance is nice-to-have; p95 < 300 ms at ~100 skills, ~1,500 evidence items. `docs/search.md` (the owner's research note) asks for an inverted index with ranking and leans to self-hosted open source over a cloud service.

The spike measured two PostgreSQL options on generated Vietnamese / English data (details and scripts in the spike folder):

| | Full-text (`tsvector`, `simple` config, unaccented) | Trigram (`pg_trgm`, unaccented) |
|---|---|---|
| No diacritics / prefix | ✓ / ✓ | ✓ / ✓ |
| Typos | ✗ | ✓ with `word_similarity` threshold 0.4 |
| Ranking | `ts_rank` | distance only |
| p95, 1.5k rows | 0.64 ms | 20 ms |
| p95, 150k rows | 24 ms | 2,252 ms |

> 🇻🇳 Bối cảnh: REQ-002 cần một ô tìm kiếm, tìm được tiếng Việt không dấu, chịu lỗi chính tả là tuỳ chọn, p95 < 300 ms. Spike đo hai phương án của PostgreSQL: full-text nhanh, có xếp hạng, mở rộng tốt nhưng không chịu lỗi chính tả; trigram chịu lỗi chính tả nhưng chậm dần khi dữ liệu lớn.

## Options

1. **Meilisearch / Typesense** (the "open-source engine" of `docs/search.md`). Best typo tolerance and relevance out of the box. A new container, a sync pipeline from PostgreSQL and a second source of truth — for ~2k rows.
2. **`LIKE '%…%'`** on unaccented text. Simple, no ranking, a full scan per query; the option `docs/search.md` already rejects.
3. **Full-text only.** Fast and ranked; no typo tolerance.
4. **Trigram only.** Typos handled; weaker ranking; scales badly.
5. **Full-text first, trigram fallback when full-text finds nothing.** Ranked results in the normal case, typo tolerance when it matters, both inside PostgreSQL.

> 🇻🇳 Các phương án: (1) Meilisearch/Typesense — chịu lỗi tốt nhất nhưng thêm container, pipeline đồng bộ và một nguồn dữ liệu thứ hai cho ~2 nghìn dòng; (2) `LIKE` — quét toàn bảng, không xếp hạng; (3) chỉ full-text; (4) chỉ trigram; (5) full-text trước, trigram khi full-text không có kết quả.

## Decision

We choose **option 5**.

> 🇻🇳 Chọn **phương án 5**.

- Migration enables `unaccent` and `pg_trgm` (both are *trusted* extensions since PostgreSQL 13, so the database owner can create them without superuser) and creates `f_unaccent(text)`, an `IMMUTABLE` SQL wrapper around `unaccent('public.unaccent', …)`. It is the only SQL function in the schema; it normalizes text and holds no business logic, which keeps ADR-0005's "rules live in PHP" intact. `down()` drops it.
- Each searchable table (`skill`, `evidence`, `learning_goal`) gets a generated column `search_text = f_unaccent(lower(<fields>))` and two GIN indexes: `to_tsvector('simple', search_text)` and `search_text gin_trgm_ops`. Generated columns keep the index correct for every writer (admin, importer, seeders) with no PHP hook.
- Query (in a repository, P3-08): normalize the input with the same `f_unaccent(lower(?))` in SQL; run `websearch_to_tsquery('simple', …)` with the last word as a prefix (`:*`), order by `ts_rank`, 10 per type. If all three types return nothing, run the trigram query (`word_similarity` threshold 0.4, ordered by distance). The response says which mode answered (`match: "exact" | "fuzzy"`), so the UI can show "did you mean …" wording.
- Re-evaluate when any searchable table passes ~50k rows or p95 passes 100 ms in the k6 baseline: then trigram moves behind a stricter threshold or option 1 gets a new ADR.

> 🇻🇳 Chi tiết: migration bật `unaccent`, `pg_trgm` (extension "trusted", không cần superuser) và tạo hàm `f_unaccent` IMMUTABLE — hàm SQL duy nhất, chỉ chuẩn hoá văn bản, không chứa nghiệp vụ. Mỗi bảng tìm kiếm có cột sinh `search_text` và hai index GIN (full-text và trigram). Truy vấn: full-text có tiền tố cho từ cuối, xếp hạng `ts_rank`; nếu không ra gì thì chạy trigram ngưỡng 0.4; response báo chế độ khớp (`exact` / `fuzzy`). Xem lại khi bảng vượt ~50 nghìn dòng hoặc p95 vượt 100 ms.

Implementation note (P3-08, 2026-10-08): `learning_goal` got **no** `search_text` column. A goal is found by its note *and* its skill's name ("postgres" should find "PostgreSQL → Independent"), which spans two tables, so a generated column cannot hold it; goals are a few dozen rows, so the expression is evaluated per query without an index. Migration `2026_10_08_100003_add_ledger_search`; `down()` drops the columns and `f_unaccent` but keeps the extensions.

> 🇻🇳 Ghi chú triển khai (P3-08): bảng `learning_goal` **không** có cột `search_text`. Mục tiêu được tìm theo ghi chú *và* tên kỹ năng (nằm ở hai bảng nên cột sinh không chứa được); số mục tiêu rất ít nên tính biểu thức lúc truy vấn, không cần index. `down()` xoá cột và hàm, giữ extension.

## Consequences

- **Easier:** no new service; search stays transactional with the data (an item is searchable the moment it is saved); the spike scripts double as a regression check for the normalization.
- **Harder:** removing diacritics merges words ("ký", "kỳ", "kỹ" → "ky"), so some extra matches appear; ranking limits the damage. `f_unaccent` depends on the `unaccent` rules file — if it changes after a PostgreSQL upgrade, `search_text` must be recomputed (`UPDATE … SET` is not possible on generated columns; drop and re-add the column, documented in the upgrade runbook when it happens).
- **Tests:** feature tests run against the real PostgreSQL `testing` DB, so the extensions, function and indexes are exercised, including "ky nang" and one typo (P3-08).

> 🇻🇳 Hệ quả: **dễ hơn** — không thêm dịch vụ, tìm được ngay sau khi lưu. **Khó hơn** — bỏ dấu làm gộp từ ("ký", "kỳ", "kỹ" → "ky") nên có kết quả thừa, xếp hạng giảm bớt ảnh hưởng; nếu file quy tắc `unaccent` đổi sau khi nâng cấp PostgreSQL thì phải tạo lại cột `search_text`. **Test** — chạy trên PostgreSQL thật nên kiểm tra được cả extension, hàm và index.
