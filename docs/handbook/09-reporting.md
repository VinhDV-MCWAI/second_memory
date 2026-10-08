# 09 — Reporting and escalation

> 🇻🇳 Báo cáo và báo lên cấp trên.

Reports are short, factual and written where they leave history. They also become the raw material for the portfolio: a year of weekly reports is a detailed record of what you did.

> 🇻🇳 Báo cáo ngắn, đúng sự thật, viết ở nơi lưu lại lịch sử. Chúng cũng là nguyên liệu cho portfolio: một năm báo cáo tuần là hồ sơ chi tiết những gì bạn đã làm.

## Daily report (stand-up)

> 🇻🇳 Báo cáo ngày — thay cho họp đứng buổi sáng. Viết trong 5 phút, đầu hoặc cuối buổi làm.

File: `docs/reports/daily/YYYY-MM-DD.md` (template [daily-report.md](../templates/daily-report.md)).

| Section | Content |
|---|---|
| Role(s) today | Which hat(s) you wore |
| Done | What was finished, with links (PR, issue, commit) |
| Next | What comes next session |
| Blockers | What stops you and who must act |
| Risks | What may go wrong (estimate overrun, unclear requirement) |
| Time | Hours per task (simple time log) |

Example:

```markdown
## 2026-10-08 — Dev, QA
- Done: P2-02 impact analysis for sliders/banners (#21), 3 tables, 8 routes, 4 FE pages
- Next: P2-03 ERD as-is / to-be
- Blockers: none
- Risks: history tables have FKs from 2 views → contract step needs a view rewrite first
- Time: P2-02 2.0h, review 0.5h
```

## Weekly report

> 🇻🇳 Báo cáo tuần — gửi cho "quản lý" (PO/TL). Viết cuối tuần, 15 phút.

File: `docs/reports/weekly/YYYY-Www.md` (template [weekly-report.md](../templates/weekly-report.md)): phase progress (tasks done / planned), highlights with numbers, problems and decisions (links to PRB/ADR), next week's plan, hours spent, mood/energy (honest, short).

## Escalation

> 🇻🇳 Báo lên khi nào và thế nào.

| When | Escalate to | How |
|---|---|---|
| Blocked > 1 session | The role that can unblock, then TL | Issue comment with what you tried and what you need |
| Scope or estimate changes significantly | PO | Options with trade-offs, ask for a decision |
| Technical risk you cannot judge | TL | Short write-up, draft RFC if needed |
| Incident | Incident commander (Ops) | `INC` issue immediately |

A good escalation message contains: **context, the problem, what you tried, options, your recommendation, the deadline for a decision.**

> 🇻🇳 Tin nhắn báo lên tốt gồm: bối cảnh, vấn đề, đã thử gì, các phương án, đề xuất của bạn, hạn cần quyết định.

## Retrospective

> 🇻🇳 Họp nhìn lại — cuối mỗi giai đoạn.

File: `docs/reports/retro/Px.md` (template [retro.md](../templates/retro.md)): what went well, what went badly, what we change (max 3 actions, each becomes an issue or a handbook PR), metrics of the phase.
