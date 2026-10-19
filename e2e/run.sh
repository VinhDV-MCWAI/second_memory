#!/usr/bin/env bash
# Critical-journey E2E (P3-15) against a running environment.
#   e2e/run.sh            the dev stack (`make e2e`)
#   e2e/run.sh prod-like  the Terraform demo (`make cd` runs it after deploying, CD-01)
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

case "${1:-dev}" in
  dev)
    PHP=ml-php NETWORK=ml_network ADMIN_URL=http://localhost:81 UPSTREAM=ml-nginx:8080
    ;;
  prod-like)
    # Names from infra/modules/stack; the proxy port from infra/live/prod-like/stack/terraform.tfvars
    PHP=sm-prod-like-php NETWORK=sm-prod-like ADMIN_URL=http://localhost:9443 UPSTREAM=sm-prod-like-proxy:8080
    ;;
  *)
    echo "usage: e2e/run.sh [dev|prod-like]" >&2
    exit 2
    ;;
esac

IMAGE=ml-e2e
E2E_USER=e2e_owner
E2E_PASSWORD="$(head -c 18 /dev/urandom | base64 | tr -d '/+=')"

# Admins without the owner role for departments.spec.ts (ADR-0017): one in the department a
# folder is locked to, one outside it. Same password as $E2E_USER, disabled again afterwards.
E2E_HELPERS=e2e_member,e2e_outsider

artisan_tinker() {
  docker exec -e E2E_USER="$E2E_USER" -e E2E_PASSWORD="$E2E_PASSWORD" -e E2E_HELPERS="$E2E_HELPERS" "$PHP" php artisan tinker --execute "$1"
}

# shellcheck disable=SC2317  # called by the EXIT trap
cleanup() {
  echo "==> Removing E2E records and disabling $E2E_USER"
  artisan_tinker '
    $skills = DB::table("skill")->where("name", "like", "E2E %")->pluck("id");
    $evidence = DB::table("evidence")->where("title", "like", "E2E %")->pluck("id");
    DB::table("evidence")->whereIn("id", $evidence)->delete();
    DB::table("skill")->whereIn("id", $skills)->delete();
    // Model deletes, so the permission package clears its cache
    $roles = Spatie\Permission\Models\Role::where("name", "like", "E2E %")->get()->each->delete();
    $admin = DB::table("admin_mst")->whereIn("user_name", [getenv("E2E_USER"), ...explode(",", getenv("E2E_HELPERS"))]);
    $audit = DB::table("audit_log")->whereIn("admin_mst_id", $admin->pluck("id"))->delete();
    // Departments of departments.spec.ts; their folder locks and memberships go with them
    $departments = DB::table("department")->where("name", "like", "E2E %")->delete();
    // Files of files.spec.ts: drop the MinIO objects (or unfinished parts), then hide the rows
    $media = App\Models\Media\MediaMgmt::query()->live()->where("virtual_path", "like", "/E2E %")->get();
    $media->each(fn ($row) => app(App\Services\Media\MediaService::class)->discardObject($row));
    App\Models\Media\MediaMgmt::query()->whereKey($media->modelKeys())->update(["is_delete" => true]);
    // ...and nothing of them stays in the trash: every version goes, then the rows
    $trash = App\Models\Media\MediaMgmt::query()->where("is_delete", true)->where("virtual_path", "like", "/E2E %")->get();
    $trash->filter(fn ($row) => $row->minio_object_key !== null)->each(fn ($row) => app(App\Services\Media\ObjectStorage::class)->purge($row->minio_object_key));
    // Thumbnails made by ffmpeg (MEDIA-11)
    $trash->map(fn ($row) => App\Services\Media\MediaInfoService::thumbnailKey($row))->filter()->each(fn ($key) => app(App\Services\Media\ObjectStorage::class)->purge($key));
    App\Models\Media\MediaMgmt::query()->whereKey($trash->modelKeys())->delete();
    // The failing test background job (background-jobs.spec.ts) is kept as a failed job
    $failed = DB::table("failed_jobs")->where("payload", "like", "%TestBackgroundJob%")->delete();
    // End users of end-users.spec.ts (their saved items go with them) and the audit rows about them
    $endUsers = DB::table("end_user")->where("username", "like", "e2e-%")->pluck("id");
    $audit += DB::table("audit_log")->where("auditable_type", "end_user")->whereIn("auditable_id", $endUsers)->delete();
    DB::table("end_user")->whereIn("id", $endUsers)->delete();
    $admin->update(["is_active" => false]);
    echo "deleted skills={$skills->count()} evidence={$evidence->count()} roles={$roles->count()} departments={$departments} end_users={$endUsers->count()} audit_log={$audit} failed_jobs={$failed} media={$media->count()} trash={$trash->count()}\n";
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
    ],
  )->syncRoles([App\Enums\AdminRole::OWNER->value]);
  // Folder locks (departments.spec.ts): a role that may browse Files, and a department per helper
  $role = Spatie\Permission\Models\Role::findOrCreate("E2E Files reader", "admin");
  $role->syncPermissions(["media.list"]);
  foreach (explode(",", getenv("E2E_HELPERS")) as $name) {
    $helper = App\Models\Master\AdminMst::updateOrCreate(
      ["user_name" => $name],
      [
        "email" => $name."@e2e.invalid",
        "password" => Hash::make(getenv("E2E_PASSWORD")),
        "first_name" => "E2E",
        "last_name" => $name,
        "status" => 1,
        "is_active" => true,
        "is_delete" => false,
      ],
    );
    $helper->syncRoles([$role]);
    $department = App\Models\Master\Department::updateOrCreate(["code" => strtoupper($name)], ["name" => "E2E ".$name, "is_active" => true]);
    $helper->departments()->sync([$department->id]);
  }
  echo "ok\n";
'

echo "==> Building the Playwright image"
docker build -q -f e2e/Dockerfile -t "$IMAGE" . >/dev/null

mkdir -p e2e/artifacts
echo "==> Running the journey"
docker run --rm --network "$NETWORK" --user "$(id -u):$(id -g)" -e HOME=/tmp \
  -e E2E_USER="$E2E_USER" -e E2E_PASSWORD="$E2E_PASSWORD" -e E2E_HELPERS="$E2E_HELPERS" \
  -e E2E_ADMIN_URL="$ADMIN_URL" -e E2E_ADMIN_UPSTREAM="$UPSTREAM" \
  -v "$PWD/e2e/artifacts:/repo/e2e/artifacts" \
  "$IMAGE"
