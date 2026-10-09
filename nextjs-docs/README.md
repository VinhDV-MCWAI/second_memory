# nextjs-docs — Second Memory public site

> 🇻🇳 Trang công khai: Skill Ledger chỉ đọc tại `/skills`; các link `/docs` cũ hiển thị trang "nội dung đã chuyển".

The public, read-only face of the Skill Ledger ([REQ-002](../docs/requirements/REQ-002-skill-ledger.md) US-3, [RFC-002 §4.4](../docs/design/RFC-002-skill-ledger.md#44-frontends)). Next.js 16 server components that call the public API from the server; no login, no client-side data fetching.

> 🇻🇳 Mặt công khai, chỉ đọc của Skill Ledger. Server components gọi API công khai từ phía server; không đăng nhập.

| URL                | What it shows                                                                                            |
| ------------------ | -------------------------------------------------------------------------------------------------------- |
| `/skills`          | public skills grouped by category, with their current level                                              |
| `/skills/{slug}`   | one skill: level history and public evidence; a private or unknown skill is a 404                        |
| `/docs`, `/docs/…` | a "content moved" page (HTTP 200): the old CMS content now lives in Obsidian, and old links keep working |

> 🇻🇳 Kỹ năng riêng tư hoặc không tồn tại trả về 404 giống nhau, để không lộ sự tồn tại của nó.

In dev it runs in the `ml-nextjs-docs` container on port 3457 and nginx serves it at <http://localhost:81/skills> (directly: <http://localhost:3002/skills>). The API base URL is `API_INTERNAL_URL` (default `http://ml-nginx:8080/api`); responses are cached for 60 seconds because the API rate-limits per IP and every request comes from this one server.

> 🇻🇳 Dev: container `ml-nextjs-docs` (port 3457), qua nginx tại `http://localhost:81/skills`. Kết quả API được cache 60 giây vì API giới hạn theo IP.

## Commands

> 🇻🇳 Lệnh, chạy từ thư mục gốc repo.

```bash
make fe-lint            # ESLint, both Next.js apps
make fe-typecheck       # tsc --noEmit
make fe-format          # Prettier
make e2e                # the Playwright journey ends on this site, through nginx
```

Production: `output: 'standalone'` image from `docker/nextjs/Dockerfile` (`--build-arg APP=nextjs-docs --target production`, run with `PORT=3457`), built by `make tf-images`. Details and gotchas: [CLAUDE.md](CLAUDE.md).

> 🇻🇳 Bản production là image `standalone`; chi tiết trong `CLAUDE.md`.
