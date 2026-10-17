#!/usr/bin/env bash
# Build the production images (ADR-0011: Terraform never builds; ADR-0016: CI and `make tf-images` share this script).
# The build context is `git archive <ref>`, so only committed code goes in: another lane's
# uncommitted work in the shared tree never ends up in an image.
#
# Usage: ci/build.sh [ref] [app...]   (default: HEAD, every app: api nextjs-fe public-site minio)
#   e.g. ci/build.sh HEAD api
# Tags: sm-<app>:<short sha of ref>. Idempotent: rebuilding a tag reuses the layer cache.
set -euo pipefail

cd "$(dirname "$0")/.."

ref=${1:-HEAD}
shift || true
apps=("$@")
[ ${#apps[@]} -gt 0 ] || apps=(api nextjs-fe public-site minio)
tag=$(git rev-parse --short "$ref")

src=$(mktemp -d)
trap 'rm -rf "$src"' EXIT
git archive --format=tar "$ref" | tar -x -C "$src"

for app in "${apps[@]}"; do
  echo "==> sm-$app:$tag"
  case "$app" in
    api)
      # Context laravel-api/, as in compose and CI; docker/laravel/Dockerfile.dockerignore applies
      docker build -q -t "sm-api:$tag" --target production \
        -f "$src/docker/laravel/Dockerfile" "$src/laravel-api"
      ;;
    nextjs-fe)
      docker build -q -t "sm-nextjs-fe:$tag" --target production --build-arg APP=nextjs-fe \
        -f "$src/docker/nextjs/Dockerfile" "$src"
      ;;
    public-site)
      docker build -q -t "sm-public-site:$tag" --target production \
        -f "$src/docker/public-site/Dockerfile" "$src"
      ;;
    minio)
      # Pinned community MinIO running as an unprivileged user (docker/minio/Dockerfile)
      docker build -q -t "sm-minio:$tag" "$src/docker/minio"
      ;;
    *)
      echo "unknown app '$app' (api, nextjs-fe, public-site, minio)" >&2
      exit 1
      ;;
  esac
done
