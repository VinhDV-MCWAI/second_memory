# Working agreement for Claude in this repo

## Before changing code
- Read the nearest `CLAUDE.md` and the existing code around the change; match its patterns.
- Anything beyond a small fix goes through the proposal step in `docs/plan/01-goals-and-workflow.md` first (the `/lab-task` skill).

## While changing code
- Keep the HTTP contract stable (routes, envelope, cookie auth) unless the approved plan item says otherwise. If a backend contract changes, update `nextjs-fe` and `public-site` in the same change (`make openapi` regenerates both type files).
- Refactors are behavior-preserving: no feature changes mixed in. If you find a bug, fix it in a separate commit and call it out.
- Delete dead code instead of commenting it out. No `.bak` files.
- Don't add dependencies without saying why; prefer what the stack already has.

## Verifying
- Run `/verify` (or the relevant subset) and report actual results. Never claim tests pass without running them.
- If the Docker stack isn't running, say so instead of skipping silently.

## Git
- One branch for all tasks (currently `feature/p3-ledger-api`): no branch per task, never switch branches. The owner pushes and opens pull requests.
- Conventional Commits: `feat(api): ...`, `fix(fe): ...`, `docs: ...`, `build(docker): ...`.
- No `Co-Authored-By` (or any AI attribution) trailer in commit messages. `.claude/settings.json` sets `attribution.commit` to empty; a local `commit-msg` hook strips it too.
- Small, reviewable commits through `scripts/lane.sh commit` ([parallel-lanes.md](parallel-lanes.md)). Never push.
