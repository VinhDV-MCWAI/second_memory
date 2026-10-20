# REQ-005 — Trang tài liệu kinh nghiệm: bỏ Skill Ledger, danh mục và bài viết kiểu trang tài liệu

| | |
|---|---|
| Người yêu cầu | Chủ dự án |
| Ngày nhận | 2026-10-20 |
| Cỡ việc | Lớn ([sổ tay chương 1](../handbook/01-tong-quan.md)): bỏ một tính năng, đổi cấu trúc DB, đổi quyền, đổi API public, đổi công cụ nhập từ Obsidian |
| Trạng thái | Đã làm rõ, chủ dự án đồng ý toàn bộ đề xuất ngày 2026-10-20 |
| Thiết kế | [ADR-0022](../adr/0022-trang-tai-lieu-kinh-nghiem.md) |

## 1. Yêu cầu gốc (tóm tắt lời chủ dự án)

1. Mục đích dự án là **lưu kinh nghiệm**, không phải tự chấm điểm bản thân. Thang cấp độ tự đánh giá không có giá trị với người khác (mỗi nơi, mỗi người chấm một kiểu); mục tiêu học chỉ có ý nghĩa trong một công ty lâu năm. **Bỏ Mục tiêu học, cấp độ đích, cấp độ**. Các thứ không quản lý được như loại bằng chứng cũng bỏ.
2. Bằng chứng giữ lại nhưng gộp vào kỹ năng: mỗi kỹ năng có bằng chứng riêng (link, file), không dùng chung. Kỹ năng gom chung với nội dung public.
3. Trang chủ giống diễn đàn: danh mục có bảng xếp hạng bài viết, lọc theo danh mục hoặc bài viết.
4. Mỗi bài viết là một chủ đề kinh nghiệm, viết Markdown; thuộc **nhiều danh mục**; có thể là bài đơn hoặc **một chương / sách** chia nhiều mục nhỏ (tối đa **10 cấp**), có menu điều hướng; nhúng được file và **nhúng bài viết khác vào nội dung** (viết riêng từng bài rồi ghép lại).
5. Giao diện như trang tài liệu của Next.js, Laravel, nhưng nội dung là kinh nghiệm của chủ dự án.
6. Ô gõ tay (category của kỹ năng) đổi sang chọn từ danh sách; bỏ ô "Sort order" gõ số; có xem trước Markdown.

## 2. Câu hỏi làm rõ và trả lời (2026-10-20)

| # | Câu hỏi | Trả lời |
|---|---|---|
| 1 | Kỹ năng thành danh mục trong nhóm "Kỹ năng", bằng chứng thành đính kèm của danh mục? | Đồng ý |
| 2 | Bỏ Nhãn (bài đã thuộc được nhiều danh mục)? | Đồng ý |
| 3 | Dữ liệu Skill Ledger và bài viết hiện có? | Là dữ liệu mẫu, **bỏ được**; tạo mẫu mới cho mô hình mới |
| 4 | Đếm lượt xem để xếp hạng "Đọc nhiều"? | Đồng ý, chỉ đếm số, không lưu ai xem |
| 5 | Cú pháp nhúng kiểu Obsidian `[[ ]]`, `![[ ]]`; nhập từ Obsidian thành bài viết? | Đồng ý |
| 6 | Chương / sách mấy cấp? | Tối đa **10 cấp** |
| 7 | Nghiệm thu `v2.3.0` trước? | Làm theo đề xuất: `v2.3.0` vẫn chờ chủ dự án thử trên trình duyệt, việc này làm song song và ra bản `v3.0.0` |

## 3. Phạm vi

