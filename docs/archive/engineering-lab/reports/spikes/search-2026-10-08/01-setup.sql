create extension if not exists unaccent;
create extension if not exists pg_trgm;
create or replace function f_unaccent(text) returns text
  language sql immutable parallel safe strict
  as $$ select public.unaccent('public.unaccent'::regdictionary, $1) $$;

select unaccent('Kỹ năng thiết kế cơ sở dữ liệu Đà Nẵng đường');

create table skill (id bigserial primary key, name text not null, category text not null, description text);
create table evidence (id bigserial primary key, title text not null, summary text);

-- word pools
create temp table w(vi text[], en text[]);
insert into w values (
 array['kỹ năng','thiết kế','cơ sở dữ liệu','tối ưu','truy vấn','bảo mật','kiểm thử','triển khai','giám sát','hiệu năng','đồng bộ','phân quyền','sự cố','khôi phục','ghi chú','học','đọc sách','mạng','bộ nhớ đệm','hàng đợi','xử lý lỗi','tài liệu','đánh giá','kiến trúc','dịch vụ'],
 array['PostgreSQL','Laravel','Redis','Docker','Kubernetes','Terraform','Go','Python','React','Next.js','index','migration','OpenTelemetry','k6','Playwright','Sanctum','audit log','queue','cache','TLS','backup','CI/CD','GitHub Actions','Nginx','MinIO']);

insert into skill(name, category, description)
select (select en[1+((g*7)%25)] from w) || ' ' || (select vi[1+((g*3)%25)] from w) || ' ' || g,
       (array['backend','database','devops','frontend','soft skill'])[1+g%5],
       'Mô tả ' || (select vi[1+((g*11)%25)] from w) || ' và ' || (select en[1+((g*13)%25)] from w)
from generate_series(1,100) g;

insert into evidence(title, summary)
select initcap((select vi[1+((g*7)%25)] from w)) || ' ' || (select en[1+((g*5)%25)] from w) || ' #' || g,
       'Ghi chú về ' || (select vi[1+((g*17)%25)] from w) || ', ' || (select vi[1+((g*19)%25)] from w) || ' với ' || (select en[1+((g*23)%25)] from w) || '. ' || repeat('Nội dung tóm tắt ngắn gọn. ', 1+g%8)
from generate_series(1,1500) g;

-- Option A: full-text, simple config on unaccented text
alter table evidence add column tsv tsvector generated always as (to_tsvector('simple', f_unaccent(title || ' ' || coalesce(summary,'')))) stored;
create index evidence_tsv on evidence using gin (tsv);
-- Option B: trigram on unaccented lower text
create index evidence_trgm on evidence using gin (f_unaccent(lower(title || ' ' || coalesce(summary,''))) gin_trgm_ops);
analyze;
select count(*) from skill; select count(*) from evidence;
