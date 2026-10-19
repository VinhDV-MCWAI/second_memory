# Dọn dẹp sau mỗi việc

Cập nhật: 2026-10-17. Mỗi việc chỉ được đánh dấu xong khi đã dọn theo bảng kiểm ở mục 1. Quy tắc cho Claude: [.claude/rules/cleanup.md](../../.claude/rules/cleanup.md).

## 1. Bảng kiểm khi xong việc

| # | Kiểm tra | Cách làm |
|---|---|---|
| 1 | Rác Docker | `scripts/lane.sh run make clean` (mục 2) |
| 2 | Dữ liệu thử trên DB dev | Bản ghi bạn tạo bằng tay để thử (tên bắt đầu bằng `TMP `): xóa trên giao diện (mục 3) |
| 3 | File thử trong MinIO | Xóa trong Files rồi **xóa vĩnh viễn** trong thùng rác (mục 3) |
| 4 | Container hoặc môi trường dựng tạm | Tắt và xóa (mục 5) |
| 5 | File tạm, code chết | Xóa script tạm, file `.bak`, đoạn code comment lại, `console.log` / `dump()` để gỡ lỗi |
| 6 | Tài liệu | Sửa mọi chỗ nhắc tới thứ vừa xóa hoặc đổi tên |
| 7 | Kiểm tra lại | `git status` sạch (trừ việc của người khác), `make clean` chạy lần hai không còn gì để xóa |

### Được xóa không cần hỏi (chủ dự án cho phép 2026-10-17)

| Loại | Ví dụ |
|---|---|
| Rác Docker | Mọi thứ `make clean` xóa; ảnh công cụ build lại được (`ml-e2e`, `sm-ci-*`, `lab-metrics-*`, `ledger-importer*`) khi cần chỗ trống; container đã dừng, volume vô danh không việc nào dùng |
| Kết quả test, bộ nhớ đệm sinh ra | `e2e/artifacts`, báo cáo PHPUnit / coverage / Playwright, cache Laravel, cache Larastan / Rector |
| File tạm, code chết | Script tạm, file `.bak` / `.orig`, dòng in ra để gỡ lỗi, code bị comment lại |
| Dữ liệu thử trên DB dev và MinIO (xóa **vĩnh viễn**) | Bản ghi tên `TMP …`, `E2E …`, `smoke…`, `test …`; tài khoản `tmp_…`; file `smoke-*.txt`, `big-*.bin`; vai trò, phòng ban, thư mục thử; việc nền lỗi của việc thử; upload treo quá 24 giờ; dòng nhật ký của chính các tài khoản thử |
| Tài liệu | Câu sai so với code (sửa), tài liệu về code hay lệnh không còn tồn tại |

### Phải hỏi trước

| Loại | Ví dụ |
|---|---|
| Dữ liệu dev không tạo lại được | Volume `ml_postgres_data`, `ml_minio_data`, `ml_redis_data`; `make fresh`; `migrate:rollback` trên DB dev; `make tf-destroy` |
| Dữ liệu thật | File thật của chủ dự án (vd. `Do_An_Tot_Nghiep.docx`), dữ liệu mẫu của `make seed`, nhật ký của tài khoản thật |
| Code, tài liệu tham khảo | Code hoặc thư mục tính năng trong git; tài liệu học tập, tham khảo trong `docs/archive/` (sổ tay, mẫu, báo cáo, ghi chú học tập) |
| Không chắc | Bất cứ thứ gì không chắc là dữ liệu thử |

Khi hệ thống quyền của Claude Code chặn một lệnh xóa đã được cho phép, Claude không lách qua. Claude soạn sẵn lệnh vào một file script để chủ dự án tự chạy.

## 2. Docker: `make clean`

Script `scripts/clean.sh`, chạy lại nhiều lần vẫn an toàn.

