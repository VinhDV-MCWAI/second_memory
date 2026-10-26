# Dev Postgres with its client rules baked in. Copied, not bind-mounted: right after WSL restarts
# Docker Desktop cannot see the repo for a few seconds, and a container whose bind mount fails
# then is never retried (exit 127, OPS-05). Rebuild after editing pg_hba.conf: `make up`.
FROM postgres:16.15-alpine3.24
COPY pg_hba.conf /etc/postgresql/custom/pg_hba.conf
