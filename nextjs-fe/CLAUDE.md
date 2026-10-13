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
- `src/components/ui` — shadcn primitives (generated; edit sparingly). `src/components/common` reusable widgets, `src/components/layout`. Feature code lives in `src/features/{history,ledger,master,media,system}`.
- Dialogs: use `AppDialog` (`src/components/common/app-dialog.tsx`): `title` / `description` header with X, scrolling body, footer = Cancel + an `actions` array (label, onClick or `form` id, variant, size, permission; at most `DIALOG_DEFAULTS.MAX_ACTIONS`). Pass `dirty` to ask before discarding unsaved changes. Entity forms (`ResourceFormProps`) render no buttons: they set `id={formId}` and report `{ pending, dirty }` via `onStateChange`. Confirm prompts stay on `ConfirmDialog`.
- Live notifications (ADR-0014): `src/shared/realtime/echo.ts` (one laravel-echo + pusher-js connection to Reverb through nginx on the API host; channel auth via raw axios to `/broadcasting/auth`; off without `NEXT_PUBLIC_REVERB_APP_KEY`). `features/system/components/background-task-listener.tsx` (mounted in `AdminLayout`) toasts `background-task.finished` on `admin.{id}` and, once subscribed, asks `background-task/status/{id}` about ids in `pendingTasks` (sessionStorage) so a missed event still shows. A screen that starts a task calls `pendingTasks.add(id)`.
- File upload (ADR-0014): `features/media/lib/uploader.ts` `uploadFile(file, { folder, onProgress, signal })` — ≤ 100 MB one presigned PUT then `media/complete`; larger in server-sized parts, 4 XHR PUTs at a time (XHR for upload progress), part URLs fetched in batches of 100, retries with backoff (403 → fresh URL), aborts the server upload on failure, returns `{ kind: 'processing', task }` (add `task.id` to `pendingTasks`). ETags are read from MinIO's response, which works because nginx serves MinIO on the same origin.
- Files screen (`app/admin/files`, `features/media/components/media-browser.tsx`): the open folder is the `folder` query param (wrap in `Suspense`, it uses `useSearchParams`); `useMediaList` / `useMediaActions` (`hooks/use-media.ts`) and the upload queue `useUploads` (`hooks/use-uploads.ts`, one file at a time; a large file stays `processing` until `onBackgroundTaskFinished` — the window event BackgroundTaskListener re-sends — reports its `media.process` task, which also refreshes the listing).
- i18n: `messages/en.json` via next-intl; every user-facing string goes through `useTranslations`.
- Permissions (ADR-0013): `usePermissions().can('<feature>.<action>')` reads `user.permissions` from `credential/me`. `ResourceListPage` derives the feature from its endpoint's last segment and hides create / edit / delete (and row selection) without `store` / `update` / `delete`; menu entries carry a `permission` and `filterMenu` drops the rest (`src/shared/utils/permissions.ts`). Hiding is cosmetic, the API answers 403. Roles screen: `app/admin/roles` + `features/master/components/{role-form,permission-matrix}.tsx`.

## API contract

Responses are wrapped: `{ data, error: { status, code, messages } }`. Read server errors from `error.messages` (string or field map), never `response.data.message`. Lists are Laravel resource collections inside `data`: `{ data: T[], links, meta }`, typed as `PaginatedResponse<T>` (`src/shared/types/api.ts`) from the generated `PaginationLinks` / `PaginationMeta`; `getPaginationInfo` reads `meta` (`from` / `to` are null on an empty page).
