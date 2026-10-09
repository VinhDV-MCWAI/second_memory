# Engineering Lab — Documentation

> 🇻🇳 Thư mục tài liệu của Engineering Lab: kế hoạch, quy trình, quyết định kỹ thuật và hồ sơ sự cố.

This folder is the single home for everything that is not code: the plan, the way of working, technical decisions, incidents and lessons learned. It is written as docs-as-code: Markdown, reviewed in pull requests, versioned by git.

> 🇻🇳 Mọi thứ không phải code đều nằm ở đây: kế hoạch, cách làm việc, quyết định kỹ thuật, sự cố, bài học. Viết theo kiểu docs-as-code: Markdown, review qua pull request, lịch sử nằm trong git.

## Start here

> 🇻🇳 Bắt đầu đọc từ đây.

| # | Tài liệu | Trả lời câu hỏi |
|---|---|---|
| 1 | [plan/01-goals-and-workflow.md](plan/01-goals-and-workflow.md) | Mục tiêu, luồng làm việc, quy ước viết tài liệu |
| 2 | [plan/02-roadmap.md](plan/02-roadmap.md) | Các giai đoạn và khi nào thì xong |
| 3 | [plan/03-backlog.md](plan/03-backlog.md) | Danh sách việc và trạng thái |
| 4 | [dev-guide.md](dev-guide.md) | Chạy, test, sửa lỗi trên máy dev |
| 5 | [adr/0012-restore-features-dev-first.md](adr/0012-restore-features-dev-first.md) | Vì sao đổi hướng ngày 2026-10-09 |
| 6 | [plan/04-ai-prompts.md](plan/04-ai-prompts.md) | Prompt giao việc cho AI: làm tiếp, xác nhận đề xuất, kiểm tra tiến độ, nghiệm thu |
| 7 | [architecture/current.md](architecture/current.md) | Hệ thống đang chạy thế nào (container, API, đăng nhập, dữ liệu) |

> Các phần bên dưới vẫn theo quy ước cũ (song ngữ); sẽ viết lại ở việc "Rút gọn docs/" (NEN-04). Quy ước mới: chỉ tiếng Việt, xem mục "Quy ước viết tài liệu" trong [01-goals-and-workflow.md](plan/01-goals-and-workflow.md).

## Folder map

> 🇻🇳 Bản đồ thư mục. Thư mục đánh dấu *(planned)* sẽ được tạo ở giai đoạn tương ứng — không tạo sẵn thư mục rỗng.

```text
docs/
├── README.md            this file
├── plan/                analysis, roadmap, backlog, GitHub setup (living documents)
├── releases/            release notes per version tag
├── adr/                 Architecture Decision Records: one decision per file, never edited after "Accepted"
├── architecture/        current.md (living: the system today), as-is.md (v1.0.0, frozen)
├── templates/           copy these to start a new record (REQ, RFC, ADR, PRB, INC, postmortem, runbook, reports)
├── handbook/            how we work: team, intake, design, git, coding, QA, release, operations, reporting, docs & data
├── requirements/        REQ-xxx: request → clarification → user stories → acceptance criteria
├── design/              design docs / RFCs: ERD, API contracts, sequence diagrams, rollout
├── problems/            PRB-xxx: problem records (origin → impact → solution → follow-ups)
├── incidents/           (planned, Phase 7) INC-xxx: incident reports and postmortems
├── runbooks/            step-by-step operational procedures (backup / restore, ledger import, content export)
├── reports/             daily / weekly reports, retrospectives, perf measurements, spikes
└── archive/             outdated docs kept for history (legacy-architecture/ = the old root 01–10 files, learning/ = the owner's learning notes, indexed in its README)
```

## Writing conventions

> 🇻🇳 Quy ước viết tài liệu.

1. **Bilingual, English first.** Every paragraph is written in English, followed by its Vietnamese version in a blockquote that starts with `> 🇻🇳`. Tables, diagrams and code stay English-only; a Vietnamese note follows when needed.
   > 🇻🇳 **Song ngữ, tiếng Anh trước.** Mỗi đoạn viết tiếng Anh, tiếp theo là bản tiếng Việt trong blockquote bắt đầu bằng `> 🇻🇳`. Bảng, sơ đồ, code chỉ dùng tiếng Anh; có ghi chú tiếng Việt bên dưới nếu cần.
2. **Removing Vietnamese later** is one command, because every Vietnamese line carries the marker:
   > 🇻🇳 Khi không cần tiếng Việt nữa, xóa bằng một lệnh vì dòng nào cũng có dấu hiệu `🇻🇳`:

   ```bash
   find docs -name '*.md' -exec sed -i '/^\s*> 🇻🇳/d' {} +
   ```
3. **Explain for a newcomer and for "future you".** Every problem, decision or incident states: context → cause → impact → options → decision → result → what came up next. Use [templates/problem-record.md](templates/problem-record.md).
   > 🇻🇳 **Viết cho người mới và cho chính mình sau 1 năm.** Mọi vấn đề / quyết định / sự cố đều có: bối cảnh → nguyên nhân → hậu quả → phương án → quyết định → kết quả → vấn đề mới phát sinh.
4. **IDs are permanent.** `REQ-012`, `ADR-0003`, `INC-004` never get reused or renumbered; link them from commits, PRs and other docs.
   > 🇻🇳 **ID là vĩnh viễn**, không tái sử dụng, không đánh số lại; dẫn link từ commit, PR và tài liệu khác.
5. **Code wins.** If a document disagrees with the code, the code is the truth and the document gets a fix.
   > 🇻🇳 **Code là sự thật.** Tài liệu lệch code thì sửa tài liệu.
