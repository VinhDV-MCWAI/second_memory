#!/bin/bash
# ============================================
# TỰ ĐỘNG HÓA PHỤC HỒI HỆ THỐNG (RESTORE)
# Chạy ở bất kỳ môi trường mới nào
# ============================================
set -e

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# Nạp file .env từ thư mục docker
if [ -f "$SCRIPT_DIR/../docker/.env" ]; then
    set -a
    source "$SCRIPT_DIR/../docker/.env"
    set +a
else
    echo "LỖI: Không tìm thấy $SCRIPT_DIR/../docker/.env"
    echo "Vui lòng copy docker/.env.example sang docker/.env và tùy chỉnh trước khi chạy restore!"
    exit 1
fi

PROJECT_DIR=${PROJECT_DIR:-"$(dirname "$SCRIPT_DIR")"}
BACKUP_DIR="$PROJECT_DIR/restore_tmp"
TIMESTAMP=$(date +"%Y%m%d_%H%M%S")

# Hàm dọn dẹp khi kết thúc hoặc lỗi
cleanup() {
    echo ">> [Cleanup] Đang dọn dẹp các tài nguyên tạm..."
    if [ -d "$BACKUP_DIR" ]; then
        rm -rf "$BACKUP_DIR"
    fi
}
trap cleanup EXIT

echo "=== BẮT ĐẦU QUÁ TRÌNH PHỤC HỒI ($TIMESTAMP) ==="

# -----------------------------------------------------------------------------
# KIỂM TRA TRẠNG THÁI HỆ THỐNG (MAINTENANCE MODE)
# -----------------------------------------------------------------------------
echo ">> Kiểm tra trạng thái các container ứng dụng..."
RUNNING_APPS=$(docker ps --format '{{.Names}}' | grep -E "ml-php|ml-reverb|ml-queue|ml-nextjs" || true)
if [ -n "$RUNNING_APPS" ]; then
    echo "CẢNH BÁO: Phát hiện các container ứng dụng đang chạy:"
    echo "$RUNNING_APPS"
    echo "Vui lòng đảm bảo hệ thống đang ở chế độ BẢO TRÌ và đã tắt các stateless containers."
    read -p "Bạn có muốn tiếp tục không? (y/n) " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

# Chọn cách lấy dữ liệu (Local File hay Rclone Cloud)
LOCAL_BACKUP_FILE=$1

if [ -n "$LOCAL_BACKUP_FILE" ] && [ -f "$LOCAL_BACKUP_FILE" ]; then
    echo ">> (LOCAL) Tìm thấy file backup được chỉ định: $LOCAL_BACKUP_FILE"
    LATEST_BACKUP=$(basename "$LOCAL_BACKUP_FILE")
    rm -rf "$BACKUP_DIR"
    mkdir -p "$BACKUP_DIR"
    cp "$LOCAL_BACKUP_FILE" "$BACKUP_DIR/$LATEST_BACKUP"
    cd "$BACKUP_DIR"
    echo "[1/3] Đang sử dụng file hệ thống Local..."
else
    # Chọn remote để lấy dữ liệu (Mặc định dùng remote đầu tiên trong danh sách)
    RCLONE_REMOTES=${RCLONE_REMOTES:-"ggdrive:second-memory-backups"}
    REMOTE=$(echo $RCLONE_REMOTES | tr ',' ' ' | awk '{print $1}')

    echo ">> Đang lấy danh sách các bản backup từ Cloud ($REMOTE)..."
    LATEST_BACKUP=$(rclone lsf "$REMOTE/history/" -F p | grep "system_backup" | sort | tail -n 1)

    if [ -z "$LATEST_BACKUP" ]; then
        echo "LỖI: Không tìm thấy bản backup nào trên $REMOTE/history/"
        exit 1
    fi

    echo ">> Đã tìm thấy bản backup mới nhất: $LATEST_BACKUP"

    rm -rf "$BACKUP_DIR"
    mkdir -p "$BACKUP_DIR"
    cd "$BACKUP_DIR"

    # -----------------------------------------------------------------------------
    # PHẦN 1: TẢI & GIẢI NÉN BACKUP
    # -----------------------------------------------------------------------------
    echo "[1/3] Đang tải file hệ thống từ Cloud..."
    rclone copy "$REMOTE/history/$LATEST_BACKUP" .
