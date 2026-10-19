# ADR-0018 — Đưa lại mục tiêu luyện quy trình làm phần mềm kiểu doanh nghiệp

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-17, chủ dự án đồng ý, trừ CI/CD) |
| Ngày | 2026-10-17 |
| Người quyết định | Chủ dự án |
| Liên quan | [ADR-0001](0001-engineering-lab-direction.md) (hướng Engineering Lab), [ADR-0012](0012-restore-features-dev-first.md) (khôi phục tính năng), [kế hoạch cũ P0–P11](../archive/engineering-lab/02-roadmap.md) |

## Bối cảnh

Mục tiêu của chủ dự án có hai phần:

1. Ứng dụng đủ tính năng.
2. Đúc kết kinh nghiệm làm việc của một developer trong doanh nghiệp thật: nhận yêu cầu → làm rõ → phân tích → thiết kế → làm → test → QA → đóng gói → triển khai → bàn giao → vận hành, xử lý sự cố.

ADR-0012 (2026-10-09) chỉ định bỏ việc **xóa tính năng**. Nhưng kế hoạch viết lại sau đó đã thay toàn bộ kế hoạch cũ, nên phần 2 không còn trong kế hoạch. Skill đóng vai PO, QA, Ops cũng bị xóa ngày 2026-10-13. Ngày 2026-10-17 chủ dự án nhắc lại phần 2.

## Quyết định

Thêm một mạch việc **Luyện quy trình** chạy song song với việc làm tính năng. Quy trình được luyện **trên chính các tính năng thật**, không thu gọn ứng dụng.

| Bước | Làm gì | Khi nào |
|---|---|---|
| Đóng gói và bàn giao | Gắn phiên bản cho phần đã làm từ `v2.1.0`: ghi chú phát hành, biên bản nghiệm thu, tài liệu bàn giao, nhìn lại giai đoạn | Làm ngay |
| Sổ tay quy trình | Viết lại [sổ tay cũ](../archive/engineering-lab/handbook/README.md) bằng tiếng Việt, gọn, khớp cách làm hiện tại. Giữ hai tốc độ: thay đổi nhỏ đi đường tắt, việc lớn đi đủ quy trình | Sau bước đóng gói |
| Một vòng đời đầy đủ | Đi trọn nhận yêu cầu → làm rõ → phân tích ảnh hưởng → thiết kế → ADR → làm → kế hoạch test → QA → phát hành → bàn giao → nhìn lại, trên tính năng **trang cho người dùng và tài khoản người dùng cuối** ([ADR-0019](0019-public-user-frontend.md)) | Cùng lúc với trang cho người dùng |
| Triển khai | Bản demo prod-like có dữ liệu mẫu và HTTPS, quản lý bí mật, diễn tập dựng lại từ đầu | Để sau |
| Vận hành | Log, chỉ số, cảnh báo, mục tiêu chất lượng dịch vụ, hướng dẫn xử lý | Để sau |
| Sự cố | Gây lỗi giả lập, báo cáo sự cố, nhìn lại không đổ lỗi, diễn tập khôi phục dữ liệu | Để sau |
| Hiệu năng và bảo mật | Đo tải trước / sau, phân tích mối đe dọa, quét lỗ hổng | Để sau |
| Portfolio | Case study: vấn đề → việc đã làm → kết quả có số liệu | Để sau |

**CI/CD không nằm trong mạch này**: giữ nguyên tắt chạy tự động, chỉ làm khi chủ dự án yêu cầu. Lý do: còn nhiều tính năng cần làm nhanh, CI/CD tự động làm chậm việc đó. Vẫn dùng `make verify` trên máy.

## Hệ quả

- Lộ trình có thêm mạch Luyện quy trình ([02-roadmap.md](../plan/02-roadmap.md)). Các bước "Để sau" được ghi ở đó để làm sau.
- Sổ tay, mẫu tài liệu, báo cáo trong `docs/archive/engineering-lab/` là tài liệu tham khảo của mạch này: không xóa.
- Bảng việc trống tính năng **không** có nghĩa là hết việc.
- Câu hỏi còn mở: có khôi phục các vai giả lập (PO, QA, Ops) để tạo đầu vào như yêu cầu mơ hồ, báo lỗi hay không. Nếu khôi phục, vai giả lập không bao giờ quyết thay chủ dự án.

## Cập nhật 2026-10-19

- Sổ tay quy trình bản mới: [docs/handbook/](../handbook/README.md), 7 chương.
- Câu hỏi về vai giả lập: **không khôi phục**. Các vai trong sổ tay dùng để ghi lại kinh nghiệm một developer làm việc với từng vai trong doanh nghiệp, không phải để AI đóng giả các vai đó (chủ dự án quyết).
