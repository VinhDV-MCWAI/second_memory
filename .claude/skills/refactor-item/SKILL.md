---
name: refactor-item
description: Execute one approved item (or group) from .claude/refactor/PLAN.md end to end — branch, implement behavior-preserving changes, verify, update the plan status. Use when the user says "làm mục X", "do item A1", "start phase P0", or similar.
---

# Refactor item workflow

Arguments: item IDs or a phase, e.g. `A1 A4` or `P0`.

1. **Load context.** Read `.claude/refactor/PLAN.md`. Confirm each requested item has status `approved` (or the user approved it in this conversation). If an item is `proposed`, ask before starting. Check `depends_on` items are `done`.
2. **Branch.** From an up-to-date `developer`: `refactor/<id>-<short-slug>` (one branch per item, or per phase if the user asked for a phase). Never work on `main`.
3. **Baseline.** Run `/verify` for the affected area *before* changing anything so pre-existing failures are known.
4. **Implement.** Follow the item's "Done when" criteria and `.claude/rules/*`. Behavior-preserving unless the item says otherwise. Keep the HTTP contract unless the item changes it — then update both FEs in the same branch. Bugs found along the way → separate commit, mentioned in the summary.
5. **Verify.** Run `/verify` again. Add/adjust tests that prove the item's goal.
6. **Update plan.** Set the item's status to `done` (or `blocked: <reason>`), add the branch name and a one-line note.
7. **Summarize** for the user: what changed, verification results, follow-ups, anything they must do by hand (rotate secrets, update GitHub settings, etc.). Commit only if the user asked; use Conventional Commits.