fi

echo ">> Đang giải nén dữ liệu..."
tar -xzf "$LATEST_BACKUP"
EXTRACTED_DIR=$(ls -d */ | grep tmp_system)

# -----------------------------------------------------------------------------
# ĐỢI DỊCH VỤ CỐT LÕI (HEALTH CHECK)
# -----------------------------------------------------------------------------
PG_CONTAINER="${POSTGRES_HOST_INSIDE_ENV:-ml-postgres}"
MINIO_CONTAINER="${MINIO_HOST_INSIDE_ENV:-ml-minio}"

echo ">> Đang chờ các dịch vụ cốt lõi (Postgres, MinIO) sẵn sàng..."
for i in {1..30}; do
    PG_HEALTH=$(docker inspect -f '{{.State.Health.Status}}' "$PG_CONTAINER" 2>/dev/null || echo "unstarted")
    MINIO_HEALTH=$(docker inspect -f '{{.State.Health.Status}}' "$MINIO_CONTAINER" 2>/dev/null || echo "unstarted")
    
    if [ "$PG_HEALTH" == "healthy" ] && [ "$MINIO_HEALTH" == "healthy" ]; then
        echo ">> Các dịch vụ cốt lõi đã SẴN SÀNG."
        break
    fi
    
    echo "   - Đang chờ... (Lần thử $i/30) [PG: $PG_HEALTH, MinIO: $MINIO_HEALTH]"
    sleep 5
    
    if [ $i -eq 30 ]; then
        echo "LỖI: Timeout chờ các dịch vụ cốt lõi đạt trạng thái healthy."
        exit 1
    fi
done

# -----------------------------------------------------------------------------
# PHẦN 2: RESTORE CORE (DATABASE)
# -----------------------------------------------------------------------------
echo "[2/3] Đang khôi phục Database (pg_restore)..."
DB_DUMP_FILE="$EXTRACTED_DIR/core/db_backup.dump"
if [ -f "$DB_DUMP_FILE" ]; then
    PG_CONTAINER="${POSTGRES_HOST_INSIDE_ENV:-ml-postgres}"
    echo "   - Đang đưa file dump vào container..."
    docker cp "$DB_DUMP_FILE" "$PG_CONTAINER":/tmp/db_backup.dump
    
    echo "   - Đang thực thi pg_restore (Custom Format)..."
    # Sử dụng pg_restore với cờ --clean và --if-exists để làm sạch schema trước khi nạp
    docker exec -i "$PG_CONTAINER" sh -c "PGPASSWORD=\$POSTGRES_PASSWORD pg_restore -U \$POSTGRES_USER -d \$POSTGRES_DB -v --clean --if-exists /tmp/db_backup.dump" > /dev/null 2>&1 || echo "Cảnh báo: Có lưu ý nhỏ trong quá trình pg_restore (thường là các ràng buộc quyền hạn)."
    
    echo "   - Đang dọn dẹp file tạm trong container..."
    docker exec -i "$PG_CONTAINER" rm /tmp/db_backup.dump
    echo ">> Khôi phục Database hoàn tất!"
else
    echo "Cảnh báo: Không tìm thấy file $DB_DUMP_FILE"
fi

# -----------------------------------------------------------------------------
# PHẦN 3: RESTORE MEDIA (MINIO)
# -----------------------------------------------------------------------------
echo "[3/3] Đang phục hồi toàn bộ dữ liệu MEDIA (MinIO)..."

MINIO_CONTAINER="${MINIO_HOST_INSIDE_ENV:-ml-minio}"
MINIO_BUCKET="${MINIO_BUCKET_OFFICIAL:-media-official}"

MEDIA_TMP="$EXTRACTED_DIR/media"

