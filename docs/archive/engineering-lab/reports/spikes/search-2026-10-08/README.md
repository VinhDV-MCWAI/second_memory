# Spike: search for the Skill Ledger (P3-04, 2026-10-08)

> 🇻🇳 Spike tìm kiếm cho Skill Ledger: so sánh full-text search và trigram của PostgreSQL trên dữ liệu mẫu tiếng Việt. Kết quả dùng cho [ADR-0009](../../../adr/0009-postgres-search.md).

**Question.** Which PostgreSQL search fits REQ-002 US-5: Vietnamese without diacritics ("ky nang" → "Kỹ năng"), optional typo tolerance, p95 < 300 ms at ~100 skills / 1,500 evidence items?

**Setup.** `ml-postgres` (PostgreSQL 16.15, Alpine image; `unaccent` 1.1 and `pg_trgm` 1.6 available). Throwaway database `spike_search`, dropped afterwards (never the dev DB). Mixed Vietnamese / English titles and summaries generated from word pools. Scripts in this folder: `create database spike_search;`, then run them in order with `psql -d spike_search < 0N-*.sql`; `drop database spike_search;` at the end.

**Candidates.**
- **A — full-text:** generated column `to_tsvector('simple', f_unaccent(title || ' ' || summary))` + GIN index. `simple` config because PostgreSQL ships no Vietnamese stemmer; `f_unaccent` is an `IMMUTABLE` SQL wrapper around `unaccent('public.unaccent', …)` (plain `unaccent()` is only `STABLE`, so it cannot be used in an index or generated column).
- **B — trigram:** GIN `gin_trgm_ops` index on `f_unaccent(lower(title || ' ' || summary))`, queried with `word_similarity` (`<%`).

## Results

| Check | A full-text | B trigram |
|---|---|---|
| "ky nang" finds "Kỹ năng …" | ✓ (60 rows) | ✓ (60) |
| "Kỹ năng" (with accents) | ✓ same 60 | ✓ |
| Prefix while typing "ky nan" | ✓ with `nan:*` | ✓ |
| Typo "ky nagn" | ✗ 0 | ✓ 60 |
| Typo "postgersql" (transposition) | ✗ | ✗ at default threshold 0.6 (similarity 0.47); ✓ at 0.4 |
| Missing letter "postgrsql" | ✗ | ✓ (0.62) |
| Relevance ranking | `ts_rank` | distance only (`<<->`) |
| p50 / p95 / max, 1.5k rows (50 queries) | 0.21 / **0.64** / 4.7 ms | 3.2 / **20** / 22.5 ms |
| p50 / p95 / max, 150k rows (100×) | 17 / **24** / 26 ms | 333 / **2,252** / 2,886 ms |
| Index size at 150k rows (heap 85 MB) | 10 MB | 28 MB |

Other findings:

- `unaccent` also maps `đ`/`Đ` → `d`/`D` ("Đà Nẵng đường" → "Da Nang duong").
- Removing diacritics merges words: "ký", "kỳ", "kỹ" all become "ky". Searching "ky nang" therefore also matches "kỳ thi … năng suất" when both tokens appear. Acceptable for a personal ledger; ranking puts the real phrase higher.
- The 150k trigram numbers are pessimistic (the scale-up copied rows, so many near-identical texts pass the index and need a recheck), but they show trigram does not scale like full-text.

## Conclusion

Full-text is the primary search (fast, ranked, scales). Trigram on the same normalized text is a fallback only when full-text finds nothing, which covers typos at the REQ-002 volume within budget. Decision and details: [ADR-0009](../../../adr/0009-postgres-search.md).

> 🇻🇳 Kết luận: dùng full-text làm tìm kiếm chính (nhanh, có xếp hạng, mở rộng tốt); trigram trên cùng văn bản đã bỏ dấu chỉ dùng khi full-text không ra kết quả, đủ để chịu lỗi chính tả ở quy mô REQ-002.