| Bước | Xóa gì | Giữ gì |
|---|---|---|
| Ảnh không tên | Ảnh `<none>` còn sót sau khi build lại | — |
| Ảnh production cũ | Tag `sm-api`, `sm-nextjs-fe`, `sm-public-site`, `sm-minio` không container nào dùng | Tag đang chạy |
| Ảnh tải về không dùng | Ảnh tải từ registry mà không container nào chạy và không file nào trong repo ghi tên (vd. `node:22-alpine` của Dockerfile cũ, `alpine:latest` của lệnh thử) | Ảnh dự án tự build (`my_lm-ml-*`, `ml-e2e`, `sm-ci-*`, `ledger-importer*`, `lab-metrics-*`, `sm-tools`) để lần sau chạy nhanh |
| Volume vô danh | Volume không container nào dùng | Mọi volume có tên |
| Bộ nhớ đệm build | Cũ hơn 7 ngày (`CACHE_KEEP=168h`, đổi được) | Phần mới hơn |
| Kết quả test | `e2e/artifacts/`, `laravel-api/.phpunit.result.cache` | — |

Cuối script in `docker system df`. Cần thêm chỗ trống thì xóa tay ảnh công cụ ít dùng (build lại được): `docker rmi lab-metrics-api lab-metrics-fe` (đo tải đang tạm dừng), `docker rmi ml-e2e` (khoảng 4 GB, `make e2e` sẽ build lại).

## 3. Dữ liệu thử trên dev

| Nguồn | Ai dọn | Cách làm |
|---|---|---|
| Test backend (`make test`) | Tự động | Chạy trên DB `testing`, mỗi test cuộn lại giao dịch (`DatabaseTransactions`), không đụng DB dev |
| Test trình duyệt (`make e2e`) | Tự động | `e2e/run.sh` đặt tên mọi bản ghi `E2E …`, chạy xong xóa skill, bằng chứng, danh mục và bài viết, vai trò, phòng ban, người dùng `e2e-…`, file, nhật ký của tài khoản e2e, và tắt các tài khoản `e2e_owner`, `e2e_member`, `e2e_outsider` |
| Thử bằng tay trên trình duyệt | Người thử | Đặt tên bắt đầu bằng `TMP ` (skill, bằng chứng, vai trò, phòng ban, thư mục, tài khoản `tmp_…`) để dễ nhận ra, xong thì xóa trên giao diện |
| File upload thử | Người thử | Files → xóa → Trash → **Delete forever** (xóa hẳn khỏi MinIO). Không làm thì lệnh `media:prune-trash` tự xóa sau 30 ngày |
| Upload dở dang | Tự động | `media:clean-stuck-uploads` mỗi giờ dọn upload treo quá 24 giờ |
| Việc nền lỗi | Người thử | `docker exec ml-php php artisan queue:failed` để xem, `queue:flush` để xóa sau khi đã đọc lỗi |

Tìm dữ liệu thử còn sót (chỉ đọc, không xóa):

```bash
docker exec -i ml-postgres sh -c 'psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"' <<'SQL'
select 'role' as kind, name from roles where name ilike '%test%' or name like 'TMP %'
union all select 'admin', user_name from admin_mst where user_name like 'tmp%'
union all select 'department', name from department where name like 'TMP %'
union all select 'skill', name from skill where name like 'TMP %'
union all select 'evidence', title from evidence where title like 'TMP %'
union all select 'file', virtual_path from media_mgmt where virtual_path ilike '%smoke%' or virtual_path like '/TMP %' or virtual_path ilike '%thử%';
SQL
```

## 4. Bộ nhớ đệm của ứng dụng

Không phải rác: Laravel tự tạo lại, git đã bỏ qua. Chỉ xóa khi thấy code mới không có tác dụng.

| Gì | Lệnh |
|---|---|
| Cache cấu hình, route, view của Laravel | `docker exec ml-php php artisan optimize:clear` |
| Code cũ trong việc nền | `make workers-restart` |
| Cache của Larastan, Rector | Nằm trong `laravel-api/storage/framework/cache/`, tự làm mới |

## 5. Môi trường dựng tạm

| Gì | Cách xóa |
|---|---|
| Bản demo prod-like (Terraform, http://localhost:9443) | `make tf-destroy` (hỏi trước, xóa cả volume của bản demo). Hiện không còn: Terraform state rỗng, không còn container hay volume `sm-prod-like-*` (kiểm tra 2026-10-17) |
| Container chạy tay để thử (`docker run …`) | Luôn chạy với `--rm`; còn sót thì `docker ps -a` rồi `docker rm <tên>` |
| Container của `make ci` | Tự xóa khi chạy xong |
