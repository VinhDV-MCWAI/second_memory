#!/bin/bash
# ============================================
# TỰ ĐỘNG HÓA SAO LƯU HỆ THỐNG (PHIÊN BẢN TỐI ƯU)
# ============================================

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Nạp file .env từ thư mục docker
if [ -f "$SCRIPT_DIR/../docker/.env" ]; then
    set -a
    source "$SCRIPT_DIR/../docker/.env"
    set +a
fi

# Hàm dọn dẹp khi kết thúc hoặc lỗi (Zero-Local Footprint)
cleanup() {
    echo ">> [Cleanup] Đang dọn dẹp TOÀN BỘ tài nguyên cục bộ..."
    if [ -d "$BACKUP_DIR" ]; then
        rm -rf "$BACKUP_DIR"
    fi
}
trap cleanup EXIT

DOCKER_DIR=${DOCKER_DIR:-"$SCRIPT_DIR"}
PROJECT_DIR=${PROJECT_DIR:-"$(dirname "$SCRIPT_DIR")"}
BACKUP_DIR="$PROJECT_DIR/backups"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")
SYSTEM_TMP="$BACKUP_DIR/tmp_system_$TIMESTAMP"
SYSTEM_TAR="system_backup_$TIMESTAMP.tar.gz"

# Thư mục chứa các bản nén DB/Config
mkdir -p "$BACKUP_DIR/history"

# Cấu hình Rclone Remotes
RCLONE_REMOTES=${RCLONE_REMOTES:-"ggdrive:second-memory-backups onedrive:second-memory-backups"}
REMOTES_LIST=$(echo $RCLONE_REMOTES | tr ',' ' ')

echo "=== BẮT ĐẦU BACKUP TỐI ƯU ($TIMESTAMP) ==="

# -----------------------------------------------------------------------------
# PHẦN 1: CHUẨN BỊ THƯ MỤC & DỮ LIỆU LOCAL
# -----------------------------------------------------------------------------
mkdir -p "$SYSTEM_TMP/core"
mkdir -p "$SYSTEM_TMP/media"

# Xuất Database (Sử dụng định dạng Custom Format -Fc để tối ưu)
echo "[1/3] Đang xuất Database (PostgreSQL - Custom Format)..."
docker exec -e PGPASSWORD="${POSTGRES_PASSWORD}" "${POSTGRES_HOST_INSIDE_ENV:-ml-postgres}" pg_dump -U "${POSTGRES_USER}" -d "${POSTGRES_DB}" -Fc > "$SYSTEM_TMP/core/db_backup.dump"
# Copy Config
cp "$SCRIPT_DIR/../docker/.env" "$SYSTEM_TMP/core/docker.env"

# Mirror từ MinIO
echo "[2/3] Đang sao chép toàn bộ MEDIA (MinIO)..."
MINIO_CONTAINER="${MINIO_HOST_INSIDE_ENV:-ml-minio}"
MINIO_PORT="${MINIO_PORT_INSIDE_ENV:-9000}"
MINIO_BUCKET="${MINIO_BUCKET_OFFICIAL:-media-official}"

docker exec "$MINIO_CONTAINER" sh -c "mc alias set myminio http://127.0.0.1:$MINIO_PORT ${MINIO_ROOT_USER} ${MINIO_ROOT_PASSWORD} && mc mirror --overwrite myminio/$MINIO_BUCKET /tmp/media-backup"
docker cp "$MINIO_CONTAINER":/tmp/media-backup/. "$SYSTEM_TMP/media/"
docker exec "$MINIO_CONTAINER" rm -rf /tmp/media-backup

# Nén Tất cả thành 1 file duy nhất
tar -czf "$BACKUP_DIR/history/$SYSTEM_TAR" -C "$BACKUP_DIR" "tmp_system_$TIMESTAMP"
# rm -rf "$SYSTEM_TMP"  # Sẽ được cleanup bởi trap

# -----------------------------------------------------------------------------
# ĐẨY LÊN CLOUD
# -----------------------------------------------------------------------------
for REMOTE in $REMOTES_LIST; do
    echo "=========================================================="
    echo ">> (RCLONE) Đang xử lý nền tảng: $REMOTE"
    
    # Đảm bảo remote thư mục tồn tại
    rclone mkdir "$REMOTE/history"

    # 1. Upload Full System Backup
    echo ">> Uploading SYSTEM FULL BACKUP..."
    rclone copy "$BACKUP_DIR/history/$SYSTEM_TAR" "$REMOTE/history" --progress
    
    # 2. Dọn dẹp bản backup cũ trên Cloud (Mặc định 3 ngày như yêu cầu)
    echo ">> Cleaning old full backups on Cloud (> 3 ngày)..."
    rclone delete "$REMOTE/history" --min-age 3d
    echo ">> Đã xử lý xong nền tảng: $REMOTE"
done

echo "=========================================================="
echo "Hoàn thành quy trình sao lưu tối ưu! (Thời gian: $(date))"
