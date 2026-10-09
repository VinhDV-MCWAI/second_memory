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

At 10 concurrent users the API now serves **116–134 requests/s instead of 55–69**, and search p95 is **123–144 ms instead of 292–399 ms** (target 300 ms) with 0 % errors; at one user every endpoint answers in 26–30 ms (p50). One new risk: building Laravel's config cache turned persistent connections off again (fixed by API-06, follow-up below).

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

### Follow-up 2026-10-09: fixed by API-06 (PERF-05)

API-06 (`3815730`) takes persistence from `DB_PERSISTENT` alone and switches it off for PHPUnit in `phpunit.xml`. Measured on `80c59e8` (no uncommitted change under `laravel-api/`; lane `infra` had uncommitted Docker hardening in the tree, which the throwaway `php -S` server does not use):

- Every cached build now holds `ATTR_PERSISTENT: true`. `perf_build_caches` (`perf/lib.sh`) prints it each time, so a regression shows in every run.
- `perf/profile.sh`, server side, 1 user, PHP / database ms (medians):

| Request | `mount` (no caches) | `mount-cached` | `mount-cached-novalidate` | `copy-cached` |
|---|---|---|---|---|
| `GET public/skills` | 18.3 / 2.8 | 16.5 / 2.9 | 12.9 / 2.6 | 15.3 / 2.7 |
| `GET public/skills` with session | 22.3 / 3.4 | 20.2 / 3.5 | 16.2 / 3.1 | 19.2 / 3.2 |
| `GET admin/search?q=postgresql` | 17.9 / 4.9 | 15.8 / 4.9 | 12.1 / 4.5 | 15.0 / 4.6 |
| `GET admin/search?q=postgersql` | 37.8 / 23.8 | 36.7 / 24.9 | 31.2 / 22.7 | 34.1 / 22.5 |

  Database time with caches is 2.6–4.9 ms (typo search ~23 ms, its query) instead of 17–26 ms. Caches now save PHP time on top: 2 ms per request, 5–6 ms with `opcache.validate_timestamps=0` (production images, P4-03).
- 10-user load, `PERF_CACHED=1 scripts/perf-baseline.sh load` (new switch: the same caches before the server starts) against the default, in pairs run back to back:

| Pair | Requests/s, no caches → caches | p95 range of the four endpoints, no caches → caches |
|---|---|---|
| 1 | 119 → 99 | 126–141 → 168–189 |
| 2 | 58\* → 140 | 175–196 → 109–125 |
| 3 | 85 → 89 | 189–211 → 189–212 |

  0 % errors in all six runs, every p95 under 300 ms. \* One request hung for 67 s (host stall) and stretched that run. Other lanes shared the host (pair 3 waited for another lane's Docker run), so runs differ by up to ±25 %. Which side is faster flips from pair to pair, so caches neither add nor cost throughput at this load, and the 57 requests/s with p95 > 300 ms from the cached server before API-06 is gone.

> 🇻🇳 Đã sửa bởi API-06. Mọi lần build cache giờ giữ `ATTR_PERSISTENT: true` (`perf_build_caches` in ra mỗi lần chạy). Có cache: DB 2,6–4,9 ms/request thay vì 17–26 ms, PHP còn nhanh hơn 2 ms (5–6 ms khi tắt `validate_timestamps`). Tải 10 user theo cặp (không cache → có cache): 119 → 99, 58\* → 140, 85 → 89 req/s; 0 % lỗi, mọi p95 < 300 ms. Máy chạy chung nên lệch tới ±25 %, nhưng không còn tình trạng 57 req/s, p95 > 300 ms như trước API-06. Thêm công tắc `PERF_CACHED=1` cho `scripts/perf-baseline.sh`.

## Limits

- The baseline ran on seed v1, this one on seed v2 (PERF-02): search terms are spread differently, the worst-case term (`postgresql`, 25 %) is the same. Public and list endpoints do not depend on the seed's word spread.
- One host for k6, PHP and PostgreSQL; other lanes' sessions may have used the CPU; the three load runs differ by ±8 %.
- Built-in PHP server, not php-fpm; repeat on the production images in P4.
- `perf/profile.sh` lost its "persistent connections patched into a copy" variant and the load run on it: persistence is in the code now. Since API-06 its cached variants keep persistence (follow-up above).

> 🇻🇳 Giới hạn: baseline cũ chạy seed v1, lần này seed v2 (từ khóa xấu nhất giữ nguyên); một máy chạy chung, ba lần đo tải lệch ±8 %; dùng server có sẵn của PHP, cần đo lại trên image production ở P4.
