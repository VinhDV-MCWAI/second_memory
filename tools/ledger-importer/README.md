# ledger-importer

Imports the Obsidian notes marked `publish: true` as articles, with their whole content (ADR-0022). The API side is `POST /api/admin/article/import`; it resolves `parent` and the `[[links]]` of the body to other published notes. How to use it, in Vietnamese: [nhap-tu-obsidian.md](../../docs/features/nhap-tu-obsidian.md) and the [runbook](../../docs/runbooks/ledger-import.md).

## Run

Everything runs in Docker (nothing is installed on the host). Mint a token first: `docker exec ml-php php artisan ledger:import-token <login_id>`.

```bash
make import vault=/path/to/vault dry=1   # then without dry=1
```

| Variable | Meaning |
|---|---|
| `LEDGER_API_TOKEN` | Required. Sanctum token with the `article:import` ability; never printed |
| `LEDGER_API_URL` | API base URL, default `http://ml-nginx:8080` (inside the Compose network) |

Frontmatter read: `publish` (only the YAML boolean `true`), `title` (default: file name), `summary` (default: the first paragraph, cut to 300 characters), `categories` (names), `parent` (a path, a file name or `"[[Note]]"`).

`--dry-run` validates the vault and asks the API what would change (`dry_run: true`), writing nothing. Any invalid published note stops the run before anything is sent, because the API hides every imported article missing from the list. Exit codes: `0` ok, `1` invalid notes, more than 2,000 published notes or more than 45 MB (nothing sent), `2` usage / configuration, `3` API error.

## Checks

```bash
make importer-lint   # ruff check, ruff format --check, mypy --strict
make importer-test   # pytest (the API is replaced by httpx.MockTransport)
```

Dependencies are locked in `uv.lock`; after editing `pyproject.toml`, regenerate it with uv 0.12.23 in a container.