| Có | Không |
|---|---|
| Danh mục 2 cấp (nhóm → danh mục), mô tả Markdown, đính kèm link / file | Danh mục nhiều hơn 2 cấp |
| Bài viết nhiều danh mục, bài cha (tối đa 10 cấp), thứ tự bằng nút ↑ ↓ | Kéo thả |
| Markdown: neo tiêu đề, mục lục, `[[bài]]`, `![[bài]]`, `![[bài#Tiêu đề]]`, `![[file:…]]`, xem trước trong trang quản trị | Soạn kiểu Word / Obsidian live preview (đề xuất riêng nếu chủ dự án muốn sau) |
| Trang public kiểu tài liệu: menu trái, mục lục phải, bài trước / sau, tìm kiếm, xếp hạng | Bình luận, chấm điểm bài viết |
| Lượt xem, "Được lưu nhiều", ☆ 🔖 cho bài viết | Thống kê theo người xem |
| Nhập từ Obsidian thành bài viết (gửi cả nội dung) | Nhập ảnh trong vault Obsidian vào Files |
| Bỏ Skill Ledger: kỹ năng, cấp độ, mục tiêu học, bằng chứng, nhãn (API, bảng, quyền, màn hình, trang `/skills`) | Giữ dữ liệu mẫu cũ |

## 4. Câu chuyện người dùng và tiêu chí chấp nhận

```text
Là người xem, tôi muốn thấy các nhóm và danh mục như một diễn đàn, để chọn chủ đề cần đọc.

Tình huống: trang chủ
  Khi tôi mở http://localhost:81/
  Thì tôi thấy từng nhóm (ví dụ "Kỹ năng", "Quy trình làm phần mềm"), mỗi danh mục một dòng
      có số bài, bài mới nhất và ngày cập nhật

Tình huống: trang danh mục
  Khi tôi mở một danh mục
  Thì tôi thấy mô tả, các đính kèm (nếu có) và danh sách bài, đổi được cách xếp:
      Mới nhất, Cập nhật gần đây, Đọc nhiều, Được lưu nhiều; lọc thêm theo danh mục khác, tìm theo tiêu đề
```

```text
Là người xem, tôi muốn đọc một bài như trang tài liệu.

Tình huống: bài trong một sách
  Biết rằng bài "Hàng đợi" là mục con của sách "Laravel thực chiến"
  Khi tôi mở bài đó
  Thì cột trái là cây mục của sách (mục đang đọc được đánh dấu), cột phải là mục lục các tiêu đề,
      cuối bài có nút bài trước / bài sau theo thứ tự của cây

Tình huống: bài nhúng bài
  Biết rằng nội dung có dòng ![[cai-dat-redis]]
  Thì chỗ đó hiện toàn bộ bài "Cài đặt Redis", có link về bài gốc
  Nếu bài đó riêng tư thì chỗ đó không hiện gì; nếu nhúng vòng (A nhúng B, B nhúng A) thì dừng ở lần lặp và hiện một dòng báo
```

```text
Là chủ dự án, tôi muốn viết bài trong trang quản trị mà không phải nhớ tên hay số thứ tự.

Tình huống
  Khi tôi tạo bài viết
  Thì tôi chọn danh mục từ danh sách (nhiều danh mục), chọn bài cha từ danh sách, bấm "Xem trước"
      thấy giống hệt trang public, bấm "Chèn file" / "Chèn bài viết" để thêm cú pháp nhúng
  Đổi thứ tự bằng nút ↑ ↓; không còn ô gõ số thứ tự
  Bài cha làm cây sâu quá 10 cấp hoặc tạo vòng (bài là cha của chính nó) thì bị chặn kèm lý do
```

```text
Là chủ dự án, tôi muốn gắn bằng chứng (link, file) cho một kỹ năng.

Tình huống
  Khi tôi sửa danh mục "Laravel" trong nhóm "Kỹ năng" và thêm link chứng chỉ, file PDF
  Thì trang danh mục public hiện các đính kèm đó; file mở qua đường dẫn /files/{mã}, không lộ thư mục
```

## 5. Yêu cầu phi chức năng

| Mặt | Yêu cầu |
|---|---|
| Nhẹ | Đọc không cần JavaScript; chỉ giữ script ☆ / 🔖 (~3 KB) và nút mở menu trên điện thoại |
| An toàn | Markdown chặn HTML thô và link `javascript:`; bài riêng tư không lộ qua nhúng, tìm kiếm, danh sách đã lưu |
| Hiệu năng | Trang bài viết có nhúng 3 lớp vẫn trả trong 300 ms trên dev (đã có lưu đệm 60 giây ở trang public) |
| Quyền | Mỗi API quản trị mới là một quyền; quyền Skill Ledger bị xóa khỏi DB |

