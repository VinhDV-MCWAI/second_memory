# public-site

Public pages of Second Memory at the site root: a home page of categories, published skills with their level history, public evidence and attached files, and articles. Built with [Astro](https://astro.build) (server rendering, no client JavaScript). Details in Vietnamese: [docs/features/trang-public.md](../docs/features/trang-public.md).

| URL                         | Shows                                                                         |
| --------------------------- | ----------------------------------------------------------------------------- |
| `/`                         | home: Skills and the public categories                                        |
| `/skills`                   | public skills grouped by category                                             |
| `/skills/{slug}`            | one skill: level history, public evidence and files; private or unknown → 404 |
| `/guides/{category}`        | the public articles of a category                                             |
| `/guides/{category}/{slug}` | one article                                                                   |
| `/files/{key}`              | opens an attached file through a fresh short-lived link                       |
| `/account/*`, `/me`         | end-user sign-in, sign-up, favourites and read later                          |

Old `/docs/…` links redirect to `/` (nginx); old `/skills/me`, `/skills/account/*`, `/skills/files/*` to the new paths.

```bash
make up              # starts ml-public-site with the rest of the stack (http://localhost:81/)
make fe-typecheck    # includes astro check for this app
make openapi         # regenerates src/types/openapi.d.ts after an API change
make e2e             # browser journeys end on these pages
```
