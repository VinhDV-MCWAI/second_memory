# Re-baseline after API-02 / API-03 — Skill Ledger read paths (2026-10-08)

| | |
|---|---|
| Task | PERF-03 (lane `perf`) |
| Compares with | [Baseline 2026-10-08](2026-10-08-baseline.md) (P3-16), [request profile](2026-10-08-request-profile.md) (PERF-01), [query plans](2026-10-08-db-01-explain.md) (DB-01) |
| Commit tested | `f930935` on `feature/p3-ledger-api`, no uncommitted change under `laravel-api/` during the runs |
| Changes since the baseline | API-02 persistent PostgreSQL connections, API-03 stored `search_tsv` + GiST trigram index, API-04 no schema lookup in lists; seed v2 (PERF-02) |
| How to repeat | `scripts/lane.sh run scripts/perf-baseline.sh` (smoke + load), `… run perf/profile.sh`, `… run perf/explain.sh` |

> 🇻🇳 Đo lại sau API-02 (kết nối persistent), API-03 (lưu sẵn vector tìm kiếm, index GiST trigram) và API-04, trên seed v2. So sánh với baseline P3-16.

## Result in one line

At 10 concurrent users the API now serves **116–134 requests/s instead of 55–69**, and search p95 is **123–144 ms instead of 292–399 ms** (target 300 ms) with 0 % errors; at one user every endpoint answers in 26–30 ms (p50). One new risk: building Laravel's config cache turns persistent connections off again (API-06).

> 🇻🇳 Với 10 user đồng thời: **116–134 req/s thay vì 55–69**, p95 search **123–144 ms thay vì 292–399 ms** (ngưỡng 300 ms), 0 % lỗi; với 1 user mọi endpoint 26–30 ms (p50). Rủi ro mới: build config cache của Laravel làm tắt lại kết nối persistent (API-06).

## Before → after

Same setup as the baseline (throwaway `perf` database, `php -S` with 8 workers, Xdebug off, OPcache on, k6 in Docker; the built-in server runs as SAPI `cli-server`, so persistent connections are on as under php-fpm). Latency in ms.

**Load, 10 users** (baseline: 4 runs on seed v1; now: 3 runs on seed v2)

| Endpoint | p50 before → after | p95 before → after | p99 before → after |
|---|---|---|---|
| `admin/search` | 117–127 → **49–56** | 292–399 → **123–144** | 369–407 → 164–197 |
| `admin/skill/list` | 109–112 → 53–61 | 217–320 → 114–140 | 296–339 → 155–200 |
| `public/skills` | 98–104 → 62–71 | 207–303 → 119–158 | 277–310 → 158–214 |
| `public/skills/{slug}` | 98–100 → 52–59 | 207–290 → 111–131 | 285–311 → 149–177 |
| Requests/s | 55–69 → **116–134** | | |
| Errors | 0 % → 0 % | | |

**Smoke, 1 user**

| Endpoint | p50 before → after | p95 before → after |
|---|---|---|
| `admin/search` | 40.3 → 25.7 | 87.2 → 56.1 |
| `admin/skill/list` | 40.2 → 28.1 | 51.3 → 42.3 |
| `public/skills` | 36.6 → 30.4 | 44.5 → 44.7 |
| `public/skills/{slug}` | 37.7 → 27.6 | 47.6 → 40.3 |

**Server side, 1 user, sequential** (`perf/profile.sh`, variant `mount` = the setup above; PHP time / time in database calls, medians)

| Request | Before (PERF-01) | After |
|---|---|---|
| `GET public/skills` | 37.5 / 18.6 | **20.8 / 3.0** |
| `GET public/skills` with session | 44.0 / 20.1 | 25.4 / 3.6 |
| `GET admin/search?q=postgresql` | 49.2 / 33.1 | **19.7 / 5.1** |
| `GET admin/search?q=postgersql` (typo) | 101.7 / 83.8 | **40.9 / 25.3** |

**Search statements** (`perf/explain.sh`, `EXPLAIN ANALYZE` on `evidence`)

| Statement | Before (DB-01) | After |
|---|---|---|
| Full text, term in 25 % of rows | 5.7–10 | 0.8–1.1 |
| Full text, term in 5–9 % (seed v1 / v2) | 1.5–2.8 | 0.8 |
| Typo (trigram), 5–9 % term (seed v1 / v2) | 33–42 | 1.0 |
| Typo (trigram), 25 % term | 37–57 | 14.4 (GiST walks many rows at similar distance) |

> 🇻🇳 Các bảng trên: trước → sau. Tải 10 user: p95 search 292–399 → 123–144 ms, thông lượng 55–69 → 116–134 req/s. Một user phía server: `public/skills` 37,5 → 20,8 ms (DB 18,6 → 3,0). Câu lệnh search: full-text 6–10 → ~1 ms, trigram 33–57 → 1–14 ms.

## Config cache switches persistent connections off

`config/database.php` sets `PDO::ATTR_PERSISTENT => PHP_SAPI !== 'cli' && …` so that PHPUnit (CLI) does not share connections between tests. `php artisan config:cache` runs in the CLI, so the cached config holds `false` for every later web request — checked by caching to `/tmp` and reading the value (`[12 => false]`). In the profile the three variants with Laravel caches show it: database time 17–26 ms per request instead of 3–5, and a 10-user load on such a server gave **57 requests/s and p95 > 300 ms** (three endpoints over the threshold), the numbers from before API-02.

Nothing builds the config cache today, so the running stack is not affected. P4-03 (production images build Laravel caches at start, which OPS-02 was folded into) would hit it. Task **API-06** (lane `api`): decide persistence at runtime or switch it off for PHPUnit only, with a test on a cached config; P4-03 must not build caches before that.

> 🇻🇳 `config:cache` chạy ở CLI nên ghi `false` cho `ATTR_PERSISTENT` vào cache, khiến mọi request web sau đó không dùng kết nối persistent (DB 17–26 ms thay vì 3–5; 10 user chỉ còn 57 req/s, p95 > 300 ms). Hiện chưa có gì build cache nên stack đang chạy không bị ảnh hưởng; P4-03 sẽ dính. Đã thêm task API-06 cho lane `api`; P4-03 phải chờ.

## Limits

- The baseline ran on seed v1, this one on seed v2 (PERF-02): search terms are spread differently, the worst-case term (`postgresql`, 25 %) is the same. Public and list endpoints do not depend on the seed's word spread.
- One host for k6, PHP and PostgreSQL; other lanes' sessions may have used the CPU; the three load runs differ by ±8 %.
- Built-in PHP server, not php-fpm; repeat on the production images in P4.
- `perf/profile.sh` lost its "persistent connections patched into a copy" variant and the load run on it: persistence is in the code now. Its cached variants measure the API-06 problem until that is fixed.

> 🇻🇳 Giới hạn: baseline cũ chạy seed v1, lần này seed v2 (từ khóa xấu nhất giữ nguyên); một máy chạy chung, ba lần đo tải lệch ±8 %; dùng server có sẵn của PHP, cần đo lại trên image production ở P4.
