# ADR-0023 — Danh mục tối đa 5 cấp, sắp xếp bằng kéo thả

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-25, chủ dự án chọn "tối đa 5 cấp" và "thêm @dnd-kit") |
| Ngày | 2026-10-25 |
| Người quyết định | Chủ dự án |
| Liên quan | Sửa [ADR-0022](0022-trang-tai-lieu-kinh-nghiem.md) ở dòng `content_category` (trước: tối đa 2 cấp) |

## Bối cảnh

ADR-0022 giới hạn danh mục 2 cấp (nhóm → danh mục). Khi thử trang quản trị, chủ dự án thấy danh mục sẽ còn nhiều và cần chia nhỏ hơn (ví dụ Kỹ năng → Backend → Laravel → Hàng đợi), đồng thời muốn sắp xếp cây bằng kéo thả thay vì chỉ nút ↑ ↓.

## Quyết định

| Phần | Quyết định |
|---|---|
| Số cấp | Tối đa **5 cấp** (`ContentConst::CATEGORY_DEPTH_MAX`), cấp trên cùng tính là 1. Chuyển một danh mục thì cả nhánh bên dưới đi theo; nhánh làm cây sâu quá 5 cấp thì bị chặn (lỗi E0055) |
| Chặn vòng | Danh mục không nằm dưới chính nó hay dưới một danh mục con cháu của nó (lỗi E0048) |
| Bảng | Không đổi: `content_category.parent_id` có sẵn. Không cần migration |
| Công khai | Danh mục hiện khi nó và **mọi cấp phía trên** công khai (giống cách bài viết xét bài cha) |
| Trang danh mục public | Liệt kê bài của chính nó và của **mọi danh mục con cháu** đang hiện. API trả `ancestors` (chuỗi danh mục từ trên cùng tới cấp cha) thay cho `group`, để hiện đủ đường dẫn phía trên trang |
| Trang chủ public | Giữ như cũ: nhóm (cấp 1) → danh mục cấp 2. Cấp sâu hơn mở từ trang danh mục |
| Trang quản trị | Danh sách là cây thu gọn / mở rộng; kéo thả để đổi thứ tự và đổi cấp (thư viện **@dnd-kit**, chỉ ở trang quản trị); nút ↑ ↓ vẫn còn để dùng bằng bàn phím. Chọn danh mục cha, chọn danh mục cho bài viết bằng ô chọn dạng cây có tìm kiếm |

## Hệ quả

| Tốt | Không tốt |
|---|---|
| Chia nhỏ được chủ đề mà không đổi bảng; quy tắc giống cây bài viết nên dùng chung code | Truy vấn đệ quy ở mỗi lần xét công khai (vài chục danh mục: không đáng kể) |
| Kéo thả nhanh khi cây lớn | Thêm một thư viện cho trang quản trị (~30 KB) |
| | Trang chủ chỉ hiện 2 cấp đầu: cấp sâu hơn phải mở trang danh mục mới thấy |
