---
name: lab-task
description: Execute one task (or a whole phase) from the Engineering Lab backlog docs/plan/03-backlog.md end to end — branch, implement, verify, document, update backlog status and the handoff log. Use when the user says "làm task P2-03", "do P1", "start phase P2", "tiếp tục", or similar.
---

# Engineering Lab task workflow

Arguments: task IDs or a phase, e.g. `P2-03 P2-04` or `P2`.

1. **Load context.** Read `.claude/lab/PROGRESS.md` (handoff), then `docs/plan/03-backlog.md` and the phase section in `docs/plan/02-roadmap.md`. Check that earlier tasks the requested one depends on are `done`. A phase whose tasks are still coarse starts with its `Px-00` refinement task.
2. **Branch.** From `developer` (or the current phase branch if it is not merged yet): one branch per phase, named `<type>/<phase>-<slug>` with type `feature`, `refactor`, `chore`, `docs` or `fix` (e.g. `refactor/p2-slim-down`). Never work on `main`.
3. **Baseline.** For code tasks run `/verify` for the affected area first so pre-existing failures are known.
4. **Implement.** Follow `.claude/rules/*` and the handbook (`docs/handbook/`). Removal and feature work are allowed when the task says so (this is not a behavior-preserving refactor), but keep each commit to one purpose. Bugs found along the way → separate `fix` commit.
5. **Document.** Write the records the task names (REQ, RFC, ADR, PRB, INC, runbook) from `docs/templates/`, bilingual per `docs/README.md` (English, then `> 🇻🇳` Vietnamese lines). IDs are permanent.
6. **Verify.** Run `/verify` again and report real results.
7. **Update.** Set the task status in `docs/plan/03-backlog.md` (`done` / `cut: <reason>`), append to the log and "Next step" in `.claude/lab/PROGRESS.md`.
8. **Commit** locally with Conventional Commits (no AI trailer). Never push; list what the owner must do by hand (push, PRs, GitHub settings, secrets).
