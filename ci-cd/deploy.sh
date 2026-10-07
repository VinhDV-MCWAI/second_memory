#!/bin/bash
set -euo pipefail

# ============================================================
# Blue-Green Deployment Script for second_memory
# Triggered by GitHub Actions CD pipeline on the self-hosted runner
# ============================================================

# --- Configuration ---
readonly PROJECT_NAME="second_memory"
readonly WORK_DIR="$(cd "$(dirname "$0")/.." && pwd)"
readonly DOCKER_DIR="$WORK_DIR/docker"
readonly CI_CD_DIR="$WORK_DIR/ci-cd"
readonly NGINX_CONF_PATH="/etc/nginx/sites-available/$PROJECT_NAME"
readonly NGINX_ENABLED_PATH="/etc/nginx/sites-enabled/$PROJECT_NAME"

# Ports for each environment
readonly BLUE_PORT=8080
readonly GREEN_PORT=8081

# Health check settings
readonly HEALTH_CHECK_RETRIES=18   # 18 * 10s = 3 minutes max
readonly HEALTH_CHECK_INTERVAL=10

# --- Image config from environment (passed by CD pipeline) ---
# REPO_LOWER, REGISTRY, IMAGE_TAG are injected from the workflow env
: "${REGISTRY:?REGISTRY env var is required}"
: "${REPO_LOWER:?REPO_LOWER env var is required}"
: "${IMAGE_TAG:=latest}"

API_IMAGE="$REGISTRY/$REPO_LOWER/api:$IMAGE_TAG"
FE_IMAGE="$REGISTRY/$REPO_LOWER/fe:$IMAGE_TAG"

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $*"; }

# --- Determine current LIVE and TARGET environment ---
if [[ -f "$NGINX_CONF_PATH" ]] && grep -q "backend_blue" "$NGINX_CONF_PATH" 2>/dev/null; then
    LIVE_ENV="blue"
    TARGET_ENV="green"
    TARGET_PORT=$GREEN_PORT
else
    LIVE_ENV="green"
    TARGET_ENV="blue"
    TARGET_PORT=$BLUE_PORT
fi

log "Live environment  : $LIVE_ENV"
log "Target environment: $TARGET_ENV (port $TARGET_PORT)"
log "Deploying API image: $API_IMAGE"
log "Deploying FE image : $FE_IMAGE"

# --- 1. Export vars needed by docker-compose ---
export API_IMAGE
export FE_IMAGE
export APP_PORT=$TARGET_PORT

# --- 2. Start new (TARGET) environment ---
log "Starting $TARGET_ENV containers..."
docker compose \
  -f "$DOCKER_DIR/docker-compose.yml" \
  -p "${PROJECT_NAME}_${TARGET_ENV}" \
  up -d --pull always --remove-orphans

# --- 3. Health Check ---
log "Waiting for $TARGET_ENV health check (port $TARGET_PORT)..."
HEALTHY=false
for i in $(seq 1 $HEALTH_CHECK_RETRIES); do
    STATUS=$(curl -s -o /dev/null -w "%{http_code}" "http://localhost:$TARGET_PORT/health" 2>/dev/null || true)
    if [[ "$STATUS" == "200" ]]; then
        HEALTHY=true
        log "Health check passed on attempt $i."
        break
    fi
    log "Attempt $i/$HEALTH_CHECK_RETRIES — received HTTP $STATUS. Retrying in ${HEALTH_CHECK_INTERVAL}s..."
    sleep $HEALTH_CHECK_INTERVAL
done

if [[ "$HEALTHY" != "true" ]]; then
    log "ERROR: Health check failed after $HEALTH_CHECK_RETRIES attempts. Rolling back..."
    docker compose \
      -f "$DOCKER_DIR/docker-compose.yml" \
      -p "${PROJECT_NAME}_${TARGET_ENV}" \
      down --remove-orphans
    exit 1
fi

# --- 4. Switch Traffic (Nginx) ---
log "Switching Nginx traffic to $TARGET_ENV..."
sed "s/backend_ENV_TARGET/backend_$TARGET_ENV/g" \
    "$CI_CD_DIR/nginx-proxy.conf.template" \
    | sudo tee "$NGINX_CONF_PATH" > /dev/null

sudo ln -sf "$NGINX_CONF_PATH" "$NGINX_ENABLED_PATH"

if ! sudo nginx -t; then
    log "ERROR: Nginx config test failed. Aborting traffic switch."
    exit 1
fi

sudo systemctl reload nginx
log "Traffic switched to $TARGET_ENV."

# --- 5. Cleanup OLD (LIVE) environment ---
log "Cleaning up old $LIVE_ENV environment..."
docker compose \
  -f "$DOCKER_DIR/docker-compose.yml" \
  -p "${PROJECT_NAME}_${LIVE_ENV}" \
  down --remove-orphans

# --- 6. Optional: prune unused images to save disk space ---
docker image prune -f --filter "until=24h"

log "Deployment to $TARGET_ENV completed successfully!"
log "Image tag deployed: $IMAGE_TAG"
