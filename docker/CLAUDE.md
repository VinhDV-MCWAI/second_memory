# docker / ops

- `docker-compose.yml` services: `ml-postgres`, `ml-redis`, `ml-php` (php-fpm), `ml-reverb` (websocket), `ml-queue` (queue worker), `ml-nextjs`, `ml-nextjs-docs`, `ml-nginx` (reverse proxy, entrypoint on :80), `ml-minio` + `ml-minio-init` (bucket + lifecycle setup).
- Env: `../setup-env.sh` generates `docker/.env`, `laravel-api/.env`, FE env. Never hand-edit generated secrets into tracked files.
- `ci-cd/deploy.sh` runs on the self-hosted runner after `.github/workflows/cd.yml` pushes images to GHCR.
- CI and CD are **paused** since 2026-10-08 (owner's decision, speed over gates until P5): both workflows run only on manual `workflow_dispatch`; merging into `developer` does not deploy. `make verify` locally is the gate before every PR.
- `backup/backup.sh` / `restore.sh` — PostgreSQL + MinIO backup. Test restore after any change.
- Conventions: `.claude/rules/infra.md`.
- Next.js dev runs Turbopack with native file watching (Turbopack's `watchOptions.pollIntervalMs` did not pick up changes in Docker). Keep the checkout on the Linux/WSL filesystem (e.g. `~/second_memory`, not `/mnt/c/...`) or hot reload won't see edits.
