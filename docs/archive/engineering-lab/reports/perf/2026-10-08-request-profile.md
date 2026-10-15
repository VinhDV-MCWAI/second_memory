# Request profile — where one API request's time goes (2026-10-08)

| | |
|---|---|
| Task | PERF-01 (lane `perf`) |
| Follows | [Baseline 2026-10-08](2026-10-08-baseline.md) (P3-16): "~28 ms per request is not query time" |
| Commit tested | branch `feature/p3-ledger-api` at `8c42674` (API code unchanged since the baseline) |
| How to repeat | `scripts/lane.sh run perf/profile.sh` (≈ 7 min; prints the variant tables and one load run) |

> 🇻🇳 Đo xem một request API tốn thời gian ở đâu (PERF-01), tiếp nối baseline P3-16. Chạy lại bằng `scripts/lane.sh run perf/profile.sh`.

## Result in one line

The baseline's guess was wrong: the biggest fixed cost **is the database**, but not the queries — it is opening a **new PostgreSQL connection on every request** (~10–12 ms) plus the cold catalog cache of that fresh backend (~3–4 ms per table touched). With persistent connections (tried on a copy of the code, the repo is unchanged) `public/skills` drops from ~38 to ~20 ms at 1 user, and under 10 users throughput goes from 55–69 to **110–118 req/s** and search p95 from 292–399 to **214–225 ms**. Laravel caches save ~2.5 ms, OPcache without revalidation ~1–2 ms, the WSL2 bind mount costs nothing measurable. The typo (fuzzy) search still spends ~60 ms in one trigram query on `evidence` → DB-01.

> 🇻🇳 Giả thuyết của baseline sai: chi phí cố định lớn nhất **nằm ở database**, nhưng không phải ở truy vấn mà ở việc **mở kết nối PostgreSQL mới cho mỗi request** (~10–12 ms) cộng với cache catalog còn lạnh của backend mới (~3–4 ms cho mỗi bảng). Dùng kết nối persistent (thử trên bản copy code, repo không đổi): `public/skills` giảm từ ~38 xuống ~20 ms với 1 user; với 10 user thông lượng tăng từ 55–69 lên **110–118 req/s**, p95 search từ 292–399 xuống **214–225 ms**. Cache của Laravel tiết kiệm ~2,5 ms, OPcache không kiểm tra timestamp ~1–2 ms, bind mount WSL2 không đáng kể. Search có lỗi chính tả vẫn tốn ~60 ms ở một truy vấn trigram trên `evidence` → DB-01.

## Method

