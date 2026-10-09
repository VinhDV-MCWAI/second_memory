# Learning notes (archive)

> 🇻🇳 Ghi chú học tập đã lưu trữ: những gì owner tự viết để học, nay đã được thay bằng quyết định hoặc code mới.

Write-ups and research notes the owner wrote while learning, before the Engineering Lab replaced the code or the plan they describe. They are history, not instructions: each file starts with a banner that names what replaced it. Code wins over anything here.

> 🇻🇳 Đây là lịch sử, không phải hướng dẫn: mỗi file có dòng đầu ghi rõ cái gì đã thay thế nó. Khi lệch với code, code là sự thật.

| File | Topic | Replaced by |
|---|---|---|
| [AUTH-GUIDE.md](AUTH-GUIDE.md) | Hand-written JWT auth and how to fix it | [ADR-0004](../../adr/0004-sanctum-spa-cookie-auth.md) (Sanctum SPA cookie auth), [PRB-001](../../problems/PRB-001-custom-jwt-auth.md) |
| [search-research.md](search-research.md) | Classic vs inverted-index search, ranking, what "good search" means | [ADR-0009](../../adr/0009-postgres-search.md) (PostgreSQL search) |
| [laravel-coding-convention.md](laravel-coding-convention.md) | First Laravel convention: modules, interfaces, CRUD generator | `.claude/rules/backend-laravel.md`, [handbook 05](../../handbook/05-coding.md) |
| [file-manager-google-drive-plan.md](file-manager-google-drive-plan.md) | Media storage on Google Drive (never built) | [ADR-0007](../../adr/0007-remove-media-api.md) (media API removed) |
| [ai-codegen-and-jwt-notes.md](ai-codegen-and-jwt-notes.md) | AI prompts for CRUD generation; JWT login review | [ADR-0004](../../adr/0004-sanctum-spa-cookie-auth.md), [handbook](../../handbook/README.md) |
| [prompt-feature-test-design.md](prompt-feature-test-design.md) | Prompt to enumerate feature-test cases of an endpoint | [handbook 06](../../handbook/06-testing-qa.md) |
| [minio-media-pipeline-notes.md](minio-media-pipeline-notes.md) | MinIO buckets, lifecycle, upload / queue / Reverb media pipeline | [ADR-0007](../../adr/0007-remove-media-api.md), [ADR-0011](../../adr/0011-infrastructure-as-code.md) |
| [backup-dr-strategy.md](backup-dr-strategy.md) | Backup and disaster-recovery strategy, maintenance windows | [backup / restore runbook](../../runbooks/backup-restore.md) |

> 🇻🇳 Bảng trên liệt kê từng file, chủ đề và tài liệu đã thay thế nó.
