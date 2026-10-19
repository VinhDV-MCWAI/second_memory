# 2. Nhận yêu cầu và làm rõ

Phần lớn việc phải làm lại đến từ việc bắt tay làm khi chưa hiểu yêu cầu. Bước này biến một mong muốn mơ hồ thành thứ developer làm được và người kiểm thử kiểm được.

## Các bước

```
Yêu cầu tới ─► Phân loại ─► Làm rõ ─► Viết câu chuyện người dùng + tiêu chí chấp nhận ─► Ước tính ─► Sẵn sàng? ─► Lên bảng việc
                              ▲                                                                       │ chưa
                              └───────────────────────────────────────────────────────────────────────┘
```

1. **Yêu cầu tới.** Một câu cũng được. Nhận qua chat thì chép lại thành chữ: việc thường vào đề xuất, việc lớn vào `docs/requirements/REQ-…`.
2. **Phân loại.** Trùng với việc đã có không? Cỡ nhỏ, thường hay lớn ([chương 1](01-tong-quan.md))? Ưu tiên sơ bộ.
3. **Làm rõ** bằng bộ câu hỏi bên dưới. Hỏi gộp một lần, đánh số để người trả lời chỉ cần ghi số. Câu trả lời ghi vào hồ sơ, không chỉ để trong chat.
4. **Viết câu chuyện người dùng và tiêu chí chấp nhận.**
5. **Ước tính** theo buổi (1 buổi ≈ 2 giờ). Việc hơn 6 buổi thì tách nhỏ.
6. **Kiểm tra "Sẵn sàng làm"**, rồi `scripts/lane.sh add` lên bảng việc.

## Hồ sơ yêu cầu (việc lớn)

File `docs/requirements/REQ-nnn-<ten>.md`. Mẫu thật: [REQ-003](../requirements/REQ-003-tai-khoan-nguoi-dung.md).

| Mục | Nội dung |
|---|---|
| 1. Vấn đề và mục tiêu | Giải quyết gì, cho ai, không làm thì sao, đo thành công bằng gì |
| 2. Phạm vi | Có gì, và **không có gì** |
| 3. Câu hỏi làm rõ | Câu hỏi đánh số, câu trả lời ghi ngay bên dưới, có ngày |
| 4. Câu chuyện người dùng và tiêu chí chấp nhận | Theo mẫu bên dưới |
| 5. Yêu cầu phi chức năng | Tốc độ, dung lượng, bảo mật, nhật ký |
| 6. Phân tích ảnh hưởng | Bảng, API, màn hình, quyền, tài liệu nào thay đổi |
| 7. Phương án | Các cách làm, đánh đổi, phương án đề xuất |
| 8. Sẵn sàng để làm khi | Danh sách kiểm bên dưới |
| 9. Ước tính và chia việc | Mã việc trên bảng, số buổi |

## Bộ câu hỏi làm rõ

Không cần hỏi hết. Chọn những câu mà câu trả lời còn chưa rõ.

| Chủ đề | Câu hỏi |
|---|---|
| Mục tiêu | Giải quyết vấn đề gì, cho ai? Không làm thì sao? Làm xong đo bằng gì là thành công? |
| Người dùng | Ai dùng, quyền gì? Bao lâu dùng một lần? Trên thiết bị nào? |
| Phạm vi | Cái gì chắc chắn **không** làm? Có bản nhỏ hơn mà vẫn dùng được không? |
| Hành vi | Luồng chính từng bước? Lỗi, dữ liệu rỗng, không có quyền thì sao? |
| Dữ liệu | Tạo, đọc, sửa, xóa dữ liệu gì? Lấy từ đâu? Giữ bao lâu? Có dữ liệu cá nhân không? |
| Quy tắc | Kiểm tra đầu vào, giới hạn, định dạng, múi giờ, làm tròn? |
| Kết nối | Hệ thống hay API nào khác liên quan? Ai phụ trách chúng? |
| Phi chức năng | Bao nhiêu dữ liệu, bao nhiêu người? Thời gian phản hồi? Cần nhật ký, bảo mật gì? |
| Ưu tiên | Hạn chót là khi nào, vì sao? Làm cái này trước thì cái gì phải lùi? |
| Nghiệm thu | Ai nhận? Nhận bằng cách nào (thử trên trình duyệt, danh sách kiểm, dữ liệu mẫu)? |

