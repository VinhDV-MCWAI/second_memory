#!/usr/bin/env bash
# Remove leftovers after a task: Docker garbage and generated test output.
# Never touches named volumes (dev data), running containers, or images a container still uses.
# Idempotent: a second run finds nothing to remove.
#
# Usage: scripts/clean.sh   (wrapped by `make clean`)
#   CACHE_KEEP=168h   build cache newer than this is kept so the next build stays fast
set -euo pipefail

cd "$(dirname "$0")/.."

cache_keep=${CACHE_KEEP:-168h}
production_images='^(sm-api|sm-nextjs-fe|sm-nextjs-docs|sm-minio):'

echo ">> Images without a tag"
docker image prune -f | tail -1

echo ">> Old production image tags (kept: the ones a container uses)"
in_use=$(docker ps -a --format '{{.Image}}' | sort -u)
stale=$(docker images --format '{{.Repository}}:{{.Tag}}' | grep -E "$production_images" | grep -vxF "$in_use" || true)
if [ -n "$stale" ]; then
  echo "$stale" | xargs docker rmi >/dev/null
  echo "removed $(echo "$stale" | wc -l) tag(s)"
else
  echo "none"
fi

echo ">> Anonymous volumes no container uses (named volumes are kept)"
docker volume prune -f | tail -1

echo ">> Build cache older than $cache_keep"
docker builder prune -f --filter "until=$cache_keep" | tail -1

echo ">> Generated test output"
rm -rf e2e/artifacts laravel-api/.phpunit.result.cache
echo "done"

docker system df
