#!/bin/bash
set -e
echo "=== SECOND MEMORY: UNIFIED CONFIGURATION SETUP ==="

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCKER_ENV_EXAMPLE="$ROOT_DIR/docker/.env.example"
DOCKER_ENV="$ROOT_DIR/docker/.env"

if [ ! -f "$DOCKER_ENV" ]; then
    echo ">> [1/4] Copying docker/.env.example to docker/.env..."
    cp "$DOCKER_ENV_EXAMPLE" "$DOCKER_ENV"
else
    echo ">> [1/4] Master docker/.env already exists. Ensuring variables are present..."
    # Ensure LARAVEL lines exist in the current .env if it was created from old example
    if ! grep -q "LARAVEL_APP_KEY" "$DOCKER_ENV"; then
        echo -e "\nLARAVEL_APP_KEY=\nLARAVEL_ACCESS_TOKEN_SECRET=\nLARAVEL_REFRESH_TOKEN_SECRET=" >> "$DOCKER_ENV"
    fi
fi

# Function to generate a secure random string and update .env
function generate_and_inject() {
    local KEY=$1
    local GENERATOR=$2
    local VAL=$(grep "^$KEY=" "$DOCKER_ENV" | cut -d '=' -f2)
    
    # If the value is empty or has a default dummy value like 'ml_pg_password' or 'my-app-key', we regenerate
    if [[ -z "$VAL" || "$VAL" == "ml_pg_password" || "$VAL" == "ml_redis_password" || "$VAL" == "ml_minio_password123" || "$VAL" == "strong-backend-password" || "$VAL" == "my-app-key" || "$VAL" == "my-app-secret" ]]; then
        if [ "$GENERATOR" == "base64" ]; then
            NEW_VAL="base64:$(openssl rand -base64 32)"
        else
            NEW_VAL="$(openssl rand -hex 16)"
        fi
        # Using sed to replace the line
        sed -i "s|^$KEY=.*|$KEY=$NEW_VAL|" "$DOCKER_ENV"
        echo "   - Generated new secure value for $KEY"
    fi
}

echo ">> [2/4] Auto-generating secrets in docker/.env..."
generate_and_inject POSTGRES_PASSWORD hex
generate_and_inject REDIS_PASSWORD hex
generate_and_inject MINIO_ROOT_PASSWORD hex
generate_and_inject MINIO_IAM_PASSWORD hex
generate_and_inject REVERB_APP_KEY hex
generate_and_inject REVERB_APP_SECRET hex
generate_and_inject LARAVEL_APP_KEY base64
generate_and_inject LARAVEL_ACCESS_TOKEN_SECRET base64
generate_and_inject LARAVEL_REFRESH_TOKEN_SECRET base64

# Now source the variables so we can inject them into sub-projects
set -a
source "$DOCKER_ENV"
set +a

echo ">> [3/5] Synchronizing configuration to Laravel API..."
LARAVEL_ENV_EXAMPLE="$ROOT_DIR/laravel-api/.env.example"
LARAVEL_ENV="$ROOT_DIR/laravel-api/.env"

if [ -f "$LARAVEL_ENV_EXAMPLE" ]; then
    # Force remove existing .env to avoid permission issues if owned by root
    rm -f "$LARAVEL_ENV"
    cp "$LARAVEL_ENV_EXAMPLE" "$LARAVEL_ENV"
    
    # Inject infrastructure variables
    sed -i "s|^DB_HOST=.*|DB_HOST=${POSTGRES_HOST_INSIDE_ENV}|" "$LARAVEL_ENV"
    sed -i "s|^DB_PASSWORD=.*|DB_PASSWORD=${POSTGRES_PASSWORD}|" "$LARAVEL_ENV"
    sed -i "s|^DB_DATABASE=.*|DB_DATABASE=${POSTGRES_DB}|" "$LARAVEL_ENV"
    sed -i "s|^DB_USERNAME=.*|DB_USERNAME=${POSTGRES_USER}|" "$LARAVEL_ENV"
    sed -i "s|^REDIS_HOST=.*|REDIS_HOST=${REDIS_HOST_INSIDE_ENV}|" "$LARAVEL_ENV"
    sed -i "s|^REDIS_PASSWORD=.*|REDIS_PASSWORD=${REDIS_PASSWORD}|" "$LARAVEL_ENV"
    sed -i "s|^AWS_ACCESS_KEY_ID=.*|AWS_ACCESS_KEY_ID=${MINIO_IAM_USER}|" "$LARAVEL_ENV"
    sed -i "s|^AWS_SECRET_ACCESS_KEY=.*|AWS_SECRET_ACCESS_KEY=${MINIO_IAM_PASSWORD}|" "$LARAVEL_ENV"
    sed -i "s|^REVERB_APP_KEY=.*|REVERB_APP_KEY=${REVERB_APP_KEY}|" "$LARAVEL_ENV"
    sed -i "s|^REVERB_APP_SECRET=.*|REVERB_APP_SECRET=${REVERB_APP_SECRET}|" "$LARAVEL_ENV"
    
    # Inject Laravel security variables
    sed -i "s|^APP_KEY=.*|APP_KEY=${LARAVEL_APP_KEY}|" "$LARAVEL_ENV"
    sed -i "s|^ACCESS_TOKEN_SECRET=.*|ACCESS_TOKEN_SECRET=${LARAVEL_ACCESS_TOKEN_SECRET}|" "$LARAVEL_ENV"
    sed -i "s|^REFRESH_TOKEN_SECRET=.*|REFRESH_TOKEN_SECRET=${LARAVEL_REFRESH_TOKEN_SECRET}|" "$LARAVEL_ENV"
    
    echo "   - laravel-api/.env updated from master configuration."
