Quá trình sao lưu dữ liệu là quá trình đóng gói các dữ liệu của môi trường tại một thời điểm nhất định, dữ liệu đó là những gì được lưu trữ và sử dụng cho môi trường tại thời điểm đó. Dữ liệu này có thể là cơ sở dữ liệu, tài liêu file or folder có cấu trúc được lưu trong các storage, file cấu hình hệ thống,... Chúng được gom nhóm, đóng gói và lưu trữ lại ở nhiều nơi khác nhau tuy theo mục đích sử dụng. Phổ biến lưu dữ liệu này ở local, máy tính cá nhân, máy chủ, cloud,...

Quá trình khôi phục dữ liệu là quá trình lấy dữ liệu đã được sao lưu và khôi phục lại môi trường. Quá trình này thường được thực hiện khi môi trường bị lỗi, bị mất hoặc cần di chuyển sang môi trường mới,.. Quá trình thực hiện ngược lại so với sao lưu, việc này có hoặc không phải build lại môi trường nếu các thay đổi đó liên quan đến cấu hình, việc này thay đổi cấu hình chúng không áp dụng runtime, cần build lại mới để cập nhật cấu hình. Ngoài ra còn có vấn đề về tính toàn vẹn dữ liệu, trong quá trình triển khai có thể sai khác dữ liệu trong quá trình build. Không thể sử dụng cách build mới môi trường và cho chúng hoạt động song song với môi trường cũ. Sau đó chuyển hướng người dùng quá môi trường mới đồng thời xóa môi trường cũ. Trong lúc đó, người dùng đang thao tác dữ liệu môi trường cũ, khi chuyển sang môi trường mới sẽ không có dữ liệu mới nhất, dẫn đến mất dữ liệu. Do đó, thông thường khi diễn ra quá trình này cần proxy điều hướng traffic người dùng đến màn hình bảo trì để đảm bảo người dùng không thao tác gì thay đổi dữ liệu trong quá trình triển khai, nhằm đảm bảo tính toàn vẹn dữ liệu. Sau khi hoàn thiện mới điều hướng người dùng trở lại hệ thống.

# Tài liệu Tiêu chuẩn: Chiến lược Sao lưu và Phục hồi Thảm họa (DR & Data Synchronization Strategy)

**Mức độ bảo mật:** Nội bộ (Internal)
**Phạm vi áp dụng:** Môi trường Containerized (Docker/Docker Compose) của hệ thống Second Memory.

## 1. Tổng quan & Mục tiêu

Tài liệu này quy định các tiêu chuẩn kỹ thuật cấp cao và quy trình vận hành chuẩn (SOP) cho việc bảo toàn dữ liệu và phục hồi thảm họa (Disaster Recovery - DR). Mục tiêu cốt lõi là đảm bảo tính toàn vẹn, tính khả dụng và khả năng lưu trữ, truy xuất tài liệu dài hạn mà không gặp rủi ro mất mát thông tin dưới bất kỳ hình thức nào. 

Hệ thống tuân thủ các chỉ số đo lường hiệu quả (KPIs) về DR theo chuẩn doanh nghiệp:
* **RPO (Recovery Point Objective):** < 24 giờ. Dữ liệu mất mát tối đa cho phép không vượt quá 24 giờ thao tác.
* **RTO (Recovery Time Objective):** < 30 phút. Thời gian gián đoạn tối đa để khôi phục toàn bộ dịch vụ cốt lõi kể từ khi kích hoạt kịch bản DR.
* **Tính nhất quán nguyên tử (Atomic Consistency):** Một điểm khôi phục (Restore Point) phải là sự khớp nối hoàn hảo về thời gian giữa Cơ sở dữ liệu (PostgreSQL), Hệ thống tệp (MinIO) và Cấu hình môi trường (`.env`).

## 2. Kiến trúc Hệ thống DR

Kiến trúc sao lưu được phân tách thành 3 phân hệ độc lập nhằm giảm thiểu rủi ro điểm lỗi đơn lẻ (Single Point of Failure):

1.  **Phân hệ Dữ liệu (Stateful Tier):** Bao gồm PostgreSQL (Relational Data) và MinIO (Object Storage). Đây là nơi chứa giá trị cốt lõi của hệ thống.
2.  **Phân hệ Điều phối (Orchestration Tier):** Các kịch bản tự động hóa (`backup.sh`, `restore.sh`) đóng vai trò Controller, xử lý logic nén, mã hóa và định tuyến luồng dữ liệu.
3.  **Phân hệ Phân tán (Sovereignty Storage Tier):** Tích hợp Rclone làm cầu nối luân chuyển bản sao lưu lên môi trường Multi-Cloud (Google Drive, OneDrive, AWS S3) để dự phòng rủi ro vật lý tại máy chủ cục bộ.

## 3. Quy trình Sao lưu Tự động (Automated Backup Lifecycle)

Tiến trình sao lưu được kích hoạt định kỳ (Cronjob) và thực thi qua kịch bản `backup/backup.sh` theo luồng 4 bước:

