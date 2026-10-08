# Parallel lanes: several conversations, one branch, one working tree

The owner runs several Claude conversations at once on the same branch and directory. Work is split into **lanes** on [.claude/lab/BOARD.md](../lab/BOARD.md); each lane owns paths, and only one conversation works a lane at a time. Start with the `/lane` skill.

## Claiming and finishing
- Take work only through `scripts/lane.sh claim <id>`; finish with `scripts/lane.sh done <id> "<commit> + one-line result"` (it mirrors `03-backlog.md`, appends to the PROGRESS log and commits those files). Never edit the board's Status column, a backlog Status cell, or the PROGRESS log by hand.
- Stuck on something outside your lane: `scripts/lane.sh block <id> "<reason>"`, and add the needed work to the owning lane with `scripts/lane.sh add <lane> <id> …`.
- New work found (a bug, a follow-up): `scripts/lane.sh add` in the lane that owns the code. Don't do it yourself if it's outside your paths.

## Files
- Edit only the paths your lane owns (listed on the board) plus the docs your task names.
- Shared files have no owner: `Makefile`, `docker/docker-compose.yml`, `.gitignore`, `package.json`, `pnpm-lock.yaml`, `pnpm-workspace.yaml`, `CLAUDE.md`, `docs/README.md`, `docs/plan/*`, `.claude/lab/PROGRESS.md` (outside the log). To change one: `scripts/lane.sh lock <path>` → small edit → `scripts/lane.sh commit "<msg>" <path>` → `scripts/lane.sh unlock <path>`. Keep the lock for minutes, not the whole task.
- Uncommitted changes you didn't make belong to another lane: don't edit, format, stage, revert or delete them, even if they break your build.

## Git
- Commit with `scripts/lane.sh commit "<conventional message>" <your paths...>` (stages and commits only those paths). Never `git add -A` / `.` / `-u`, `git commit -a`, `git stash`, `git checkout -- .`, `git restore`, `git reset --hard`, `git clean`, rebase, amend, or switching branches — they touch other lanes' work.
- Small commits, one purpose each; no push (owner pushes).

## Docker, tests, databases
- Wrap every Docker-backed check in `scripts/lane.sh run …` (e.g. `scripts/lane.sh run make test f=EvidenceApiTest`, `scripts/lane.sh run make verify`). It queues lanes so two test runs never share the `testing` DB at once.
- Run the checks for your own area. A full `make verify` may fail on another lane's unfinished work: report it in your task note, don't fix it.
- `make restart`, `make down`, `make fresh`, `make setup`, migrations on the dev DB and container rebuilds belong to lane `infra` / `api` and only while no other lane is running checks (`run` holds the lock).
