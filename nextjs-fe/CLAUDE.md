# nextjs-fe (admin dashboard)

Client-rendered admin panel for the Laravel API. Conventions: `.claude/rules/frontend-nextjs.md` (auto-loaded for `src/**`).

## Commands

```bash
docker exec ml-nextjs pnpm lint            # eslint (flat config, eslint-config-next + prettier)
docker exec ml-nextjs pnpm typecheck
docker exec ml-nextjs pnpm test --run      # Vitest 4 (jsdom)
docker exec ml-nextjs pnpm format:check    # Prettier (.prettierrc.json, same as nextjs-docs)
```

## Layout

- `src/app/admin/<entity>/page.tsx` — one `'use client'` page per entity: list + filters + dialog form. Routes are in `ADMIN_ROUTES` (`src/shared/config`).
- `src/shared/api` — axios client (`client/`, Sanctum session cookie + XSRF header; a 401 calls the handler the auth provider registers with `apiClient.onUnauthorized`) and `API_ENDPOINTS`. Error toasts are shown by callers via `getApiErrorMessage` (`src/shared/utils/error-handler.ts`).
- `src/shared/hooks` — `useApiData` (list query), `useCrud` (mutations), `useActionLock`. Query keys come from `queryKeys` (`src/shared/api/query-keys.ts`).
- `src/shared/services` — service classes per module (`modules/auth.service.ts`: CSRF cookie → login, logout, me).
- `src/proxy.ts` — route guard: `/admin/*` without the Laravel session cookie redirects to `/login?redirect=…` (presence check only; the API validates the session).
- `src/shared/types/openapi.d.ts` — **generated** from `laravel-api/openapi.json` (`make openapi` regenerates spec + types; CI fails when stale). Never edit it; `types/models/*` alias its Resource schemas (`AdminMst`, `RoleMst`, `HistoryRecord`, …).
- `src/shared/types`, `src/shared/enums`, `src/shared/config` — UI types, enums mirrored from the backend, constants.
- `src/components/ui` — shadcn primitives (generated; edit sparingly). `src/components/common` reusable widgets, `src/components/layout`. Feature code lives in `src/features/{history,ledger,master}`.
- i18n: `messages/en.json` via next-intl; every user-facing string goes through `useTranslations`.

## API contract

Responses are wrapped: `{ data, error: { status, code, messages } }`. Read server errors from `error.messages` (string or field map), never `response.data.message`. Lists are Laravel paginators inside `data`.
