\pset footer off
\echo '--- A: FTS, "ky nang" (no accents)'
select count(*) from evidence where tsv @@ websearch_to_tsquery('simple', f_unaccent('ky nang'));
\echo '--- A: FTS, "Kỹ năng" (accents)'
select count(*) from evidence where tsv @@ websearch_to_tsquery('simple', f_unaccent('Kỹ năng'));
\echo '--- A: FTS prefix "ky nan" (typing)'
select count(*) from evidence where tsv @@ to_tsquery('simple', 'ky & nan:*');
\echo '--- A: FTS typo "ky nagn"'
select count(*) from evidence where tsv @@ websearch_to_tsquery('simple', f_unaccent('ky nagn'));
\echo '--- A: top 3 ranked for "postgresql toi uu"'
select id, left(title,40), round(ts_rank(tsv, q)::numeric,3) r from evidence, websearch_to_tsquery('simple', f_unaccent('postgresql tối ưu')) q where tsv @@ q order by r desc limit 3;
\echo '--- B: trigram substring "ky nang"'
select count(*) from evidence where f_unaccent(lower(title || ' ' || coalesce(summary,''))) like '%ky nang%';
\echo '--- B: trigram word_similarity typo "ky nagn"'
select count(*) from evidence where 'ky nagn' <% f_unaccent(lower(title || ' ' || coalesce(summary,'')));
\echo '--- B: trigram typo "postgersql"'
select count(*) from evidence where 'postgersql' <% f_unaccent(lower(title || ' ' || coalesce(summary,'')));
\echo '--- plans'
explain (analyze, costs off, timing off, summary on) select id from evidence where tsv @@ websearch_to_tsquery('simple', f_unaccent('ky nang')) order by ts_rank(tsv, websearch_to_tsquery('simple', f_unaccent('ky nang'))) desc limit 10;
explain (analyze, costs off, timing off, summary on) select id from evidence where 'postgersql' <% f_unaccent(lower(title || ' ' || coalesce(summary,''))) limit 10;
