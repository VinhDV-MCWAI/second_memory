-- DB-01: the two slow search statements on evidence and candidate fixes, on the perf seed.
-- Run by perf/explain.sh after the plan capture; everything is rolled back at the end.
-- Terms: 'postgresql' is in 25 % of the seeded evidence (PerfLedgerSeeder::HOT_TERM, the worst case),
-- 'kubernetes' in ~9 % (like every other word since PERF-02; 5 % before).
\set ON_ERROR_STOP on
\pset pager off
BEGIN;
SET LOCAL pg_trgm.word_similarity_threshold = 0.4;

\echo '### A1 current full text, postgresql:* (25 %)'
\set q 'postgresql:*'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where to_tsvector('simple', search_text) @@ to_tsquery('simple', f_unaccent(lower(:'q'))) order by ts_rank(to_tsvector('simple', search_text), to_tsquery('simple', f_unaccent(lower(:'q')))) desc limit 10;
\echo '### A2 current full text, kubernetes:* (~9 %)'
\set q 'kubernetes:*'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where to_tsvector('simple', search_text) @@ to_tsquery('simple', f_unaccent(lower(:'q'))) order by ts_rank(to_tsvector('simple', search_text), to_tsquery('simple', f_unaccent(lower(:'q')))) desc limit 10;

\echo '### B1 current trigram, postgersql'
\set q 'postgersql'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;
\echo '### B2 current trigram, kubernets'
\set q 'kubernets'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;
\echo '### B3 trigram, postgersql, GIN forced (enable_seqscan off)'
SET LOCAL enable_seqscan = off;
\set q 'postgersql'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;
\echo '### B4 trigram, kubernets, GIN forced (enable_seqscan off)'
\set q 'kubernets'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;
SET LOCAL enable_seqscan = on;

\echo '### C GiST trigram index (KNN order by <<->)'
CREATE INDEX evidence_search_gist ON evidence USING gist (search_text gist_trgm_ops);
ANALYZE evidence;
\echo '### C1 postgersql'
\set q 'postgersql'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;
\echo '### C2 kubernets'
\set q 'kubernets'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;
DROP INDEX evidence_search_gist;

\echo '### D stored tsvector column + GIN, rank on the stored vector'
-- A generated column cannot read another one, so it repeats the search_text expression
ALTER TABLE evidence ADD COLUMN search_tsv tsvector
    GENERATED ALWAYS AS (to_tsvector('simple', f_unaccent(lower(title || ' ' || coalesce(summary, ''))))) STORED;
CREATE INDEX evidence_search_tsv ON evidence USING gin (search_tsv);
ANALYZE evidence;
\echo '### D1 postgresql:*'
\set q 'postgresql:*'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where search_tsv @@ to_tsquery('simple', f_unaccent(lower(:'q'))) order by ts_rank(search_tsv, to_tsquery('simple', f_unaccent(lower(:'q')))) desc limit 10;
\echo '### D2 kubernetes:*'
\set q 'kubernetes:*'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where search_tsv @@ to_tsquery('simple', f_unaccent(lower(:'q'))) order by ts_rank(search_tsv, to_tsquery('simple', f_unaccent(lower(:'q')))) desc limit 10;
\echo '### D3 table size with and without the stored vector'
select pg_size_pretty(pg_table_size('evidence')) as evidence_with_tsv, pg_size_pretty(pg_relation_size('evidence_search_tsv')) as tsv_index;
ROLLBACK;
select pg_size_pretty(pg_table_size('evidence')) as evidence_now, pg_size_pretty(pg_relation_size('evidence_search_fts')) as fts_index, pg_size_pretty(pg_relation_size('evidence_search_trgm')) as trgm_index;
