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
- `src/shared/api` — axios client (`client/`, cookie auth, refresh-token interceptor) and `API_ENDPOINTS`. Error toasts are shown by callers via `getApiErrorMessage` (`src/shared/utils/error-handler.ts`).
- `src/shared/hooks` — `useApiData` (list query), `useCrud` (mutations), `useJunctionTable`, `useHistory`, `useFileManager`, `useActionLock`.
- `src/shared/services` — service classes per module + factories; `multipart-uploader.ts` for MinIO multipart upload.
- `src/shared/types`, `src/shared/enums`, `src/shared/config` — types, enums mirrored from the backend, constants.
- `src/components/ui` — shadcn primitives (generated; edit sparingly). `common/` reusable widgets, `forms/` entity forms, `features/` editor/history/auth, `layout/`.
- i18n: `messages/en.json` via next-intl; every user-facing string goes through `useTranslations`.

## API contract

Responses are wrapped: `{ data, error: { status, code, messages } }`. Read server errors from `error.messages` (string or field map), never `response.data.message`. Lists are Laravel paginators inside `data`.
