# ADR-0007 — Remove the media API, keep the stored files

> 🇻🇳 Xoá media API, giữ lại các file đã lưu.

| | |
|---|---|
| Status | Superseded by [ADR-0012](0012-restore-features-dev-first.md) (2026-10-09); was: Accepted |
| Date | 2026-10-08 |
| Deciders | PO, TL |
| Related | [REQ-001](../requirements/REQ-001-slim-down.md) Q3 / US-3, [RFC-001](../design/RFC-001-slim-down.md) §3 correction, [REQ-002](../requirements/REQ-002-skill-ledger.md) Q3, [RFC-002](../design/RFC-002-skill-ledger.md) slice 1, backlog P3-05b |

## Context

RFC-001 kept the media API because the avatar upload was thought to use it. Slice 6 showed that the avatar upload was never wired up, so since `v2.0.0` no screen calls the API. The decision was left open in case the Skill Ledger needed evidence files. REQ-002 Q3 answers it: evidence is a link, no uploads.

The API is 26 files and about 2.2k lines (controller, 10 FormRequests, service, `MinioService`, two queued jobs, two console commands with a daily schedule, two Reverb broadcast events, enum, constants, rule, resources). It has no tests. It is the only code that uses the queue worker and Reverb. REQ-001 Q3 / US-3 asks to keep the uploaded files.

> 🇻🇳 Bối cảnh: RFC-001 giữ media API vì tưởng upload avatar dùng nó; thực tế chưa từng nối, nên từ `v2.0.0` không màn hình nào gọi API này. Quyết định được để mở phòng khi Skill Ledger cần file minh chứng; REQ-002 câu 3 trả lời: bằng chứng là link, không upload. API gồm 26 file, ~2.2k dòng, không có test, là code duy nhất dùng queue worker và Reverb. REQ-001 yêu cầu giữ các file đã upload.

## Options

1. **Keep the API.** No work now. An untested public-facing upload surface stays, and every framework upgrade must carry it.
2. **Remove the API and the `media_mgmt` table.** Smallest result, but the table is the only index of what the MinIO objects are (names, folders, owners); dropping it makes the kept files much harder to use.
3. **Remove the API code, keep the `media_mgmt` rows and the MinIO objects.** The code goes; the data stays exactly as REQ-001 promised. If uploads are ever needed again, they come back through a new requirement and a design that fits the new domain.

> 🇻🇳 Các phương án: (1) giữ API — không tốn công nhưng giữ một bề mặt upload chưa có test; (2) xoá API và bảng `media_mgmt` — gọn nhất nhưng bảng là chỉ mục duy nhất của các object MinIO; (3) xoá code API, giữ bảng và object.

## Decision

We choose **option 3**.

> 🇻🇳 Chọn **phương án 3**.

- Delete the media controller, requests, resources, service, `MinioService`, model and repository, jobs, events, console commands and their schedule entry, `MediaConst`, `UploadStatus`, `IsImageMedia`, the media routes, the stale PHPStan baseline entries, and regenerate OpenAPI and the FE types.
- Keep the `media_mgmt` table (no migration) and the MinIO bucket contents. Keep the MinIO container; `backup/backup.sh` mirrors the bucket.
- `ml-queue` and `ml-reverb` are left with no work; removing them (and Reverb / queue config) is a P4 Docker-hardening item, not part of this change.

> 🇻🇳 Xoá toàn bộ code media (controller, request, resource, service, `MinioService`, model, repository, job, event, command và lịch chạy, hằng số, enum, rule, route), dọn baseline PHPStan, sinh lại OpenAPI và type FE. Giữ bảng `media_mgmt` (không migration) và dữ liệu trong bucket MinIO; giữ container MinIO cho backup. `ml-queue` và `ml-reverb` không còn việc gì → xử lý ở P4.

## Consequences

- **Easier:** ~2.2k untested lines less; no upload endpoint to secure; the queue worker and Reverb can go in P4.
- **Harder:** a future upload feature starts from scratch (the old code stays in git history and tag `v2.0.0`).
- **Data:** nothing is deleted; `media_mgmt` becomes a read-only record of the stored objects, with no code using it. Dropping it later needs its own decision.

> 🇻🇳 Hệ quả: **dễ hơn** — bớt ~2.2k dòng chưa test, không còn endpoint upload phải bảo vệ, P4 có thể bỏ queue worker và Reverb. **Khó hơn** — nếu sau này cần upload thì làm lại từ đầu (code cũ còn trong lịch sử git và tag `v2.0.0`). **Dữ liệu** — không xoá gì; `media_mgmt` thành bản ghi chỉ đọc, muốn xoá phải có quyết định riêng.
