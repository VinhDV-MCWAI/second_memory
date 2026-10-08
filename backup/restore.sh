#!/usr/bin/env bash
# Restore a backup made by backup.sh (runbook: docs/runbooks/backup-restore.md).
#
# Usage: bash backup/restore.sh [ARCHIVE] [--target-db NAME [--target-bucket NAME]] [--yes]
#   ARCHIVE          local system_backup_*.tar.gz; without it the newest one on the first
#                    RCLONE_REMOTES remote is downloaded
#   --target-db      TEST RESTORE: load the dump into this new database (dropped and recreated)
#                    instead of POSTGRES_DB; docker/.env and the containers are left alone
#   --target-bucket  with --target-db: mirror the media into this bucket instead of the official one
#   --yes            full restore: don't ask when application containers are still running
#
# Without --target-db this is the disaster-recovery restore: it overwrites POSTGRES_DB and the
# official bucket, puts back docker/.env from the backup and recreates the app containers.
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR=${PROJECT_DIR:-"$(dirname "$SCRIPT_DIR")"}
ENV_FILE="$PROJECT_DIR/docker/.env"
COMPOSE=(docker compose -f "$PROJECT_DIR/docker/docker-compose.yml")

usage() { echo "usage: $0 [ARCHIVE] [--target-db NAME [--target-bucket NAME]] [--yes]" >&2; exit 2; }
ARCHIVE_ARG="" TARGET_DB="" TARGET_BUCKET="" ASSUME_YES=false
while (($#)); do
  case "$1" in
    --target-db) TARGET_DB=${2:-}; [ -n "$TARGET_DB" ] || usage; shift 2 ;;
    --target-bucket) TARGET_BUCKET=${2:-}; [ -n "$TARGET_BUCKET" ] || usage; shift 2 ;;
    --yes) ASSUME_YES=true; shift ;;
    -*) usage ;;
    *) [ -z "$ARCHIVE_ARG" ] || usage; ARCHIVE_ARG=$1; shift ;;
  esac
done
[ -z "$TARGET_BUCKET" ] || [ -n "$TARGET_DB" ] || { echo "error: --target-bucket needs --target-db" >&2; exit 2; }

[ -f "$ENV_FILE" ] || { echo "error: $ENV_FILE not found (copy docker/.env.example and run make setup)" >&2; exit 2; }
set -a
# shellcheck source=/dev/null
source "$ENV_FILE"
set +a

PG_CONTAINER="${POSTGRES_HOST_INSIDE_ENV:-ml-postgres}"
MINIO_CONTAINER="${MINIO_HOST_INSIDE_ENV:-ml-minio}"
OFFICIAL_BUCKET="${MINIO_BUCKET_OFFICIAL:-media-official}"
TEST_MODE=false
DB_NAME="$POSTGRES_DB"
BUCKET="$OFFICIAL_BUCKET"
if [ -n "$TARGET_DB" ]; then
  TEST_MODE=true
  DB_NAME="$TARGET_DB"
  BUCKET="${TARGET_BUCKET:-}"
  # A test restore must never land on live data
  case "$TARGET_DB" in
    "$POSTGRES_DB" | postgres | testing | template0 | template1)
      echo "error: --target-db $TARGET_DB is not a throwaway database" >&2; exit 2 ;;
  esac
  [ "$BUCKET" != "$OFFICIAL_BUCKET" ] || { echo "error: --target-bucket must not be $OFFICIAL_BUCKET" >&2; exit 2; }
fi

WORK_DIR="$PROJECT_DIR/restore_tmp"
cleanup() {
  rm -rf "$WORK_DIR"
  docker exec "$PG_CONTAINER" rm -f /tmp/db_backup.dump >/dev/null 2>&1 || true
  docker exec "$MINIO_CONTAINER" rm -rf /tmp/restore-media >/dev/null 2>&1 || true
}
trap cleanup EXIT
SECONDS=0

if $TEST_MODE; then
  echo "=== TEST restore into database $DB_NAME${BUCKET:+ and bucket $BUCKET} (live data untouched) ==="
else
  echo "=== FULL restore into $DB_NAME / $BUCKET (overwrites live data) ==="
  running=$(docker ps --format '{{.Names}}' | grep -E '^ml-(php|reverb|queue|nextjs)' || true)
  if [ -n "$running" ] && ! $ASSUME_YES; then
    echo "Application containers are running (put the site in maintenance first):"
    echo "$running"
    read -r -p "Continue anyway? (y/n) " reply
    [[ $reply =~ ^[Yy]$ ]] || exit 1
  fi
fi

rm -rf "$WORK_DIR"
mkdir -p "$WORK_DIR"
if [ -n "$ARCHIVE_ARG" ]; then
  [ -f "$ARCHIVE_ARG" ] || { echo "error: $ARCHIVE_ARG not found" >&2; exit 2; }
  echo "[1/4] Local archive $ARCHIVE_ARG"
  cp "$ARCHIVE_ARG" "$WORK_DIR/"
