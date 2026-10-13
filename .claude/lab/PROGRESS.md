# Nhật ký tiến độ

Đọc file này đầu tiên khi bắt đầu một phiên làm việc mới. Nhật ký giai đoạn trước (Engineering Lab): [docs/archive/engineering-lab/lab/PROGRESS.md](../../docs/archive/engineering-lab/lab/PROGRESS.md).

## Trạng thái hiện tại

| | |
|---|---|
| Giai đoạn đang làm | 1. Dọn nền |
| Branch | `feature/p3-ledger-api` (đổi branch khi chủ dự án yêu cầu) |
| Môi trường | Chỉ dev. Staging, production, Terraform, đo tải, CI/CD tự động: tạm dừng |
| Việc tiếp theo | `scripts/lane.sh status` → nhận việc đầu tiên trong danh sách "Claimable now" |

## Việc chủ dự án phải tự làm

Claude không có quyền làm những việc này:

1. Push branch và tag lên GitHub, tạo pull request.
2. Chạy `make tf-apply` lần đầu (Terraform), nhưng việc này đang tạm dừng.
3. Cài rclone và lên lịch sao lưu định kỳ, xem [backup-restore.md](../../docs/runbooks/backup-restore.md).

## Lưu ý môi trường

Xem [docs/dev-guide.md](../../docs/dev-guide.md) mục 6 "Các lỗi hay gặp". Thêm lỗi mới vào đó, không ghi ở đây.

## Log

Mỗi dòng do `scripts/lane.sh done` tự thêm. Không sửa tay.

- 2026-10-09 — [lane main] NEN-01 done: docs/dev-guide.md
- 2026-10-09 — [lane main] NEN-02 done: kế hoạch mới trong docs/plan/, ADR-0012, kế hoạch cũ chuyển vào docs/archive/engineering-lab/

- 2026-10-13 — [lane main] NEN-03 done: 42ce9580, 774c59da — setup-env.sh chuyển vào scripts/, xóa start.sh, sửa mọi chỗ nhắc tới

- 2026-10-13 — [lane main] NEN-04 done: 78462665, e4743898 — docs/ còn plan, adr, releases, runbooks, architecture; tài liệu Engineering Lab vào archive; mục lục tiếng Việt

- 2026-10-13 — [lane main] NEN-05 done: 982773ee — docs/features/: 7 file mô tả tính năng + mục lục; docs/README.md trỏ tới

- 2026-10-13 — [lane main] NEN-06 done: d0e0895f — bỏ 3 vai giả lập và skill lane (gộp vào lab-task), rules + CLAUDE.md theo cách làm một branch

## Next step

Nhận việc tiếp theo bằng `scripts/lane.sh status`. Làm theo luồng trong [01-goals-and-workflow.md](../../docs/plan/01-goals-and-workflow.md).
