# backup — PostgreSQL + MinIO backup and restore

> 🇻🇳 Script sao lưu và khôi phục PostgreSQL + MinIO. Cách chạy từng bước nằm trong runbook.

How to run a backup, a test restore and a disaster-recovery restore, step by step: **[docs/runbooks/backup-restore.md](../docs/runbooks/backup-restore.md)**.

> 🇻🇳 Hướng dẫn chạy từng bước (backup, khôi phục thử, khôi phục thảm hoạ): xem runbook ở trên.

| Script | What it does |
|---|---|
| `backup.sh [--local-only]` (`make backup`) | `pg_dump` (custom format) + mirror of the MinIO bucket + `docker/.env` → `backups/history/system_backup_<timestamp>.tar.gz`, copied to every rclone remote in `RCLONE_REMOTES`. The local archive is deleted only after every remote has it; any failure keeps it and exits non-zero |
| `restore.sh [ARCHIVE] [--target-db NAME [--target-bucket NAME]] [--yes]` (`make restore`) | without `--target-db`: disaster recovery over the live database, bucket and `docker/.env`, in one database transaction; with it: a test restore into a throwaway database / bucket that leaves live data alone |

## Targets and retention

> 🇻🇳 Mục tiêu và thời gian lưu giữ.

| | |
|---|---|
| RPO (data you may lose) | < 24 hours — one backup a day |
| RTO (time to restore) | < 30 minutes — not yet measured end to end (planned on a P4 environment) |
| Retention | archives older than 3 days are deleted from each remote after a successful upload (`rclone delete --min-age 3d`) |
| Secrets | the archive contains `docker/.env`: store it like a password file. `backups/` and `restore_tmp/` are git-ignored |

> 🇻🇳 RPO < 24 giờ, RTO < 30 phút (chưa đo trọn vẹn), giữ 3 ngày trên mỗi remote. File backup chứa `docker/.env` nên phải bảo vệ như mật khẩu.

Any change to these scripts must be checked with a real test restore (`.claude/rules/infra.md`). The original strategy write-up (tiers, maintenance mode, restore phases) is archived in [docs/archive/learning/backup-dr-strategy.md](../docs/archive/learning/backup-dr-strategy.md).

> 🇻🇳 Mọi thay đổi script phải được kiểm tra bằng một lần khôi phục thử thật. Bản chiến lược gốc đã lưu trữ ở link trên.
