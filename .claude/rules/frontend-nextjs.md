---
paths:
  - "nextjs-fe/**/*.{ts,tsx,mjs,css}"
  - "nextjs-docs/**/*.{ts,tsx,mjs,css}"
---

# Next.js / React conventions

## Style
- TypeScript `strict`. No `any` (use `unknown` + narrowing). No non-null `!` unless proven safe.
- Prettier + ESLint (flat config) are the source of truth; don't hand-format.
- Files: kebab-case (`category-form.tsx`), components PascalCase, hooks `useXxx` in `use-xxx.ts`.
- Named exports. Default export only where Next.js requires it (`page`, `layout`, `loading`, `error`, `not-found`).
- Import via the `@/` alias. No deep relative paths (`../../..`).

## React
- React 19 + React Compiler: don't add `useMemo` / `useCallback` / `memo` for performance unless profiling shows a need.
- Server Components by default in `nextjs-docs`. In `nextjs-fe` (client-rendered SPA), add `'use client'` at the lowest level that needs it.
- Shared layouts go in `layout.tsx`, not wrapped inside each `page.tsx`.
- Forms: react-hook-form + zod schema (`zodResolver`). Validation messages via next-intl.
- Server state: TanStack Query only (no Redux, no ad-hoc `useEffect` fetching). Query keys from a central key factory.
- Every user-facing string goes through `useTranslations` / `messages/en.json`.

## API
- All HTTP through `@/shared/api` client. Endpoints from `API_ENDPOINTS`, routes from `ADMIN_ROUTES`.
- Backend envelope: `{ data, error: { status, code, messages } }`. Extract errors with `getApiErrorMessage` / `handleBindErrors` (`src/shared/utils/error-handler.ts`); never read `response.data.message`.
- Types for API payloads live in `src/shared/types` (or are generated from OpenAPI once that refactor lands).

## UI
- shadcn/ui in `components/ui` is generated code: extend via composition, edit only when necessary.
- Tailwind 4 utilities + `cn()`; no inline `style` for things Tailwind covers.
- Accessible by default: labels for inputs, `aria-*` on icon-only buttons, keyboard-operable dialogs.

## Tests
- Vitest + Testing Library + MSW. Hooks and API utilities need unit tests; test behavior, not implementation.
