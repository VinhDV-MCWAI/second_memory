#!/bin/bash
set -e

# Second Memory Wrapper Script
# Automatically ensures .env is generated before building/running docker

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCKER_ENV="$ROOT_DIR/docker/.env"

echo "============================================"
echo "    SECOND MEMORY - STARTUP SCRIPT"
echo "============================================"

if [ ! -f "$DOCKER_ENV" ]; then
    echo ">> [INFO] docker/.env not found! Running setup-env.sh to generate all environment configurations..."
    bash "$ROOT_DIR/setup-env.sh"
else
    echo ">> [INFO] docker/.env found. Environment configurations are present."
fi

echo ">> [INFO] Starting Docker environment..."
cd "$ROOT_DIR/docker"
# Sử dụng --force-recreate để đảm bảo các thay đổi trong .env luôn được áp dụng
docker-compose up -d --build --force-recreate ml-php ml-reverb ml-queue ml-redis
docker-compose up -d

echo "============================================"
echo "    STARTUP COMMAND COMPLETED"
echo "============================================"
