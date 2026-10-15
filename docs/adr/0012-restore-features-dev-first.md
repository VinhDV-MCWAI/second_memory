# ADR-0012 — Quay lại nâng cấp: khôi phục tính năng, làm trên môi trường dev trước

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-09, chủ dự án xác nhận) |
| Ngày | 2026-10-09 |
| Người quyết định | Chủ dự án |
| Thay thế | ADR-0001 (hướng Engineering Lab), ADR-0002 (quy ước song ngữ), ADR-0005 (hai vai trò cố định), ADR-0007 (xóa media API); ADR-0003 chỉ còn hiệu lực cho phần CMS nội dung |

## Bối cảnh

Từ 2026-10-07 dự án đi theo hướng "Engineering Lab": thu gọn ứng dụng để luyện quy trình làm phần mềm. Trong giai đoạn thu gọn, các tính năng sau đã bị xóa:

| Tính năng | Bị xóa vì |
|---|---|
| Phân quyền động (vai trò → chức năng → API, lưu trong DB) | Cho rằng chỉ có một người dùng, không cần phân quyền chi tiết |
| Quản lý media trên MinIO, upload file nhỏ và upload file lớn chia phần | Một vai "Product Owner" do AI đóng giả trả lời "bằng chứng chỉ là link, không cần upload" |
| Quản lý người dùng cuối, phòng ban, chính sách phòng ban | Cho rằng không còn ai dùng |
| CMS nội dung, slider, banner, liên kết, mạng xã hội | Việc viết nội dung đã chuyển sang Obsidian |

Ngày 2026-10-09 chủ dự án đánh giá: phần nâng cấp chất lượng code là tốt, nhưng việc xóa tính năng là **hạ cấp** so với mục tiêu của mình. Các quyết định xóa chỉ được duyệt chung chung, có quyết định dựa trên câu trả lời của vai giả lập chứ không phải của chủ dự án.

## Các phương án

1. **Giữ hướng Engineering Lab.** Không tốn công. Mất các tính năng chủ dự án coi trọng.
2. **Giữ nền code hiện tại, khôi phục các tính năng cần thiết.** Giữ được phần nâng cấp (Laravel 13, Sanctum, nhật ký thay đổi, test, Skill Ledger) và lấy lại tính năng. Tốn công viết lại cho khớp nền mới.
3. **Quay về bản `v1.0.0`.** Có đủ tính năng ngay. Mất toàn bộ phần làm sau đó.

## Quyết định

Chọn **phương án 2**. Không quay lui code, không cần sao lưu riêng các thay đổi hiện tại: coi như bắt đầu nâng cấp lại từ đây.

| Hạng mục | Quyết định |
|---|---|
| Đăng nhập | Giữ Sanctum (session cookie) |
| Phân quyền động | Khôi phục, làm bằng thư viện `spatie/laravel-permission`: vai trò và quyền lưu trong DB, gán quyền theo chức năng và theo API, có màn hình cấu hình |
| Media MinIO + upload | Khôi phục: thư mục, upload file nhỏ, upload file lớn chia phần, file tạm, tiến trình upload |
| Người dùng cuối, phòng ban, chính sách | Khôi phục |
| CMS nội dung, slider, banner, liên kết, mạng xã hội | Không khôi phục |
| Skill Ledger, nhật ký thay đổi (`audit_log`), tìm kiếm | Giữ |
| Môi trường | Chỉ làm trên **dev** cho tới khi sản phẩm hoàn thiện. Staging, production, Terraform, giám sát, đo tải tạm dừng (giữ code, không phát triển tiếp) |
| CI/CD | Gộp thành một bộ script dùng được trên nhiều nền tảng, test xong thì để **tắt** tới giai đoạn cuối |
| Tài liệu | Chỉ tiếng Việt, ngôn ngữ dễ hiểu, gọi tính năng bằng tên đầy đủ thay vì mã |
| Cách làm việc | Yêu cầu → đề xuất có lợi ích và tác hại → chủ dự án xác nhận → làm → test nhanh. Không xóa tính năng nào khi chưa có xác nhận cụ thể. Không dùng câu trả lời của vai giả lập để quyết định thật |

## Hệ quả

- Kế hoạch mới ở [plan/](../plan/). Kế hoạch Engineering Lab cũ chuyển vào [archive/engineering-lab/](../archive/engineering-lab/).
- Code của các tính năng cũ lấy từ tag `v1.0.0` làm tài liệu tham khảo, viết lại cho khớp nền hiện tại (Sanctum, nhật ký thay đổi chung, repository/service chung, OpenAPI sinh tự động).
- Bảng `media_mgmt` và dữ liệu MinIO vẫn còn nguyên, nên tính năng media có thể dùng lại bảng cũ.
- Phải bật lại hàng đợi xử lý nền (queue) cho upload file lớn. Có cần kết nối thời gian thực (Reverb) cho thanh tiến trình hay không sẽ quyết định khi làm tính năng media.