## 6. Phân tích ảnh hưởng

| Phần | Thay đổi |
|---|---|
| DB | Xóa `skill`, `skill_level`, `skill_tag`, `tag`, `evidence`, `evidence_skill`, `evidence_tag`, `evidence_media`, `learning_goal`. `content_category` thêm danh mục cha; `article` bỏ danh mục đơn, thêm bài cha, vị trí, lượt xem; bảng mới `article_category`, `article_media`, `category_attachment`. `end_user_saved` bỏ kỹ năng, bằng chứng |
| API quản trị | Bỏ `skill/*`, `skill-level/*`, `evidence/*`, `learning-goal/*`, `tag/*`, `evidence/import`. Thêm `article/preview`, `article/move`, `content-category/move`, `article/import`. `dashboard/summary`, `search` đổi nội dung |
| API public | Bỏ `public/skills*`. Danh mục, bài viết, tìm kiếm, lượt xem theo mô hình mới |
| Trang quản trị | Bỏ Skills, Evidence, Goals, Tags. Categories dạng cây có đính kèm; Articles có Viết / Xem trước, chèn file, chèn bài; trang tổng quan mới; cột "dùng ở đâu" của Files |
| Trang public | Bỏ `/skills`. Trang chủ diễn đàn, `/categories/{slug}`, `/articles/{slug}`, `/search`; `/skills…`, `/guides…` cũ chuyển hướng |
| Công cụ nhập Obsidian | Tạo bài viết, gửi cả nội dung; khóa token đổi sang `article:import` |
| Test, tài liệu | Bỏ test Skill Ledger; test mới cho mọi phần trên; `docs/features/` (bỏ `skill-ledger.md`, viết lại `bai-viet.md`, `trang-public.md`, `tim-kiem.md`, `nhap-tu-obsidian.md`), README, kiến trúc |

## 7. Sẵn sàng để làm khi

- [x] Mục tiêu và phạm vi đã ghi
- [x] Tiêu chí chấp nhận kiểm tra được
- [x] Chủ dự án trả lời mục 2
- [x] ADR-0022

## 8. Kế hoạch test

| Tiêu chí | Test |
|---|---|
| Skill Ledger biến mất, không còn quyền, bảng | Test backend (schema, quyền), `make verify` |
| Danh mục 2 cấp, bài nhiều danh mục, bài cha 10 cấp, chặn vòng | Test backend API quản trị |
| Nhúng bài, nhúng mục, nhúng file, bài riêng tư, nhúng vòng, Markdown an toàn | Test backend cho bộ chuyển Markdown |
| Trang quản trị mới | Test giao diện |
| Trang public kiểu tài liệu, xếp hạng, tìm kiếm, chuyển hướng cũ | Test backend API public; test trình duyệt |
| Lượt xem: một người mở lại trong 30 phút chỉ tính 1 | Test backend |
| Nhập từ Obsidian | Test của công cụ nhập + test backend API nhập |

## 9. Chia việc

| Việc | Gồm |
|---|---|
| KB-01 | Hồ sơ yêu cầu này + ADR-0022 + đưa việc lên bảng |
| KB-02 | Bỏ Skill Ledger: API, bảng, quyền, màn hình, trang `/skills`, dữ liệu mẫu, ☆ 🔖 kỹ năng / bằng chứng |
| KB-03 | Mô hình mới: danh mục 2 cấp + đính kèm, bài nhiều danh mục + bài cha + vị trí; API và màn hình quản trị; dữ liệu mẫu |
| KB-04 | Bộ chuyển Markdown: neo, mục lục, nhúng bài / mục / file; xem trước; chèn file, chèn bài trong form |
| KB-05 | Trang public kiểu tài liệu: trang chủ diễn đàn, trang danh mục, trang bài 3 cột, chuyển hướng cũ |
| KB-06 | Lượt xem, xếp hạng, tìm kiếm public và quản trị, trang tổng quan, cột "dùng ở đâu" của Files |
| KB-07 | Nhập từ Obsidian thành bài viết |
| KB-08 | Tài liệu, test trình duyệt, dọn dẹp, đóng gói `v3.0.0` |
