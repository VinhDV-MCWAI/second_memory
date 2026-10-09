# ADR-0011 — Infrastructure as Code: Compose for dev, Terraform for `staging` and `prod-like`

> 🇻🇳 Hạ tầng bằng code: Compose giữ cho dev, Terraform dựng `staging` và `prod-like`; state, bố cục `infra/`, khác biệt giữa môi trường, cách đưa secret vào, và xem lại lựa chọn MinIO.

| | |
|---|---|
| Status | Accepted |
| Date | 2026-10-09 |
| Deciders | TL |
| Related | [Roadmap P4](../plan/02-roadmap.md#p4--infrastructure-as-code-34-weeks); [backlog P4](../plan/03-backlog.md#p4--infrastructure-as-code) P4-02 … P4-10; [ADR-0001](0001-engineering-lab-direction.md) (local, zero cost); [ADR-0007](0007-remove-media-api.md) (MinIO kept for data only); refactor items I1, I3 ([frozen plan](../../.claude/refactor/PLAN.md)); [backup / restore runbook](../runbooks/backup-restore.md); [request profile](../reports/perf/2026-10-08-request-profile.md) |

## Context

P4's goal is to recreate the whole environment from zero with one command and prove it with a timed `destroy` / `apply` / restore of a `prod-like` environment. What is true today:

- **One Compose file** (`docker/docker-compose.yml`, project `my_LM`) runs the dev stack: `ml-postgres`, `ml-redis`, `ml-php`, `ml-reverb`, `ml-queue`, `ml-nextjs`, `ml-nextjs-docs`, `ml-nginx`, `ml-minio`, `ml-minio-init`. Dev depends on it heavily: bind-mounted source for hot reload (Next.js dev servers, PHP), and the fixed `ml-*` names are used by the Makefile, `scripts/lane.sh run`, the perf scripts, `backup/` and every lane's habits.
- **Images.** The Next.js services run the `development` target; the API image is ~1.8 GB; nothing builds Laravel caches (OPS-02 → P4-03). `NEXT_PUBLIC_API_URL` is an absolute `http://localhost:<port>/api` written by `setup-env.sh`, and Next.js inlines `NEXT_PUBLIC_*` at build time, so today an image is tied to one host and port.
- **Secrets.** `setup-env.sh` generates plain, git-ignored env files (`docker/.env`, `laravel-api/.env`, FE env). Fine for dev, where every secret is throwaway.
- **Object storage.** Since ADR-0007 no API code reads or writes S3 (`Storage::disk('s3')` has no caller); MinIO only holds the kept `media_mgmt` objects, which `backup.sh` mirrors. The image is the pinned community build `pgsty/minio:RELEASE.2026-08-04T00-00-00Z` (I3 kept it until this ADR). The upstream MinIO community edition stopped publishing binaries and images in 2025 and is in maintenance mode.
- **Host.** One WSL2 machine with ~7 GB RAM for the distro, 8 CPUs, Docker Desktop. Dev alone reserves ~3 GB (Next.js dev 2 GB limit, docs 1 GB, Postgres 1 GB, MinIO 1 GB). CI / CD are paused until P5; the old `ci-cd/deploy.sh` blue-green script deploys Compose to a self-hosted runner and is not part of P4.
- **One person.** No team shares state; no cloud account; zero cost (ADR-0001).

> 🇻🇳 Bối cảnh: mục tiêu P4 là dựng lại toàn bộ môi trường từ số 0 bằng một lệnh và chứng minh bằng một lần destroy / apply / restore có đo thời gian trên `prod-like`. Hiện trạng: một file Compose chạy dev, dev phụ thuộc nặng vào bind mount (hot reload) và tên cố định `ml-*` (Makefile, `lane.sh run`, script perf, backup đều dùng). Image Next.js đang là target development, image API ~1,8 GB, chưa build cache Laravel; `NEXT_PUBLIC_API_URL` là URL tuyệt đối bị Next.js nhúng lúc build, nên mỗi image chỉ chạy được ở một host/port. Secret là file env thường, bị git-ignore — ổn cho dev. Từ ADR-0007 không còn code nào dùng S3; MinIO chỉ giữ object cũ của `media_mgmt` và được backup; image là bản community `pgsty/minio` đã pin, MinIO upstream ngừng phát hành bản community từ 2025. Máy: WSL2 ~7 GB RAM, 8 CPU; riêng dev đã giữ ~3 GB. Một người, không cloud, chi phí 0.

## Options

### Compose and Terraform

1. **Compose only.** Override files or profiles per environment (`compose.staging.yml`, `compose.prod-like.yml`). Nothing new to learn or run; but "destroy and recreate" is `docker compose down -v && up`, there is no plan / diff before a change, no state of what exists, and buckets, policies and users stay in a shell script. It does not practise IaC, which is the phase's point.
2. **Terraform for everything, dev included.** One definition for all three environments. Dev loses what makes it usable: Compose's bind-mount and rebuild ergonomics, `docker compose logs/exec` habits, and the `ml-*` names every script and lane uses would all change in the middle of parallel work. A Terraform apply for every dev restart is slower than `compose up`.
3. **Both: Compose stays the dev tool, Terraform owns `staging` and `prod-like`.** Dev is untouched (hot reload, `ml-*` names, `make up`). The two non-dev environments run the production images (P4-03) and are fully described by Terraform: plan before change, state, destroy / apply. Cost: two descriptions of the same services that can drift.

### Terraform or OpenTofu

4. **Terraform** (HashiCorp, BUSL 1.1 since 1.6): the name employers search for, the reference docs, the `kreuzwerker/docker` and `aminueza/minio` providers. BUSL allows this use (no competing hosted product).
5. **OpenTofu** (MPL 2.0 fork, Linux Foundation): same language and providers, open licence; less recognised.

### State

6. **Local backend** per environment and layer, git-ignored, with Terraform's lock file on the local filesystem.
7. **S3 backend on the environment's own MinIO** (native S3 locking, `use_lockfile`): realistic, but the state would live inside the stack it describes — `destroy` deletes its own state, and the first `apply` has nowhere to write. A separate always-on MinIO only for state is one more thing to run for one person.
8. **Terraform Cloud / HCP**: free tier exists, but state with secrets leaves the machine and it needs an account; against ADR-0001.

### Object storage (I3 revisited)

9. **Keep the pinned `pgsty/minio` community build.** Works today with `backup.sh`, `create-buckets.sh` and the MinIO admin API the `aminueza/minio` provider needs (users, policies, lifecycle). Risk: a community-maintained fork with no vendor support.
10. **Garage, SeaweedFS, Versity S3 gateway, RustFS.** Open S3 servers. None implements the MinIO admin API, so the MinIO provider (users, policies, ILM) does not work; Garage has its own admin API and a less mature Terraform provider, RustFS is still young. Moving changes backup scripts for no product gain — the app does not use S3 at all.
11. **Drop object storage** from the Terraform environments. Simplest, but the restore drill must bring back everything `backup.sh` saves, and the MinIO provider practice is in scope.

> 🇻🇳 Phương án. Compose / Terraform: (1) chỉ Compose với file override theo môi trường — không học thêm gì nhưng không có plan, không có state, không luyện IaC; (2) Terraform cho tất cả kể cả dev — một định nghĩa duy nhất nhưng dev mất hot reload và tên `ml-*` mà mọi script và lane đang dùng; (3) **cả hai**: Compose giữ cho dev, Terraform quản lý `staging` và `prod-like` chạy image production — cái giá là hai mô tả có thể lệch nhau. Terraform hay OpenTofu: (4) Terraform — tên quen với nhà tuyển dụng, giấy phép BUSL vẫn cho phép dùng thế này; (5) OpenTofu — cùng ngôn ngữ, giấy phép mở, ít người biết hơn. State: (6) backend local, git-ignore, khoá bằng file; (7) backend S3 trên chính MinIO của môi trường — `destroy` sẽ xoá luôn state của nó; (8) Terraform Cloud — state chứa secret rời khỏi máy. Object storage: (9) giữ `pgsty/minio` đã pin — đang chạy được với backup và admin API mà provider MinIO cần; (10) Garage, SeaweedFS, Versity, RustFS — không có admin API kiểu MinIO, phải sửa backup mà ứng dụng không hề dùng S3; (11) bỏ object storage — diễn tập restore phải khôi phục đủ những gì backup lưu.

## Decision

We choose **option 3** (Compose for dev, Terraform for `staging` and `prod-like`), **option 4** (Terraform, written so OpenTofu can run it), **option 6** (local state) and **option 9** (keep the pinned `pgsty/minio`).

> 🇻🇳 Chọn **(3)** Compose cho dev, Terraform cho `staging` và `prod-like`; **(4)** Terraform, viết sao cho OpenTofu cũng chạy được; **(6)** state local; **(9)** giữ `pgsty/minio` đã pin.

### Environments

| | `dev` | `staging` | `prod-like` |
|---|---|---|---|
| Tool | Compose (`docker/docker-compose.yml`, unchanged) | Terraform | Terraform |
| Purpose | daily work, hot reload, tests | try a build before calling it a release | the release as it would run in production; destroy / apply / restore drill, k6 (P4-11) |
| Images | `development` targets, bind-mounted source | production images (P4-03), tag = git SHA under test | production images, tag of the last release |
| Names | `ml-<service>` | `sm-staging-<service>` | `sm-prod-like-<service>` |
| Network / volumes | `my_LM_*` | `sm-staging`, `sm-staging-<data>` | `sm-prod-like`, `sm-prod-like-<data>` |
| Entry | `http://localhost:81` (nginx), plus the ports dev publishes today | `https://staging.sm.localhost:8443` | `https://prod-like.sm.localhost:9443` |
| Published ports | as today (DB, Redis, MinIO for tools) | the proxy's HTTPS port only | the proxy's HTTPS port only |
| `APP_ENV` / debug | `local` / on | `staging` / off | `production` / off |
| Laravel caches | off | built at start (P4-03) | built at start (P4-03) |
| Data | dev DB | seeded fresh (`db:seed`) or a restored backup | restored from the latest backup (`restore.sh`) |
| Secrets | `setup-env.sh`, plain git-ignored files | SOPS + age | SOPS + age |
| Memory budget | as today (~3 GB) | ≤ 1.5 GB | ≤ 1.5 GB |

- `*.localhost` names resolve to the loopback address in browsers and curl without editing `hosts`; each environment gets its own cookie domain, Sanctum stateful domain and secure cookies (P4-08).
- `ml-queue` and `ml-reverb` are not created in the Terraform environments at all; dev drops them in P4-04.
- Everything a Terraform environment creates carries the Docker label `sm.env=<env>`, so a lost state can be cleaned up by label (`docker ps -aq --filter label=sm.env=prod-like`).
- The three environments must fit side by side on the host; P4-07 measures it. If they do not, `staging` and `prod-like` take turns, never dev.

> 🇻🇳 Môi trường: `dev` giữ nguyên Compose (tên `ml-*`, hot reload, secret sinh bằng `setup-env.sh`). `staging` và `prod-like` do Terraform tạo, chạy image production (staging = SHA đang thử, prod-like = bản release gần nhất), tên `sm-<env>-<service>`, chỉ mở cổng HTTPS của proxy (`https://staging.sm.localhost:8443`, `https://prod-like.sm.localhost:9443`), `APP_DEBUG` tắt, có cache Laravel, secret qua SOPS + age, mỗi môi trường ≤ 1,5 GB RAM. `*.localhost` tự trỏ về loopback nên không cần sửa file hosts. Không tạo `ml-queue` / `ml-reverb` trong môi trường Terraform. Mọi thứ Terraform tạo đều gắn nhãn `sm.env=<env>` để dọn được khi mất state. Ba môi trường phải chạy song song được (đo ở P4-07); nếu không đủ RAM thì `staging` và `prod-like` chạy luân phiên, dev luôn được ưu tiên.

### How the two descriptions stay the same

- **Build once, run anywhere.** Production images take every per-environment value at runtime, never at build: the admin and public apps call the API on the same origin (relative `/api` behind the environment's proxy) instead of a baked `NEXT_PUBLIC_API_URL`. One image tag moves from `staging` to `prod-like` unchanged. This is a P4-03 requirement.
- **One source per config file.** nginx, PostgreSQL and Redis configs stay in `docker/`; Terraform reads the same files (`file()`) and copies them into the containers with `upload` blocks, instead of bind mounts (no host paths in Terraform, which also runs inside a container). The proxy config that differs (TLS, host names) is a template next to it.
- **Same env variable names** in Compose and Terraform; Terraform only changes values.
- P4-07 adds a check that the service list in Compose (minus dev-only services) matches the Terraform module; drift found later goes into the same check.

> 🇻🇳 Giữ hai mô tả giống nhau: (1) **build một lần, chạy mọi nơi** — image production nhận mọi giá trị theo môi trường lúc chạy; FE gọi API cùng origin (`/api` sau proxy) thay vì nhúng `NEXT_PUBLIC_API_URL` lúc build, để một tag image đi từ staging sang prod-like không đổi (yêu cầu cho P4-03); (2) file cấu hình nginx / PostgreSQL / Redis chỉ có một bản trong `docker/`, Terraform đọc bằng `file()` và chép vào container qua `upload` thay vì bind mount; (3) tên biến môi trường giống nhau, Terraform chỉ đổi giá trị; (4) P4-07 thêm kiểm tra danh sách service của Compose khớp với module Terraform.

### Layout under `infra/`

```text
infra/
├── Dockerfile                 # tools image: terraform + sops + age, exact versions
├── modules/
│   ├── stack/                 # Docker provider: network, volumes, postgres, redis, php, proxy, nextjs, docs, minio
│   └── storage/               # MinIO provider: buckets, versioning, lifecycle, policy, app user
└── live/
    ├── staging/
    │   ├── stack/             # root: calls modules/stack with staging values (terraform.tfvars, no secrets)
    │   ├── storage/           # root: calls modules/storage
    │   └── secrets.enc.yaml   # SOPS-encrypted (P4-09)
    └── prod-like/             # same shape
```

- **Two layers per environment, applied in order** (`stack`, then `storage`). The MinIO provider needs a reachable endpoint when it is configured, and a provider cannot depend on a container created in the same apply; splitting the roots removes the race. `make tf-apply env=<env>` runs both, `make tf-destroy env=<env>` runs them in reverse.
- **Terraform runs in the tools container**, never on the host (nothing is installed there): it mounts `/var/run/docker.sock` for the Docker provider and joins the environment's network, so the `storage` layer reaches MinIO by its container name (`http://sm-<env>-minio:9000`) without publishing the port.
- **Versions pinned:** Terraform, SOPS and age by exact version in the tools image (digests with the P5 image policy); providers by exact version in `required_providers`, with `.terraform.lock.hcl` committed.
- **Images are built outside Terraform** (`docker build` from the existing Dockerfiles, P4-03) and passed in as a tag variable; Terraform does not build.
- **Modules only where used twice:** `stack` and `storage` are each used by two environments. No module per service.

> 🇻🇳 Bố cục `infra/`: `Dockerfile` cho image công cụ (terraform + sops + age, pin phiên bản); `modules/stack` (provider Docker: network, volume, các container) và `modules/storage` (provider MinIO: bucket, versioning, lifecycle, policy, user ứng dụng); `live/<env>/stack`, `live/<env>/storage` là các root gọi module với giá trị riêng, cùng `secrets.enc.yaml`. Mỗi môi trường **hai lớp áp dụng theo thứ tự** (stack rồi storage) vì provider MinIO cần endpoint có sẵn lúc cấu hình, không thể phụ thuộc container tạo trong cùng lần apply. Terraform chạy trong container công cụ (mount socket Docker, tham gia network của môi trường nên gọi MinIO bằng tên container). Pin phiên bản Terraform, SOPS, age, provider; commit `.terraform.lock.hcl`. Image được build ngoài Terraform và truyền vào qua biến tag. Chỉ tạo module khi dùng ít nhất hai lần.

### State

- Local backend: `infra/live/<env>/<layer>/terraform.tfstate`, git-ignored with its backups and `.terraform/`. Terraform's local lock file stops two runs on the same state; the Make targets also run through `scripts/lane.sh run`, so Terraform never runs next to another lane's test run.
- **The state holds secrets in plain text** (container environment variables are resource attributes). It stays on the host only, file mode `600`, never in git, never in `backup.sh`'s archive or an rclone remote.
- **Losing state is cheap by design:** remove the environment by its `sm.env` label, `apply` again and restore data from the backup — the same steps as the P4-10 drill. Data lives in Docker volumes and in backups, never only in state.
- Revisit a remote backend when there is a second person or a CI job that applies (P5 / P7).

> 🇻🇳 State: backend local tại `infra/live/<env>/<layer>/terraform.tfstate`, git-ignore; file lock của Terraform chặn hai lần chạy cùng lúc, và target Make chạy qua `lane.sh run` để không đụng lane khác. **State chứa secret dạng rõ** (biến môi trường của container), nên chỉ nằm trên máy, quyền `600`, không vào git, không vào archive backup hay remote rclone. Mất state không đáng ngại: xoá môi trường theo nhãn, apply lại, restore dữ liệu — đúng các bước của buổi diễn tập P4-10. Xem lại backend remote khi có người thứ hai hoặc CI tự apply.

### Secrets (built in P4-09)

- `infra/live/<env>/secrets.enc.yaml`, encrypted with SOPS using an **age** recipient; committed. The age private key stays outside the repo (`~/.config/sops/age/keys.txt`, mounted read-only into the tools container) and has an offline copy; losing it is covered by the P4-09 runbook (generate new secrets, re-encrypt, rotate DB / MinIO credentials).
- Make targets run Terraform through `sops exec-env`, which exposes the values as `TF_VAR_*` variables to that one process: **no decrypted file on disk**. Secret variables are declared `sensitive = true`.
- Dev keeps `setup-env.sh` and plain git-ignored files; its secrets are throwaway and changing dev is out of scope. P4-09's "`setup-env.sh` decrypts" is replaced by `sops exec-env` in the Make targets.

> 🇻🇳 Secret (làm ở P4-09): `infra/live/<env>/secrets.enc.yaml` mã hoá bằng SOPS với khoá **age**, được commit; khoá riêng age nằm ngoài repo (`~/.config/sops/age/keys.txt`, mount chỉ đọc) và có bản sao offline; mất khoá xử lý theo runbook P4-09. Target Make chạy Terraform qua `sops exec-env` nên giá trị chỉ là biến `TF_VAR_*` của một tiến trình, **không có file giải mã trên đĩa**; biến secret khai báo `sensitive = true`. Dev giữ `setup-env.sh` như cũ. Ý "`setup-env.sh` giải mã" trong P4-09 được thay bằng `sops exec-env` trong target Make.

### Object storage

- Keep `pgsty/minio`, pinned by release tag (digest with P5), in all three environments. Its only job is to hold the kept media objects so that backup and restore are complete, and to be the target of the MinIO provider.
- In the Terraform environments the `storage` layer replaces `docker/minio/create-buckets.sh` (buckets, versioning, lifecycle from `ilm-*.json`, policy, app user); dev keeps the script.
- **Revisit** when one of these happens: no `pgsty/minio` release for six months while a MinIO CVE is open; the `aminueza/minio` provider stops working against it; or a feature needs object storage again (then choose with the requirement). The S3 API is the contract, so a swap touches infrastructure and backup scripts, not product code.

> 🇻🇳 Object storage: giữ `pgsty/minio` (pin theo tag release, digest ở P5) ở cả ba môi trường; nhiệm vụ duy nhất là giữ object media cũ để backup/restore đầy đủ và làm đích cho provider MinIO. Ở môi trường Terraform, lớp `storage` thay `create-buckets.sh`; dev giữ script. **Xem lại** khi: sáu tháng không có bản `pgsty/minio` mới trong khi có CVE MinIO; provider `aminueza/minio` không còn chạy với nó; hoặc có tính năng cần lại object storage. Hợp đồng là S3 API, nên đổi server chỉ ảnh hưởng hạ tầng và script backup, không đụng code sản phẩm.

## Consequences

- **Easier:** dev and every lane keep working exactly as today while P4 proceeds; `staging` / `prod-like` can be planned, diffed, destroyed and recreated with one command each; one image tag is promoted between environments; the restore drill has a scratch place that is not dev (the gap OPS-01 left); secrets of the non-dev environments are in git but encrypted.
- **Harder:** two descriptions of the stack (Compose and Terraform) — mitigated by shared config files, same variable names and the P4-07 check, but a new service must be added in both. The state file is a secret on disk. A tools image and an age key to look after. Running three environments on ~7 GB may force `staging` and `prod-like` to take turns.
- **Next, by task:**
  - P4-02 hardening applies to the dev Compose file and to the Dockerfiles both worlds share.
  - P4-03: production images take the API URL at runtime (same-origin `/api`), build Laravel caches at start only after API-06; one tag for both Terraform environments.
  - P4-05: `infra/Dockerfile`, `modules/stack`, `live/prod-like/stack`, `make tf-plan` / `tf-apply` / `tf-destroy env=…`, `.gitignore` entries for state; labels `sm.env`.
  - P4-06: `modules/storage` + `live/*/storage`, replacing `create-buckets.sh` there.
  - P4-07: `staging` next to `prod-like` and dev (the two Terraform environments plus Compose dev, not three Terraform environments); memory measured; Compose ↔ Terraform service-list check.
  - P4-08: proxy with `mkcert` certificates for `*.sm.localhost`.
  - P4-09: SOPS + age as above; `setup-env.sh` untouched.
  - P4-10: `backup/restore.sh` must target `sm-prod-like-*` containers (it already reads `POSTGRES_HOST_INSIDE_ENV` / `MINIO_HOST_INSIDE_ENV`); drill timed against RTO 30 min.

> 🇻🇳 Hệ quả. **Dễ hơn:** dev và các lane làm việc như cũ trong suốt P4; `staging` / `prod-like` có plan, diff, destroy và dựng lại bằng một lệnh; một tag image được đẩy qua các môi trường; có nơi restore thử không phải dev (khoảng trống OPS-01 để lại); secret môi trường không phải dev nằm trong git nhưng đã mã hoá. **Khó hơn:** hai mô tả stack (Compose và Terraform), thêm service mới phải thêm cả hai chỗ — giảm rủi ro bằng file cấu hình dùng chung, cùng tên biến, kiểm tra ở P4-07; file state là secret trên đĩa; phải giữ image công cụ và khoá age; ~7 GB RAM có thể buộc staging và prod-like chạy luân phiên. **Việc tiếp theo:** P4-02 hardening file Compose dev và Dockerfile dùng chung; P4-03 image production nhận URL API lúc chạy, build cache Laravel sau API-06; P4-05 image công cụ, module `stack`, `make tf-plan/tf-apply/tf-destroy`, git-ignore state; P4-06 module `storage` thay `create-buckets.sh`; P4-07 staging song song với prod-like và dev Compose, đo RAM, kiểm tra lệch service; P4-08 proxy `mkcert` cho `*.sm.localhost`; P4-09 SOPS + age, không đụng `setup-env.sh`; P4-10 `restore.sh` nhắm container `sm-prod-like-*`, đo thời gian so với RTO 30 phút.
