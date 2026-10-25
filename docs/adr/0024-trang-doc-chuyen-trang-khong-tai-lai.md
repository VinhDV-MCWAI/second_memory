# ADR-0024 — Trang đọc 4 cột chuyển trang không tải lại bằng công cụ có sẵn của Astro; lượt đọc thật; nhớ chỗ đang đọc

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-25, chủ dự án đồng ý đề xuất và đổi DB) |
| Ngày | 2026-10-25 |
| Người quyết định | Chủ dự án |
| Liên quan | [REQ-006](../requirements/REQ-006-trang-chu-va-trang-doc.md). Sửa [ADR-0019](0019-public-user-frontend.md) và [ADR-0022](0022-trang-tai-lieu-kinh-nghiem.md) ở quy tắc "trang public gần như không có JavaScript" và cách đếm lượt xem |

## Bối cảnh

Trang public chạy Astro, máy chủ dựng HTML, chỉ ~3 KB JavaScript. Mỗi lần bấm link là tải lại cả trang: nháy trắng, mất chỗ cuộn của menu trái. Chủ dự án muốn trang đọc 4 cột mượt như trang tài liệu của Next.js nhưng vẫn giữ Astro (nhẹ, tốt cho SEO). Lượt xem hiện tính mỗi lần máy chủ dựng trang, nên bấm nhầm và máy tìm kiếm cũng được tính.

## Các cách đã xét

| Cách | Mượt | SEO | JavaScript | Ghi chú |
|---|---|---|---|---|
| Giữ tải lại trang | Không | Tốt | ~3 KB | Mất chỗ cuộn cột trái |
| Hiệu ứng chuyển trang của trình duyệt (CSS, không JS) | Một phần | Tốt | 0 | Firefox chưa hỗ trợ đủ; vẫn tải lại nên mất chỗ cuộn |
| **`ClientRouter` có sẵn trong Astro 7** | Có | Tốt | +16,4 KB (5,6 KB nén) | Không thêm thư viện; giữ được phần tử giữa hai trang (`transition:persist`); tải trước khi rê chuột |
| Ứng dụng một trang (React / Next.js) | Có | Phải làm thêm | Hàng trăm KB | Viết lại trang, bỏ Astro |

Đo 2026-10-25 bằng bản build production (bản sao tạm, đã xóa): không có `ClientRouter` tổng JavaScript cả site 7,1 KB; có thì 23,5 KB. HTML máy chủ trả về vẫn đủ tiêu đề và nội dung bài.

## Quyết định

| Phần | Quyết định |
|---|---|
| Chuyển trang | Bật `<ClientRouter />` trong layout trang public. Cột 1 (cây danh mục) đánh dấu `transition:persist` để giữ chỗ cuộn và nhánh đang mở. Tải trước theo mặc định của Astro (rê chuột). Không có JavaScript thì link vẫn là link thường |
| Code chạy trên trình duyệt | TypeScript thường, không thêm framework. Mọi script khởi động lại ở sự kiện `astro:page-load` (không chỉ khi tải trang lần đầu). Lọc trong cột, tìm trong bài, mục lục sáng (`IntersectionObserver`), thu gọn cột (nhớ bằng `localStorage`, có try/catch) |
| Giới hạn | JavaScript trang đọc dưới 30 KB chưa nén; đo lại ở READ-06 |
| Lượt đọc thật (bài) | Trình duyệt báo khi tab đang hiện đủ `min(15 giây, 30% thời gian đọc ước tính ~200 chữ/phút)` **và** đã cuộn ≥ 25% bài (bài không cần cuộn chỉ xét thời gian). Gửi `navigator.sendBeacon` tới một endpoint của Astro; Astro tính mã người xem (sha256 IP + trình duyệt, như cũ) rồi gửi API. Bỏ việc đếm khi máy chủ dựng trang. Bot không chạy JavaScript nên không được tính. Số cũ giữ nguyên |
| Lượt xem danh mục | Tab đang hiện trang đọc của danh mục ≥ 10 giây. Cột lượt xem mới trên `content_category`. Đọc bài không cộng vào danh mục |
| Không đếm trùng | 1 người, 1 bài hoặc danh mục, 30 phút tính 1 (khóa Redis như hiện nay, thêm tiền tố cho danh mục) |
| ☆ danh mục | `end_user_saved` thêm `content_category_id` (xóa danh mục thì dòng đã lưu mất theo). Số ☆ của danh mục chỉ là số người ☆ chính danh mục |
| Nhớ chỗ đang đọc | Chỉ cho 🔖 của bài. `end_user_saved` thêm mã neo của tiêu đề đang đọc và lúc cập nhật. Trình duyệt gửi khi đổi mục, tối đa 1 lần / 10 giây, và khi rời trang. Mở bài từ danh sách đã lưu thì cuộn tới neo; neo không còn thì từ đầu bài kèm dòng báo |
| Bài xóa / riêng tư | Xóa bài: dòng đã lưu mất theo (khóa ngoại đã có). Riêng tư: ẩn khỏi danh sách, giữ dòng; công khai lại thì hiện lại |
| Tìm kiếm | API trả thêm loại kết quả (danh mục, bài viết, mục trong bài, nội dung) và đoạn trích quanh từ khóa. Cần chỉ mục mới thì hỏi chủ dự án trước |

## Ghi chú khi làm (READ-04, 2026-10-25)

| Điểm | Đã làm | Vì sao |
|---|---|---|
| Chỗ bật `ClientRouter` | Chỉ trong layout trang đọc (`Reader.astro`), không trong layout chung | Trang chủ, tìm kiếm, tài khoản giữ 0 KB JavaScript riêng và không phải viết lại script; Astro tự tải cả trang khi trang đích không có công cụ chuyển trang. Ô tìm ở đầu trang có `data-astro-reload` vì form không được Astro tự tải lại như link |
| Mục lục sáng | Theo vị trí cuộn (tiêu đề cuối cùng đã qua mép trên cửa sổ), không dùng `IntersectionObserver` | Đúng cả khi giữa hai tiêu đề có đoạn dài hơn màn hình |
| Cột cuộn độc lập | Cột 3 cuộn theo cửa sổ, cột 1, 2, 4 dính (`sticky`) và tự cuộn | Giống trang tài liệu của Next.js; link tới `#mục`, nút Back và điện thoại chạy như trang thường |
| Giữ chỗ cuộn cột 1 | Script lưu trước khi đổi trang, đặt lại sau đó | Astro chỉ giữ được trên trình duyệt có `moveBefore()` |

## Hệ quả

| Tốt | Không tốt |
|---|---|
| Chuyển bài mượt, cột trái giữ nguyên, vẫn giữ Astro và SEO | JavaScript trang public từ ~3 KB lên ~25 KB |
| Không thêm thư viện | Script phải viết theo kiểu chạy lại sau mỗi lần chuyển trang; sót là lỗi khó thấy (có test trình duyệt) |
| Lượt xem sát với người đọc thật, bot tự bị loại | Người tắt JavaScript không được tính lượt đọc; số mới không so được với số cũ |
| Người dùng đọc tiếp đúng chỗ | Thêm ghi DB khi đọc bài đã 🔖 (giới hạn 1 lần / 10 giây) |
