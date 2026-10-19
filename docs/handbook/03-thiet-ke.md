# 3. Thiết kế và ghi quyết định

## Khi nào phải viết thiết kế

| Viết đề xuất có phương án (và ADR nếu là quyết định lâu dài) khi thay đổi… | Còn lại |
|---|---|
| thêm hoặc bỏ một phần ứng dụng, một dịch vụ, một bảng | Mô tả cách làm trong đề xuất hoặc message commit là đủ |
| đổi API mà ứng dụng khác đang gọi (`nextjs-fe`, `public-site`, importer) | |
| đưa vào công nghệ, thư viện, thành phần hạ tầng mới | |
| phải chuyển đổi dữ liệu đang có | |
| ước tính hơn 6 buổi, hoặc có hơn một cách làm hợp lý | |

**Đề xuất** (hoặc phần "Phương án" trong `REQ-…`) giải thích *định làm thế nào*, được bàn trước khi viết code. **ADR** ghi lại *một quyết định* và lý do, giữ mãi. Một đề xuất lớn thường sinh ra một hoặc vài ADR.

## Trình bày và xin duyệt một ý tưởng

1. **Bắt đầu từ vấn đề**, không phải giải pháp: một đoạn mà người không đọc code cũng hiểu.
2. **Phác trước, viết dài sau.** Một sơ đồ khối hoặc 5 dòng luồng là đủ để kiểm tra hướng đi. Gửi sớm: sửa 10 phút lúc này đỡ nhiều ngày về sau.
3. **Ít nhất hai phương án** kèm đánh đổi, kể cả "không làm gì" nếu hợp lý, và nói rõ mình chọn cái nào, vì sao.
4. **Nêu rủi ro và cách quay lui.**
5. **Xin quyết định rõ ràng**, đúng người, có hạn ("anh chọn A hay B?").
6. **Ghi quyết định** vào ADR; link ADR từ đề xuất và từ tài liệu tính năng.

Ví dụ thật: chọn công nghệ cho trang public ([ADR-0019](../adr/0019-public-user-frontend.md)) có thử nhanh và đo RAM, dung lượng image, JS xuống trình duyệt trước khi chốt Astro.

## ADR

- Mỗi ADR một quyết định, đánh số `docs/adr/nnnn-<ten>.md`.
- Gồm: bối cảnh, quyết định, các phương án đã xét, hệ quả (tốt và xấu).
- Trạng thái: Đề xuất → Chấp nhận; về sau Bị thay thế. ADR đã chấp nhận **không sửa nội dung**: viết ADR mới thay nó, ADR cũ chỉ thêm một dòng "bị thay bởi ADR-…".
- Mọi công cụ, ngôn ngữ, thư viện lớn hay thành phần hạ tầng mới phải có ADR nêu vấn đề nó giải quyết.

## Sơ đồ

| Sơ đồ | Dùng khi | Công cụ |
|---|---|---|
| Khối hệ thống | Hệ thống nằm ở đâu, có những dịch vụ nào | Mermaid `flowchart` hoặc khối chữ |
| Quan hệ bảng | Bảng, khóa, quan hệ | Mermaid `erDiagram` |
| Trình tự | Một request đi qua nhiều phần (đăng nhập, upload, hàng đợi) | Mermaid `sequenceDiagram` |
| Trạng thái | Vòng đời một bản ghi (nháp → công khai → thùng rác) | Mermaid `stateDiagram-v2` |
| Hợp đồng API | Endpoint, dữ liệu gửi / nhận, lỗi | `laravel-api/openapi.json` (sinh bằng `make openapi`) |

Quy tắc: mỗi sơ đồ trả lời một câu hỏi; dưới khoảng 15 khối; đặt ngay cạnh phần chữ giải thích nó.

## Làm việc với trưởng nhóm kỹ thuật (Tech Lead, kiến trúc sư)

| Tình huống | Developer có kinh nghiệm làm gì | Hay sai ở đâu |
|---|---|---|
| Có ý tưởng thiết kế | Gửi bản phác sớm, hỏi "hướng này có sai không?" trước khi viết chi tiết | Viết xong cả tài liệu dài hoặc cả code mới đưa xem, bị bác thì mất trắng |
| Muốn dùng thư viện / công nghệ mới | Nêu vấn đề nó giải quyết, cái đang có thiếu gì, chi phí duy trì, cách bỏ đi nếu không hợp | Thêm vào vì "đang hot", không ai khác biết cách dùng |
| Trưởng nhóm bác phương án | Hỏi lý do để hiểu tiêu chí, ghi vào ADR; nếu có số liệu mới thì đưa lại một lần, không cãi vòng vo | Làm theo phương án cũ "cho nhanh" |
| Thấy thiết kế hiện tại có vấn đề | Ghi vấn đề cụ thể (ví dụ, số đo, lỗi đã gặp) và đề xuất sửa từng bước | Tự viết lại cả phần trong một lần mà không báo |
| Phải đánh đổi nhanh / đúng | Nói rõ khoản "nợ" mình đang vay, ghi thành việc để trả sau | Làm tạm rồi quên, khoản nợ thành lỗi về sau |
| Được hỏi ước tính cho thiết kế chưa rõ | Đưa khoảng và phần chưa chắc: "3–5 buổi; nếu phải chuyển dữ liệu cũ thì thêm 2" | Đưa một con số rồi bị giữ chặt vào nó |
