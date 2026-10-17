# public-site

Public pages of Second Memory at `/skills`: published skills, their level history, public evidence and attached files. Built with [Astro](https://astro.build) (server rendering, no client JavaScript). Details in Vietnamese: [docs/features/trang-public.md](../docs/features/trang-public.md).

| URL                   | Shows                                                                         |
| --------------------- | ----------------------------------------------------------------------------- |
| `/skills`             | public skills grouped by category                                             |
| `/skills/{slug}`      | one skill: level history, public evidence and files; private or unknown → 404 |
| `/skills/files/{key}` | opens an attached file through a fresh short-lived link                       |

Old `/docs/…` links redirect to `/skills` (nginx).

```bash
make up              # starts ml-public-site with the rest of the stack (http://localhost:81/skills)
make fe-typecheck    # includes astro check for this app
make openapi         # regenerates src/types/openapi.d.ts after an API change
make e2e             # browser journeys end on these pages
```