## Câu chuyện người dùng và tiêu chí chấp nhận

```text
Là <vai>, tôi muốn <làm được gì>, để <được lợi gì>.

Tiêu chí chấp nhận
  Tình huống: <tên>
    Cho trước <trạng thái>
    Khi       <hành động>
    Thì       <kết quả nhìn thấy được>
```

Tiêu chí tốt thì **nhìn thấy được** (người kiểm thử kiểm được mà không đọc code), có các trường hợp lỗi chính và có số cụ thể ("mật khẩu ít nhất 8 ký tự", không viết "mật khẩu đủ mạnh"). Thứ tự ("mới nhất lên đầu") và danh tính ("tên người đang đăng nhập") cũng là tiêu chí, phải viết ra.

## Sẵn sàng làm

Một việc chỉ được `claim` khi đạt hết:

- [ ] Có vấn đề và mục tiêu.
- [ ] Có phần "không làm".
- [ ] Có tiêu chí chấp nhận, và kiểm được.
- [ ] Câu hỏi mở đã được trả lời, hoặc ghi rõ là để sau.
- [ ] Phụ thuộc (việc khác, dữ liệu, quyền truy cập) đã có.
- [ ] Ước tính không quá 6 buổi.
- [ ] Việc lớn: phương án đã được duyệt.

## Xếp ưu tiên

- Trong một giai đoạn: chia **Phải có / Nên có / Có thì tốt / Lần này không làm**.
- So nhiều ý tưởng: chấm điểm **phạm vi ảnh hưởng × mức tác động × độ chắc chắn ÷ công sức**, ghi số ra để sau này còn xem lại lý do.

## Làm việc với người đặt yêu cầu (Product Owner, khách hàng)

| Tình huống | Developer có kinh nghiệm làm gì | Hay sai ở đâu |
|---|---|---|
| Yêu cầu chỉ có một câu ("làm cho người dùng đăng nhập được") | Hỏi mục tiêu trước giải pháp: "Đăng nhập để làm gì? Không đăng nhập thì mất gì?" Có khi câu trả lời cho thấy chỉ cần việc nhỏ hơn nhiều | Lao vào thiết kế bảng và API ngay |
| Người đặt yêu cầu đưa sẵn giải pháp ("thêm nút X ở đây") | Hỏi ngược về vấn đề phía sau; nếu có cách tốt hơn thì đưa phương án kèm đánh đổi, quyết định vẫn là của họ | Làm đúng nút X dù biết nó không giải quyết được vấn đề |
| Yêu cầu thay đổi giữa chừng | Ghi lại thay đổi, nói rõ ảnh hưởng tới thời gian và phần đã làm, hỏi "làm cái mới thì cái gì lùi?" | Âm thầm làm thêm, rồi trễ hạn mà không ai biết vì sao |
| Phạm vi cứ phình ra | Đề xuất tách: bản đầu nhỏ mà dùng được, phần còn lại thành việc sau | Cố nhét hết vào một lần giao |
| Câu trả lời mơ hồ ("tùy em") | Đưa một lựa chọn mặc định cụ thể và hỏi xác nhận: "Em làm A, anh không trả lời trước thứ Năm thì em làm A" | Coi "tùy em" là được làm gì cũng được, rồi bị trả lại |
| Không đồng ý về ưu tiên | Nêu số liệu và rủi ro, rồi tôn trọng quyết định của người đặt yêu cầu | Tự đổi thứ tự việc theo ý mình |
