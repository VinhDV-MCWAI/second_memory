---
name: lab-task
description: Do the next task (or a given task ID) from the plan in docs/plan/ — claim it on the board with scripts/lane.sh, propose when the task needs the owner's confirmation, implement on the dev environment, quick-test, document in Vietnamese, commit only your own paths and mark it done. Use when the user says "làm việc tiếp", "tiếp tục", "nhận việc", "lấy task tiếp", "làm NEN-07", "/lane", or resumes a task.
---

# Task workflow (plan since 2026-10-09)

Arguments: nothing (next claimable task), a task ID such as `NEN-07`, or `resume <id>`.

All work stays on the current branch; the owner pushes. Board, locks and commits follow [.claude/rules/parallel-lanes.md](../../rules/parallel-lanes.md), so a second conversation can run at the same time without clashing.

1. **Load context.** `.claude/lab/PROGRESS.md`, `docs/plan/01-goals-and-workflow.md`, the task's row in `docs/plan/03-backlog.md` and its phase in `docs/plan/02-roadmap.md`, `docs/dev-guide.md`, the matching `docs/features/` file and the nearest `CLAUDE.md` for the code you will touch. Check that the dependencies' results are really there.
2. **Claim.** `scripts/lane.sh status`, then `scripts/lane.sh claim <id>` (the first claimable task when no ID was given; if the claim fails, take the next one). Nothing claimable → tell the owner what is blocked on what and stop.
   - `resume <id>` or a task already `doing` from an ended session: read the last PROGRESS log lines and `git status`, then continue without claiming again.
3. **Propose when required.** If the task is marked **[XÁC NHẬN]**, or it would remove / replace a feature, delete or move files or folders, add a dependency, or change auth, permissions or the DB structure: write the proposal using the template in `01-goals-and-workflow.md` (plain Vietnamese, benefits and harms against the owner's goals, options with a recommendation, concrete questions) and **stop**. Continue only after the owner's explicit answer.
4. **Implement** on the dev environment. Follow `.claude/rules/*`. Shared files (list in `parallel-lanes.md`) → `scripts/lane.sh lock` / `commit` / `unlock`. Uncommitted changes you did not make belong to someone else: leave them alone. One purpose per commit; a bug found on the way gets its own `fix` commit. Code from tag `v1.0.0` is reference material, rewritten to fit the current base.
5. **Quick test.** Run the checks from "Test nhanh" in `01-goals-and-workflow.md` for what you changed, wrapped in `scripts/lane.sh run` (`make test f=<Name>`, `make fe-test`, `make openapi` when routes or payloads changed). The stack is down → `make up` first, and say so. Report real results, never claim a pass you did not run.
6. **Document in Vietnamese**, plain language: the feature's file in `docs/features/`, `docs/dev-guide.md` for new commands or new errors and fixes, an ADR for a real decision.
7. **Commit** with `scripts/lane.sh commit "<type>(<scope>): <summary>" <paths...>` (Conventional Commits, no AI trailer, never `git add -A`, never push).
8. **Mark done.** `scripts/lane.sh done <id> "<short sha> — <one-line result>"` (updates the board, the backlog and the PROGRESS log, and commits them). Stuck → `scripts/lane.sh block <id> "<reason>"`; new work found → `scripts/lane.sh add main <id> …`.
9. **Report** to the owner in Vietnamese: what changed, what was tested and how, what to try in the browser (http://localhost:81, `root` / `12345678`), what they must do by hand (push), and the next task.
