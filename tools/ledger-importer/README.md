# ledger-importer

Imports the Obsidian notes marked `publish: true` into the Skill Ledger as `note` evidence (REQ-002 US-4). The contract — which frontmatter is read, how a note becomes a request item, exit codes — is [ADR-0010](../../docs/adr/0010-python-importer-tooling.md#importer-contract-note--request-item); the API side is `POST /api/admin/evidence/import`.

> 🇻🇳 Công cụ dòng lệnh import các note Obsidian có `publish: true` vào Skill Ledger dưới dạng bằng chứng loại `note`. Hợp đồng chi tiết nằm ở ADR-0010.

## Run

Everything runs in Docker (nothing is installed on the host). Mint a token first: `docker exec ml-php php artisan ledger:import-token <login_id>`.

```bash
docker build -t ledger-importer tools/ledger-importer
docker run --rm --network ml_network -v /path/to/vault:/vault:ro \
  -e LEDGER_API_TOKEN -e LEDGER_NOTE_BASE_URL=https://notes.example.com \
  ledger-importer --dry-run        # then without --dry-run
```

| Variable | Meaning |
|---|---|
| `LEDGER_API_TOKEN` | Required. Sanctum token with the `evidence:import` ability; never printed |
| `LEDGER_API_URL` | API base URL, default `http://ml-nginx` (inside the Compose network) |
| `LEDGER_NOTE_BASE_URL` | Base of the published note URLs, used when a note has no `url` in its frontmatter |

`--dry-run` validates the vault and asks the API what would change (`dry_run: true`), writing nothing. Any invalid published note stops the run before anything is sent, because the API hides every imported row missing from the list. Exit codes: `0` ok, `1` invalid notes or more than 2,000 published notes (nothing sent), `2` usage / configuration, `3` API error.

> 🇻🇳 `--dry-run` kiểm tra vault và hỏi API xem sẽ thay đổi gì, không ghi gì. Chỉ cần một note publish bị lỗi là CLI dừng và không gửi gì, vì API sẽ ẩn mọi dòng không có trong danh sách. Mã thoát: 0 thành công, 1 note lỗi, 2 cấu hình sai, 3 lỗi API.

## Checks

```bash
docker build --target dev -t ledger-importer-dev tools/ledger-importer && docker run --rm ledger-importer-dev
```

runs `ruff check`, `ruff format --check`, `mypy --strict` and `pytest` (the API is replaced by `httpx.MockTransport`). Dependencies are locked in `uv.lock`; after editing `pyproject.toml`, regenerate it with uv 0.12.23 in a container.

> 🇻🇳 Lệnh trên chạy ruff, mypy strict và pytest trong image (API được giả lập bằng `MockTransport`). Phụ thuộc khoá trong `uv.lock`.
