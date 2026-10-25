# REQ-006 — Trang chủ tóm tắt, trang đọc 4 cột, tìm kiếm kiểu Obsidian

| | |
|---|---|
| Người yêu cầu | Chủ dự án |
| Ngày nhận | 2026-10-25 |
| Cỡ việc | Lớn ([sổ tay chương 1](../handbook/01-tong-quan.md)): đổi giao diện trang public, đổi cấu trúc DB, thêm API public, thêm JavaScript cho trang public |
| Trạng thái | Đã làm rõ qua 3 lần đề xuất, chủ dự án đồng ý toàn bộ ngày 2026-10-25 (kể cả đổi DB) |
| Thiết kế | [ADR-0024](../adr/0024-trang-doc-chuyen-trang-khong-tai-lai.md) |

## 1. Yêu cầu gốc (tóm tắt lời chủ dự án)

1. Trang public (xem không cần đăng nhập) làm lại thành **hai giao diện riêng**: trang chủ chỉ để tóm tắt, và trang đọc nội dung.
2. Trang chủ: phần danh mục là một loạt thẻ (badge), sắp xếp được theo yêu thích nhiều nhất, xem nhiều nhất; phần bài viết chia tab theo lượt xem, yêu thích. Cả hai có "Xem thêm".
3. Bấm danh mục, bài viết, "Xem thêm" hay kết quả tìm kiếm thì sang trang đọc, giống trang tài liệu của Next.js: 4 cột cuộn độc lập.
   - Cột 1: cây danh mục; bấm danh mục thì cột 2 hiện bài của nó.
   - Cột 2: bài của danh mục đang chọn.
   - Cột 3: nội dung bài (Markdown), trên đầu là đường dẫn kiểu `Nhóm > Danh mục > Bài`.
   - Cột 4: mục lục theo tiêu đề trong bài; bấm thì cuộn tới, đang đọc tới đâu thì tiêu đề đó sáng lên.
   - Cột 1, 2, 4 thu gọn được; mỗi cột có ô tìm theo chữ của chính cột đó.
4. Chuyển trang phải mượt, không khó chịu như tải lại cả trang, nhưng **vẫn giữ Astro** vì trang chỉ để đọc tài liệu và đánh dấu bài; giữ ưu điểm nhẹ và tốt cho SEO.
5. Tìm kiếm như Obsidian: tìm theo từ khóa trong danh mục, bài viết và nội dung bài; một danh sách, mỗi dòng có nhãn cho biết là loại gì, có đoạn chứa từ khóa.
6. Yêu thích và lượt xem có cho **cả danh mục và bài viết**, đếm riêng. Lượt xem phải là người đọc thật (ở lại, có thao tác), không tính lướt qua hay bấm nhầm.
7. "Lưu đọc lại" chỉ cho bài viết và **nhớ mục (tiêu đề `##`) đang đọc**: lần sau mở lại thì vào bài đó và cuộn tới mục đó; trong lúc đọc tự cập nhật. Mục không còn (bài đã sửa) thì đọc từ đầu bài. Bài bị xóa thì bỏ khỏi danh sách của mọi người; bài chuyển sang riêng tư thì chỉ ẩn, không xóa.

## 2. Câu hỏi làm rõ và trả lời (2026-10-25)

| # | Câu hỏi | Trả lời |
|---|---|---|
| 1 | Trang 4 cột thay trang cũ hay là trang riêng? | Hai giao diện riêng: trang chủ tóm tắt; bấm vào danh mục, bài, kết quả tìm kiếm thì sang trang đọc |
| 2 | Bấm link: tải lại trang hay đổi tại chỗ? | Phải mượt cho người dùng nhưng giữ Astro; nhờ nghiên cứu → chọn công cụ chuyển trang có sẵn của Astro (mục 5, ADR-0024) |
| 3 | Thêm JavaScript cho trang public? | Đồng ý ở mức cần cho việc đọc (đã đo: mục 5) |
| 4 | "Yêu thích" dùng nút ☆ đang có? | Có, để sắp xếp / lọc; lượt xem tính riêng theo người đọc thật |
| 5 | "Xem thêm" của bài viết mở đâu? | Trang đọc ở bài đầu tiên của tab đang chọn |
| 6 | ☆ và lượt xem cho danh mục? | Có, đếm riêng với bài viết |
| 7 | "Nhớ chỗ đang đọc" theo bài hay theo danh mục? | Theo bài: mục `##` gần nhất của bài đã bấm 🔖 |
| 8 | Bài chuyển sang riêng tư thì sao với danh sách đã lưu? | Ẩn, không xóa |
| 9 | Đổi cấu trúc DB? | Đồng ý |

