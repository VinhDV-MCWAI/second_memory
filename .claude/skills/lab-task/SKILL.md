---
name: lab-task
description: Do the next task (or a given task ID) from the plan in docs/plan/ — claim it on the board, propose when the task needs the owner's confirmation, implement on the dev environment, quick-test, document in Vietnamese, commit and mark it done. Use when the user says "làm việc tiếp", "tiếp tục", "làm NEN-03", "nhận việc", or similar.
---

# Task workflow (plan since 2026-10-09)

Arguments: nothing (next claimable task) or a task ID such as `NEN-03`.

1. **Load context.** Read `.claude/lab/PROGRESS.md`, `docs/plan/01-goals-and-workflow.md`, the task's row in `docs/plan/03-backlog.md` and its phase in `docs/plan/02-roadmap.md`, `docs/dev-guide.md`, and the nearest `CLAUDE.md` for the code you will touch.
2. **Claim.** `scripts/lane.sh status`, then `scripts/lane.sh claim <id>` (the first claimable task when no ID was given). A task already `doing` from an ended session: continue it without claiming again.
3. **Propose when required.** If the task is marked **[XÁC NHẬN]**, or it would remove / replace a feature, delete or move folders, add a dependency, or change auth, permissions or the DB structure: write the proposal using the template in `01-goals-and-workflow.md` (plain Vietnamese, benefits and harms against the owner's goals, options with a recommendation, concrete questions) and **stop**. Continue only after the owner's explicit answer. Never use simulated stakeholder skills to decide.
4. **Implement** on the dev environment. Follow `.claude/rules/*`. One purpose per commit; a bug found on the way gets its own `fix` commit. Code from tag `v1.0.0` is reference material, rewritten to fit the current base.
5. **Quick test.** Run the checks from "Test nhanh" in `01-goals-and-workflow.md` for what you changed (`scripts/lane.sh run make test f=<Name>`, `make fe-test`, `make openapi` when routes or payloads changed). Tell the owner what to try in the browser. Report real results, never claim a pass you did not run.
6. **Document in Vietnamese**, plain language: the feature's file in `docs/features/`, `docs/dev-guide.md` for new commands or new errors and fixes, an ADR for a real decision.
7. **Commit** with `scripts/lane.sh commit "<type>(<scope>): <summary>" <paths...>` (Conventional Commits, no AI trailer, never `git add -A`, never push).
8. **Mark done.** `scripts/lane.sh done <id> "<short sha> — <one-line result>"` (updates the board, the backlog and the PROGRESS log, and commits them).
9. **Report** to the owner in Vietnamese: what changed, what was tested and how, what they should check in the browser, what they must do by hand (push, PR).
