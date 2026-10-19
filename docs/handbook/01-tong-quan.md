# 1. Tổng quan: hai tốc độ, ai làm gì

## Hai tốc độ làm việc

Quy trình đầy đủ chỉ dành cho việc lớn. Bắt việc nhỏ đi đủ quy trình là cách nhanh nhất để quy trình biến thành hình thức.

| Cỡ việc | Ví dụ | Phải có |
|---|---|---|
| **Nhỏ** | Sửa chữ, sửa một lỗi rõ ràng, đổi tên biến, nâng phiên bản thư viện nhỏ | Commit có message rõ, test của phần vừa sửa pass. Bỏ qua bước đề xuất |
| **Thường** | Thêm một API, một màn hình, một cột DB | Đề xuất theo mẫu trong [01-goals-and-workflow.md](../plan/01-goals-and-workflow.md) → chủ dự án xác nhận → làm → test → cập nhật `docs/features/` → một dòng trong ghi chú phát hành |
| **Lớn** | Tính năng mới cả cụm, công nghệ mới, xóa một phần ứng dụng, đổi đăng nhập / phân quyền / cấu trúc DB | Như việc thường, thêm: hồ sơ yêu cầu `REQ-…` ([chương 2](02-yeu-cau.md)), ADR ([chương 3](03-thiet-ke.md)), kế hoạch test và nghiệm thu ([chương 5](05-test-nghiem-thu.md)), phát hành có tag ([chương 6](06-phat-hanh-ban-giao.md)), nhìn lại |
| **Sự cố** | Ứng dụng hỏng, mất dữ liệu, lộ thông tin | Khắc phục trước, điều tra sau, báo cáo sự cố ([chương 7](07-van-hanh-tai-lieu.md)) |

Không chắc cỡ nào thì chọn cỡ lớn hơn một bậc. Những việc trong mục "luôn phải xác nhận" của [01-goals-and-workflow.md](../plan/01-goals-and-workflow.md) tối thiểu là việc thường.

Ví dụ thật của việc lớn đi đủ vòng đời: tài khoản người dùng trên trang public, [REQ-003](../requirements/REQ-003-tai-khoan-nguoi-dung.md) → [ADR-0020](../adr/0020-end-user-accounts.md) → các việc PUB-03, PUB-04 → bản `v2.3.0`.

## Ai làm gì trong dự án này

Dự án có một người là chủ dự án, làm cùng Claude.

| Vai trong doanh nghiệp | Ở đây là | Quyết gì |
|---|---|---|
| Người đặt yêu cầu, chủ sản phẩm (Product Owner) | Chủ dự án | Làm gì, vì sao, việc nào trước; nhận hay trả lại một tính năng |
| Trưởng nhóm kỹ thuật (Tech Lead) | Chủ dự án duyệt, Claude đề xuất | Làm thế nào: phương án, công nghệ, ADR |
| Developer | Claude (chủ dự án có thể làm cùng) | Code và test của phần mình làm |
| Người kiểm thử (QA) | Claude viết kế hoạch test và chạy test tự động; chủ dự án thử trên trình duyệt | Chất lượng nhìn từ phía người dùng |
| Người vận hành (DevOps / Ops) | Chủ dự án | Môi trường, sao lưu, push, phát hành |
| Bảo mật | Chủ dự án, Claude nêu rủi ro trong đề xuất | Rủi ro và cách chặn |

Claude không bao giờ quyết thay chủ dự án ở các ô "Quyết gì" phía trên.

## Báo cái gì cho ai

Nguyên tắc: **báo sớm, báo bằng chữ ở nơi còn lưu lại, luôn kèm hướng xử lý đề xuất** chứ không chỉ nêu vấn đề.

| Tình huống | Báo ai | Ở đâu | Khi nào |
|---|---|---|---|
| Yêu cầu chưa rõ | Người đặt yêu cầu | Mục "Câu hỏi làm rõ" trong `REQ-…` hoặc trong đề xuất | Trước khi bắt đầu |
| Sẽ vượt ước tính hơn 50% | Người đặt yêu cầu + trưởng nhóm | Ghi chú của việc (`lane.sh block` nếu phải dừng) | Ngay khi biết |
| Bị chặn vì người / phần khác | Người đó | `scripts/lane.sh block <mã> "<lý do>"` | Trong ngày |
| Quyết định kỹ thuật có ảnh hưởng lâu dài | Trưởng nhóm | Đề xuất, rồi ADR | Trước khi làm |
| Thấy lỗi ở phần không phải của mình | Người phụ trách phần đó | `scripts/lane.sh add …` | Ngay |
| Ứng dụng đang hỏng | Người vận hành | Báo cáo sự cố | Ngay ([chương 7](07-van-hanh-tai-lieu.md)) |
| Nghi có lỗ hổng bảo mật | Bảo mật + trưởng nhóm | Ghi chú riêng, không kèm cách khai thác | Ngay |

## Làm việc với các vai: điều chung

Các chương sau có mục riêng cho từng vai. Mấy điều dùng chung cho mọi vai:

| Tình huống | Developer có kinh nghiệm làm gì | Hay sai ở đâu |
|---|---|---|
| Nhận việc qua lời nói, chat | Chép lại thành chữ ở nơi có lưu lại, gửi lại "tôi hiểu thế này, đúng không?" | Làm theo trí nhớ, tới lúc giao thì hai bên nhớ khác nhau |
| Bị hỏi "bao giờ xong?" | Trả lời bằng khoảng và điều kiện: "2–3 buổi, nếu câu hỏi 2 được trả lời trước thứ Tư" | Hứa một con số chắc chắn để làm vui lòng người hỏi |
| Không đồng ý với yêu cầu | Đưa phương án khác kèm đánh đổi và đề xuất của mình | Chỉ nói "cái này khó", hoặc im lặng làm theo ý mình |
| Mình làm sai | Báo ngay, kèm ảnh hưởng và cách sửa | Giấu, chờ người khác phát hiện |
| Cần người khác quyết | Hỏi rõ một câu, đúng người, có hạn: "Anh chọn A hay B trước thứ Sáu?" | Hỏi chung chung "anh thấy sao?" |
