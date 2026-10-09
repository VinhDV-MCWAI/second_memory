# Mục tiêu và cách làm việc

Cập nhật: 2026-10-09. Lý do đổi hướng: [ADR-0012](../adr/0012-restore-features-dev-first.md).

## Mục tiêu

Second Memory là hệ thống quản lý kiến thức cá nhân, tự host. Từ bây giờ:

1. **Ứng dụng phải đủ tính năng.** Nâng cấp là làm cho ứng dụng tốt hơn và làm được nhiều hơn, không phải ít đi.
2. **Code sạch, dễ hiểu.** Mỗi thư mục, mỗi file có lý do tồn tại rõ ràng.
3. **Làm nhanh trên dev.** Build nhanh, sửa lỗi nhanh, test nhanh. Staging và production làm sau, khi sản phẩm đã hoàn thiện.
4. **Tài liệu dễ đọc.** Viết tiếng Việt, gọi tính năng bằng tên đầy đủ.

## Luồng làm một việc

```
① Yêu cầu       Chủ dự án gửi yêu cầu (một câu cũng được)
      ↓
② Đề xuất       Claude trả lời theo mẫu "Đề xuất" bên dưới. Chưa sửa code.
      ↓
③ Xác nhận      Chủ dự án trả lời: đồng ý / sửa lại / bỏ
      ↓
④ Thực hiện     Làm trên môi trường dev, commit nhỏ theo từng bước
      ↓
⑤ Test nhanh    Test tự động của phần vừa sửa + chủ dự án thử trên trình duyệt
      ↓
   Xong         Ghi một dòng vào nhật ký, cập nhật tài liệu của tính năng
```

Việc nhỏ (sửa chữ, sửa một lỗi rõ ràng, đổi tên biến) được bỏ qua bước ② và ③.

Các việc sau **luôn** phải qua bước ③:
- xóa hoặc thay thế một tính năng;
- xóa hoặc di chuyển thư mục;
- thêm thư viện mới;
- đổi cách đăng nhập, phân quyền, cấu trúc DB.

Không dùng câu trả lời của vai giả lập (PO, QA, Ops do AI đóng) để quyết định thay chủ dự án.

## Mẫu "Đề xuất"

```markdown
### Đề xuất: <tên việc, viết đầy đủ>

**Làm gì:** 2–3 câu, người không đọc code cũng hiểu.

| | |
|---|---|
| Lợi ích (so với mục tiêu) | ... |
| Tác hại / rủi ro | ... |
| Thời gian ước tính | ... |
| Ảnh hưởng | Màn hình nào, API nào, bảng nào thay đổi |

**Các phương án** (nếu có nhiều cách): bảng so sánh, ghi rõ phương án Claude khuyên chọn và lý do.

**Cần chủ dự án quyết:** câu hỏi cụ thể.
```

## Test nhanh nghĩa là gì

| Loại thay đổi | Bắt buộc chạy |
|---|---|
| Sửa API | `make test f=<tên tính năng>` |
| Sửa giao diện | `make fe-test` + mở trình duyệt thử |
| Đổi route hoặc dữ liệu API trả về | thêm `make openapi` |
| Trước khi merge vào `developer` | `make verify` |

Lệnh chi tiết và cách xử lý lỗi: [dev-guide.md](../dev-guide.md).

## Việc tạm dừng

Những phần dưới đây giữ nguyên code, không phát triển tiếp cho tới giai đoạn cuối:

| Phần | Thư mục | Lý do dừng |
|---|---|---|
| Dựng staging / production bằng Terraform | `infra/` | Chỉ cần khi sản phẩm hoàn thiện |
| Đo tải, đo hiệu năng | `perf/` | Đo sau khi có đủ tính năng |
| CI/CD tự động trên GitHub | `.github/workflows/`, `ci-cd/` | Chạy tự động thì làm chậm việc merge khi làm một mình; giai đoạn 5 sẽ gộp lại, test rồi để tắt |
| Giám sát, mô phỏng sự cố | (chưa có) | Làm sau production |

## Quy ước viết tài liệu

1. **Chỉ tiếng Việt.** Tên code, lệnh, tên file và thuật ngữ không có từ tiếng Việt quen thuộc thì giữ tiếng Anh.
2. **Gọi tên đầy đủ.** Viết "phân quyền động", "upload file lớn", không viết "P2-12" hay "slice 8". Nếu cần dẫn mã thì đặt sau tên: "phân quyền động (ADR-0012)".
3. **Ngắn, có bảng.** Ưu tiên bảng và so sánh trước/sau thay cho đoạn văn dài.
4. **Code là sự thật.** Tài liệu lệch với code thì sửa tài liệu.
5. **Mỗi tính năng một file** trong `docs/features/`: nó làm gì, màn hình nào, API nào, bảng nào, quyền nào được dùng.

Các tài liệu cũ (tiếng Anh + dòng `> 🇻🇳`) vẫn giữ. Khi sửa một tài liệu cũ thì viết lại theo quy ước mới.
