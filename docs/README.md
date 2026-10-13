# Tài liệu Second Memory

Mọi thứ không phải code nằm ở đây. Tài liệu viết bằng tiếng Việt, theo quy ước ở mục "Quy ước viết tài liệu" trong [plan/01-goals-and-workflow.md](plan/01-goals-and-workflow.md). Tài liệu lệch với code thì sửa tài liệu.

## Đọc trước

| # | Tài liệu | Trả lời câu hỏi |
|---|---|---|
| 1 | [plan/01-goals-and-workflow.md](plan/01-goals-and-workflow.md) | Mục tiêu, luồng làm việc, quy ước viết tài liệu |
| 2 | [plan/02-roadmap.md](plan/02-roadmap.md) | Các giai đoạn và khi nào thì xong |
| 3 | [plan/03-backlog.md](plan/03-backlog.md) | Danh sách việc và trạng thái |
| 4 | [dev-guide.md](dev-guide.md) | Chạy, test, sửa lỗi trên máy dev |
| 5 | [plan/04-ai-prompts.md](plan/04-ai-prompts.md) | Prompt giao việc cho AI: làm tiếp, xác nhận đề xuất, kiểm tra tiến độ, nghiệm thu |
| 6 | [architecture/current.md](architecture/current.md) | Hệ thống đang chạy thế nào (container, API, đăng nhập, dữ liệu) |
| 7 | [adr/0012-restore-features-dev-first.md](adr/0012-restore-features-dev-first.md) | Vì sao đổi hướng ngày 2026-10-09 |

## Các thư mục

| Thư mục | Chứa gì | Khi nào sửa |
|---|---|---|
| [plan/](plan/) | Mục tiêu, lộ trình, danh sách việc, prompt cho AI | Khi đổi kế hoạch. Trạng thái việc chỉ đổi bằng `scripts/lane.sh` |
| [dev-guide.md](dev-guide.md) | Hướng dẫn môi trường dev | Khi có lệnh mới hoặc gặp lỗi mới |
| [architecture/](architecture/) | `current.md`: kiến trúc hiện tại | Khi đổi container, luồng đăng nhập, cấu trúc dữ liệu |
| [adr/](adr/) | Các quyết định kỹ thuật, mỗi quyết định một file. Đã "Accepted" thì không sửa, muốn đổi thì viết quyết định mới | Khi có một lựa chọn thật sự giữa nhiều phương án |
| [releases/](releases/) | Ghi chú phát hành theo từng phiên bản | Khi gắn tag phiên bản mới |
| [runbooks/](runbooks/) | Hướng dẫn vận hành từng bước: sao lưu và khôi phục, xuất nội dung, nhập từ Obsidian | Khi đổi script vận hành |
| [archive/](archive/) | Tài liệu cũ, giữ để tra lịch sử, không cập nhật nữa | Không sửa |

Sắp có: `features/`, mỗi tính năng một file (việc "Mô tả từng tính năng").

## Tài liệu cũ trong archive

| Thư mục | Nội dung |
|---|---|
| [archive/engineering-lab/](archive/engineering-lab/) | Giai đoạn Engineering Lab (trước 2026-10-09): kế hoạch, sổ tay quy trình, mẫu tài liệu, báo cáo, hồ sơ yêu cầu, thiết kế, sự cố |
| [archive/learning/](archive/learning/) | Ghi chú học tập của chủ dự án |
| [archive/legacy-architecture/](archive/legacy-architecture/) | Bộ tài liệu kiến trúc tiếng Việt đầu tiên (đã lỗi thời) |