## 3. Phạm vi

| Có | Không |
|---|---|
| Trang chủ tóm tắt: thẻ danh mục (Theo thứ tự / Xem nhiều / Yêu thích nhiều), tab bài (Đọc nhiều / Yêu thích nhiều / Mới nhất), ô tìm kiếm lớn | Bình luận, chấm điểm |
| Trang đọc 4 cột ở `/categories/{slug}` và `/articles/{slug}`, chuyển trang không tải lại | Đổi bài tại chỗ bằng gọi API từ trình duyệt (vẫn là trang do máy chủ dựng) |
| Ô tìm từng cột, thu gọn cột (nhớ trên trình duyệt), mục lục sáng theo chỗ đọc | Ghi nhớ thu gọn cột theo tài khoản |
| ☆ và lượt xem cho danh mục; lượt đọc thật cho bài | Thống kê theo người xem, lưu ai đã đọc |
| 🔖 nhớ mục đang đọc, tự cập nhật | "Đọc tiếp" theo danh mục |
| Tìm kiếm có nhãn và đoạn trích | Tìm trong file đính kèm |

## 4. Câu chuyện người dùng và tiêu chí chấp nhận

```text
Là người xem, tôi muốn trang chủ cho thấy ngay chủ đề và bài được quan tâm.

Tình huống: trang chủ
  Khi tôi mở http://localhost:81/
  Thì tôi thấy các thẻ danh mục kèm số bài, đổi được cách xếp: Theo thứ tự, Xem nhiều, Yêu thích nhiều
  Và các tab bài viết Đọc nhiều, Yêu thích nhiều, Mới nhất (mỗi tab 10 bài)
  Khi tôi bấm "Xem thêm" ở tab bài viết
  Thì trang đọc mở ở bài đầu tiên của tab đó
```

```text
Là người xem, tôi muốn đọc như trang tài liệu mà không mất chỗ khi chuyển bài.

Tình huống: chuyển bài
  Khi tôi bấm một bài ở cột 2
  Thì cột 3, cột 4 đổi sang bài đó mà trang không nháy trắng; cột 1 giữ nguyên chỗ cuộn và nhánh đang mở
  Và địa chỉ trên thanh trình duyệt đổi theo, nút Back quay về bài trước

Tình huống: mục lục
  Khi tôi cuộn tới mục "Cài đặt"
  Thì "Cài đặt" sáng lên ở cột 4; bấm mục khác ở cột 4 thì cột 3 cuộn tới mục đó

Tình huống: không có JavaScript
  Khi trình duyệt tắt JavaScript
  Thì vẫn đọc được: link chuyển trang bằng cách tải lại, mục lục vẫn bấm được
```

```text
Là người xem, tôi muốn tìm cả trong nội dung bài.

Tình huống
  Khi tôi tìm "hàng đợi"
  Thì mỗi kết quả có nhãn Danh mục / Bài viết / Mục trong bài / Nội dung và một đoạn có "hàng đợi" được tô đậm
  Khi tôi bấm một kết quả "Mục trong bài"
  Thì trang đọc mở bài đó và cuộn tới đúng mục
```

```text
Là người dùng đã đăng nhập, tôi muốn đọc tiếp đúng chỗ đã dừng.

Tình huống: nhớ chỗ đang đọc
  Biết rằng tôi đã bấm 🔖 ở bài "Laravel thực chiến"
  Khi tôi đọc tới mục "Hàng đợi" rồi rời trang
  Và sau đó mở bài từ danh sách "Của tôi"
  Thì trang đọc cuộn tới mục "Hàng đợi"

Tình huống: mục không còn
  Biết rằng chủ dự án đã sửa bài, bỏ mục "Hàng đợi"
  Thì bài mở từ đầu kèm dòng báo "Mục bạn đọc lần trước không còn"

Tình huống: bài bị xóa / chuyển sang riêng tư
  Thì bài biến khỏi danh sách đã lưu; riêng tư rồi công khai lại thì bài quay lại với chỗ đọc cũ
```

