# 01 — Team model

> 🇻🇳 Mô hình team.

## Roles

> 🇻🇳 Các vai trò. Một người có thể giữ nhiều vai; khi đổi vai, đổi "mũ" một cách có ý thức (ghi vai đang đóng trong báo cáo ngày).

| Role | Owns | Asks / produces | Simulated by |
|---|---|---|---|
| **Product Owner (PO)** | *What* and *why*: priorities, scope, acceptance | Requests, priorities, accepts or rejects a delivery | Claude skill `simulate-po` |
| **Tech Lead (TL)** | *How*: architecture, technical risk, standards | RFC review, ADR decisions, code review | Owner |
| **Developer (Dev)** | Implementation and its tests | Code, unit/integration tests, PR | Owner |
| **QA** | Quality evidence from the user's point of view | Test plans, exploratory testing, bug reports, sign-off | Claude skill `simulate-qa` + owner |
| **DevOps / SRE (Ops)** | Environments, pipelines, monitoring, reliability | Infra changes, alerts, runbooks, incident command | Owner; requests from `simulate-ops` |
| **Security** | Threats and controls | Threat model, security review, findings | Owner (Phase 8) |
| **Support / User** | The person using the product | Feedback, bug reports from the field | `simulate-po` in "user" mode |

> 🇻🇳 PO quyết định *làm gì và vì sao*; Tech Lead quyết định *làm thế nào*; Dev viết code và test; QA kiểm chứng chất lượng từ góc nhìn người dùng; Ops lo môi trường, pipeline, giám sát; Security lo rủi ro bảo mật; Support/User đưa phản hồi.

## RACI matrix

R = Responsible (does it), A = Accountable (decides, one per row), C = Consulted (gives input before), I = Informed (told after).

> 🇻🇳 R = người làm; A = người chịu trách nhiệm cuối cùng/quyết định (mỗi dòng chỉ một A); C = được hỏi ý kiến trước; I = được thông báo sau.

| Activity | PO | TL | Dev | QA | Ops | Sec |
|---|---|---|---|---|---|---|
| Write / prioritise a request | **A/R** | C | C | I | I | I |
| Clarify to "Ready" | A | R | R | C | C | C |
| Design doc / ADR | I | **A** | R | C | C | C |
| Implement + unit/integration tests | I | C | **A/R** | I | I | I |
| Code review | – | **A** | R (author fixes) | – | C (infra) | C (auth, data) |
| Test plan, exploratory test, sign-off | C | I | C | **A/R** | I | I |
| Accept the feature | **A** | I | I | C | I | I |
| Release & deploy | I | C | R | C | **A** | I |
| Monitoring & alerts | I | C | C | I | **A/R** | I |
| Incident command | I | C | R | I | **A** | C |
| Postmortem | I | **A** | R | C | R | C |
| Security review | I | C | R | I | C | **A** |

## Who to tell what

> 🇻🇳 Báo cái gì cho ai.

| Situation | Tell | Channel | When |
|---|---|---|---|
| Requirement is unclear | PO | Comment on the `REQ` issue, list the questions | Before starting |
| Estimate will be exceeded by > 50% | PO + TL | Issue comment + daily report "Risks" | As soon as known |
| Blocked by another role | That role, cc TL | Issue comment, daily report "Blockers" | Same day |
| Technical decision with lasting impact | TL | RFC / ADR PR | Before implementing |
| Bug found in someone else's area | QA (logs it), owner of the area | Bug issue | Immediately |
| Production impact | Ops (incident commander) | `INC` issue, status note | Immediately (see [08-operations.md](08-operations.md)) |
| Security concern | Security, TL | Private note / issue without exploit details | Immediately |

> 🇻🇳 Nguyên tắc: báo sớm, báo bằng văn bản ở nơi có lịch sử (issue/PR), và luôn kèm đề xuất hướng xử lý chứ không chỉ nêu vấn đề.

## Meetings (simulated as written rituals)

> 🇻🇳 Các buổi họp — ở đây thực hiện dưới dạng văn bản.

| Ritual | Real team | Here |
|---|---|---|
| Daily stand-up | 15 min, every day | Daily report file ([09-reporting.md](09-reporting.md)) |
| Planning | Start of iteration | Pick tasks from the backlog into "Ready", note them in the weekly report |
| Review / demo | End of iteration | Release notes + short demo GIF or screenshots |
| Retrospective | End of iteration | `docs/reports/retro/Px.md` (what went well / badly / we change) |
| Design review | As needed | RFC pull request with comments |