fi

echo ">> [4/5] Synchronizing configuration to Redis..."
REDIS_CONF="$ROOT_DIR/docker/redis/redis.conf"
if [ -f "$REDIS_CONF" ]; then
    sed -i "s|^requirepass .*|requirepass ${REDIS_PASSWORD}|" "$REDIS_CONF"
    echo "   - docker/redis/redis.conf updated with REDIS_PASSWORD."
fi

echo ">> [5/5] Synchronizing configuration to Frontend..."
NEXTJS_FE_ENV="$ROOT_DIR/nextjs-fe/.env"

# For nextjs-fe
if [ -f "$ROOT_DIR/nextjs-fe/.tenv.example" ]; then
    rm -f "$NEXTJS_FE_ENV"
    cp "$ROOT_DIR/nextjs-fe/.tenv.example" "$NEXTJS_FE_ENV"
    sed -i "s|^NEXT_PUBLIC_REVERB_APP_KEY=.*|NEXT_PUBLIC_REVERB_APP_KEY=${REVERB_APP_KEY}|" "$NEXTJS_FE_ENV"
    sed -i "s|^NEXT_PUBLIC_API_URL=.*|NEXT_PUBLIC_API_URL=http://localhost:${NGINX_PORT_OUTSIDE_ENV}/api|" "$NEXTJS_FE_ENV"
    echo "   - nextjs-fe/.env updated from master configuration."
fi

# For nextjs-docs
NEXTJS_DOCS_ENV="$ROOT_DIR/nextjs-docs/.env"
if [ -d "$ROOT_DIR/nextjs-docs" ]; then
    rm -f "$NEXTJS_DOCS_ENV"
    echo "NEXT_PUBLIC_API_URL=http://localhost:${NGINX_PORT_OUTSIDE_ENV}/api" > "$NEXTJS_DOCS_ENV"
    echo "PORT=${NEXTJS_DOCS_PORT_INSIDE_ENV:-3001}" >> "$NEXTJS_DOCS_ENV"
    echo "   - nextjs-docs/.env updated from master configuration."
fi

echo "=== UNIFIED CONFIGURATION SETUP COMPLETE ==="
echo "Các mật khẩu và khóa bảo mật đã được đồng bộ tự động cho toàn bộ hệ thống!"

# Check if containers are running
RUNNING_CONTAINERS=$(docker ps --filter name=ml- --format '{{.Names}}' || true)
if [ -n "$RUNNING_CONTAINERS" ]; then
    echo ""
    echo ">> [CẢNH BÁO] Hệ thống Docker đang chạy! Các thay đổi về mật khẩu (.env) SẼ KHÔNG có hiệu lực ngay lập tức."
    echo ">> Bạn CẦN thực hiện khởi động lại container để áp dụng cấu hình mới:"
    echo "   cd docker && docker-compose up -d --force-recreate ml-php ml-reverb ml-queue ml-redis"
    echo ""
    
    # Optional: Automatically try to sync DB password if postgres is running
    if [[ "$RUNNING_CONTAINERS" == *"ml-postgres"* ]]; then
        echo ">> Phát hiện ml-postgres đang chạy. Đang thử đồng bộ mật khẩu role nội bộ..."
        docker exec -i ml-postgres psql -U "$POSTGRES_USER" -d "$POSTGRES_DB" -c "ALTER USER $POSTGRES_USER WITH PASSWORD '$POSTGRES_PASSWORD';" > /dev/null 2>&1 || echo "   [!] LƯU Ý: Không thể tự động cập nhật mật khẩu Database nội bộ. Có thể bạn cần làm thủ công."
    fi
fi
