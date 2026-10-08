---
name: lane
description: Work as one of several parallel conversations on the shared branch — pick a free lane on .claude/lab/BOARD.md, claim its next task, do it inside the lane's paths, document it, commit only your own files and mark it done. Use when the user says "/lane", "/lane api", "nhận việc", "làm việc song song", "lấy task tiếp", or resumes a lane.
---

# Lane workflow

Arguments: nothing (any free lane), a lane name (`api`, `fe`, `public`, `importer`, `perf`, `infra`, `docs`, `release`), a task ID, or `resume <id>`.

Read [.claude/rules/parallel-lanes.md](../../rules/parallel-lanes.md) first; it overrides `/lab-task` steps 2 and 7–8 (no new branch, no hand edits of backlog / PROGRESS, pathspec commits).

1. **Pick.** `scripts/lane.sh status`. With a task ID or lane, take that; otherwise take the first line of `scripts/lane.sh next`. `scripts/lane.sh claim <id>`. If the claim fails (another conversation got there first), run `next` again. Nothing claimable → tell the owner what is blocked on what and stop.
   - `resume <id>`: the task is `doing` from a conversation that ended. Read the lane's last PROGRESS log lines and `git status` for its paths, then continue without claiming again.
2. **Load context.** The task row's Docs links, the nearest `CLAUDE.md` for the lane's paths, `.claude/lab/PROGRESS.md` → "Environment gotchas", and the relevant `docs/handbook/` chapter. Check that the dependencies' results are really there.
3. **Work** only in the lane's owned paths (board) plus the records the task names. Shared files → `lock` / `commit` / `unlock`. Anything outside your lane → `scripts/lane.sh add <lane> …` or `block`. Follow `.claude/rules/*`.
4. **Check** with `scripts/lane.sh run <make target>` for your area (tests, lint, typecheck; `make verify` before `done` for code tasks). Report real results; if a failure belongs to another lane's uncommitted work, say so in the note.
5. **Document** what the task names (ADR, RFC section, runbook, report) — bilingual per `docs/README.md`.
6. **Commit** with `scripts/lane.sh commit "<type>(<scope>): <summary>" <paths...>`, one purpose per commit, no AI trailer.
7. **Done.** `scripts/lane.sh done <id> "<short sha> — <one-line result with numbers>"`. Long findings or gotchas: add them under "Environment gotchas" in PROGRESS.md with `lock` / `commit` / `unlock`.
8. **Next.** Tell the owner in Vietnamese what was done, what was verified and what the owner must do by hand. If the same lane has another claimable task, ask whether to continue with it.
