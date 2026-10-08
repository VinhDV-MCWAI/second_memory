# nextjs-docs (public docs site)

Since RFC-001 slice 5 the CMS content is gone: every `/docs` URL renders a static "content moved" page (`src/components/content-moved.tsx`, HTTP 200) until this app becomes the portfolio site. It no longer calls the API. Conventions: `.claude/rules/frontend-nextjs.md`.

- `src/app/docs/page.tsx` and `src/app/docs/[...slug]/page.tsx` — server components; the catch-all keeps old category/entry links from returning 404.
- Port 3457. Container `ml-nextjs-docs`. nginx serves it under `/docs` (`assetPrefix: '/docs'`).
- Commands: `docker exec ml-nextjs-docs pnpm lint` (eslint; `next lint` no longer exists), `pnpm typecheck`, `pnpm format:check`. ESLint/Prettier configs are identical to `nextjs-fe` — each dev container only mounts its own app, so they are not shared files.
