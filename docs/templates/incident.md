# INC-nnn — <symptom, e.g. "API returns 500 on every request">

> 🇻🇳 Mẫu báo cáo sự cố — mở ngay khi phát hiện, cập nhật liên tục trong lúc xử lý. Postmortem viết sau, ở file riêng.

| | |
|---|---|
| Severity | SEV-1 / 2 / 3 / 4 |
| Status | Investigating / Mitigated / Resolved |
| Detected | YYYY-MM-DD HH:MM by <alert / user / check> |
| Commander | <role> |
| Investigators | <roles> |
| Environment | dev / staging / prod-like |

## Impact (current)

What users see, how many, since when.
> 🇻🇳 Người dùng đang thấy gì, bao nhiêu người, từ khi nào.

## Timeline

| Time | Event / action / finding |
|---|---|
| HH:MM | Alert fired: … |
| HH:MM | Hypothesis: … → checked … → result … |
| HH:MM | Mitigation: … |
| HH:MM | Resolved |

## Status updates

- HH:MM — what we know, what we are doing, next update at HH:MM.

## Evidence

Log lines, queries, screenshots (no secrets, no personal data).

## Postmortem

Link: `docs/incidents/INC-nnn-postmortem.md` (required for SEV-1/2).
