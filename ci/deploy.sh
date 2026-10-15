#!/usr/bin/env bash
# Local CD (CD-01, ADR-0016): check → build → deploy the prod-like demo with Terraform → browser test.
# Every step runs on committed code, so the demo is exactly the commit that passed the checks.
#
# Usage: ci/deploy.sh [ref]   (default: HEAD; `make cd`, wrapped in scripts/lane.sh run)
#   SKIP_CHECK=1  skip ci/check.sh (the commit already passed `make ci`)
#   SKIP_E2E=1    skip the Playwright journey after deploying
# Idempotent: images are rebuilt from the layer cache, Terraform only changes what differs.
set -euo pipefail

cd "$(dirname "$0")/.."

ref=${1:-HEAD}
env=prod-like
tag=$(git rev-parse --short "$ref")

step() {
  echo
  echo "######## $1"
}

if [ "${SKIP_CHECK:-0}" = 1 ]; then
  step "1/4 Checks: skipped (SKIP_CHECK=1)"
else
  step "1/4 Checks (ci/check.sh $tag)"
  ci/check.sh "$ref"
fi

step "2/4 Production images (ci/build.sh $tag)"
ci/build.sh "$ref"

step "3/4 Deploy $env with Terraform (tag $tag)"
TAG=$tag infra/tf.sh "$env" apply -auto-approve

if [ "${SKIP_E2E:-0}" = 1 ]; then
  step "4/4 Browser test: skipped (SKIP_E2E=1)"
else
  step "4/4 Browser test on $env (e2e/run.sh $env)"
  e2e/run.sh "$env"
fi

echo
echo "Deployed $tag: $(TAG=$tag infra/tf.sh "$env" output -raw url) (root / 12345678)"
