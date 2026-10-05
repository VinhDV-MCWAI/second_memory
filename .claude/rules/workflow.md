# Working agreement for Claude in this repo

## Before changing code
- Read the nearest `CLAUDE.md` and the existing code around the change; match its patterns unless the refactor plan replaces them.
- For anything beyond a small fix, state the plan briefly first.

## While changing code
- Keep the HTTP contract stable (routes, envelope, cookie auth) unless the approved plan item says otherwise. If a backend contract changes, update `nextjs-fe` and `nextjs-docs` in the same change.
- Refactors are behavior-preserving: no feature changes mixed in. If you find a bug, fix it in a separate commit and call it out.
- Delete dead code instead of commenting it out. No `.bak` files.
- Don't add dependencies without saying why; prefer what the stack already has.

## Verifying
- Run `/verify` (or the relevant subset) and report actual results. Never claim tests pass without running them.
- If the Docker stack isn't running, say so instead of skipping silently.

## Git
- Branch per plan item/group: `refactor/<id>-<slug>` from `developer`.
- Conventional Commits: `refactor(api): ...`, `fix(fe): ...`, `chore(ci): ...`, `build(docker): ...`.
- Small, reviewable commits. Commit/push only when the user asks.
