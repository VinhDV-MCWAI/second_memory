\pset footer off
create or replace function bench(kind text, n int) returns table(p50_ms numeric, p95_ms numeric, max_ms numeric) language plpgsql as $$
declare terms text[] := array['ky nang','toi uu truy van','bao mat','postgresql','redis cache','kiem thu','trien khai docker','su co','hieu nang','tai lieu'];
        typos text[] := array['ky nagn','postgersql','redsi','dokcer','laravle','terrafrom','kubernetse','pyhton','nginxx','mniio'];
        t0 timestamptz; d numeric[] := '{}'; q text; c int;
begin
  perform set_config('pg_trgm.word_similarity_threshold','0.4',true);
  for i in 1..n loop
    t0 := clock_timestamp();
    if kind = 'fts' then
      q := terms[1+(i%10)];
      select count(*) into c from (select id from evidence, websearch_to_tsquery('simple', f_unaccent(q)) tq where tsv @@ tq order by ts_rank(tsv,tq) desc limit 10) s;
    elsif kind = 'trgm' then
      q := typos[1+(i%10)];
      select count(*) into c from (select id from evidence where q <% f_unaccent(lower(title || ' ' || coalesce(summary,''))) order by q <<-> f_unaccent(lower(title || ' ' || coalesce(summary,''))) limit 10) s;
    end if;
    d := d || extract(epoch from clock_timestamp()-t0)*1000;
  end loop;
  return query select round(percentile_cont(0.5) within group (order by x)::numeric,2), round(percentile_cont(0.95) within group (order by x)::numeric,2), round(max(x)::numeric,2) from unnest(d) x;
end $$;
\echo '== 1.5k rows'
select 'fts' k, * from bench('fts',50) union all select 'trgm typo', * from bench('trgm',50);
insert into evidence(title, summary) select title || ' v' || g, summary from evidence, generate_series(1,99) g;
analyze evidence;
select count(*) rows from evidence;
\echo '== 150k rows'
select 'fts' k, * from bench('fts',50) union all select 'trgm typo', * from bench('trgm',50);
select pg_size_pretty(pg_relation_size('evidence_tsv')) tsv_idx, pg_size_pretty(pg_relation_size('evidence_trgm')) trgm_idx, pg_size_pretty(pg_relation_size('evidence')) heap;
