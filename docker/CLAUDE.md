# docker / ops

- `docker-compose.yml` services: `ml-postgres`, `ml-redis`, `ml-php` (php-fpm), `ml-reverb` (websocket), `ml-queue` (queue worker), `ml-nextjs`, `ml-nextjs-docs`, `ml-nginx` (reverse proxy, host :81 → 8080 inside), `ml-minio` + `ml-minio-init` (bucket + lifecycle setup).
- Env: `../setup-env.sh` generates `docker/.env`, `laravel-api/.env`, FE env. Never hand-edit generated secrets into tracked files.
- `ci-cd/deploy.sh` runs on the self-hosted runner after `.github/workflows/cd.yml` pushes images to GHCR.
- CI and CD are **paused** since 2026-10-08 (owner's decision, speed over gates until P5): both workflows run only on manual `workflow_dispatch`; merging into `developer` does not deploy. `make verify` locally is the gate before every PR.
- `backup/backup.sh` / `restore.sh` — PostgreSQL + MinIO backup. Test restore after any change.
- Conventions: `.claude/rules/infra.md`.
- Images are pinned to exact versions (P4-02); bump them on purpose, never back to a floating tag. No service runs as root: `ml-php` (and the images built from the same Dockerfile) and the Next.js dev containers run as UID 1000 so files in the bind mounts stay the developer's (another host UID: build arg `UID` / `GID`); nginx is `nginx-unprivileged` listening on 8080 inside; MinIO runs as 1000 from `docker/minio/Dockerfile`; Redis must keep an exec-form `command` starting with `redis-server` so the entrypoint drops to the `redis` user. Files left root- or www-data-owned by older containers: `docker run --rm -v "$PWD/laravel-api":/app alpine:3.24 chown -R 1000:1000 /app/vendor /app/storage /app/bootstrap/cache`.
- Next.js dev images install dependencies at build time and skip pnpm's run-time reinstall (`pnpm_config_verify_deps_before_run=false`): after a `pnpm-lock.yaml` change, rebuild (`docker compose up -d --build ml-nextjs ml-nextjs-docs`).
- Docs container listens on 3457 (fixed by `nextjs-docs/package.json` and the nginx upstream); `NEXTJS_DOCS_PORT_OUTSIDE_ENV` is only the host port.
- Next.js dev runs Turbopack with native file watching (Turbopack's `watchOptions.pollIntervalMs` did not pick up changes in Docker). Keep the checkout on the Linux/WSL filesystem (e.g. `~/second_memory`, not `/mnt/c/...`) or hot reload won't see edits.