* **Bước 1: Trích xuất Dữ liệu Định tuyến (Database Snapshot):** Sử dụng `pg_dump` với định dạng **Custom format (`-Fc`)**. Đây là định dạng nhị phân tối ưu nhất của PostgreSQL, tích hợp sẵn nén nội bộ và cho phép khôi phục cực nhanh thông qua công cụ `pg_restore`.
* **Bước 2: Đồng bộ Dữ liệu Phi cấu trúc (Media Mirroring):** Kích hoạt `mc mirror` (MinIO Client) quét và đồng bộ delta changes (chỉ copy các file thay đổi) ra vùng nhớ đệm tại Host. Đảm bảo giữ nguyên Metadata.
* **Bước 3: Đóng gói Định danh (Versioning & Archiving):** Gộp SQL Dump, thư mục MinIO và tệp `.env` thành một khối (Archive) duy nhất theo định dạng định danh: `system_backup_YYYYMMDD_HHMMSS.tar.gz`.
* **Bước 4: Phân phối & Vòng đời (Distribution & Retention):**
    * Đẩy bản sao lưu lên các node Cloud định sẵn trong biến `RCLONE_REMOTES`.
    * Thực thi chính sách dọn dẹp (Housekeeping): Tự động xóa các bản sao lưu trên Cloud vượt quá ngưỡng **3 ngày** (tuỳ chỉnh qua tham số `--min-age`).
    * **Xóa toàn bộ thư mục sao lưu cục bộ (`backups/`)** ngay sau khi hoàn tất việc tải lên Cloud để tối ưu tài nguyên lưu trữ (Zero-Local Footprint).

**Cách chạy Backup thủ công (hoặc qua Cronjob):**
```bash
bash backup/backup.sh
```

## 4. Quy trình Phục hồi & Vận hành Cắt lớp (Restore & Cutover SOP)

> **CẢNH BÁO QUAN TRỌNG:** Quá trình phục hồi yêu cầu thời gian gián đoạn dịch vụ (Downtime Window). Tuyệt đối **KHÔNG** chạy song song hệ thống cũ và hệ thống đang restore để tránh phân mảnh và ghi đè dữ liệu (Data Corruption). Việc khởi động container phải tuân thủ nghiêm ngặt theo trình tự 3 giai đoạn dưới đây.

### Giai đoạn 1: Cách ly & Thiết lập Trạng thái Bảo trì (Maintenance Mode)
* Chuyển hướng toàn bộ lưu lượng truy cập (Traffic) thông qua Nginx/Reverse Proxy đến trang trạng thái "Hệ thống đang bảo trì". 
* Tắt toàn bộ các container ứng dụng (Stateless) hiện tại để chặn mọi Transaction mới.

### Giai đoạn 2: Tái thiết lập Phân hệ Lưu trữ (Stateful Provisioning)
Chỉ khởi động các dịch vụ lõi lưu trữ để chuẩn bị nhận dữ liệu:
```bash
cd docker
docker compose up -d ml-postgres ml-minio
```
*(Lưu ý: Chờ trạng thái health-check của các container này đạt `healthy` trước khi qua Bước 3).*

### Giai đoạn 3: Thực thi Phục hồi & Đồng nhất (Data Ingestion)
Chạy kịch bản khôi phục tự động:
```bash
bash backup/restore.sh
```
* Hệ thống tải bản Snapshot mới nhất từ Cloud.
* Giải nén, sử dụng công cụ `pg_restore` để nạp dữ liệu nhị phân vào PostgreSQL và đồng bộ MinIO.
* Áp dụng tệp `.env` từ bản Snapshot.

### Giai đoạn 4: Khởi động Ứng dụng & Hủy cách ly (Application Boot & Cutover)
Khởi động phần còn lại của hệ thống dựa trên dữ liệu đã được đảm bảo tính toàn vẹn:
```bash
docker compose up -d ml-php ml-reverb ml-queue ml-nextjs ml-nextjs-docs
```
Kiểm tra logs. Nếu không có lỗi, tắt chế độ bảo trì trên Nginx và khôi phục luồng truy cập bình thường cho người dùng. Toàn bộ thư mục tạm khôi phục (`restore_tmp/`) sẽ được tự động xóa sạch.

## 5. Tiêu chuẩn Vận hành Nâng cao (Best Practices)

* **Bảo mật Thông tin Nhạy cảm (Credential Security):** Tệp `.env` chứa các Secret Keys. Hệ thống Cloud đích cấu hình qua Rclone phải sử dụng xác thực OAuth2 hoặc Token phân quyền hạn chế (Least Privilege). Khuyến nghị bật tính năng Mã hóa tại chỗ (Encryption at Rest) trên phía Cloud Provider.
* **Cô lập Dữ liệu Tạm (Zero-Local Footprint):** Hệ thống được thiết kế để không để lại dấu vết dữ liệu tại máy chủ cục bộ. Mọi tệp tin trung gian và bản sao lưu vừa tạo/tải về phải được kịch bản bash tự động xóa sạch (purge) bằng cờ `trap` nhắm vào toàn bộ thư mục làm việc (`backups/` hoặc `restore_tmp/`) ở cuối kịch bản, ngay cả khi quy trình thất bại.
* **Cơ chế Cảnh báo (Alerting):** Tích hợp Webhook (Slack/Telegram) vào kịch bản Bash để báo cáo trạng thái `SUCCESS` hoặc `FAILED` sau mỗi chu kỳ Cronjob, đảm bảo đội ngũ kỹ thuật có khả năng phản ứng ngay lập tức (Proactive Monitoring). Cái này bỏ qua làm sau.