if [ -d "$MEDIA_TMP" ]; then
    echo ">> Đang đưa dữ liệu vào MinIO Bucket..."
    docker exec "$MINIO_CONTAINER" rm -rf /tmp/restore-media
    docker cp "$MEDIA_TMP/." "$MINIO_CONTAINER:/tmp/restore-media/"

    # Sử dụng biến môi trường nội bộ của container để tránh lỗi lệch signature
    docker exec "$MINIO_CONTAINER" sh -c "mc alias set myminio http://127.0.0.1:\${MINIO_PORT_INSIDE_ENV:-9000} \$MINIO_ROOT_USER \$MINIO_ROOT_PASSWORD && mc mb --ignore-existing myminio/$MINIO_BUCKET && mc mirror --overwrite /tmp/restore-media/ myminio/$MINIO_BUCKET && rm -rf /tmp/restore-media/"
    echo ">> Restore Media hoàn tất!"
else
    echo "Cảnh báo: Không tìm thấy thư mục MEDIA trong bản backup."
fi

# -----------------------------------------------------------------------------
# PHẦN 4: ĐỒNG BỘ CẤU HÌNH (SETUP-ENV) TRƯỚC KHI XOÁ rác
# -----------------------------------------------------------------------------
# docker.env từ backup ghi đè lên docker/.env (trước khi xoá extracted dir)
DOCKER_ENV_BACKUP="$BACKUP_DIR/$EXTRACTED_DIR/core/docker.env"
if [ -f "$DOCKER_ENV_BACKUP" ]; then
    echo ">> Phục hồi file cấu hình docker/.env từ backup..."
    rm -f "$SCRIPT_DIR/../docker/.env"
    cp "$DOCKER_ENV_BACKUP" "$SCRIPT_DIR/../docker/.env"
fi

# -----------------------------------------------------------------------------
# DỌN DẸP TẠM
# -----------------------------------------------------------------------------
echo "=========================================================="
echo ">> Đang dọn dẹp các file tạm..."
cd "$SCRIPT_DIR"
rm -rf "$BACKUP_DIR"

# Gọi setup-env.sh để inject credentials vào tất cả file .env liên quan
SETUP_ENV="$SCRIPT_DIR/../setup-env.sh"
if [ -f "$SETUP_ENV" ]; then
    echo ">> Chạy setup-env.sh để đồng bộ credentials vào laravel-api/.env..."
    bash "$SETUP_ENV"
fi

# -----------------------------------------------------------------------------
# PHẦN 5: ĐỒNG BỘ MẬT KHẨU ROLE DB & KHỞI ĐỘNG LẠI DỊCH VỤ
# -----------------------------------------------------------------------------
echo "=========================================================="
echo ">> [ÚP DATE] Đồng bộ mật khẩu Role Database và khởi động lại dịch vụ..."

# Đảm bảo Postgres nhận mật khẩu mới (Nếu có lệch do restore từ volume cũ)
if [ -n "$POSTGRES_PASSWORD" ]; then
    echo ">> Đang đồng bộ mật khẩu role $POSTGRES_USER trong Database..."
    docker exec -i "$PG_CONTAINER" psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "ALTER USER $POSTGRES_USER WITH PASSWORD '$POSTGRES_PASSWORD';" > /dev/null 2>&1 || echo "Cảnh báo: Không thể tự động ALTER USER (có thể do quyền hạn hoặc DB chưa sẵn sàng)."
fi

# Khởi động lại các stateless containers để nạp biến môi trường mới
echo ">> Đang khởi động lại các dịch vụ ứng dụng (ml-php, ml-reverb, ml-queue, ml-redis)..."
cd "$SCRIPT_DIR/../docker"
docker-compose up -d --force-recreate ml-php ml-reverb ml-queue ml-redis

# Xóa config cache (lần nữa để chắc chắn)
echo ">> Xóa Laravel config cache trong container mới..."
docker exec ml-php php artisan config:clear

# Xóa config cache để Laravel load lại .env với credentials mới
echo ">> Xóa Laravel config cache..."
if docker ps -q --filter name=ml-php | grep -q .; then
    docker exec ml-php php artisan config:clear
    echo ">> Config cache cleared."
fi

echo "Hoàn thành quy trình khôi phục dữ liệu! (Thời gian: $(date))"
echo "→ Hãy kiểm tra lại hệ thống và khởi động lại ml-php nếu cần thiết."
