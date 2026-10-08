# Runbook — Export legacy CMS content to Markdown

> 🇻🇳 Runbook xuất nội dung CMS cũ ra Markdown. Bắt buộc chạy trên môi trường đang có dữ liệu **trước khi** nâng cấp lên `v2.0.0` (bản này xoá các bảng nội dung).

| | |
|---|---|
| Use when | Before upgrading an environment from `v1.x` to `v2.0.0` (REQ-001 US-2) |
| Risk | Low (read-only on the database; writes files only) |
| Duration | ~1 min |
| Available in | `v1.2.0` (command removed again in `v2.0.0` together with the tables) |
| Last tested | 2026-10-07 (feature tests with factory data; dev DB has no content) |

## Preconditions

- The environment runs `v1.2.0` (the version that contains `content:export-markdown`).
- A fresh backup: `make backup`.

> 🇻🇳 Môi trường đang chạy `v1.2.0` và đã có backup mới.

## Steps

1. Run the export inside the API container:
   ```bash
   docker exec ml-php php artisan content:export-markdown --path=storage/app/exports/content-markdown
   ```
   The command prints a table: categories, entries, linked and unlinked descriptions, files written/unchanged.
2. Optional: also export rows flagged as deleted into a separate folder:
   ```bash
   docker exec ml-php php artisan content:export-markdown --include-deleted --path=storage/app/exports/content-markdown-with-deleted
   ```
3. Copy the files out of the container and into the Obsidian vault:
   ```bash
   docker cp "ml-php:$(docker exec ml-php pwd)/storage/app/exports/content-markdown" ./content-markdown
   ```
4. Archive the folder next to the pre-upgrade backup (it is the only Markdown copy of the old content).

> 🇻🇳 Chạy lệnh export trong container API, copy thư mục kết quả ra ngoài, đưa vào vault Obsidian và lưu kèm bản backup trước nâng cấp.

## Verify

- Run step 1 again: `Files written` must be `0` (the export is idempotent).
- Entry count in the output equals `select count(*) from entry_mgmt where is_delete = false`.
- Open two or three files in Obsidian: front matter, headings, lists and links look right.

## Output layout

```text
<category-slug>/_index.md         category description + links to its entries
<category-slug>/<entry-slug>.md   entry with its descriptions (## title, > summary, article)
_uncategorized/<entry-slug>.md    entries no category referenced
_unlinked/<id>-<title>.md         descriptions no entry referenced
```

## Roll back

Nothing to roll back: the command only reads the database. Delete the output folder to start over.

## Escalate

If the counts do not match the database, stop the upgrade and open a `PRB` with the command output.
