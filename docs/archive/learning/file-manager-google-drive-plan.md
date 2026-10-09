> **Learning note (archived 2026-10-09, HK-01; was `laravel-api/docs/manager-file.md`).** A plan for storing media on Google Drive through the API. Never built: media went to MinIO, and the media API itself was removed in [ADR-0007](../../adr/0007-remove-media-api.md); the unused `google/apiclient` package went in API-08. Kept unchanged otherwise.
>
> 🇻🇳 **Ghi chú học tập, đã lưu trữ (2026-10-09).** Kế hoạch lưu media lên Google Drive qua API. Chưa từng làm: media dùng MinIO, rồi API media bị xoá ở ADR-0007; package `google/apiclient` bị gỡ ở API-08. Nội dung còn lại giữ nguyên.

Dưới đây là **PLAN triển khai đầy đủ, rõ ràng, theo thứ tự thực hiện** cho mô hình:
**Next.js (FE) → Laravel API → Google Drive (Storage)**

Không có code, chỉ hướng dẫn kiến trúc – quy trình – luồng dữ liệu – bảo mật – cấu trúc.

---

# 🔥 **PLAN TỔNG THỂ**

## 1. **Mục tiêu hệ thống**

* Next.js upload ảnh/video/document từ FE → Laravel.
* Laravel kiểm tra auth → upload lên Google Drive thông qua API.
* Laravel trả về file ID + metadata → FE lưu vào DB.
* FE load media thông qua API của Laravel (proxied) hoặc Google Drive Link (nếu public).
* Quản lý CRUD: upload, rename, move folder, delete, list.

---

# 🧱 **2. Kiến trúc tổng quan**

```
[ Client Next.js ]
        |
        | HTTP Request (Upload/CRUD)
        v
[ Laravel API ] --- uses Google Drive API ---> [ Google Drive Storage ]
        |
        | DB record (file_id, metadata)
        v
[ MySQL/Postgres ]
```

### Laravel làm các nhiệm vụ:

* Xác thực user.
* Validate file.
* Tương tác Google Drive thông qua **Google Drive API** (sử dụng Service Account).
* Stream file về cho Client (để bảo mật đường dẫn thực).
* Ghi metadata vào database:
  * google_file_id
  * tên file
  * path (logic folder)
  * kích thước
  * loại file
  * admin_mst_id
  * created_at

### Next.js làm:

* UI upload.
* Preview image.
* Hiển thị list file.
* Gửi lệnh CRUD → Laravel API.
* Load hình ảnh thông qua API Laravel (ví dụ: `/api/files/{id}/view`).

---

# ☁️ **3. Cấu trúc Folder trên Google Drive**

Bạn sẽ tạo một **Root Folder** dành riêng cho ứng dụng này. Mọi file sẽ nằm trong đó.

```
MyLifeManagement_Root/ (Root Folder ID)
    ├── images/
    │     ├── 2025/
    │     │     └── 11/
    │     └── thumbs/
    ├── videos/
    ├── documents/
    ├── users/
    │     └── {admin_mst_id}/
```

### Tại sao tách folder theo năm/tháng?

* Google Drive có thể xử lý số lượng file lớn, nhưng việc phân chia giúp dễ quản lý logic và backup thủ công nếu cần.

---

# 🔐 **4. Bảo mật – Service Account**

1. **Không dùng tài khoản Google cá nhân trực tiếp**: Sử dụng **Service Account** (Google Cloud Platform).
2. **Credential**: File `service-account.json` sẽ được đặt trong Laravel (không public ra ngoài).
3. **Quyền truy cập**:
   * Service Account được share quyền "Editor" vào `MyLifeManagement_Root` folder của Google Drive chính của bạn.
   * Laravel dùng Service Account để thao tác (Upload, Delete, Read).

---

# 🔄 **5. Luồng xử lý upload file**

## FE (Next.js)

1. User chọn file → FE gọi API Laravel:
   ```
   POST /api/files/upload
   ```
2. Gửi file dạng multipart.

## Laravel

1. Xác thực JWT.
2. Validate file (size, type).
3. Upload file lên Google Drive vào folder tương ứng (tự động tạo folder nếu chưa có).
4. Nhận về `file_id`, `web_view_link`, `web_content_link`.
5. Save file info vào DB.
6. Trả về response cho FE.

---

# 🗂️ **6. Luồng xử lý READ file**

Do Google Drive link gốc thường có hạn chế về bandwidth hoặc yêu cầu quyền truy cập phức tạp, khuyến nghị dùng **Laravel làm Proxy**.

### **Phương án: Laravel Proxy (Bảo mật & Ổn định)**

➡️ FE gọi Laravel:
```
GET /api/files/{id}/view
```

