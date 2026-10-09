# Runbook — Backup, test restore and disaster-recovery restore

> 🇻🇳 Runbook sao lưu, khôi phục thử (vào DB/bucket tạm) và khôi phục thật khi có sự cố. Khôi phục thử nên làm định kỳ — một bản backup chưa từng được restore thì chưa được coi là có.

| | |
|---|---|
| Use when | Daily backup; a restore drill (monthly, and after any schema change); real data loss |
| Risk | Backup and test restore: low (read live data, write only a throwaway DB / bucket). Full restore: **high** — overwrites the live database, bucket and `docker/.env` |
| Duration | Backup ~1 s, test restore ~2 s at today's volume (dev DB, and the REQ-002 volume of the perf seed: 1,500 evidence). Full restore adds container recreation (~1 min) |
| Targets | RPO < 24 h, RTO < 30 min ([backup/README.md](../../backup/README.md)) |
| Last tested | 2026-10-08 (OPS-01): backup → test restore of the dev DB and of the perf DB, row counts identical in every table; full-restore `pg_restore` flags checked on a throwaway copy. The full mode itself was not run (it would overwrite dev) |

## Preconditions

- Stack up (`make up`); `ml-postgres` and `ml-minio` healthy.
- Cloud upload: [rclone](https://rclone.org) installed on the host with the remotes in `RCLONE_REMOTES` (default `ggdrive:second-memory-backups onedrive:second-memory-backups`). Without rclone use `--local-only` and copy the archive off the machine yourself.
- The archive contains `docker/.env` (all secrets): treat it like a password file. `backups/` and `restore_tmp/` are git-ignored.

> 🇻🇳 Điều kiện: stack đang chạy; muốn đẩy lên cloud thì cần rclone đã cấu hình. File backup chứa `docker/.env` (toàn bộ secret) — giữ như mật khẩu.

## Steps

### Backup

```bash
bash backup/backup.sh                 # dump + media + docker/.env → upload to every remote
bash backup/backup.sh --local-only    # keep backups/history/system_backup_<timestamp>.tar.gz, no upload
```

The script stops at the first error. The local archive is deleted only when **every** remote received it; otherwise it stays and the script exits `1`. Without rclone (and without `--local-only`) it refuses before doing anything (exit `2`). The dump is checked with `pg_restore --list` before it is archived.

### Test restore (drill)

Restores into a throwaway database (dropped and recreated) and bucket; `docker/.env` and the containers are not touched.

```bash
bash backup/restore.sh backups/history/system_backup_<timestamp>.tar.gz \
  --target-db restore_check --target-bucket restore-check
```

Without an archive argument the newest backup on the first remote is downloaded (needs rclone). The script refuses `POSTGRES_DB`, `testing`, `postgres` and the official bucket as targets.

### Full restore (disaster recovery)

1. Maintenance mode: stop traffic at nginx, stop the app containers (`docker compose -f docker/docker-compose.yml stop ml-php ml-nextjs ml-nextjs-docs`).
2. Make sure `ml-postgres` and `ml-minio` run: `docker compose -f docker/docker-compose.yml up -d ml-postgres ml-minio`.
3. Restore (newest cloud backup, or pass the archive path):
   ```bash
   bash backup/restore.sh [backups/history/system_backup_<timestamp>.tar.gz]
   ```
   The database restore runs in **one transaction** (`--clean --if-exists --single-transaction`): on any error nothing is changed and the script stops, so the old data is still there. Then it puts back `docker/.env`, runs `setup-env.sh`, re-syncs the database role password and recreates `ml-php` and `ml-redis`.
4. Start the rest (`docker compose -f docker/docker-compose.yml up -d`), check the site, end maintenance mode.

> 🇻🇳 Các bước: (1) backup — `backup.sh` (đẩy cloud) hoặc `--local-only`; chỉ xoá file cục bộ khi mọi remote đã nhận. (2) Khôi phục thử — `restore.sh <file> --target-db restore_check --target-bucket restore-check`, không đụng dữ liệu thật. (3) Khôi phục thật — bật bảo trì, dừng container ứng dụng, chạy `restore.sh` (DB khôi phục trong một transaction: lỗi thì không đổi gì), rồi khởi động lại và kiểm tra.

## Verify

- Backup: the script prints the archive path and size; with upload, `rclone lsf <remote>/history/` lists it.
- Test restore: every table has the same row count as the source. Compare with:
  ```bash
  q="select table_name, (xpath('/row/c/text()', query_to_xml(format('select count(*) as c from %I', table_name), false, true, '')))[1]::text::int from information_schema.tables where table_schema='public' and table_type='BASE TABLE' order by 1"
  diff <(docker exec ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d \"\$POSTGRES_DB\" -qAt -c \"$q\"") \
       <(docker exec ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d restore_check -qAt -c \"$q\"") && echo identical
  ```
  (Rows written to the live DB after the backup show up as differences; run it right after the backup.)
- Full restore: log in to the admin, open the dashboard and the public skills page.

Clean up a drill: `docker exec ml-postgres sh -c 'psql -U "$POSTGRES_USER" -d postgres -c "DROP DATABASE restore_check WITH (FORCE)"'` and `docker exec ml-minio sh -c 'mc alias set m http://127.0.0.1:9000 "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD" && mc rb --force m/restore-check'`; delete the local archive.

## Roll back

- Backup and test restore change no live data; drop the throwaway database / bucket.
- Full restore: a failed database step changes nothing (single transaction). After a successful restore the previous state is gone — take a `--local-only` backup of the broken system first if you may need anything from it.

## Escalate

- `pg_restore` fails in the full restore → the live database is unchanged; keep maintenance mode, open an incident, try the previous archive.
- Backup exits `1` (an upload failed) on two consecutive days → RPO at risk: fix the remote or copy the kept archive by hand.

## Findings of the OPS-01 check (2026-10-08)

- The previous `backup.sh` had no error handling and always deleted the local archive: without rclone it printed "done" and exited `0` with **no backup anywhere**. Fixed (see Backup above).
- The previous `restore.sh` discarded every `pg_restore` error (`> /dev/null 2>&1`) and, with `--clean`, could leave a half-dropped database; it could only restore over the live data and used `docker-compose` v1. Fixed: single-transaction restore, errors stop the script, test mode, `docker compose`.
- Today's MinIO buckets are empty (media API removed in ADR-0007), so the media step ran but copied no object.
- Not measured: the full restore end to end (it would overwrite the dev data) — do it on a scratch environment when P4 provides one.

> 🇻🇳 Phát hiện: `backup.sh` cũ không kiểm tra lỗi và luôn xoá file cục bộ — thiếu rclone vẫn báo xong, thoát 0 mà **không có bản backup nào**. `restore.sh` cũ nuốt mọi lỗi `pg_restore` và có thể để DB bị xoá dở. Đã sửa cả hai. Bucket MinIO hiện trống. Chưa đo khôi phục thật đầu-cuối (sẽ ghi đè dữ liệu dev) — làm khi P4 có môi trường tạm.
