---
paths:
  - "docker/**"
  - "ci-cd/**"
  - ".github/**"
  - "backup/**"
  - "*.sh"
---

# Infra, CI/CD and scripts

- Shell scripts: `#!/usr/bin/env bash` + `set -euo pipefail`, idempotent (safe to re-run), quote all variables, pass `shellcheck`.
- Docker: pin image versions (no `:latest`), multi-stage builds, run as non-root, add `HEALTHCHECK`, keep `.dockerignore` tight.
- Compose: use `docker compose` (v2), `depends_on` with `condition: service_healthy`, secrets via env files that are gitignored.
- Never commit `.env`, credentials, dumps or binaries. Provide `.env.example` with placeholders.
- GitHub Actions: pin major versions (`actions/checkout@v5` etc.), least-privilege `permissions:`, cache pnpm/composer, image tags by commit SHA (plus `latest` only as an alias).
- Changes to backup/restore must be tested by an actual restore into a scratch DB.
