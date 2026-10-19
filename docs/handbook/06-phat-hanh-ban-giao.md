# 6. Phát hành và bàn giao

## Môi trường

| Môi trường | Để làm gì | Hiện tại |
|---|---|---|
| Dev | Làm hằng ngày | `make up`, http://localhost:81 |
| Prod-like (giống production) | Diễn tập phát hành, sự cố, khôi phục | Đang tắt, dựng lại bằng `make cd` khi cần (`infra/`) |
| Staging, production | Kiểm thử chung, người dùng thật | Chưa có, để sau ([02-roadmap.md](../plan/02-roadmap.md) mục 7) |

## Đánh số phiên bản

`MAJOR.MINOR.PATCH`:

| Tăng | Khi | Ví dụ |
|---|---|---|
| MAJOR | Phá vỡ tương thích API hay dữ liệu | `v2.0.0` bỏ CMS |
| MINOR | Thêm tính năng | `v2.3.0` thêm tài khoản người dùng trên trang public |
| PATCH | Chỉ sửa lỗi | `v2.3.1` |

Gắn phiên bản sau mỗi nhóm tính năng, **không để quá 2 tuần** (bài học từ `v2.2.0`: 225 commit không gắn phiên bản nào).

## Các bước phát hành

| # | Việc | Ai |
|---|---|---|
| 1 | Mọi việc của bản này đã `done` trên bảng việc | Claude |
| 2 | `make verify` và `make e2e` pass trên code đã commit | Claude |
| 3 | Viết ghi chú phát hành `docs/releases/vX.Y.Z.md` (cấu trúc bên dưới) | Claude |
| 4 | Thử trên trình duyệt theo bảng nghiệm thu, ghi kết quả | Chủ dự án |
| 5 | Sao lưu môi trường sẽ nâng cấp (`make backup`) | Người vận hành |
| 6 | Tag trên máy: `git tag -a vX.Y.Z -m "…"` | Claude |
| 7 | Push branch và tag, mở pull request `developer` → `main` | Chủ dự án |
| 8 | Nâng cấp môi trường, kiểm tra nhanh (trang chủ, đăng nhập, một luồng chính), xem log 15 phút | Người vận hành |

## Ghi chú phát hành

Mẫu thật: [v2.2.0](../releases/v2.2.0.md).

| Mục | Nội dung |
|---|---|
| Nổi bật | Người dùng được gì, viết bằng lời thường |
| Thay đổi phá vỡ tương thích | Cái gì không còn chạy như cũ, phải làm gì |
| Nâng cấp một môi trường đang chạy | Lệnh theo thứ tự: sao lưu, migration, build, khởi động lại |
| Trước / sau | Số đo: bảng DB, số test, RAM, dung lượng |
| Nghiệm thu | Kiểm tra tự động + bảng thử trên trình duyệt ([chương 5](05-test-nghiem-thu.md#biên-bản-nghiệm-thu)) |
| Bàn giao | Bảng "cần biết gì → ở đâu" |
| Lỗi đã biết | Vấn đề còn lại và cách xử lý |
| Không có trong bản này | Để người đọc khỏi tìm |
| Nhìn lại | Tốt / chưa tốt / sẽ làm khác ([chương 7](07-van-hanh-tai-lieu.md#nhìn-lại)) |

## Migration an toàn

- Migration đã chạy ở môi trường khác thì **không sửa**: viết migration mới.
- Mỗi migration có `down()` chạy được; thử quay lui trên DB `testing`, **không** trên DB dev.
- Thay đổi phá dữ liệu đi theo **mở rộng / thu hẹp** qua nhiều lần phát hành:
  1. *Mở rộng*: thêm cột / bảng mới; code ghi vào cả cũ và mới.
  2. *Chuyển*: chép dữ liệu cũ sang; đếm lại cho khớp.
  3. *Đổi*: code đọc từ cấu trúc mới.
  4. *Thu hẹp*: lần phát hành sau mới bỏ cấu trúc cũ (đã sao lưu).
- Ngoại lệ khi chỉ có một máy chủ: cả bốn bước được nằm trong một lần phát hành nếu mỗi bước là một migration và commit riêng, bước thu hẹp tự kiểm điều kiện và từ chối chạy nếu chưa đạt, và ghi chú phát hành bắt buộc sao lưu trước.
- Chuyển dữ liệu lâu thì dùng việc chạy ngầm, không nhét vào migration.

## Quay lui

| Hỏng gì | Làm gì |
|---|---|
| Code (không có migration) | Chạy lại image của bản trước |
| Migration có `down()` | `migrate:rollback --step=N`, rồi image bản trước |
| Dữ liệu hỏng | Khôi phục bản sao lưu trước khi phát hành ([backup-restore.md](../runbooks/backup-restore.md)) |

Nguyên tắc: **quay lui trước, điều tra sau**. Quay lui không phải thất bại; phát hành mà không quay lại được mới là thất bại.

## Bàn giao

Bàn giao tốt là người nhận làm tiếp được mà không phải hỏi lại. Tối thiểu có:

| Cần biết | Ở đâu |
|---|---|
| Chạy, test, sửa lỗi | [dev-guide.md](../dev-guide.md) |
| Kiến trúc | `docs/architecture/` |
| Từng tính năng | `docs/features/` |
| Quyết định kỹ thuật | `docs/adr/` |
| Thao tác vận hành | `docs/runbooks/` |
| Việc còn mở | `docs/plan/03-backlog.md` |
| Tài khoản, quyền truy cập cần cấp | Ghi tên tài khoản, không ghi mật khẩu thật |
| Việc người nhận phải tự làm | Danh sách rõ ràng (push, lên lịch sao lưu…) |

## Làm việc với người vận hành (DevOps / Ops) và người nhận bàn giao

| Tình huống | Developer có kinh nghiệm làm gì | Hay sai ở đâu |
|---|---|---|
| Tính năng cần cấu hình mới (biến môi trường, cổng, dịch vụ) | Báo người vận hành **trước** ngày phát hành, ghi vào mục "Nâng cấp một môi trường đang chạy" | Người vận hành phát hiện lúc nâng cấp production thì ứng dụng không chạy |
| Có migration nặng | Ước lượng thời gian chạy trên dữ liệu thật, bàn giờ chạy, chuẩn bị cách quay lui | Chạy migration khóa bảng giữa giờ làm việc |
| Phát hành xong | Ở lại xem log, sẵn sàng quay lui; không phát hành rồi nghỉ ngay | Phát hành chiều thứ Sáu rồi tắt máy |
| Người vận hành báo lỗi production | Hỏi mã request, giờ xảy ra, bản đang chạy; ưu tiên giúp khắc phục trước, tìm nguyên nhân sau | Đòi người vận hành tự tìm lỗi trong code |
| Bàn giao cho người khác | Ngồi cùng họ chạy thử một lần theo tài liệu bàn giao, sửa tài liệu chỗ họ vấp | Gửi link tài liệu rồi coi như xong |
| Người nhận hỏi lại cùng một điều hai lần | Đó là lỗ hổng của tài liệu: thêm vào tài liệu bàn giao | Trả lời qua chat mỗi lần |