➡️ Laravel:
1. Tìm `google_file_id` từ DB.
2. Gọi Google Drive API lấy content stream.
3. Stream trả về browser với header đúng (image/jpeg, video/mp4...).
4. Có thể cache response này để tăng tốc.

*Ưu điểm*:
* Không lộ link Google Drive thật.
* Kiểm soát quyền truy cập chặt chẽ (chỉ user login mới xem được nếu muốn).

---

# 🧰 **7. Luồng xử lý DELETE file**

## FE
Gọi:
```
DELETE /api/files/{id}
```

## Laravel
1. Kiểm tra quyền.
2. Gọi Google Drive API xoá file theo `file_id`.
3. Xoá record DB.

---

# 🔧 **8. Luồng xử lý RENAME file**

Google Drive hỗ trợ rename trực tiếp (không cần copy/delete như S3).

1. FE gửi tên mới.
2. Laravel gọi Google Drive API update metadata (name) cho `file_id`.
3. Cập nhật DB.

---

# 📁 **9. Luồng xử lý MOVE folder hoặc MOVE file**

Google Drive hỗ trợ move bằng cách thay đổi `parents`.

1. FE gửi `new_folder_id`.
2. Laravel gọi Google Drive API: remove parent cũ, add parent mới.
3. Update DB.

---

# 📜 **10. Database Schema gợi ý**

Bảng `media_files`:

* id
* admin_mst_id
* google_file_id (Quan trọng: ID chuỗi của Google Drive)
* original_name
* extension
* mime_type
* size
* folder_path (để hiển thị UI)
* is_public (bool)
* metadata (json)
* created_at
* updated_at

---

# ⚙️ **11. Các API chuẩn bạn cần trong Laravel**

1. `POST /files/upload`
2. `GET /files` – list (phân trang từ DB)
3. `GET /files/{id}/view` – stream file content
4. `GET /files/{id}/download` – force download
5. `DELETE /files/{id}`
6. `PUT /files/{id}/rename`
7. `PUT /files/{id}/move`
8. `POST /folders/create`

---

# 🚀 **12. Setup Google Cloud (Quan trọng)**

Bạn cần thực hiện các bước này trước khi code:

1. Vào **Google Cloud Console**.
2. Tạo Project mới.
3. Enable **Google Drive API**.
4. Tạo **Service Account**:
   * Vào "Credentials" -> "Create Credentials" -> "Service Account".
   * Tải JSON Key về, đổi tên thành `google-drive-credentials.json`.
   * Lưu vào `storage/app/` (nhớ `.gitignore`).
5. **Share Folder**:
   * Tạo folder trên Google Drive thật của bạn.
   * Chuột phải -> Share -> Nhập email của Service Account (có trong file JSON) -> Quyền Editor.
   * Lấy ID của folder này làm `GOOGLE_DRIVE_ROOT_FOLDER_ID` trong `.env`.

---

# �️ **13. Thư viện Laravel khuyên dùng**

Sử dụng package adapter để tích hợp vào Laravel Filesystem (Storage Facade) cho chuẩn:

* Package: `spatie/laravel-google-cloud-storage` (dành cho GCS, nhưng Google Drive thường dùng package khác hoặc custom adapter).
* **Khuyên dùng**: `masbug/flysystem-google-drive-ext` hoặc tự viết Service Wrapper dùng `google/apiclient`.

*Cách đơn giản nhất*: Dùng `google/apiclient` trực tiếp trong Service class riêng để kiểm soát tốt hơn các tính năng như Create Folder, Move, Rename mà Flysystem đôi khi hạn chế.

---

# 📌 **14. Checklist thực hiện**

### **Tuần 1 – Setup & Core**
* [ ] Setup Google Cloud Project & Service Account.
* [ ] Cấu hình biến môi trường `.env` (Client ID, Secret, Refresh Token hoặc Service Account JSON).
* [ ] Viết `GoogleDriveService` trong Laravel để test kết nối (List files, Upload thử).

### **Tuần 2 – API Backend**
* [ ] Tạo bảng `media_files`.
* [ ] API Upload (lưu file lên Drive, lưu DB).
* [ ] API Stream file (View).
* [ ] API Delete/Rename.

### **Tuần 3 – Next.js UI**
* [ ] UI Upload (Dropzone).
* [ ] Grid View file.
* [ ] Preview ảnh/video.

### **Tuần 4 – Hoàn thiện**
* [ ] Caching (Redis) cho API list.
* [ ] Tối ưu tốc độ stream.

---

# 🎯 **Kết luận**

Chuyển sang Google Drive giúp bạn tận dụng dung lượng lưu trữ lớn miễn phí (15GB+) hoặc gói mua rẻ, không lo về bandwidth cost nếu dùng cá nhân/nội bộ. Việc dùng Service Account giúp bảo mật và tách biệt quyền truy cập.
