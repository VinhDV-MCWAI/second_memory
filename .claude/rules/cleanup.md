# Clean up after every task

A task is not done until its leftovers are gone. Full guide and the checklist: [docs/runbooks/don-dep.md](../../docs/runbooks/don-dep.md).

## While working
- Name every record you create by hand on dev with the prefix `TMP ` (skills, evidence, roles, departments, folders; admin accounts `tmp_…`), so it is easy to find and remove. E2E records use `E2E ` and `e2e/run.sh` removes them.
- Throwaway containers: `docker run --rm`. Temporary scripts and outputs go in the scratchpad, never in the repo.
- Never test on the dev DB what a PHPUnit test on the `testing` DB can prove.

## Before `scripts/lane.sh done`
1. `scripts/lane.sh run make clean` (Docker garbage, unused pulled images, test output; keeps dev data).
2. Remove the `TMP ` records and test files you created: through the admin UI, or the app's own services (Files: delete → trash → delete forever, so MinIO drops the object too). Never raw `DELETE` on `media_mgmt` (the MinIO object stays behind).
3. Stop and remove containers or environments you started for the task.
4. Delete dead code, commented-out code, `.bak` files, debug output.
5. Fix docs that mention anything you removed or renamed.
6. Say in the task note what you cleaned, and what you left on purpose.

## Never without the owner's explicit yes
- Named volumes (`ml_postgres_data`, `ml_minio_data`, `ml_redis_data`), `make fresh`, `migrate:rollback` on the dev DB, `make tf-destroy`.
- Data that was not created during your task, `make seed` sample data, the owner's real files.
