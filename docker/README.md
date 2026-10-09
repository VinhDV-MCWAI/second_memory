# docker — the dev stack (Docker Compose)

> 🇻🇳 Môi trường dev chạy bằng Docker Compose. Mọi công cụ (PHP, Composer, Node, pnpm) chạy trong container.

The whole development environment is one Compose project, `docker/docker-compose.yml`. The host needs only Docker (Docker Desktop with WSL2 works) and `make`; run the commands from the repository root. Production-like environments are built with Terraform instead: see [infra/README.md](../infra/README.md).

> 🇻🇳 Toàn bộ môi trường dev là một Compose project. Máy chỉ cần Docker và `make`; chạy lệnh từ thư mục gốc. Môi trường giống production dùng Terraform (`infra/`).

## Services

> 🇻🇳 Các service. Port bên ngoài là mặc định trong `docker/.env.example`.

| Container | Image / build | Role | Host port | Memory limit |
|---|---|---|---|---|
| `ml-nginx` | `nginx-unprivileged` 1.25 | reverse proxy: `/api/` → PHP-FPM, `/skills`, `/docs` → public site, the rest → admin | **81** | 256 MB |
| `ml-php` | `laravel/Dockerfile` (PHP 8.5 FPM) | Laravel API; migrates and seeds an empty database on start | – | 2 GB |
| `ml-nextjs` | `nextjs/Dockerfile` (Node 24) | admin dashboard, Next.js dev server | 3456 | 2 GB |
| `ml-nextjs-docs` | `nextjs/Dockerfile` | public site, Next.js dev server | 3002 | 1 GB |
| `ml-postgres` | `postgres:16` | dev database `ml_pg_db` and test database `testing` | 5502 | 1 GB |
| `ml-redis` | `redis:7.4` | sessions and cache (tests use Redis DBs 14 / 15) | 6601 | 512 MB |
| `ml-minio` + `ml-minio-init` | `minio/Dockerfile` | object storage for the kept media objects; `init` creates buckets and lifecycle rules once | 9100, console 9102 | 1 GB |

Every image is pinned to an exact version and no service runs as root; the details and the reasons are in [CLAUDE.md](CLAUDE.md).

> 🇻🇳 Mọi image đều ghim phiên bản cụ thể, không service nào chạy bằng root; chi tiết trong `CLAUDE.md`.

## Files

> 🇻🇳 Các file trong thư mục.

| Path | Content |
|---|---|
| `docker-compose.yml` | the services above, health checks, volumes, network `ml_network` |
| `.env.example` | template of `docker/.env`, which `make setup` (`setup-env.sh`) generates with fresh secrets; never commit `.env` |
| `laravel/` | PHP image (dev and production targets), entrypoint, `php.ini` |
| `nextjs/` | one image for both Next.js apps (`--build-arg APP=…`), dev and production targets |
| `nginx/` | `nginx.conf`, `default.conf` (routing; the Terraform stack reuses it) |
| `postgres/` | `postgresql.conf`, `pg_hba.conf` |
| `redis/` | `redis.conf` |
| `minio/` | MinIO image and `create-buckets.sh` |

## Commands

> 🇻🇳 Lệnh thường dùng (`make help` có đủ).

```bash
make up                 # generate env on first run, build and start everything
make ps                 # status and health
make logs s=ml-php      # follow one container's logs
make sh s=ml-php        # shell into a container
make restart            # recreate containers and rebuild images (after env or Dockerfile changes)
make down               # stop the stack, keep the volumes
docker exec ml-nginx nginx -t && docker exec ml-nginx nginx -s reload   # after editing nginx config in place
(cd docker && docker compose up -d --no-deps --force-recreate ml-nginx) # if the container still sees the old file
```

## Troubleshooting

> 🇻🇳 Xử lý sự cố thường gặp. Thêm các lưu ý khác ở `.claude/lab/PROGRESS.md` → "Environment gotchas".

| Symptom | Fix |
|---|---|
| Containers exit with code 127 after a Docker Desktop / WSL restart | stale bind mounts: `make up` recreates them |
| nginx ignores a config edit, or `docker restart ml-nginx` fails with "no such file or directory" | the single-file bind mount still points at the old file (`sed -i` / editors write a new one): recreate the container, see Commands |
| `ml-redis is unhealthy` on `make up` | Redis is replaying a large AOF; wait for `PONG`, then `make up` again |
| A host port is already in use | change the `*_PORT_OUTSIDE_ENV` value in `docker/.env`, then `make restart` |
| nginx answers 502 | the upstream container is down or restarting: `make ps`, then `make logs s=<container>` |
| Permission errors in `vendor/`, `storage/` or `node_modules/` | files owned by root from older images: see the `chown` command in [CLAUDE.md](CLAUDE.md) |
| Hot reload misses edits | keep the checkout on the WSL filesystem (`~/…`), not under `/mnt/c/…` |
| Next.js dev container fails after a lockfile change | rebuild it: `docker compose up -d --build ml-nextjs ml-nextjs-docs` (from `docker/`) |
