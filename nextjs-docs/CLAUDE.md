# nextjs-docs (public docs site)

Since RFC-001 slice 5 the CMS content is gone: every `/docs` URL renders a static "content moved" page (`src/components/content-moved.tsx`, HTTP 200). Since P3-12 it also serves the public Skill Ledger (RFC-002 §4.4, REQ-002 US-3). Conventions: `.claude/rules/frontend-nextjs.md`.

- `src/app/skills/page.tsx` (list by category) and `src/app/skills/[slug]/page.tsx` (level history dates + public evidence; private or missing skill → `notFound()`, same 404). Server components, `dynamic = 'force-dynamic'` so `next build` never calls the API.
- `src/lib/ledger-api.ts` — server-only client for `GET /api/public/skills[/{slug}]`; types mirror `PublicSkillResource` / `PublicSkillDetailResource` in `laravel-api/openapi.json` (update them together). Base URL `API_INTERNAL_URL`, default `http://ml-nginx:8080/api` (Compose network). Responses cached 60 s: the API rate-limits per IP and every request comes from this server.
- Through nginx the pages need a `/skills` location → this app (lane infra); directly: `http://localhost:3002/skills`.
- Production image: `output: 'standalone'` + `public/` (docker/nextjs/Dockerfile `--build-arg APP=nextjs-docs --target production`, run with `PORT=3457`).

- `src/app/docs/page.tsx` and `src/app/docs/[...slug]/page.tsx` — server components; the catch-all keeps old category/entry links from returning 404.
- Port 3457. Container `ml-nextjs-docs`. nginx serves it under `/docs` (`assetPrefix: '/docs'`).
- Commands: `docker exec ml-nextjs-docs pnpm lint` (eslint; `next lint` no longer exists), `pnpm typecheck`, `pnpm format:check`. ESLint/Prettier configs are identical to `nextjs-fe` — each dev container only mounts its own app, so they are not shared files.
