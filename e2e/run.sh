#!/usr/bin/env bash
# Critical-journey E2E (P3-15) against the running dev stack: `make e2e`.
#
# The journey writes to the dev database, so the script keeps that small and reversible:
# - it signs in as a dedicated owner `e2e_owner` with a fresh random password each run
#   (the account is disabled again afterwards);
# - every record the spec creates is named `E2E …` and is deleted afterwards together with the
#   account's audit_log rows, also when the run fails or a previous run was interrupted.
# Report and traces of a failed run: e2e/artifacts/ (not committed).
# shellcheck disable=SC2016  # the single-quoted PHP for tinker is expanded by PHP, not bash
set -euo pipefail

cd "$(dirname "$0")/.."

IMAGE=ml-e2e
E2E_USER=e2e_owner
E2E_PASSWORD="$(head -c 18 /dev/urandom | base64 | tr -d '/+=')"

artisan_tinker() {
  docker exec -e E2E_USER="$E2E_USER" -e E2E_PASSWORD="$E2E_PASSWORD" ml-php php artisan tinker --execute "$1"
}

# shellcheck disable=SC2317  # called by the EXIT trap
cleanup() {
  echo "==> Removing E2E records and disabling $E2E_USER"
  artisan_tinker '
    $skills = DB::table("skill")->where("name", "like", "E2E %")->pluck("id");
    $evidence = DB::table("evidence")->where("title", "like", "E2E %")->pluck("id");
    DB::table("evidence")->whereIn("id", $evidence)->delete();
    DB::table("skill")->whereIn("id", $skills)->delete();
    $admin = DB::table("admin_mst")->where("user_name", getenv("E2E_USER"));
    $audit = DB::table("audit_log")->where("admin_mst_id", $admin->value("id"))->delete();
    $admin->update(["is_active" => false]);
    echo "deleted skills={$skills->count()} evidence={$evidence->count()} audit_log={$audit}\n";
  '
}
trap cleanup EXIT

echo "==> Preparing the $E2E_USER owner account"
artisan_tinker '
  App\Models\Master\AdminMst::updateOrCreate(
    ["user_name" => getenv("E2E_USER")],
    [
      "email" => getenv("E2E_USER")."@e2e.invalid",
      "password" => Hash::make(getenv("E2E_PASSWORD")),
      "first_name" => "E2E",
      "last_name" => "Runner",
      "status" => 1,
      "is_active" => true,
      "is_delete" => false,
      "role" => App\Enums\AdminRole::OWNER,
    ],
  );
  echo "ok\n";
'

echo "==> Building the Playwright image"
docker build -q -f e2e/Dockerfile -t "$IMAGE" . >/dev/null

mkdir -p e2e/artifacts
echo "==> Running the journey"
docker run --rm --network ml_network --user "$(id -u):$(id -g)" -e HOME=/tmp \
  -e E2E_USER="$E2E_USER" -e E2E_PASSWORD="$E2E_PASSWORD" \
  -v "$PWD/e2e/artifacts:/repo/e2e/artifacts" \
  "$IMAGE"
