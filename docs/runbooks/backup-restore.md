# Hướng dẫn: sao lưu, khôi phục thử và khôi phục thật

Một bản sao lưu chưa từng được khôi phục thử thì chưa được coi là có. Khôi phục thử mỗi tháng một lần, và sau mỗi lần đổi cấu trúc DB.

| | |
|---|---|
| Dùng khi | Sao lưu hằng ngày; diễn tập khôi phục; mất dữ liệu thật |
| Rủi ro | Sao lưu và khôi phục thử: thấp (chỉ đọc dữ liệu thật, ghi vào DB và bucket tạm). Khôi phục thật: **cao**, ghi đè DB, bucket chính và `docker/.env` |
| Thời gian | Với dữ liệu dev hiện tại (212 KB): sao lưu dưới 1 giây, khôi phục thử 5 giây. Khôi phục thật cộng thêm thời gian tạo lại container (khoảng 1 phút) |
| Mục tiêu | Mất tối đa 24 giờ dữ liệu, khôi phục xong trong 30 phút ([backup/README.md](../../backup/README.md)). Hiện chưa đạt mục tiêu 24 giờ vì chưa lên lịch sao lưu tự động (chưa cài rclone) |
| Lần thử gần nhất | 2026-10-20, [diễn tập sự cố](../incidents/2026-10-20-dien-tap-su-co.md): khôi phục thử, 32/32 bảng giống hệt từng dòng, file MinIO giống hệt từng byte, ứng dụng chạy trên bản khôi phục đọc được dữ liệu. Chế độ khôi phục thật chưa chạy (sẽ ghi đè dev) |

## Lỗi đã biết

| Vấn đề | Hậu quả | Xử lý |
|---|---|---|
| Sao lưu chỉ chép phiên bản mới nhất của file trong MinIO. File trong thùng rác chỉ còn ở phiên bản cũ | Khôi phục sau thảm họa thì mất mọi file đang trong thùng rác (tối đa 30 ngày); màn hình Thùng rác vẫn hiện tên nhưng khôi phục báo lỗi | Việc "Sao lưu cả file trong thùng rác" trên bảng việc |
| Tài khoản MinIO của ứng dụng chỉ có quyền trên bucket chính | Khi khôi phục thử vào bucket tạm, ứng dụng không đọc được file ở đó; phải kiểm file bằng tài khoản quản trị MinIO (mục "Kiểm tra") | Giữ nguyên: quyền chặt là đúng, khôi phục thật dùng bucket chính |

## Điều kiện

