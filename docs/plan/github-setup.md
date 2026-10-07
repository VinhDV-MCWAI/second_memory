# GitHub setup (P0-06, manual)

> 🇻🇳 Thiết lập GitHub — bạn làm tay vì Claude không có quyền trên GitHub. Mất khoảng 20 phút.

These steps need the owner's GitHub account. Everything here is free for a public repository.

> 🇻🇳 Các bước cần tài khoản GitHub của bạn. Tất cả miễn phí với repo public.

## 1. Labels

> 🇻🇳 Nhãn phân loại issue. Vào **Issues → Labels → New label**.

| Label | Colour | Meaning |
|---|---|---|
| `type:req` | `#1d76db` | Requirement / change request (REQ) |
| `type:bug` | `#d73a4a` | Defect |
| `type:task` | `#c5def5` | Technical task |
| `type:spike` | `#fbca04` | Time-boxed research |
| `type:incident` | `#b60205` | Incident (INC) |
| `type:docs` | `#0075ca` | Documentation only |
| `prio:p1` | `#b60205` | Do now (blocks others / production down) |
| `prio:p2` | `#d93f0b` | This phase |
| `prio:p3` | `#fbca04` | Next phase |
| `prio:p4` | `#e4e669` | Someday |
| `phase:P0` … `phase:P11` | `#5319e7` | Roadmap phase |
| `role:po` / `role:qa` / `role:ops` / `role:sec` | `#bfd4f2` | Simulated stakeholder who raised it |

With the GitHub CLI installed (`gh auth login` first) the same can be scripted:

> 🇻🇳 Nếu đã cài GitHub CLI thì chạy script:

```bash
for l in "type:req 1d76db" "type:bug d73a4a" "type:task c5def5" "type:spike fbca04" \
         "type:incident b60205" "type:docs 0075ca" "prio:p1 b60205" "prio:p2 d93f0b" \
         "prio:p3 fbca04" "prio:p4 e4e669" "role:po bfd4f2" "role:qa bfd4f2" \
         "role:ops bfd4f2" "role:sec bfd4f2"; do
  set -- $l; gh label create "$1" --color "$2" --force
done
for p in $(seq 0 11); do gh label create "phase:P$p" --color 5319e7 --force; done
```

## 2. Project board

> 🇻🇳 Bảng quản lý công việc. Vào **Projects → New project → Board**.

1. Name: `Engineering Lab`. Columns: `Backlog`, `Ready`, `Doing`, `Review`, `Done`.
2. Add a field `Phase` (single select P0–P11) and `Estimate` (number, sessions).
3. Workflows (built in): item added → `Backlog`; PR merged → `Done`.
4. WIP rule: at most 2 items in `Doing`.

> 🇻🇳 Tạo board 5 cột, thêm trường Phase và Estimate, bật workflow tự động, giới hạn tối đa 2 việc ở cột Doing.

## 3. Branch rulesets

> 🇻🇳 Bảo vệ branch. **Settings → Rules → Rulesets → New branch ruleset**, áp dụng cho `developer` và `main`.

- Restrict deletions, block force pushes.
- Require a pull request before merging (0 approvals while working alone).
- Require status checks: `Frontend (lint, format, typecheck, test)`, `Backend (pint, larastan, tests)` (they appear after CI has run once).
- Keep "Automatically delete head branches" **off**: `cleanup-branch.yml` already deletes merged work branches and must never delete `developer`.

## 4. Release `v1.0.0`

> 🇻🇳 Tạo release đầu tiên sau khi PR đã merge vào `main`.

The tag `v1.0.0` already exists locally on the last commit of `chore/p0-baseline`. After that branch is merged into `main` with a merge commit (not squash), push it:

```bash
git push origin v1.0.0
```

Then **Releases → Draft a new release → tag `v1.0.0`**, paste [docs/releases/v1.0.0.md](../releases/v1.0.0.md).

> 🇻🇳 Sau đó tạo release trên GitHub và dán nội dung release notes.
