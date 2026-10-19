# 5. Test, kiểm thử, nghiệm thu

## Test theo rủi ro, không chạy theo phần trăm

Hỏi: "cái gì hỏng thì đau nhất, và test rẻ nhất nào bắt được nó?". Ngưỡng độ phủ chỉ để độ phủ không âm thầm giảm, không phải mục tiêu.

| Mức | Chứng minh gì | Ở đâu | Viết khi |
|---|---|---|---|
| Đơn vị | Một hàm, một lớp đúng quy tắc | `laravel-api/tests/Unit`, file `*.test.ts` | Quy tắc nghiệp vụ, tính toán, xử lý chuỗi |
| Tích hợp | Các phần chạy đúng với DB, Redis thật | `laravel-api/tests/Feature` | Mỗi API: đúng, 422, 401 / 403, 404 |
| Hợp đồng | Giao diện và API hiểu nhau | Mọi response trong test được so với `openapi.json`; `make verify` báo lỗi nếu file OpenAPI cũ | Mỗi lần đổi API |
| Trình duyệt | Một luồng người dùng thật chạy được | Playwright trong `e2e/` (`make e2e`) | Chỉ các luồng quan trọng |
| Hiệu năng | Nhanh đủ dưới tải | k6 trong `perf/` (đang tạm dừng) | Trước khi phát hành API nặng |
| Bảo mật | Các kiểu tấn công đã biết bị chặn | Test quyền trong `tests/Feature` | Đăng nhập, phân quyền, upload |

Nhiều test đơn vị và tích hợp, ít test trình duyệt.

Ba bài học đã trả giá:

- **Test không bao giờ chạm dữ liệu dev.** Test dùng DB `testing` và Redis riêng; `TestEnvironmentTest` báo lỗi nếu cô lập bị hỏng. Kho dữ liệu mới nào cũng phải cô lập như vậy trước khi test đầu tiên ghi vào. Ngoại lệ duy nhất là test trình duyệt: nó chạy trên DB dev nhưng tự tạo tài khoản riêng, đặt tên bản ghi `E2E …` và tự xóa khi xong, kể cả khi lỗi.
- **Kiểm thứ tự và danh tính, không chỉ kiểm "có kết quả".** "Khớp nhất lên đầu" cần hai dòng cạnh tranh và kiểm đúng thứ tự; "tên người đang đăng nhập" cần kiểm đúng tên.
- **Cấu hình quan trọng phải test đúng cách production nạp nó** (qua `config:cache`, image production, nginx). Một lần `config:cache` đã "nướng" một cấu hình thành sai cho mọi request.

## Định nghĩa "xong"

- [ ] Đạt tiêu chí chấp nhận, đã cho xem chạy thật.
- [ ] Có test đúng mức; bỏ thay đổi thì test fail.
- [ ] `make verify` pass (chạy qua `scripts/lane.sh run`).
- [ ] Đổi API: đã `make openapi`, giao diện và trang public đã sửa theo.
- [ ] Migration có `down()` chạy được, hoặc có kế hoạch mở rộng / thu hẹp ([chương 6](06-phat-hanh-ban-giao.md)).
- [ ] Tài liệu đã cập nhật: `docs/features/`, runbook, ADR, README nếu hành vi hay cách vận hành thay đổi.
- [ ] Đã dọn dẹp theo [don-dep.md](../runbooks/don-dep.md).

## Luồng kiểm thử (việc lớn)

```
Tiêu chí chấp nhận ─► Kế hoạch test ─► Ca kiểm thử ─► Chạy: tự động + khám phá ─┬─ lỗi ─► Báo lỗi ─► Sửa ─► Kiểm lại + kiểm hồi quy ─┐
                                                                               └─ đạt ──────────────────────────────────────────────┴─► Nghiệm thu
```

