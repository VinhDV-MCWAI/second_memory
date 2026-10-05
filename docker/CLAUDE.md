# docker / ops

- `docker-compose.yml` services: `ml-postgres`, `ml-redis`, `ml-php` (php-fpm), `ml-reverb` (websocket), `ml-queue` (queue worker), `ml-nextjs`, `ml-nextjs-docs`, `ml-nginx` (reverse proxy, entrypoint on :80), `ml-minio` + `ml-minio-init` (bucket + lifecycle setup).
- Env: `../setup-env.sh` generates `docker/.env`, `laravel-api/.env`, FE env. Never hand-edit generated secrets into tracked files.
- `ci-cd/deploy.sh` runs on the self-hosted runner after `.github/workflows/cd.yml` pushes images to GHCR.
- `backup/backup.sh` / `restore.sh` — PostgreSQL + MinIO backup. Test restore after any change.
- Conventions: `.claude/rules/infra.md`.
