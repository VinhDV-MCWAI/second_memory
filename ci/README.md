# CI/CD dùng chung

Mọi bước kiểm tra và build nằm trong thư mục này. Máy dev (`make ci`), GitHub và GitLab đều gọi đúng các script ở đây, nên kết quả giống nhau. Lý do và các quyết định: [ADR-0016](../docs/adr/0016-ci-shared-folder.md).

## Có gì trong thư mục

| File | Làm gì |
|---|---|
| `check.sh` | Chạy mọi bước kiểm tra trong container tạo mới, trên code **đã commit** |
| `compose.yml` | Môi trường riêng cho `check.sh`: Postgres, Redis, PHP, Node. Tên project `sm-ci`, không mở cổng, không đụng DB dev |
| `build.sh` | Build 3 image production (API, giao diện quản trị, trang Skill Ledger) từ code đã commit. `make tf-images` cũng dùng file này |

Mỗi nền tảng chỉ có một file mỏng gọi vào đây: [`.github/workflows/ci.yml`](../.github/workflows/ci.yml), [`.gitlab-ci.yml`](../.gitlab-ci.yml).

## Chạy trên máy

```bash
scripts/lane.sh run make ci            # kiểm tra commit hiện tại (HEAD)
scripts/lane.sh run make ci ref=abc123 # kiểm tra một commit khác
make tf-images                         # build image production (ci/build.sh)
```

| | `make verify` | `make ci` |
|---|---|---|
| Kiểm tra code nào | Thư mục làm việc, **gồm cả file chưa commit** | Chỉ code **đã commit** (`git archive`) |
| Chạy ở đâu | Container dev đang bật (`ml-*`) | Container tạo mới, xong thì xóa |
| DB test | DB `testing` của dev | Postgres riêng, dữ liệu nằm trong RAM |
| Thời gian | Vài phút | Khoảng 4–5 phút (thư viện được giữ lại giữa các lần chạy trong `~/.cache/second-memory-ci`) |
| Dùng khi | Đang code, muốn biết nhanh | Trước khi push, hoặc khi GitHub báo lỗi mà máy không thấy |

## Các bước của `check.sh`

| Phần | Bước | Lỗi thì dừng? |
|---|---|---|
| Backend | Cài thư viện, Pint, Larastan, tạo bảng + đăng ký quyền trong DB test, toàn bộ test, `openapi.json` có cũ không | Có |
| Backend | `composer audit` (lỗ hổng thư viện) | Không, chỉ báo |
| Giao diện | Cài thư viện, lint, format, kiểu dữ liệu API có cũ không, kiểm tra kiểu TypeScript, test giao diện quản trị | Có |
| Giao diện | `pnpm audit` (lỗ hổng thư viện, mức cao trở lên) | Không, chỉ báo |
| Công cụ nhập ghi chú Obsidian | ruff, mypy, pytest | Có |

Test Playwright (`make e2e`) không nằm trong CI vì cần cả stack chạy thật.

## Chạy trên GitHub

Đang **chỉ chạy tay**: GitHub → Actions → CI → Run workflow → chọn branch. Có 2 job chạy song song: `Checks (ci/check.sh)` và `Production images (ci/build.sh)`.

## Chạy trên GitLab

**Chưa test** (chưa có project GitLab). Cần một runner kiểu shell executor trên máy có bash, git, Docker; giống máy dev. Runner kiểu Docker + `docker:dind` không chạy được, vì `check.sh` gắn một thư mục tạm vào container, mà Docker trong dind không thấy thư mục đó. Chạy tay: Build → Pipelines → Run pipeline.

## Lỗi hay gặp

| Lỗi | Nguyên nhân | Cách xử lý |
|---|---|---|
| `openapi.json is stale` | Đổi route hoặc dữ liệu API mà chưa sinh lại file | `make openapi`, commit cả `openapi.json` và `openapi.d.ts` |
| Test chạy được với `make verify` nhưng lỗi với `make ci` | Chưa commit file, hoặc code dựa vào dữ liệu chỉ DB dev mới có | Commit rồi chạy lại; đọc test lỗi |
| `EACCES ... corepack` khi `pnpm install` | Image Node cũ chưa có sẵn file chạy của pnpm | `docker rmi sm-ci-node`, lần chạy sau tự build lại |
| Còn container `sm-ci-*` sau khi bấm Ctrl+C | Script bị dừng giữa chừng | `docker compose -p sm-ci down -v` |
