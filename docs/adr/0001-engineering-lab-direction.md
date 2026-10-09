# ADR-0001 — Turn Second Memory into an Engineering Lab

| | |
|---|---|
| Status | Superseded by [ADR-0012](0012-restore-features-dev-first.md) (2026-10-09); was: Accepted (2026-10-07, owner: "start implementing") |
| Date | 2026-10-07 |
| Deciders | Owner (all roles) |
| Related | [plan/01-analysis.md](../plan/01-analysis.md), [plan/02-roadmap.md](../plan/02-roadmap.md) |

## Context

Second Memory (since 2023) is a self-hosted CMS for personal knowledge: content authoring, file manager, enterprise-style RBAC, custom auth, multi-cloud backup. In 2026 the owner moved document authoring to Obsidian, so most features lost their purpose. The code quality is good after the 2026-10 refactor (tests, static analysis, CI/CD), and the project is listed on the owner's CV. The owner's goals are now: learn and practise the full professional software lifecycle, produce evidence for employers, and build tools useful in the long term, all locally and at zero cost.

> 🇻🇳 Từ 2023, Second Memory là CMS tự host. Năm 2026 việc viết tài liệu chuyển sang Obsidian nên phần lớn tính năng mất mục đích. Chất lượng code đã tốt sau đợt refactor. Mục tiêu mới: học và thực hành đầy đủ vòng đời phần mềm chuyên nghiệp, có bằng chứng cho nhà tuyển dụng, xây công cụ hữu ích lâu dài; tất cả chạy local, chi phí 0.

## Options

1. **Abandon the project.** Start fresh elsewhere. Loses history, CI/CD, tests, and the CV reference.
2. **Keep extending the CMS.** Low learning value, no real user.
3. **Keep the repository, change its purpose to an Engineering Lab** with a small real product (Skill Ledger) run through a documented professional process, and remove what is obsolete.

> 🇻🇳 1. Bỏ dự án. 2. Tiếp tục mở rộng CMS. 3. Giữ repo, đổi mục đích thành Engineering Lab với sản phẩm nhỏ có thật (Skill Ledger), vận hành theo quy trình chuyên nghiệp có tài liệu, và loại bỏ phần lỗi thời.

## Decision

Option 3. The roadmap in `docs/plan/02-roadmap.md` is the single plan. New technologies enter only through an ADR that names the problem they solve.

> 🇻🇳 Chọn phương án 3. Roadmap là kế hoạch duy nhất. Công nghệ mới chỉ được đưa vào qua ADR nêu rõ vấn đề nó giải quyết.

## Consequences

- The legacy version is preserved as tag `v1.0.0`; removed features stay recoverable.
- Roughly 70% of current features will be removed or simplified (Phase 2), which is itself documented as the first full lifecycle.
- The old refactor plan (`.claude/refactor/PLAN.md`) is frozen after Phase 0.
- Documentation becomes a first-class deliverable, written bilingually (English + Vietnamese) until the owner drops Vietnamese.

> 🇻🇳 Bản cũ được giữ ở tag `v1.0.0`. Khoảng 70% tính năng sẽ bị xoá hoặc đơn giản hoá ở Phase 2, và chính việc đó là vòng đời đầy đủ đầu tiên. Plan refactor cũ đóng băng sau Phase 0. Tài liệu là sản phẩm chính thức, viết song ngữ.
