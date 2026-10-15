# Việc nền và thông báo tức thì

> Trạng thái: **chạy được**. Thiết kế: [ADR-0014](../adr/0014-media-minio-upload.md). Dùng chung cho mọi tính năng có việc chạy lâu (upload file lớn, nhập dữ liệu, sao lưu…).

## Làm gì

Việc chạy lâu không làm trong request (sẽ bị timeout), mà được đẩy vào **hàng đợi** để container riêng làm. Làm xong, người bắt đầu việc nhận **thông báo ngay trên màn hình**, ở trang nào cũng được.

## Cách chạy (giống môi trường thật)

```
Màn hình ──1. bắt đầu việc──▶ API: ghi trạng thái "queued" (giữ 1 ngày), đẩy việc vào Redis, trả lời ngay
                              ml-queue lấy việc ra làm: "running" → "completed" hoặc "failed"
Màn hình ◀──2. sự kiện─────── ml-reverb ◀── Laravel gửi "background-task.finished" vào kênh riêng của người đó
Màn hình ──3. (nếu lỡ)──────▶ API trạng thái: hỏi lại các việc đang chờ mỗi khi kết nối lại
```

| Thành phần | Container | Việc |
|---|---|---|
| Hàng đợi | `ml-redis` (có sẵn) | Giữ các việc chờ làm |
| Làm việc nền | `ml-queue` (`queue:work`) | Lấy việc ra làm; lỗi thì thử lại tối đa 3 lần (chờ 5 giây, rồi 30 giây) |
| Việc hẹn giờ | `ml-scheduler` (`schedule:work`) | Chạy các lệnh theo lịch trong `routes/console.php` (hiện có: dọn upload treo mỗi giờ) |
| Thông báo tức thì | `ml-reverb` | Máy chủ WebSocket; trình duyệt nối qua nginx `ws://localhost:81/app/…` |
| Việc lỗi hẳn | bảng `failed_jobs` | Xem: `php artisan queue:failed`; chạy lại: `queue:retry <id>` |

| Tình huống | Kết quả |
|---|---|
| Việc xong | Thông báo xanh với lời nhắn của việc |
| Việc lỗi sau mọi lần thử | Thông báo đỏ "The background task failed"; chi tiết lỗi chỉ nằm trong log và `failed_jobs`, không gửi ra màn hình |
| Đang tải lại trang hoặc mạng chậm khi việc xong | Lúc kết nối lại, màn hình hỏi API trạng thái các việc nó đang chờ (lưu trong tab, `sessionStorage`) và vẫn báo kết quả |
| Nghe kênh của người khác | 403: mỗi người chỉ nghe được kênh của mình (`private-admin.{id}`) |
| Mở hai tab | Cả hai tab đều nhận thông báo |

## Thử trên màn hình

Dashboard → thẻ **Background jobs** (chỉ hiện với ai có quyền `background-task.test`):

| Nút | Kết quả mong đợi |
|---|---|
| Run test job | "Test job queued…", khoảng 3 giây sau: "Test background job finished after 3s" |
| Run failing job | Khoảng 3 giây sau: thông báo đỏ "The background task failed" |

## API

| API | Làm gì | Quyền | Chỉ xem |
|---|---|---|---|
| `POST /api/admin/background-task/test` `{seconds?, fail?}` | Đẩy việc thử (0–30 giây, `fail` để cố ý lỗi); trả trạng thái `queued` | `background-task.test` | Không |
| `GET /api/admin/background-task/status/{id}` | Trạng thái một việc **của chính mình** (giữ 1 ngày); việc của người khác hoặc đã hết hạn → 404 | `background-task.status` | Được |
| `POST /api/broadcasting/auth` | Ký quyền nghe kênh riêng (thư viện `laravel-echo` tự gọi) | đăng nhập | Được |

Sự kiện `background-task.finished` có các trường: `id`, `type` (ví dụ `system.test`), `subject_id`, `status`, `message`, `updated_at`.

## Thêm một việc nền mới (cho lập trình viên)

1. Tạo class kế thừa `App\Jobs\BackgroundJob`, viết `type()` và `run()` (trả lời nhắn hiện trên màn hình). Lỗi mà thử lại cũng không sửa được (dữ liệu sai): ném `App\Exceptions\BackgroundTaskFailed` với một hằng số `Messages`; việc lỗi ngay, lời nhắn đó hiện trên màn hình. Ví dụ: `app/Jobs/Media/ProcessUploadJob.php`.
2. Bắt đầu bằng `app(BackgroundTaskTracker::class)->dispatch(new MyJob($adminId, $subjectId))`; trả `id` cho màn hình.
3. Màn hình gọi `pendingTasks.add(id)`; `BackgroundTaskListener` (gắn sẵn trong layout quản trị) lo phần thông báo.
4. Sửa code job xong: `make workers-restart`.

## Cấu hình

| Biến | Nơi | Nghĩa |
|---|---|---|
| `QUEUE_CONNECTION=redis` | `laravel-api/.env` | Dùng hàng đợi Redis (test dùng `sync`) |
| `BROADCAST_CONNECTION=reverb` | `laravel-api/.env` | Gửi sự kiện tới Reverb (test dùng `null`) |
| `REVERB_APP_ID/KEY/SECRET` | `docker/.env` → `make setup` chép sang Laravel | Khóa của Reverb; `KEY` công khai, `SECRET` bí mật |
| `REVERB_HOST=ml-reverb`, `REVERB_PORT=8080` | `laravel-api/.env` | Laravel gửi sự kiện tới Reverb trong mạng Docker |
| `NEXT_PUBLIC_REVERB_APP_KEY` | `nextjs-fe/.env` (do `make setup` sinh) | Thiếu thì giao diện tắt thông báo tức thì, không lỗi |

## Code

| Phần | File |
|---|---|
| Lớp việc nền dùng chung | `laravel-api/app/Jobs/BackgroundJob.php` |
| Lưu trạng thái, gửi sự kiện | `app/Services/BackgroundTasks/BackgroundTaskTracker.php`, `app/Events/BackgroundTaskFinished.php` |
| Việc thử | `app/Jobs/System/TestBackgroundJob.php` |
| Kênh riêng | `routes/channels.php`, `bootstrap/app.php` (`withBroadcasting`) |
| Test | `tests/Feature/System/BackgroundTaskApiTest.php` |
| Giao diện | `nextjs-fe/src/shared/realtime/echo.ts`, `src/features/system/` |
| Container | `ml-queue`, `ml-scheduler`, `ml-reverb` trong `docker/docker-compose.yml`; nhánh `/app/` trong `docker/nginx/default.conf` |
