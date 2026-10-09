#!/usr/bin/env bash
# Run Terraform for one environment in the pinned tools image (infra/Dockerfile); nothing on the host.
#
# Usage: infra/tf.sh <env> <plan|apply|destroy|output|validate|fmt|init> [terraform args...]
#   TAG=<image tag>   images to run (default: short SHA of HEAD; build them with infra/build-images.sh)
# Wrapped by `make tf-plan|tf-apply|tf-destroy env=…`, which also goes through scripts/lane.sh run.
# Idempotent: init runs every time (no-op when providers are installed), apply converges.
set -euo pipefail

cd "$(dirname "$0")/.."

env=${1:?usage: infra/tf.sh <env> <command> [args...]}
command=${2:?usage: infra/tf.sh <env> <command> [args...]}
shift 2
root="infra/live/$env/stack"
[ -d "$root" ] || { echo "no environment '$env' ($root)" >&2; exit 1; }

image="sm-tools:$(git hash-object infra/Dockerfile | cut -c1-12)"
docker image inspect "$image" >/dev/null 2>&1 || docker build -q -t "$image" infra >/dev/null

socket=/var/run/docker.sock
socket_gid=$(docker run --rm -v "$socket:$socket" --entrypoint stat "$image" -c %g "$socket")

# A terminal gets a TTY so apply / destroy can ask for confirmation
tty_flag=()
[ -t 0 ] && [ -t 1 ] && tty_flag=(-t)

tf() {
  docker run --rm -i "${tty_flag[@]}" \
    --user "$(id -u):$(id -g)" --group-add "$socket_gid" \
    -v "$socket:$socket" -v "$PWD:/work" -w /work \
    -e TF_VAR_image_tag="${TAG:-$(git rev-parse --short HEAD)}" \
    "$image" -chdir="$root" "$@"
}

case "$command" in
  fmt) tf fmt -recursive "$@" /work/infra ;;
  init) tf init -input=false "$@" ;;
  plan | apply | destroy)
    tf init -input=false >/dev/null
    tf "$command" -input=false "$@"
    ;;
  *)
    tf init -input=false >/dev/null
    tf "$command" "$@"
    ;;
esac

# State holds secrets in plain text (ADR-0011): owner-only
find "$root" -maxdepth 1 -name 'terraform.tfstate*' -exec chmod 600 {} +
