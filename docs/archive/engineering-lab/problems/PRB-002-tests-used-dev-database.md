# PRB-002 — Local test runs wiped the dev database

> 🇻🇳 Hồ sơ vấn đề: chạy test ở local đã xoá sạch database dev.

| | |
|---|---|
| Status | Solved (2026-10-07, `e58b418`) |
| Found | 2026-10-07, by Dev, while writing the Sanctum tests (P2-11) |
| Area | testing, local environment |
| Related | [PRB-001](PRB-001-custom-jwt-auth.md), `0df42fa` (first attempt, 2026-10-05), `0b1a294` (same class of bug for Redis) |

## 1. Context

Backend tests run inside the `ml-php` container (`make test`, `make verify`). `phpunit.xml` is meant to send them to a separate PostgreSQL database, `testing`, so that `RefreshDatabase` (which runs `migrate:fresh` once per run) never touches the dev database `ml_pg_db`.

> 🇻🇳 Bối cảnh: test backend chạy trong container `ml-php`. `phpunit.xml` lẽ ra chuyển test sang DB riêng `testing`, để `RefreshDatabase` (chạy `migrate:fresh` một lần mỗi lượt test) không bao giờ đụng vào DB dev `ml_pg_db`.

## 2. Symptom

The new CSRF middleware rejected test requests, although Laravel skips CSRF checks when `APP_ENV=testing`. A probe test printed `APP_ENV=local`, database `ml_pg_db`, broadcaster `reverb`. Earlier hints had been explained away: "dev DB has no admin rows" (P1-14), "dev tables were all empty" (slices 3–5).

> 🇻🇳 Triệu chứng: middleware CSRF chặn request trong test, dù Laravel bỏ qua CSRF khi `APP_ENV=testing`. Test thăm dò in ra `APP_ENV=local`, DB `ml_pg_db`, broadcaster `reverb`. Các dấu hiệu trước đó ("DB dev không có admin", "bảng dev đều rỗng") đã bị bỏ qua.

## 3. Root cause

1. Why did tests use the dev DB? `DB_DATABASE` resolved to `ml_pg_db`.
2. Why? Laravel's env repository reads `$_SERVER` before `$_ENV`, and PHPUnit's `<env force="true">` only sets `$_ENV` and `putenv()`.
3. Why was `$_SERVER` set? `docker-compose.yml` exports `APP_ENV=local`, `DB_DATABASE`, `BROADCAST_CONNECTION` into the `ml-php` container, and PHP copies process env into `$_SERVER`.
4. Why was it not noticed? CI has no `DB_DATABASE` in the job env, so CI used `testing` and stayed green; locally every test passed against whatever was in `ml_pg_db`, and the fix of 2026-10-05 (`0df42fa`) was verified in CI only.

> 🇻🇳 Nguyên nhân gốc: Laravel đọc `$_SERVER` trước `$_ENV`; `<env force>` của PHPUnit chỉ đặt `$_ENV`; container `ml-php` export `APP_ENV`, `DB_DATABASE`, `BROADCAST_CONNECTION` nên chúng nằm sẵn trong `$_SERVER`. CI không export `DB_DATABASE` nên CI vẫn đúng; bản sửa ngày 05/10 chỉ được kiểm chứng trên CI.

## 4. Impact

- Every local `make test` / `make verify` dropped and re-created all tables in the dev database (`migrate:fresh`) and left it empty: dev data created between runs was lost each time. Only dev data was affected (no deployed environment runs tests); the owner's real content lives in Obsidian.
- Local tests ran with `APP_ENV=local` (debug behaviour, CSRF not skipped) and sent broadcasts to the real Reverb server.
- The "drop migration ran once on the dev DB" in slice 2 was most likely this, not a manual run.

> 🇻🇳 Hậu quả: mỗi lần `make test` / `make verify` ở local đều `migrate:fresh` DB dev và để trống — dữ liệu dev tạo giữa các lần chạy bị mất. Chỉ ảnh hưởng dữ liệu dev (môi trường deploy không chạy test); nội dung thật nằm trong Obsidian. Test local còn chạy với `APP_ENV=local` và broadcast lên Reverb thật.

## 5. Options considered

| Option | Pros | Cons | Effort |
|---|---|---|---|
| A. Also force the variables as `<server>` in `phpunit.xml` | One file, works in container and CI | Must list each variable the container exports | Low |
| B. Stop exporting DB/app variables from compose, read only `.env` | Cleaner container env | Touches every service; P4 (Docker hardening) work | Medium |
| C. Separate test container / compose profile | Full isolation | More infrastructure for one developer | High |

## 6. Solution

Option A now (`e58b418`): `APP_ENV`, `DB_DATABASE` and `BROADCAST_CONNECTION` are set as `<server force="true">` in addition to `<env>`. Option B is noted for P4.

> 🇻🇳 Chọn A: đặt thêm ba biến dưới dạng `<server force="true">`. Phương án B để dành cho P4.

## 7. Implementation and verification

A probe test printed `testing / testing / log` after the change; the full suite passed (242) against `testing` before the auth work continued. The dev database still had 0 admins afterwards; nothing was restored, because there was nothing to restore from.

> 🇻🇳 Sau khi sửa, test thăm dò in ra `testing / testing / log`; toàn bộ 242 test pass trên DB `testing`. DB dev vẫn rỗng; không có gì để khôi phục.

## 8. New problems that appeared

None. The owner needs to seed the dev DB again to use the admin UI (`RootAccountSeeder`).

> 🇻🇳 Không có. Owner cần seed lại DB dev (`RootAccountSeeder`) để dùng giao diện admin.

## 9. Follow-up improvements

- P4 (Docker hardening): stop exporting `DB_DATABASE` / `APP_ENV` to `ml-php` from compose (option B).
- Guard test `tests/Feature/TestEnvironmentTest.php` (added in P2-11): fails when the environment, database or broadcaster is not the test one.

> 🇻🇳 Cải tiến: ở P4 bỏ export biến DB/app từ compose; đã thêm test canh gác `TestEnvironmentTest` báo lỗi khi môi trường/DB/broadcaster không phải bản dành cho test.

## 10. Lessons learned

A safety setting is only fixed when it is checked in the environment where the damage happens. "CI is green" proved the CI environment, not the developer's container.

> 🇻🇳 Một thiết lập an toàn chỉ thực sự được sửa khi đã kiểm tra ngay trong môi trường gây hại. CI xanh chỉ chứng minh môi trường CI, không chứng minh container của developer.