- Same setup as the baseline (throwaway `perf` database at the REQ-002 volume, `php -S` inside `ml-php`, Xdebug mode off, OPcache on, `APP_DEBUG=false`), but **1 user, sequential requests** (`perf/k6/request-cost.js`, 200 measured iterations after 20 warm-up), so the numbers are service time without queueing.
- Five requests per iteration: `/up` (framework only, no DB, no session), `public/skills` without and with the SPA `Referer` (the second starts a session), `admin/search?q=postgresql` (full-text path) and `admin/search?q=postgersql` (typo → trigram fallback).
- Server side, `perf/php/timing-prepend.php` (loaded with `auto_prepend_file` by the profile script only) logs per request the PHP wall time, the time inside database calls (Laravel's `totalQueryDuration()`; the first call includes opening the connection) and the number of PHP files loaded.
- Variants change one thing at a time. Laravel caches go to `/tmp` through `APP_CONFIG_CACHE` / `APP_ROUTES_CACHE` / `APP_EVENTS_CACHE`, so the shared `bootstrap/cache` is untouched. The "copy" variants run a copy of `laravel-api/` on the container filesystem; the persistent-connection variant patches `config/database.php` **in that copy only**.
- One-off checks: raw PDO from `ml-php`, and every SQL statement of one iteration logged with `log_min_duration_statement = 0` set on the `perf` database only.

> 🇻🇳 Cùng môi trường với baseline nhưng chỉ 1 user gửi tuần tự, để đo thời gian xử lý thật (không có xếp hàng). Phía server ghi thời gian PHP, thời gian ở DB và số file PHP được nạp cho từng request. Mỗi biến thể chỉ đổi một yếu tố; cache Laravel và bản copy code đều nằm trong `/tmp` của container, không đụng vào repo hay stack dev.

## Numbers

Medians in ms, 200 requests each. "k6" is what the client sees; "PHP" and "DB" come from the server log.

| Variant | `/up` k6 / PHP | `public/skills` k6 / PHP / DB | + session (SPA) k6 / DB | search k6 / DB | typo search k6 / DB |
|---|---|---|---|---|---|
| mount (as in the baseline) | 10.2 / 9.5 | 38.5 / 37.5 / 18.6 | 44.9 / 20.1 | 50.3 / 33.1 | 102.7 / 83.8 |
| + Laravel caches | 7.2 / 6.4 | 36.2 / 35.3 / 19.1 | 41.9 / 20.6 | 48.0 / 33.7 | 100.7 / 85.6 |
| + `opcache.validate_timestamps=0` | 5.4 / 4.7 | 34.5 / 33.5 / 19.6 | 41.0 / 21.7 | 46.9 / 35.5 | 100.6 / 88.1 |
| code copy (no bind mount) + caches | 9.2 / 8.3 | 42.1 / 41.1 / 21.6 | 49.7 / 23.9 | 56.4 / 39.4 | 117.4 / 98.9 |
| copy + caches + **persistent PDO** | 7.2 / 6.6 | **19.8 / 19.0 / 3.0** | 24.3 / 3.7 | **25.5 / 11.7** | 74.9 / 59.9 |

The table is the third full run (all variants in one run). Three runs differed by up to ±10 % per cell (e.g. `public/skills` on mount: 34.8 / 40.8 / 38.5 ms), with the same order between variants every time. Files loaded: 603 (`/up`) to 790 (search) without caches, 536–720 with them.

**Raw PostgreSQL cost from `ml-php`** (PDO, medians):

| | ms |
|---|---|
| Open a connection (TCP + backend fork + SCRAM-SHA-256, 4096 iterations; no TLS) | 10.2–12.1 (4 measurements) |
| First `select * from skill …` on that new backend | 4.5 |
| Same statement again on the same backend | 0.73 |
| `select 1` on an open connection | 0.26–0.37 |

**SQL log of one iteration:** on a new backend the *parse* and *bind* (planning) steps of even trivial statements (`select * from admin_mst where id = $1 limit 1`) take 1–3 ms each, the execution itself < 1 ms. Exceptions that are real query time: the full-text query on `evidence` (execute 7.6 ms) and the trigram fallback on `evidence` (`f_unaccent(lower($1)) <% search_text`, execute **46 ms**).

**Load, 10 users, 8 workers** (`ledger-baseline.js`, same as the baseline) on copy + caches + persistent PDO, two runs:

| Endpoint | p50 | p95 | p99 | Baseline p95 (mount, 4 runs) |
|---|---|---|---|---|
| `admin/search` | 65–71 | **214–225** | 277–289 | 292–399 |
| `admin/skill/list` | 55–58 | 118–128 | 183–187 | 217–320 |
| `public/skills` | 58–63 | 122–132 | 198–220 | 207–303 |
| `public/skills/{slug}` | 48–52 | 109–121 | 177–179 | 207–290 |

110–118 requests/s (baseline 55–69), 0 % errors.

> 🇻🇳 Bảng trên: trung vị (ms) của 200 request cho mỗi biến thể. Mở kết nối PostgreSQL tốn ~10–12 ms, truy vấn đầu tiên trên backend mới 4,5 ms so với 0,73 ms khi backend đã "ấm". Với kết nối persistent, DB của `public/skills` từ ~19 ms còn 3 ms. Khi có tải 10 user: 110–118 req/s, p95 search 214–225 ms.

## What the numbers say

Breakdown of `GET public/skills` (no session, mount, no caches, ~38 ms PHP time):

| Part | ms | Evidence |
|---|---|---|
| Framework boot + routing (Xdebug loaded, mode off) | ~9.5 | `/up` |
| New PostgreSQL connection | ~10–12 | raw PDO |
| Cold catalog on the new backend (parse / bind of 2 statements) | ~4–5 | SQL log, cold vs warm |
| Queries themselves | ~3 | persistent variant (DB 3.0) |
| Controller, Eloquent hydration, resource → JSON (67 skills + tags) | ~10 | remainder (PHP 19.0 − DB 3.0 − boot ~6.6 on the persistent variant) |

- **Connection per request is the main cost.** It is ~15–16 ms of a ~38 ms request, it grows with each extra table a request touches (search: ~33 ms of DB, of which ~12 ms remain with persistent connections), and it is the reason PostgreSQL used ~3.5 CPU cores at 67 req/s in the baseline (a backend fork + SCRAM + catalog load per request).
- **Session start** (SPA `Referer`) adds ~5–6 ms: Redis connection plus session read / write. Real visitors of the public site send no SPA `Referer`, so they don't pay it.
- **Laravel caches** (config / route / event) save ~2.5 ms and ~70 loaded files; worth doing in the production image (P4) but not the bottleneck.
- **OPcache `validate_timestamps=0`** saves ~1–2 ms on `/up` only; within noise elsewhere.
- **The WSL2 bind mount is not a cost**: the copy on the container filesystem was 2–6 ms *slower* in all three runs (overlay filesystem), never faster.
- **The typo search** remains the slowest request even with persistent connections (~60 ms of DB): the trigram fallback over `evidence` (execute 46 ms in the SQL log). ADR-0009 expected the fallback to be rare; it is still the p95 risk → DB-01 (`EXPLAIN (ANALYZE, BUFFERS)` of that statement).

> 🇻🇳 Mở kết nối mới cho mỗi request là chi phí chính (~15–16 ms trên ~38 ms), tăng thêm theo số bảng request đụng tới, và là lý do PostgreSQL ngốn ~3,5 core ở baseline. Session thêm ~5–6 ms (khách public không bị). Cache Laravel tiết kiệm ~2,5 ms; tắt kiểm tra timestamp của OPcache ~1–2 ms; bind mount WSL2 không đáng kể. Search có lỗi chính tả vẫn chậm nhất (~60 ms ở DB) do truy vấn trigram trên `evidence` → DB-01.

## Proposals (not applied; each needs the owner's OK and goes to its lane)

1. **Reuse PostgreSQL connections** (lane `api`, task API-02): `PDO::ATTR_PERSISTENT` for the `pgsql` connection behind an env flag (`DB_PERSISTENT`, default on), then re-run `scripts/perf-baseline.sh`. Under php-fpm this means one backend per FPM worker (`pm.max_children`, well below `max_connections = 200`). Risks to check in the task: a request that dies inside a transaction leaves it open for the next one (Laravel rolls back on its own exceptions; a fatal error does not), and per-session settings persist (Laravel re-applies `search_path`; nothing else is set per session today). Alternative if that is not acceptable: PgBouncer (lane `infra`), which needs transaction pooling with prepared-statement support (PgBouncer ≥ 1.21 `max_prepared_statements`) because PDO uses server-side prepares.
2. **Build Laravel caches in the production image** (lane `infra`, with P4): `php artisan optimize` at container start; ~2.5 ms per request.
3. **Trigram fallback on `evidence`** (lane `perf`, DB-01): explain and fix the 46 ms statement; it decides the search p95.

Not proposed: moving the code off the bind mount (no gain) and turning off OPcache revalidation in dev (≤ 2 ms, hurts the edit-reload loop).

> 🇻🇳 Đề xuất (chưa áp dụng, cần owner đồng ý): (1) dùng lại kết nối PostgreSQL bằng `PDO::ATTR_PERSISTENT` có cờ env — lane `api`, task API-02; phương án thay thế là PgBouncer (lane `infra`). (2) chạy `php artisan optimize` trong image production (P4). (3) xử lý truy vấn trigram 46 ms trên `evidence` trong DB-01. Không đề xuất bỏ bind mount hay tắt kiểm tra timestamp của OPcache ở dev.

## Limits

- Built-in PHP server, not php-fpm; Xdebug is loaded (mode off) in the dev image, so `/up` is a few ms slower than production will be. Repeat on the production image in P4.
- The persistent-connection numbers come from a patched copy of the code, not from a reviewed change; API-02 must measure again after the real change.
- One host for k6, PHP and PostgreSQL; ±10 % between runs at 1 user, more under load. The load test on the persistent variant ran twice.

> 🇻🇳 Giới hạn: dùng server có sẵn của PHP (không phải php-fpm), image dev vẫn nạp Xdebug; số liệu kết nối persistent đo trên bản copy đã vá, chưa phải thay đổi thật; mọi thứ chạy trên một máy; phép đo tải chạy hai lần; số dao động ±10 % giữa các lần chạy.

## Follow-up: API-02 applied (2026-10-08, `edf8489`)

Proposal 1 is in the code: `config/database.php` sets `PDO::ATTR_PERSISTENT` for web requests when `DB_PERSISTENT` is not false (default on). Measured on the real change, not a patched copy: same `perf` database, `perf_start_server 8` with `-e DB_PERSISTENT=false|true`, alternating off / on twice, smoke then load each time (`ledger-baseline.js`, unchanged). Runs as `api02-<scenario>-<persistent>-<round>.json` in `perf/results/` (not committed).

| Load, 10 users | req/s | p95 `public/skills` | p95 `public/skills/{slug}` | p95 `admin/search` | p95 `admin/skill/list` |
|---|---|---|---|---|---|
| off, round 1 | 51.2 | 288 | 284 | **365** | 303 |
| on, round 1 | 81.3 | 218 | 195 | 274 | 206 |
| off, round 2 | 66.3 | 214 | 232 | 278 | 243 |
| on, round 2 | **127.5** | 124 | 106 | **176** | 116 |

Smoke (1 user), p50 ms off → on: `public/skills` 43–49 → 24–33, `public/skills/{slug}` 46–51 → 22–29, `admin/search` 50 → 21–33, `admin/skill/list` 49–54 → 24–34. Error rate 0 % in every run.

- **Persistent wins in every pair**, by +59 % / +92 % throughput under load. The host was noisy (round 1 slower in both variants; the other lanes share it), so read the pairs, not the absolute values. The prediction (~110 req/s, `public/skills` ~20 ms) is inside the measured range (81–128 req/s, 22–33 ms p50).
- The control (off) crossed the 300 ms p95 threshold on search and the skill list in round 1; the persistent runs stayed under it in both rounds.
- Risks checked on the `testing` database: a fatal error inside a transaction (PDO `beginTransaction()` or a raw `BEGIN`) is rolled back by PHP at request end, the next request on the same backend sees no open transaction; a killed backend is reconnected by Laravel without an error reaching the client; a session-level `set_config(…, false)` does survive into the next request, and the app sets none (search uses `set_config(…, true)` inside a transaction). On dev php-fpm, 20 requests opened 2 backends (one per worker; `pm.max_children = 5` ≪ `max_connections = 200`).
- **CLI is excluded on purpose.** With persistence on in PHPUnit, 3 tests failed and rows leaked into `testing`: each test boots a fresh app, two PDO objects share the handle, and freeing the old one rolls back the running test's transaction. Tests, artisan and queue workers keep one connection per process anyway, so they lose nothing.

> 🇻🇳 Theo dõi: đã áp dụng API-02 (`edf8489`). Đo trên thay đổi thật, chạy xen kẽ tắt/bật hai vòng trên cùng DB `perf`. Bật persistent thắng ở mọi cặp: tải 10 user từ 51 → 81 và 66 → 128 req/s; p95 search 365 → 274 và 278 → 176 ms; với 1 user p50 `public/skills` từ 43–49 còn 24–33 ms; lỗi 0 %. Máy đo bị nhiễu (vòng 1 chậm ở cả hai biến thể) nên chỉ so theo cặp. Lượt đối chứng (tắt) vòng 1 vượt ngưỡng p95 300 ms, các lượt bật persistent thì không. Rủi ro đã kiểm tra trên DB `testing`: transaction còn mở khi fatal error được PHP rollback; backend bị ngắt thì Laravel tự kết nối lại; setting cấp session có bị mang sang request sau, nhưng app không dùng. Trên php-fpm dev, 20 request chỉ mở 2 backend. CLI (test, artisan, queue) cố ý không dùng persistent vì trong PHPUnit hai object PDO dùng chung handle làm rollback mất transaction của test đang chạy (3 test fail, dữ liệu bị commit vào `testing`).
