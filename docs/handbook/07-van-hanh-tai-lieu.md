# 7. Vận hành, sự cố, nhìn lại, viết tài liệu

Giám sát và mô phỏng sự cố đang tạm dừng tới khi có production ([01-goals-and-workflow.md](../plan/01-goals-and-workflow.md)). Các nguyên tắc dưới đây vẫn áp dụng ngay cho dev.

## Giám sát cơ bản

| Tín hiệu | Trả lời câu hỏi | Ví dụ |
|---|---|---|
| Chỉ số | Có gì sai không, sai bao nhiêu? | Số request, tỉ lệ lỗi, thời gian phản hồi; CPU, RAM, ổ đĩa, kết nối DB |
| Log | Chính xác chuyện gì đã xảy ra? | Lỗi kèm mã request, việc chạy ngầm thất bại, truy vấn chậm |
| Kiểm tra sống | Dịch vụ còn chạy không? | `/health`, `HEALTHCHECK` của container, `make ps` |

Cảnh báo theo **triệu chứng người dùng thấy** (lỗi, chậm, không vào được), không theo mọi nguyên nhân. Mỗi cảnh báo phải có runbook; cảnh báo không ai xử lý thì xóa.

## Sự cố

| Mức | Nghĩa | Phản ứng |
|---|---|---|
| Rất nặng | Sập, mất dữ liệu, lộ thông tin | Bỏ hết việc khác; có người chỉ huy; báo tình hình 30 phút một lần |
| Nặng | Tính năng chính hỏng với đa số người dùng | Bắt đầu trong 30 phút; báo mỗi giờ |
| Vừa | Hỏng một phần, có cách né | Buổi làm việc tiếp theo |
| Nhẹ | Chưa ảnh hưởng người dùng | Đưa vào danh sách việc |

Các bước:

1. **Phát hiện**: cảnh báo, người dùng báo, kiểm tra thất bại.
2. **Phân loại**: đặt mức, mở báo cáo sự cố, cử **người chỉ huy** (điều phối, không tự sửa) và người điều tra.
3. **Khắc phục tạm trước**: quay lui, tắt tính năng, khởi động lại, thêm tài nguyên. Cho người dùng dùng lại được rồi mới tìm nguyên nhân.
4. **Báo tình hình**: người dùng đang thấy gì, đang làm gì, khi nào báo tiếp.
5. **Điều tra**: theo cách bên dưới, ghi dòng thời gian trong lúc làm (giờ + thấy gì / làm gì).
6. **Giải quyết dứt điểm**.
7. **Nhìn lại sự cố** trong 5 ngày làm việc với sự cố nặng trở lên.

## Cách điều tra

1. **Phạm vi**: hỏng gì, với ai, từ lúc nào? Lúc đó **vừa thay đổi gì** (phát hành, cấu hình, dữ liệu, lượng truy cập)? `git log --since=…`.
2. **Lần theo request**: tab Network của trình duyệt → log nginx → log API (mã request) → DB / Redis / hàng đợi → dịch vụ ngoài.
3. **Nghi phạm quen thuộc**, theo thứ tự: lần phát hành gần nhất → cấu hình, bí mật → giới hạn tài nguyên (ổ đĩa, RAM, kết nối) → dịch vụ phụ thuộc (Postgres, Redis, MinIO) → dữ liệu (một dòng hỏng, migration) → tải.
4. **Mỗi lần một giả thuyết**, mỗi lần đổi một thứ, ghi kết quả vào dòng thời gian.
5. **Giữ bằng chứng** (log, ảnh, kết quả truy vấn) trước khi khởi động lại.

Lệnh hay dùng: `make ps`, `make logs s=ml-php` (hoặc `ml-nginx`, `ml-postgres`…), `docker stats --no-stream`, `df -h && docker system df`. Lỗi hay gặp trên dev: [dev-guide.md](../dev-guide.md) mục 6.

## Nhìn lại

Sau mỗi lần phát hành (mục "Nhìn lại" trong ghi chú phát hành) và sau mỗi sự cố nặng.

| Phần | Nội dung |
|---|---|
| Tốt | Điều nên giữ |
| Chưa tốt | Điều đã gây chậm, gây lỗi |
| May mắn | Chỗ suýt hỏng mà không hỏng |
| Sẽ làm | Mỗi dòng thành một việc trên bảng, hoặc một dòng sửa sổ tay này |

Nguyên tắc **không đổ lỗi**: mô tả hệ thống và quy trình đã để lỗi xảy ra thế nào, không phải ai sai. Viết "script phát hành không kiểm tra migration", không viết "X quên". Tìm nguyên nhân gốc bằng cách hỏi "vì sao?" khoảng năm lần.

## Runbook

