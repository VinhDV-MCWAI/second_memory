# Runbook — Import published Obsidian notes into the Skill Ledger

> 🇻🇳 Runbook import các note Obsidian có `publish: true` vào Skill Ledger (bằng chứng loại `note`). Chạy tay mỗi khi muốn đồng bộ vault; chạy lại bao nhiêu lần cũng được.

| | |
|---|---|
| Use when | You published, edited or unpublished notes in the vault and want the ledger to match (REQ-002 US-4) |
| Risk | Medium: upserts by path and never deletes, but every imported note missing from the vault is **hidden** and set private. A wrong vault path hides them all; they come back private and must be made public again one by one. Always read `hidden` in the dry run (step 3) first |
| Duration | ~1 min (first run builds the image) |
| Last tested | 2026-10-08 on the throwaway `perf` database through `make import` (dry run, run, second run `unchanged` only). Not yet on the dev stack: the dev DB has no owner account (see Preconditions) |

Contract (which frontmatter counts, exit codes): [ADR-0010](../adr/0010-python-importer-tooling.md#importer-contract-note--request-item). Tool: [tools/ledger-importer](../../tools/ledger-importer/README.md).

## Preconditions

- The stack is up (`make up`) and the target database has an **active owner** account. A fresh dev DB has none: create one first (`docker exec ml-php php artisan db:seed --class=RootAccountSeeder` creates `root`, see PROGRESS "Needs the owner").
- Each note to publish has `publish: true` (the YAML boolean) and `date: YYYY-MM-DD`, plus a `url` in its frontmatter or a base URL for all notes (`LEDGER_NOTE_BASE_URL`).
- Skills you list in `skills:` already exist in the admin (unknown names are reported, never created).

> 🇻🇳 Điều kiện: stack đang chạy và DB có tài khoản owner đang hoạt động (DB dev mới chưa có — tạo bằng `RootAccountSeeder`). Note cần `publish: true`, `date`, và `url` hoặc `LEDGER_NOTE_BASE_URL`. Kỹ năng phải có sẵn trong admin.

## Steps

1. Mint a token for the owner (printed once; the server keeps only its hash). Keep it in your shell or a git-ignored file next to the vault, never in the repo:
   ```bash
   export LEDGER_API_TOKEN="$(docker exec ml-php php artisan ledger:import-token root | head -1)"
   ```
   Default lifetime 90 days (`--days=N` to change). One token per import session is fine; revoke it in step 5.
2. Set where published notes live (skip if every note has its own `url`):
   ```bash
   export LEDGER_NOTE_BASE_URL=https://notes.example.com
   ```
3. Dry run — validates the vault and asks the API what would change, writes nothing:
   ```bash
   make import vault=/path/to/vault dry=1
   ```
   Check `hidden`: it should be the number of notes you unpublished, not your whole vault (a wrong path hides everything). Exit `1` lists the invalid published notes (`path: reason`): fix them and repeat. Nothing is sent while any published note is invalid, because the API would hide what is missing from the list.
4. Import:
   ```bash
   make import vault=/path/to/vault
   ```
5. Revoke the token when done (or keep it until it expires if you import often):
   ```bash
   docker exec ml-php php artisan ledger:import-token root --revoke
   ```

`LEDGER_API_URL` defaults to `http://ml-nginx` (the Compose network); set it only to reach another API.

> 🇻🇳 Các bước: (1) tạo token cho owner và export vào `LEDGER_API_TOKEN`; (2) đặt `LEDGER_NOTE_BASE_URL` nếu note không có `url`; (3) chạy thử `make import vault=… dry=1` — mã thoát 1 kèm danh sách note lỗi thì sửa rồi chạy lại; (4) chạy thật `make import vault=…`; (5) thu hồi token.

## Verify

- The report prints `created`, `updated`, `unchanged`, `hidden`. Run step 4 again: it must print `created 0`, `updated 0`, `hidden 0` (idempotent).
- In the admin, the evidence list shows the notes as type `note`. A newly imported note is **public at once** (`publish: true` means public); you can make it private in the admin and the import keeps that choice.
- `Unknown skills (…)` lists names to add in the admin or fix in the notes; then run again.

> 🇻🇳 Kiểm tra: chạy lại bước 4 phải ra `created 0`, `updated 0`, `hidden 0`. Trong admin, note xuất hiện với loại `note`; note mới import **công khai ngay** (`publish: true` nghĩa là công khai), có thể chuyển riêng tư trong admin và import giữ nguyên lựa chọn đó. Kỹ năng lạ được liệt kê để thêm hoặc sửa note.

## Roll back

- A note hidden by mistake (wrong vault path, note moved): run the import again with the right vault; the note comes back as `updated` but **stays private** until you make it public again in the admin (the hide set it private).
- Rows are never deleted by the import; to remove one, delete it in the admin.
- A leaked token: `ledger:import-token <login> --revoke`. The token only opens `POST admin/evidence/import` (403 elsewhere).

> 🇻🇳 Hoàn tác: chạy lại với đúng vault để lấy lại note bị ẩn nhầm (note quay lại nhưng vẫn riêng tư cho đến khi bật công khai trong admin); import không bao giờ xoá dòng nào; token bị lộ thì thu hồi bằng `--revoke` (token chỉ gọi được route import).

## Escalate

- Exit `3` with `401` → the token expired or was revoked (mint a new one); `403` → the login is not an active owner; `429` → more than 10 runs in a minute, wait; `422` → the API rejected a note the CLI accepted: that is a contract bug, open an issue with the message.
- More than 2,000 published notes: the CLI stops (exit `1`); batching needs a new decision (ADR-0010).

> 🇻🇳 Leo thang: mã 3 kèm 401 → token hết hạn/bị thu hồi; 403 → tài khoản không phải owner; 429 → chạy quá 10 lần/phút; 422 → API từ chối note mà CLI chấp nhận, là lỗi hợp đồng, mở issue. Hơn 2.000 note: cần quyết định mới.
