#!/usr/bin/env bash
# Build the production images the Terraform environments run (ADR-0011: Terraform never builds).
# The build context is `git archive <ref>`, so only committed code goes in: another lane's
# uncommitted work in the shared tree never ends up in an image.
#
# Usage: infra/build-images.sh [ref] [app...]   (default: HEAD, all three apps)
#   e.g. infra/build-images.sh HEAD api
# Tags: sm-<app>:<short sha of ref>. Idempotent: rebuilding a tag reuses the layer cache.
set -euo pipefail

cd "$(dirname "$0")/.."

ref=${1:-HEAD}
shift || true
apps=("$@")
[ ${#apps[@]} -gt 0 ] || apps=(api nextjs-fe nextjs-docs)
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
    nextjs-fe | nextjs-docs)
      docker build -q -t "sm-$app:$tag" --target production --build-arg "APP=$app" \
        -f "$src/docker/nextjs/Dockerfile" "$src"
      ;;
    *)
      echo "unknown app '$app' (api, nextjs-fe, nextjs-docs)" >&2
      exit 1
      ;;
  esac
done
