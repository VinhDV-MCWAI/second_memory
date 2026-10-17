# ADR-0015 — Hoãn người dùng cuối, phòng ban, chính sách phòng ban

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-14, chủ dự án xác nhận). Phần phòng ban và chính sách được làm lại theo [ADR-0017](0017-departments-folder-policies.md) (2026-10-17); người dùng cuối vẫn để sau |
| Ngày | 2026-10-14 |
| Người quyết định | Chủ dự án |
| Liên quan | [ADR-0012](0012-restore-features-dev-first.md) (danh sách tính năng cần lấy lại), [ADR-0013](0013-dynamic-permissions.md) (phân quyền động) |

## Bối cảnh

[ADR-0012](0012-restore-features-dev-first.md) đưa "quản lý người dùng cuối, phòng ban, chính sách phòng ban" vào danh sách tính năng cần lấy lại (giai đoạn 4 của lộ trình). Khi đọc lại bản `v1.0.0` để đề xuất thiết kế, thấy:

| Tính năng | Bản `v1.0.0` có | Điểm cần biết khi làm lại |
|---|---|---|
| Người dùng cuối (`user_mgmt`) | Hồ sơ: họ tên, email, mật khẩu, địa chỉ, ngày sinh, giới tính, trạng thái, ảnh; màn hình thêm/sửa/xóa | Không có chỗ nào cho người dùng cuối đăng nhập nên mật khẩu không dùng vào đâu; email chỉ 30 ký tự; ngày lưu dạng chữ |
| Phòng ban (`department_mst`, `admin_department_mst`) | Mã, tên, trạng thái; gán admin vào phòng ban | Ổn, chỉ cần viết lại |
| Chính sách phòng ban (`policy_department_mst`, `department_management_mst`) | Mỗi chính sách là cặp "(bảng, dòng)" gắn cho phòng ban; trigger cho phòng ban `root` nhận mọi chính sách; view `admin_policy_view` | **Chưa từng được áp dụng**: không truy vấn nào dùng view đó, nên chính sách chỉ được lưu, không chặn gì |

Các bảng này đã bị xóa từ 2026-10-07 (`2026_10_07_100002_drop_user_mgmt_tables`, `2026_10_07_100003_drop_department_policy_tables`); không còn dữ liệu cũ.

## Quyết định

**Hoãn cả ba tính năng.** Phạm vi hiện tại chỉ cần phân quyền theo vai trò: mỗi API là một quyền, vai trò gom quyền, admin nhận vai trò ([ADR-0013](0013-dynamic-permissions.md)). Chủ dự án sẽ phát triển phần này sau.

## Khi làm lại

Bắt đầu từ đề xuất đã soạn ngày 2026-10-14, gồm các câu hỏi còn mở:

1. Người dùng cuối chỉ là hồ sơ (không mật khẩu), hay có đăng nhập riêng (một kiểu đăng nhập mới, tách khỏi admin)?
2. Chính sách phòng ban có chặn dữ liệu thật không? Phương án đã cân nhắc: (A) chỉ lưu như bản cũ; (B) chặn thật, bắt đầu từ thư mục trong Files: admin chỉ thấy thư mục mà phòng ban mình được cấp, Chủ thấy tất cả, admin không thuộc phòng ban nào và thư mục chưa có chính sách thì không bị giới hạn; (C) bỏ chính sách, phòng ban mang vai trò.
3. Người dùng cuối có thuộc phòng ban không?
4. Bảng mới theo quy ước hiện tại: `end_user`, `department`, `admin_department`, `department_policy`; nhật ký dùng `audit_log` chung, không tạo bảng `_hist`; ảnh đại diện chọn từ Files.

## Hệ quả

- Lộ trình bỏ qua giai đoạn 4, đi tiếp sang CI/CD gộp chung.
- Việc "Quản lý người dùng cuối" và "Phòng ban, chính sách" ở lại bảng việc nhưng bị chặn với lý do "để sau", không ai nhận nhầm.