Runbook là các bước cụ thể cho một cảnh báo hoặc một thao tác định kỳ (khôi phục sao lưu, đổi bí mật, dọn dẹp). Đặt trong `docs/runbooks/`. Gồm: khi nào dùng · điều kiện trước · các bước (lệnh chép được) · cách kiểm tra · cách quay lui. Ví dụ: [backup-restore.md](../runbooks/backup-restore.md), [don-dep.md](../runbooks/don-dep.md).

## Tài liệu nào cho tình huống nào

| Tình huống | Tài liệu | Đặt ở |
|---|---|---|
| "Cần làm X" (việc lớn) | Hồ sơ yêu cầu `REQ-nnn` | `docs/requirements/` |
| "Làm X thế nào?" | Đề xuất (trong chat hoặc trong `REQ-…`) | – |
| "Chọn A thay vì B" | ADR | `docs/adr/` |
| "Tính năng X làm gì?" | Tài liệu tính năng | `docs/features/` |
| "Làm Y an toàn thế nào?" | Runbook | `docs/runbooks/` |
| "Bản này có gì?" | Ghi chú phát hành | `docs/releases/` |
| "Đang làm tới đâu?" | Bảng việc, nhật ký tiến độ | `.claude/lab/` (qua `scripts/lane.sh`) |
| "Production đang hỏng" | Báo cáo sự cố + nhìn lại | `docs/incidents/` (tạo khi có sự cố đầu tiên) |

Mẫu tài liệu cũ (tiếng Anh) để tham khảo: [docs/archive/engineering-lab/templates/](../archive/engineering-lab/templates/).

## Viết sao cho người mới hiểu

1. **Bắt đầu bằng bối cảnh**: tình huống là gì, một đoạn, không giả định người đọc biết code.
2. **Nối nguyên nhân và hậu quả**: gốc → hậu quả → cách giải quyết → điều gì xảy ra tiếp; link tài liệu trước và sau.
3. **Cụ thể hơn chung chung**: thông báo lỗi thật, con số, lệnh, đường dẫn file.
4. **Ghi ngày**, và nói điều gì đúng vào ngày đó; không âm thầm sửa lịch sử, thêm mục cập nhật.
5. **Giải thích thuật ngữ** ở lần đầu xuất hiện. Quy ước viết đầy đủ: [01-goals-and-workflow.md](../plan/01-goals-and-workflow.md#quy-ước-viết-tài-liệu).

## Quy tắc dữ liệu

| Chủ đề | Quy tắc |
|---|---|
| Dữ liệu dev | Tạo bằng seeder (`make seed`); dữ liệu thử đặt tên `TMP …` và xóa khi xong việc |
| Dữ liệu test | Mỗi test tự tạo dữ liệu bằng factory, chạy trên DB `testing` |
| Dữ liệu cá nhân | Không bao giờ có dữ liệu cá nhân thật trong repo, seeder, ảnh chụp, log, báo cáo |
| Bí mật | Chỉ trong file env bị git bỏ qua; không bao giờ trong tài liệu, commit |
| Sao lưu | DB + kho file MinIO + cấu hình, cùng một thời điểm; khôi phục phải được thử, không phải đoán |
| Nhập dữ liệu | Chạy lại không được nhân đôi dữ liệu (importer Obsidian) |

## Làm việc với bảo mật, hỗ trợ và người dùng

| Tình huống | Developer có kinh nghiệm làm gì | Hay sai ở đâu |
|---|---|---|
| Tính năng đụng đăng nhập, quyền, upload, dữ liệu cá nhân | Mời người phụ trách bảo mật xem từ lúc thiết kế, liệt kê sẵn: ai được làm gì, dữ liệu gì được lưu, kẻ xấu thử gì | Gửi bảo mật xem khi đã sắp phát hành, phải làm lại |
| Bảo mật báo một lỗ hổng | Coi là sự cố, sửa theo mức nghiêm trọng, không bàn chi tiết khai thác ở nơi công khai | Cãi rằng "không ai tìm ra đâu" |
| Người dùng báo lỗi mơ hồ ("không dùng được") | Hỏi: đang làm gì, thấy gì, lúc nào, trình duyệt nào, ảnh chụp; tự làm lại được rồi mới sửa | Đoán nguyên nhân rồi sửa thứ khác |
| Người dùng xin tính năng | Ghi lại nhu cầu phía sau, chuyển cho người đặt yêu cầu xếp ưu tiên; không tự hứa | Hứa "tuần sau có" khi chưa ai duyệt |
| Bộ phận hỗ trợ hỏi cùng một câu nhiều lần | Viết hướng dẫn hoặc sửa thông báo lỗi trong ứng dụng cho rõ | Trả lời từng lần |
| Sau sự cố do mình gây ra | Tham gia nhìn lại, kể đúng sự thật, đề xuất cách chặn để không lặp lại | Né tránh, hoặc tự trách mà không sửa quy trình |
