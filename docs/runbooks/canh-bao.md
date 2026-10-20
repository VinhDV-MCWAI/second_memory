# Cảnh báo cơ bản trên máy dev

Cập nhật: 2026-10-20. Mục "Vận hành" trong giai đoạn Sau cùng của [lộ trình](../plan/02-roadmap.md). Lý do: trong [diễn tập sự cố](../incidents/2026-10-20-dien-tap-su-co.md) và [sự cố khởi động lại](../incidents/2026-10-20-trang-sap-sau-khoi-dong-lai.md), không có gì báo khi hệ thống hỏng; chỉ tình cờ mới thấy.

| | |
|---|---|
| Chạy ở đâu | Bộ hẹn giờ của systemd trong WSL, mỗi phút một lần: `scripts/health-watch.sh check` |
| Báo bằng gì | Thông báo góc màn hình Windows, kèm một dòng trong `~/.local/state/second-memory-health/alerts.log` |
| Báo khi nào | Mỗi vấn đề báo **một lần** khi mới xuất hiện, và một lần "OK again" khi hết. Không báo lặp lại mỗi phút |
| Không báo được khi | WSL (Ubuntu) không chạy, ví dụ vừa bật máy mà chưa mở terminal hay VS Code. Lúc đó trang dev vẫn có thể chạy (Docker Desktop chạy riêng), nhưng không ai canh |

## Bật, tắt, xem

| Muốn | Lệnh |
|---|---|
| Bật (một lần, tự chạy lại sau khi khởi động máy) | `make watch-on` |
| Tắt hẳn | `make watch-off` (xóa luôn trạng thái đã lưu) |
| Xem bộ hẹn giờ, vấn đề đang có, 5 cảnh báo gần nhất | `make watch-status` |
| Chạy kiểm tra ngay | `bash scripts/health-watch.sh check` |

Tắt máy chủ dev có chủ đích (`make down`) thì sẽ có một thông báo cho mỗi container; muốn yên lặng thì `make watch-off` trước, `make watch-on` sau.

## Từng loại cảnh báo và cách xử lý

| Thông báo | Nghĩa là | Xử lý |
|---|---|---|
| `ml-… is exited (Exited (127) …)` | Container dừng. Mã 127 sau khi máy khởi động lại: có chỗ gắn file lẻ (xem [dev-guide](../dev-guide.md) mục 6) | `docker logs ml-…` xem lý do, rồi `docker compose -f docker/docker-compose.yml up -d` |
| `ml-… is unhealthy` | Container chạy nhưng bài kiểm tra sức khỏe thất bại | `docker inspect ml-… -f '{{json .State.Health}}'` xem lần kiểm tra cuối; sửa nguyên nhân rồi tạo lại container |
| `ml-… does not exist (make up)` | Container bị xóa hoặc chưa tạo | `make up` |
| `Docker is not reachable` | Docker Desktop chưa chạy hoặc mất kết nối với WSL | Mở Docker Desktop; nếu vẫn lỗi: khởi động lại Docker Desktop |
| `a job has waited N min in queue …` | Một việc nền chờ quá 5 phút chưa được xử lý. Thường do `ml-queue` dừng hoặc bị treo. Quá 24 giờ thì file lớn đang chờ ghép sẽ hỏng (MinIO xóa các phần upload) | `docker ps` xem `ml-queue`; `docker logs ml-queue`; `make workers-restart` |
| `N new failed job(s)` | Có việc nền chạy hết số lần thử mà vẫn lỗi | `docker exec ml-php php artisan queue:failed` xem lỗi; sửa xong thì `queue:retry <id>`, hoặc `queue:forget <id>` nếu bỏ |
| `… is fine again (was: …)` | Vấn đề trước đó đã hết | Không cần làm gì |

Ngưỡng 5 phút nằm trong `QUEUE_WAIT_LIMIT_SECONDS` ở đầu script.

## Khi cảnh báo không hiện

1. `make watch-status`: bộ hẹn giờ có trong danh sách và có "NEXT"? Không có thì `make watch-on`.
2. `journalctl --user -u second-memory-health --since -10min`: lỗi của lần chạy gần nhất.
3. Log có dòng `warning: Windows notification failed`: WSL không gọi được PowerShell. Thử `powershell.exe -NoProfile -Command 'echo ok'` trong terminal WSL. Cảnh báo vẫn được ghi trong `alerts.log`.
4. Windows tắt thông báo của "Windows PowerShell" (Settings → System → Notifications): bật lại.

## Thử lần đầu (2026-10-20)

| Tình huống | Kết quả |
|---|---|
| Hệ thống bình thường | Không báo gì |
| Một hàng đợi giả `tmp_probe_queues:tmpcheck` có việc tạo cách đây 10 phút | Báo "a job has waited 10 min", xóa hàng đợi thì báo "OK again" |
| Việc nền lỗi thật (do bộ test trình duyệt tạo) | Báo "1 new failed job(s)" |
| Dừng `ml-queue` (qua bộ hẹn giờ thật) | Sau khoảng 1 phút báo "ml-queue is exited", bật lại thì báo "OK again" |
| Thử lại khi đóng việc: dừng `ml-reverb`, hàng đợi giả có việc chờ 10 phút, số việc lỗi tăng (thư mục trạng thái tạm) | Cả ba đều báo đúng một lần, rồi báo "OK again" khi hết; lần kiểm tra sau không báo lặp. Kiểm tra cú pháp script (shellcheck): sạch |
