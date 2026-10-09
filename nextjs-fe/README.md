# nextjs-fe — Second Memory admin dashboard

> 🇻🇳 Trang quản trị Skill Ledger: Next.js 16, React 19, render phía client, gọi Laravel API bằng cookie session.

The admin panel of the Skill Ledger: skills and their level history, learning goals, evidence, tags, one search box over all of them, admin accounts and the audit history. Next.js 16 (App Router) with React 19; every page is a client component that talks to the Laravel API with the Sanctum session cookie. In dev it runs in the `ml-nextjs` container and is reached through nginx at <http://localhost:81> (directly: <http://localhost:3456>).

> 🇻🇳 Quản lý kỹ năng, lịch sử cấp độ, mục tiêu, bằng chứng, tag, tìm kiếm, tài khoản admin và lịch sử audit. Mọi trang là client component gọi API bằng cookie session. Dev chạy trong container `ml-nextjs`, truy cập qua `http://localhost:81`.

| Area  | Libraries                                                                                                  |
| ----- | ---------------------------------------------------------------------------------------------------------- |
| Data  | TanStack Query 5, axios (session cookie + `X-XSRF-TOKEN`), types generated from `laravel-api/openapi.json` |
| Forms | react-hook-form + zod                                                                                      |
| UI    | shadcn/ui, Tailwind CSS 4                                                                                  |
| Text  | next-intl (`messages/en.json`)                                                                             |
| Tests | Vitest 4 (jsdom); the end-to-end journey is in [`e2e/`](../e2e/)                                           |

## Pages

> 🇻🇳 Các trang. Chưa đăng nhập vào `/admin/*` sẽ bị chuyển về `/login`.

`/login`, then under `/admin`: dashboard, `skills`, `goals`, `evidence`, `tags`, `search`, `admins`. Without the session cookie, `/admin/*` redirects to `/login` (`src/proxy.ts`); the API decides what the account may do (role `owner` writes, `viewer` reads).

## Commands

> 🇻🇳 Lệnh, chạy từ thư mục gốc repo.

```bash
make fe-lint            # ESLint, both Next.js apps
make fe-typecheck       # tsc --noEmit
make fe-test            # Vitest
make fe-format          # Prettier
make openapi            # after an API change: regenerate openapi.json and src/shared/types/openapi.d.ts
make e2e                # Playwright journey through nginx
```

Single commands inside the container: `docker exec ml-nextjs pnpm <script>` (see `package.json`). After a `pnpm-lock.yaml` change, rebuild the dev image (`docker compose up -d --build ml-nextjs` from `docker/`).

> 🇻🇳 Đổi `pnpm-lock.yaml` thì phải build lại image dev.

## Build

> 🇻🇳 Bản production: Next.js `standalone` chạy bằng Node sau nginx.

Production uses `output: 'standalone'`: a small Node server (`node server.js`) behind nginx, built by `docker/nextjs/Dockerfile` (`--build-arg APP=nextjs-fe --target production`, see `make tf-images`). The build bakes `NEXT_PUBLIC_API_URL=/api`, so the same image works in every environment that serves the API on the same origin.

> 🇻🇳 Build dùng `NEXT_PUBLIC_API_URL=/api` (cùng origin), nên một image dùng được cho mọi môi trường.

## More

- Layout, API contract and gotchas: [CLAUDE.md](CLAUDE.md).
- Conventions: [.claude/rules/frontend-nextjs.md](../.claude/rules/frontend-nextjs.md), [handbook 05](../docs/handbook/05-coding.md).
