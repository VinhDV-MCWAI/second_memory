# Engineering Lab — progress / handoff

> Handoff log. Updated after every task so a new conversation can resume.
> To resume: read this file, then `docs/plan/03-backlog.md`, then continue at **Next step** with the `/lab-task` skill.

## State

| | |
|---|---|
| Active phase | P2 Slim down (P0, P1 done locally) |
| Working branch | `refactor/p2-slim-down` (from `docs/p1-handbook`); `docs/p1-handbook` (stacked on `chore/p0-baseline`, tag `v1.0.0`); `chore/p0-baseline` (from `refactor/fe6-features` ← `fix/security-deps` ← `developer`); local only, nothing pushed |
| Old refactor | Frozen (`.claude/refactor/PLAN.md`, `PROGRESS.md`) |
| Owner defaults | 8–10 h/week, backend role, §4 remove list accepted, Obsidian vault private (see analysis §9) |

## Needs the owner (Claude cannot do these)

1. Push `chore/p0-baseline`, open PR → `developer`, then `developer` → `main` (merging into `developer` deploys via `cd.yml`).
2. Push tag `v1.0.0` after the merge (`git push origin v1.0.0`) and create the GitHub release from `docs/releases/v1.0.0.md`.
3. GitHub board and labels (P0-06): see `docs/plan/github-setup.md`.
4. Remote branch cleanup (P0-09): commands in `.claude/refactor/PROGRESS.md` → "Branches".
5. Still open from the refactor: rotate secrets on any real deployment; browser check while logged in.

## Log

- 2026-10-07 — Plan written and accepted (`44b9c89`). P0-01: `fix/security-deps` + `refactor/fe6-features` gathered into `chore/p0-baseline`; the FE6 WIP tip typechecks, lint 0 errors, Vitest 106 ✓.

- 2026-10-07 — P0 done locally: as-is architecture, root docs archived, old plan frozen (open items mapped), `refactor-item` skill → `lab-task`, ADR-0002, GitHub setup guide, v1.0.0 release notes. `make verify` exit 0 (Pint ✓, Larastan ✓, backend 598 tests, FE lint 0 errors, tsc ✓, Vitest 106). Tag `v1.0.0` (local).

- 2026-10-07 — P1-01…P1-13 done: handbook (10 chapters), templates (REQ, RFC, INC, postmortem, runbook, daily, weekly, retro), GitHub issue forms + PR template + CODEOWNERS, skills `simulate-po` / `simulate-qa` / `simulate-ops` (sealed briefs in gitignored `.claude/sim/sealed/`).

- 2026-10-07 — P1-14 dry run: REQ-001 Ready (7 clarification questions; hidden needs surfaced: content export → new task P2-05b, keep MinIO files, "moved" page for /docs). Daily + weekly report, retro P0–P1, `v1.1.0` notes + local tag. Dev DB has no content/admin rows → export tested with factories.

- 2026-10-07 — P2-02…P2-05b done on `refactor/p2-slim-down`: metrics script (`7ab434a`), `content:export-markdown` command (`c785085`, RFC slice 1), RFC-001 with impact analysis, ERD, slices, before-metrics + ADR-0003 (`84278a7`).

- 2026-10-07 — RFC-001 slice 2 (remove sliders, banners, setting links, socials) **started, uncommitted, not verified**: API controllers/requests/resources/models/repos/services/factories/tests deleted, routes edited, migration `2026_10_07_100001_drop_site_decoration_tables.php`, `app/Support/`, `BulkDeleteHistoryTest` moved to `Master/AdminMst`; FE pages/forms deleted, navigation/endpoints/enums/types/validation/messages trimmed. Backlog P2-06/07/09 set to `doing`.

## Next step

Finish slice 2 from the working tree (owner has the implementation plan): review the WIP, run `make verify`, test the drop migration's `down()`, then commit as `refactor(api)` / `refactor(fe)` / drop-migration commits. Then slices 3–10 of RFC-001 §5.