- Hệ thống đang chạy (`make up`), `ml-postgres` và `ml-minio` ở trạng thái healthy.
- Muốn đẩy lên cloud: cài [rclone](https://rclone.org) trên máy, cấu hình các nơi lưu trong `RCLONE_REMOTES` (mặc định `ggdrive:second-memory-backups onedrive:second-memory-backups`). Chưa có rclone thì dùng `--local-only` và tự chép file sao lưu ra khỏi máy.
- File sao lưu chứa `docker/.env` (mọi mật khẩu): giữ như giữ mật khẩu. `backups/` và `restore_tmp/` không vào git.

## Các bước

### Sao lưu

```bash
bash backup/backup.sh                 # DB + file MinIO + docker/.env → đẩy lên mọi nơi lưu
bash backup/backup.sh --local-only    # giữ backups/history/system_backup_<thời gian>.tar.gz, không đẩy
```

Script dừng ở lỗi đầu tiên. File trên máy chỉ bị xóa khi **mọi** nơi lưu đã nhận; nếu không, file được giữ lại và script thoát mã `1`. Không có rclone (và không có `--local-only`) thì script từ chối ngay từ đầu (mã `2`). Bản sao DB được kiểm bằng `pg_restore --list` trước khi đóng gói. Tên file dùng giờ theo múi giờ trong `docker/.env`.

### Khôi phục thử (diễn tập)

Khôi phục vào DB tạm (xóa và tạo lại) và bucket tạm. Không đụng `docker/.env` và các container.

```bash
bash backup/restore.sh backups/history/system_backup_<thời gian>.tar.gz \
  --target-db restore_check --target-bucket restore-check
```

Không truyền file thì script tải bản mới nhất từ nơi lưu đầu tiên (cần rclone). Script từ chối khôi phục vào DB chính, `testing`, `postgres` và bucket chính.

### Khôi phục thật (khi mất dữ liệu)

1. Bật bảo trì: chặn truy cập ở nginx, dừng các container ứng dụng (`docker compose -f docker/docker-compose.yml stop ml-php ml-nextjs ml-public-site`).
2. Bảo đảm `ml-postgres` và `ml-minio` đang chạy: `docker compose -f docker/docker-compose.yml up -d ml-postgres ml-minio`.
3. Khôi phục (bản mới nhất trên cloud, hoặc truyền đường dẫn file):
   ```bash
   bash backup/restore.sh [backups/history/system_backup_<thời gian>.tar.gz]
   ```
   DB được khôi phục trong **một giao dịch** (`--clean --if-exists --single-transaction`): lỗi ở bất kỳ đâu thì không có gì thay đổi và script dừng, dữ liệu cũ vẫn còn. Sau đó script trả lại `docker/.env`, chạy `scripts/setup-env.sh`, đặt lại mật khẩu tài khoản DB, tạo lại `ml-php` và `ml-redis`.
4. Bật phần còn lại (`docker compose -f docker/docker-compose.yml up -d`), kiểm tra trang, tắt bảo trì.

## Kiểm tra

- Sao lưu: script in đường dẫn và dung lượng file; nếu có đẩy lên cloud thì `rclone lsf <nơi lưu>/history/` thấy file.
- Khôi phục thử: so **từng dòng** của mọi bảng giữa DB chính và DB tạm (số dòng + mã băm nội dung). Chạy ngay sau khi sao lưu, vì dữ liệu ghi vào DB chính sau đó sẽ hiện thành khác biệt:
  ```bash
  q="SELECT string_agg(format('SELECT %L, count(*), md5(coalesce(string_agg(x::text, %L ORDER BY x::text), %L)) FROM %I x', table_name, ',', '', table_name), ' UNION ALL ' ORDER BY table_name) FROM information_schema.tables WHERE table_schema='public' AND table_type='BASE TABLE'"
  fp() { docker exec ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d $1 -qAt -c \"$q\"" | docker exec -i ml-postgres sh -c "psql -U \"\$POSTGRES_USER\" -d $1 -qAt -F ' '"; }
  diff <(fp '"$POSTGRES_DB"') <(fp restore_check) && echo "giống hệt"
  ```
- File MinIO: so tên và dung lượng mọi file giữa hai bucket bằng tài khoản quản trị MinIO (muốn chắc nội dung: `mc cat m/<bucket>/<tên file> | md5sum` ở cả hai bên):
  ```bash
  docker exec ml-minio sh -c 'mc alias set m http://127.0.0.1:9000 "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD" >/dev/null && mc diff m/media-official m/restore-check && echo "giống hệt"'
  ```
- Khôi phục thật: đăng nhập trang quản trị, mở trang tổng quan, trang Skills trên trang public, tải thử một file trong Files.

## Dọn sau diễn tập

```bash
docker exec ml-postgres sh -c 'psql -U "$POSTGRES_USER" -d postgres -c "DROP DATABASE restore_check WITH (FORCE)"'
docker exec ml-minio sh -c 'mc alias set m http://127.0.0.1:9000 "$MINIO_ROOT_USER" "$MINIO_ROOT_PASSWORD" >/dev/null && mc rb --force m/restore-check'
rm -f backups/history/system_backup_<thời gian>.tar.gz   # file có chứa mật khẩu
```

## Quay lại

- Sao lưu và khôi phục thử không đổi dữ liệu thật; chỉ cần xóa DB và bucket tạm.
- Khôi phục thật: bước DB lỗi thì không có gì thay đổi. Khôi phục thành công thì trạng thái cũ mất hẳn: nếu có thể còn cần gì từ hệ thống đang hỏng, chạy `backup.sh --local-only` trước.

## Khi nào báo lên

- `pg_restore` lỗi khi khôi phục thật: DB chính không đổi; giữ bảo trì, mở báo cáo sự cố, thử bản sao lưu trước đó.
- Sao lưu thoát mã `1` (đẩy lên cloud lỗi) hai ngày liền: có nguy cơ mất quá 24 giờ dữ liệu; sửa nơi lưu hoặc tự chép file đang giữ trên máy.

## Lịch sử phát hiện

| Ngày | Phát hiện |
|---|---|
| 2026-10-08 | `backup.sh` cũ không kiểm lỗi và luôn xóa file trên máy: thiếu rclone vẫn báo xong, thoát 0 mà **không có bản sao lưu nào**. `restore.sh` cũ nuốt mọi lỗi `pg_restore` và có thể để DB bị xóa dở. Đã sửa cả hai. Lúc đó MinIO trống nên chưa thử được phần file |
| 2026-10-20 | Diễn tập có file MinIO: file còn dùng khôi phục đúng; file trong thùng rác không được sao lưu (bảng "Lỗi đã biết") |
