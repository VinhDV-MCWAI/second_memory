# 07 — Release and deployment

> 🇻🇳 Release và triển khai.

## Environments

> 🇻🇳 Các môi trường. Tất cả chạy local (không tốn phí); P4 sẽ dựng chúng bằng Terraform.

| Environment | Purpose | Source | Data | Today | From P4 |
|---|---|---|---|---|---|
| `dev` | Daily development | Working tree | Seeders / factories | `make up` | Terraform `env=dev` |
| `staging` | Integration test, QA sign-off | `developer` | Anonymised copy or seeders | `cd.yml` → self-hosted | Terraform `env=staging` |
| `prod-like` | Release rehearsal, incidents, DR drills | Tags on `main` | Realistic seed + backups | – | Terraform `env=prod` |

## Versioning

> 🇻🇳 Đánh số phiên bản theo SemVer.

[Semantic Versioning](https://semver.org): `MAJOR.MINOR.PATCH`. MAJOR = breaking API or data change (e.g. `v2.0.0` removes the CMS); MINOR = new features; PATCH = fixes. Each roadmap phase ends with a tag and release notes in `docs/releases/`.

> 🇻🇳 MAJOR = thay đổi phá vỡ tương thích; MINOR = tính năng mới; PATCH = sửa lỗi. Mỗi giai đoạn kết thúc bằng một tag và release notes.

## Release checklist

> 🇻🇳 Checklist release.

1. All phase PRs merged into `developer`; staging deployed and QA signed off.
2. Release notes written (`docs/releases/vX.Y.Z.md`): highlights, breaking changes, migrations, known issues.
3. **Backup** taken of the target environment (`make backup`).
4. PR `developer` → `main`, merge commit.
5. Tag: `git tag -a vX.Y.Z -m "…" && git push origin vX.Y.Z`; GitHub release from the notes.
6. Deploy, run smoke checks (health endpoint, login, one core flow), watch dashboards/logs for 15 minutes.
7. Announce (weekly report).

## Database migrations

> 🇻🇳 Migration cơ sở dữ liệu — an toàn khi deploy.

- A deployed migration is never edited; write a new one.
- Every migration has a working `down()`; test it with `migrate:rollback` on a scratch DB.
- Destructive changes use **expand / contract** across releases:
  1. *Expand*: add the new column/table; code writes to both old and new.
  2. *Migrate*: backfill existing rows; verify counts.
  3. *Switch*: code reads from the new structure.
  4. *Contract*: in a later release, stop writing the old one and drop it (after a backup).
- Long-running data migrations are queued jobs, not migration files.

> 🇻🇳 Thay đổi mang tính phá huỷ đi theo expand/contract qua nhiều release: thêm mới và ghi song song → backfill → chuyển sang đọc cấu trúc mới → release sau mới xoá cấu trúc cũ (có backup).

- Exception (single instance, no rolling deploy, as long as there is one server): all four steps may ship in **one** release if each step is its own migration and commit, the contract migration checks its precondition and refuses to run otherwise (e.g. "every old row is copied"), and the release notes require a backup first. Used for `audit_log` in `v2.0.0` ([ADR-0006](../adr/0006-audit-log.md), retro P2). With more than one app instance the multi-release rule applies again.

> 🇻🇳 Ngoại lệ (một instance, không rolling deploy): cả bốn bước được phép nằm trong **một** release nếu mỗi bước là một migration và commit riêng, migration contract tự kiểm tra điều kiện và từ chối chạy nếu chưa đạt, và release notes bắt buộc backup trước. Khi có nhiều instance thì quay lại quy tắc nhiều release.

## Rollback

> 🇻🇳 Quay lui.

| What failed | Action |
|---|---|
| App code (no migration) | Redeploy the previous image tag (images are tagged by commit SHA) |
| Migration with `down()` | `php artisan migrate:rollback --step=N`, then previous image |
| Data damaged | Restore from the pre-release backup (`make restore`); see the DR runbook |
| Feature misbehaves but deploy is fine | Turn off its feature flag (from P5) |

Rule of thumb: **roll back first, debug later**. A rollback is not a failure; an unrecoverable release is.

> 🇻🇳 Nguyên tắc: **quay lui trước, điều tra sau**. Quay lui không phải thất bại; release không quay lại được mới là thất bại.

## Hotfix

> 🇻🇳 Sửa nóng.

Branch `hotfix/<issue>-<slug>` from `main` → fix + test → PR to `main` → tag PATCH → deploy → merge `main` back into `developer` the same day.

> 🇻🇳 Tách từ `main`, sửa và test, PR vào `main`, tag bản PATCH, deploy, và merge ngược về `developer` ngay trong ngày.
