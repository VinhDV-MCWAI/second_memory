\pset footer off
select count(*) postgresql_fts from evidence where tsv @@ websearch_to_tsquery('simple','postgresql');
select round(word_similarity('postgersql','postgresql laravel')::numeric,2) ws_transposition, round(word_similarity('postgrsql','postgresql laravel')::numeric,2) ws_missing_letter;
set pg_trgm.word_similarity_threshold = 0.4;
select count(*) typo_04 from evidence where 'postgersql' <% f_unaccent(lower(title || ' ' || coalesce(summary,'')));
select count(*) missing_letter_04 from evidence where 'postgrsql' <% f_unaccent(lower(title || ' ' || coalesce(summary,'')));
-- false positives of "ky": ký/kỳ/kỹ all become ky
select f_unaccent('ký hợp đồng, kỳ thi, kỹ năng') as collisions;