1. **Kế hoạch test**: phạm vi, môi trường, dữ liệu thử, phần nào tự động, phần nào thử tay, khi nào bắt đầu / khi nào coi là đạt. Việc thường chỉ cần vài dòng trong đề xuất.
2. **Ca kiểm thử** lấy từ tiêu chí chấp nhận: mỗi "Cho trước / Khi / Thì" thành ít nhất một test tự động.
3. **Thử khám phá**: giới hạn 30 phút, một mục tiêu cụ thể ("thử upload với file rất lớn và file hỏng"), ghi lại đã thử gì.
4. **Nghiệm thu**: ghi đạt hay không, kèm môi trường và commit đã thử.

## Biên bản nghiệm thu

Nằm trong ghi chú phát hành, mục "Nghiệm thu". Mẫu thật: [v2.2.0](../releases/v2.2.0.md#nghiệm-thu).

| Phần | Nội dung |
|---|---|
| Kiểm tra tự động | Lệnh, kết quả, số test, trên commit nào |
| Thử trên trình duyệt | Bảng: bước số mấy, làm gì, mong đợi gì, kết quả. Người không đọc code cũng làm theo được |
| Lỗi tìm thấy | Mã lỗi, mức nghiêm trọng, đã sửa hay để sau |

## Báo lỗi

Báo lỗi tốt giúp người khác làm lại lỗi trong 5 phút: tiêu đề nêu triệu chứng và chỗ xảy ra; môi trường và commit; các bước chính xác; mong đợi và thực tế; bằng chứng (ảnh, dòng log, mã request); mức nghiêm trọng.

**Mức nghiêm trọng** là *ảnh hưởng* (người kiểm thử đặt); **ưu tiên** là *thứ tự sửa* (người đặt yêu cầu đặt). Lỗi chính tả ở trang chủ ít nghiêm trọng nhưng có thể ưu tiên cao trước buổi demo.

| Mức | Nghĩa | Ví dụ |
|---|---|---|
| Rất nặng | Mất dữ liệu, lộ thông tin, không dùng được | Không đăng nhập được; dữ liệu bị xóa |
| Nặng | Tính năng chính hỏng, không có cách né | Không lưu được kỹ năng |
| Nhẹ | Chạy được nếu đi đường vòng | Sắp xếp sai thứ tự |
| Rất nhẹ | Chỉ là hình thức | Icon lệch |

## Làm việc với người kiểm thử (QA)

| Tình huống | Developer có kinh nghiệm làm gì | Hay sai ở đâu |
|---|---|---|
| Trước khi làm | Đưa tiêu chí chấp nhận cho người kiểm thử xem, hỏi "anh sẽ thử những gì?"; câu trả lời thường lộ ra trường hợp mình chưa nghĩ tới | Chỉ gặp người kiểm thử khi đã xong code |
| Giao việc để kiểm | Ghi rõ: commit nào, môi trường nào, đã đổi gì, chỗ nào rủi ro, cần dữ liệu gì, tài khoản nào | Chỉ nói "xong rồi, test đi" |
| Nhận báo lỗi | Cảm ơn, làm lại lỗi trước khi sửa, viết test bắt được lỗi đó rồi mới sửa | Trả lời "trên máy em chạy được" và đóng lỗi |
| Không đồng ý đó là lỗi | So với tiêu chí chấp nhận; nếu tiêu chí không nói tới thì hỏi người đặt yêu cầu, không tranh cãi tay đôi | Cãi nhau xem ai đúng, lỗi treo mãi |
| Sửa xong | Báo commit sửa, nói phần nào bị ảnh hưởng để người kiểm thử kiểm hồi quy | Sửa im lặng, người kiểm thử không biết phải thử lại |
| Sát ngày phát hành mà còn lỗi | Đưa danh sách lỗi kèm mức nghiêm trọng cho người đặt yêu cầu quyết: sửa, hoãn, hay phát hành kèm "Lỗi đã biết" | Tự quyết giấu lỗi để kịp hạn |
