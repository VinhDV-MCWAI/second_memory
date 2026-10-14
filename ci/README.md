# CI/CD dùng chung

Mọi bước kiểm tra, build và deploy nằm trong thư mục này. Máy dev (`make ci`, `make cd`), GitHub và GitLab đều gọi đúng các script ở đây, nên kết quả giống nhau. Lý do và các quyết định: [ADR-0016](../docs/adr/0016-ci-shared-folder.md).

> **Trạng thái (2026-10-14): CI/CD chỉ chạy trên máy. Trên GitHub và GitLab đang TẮT chạy tự động.**
> Push hay mở pull request **không** chạy gì. Chạy trên GitHub để làm sau (quyết định của chủ dự án). Cách bật lại: mục [Bật lại trên GitHub, GitLab](#bật-lại-trên-github-gitlab).

## Có gì trong thư mục

| File | Làm gì |
|---|---|
| `check.sh` | Chạy mọi bước kiểm tra trong container tạo mới, trên code **đã commit** |
| `compose.yml` | Môi trường riêng cho `check.sh`: Postgres, Redis, PHP, Node. Tên project `sm-ci`, không mở cổng, không đụng DB dev |
| `build.sh` | Build image production (API, giao diện quản trị, trang Skill Ledger, MinIO) từ code đã commit. `make tf-images` cũng dùng file này |
| `deploy.sh` | CD trên máy (`make cd`): `check.sh` → `build.sh` → Terraform dựng bản demo `prod-like` → test trình duyệt (Playwright) trên bản demo |

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

## CD trên máy: bản demo

```bash
make cd                 # khoảng 10–15 phút; xong thì mở http://localhost:9443 (root / 12345678)
make cd SKIP_CHECK=1    # commit đã qua make ci: bỏ bước kiểm tra
make e2e env=prod-like  # chỉ chạy lại test trình duyệt trên bản demo
make tf-destroy         # gỡ bản demo (mất dữ liệu demo)
```

| Bước | Làm gì | Lỗi thì |
|---|---|---|
| 1. Kiểm tra | `check.sh`, như `make ci` | Dừng, không deploy |
| 2. Build | `build.sh`: 4 image gắn tag là mã commit ngắn | Dừng |
| 3. Deploy | Terraform (`infra/`) thay các container của bản demo bằng image mới, chờ tất cả khỏe. Dữ liệu demo (DB, file) giữ nguyên giữa các lần deploy | Dừng; bản demo có thể ở trạng thái nửa cũ nửa mới, chạy lại `make cd` |
| 4. Test trình duyệt | `e2e/run.sh prod-like`: đăng nhập, Skill Ledger, vai trò, Files (upload nhỏ và lớn), việc nền + thông báo tức thì. Dữ liệu test tự xóa sau khi chạy | Báo lỗi, ảnh chụp và trace ở `e2e/artifacts/` |

Chi tiết bản demo (container, RAM, giới hạn): [infra/README.md](../infra/README.md).

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

Đang **tắt chạy tự động**, chỉ chạy khi bấm tay: GitHub → Actions → CI → Run workflow → chọn branch. Có 2 job song song: `Checks (ci/check.sh)` và `Production images (ci/build.sh)`. **Chưa chạy thử lần nào trên GitHub.** Deploy chỉ có trên máy (`make cd`), GitHub không có bước deploy.

## Chạy trên GitLab

**Chưa test** (chưa có project GitLab). Cần một runner kiểu shell executor trên máy có bash, git, Docker; giống máy dev. Runner kiểu Docker + `docker:dind` không chạy được, vì `check.sh` gắn một thư mục tạm vào container, mà Docker trong dind không thấy thư mục đó. Chạy tay: Build → Pipelines → Run pipeline.

## Lỗ hổng thư viện đã biết

Hai bước `audit` chỉ báo, không làm hỏng lần chạy. Những gì đã xem xét:

| Thư viện | Mức | Kết luận (ngày xem) |
|---|---|---|
| `braces` 3.0.3 ([GHSA-vfj7-8cjw-p6xm](https://github.com/advisories/GHSA-vfj7-8cjw-p6xm)) | Cao | **Chưa có bản sửa** (3.0.3 là bản mới nhất). Chỉ có trong công cụ lint (`eslint-config-next` → `fast-glob` → `micromatch`), không vào image production, chỉ đọc mẫu glob của chính dự án. Chấp nhận, xem lại khi `eslint-config-next` lên bản mới (2026-10-14) |

## Bật lại trên GitHub, GitLab

Những gì đang tắt và cách bật lại, mỗi chỗ là một thay đổi nhỏ:

| Nơi | Đang tắt thế nào | Bật lại |
|---|---|---|
| GitHub, kiểm tra khi mở pull request | `.github/workflows/ci.yml` chỉ có `on: workflow_dispatch` | Thêm vào dưới `on:` 3 dòng: `pull_request:` / `branches: [developer, main]`. Muốn chạy cả khi push thì thêm `push:` / `branches: [developer]` |
| GitLab | `.gitlab-ci.yml` chỉ chạy khi `$CI_PIPELINE_SOURCE == "web"` | Thêm một luật: `- if: $CI_PIPELINE_SOURCE == "merge_request_event"` |
| Deploy tự động | Chưa có trên GitHub/GitLab | Cần máy chạy (runner) có Docker và Terraform state; làm ở giai đoạn "Staging, production" |

Trước khi bật: chạy tay một lần trên GitHub để chắc 2 job xanh, rồi mới bắt buộc check trong Settings → Branches.

Những thứ **vẫn tự chạy** trên GitHub (không phải CI/CD, không chặn merge, chủ dự án quyết giữ): `cleanup-branch.yml` (xóa branch sau khi merge pull request), Dependabot (mở pull request nâng thư viện).

## Lỗi hay gặp

| Lỗi | Nguyên nhân | Cách xử lý |
|---|---|---|
| `openapi.json is stale` | Đổi route hoặc dữ liệu API mà chưa sinh lại file | `make openapi`, commit cả `openapi.json` và `openapi.d.ts` |
| Test chạy được với `make verify` nhưng lỗi với `make ci` | Chưa commit file, hoặc code dựa vào dữ liệu chỉ DB dev mới có | Commit rồi chạy lại; đọc test lỗi |
| `EACCES ... corepack` khi `pnpm install` | Image Node cũ chưa có sẵn file chạy của pnpm | `docker rmi sm-ci-node`, lần chạy sau tự build lại |
| `make cd`: test trình duyệt bị đẩy về trang đăng nhập | Cookie phiên không tên `laravel_session` (giao diện quản trị chỉ nhận tên này) | Giữ `SESSION_COOKIE=laravel_session` trong `infra/modules/stack/main.tf` |
| `make cd`: thiếu RAM, container bị dừng | Bản demo (~2 GB) chạy cùng stack dev | `make down` trong lúc demo, hoặc `make tf-destroy` khi xong |
| Còn container `sm-ci-*` sau khi bấm Ctrl+C | Script bị dừng giữa chừng | `docker compose -p sm-ci down -v` |
