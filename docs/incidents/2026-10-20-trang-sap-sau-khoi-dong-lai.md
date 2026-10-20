# Sự cố 2026-10-20: cả trang không vào được sau khi máy khởi động lại

| | |
|---|---|
| Mức | Rất nặng nếu ở production (cả trang public và trang quản trị không vào được). Trên dev chỉ ảnh hưởng chủ dự án |
| Trạng thái | Đã giải quyết. Nguyên nhân thứ hai đã sửa dứt điểm; nguyên nhân thứ nhất còn việc theo dõi |
| Môi trường | dev |
| Phát hiện | 18:21 do Claude thấy `ml-postgres` không chạy khi bắt đầu diễn tập sự cố |
| Thời gian ảnh hưởng | 18:17:59 → 18:25:32, khoảng 7 phút 30 giây (giờ Việt Nam) |
| Người xử lý | Claude (điều tra và sửa), chủ dự án (duyệt) |

## Người dùng thấy gì

Mở http://localhost:81 thì trình duyệt báo không kết nối được: cả trang public lẫn trang quản trị. Không mất dữ liệu.

## Dòng thời gian (giờ Việt Nam)

| Giờ | Thấy gì / làm gì |
|---|---|
| 18:17:52 | Máy (WSL) khởi động lại |
| 18:17:59 | 7 container dừng với mã 127: `ml-postgres`, `ml-redis`, `ml-php`, `ml-queue`, `ml-scheduler`, `ml-reverb`, `ml-nginx`. Còn chạy: `ml-minio`, `ml-nextjs`, `ml-public-site` |
| 18:21:15 | Phát hiện khi đếm dữ liệu cho diễn tập: `ml-postgres` "is not running". Giữ bằng chứng (`docker ps -a`, `docker inspect`, log) trước khi bật lại |
| 18:21:20 | Giả thuyết 1: container gắn **từng file lẻ** từ máy vào thì hỏng sau khi WSL khởi động lại. Kiểm: 7/7 container hỏng đều gắn file lẻ (`php.ini`, `docker-entrypoint.sh`, `nginx.conf`, file cấu hình Redis, Postgres); 3/3 container còn chạy không gắn file lẻ nào |
| 18:21:31 | Khắc phục tạm theo hướng dẫn dev: `docker compose -f docker/docker-compose.yml up -d`. 9/10 container chạy lại |
| 18:24:42 | Vẫn không vào được: `ml-nginx` không bật vì chờ `ml-nextjs` "khỏe", mà `ml-nextjs` bị đánh dấu "không khỏe" |
| 18:24:50 | Giả thuyết 2: bài kiểm tra sức khỏe của `ml-nextjs` gọi `/`, nhưng từ hôm qua trang quản trị đã chuyển vào `/admin` nên `/` trả 404. Log `ml-nextjs` xác nhận: `HEAD / 404` lặp lại |
| 18:25:08 | Sửa bài kiểm tra sức khỏe sang `/admin/login` (dev và cấu hình Terraform), tạo lại `ml-nextjs`, `ml-nginx` |
| 18:25:32 | Trang public, trang quản trị và API trả lời bình thường; 10/10 container healthy |

## Nguyên nhân

**Nguyên nhân thứ hai (chính, đã sửa):** việc chuyển trang quản trị vào `/admin` hôm 2026-10-19 không cập nhật bài kiểm tra sức khỏe của container `ml-nextjs`. Hôm đó container không bị tạo lại nên lỗi không lộ ra. Hỏi "tại sao" tới gốc:

1. Tại sao không vào được trang? `ml-nginx`, cổng vào duy nhất, không bật.
2. Tại sao `ml-nginx` không bật? Nó được cấu hình chỉ bật khi `ml-nextjs` khỏe.
3. Tại sao `ml-nextjs` không khỏe? Bài kiểm tra gọi `/` và nhận 404.
4. Tại sao lại 404? Trang quản trị đã chuyển vào `/admin`, còn bài kiểm tra vẫn gọi đường dẫn cũ.
5. Tại sao không ai phát hiện? Không bước kiểm tra nào (`make verify`, `make e2e`) tạo lại container từ đầu; container cũ vẫn giữ trạng thái "khỏe" từ trước.

**Nguyên nhân thứ nhất (giả thuyết mạnh, chưa chứng minh):** sau khi WSL khởi động lại, các container gắn file lẻ từ máy không bật được (mã 127). Đã gặp ngày 2026-10-15 và 2026-10-19, ghi trong [dev-guide.md](../dev-guide.md) mục 6, nhưng trước giờ chỉ xử lý bằng cách bật lại bằng tay.

## Cái gì tốt, cái gì chưa tốt

| Tốt | Chưa tốt | May mắn |
|---|---|---|
| Hướng dẫn dev có sẵn cách bật lại; giữ bằng chứng trước khi bật lại nên tìm ra quy luật file lẻ | Không có gì báo động: phải tình cờ mới phát hiện | Xảy ra trên dev, lúc đang làm về sự cố |
| Sửa trong 4 phút sau khi thấy lỗi thứ hai | Lỗi bài kiểm tra sức khỏe nằm im một ngày; cấu hình Terraform cũng mắc cùng lỗi | |
| | Lúc điều tra, lệnh `docker inspect` in ra mật khẩu Redis của dev (mật khẩu nằm trên dòng lệnh khởi động Redis). Ai xem được Docker thì vốn đã có toàn quyền, nên rủi ro thấp | |

## Việc tiếp theo

| Việc | Loại | Trạng thái |
|---|---|---|
| Sửa bài kiểm tra sức khỏe `ml-nextjs` sang `/admin/login` (dev + Terraform) | Chặn tái diễn | Xong, commit `9830eb8f` |
| Container tự bật lại được sau khi WSL khởi động lại: bỏ gắn file lẻ (chép vào image hoặc gắn cả thư mục); đưa mật khẩu Redis ra khỏi dòng lệnh | Chặn tái diễn | Trên bảng việc, chờ chủ dự án duyệt |
| Cảnh báo khi một container dừng hoặc không khỏe | Phát hiện sớm | Trên bảng việc (mục Vận hành), chờ chủ dự án duyệt |
| Khi đổi đường dẫn của một app, tìm mọi chỗ gọi đường dẫn cũ (`grep` cả `docker/`, `infra/`, `e2e/`) | Bài học | Ghi ở đây |

## Bài học

- Bài kiểm tra sức khỏe cũng là code: đổi đường dẫn thì phải sửa theo.
- "Bật lại bằng tay" lặp lại ba lần là dấu hiệu phải sửa tận gốc, không chỉ ghi vào danh sách lỗi hay gặp.
