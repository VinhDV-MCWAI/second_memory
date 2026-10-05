# nextjs-docs (public docs site)

Read-only site rendering categories and entries from the public API (`/api/docs/*`, no auth). Conventions: `.claude/rules/frontend-nextjs.md`.

- `src/app/docs/**` — server components fetching via `src/lib/api.ts` (`fetchApi`).
- `src/components/content-renderer.tsx` + `components/extensions/` — render Tiptap JSON; keep extensions in sync with the editor in `nextjs-fe/src/components/features/editor`.
- `src/types/docs.ts` — response types.
- Server-side fetches go through the internal nginx container; browser fetches use `NEXT_PUBLIC_API_URL`.
- `src/lib/layout-structure.ts` — `layout_structure` type + parser shared by the docs pages/components.
- Port 3457. Container `ml-nextjs-docs`.
- Commands: `docker exec ml-nextjs-docs pnpm lint` (eslint; `next lint` no longer exists), `pnpm typecheck`, `pnpm format:check`. ESLint/Prettier configs are identical to `nextjs-fe` — each dev container only mounts its own app, so they are not shared files.