```text
Là chủ dự án, tôi muốn con số lượt xem phản ánh người đọc thật.

Tình huống
  Khi một người mở bài rồi rời đi sau 3 giây
  Thì lượt đọc không tăng
  Khi một người ở lại đủ lâu (tab đang mở) và cuộn ít nhất 25% bài
  Thì lượt đọc tăng 1; mở lại trong 30 phút vẫn chỉ tính 1
```

## 5. Yêu cầu phi chức năng

| Mặt | Yêu cầu |
|---|---|
| Nhẹ | JavaScript trên trang đọc dưới 30 KB (chưa nén). Đo 2026-10-25 trên bản build: công cụ chuyển trang của Astro 16,4 KB (5,6 KB nén); trang bài hiện có ~3 KB; tổng ước tính ~25 KB sau khi thêm code đọc |
| SEO | Mỗi đường dẫn vẫn trả HTML đầy đủ do máy chủ dựng (đã thử 2026-10-25: tiêu đề, nội dung bài có trong HTML khi bật công cụ chuyển trang) |
| Không JavaScript | Đọc được, chuyển trang bằng tải lại |
| Riêng tư | Lượt xem chỉ lưu con số; mục đang đọc chỉ lưu cho người đã bấm 🔖 |
| Tải máy chủ | Cập nhật chỗ đang đọc tối đa 1 lần / 10 giây / người, chỉ khi đổi mục |

## 6. Phân tích ảnh hưởng

| Phần | Thay đổi |
|---|---|
| DB | `content_category` thêm lượt xem; `end_user_saved` thêm danh mục (☆ danh mục), mục đang đọc và lúc cập nhật |
| API public | Trang chủ trả thêm số xem / ☆ của danh mục và danh sách bài cho các tab; cây danh mục đầy đủ; nhận lượt đọc thật của bài và lượt xem danh mục; tìm kiếm trả thêm loại kết quả và đoạn trích. Chỉ thêm trường, không đổi trường cũ |
| API người dùng | ☆ danh mục; lưu / cập nhật mục đang đọc của 🔖 |
| Trang public | Trang chủ mới; trang đọc 4 cột thay trang danh mục và trang bài hiện có (giữ đường dẫn); công cụ chuyển trang của Astro; script ☆ 🔖 và link tài khoản chạy lại sau mỗi lần chuyển trang |
| Trang quản trị | Trang tổng quan: số liệu lượt đọc theo cách tính mới (không thêm màn hình) |
| Test, tài liệu | Test backend cho API mới; test trình duyệt trang public viết lại; `docs/features/trang-public.md`, `tim-kiem.md`, `nguoi-dung-cuoi.md` |

## 7. Sẵn sàng để làm khi

- [x] Mục tiêu và phạm vi đã ghi
- [x] Tiêu chí chấp nhận kiểm tra được
- [x] Chủ dự án trả lời mục 2
- [x] ADR-0024, có số đo

## 8. Kế hoạch test

| Tiêu chí | Test |
|---|---|
| Số xem, ☆ của danh mục; danh sách bài cho tab; cây danh mục | Test backend API public |
| Lượt đọc thật không đếm trùng trong 30 phút; lượt xem danh mục | Test backend |
| Mục đang đọc: lưu, cập nhật, bài riêng tư bị ẩn, bài xóa thì mất | Test backend API người dùng |
| Tìm kiếm có loại kết quả, đoạn trích | Test backend |
| Trang chủ, trang đọc, chuyển trang không tải lại, mục lục sáng, nhớ chỗ đọc | Test trình duyệt (`make e2e`) |
| JavaScript dưới 30 KB | Đo trên bản build ở READ-06 |

## 9. Chia việc

| Việc | Gồm |
|---|---|
| READ-01 | Hồ sơ yêu cầu này, thử và đo công cụ chuyển trang, ADR-0024, đưa việc lên bảng |
| READ-02 | API + DB (mục 6) |
| READ-03 | Trang chủ tóm tắt |
| READ-04 | Trang đọc 4 cột, chuyển trang không tải lại, nhớ chỗ đang đọc, lượt đọc thật |
| READ-05 | Tìm kiếm có nhãn và đoạn trích |
| READ-06 | Tài liệu, test trình duyệt, đo JavaScript, dọn dẹp, thử lại nghiệm thu `v3.0.0` |
