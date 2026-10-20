#!/usr/bin/env bash
# Full backup: PostgreSQL dump (custom format) + MinIO media bucket (including the files in the
# trash) + docker/.env, packed into system_backup_<timestamp>.tar.gz and copied to every rclone
# remote in RCLONE_REMOTES.
#
# Usage: bash backup/backup.sh [--local-only]
#   --local-only   keep the archive in backups/history/ and skip the cloud upload
# Env overrides: RCLONE_REMOTES (space or comma separated), BACKUP_DB_NAME (default POSTGRES_DB)
#
# The local archive is deleted only after every remote received it; any failure keeps it and
# exits non-zero. Runbook: docs/runbooks/backup-restore.md
set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_DIR=${PROJECT_DIR:-"$(dirname "$SCRIPT_DIR")"}
ENV_FILE="$PROJECT_DIR/docker/.env"

LOCAL_ONLY=false
case "${1:-}" in
  "") ;;
  --local-only) LOCAL_ONLY=true ;;
  *) echo "usage: $0 [--local-only]" >&2; exit 2 ;;
esac

[ -f "$ENV_FILE" ] || { echo "error: $ENV_FILE not found (make setup)" >&2; exit 2; }
set -a
# shellcheck source=/dev/null
source "$ENV_FILE"
set +a

BACKUP_DIR="$PROJECT_DIR/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
WORK_NAME="tmp_system_$TIMESTAMP"
WORK_DIR="$BACKUP_DIR/$WORK_NAME"
ARCHIVE="$BACKUP_DIR/history/system_backup_$TIMESTAMP.tar.gz"
PG_CONTAINER="${POSTGRES_HOST_INSIDE_ENV:-ml-postgres}"
DB_NAME="${BACKUP_DB_NAME:-$POSTGRES_DB}"
MINIO_CONTAINER="${MINIO_HOST_INSIDE_ENV:-ml-minio}"
MINIO_PORT="${MINIO_PORT_INSIDE_ENV:-9000}"
MINIO_BUCKET="${MINIO_BUCKET_OFFICIAL:-media-official}"
RCLONE_REMOTES=${RCLONE_REMOTES:-"ggdrive:second-memory-backups onedrive:second-memory-backups"}
read -ra REMOTES <<<"${RCLONE_REMOTES//,/ }"

# The work folder always goes; the archive stays unless every upload succeeded (see the end)
cleanup() {
  rm -rf "$WORK_DIR"
  docker exec "$MINIO_CONTAINER" rm -rf /tmp/media-backup /tmp/media-backup-trash >/dev/null 2>&1 || true
}
trap cleanup EXIT

# Fail before doing any work if the upload cannot happen
if ! $LOCAL_ONLY && ! command -v rclone >/dev/null; then
  echo "error: rclone is not installed; install it or run with --local-only" >&2
  exit 2
fi

echo "=== Backup $TIMESTAMP (database $DB_NAME, bucket $MINIO_BUCKET) ==="
mkdir -p "$WORK_DIR/core" "$WORK_DIR/media" "$WORK_DIR/media-trash" "$BACKUP_DIR/history"

echo "[1/4] PostgreSQL dump (custom format)"
docker exec -e PGPASSWORD="$POSTGRES_PASSWORD" "$PG_CONTAINER" \
  pg_dump -U "$POSTGRES_USER" -d "$DB_NAME" -Fc >"$WORK_DIR/core/db_backup.dump"
# A truncated or empty dump fails here instead of at restore time
docker exec -i "$PG_CONTAINER" pg_restore --list <"$WORK_DIR/core/db_backup.dump" >/dev/null
cp "$ENV_FILE" "$WORK_DIR/core/docker.env"

echo "[2/4] MinIO bucket $MINIO_BUCKET"
docker exec "$MINIO_CONTAINER" sh -c "mc alias set myminio http://127.0.0.1:$MINIO_PORT \"\$MINIO_ROOT_USER\" \"\$MINIO_ROOT_PASSWORD\" >/dev/null && mc mirror --overwrite --quiet myminio/$MINIO_BUCKET /tmp/media-backup >/dev/null && mkdir -p /tmp/media-backup"
docker cp "$MINIO_CONTAINER":/tmp/media-backup/. "$WORK_DIR/media/"
# mirror copies only current objects. A file in the trash is a delete marker on top of its last
# version (versioned bucket), so that version is saved under media-trash/ and restore.sh puts the
# delete marker back.
docker exec -i "$MINIO_CONTAINER" bash -s -- "$MINIO_BUCKET" "$MINIO_PORT" <<'TRASH'
set -euo pipefail
bucket=$1
mc alias set myminio "http://127.0.0.1:$2" "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD" >/dev/null
declare -A latest_ord latest_marker kept_ord kept_id
while IFS= read -r line; do
  [[ $line =~ \"key\":\"([^\"]*)\" ]] || continue
  key=${BASH_REMATCH[1]}
  [[ $line =~ \"versionId\":\"([^\"]*)\" ]] && id=${BASH_REMATCH[1]} || continue
  [[ $line =~ \"versionOrdinal\":([0-9]+) ]] && ord=${BASH_REMATCH[1]} || continue
  marker=false
  [[ $line == *'"isDeleteMarker":true'* ]] && marker=true
  if ((ord > ${latest_ord[$key]:-0})); then latest_ord[$key]=$ord; latest_marker[$key]=$marker; fi
  if ! $marker && ((ord > ${kept_ord[$key]:-0})); then kept_ord[$key]=$ord; kept_id[$key]=$id; fi
done < <(mc ls --versions --recursive --json "myminio/$bucket")
mkdir -p /tmp/media-backup-trash
count=0
for key in "${!latest_marker[@]}"; do
  [[ ${latest_marker[$key]} == true && -n ${kept_id[$key]:-} ]] || continue
  mc cp --quiet --version-id "${kept_id[$key]}" "myminio/$bucket/$key" "/tmp/media-backup-trash/$key" >/dev/null
  count=$((count + 1))
done
echo "    $count file(s) in the trash"
TRASH
docker cp "$MINIO_CONTAINER":/tmp/media-backup-trash/. "$WORK_DIR/media-trash/"

echo "[3/4] Archive"
tar -czf "$ARCHIVE" -C "$BACKUP_DIR" "$WORK_NAME"
echo "    $ARCHIVE ($(du -h "$ARCHIVE" | cut -f1))"

if $LOCAL_ONLY; then
  echo "[4/4] Upload skipped (--local-only); the archive stays in $BACKUP_DIR/history"
  exit 0
fi

echo "[4/4] Upload to: ${REMOTES[*]}"
failed=0
for remote in "${REMOTES[@]}"; do
  if rclone mkdir "$remote/history" && rclone copy "$ARCHIVE" "$remote/history"; then
    # Retention: 3 days on each remote
    rclone delete "$remote/history" --min-age 3d || echo "warning: retention cleanup failed on $remote" >&2
    echo "    $remote: ok"
  else
    echo "error: upload to $remote failed" >&2
    failed=1
  fi
done

if [ "$failed" -ne 0 ]; then
  echo "error: not every remote has the backup; keeping $ARCHIVE" >&2
  exit 1
fi
rm -f "$ARCHIVE"
echo "Done: backup uploaded to every remote, local archive removed."