else
  command -v rclone >/dev/null || { echo "error: rclone is not installed; pass a local archive" >&2; exit 2; }
  RCLONE_REMOTES=${RCLONE_REMOTES:-"ggdrive:second-memory-backups"}
  read -ra remotes <<<"${RCLONE_REMOTES//,/ }"
  latest=$(rclone lsf "${remotes[0]}/history/" | grep system_backup | sort | tail -n 1 || true)
  [ -n "$latest" ] || { echo "error: no backup in ${remotes[0]}/history/" >&2; exit 1; }
  echo "[1/4] Downloading $latest from ${remotes[0]}"
  rclone copy "${remotes[0]}/history/$latest" "$WORK_DIR/"
fi
archive_file=$(find "$WORK_DIR" -maxdepth 1 -name '*.tar.gz' | head -n 1)
tar -xzf "$archive_file" -C "$WORK_DIR"
EXTRACTED=$(find "$WORK_DIR" -maxdepth 1 -type d -name 'tmp_system_*' | head -n 1)
if [ -z "$EXTRACTED" ] || [ ! -f "$EXTRACTED/core/db_backup.dump" ]; then
  echo "error: archive has no core/db_backup.dump" >&2
  exit 1
fi

echo "    waiting for $PG_CONTAINER and $MINIO_CONTAINER to be healthy"
for attempt in $(seq 1 30); do
  pg=$(docker inspect -f '{{.State.Health.Status}}' "$PG_CONTAINER" 2>/dev/null || echo missing)
  minio=$(docker inspect -f '{{.State.Health.Status}}' "$MINIO_CONTAINER" 2>/dev/null || echo missing)
  [ "$pg" = healthy ] && [ "$minio" = healthy ] && break
  [ "$attempt" -lt 30 ] || { echo "error: core services not healthy (postgres $pg, minio $minio)" >&2; exit 1; }
  sleep 5
done

echo "[2/4] Database $DB_NAME (pg_restore)"
docker cp "$EXTRACTED/core/db_backup.dump" "$PG_CONTAINER":/tmp/db_backup.dump
psql_admin() { docker exec "$PG_CONTAINER" sh -c "psql -U \"\$POSTGRES_USER\" -d postgres -v ON_ERROR_STOP=1 -qc \"$1\""; }
if $TEST_MODE; then
  psql_admin "DROP DATABASE IF EXISTS \\\"$DB_NAME\\\" WITH (FORCE)"
  psql_admin "CREATE DATABASE \\\"$DB_NAME\\\""
  restore_flags="--no-owner --single-transaction"
else
  restore_flags="--clean --if-exists --single-transaction"
fi
# One transaction: any error rolls the whole restore back and stops here, so the database is
# either fully restored or left as it was (never half-dropped by --clean)
docker exec "$PG_CONTAINER" sh -c "pg_restore -U \"\$POSTGRES_USER\" -d \"$DB_NAME\" $restore_flags /tmp/db_backup.dump"

if [ -n "$BUCKET" ]; then
  echo "[3/4] Media into bucket $BUCKET"
  docker exec "$MINIO_CONTAINER" rm -rf /tmp/restore-media
  docker cp "$EXTRACTED/media/." "$MINIO_CONTAINER:/tmp/restore-media/"
  docker exec "$MINIO_CONTAINER" sh -c "mc alias set myminio http://127.0.0.1:\${MINIO_PORT_INSIDE_ENV:-9000} \"\$MINIO_ROOT_USER\" \"\$MINIO_ROOT_PASSWORD\" >/dev/null && mc mb --ignore-existing myminio/$BUCKET >/dev/null && mc mirror --overwrite --quiet /tmp/restore-media/ myminio/$BUCKET >/dev/null"
else
  echo "[3/4] Media skipped (test restore without --target-bucket)"
fi

if $TEST_MODE; then
  echo "[4/4] Restored content of $DB_NAME"
  docker exec "$PG_CONTAINER" sh -c "psql -U \"\$POSTGRES_USER\" -d \"$DB_NAME\" -qAt -F ' ' -c \"
    SELECT 'tables', count(*) FROM information_schema.tables WHERE table_schema = 'public'
    UNION ALL SELECT 'migrations', count(*) FROM migrations\"" | sed 's/^/    /'
  echo "Done in ${SECONDS}s. Live data untouched. Drop the copy when done: DROP DATABASE \"$DB_NAME\"${BUCKET:+; mc rb --force myminio/$BUCKET}"
  exit 0
fi

echo "[4/4] Configuration and application containers"
cp "$EXTRACTED/core/docker.env" "$ENV_FILE"
bash "$PROJECT_DIR/setup-env.sh"
set -a
# shellcheck source=/dev/null
source "$ENV_FILE"
set +a
# The restored role may carry an older password than the restored docker/.env
docker exec -e NEW_PASSWORD="$POSTGRES_PASSWORD" "$PG_CONTAINER" sh -c \
  "psql -U \"\$POSTGRES_USER\" -d postgres -v ON_ERROR_STOP=1 -qc \"ALTER USER \\\"\$POSTGRES_USER\\\" WITH PASSWORD '\$NEW_PASSWORD'\""
"${COMPOSE[@]}" up -d --force-recreate ml-php ml-reverb ml-queue ml-redis
docker exec ml-php php artisan config:clear
echo "Done in ${SECONDS}s. Check the site, then end maintenance mode."
