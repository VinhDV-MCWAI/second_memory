-- Plans of the search statements on the current schema (ADR-0009 + API-03: stored search_tsv with
-- GIN, GiST trigram index on search_text), same shape as SearchRepository. Run by perf/explain.sh.
-- The what-if experiments that led here are in docs/reports/perf/2026-10-08-db-01-explain.md.
-- Terms: 'postgresql' is in 25 % of the seeded evidence (PerfLedgerSeeder::HOT_TERM, the worst
-- case), 'kubernetes' in ~9 % like every other word.
\set ON_ERROR_STOP on
\pset pager off
BEGIN;
SET LOCAL pg_trgm.word_similarity_threshold = 0.4;

\echo '### F1 evidence full text, postgresql:* (25 %)'
\set q 'postgresql:*'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where search_tsv @@ to_tsquery('simple', f_unaccent(lower(:'q'))) order by ts_rank(search_tsv, to_tsquery('simple', f_unaccent(lower(:'q')))) desc limit 10;
\echo '### F2 evidence full text, kubernetes:* (~9 %)'
\set q 'kubernetes:*'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where search_tsv @@ to_tsquery('simple', f_unaccent(lower(:'q'))) order by ts_rank(search_tsv, to_tsquery('simple', f_unaccent(lower(:'q')))) desc limit 10;
\echo '### F3 skill full text, postgresql:*'
\set q 'postgresql:*'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, name, description from skill where search_tsv @@ to_tsquery('simple', f_unaccent(lower(:'q'))) order by ts_rank(search_tsv, to_tsquery('simple', f_unaccent(lower(:'q')))) desc limit 10;

\echo '### T1 evidence trigram, postgersql (typo of the 25 % term)'
\set q 'postgersql'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;
\echo '### T2 evidence trigram, kubernets'
\set q 'kubernets'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, title, summary from evidence where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;
\echo '### T3 skill trigram, postgersql'
\set q 'postgersql'
EXPLAIN (ANALYZE, BUFFERS, COSTS OFF) select id, name, description from skill where f_unaccent(lower(:'q')) <% search_text order by f_unaccent(lower(:'q')) <<-> search_text limit 10;

\echo '### S index sizes'
select indexrelname as index, pg_size_pretty(pg_relation_size(indexrelid)) as size
from pg_stat_user_indexes where relname in ('skill', 'evidence') and indexrelname like '%search%' order by 1;
ROLLBACK;
