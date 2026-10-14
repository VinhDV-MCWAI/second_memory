# ADR-0016 — CI/CD gộp chung trong thư mục `ci/`

| | |
|---|---|
| Trạng thái | Đã chấp nhận (2026-10-14, chủ dự án xác nhận) |
| Ngày | 2026-10-14 |
| Người quyết định | Chủ dự án |
| Liên quan | [ADR-0011](0011-infrastructure-as-code.md) (image production, Terraform), [ADR-0012](0012-restore-features-dev-first.md) (tạm dừng CI/CD tới giai đoạn 5) |

## Bối cảnh

| Chỗ | Tình trạng trước khi đổi |
|---|---|
| `.github/workflows/ci.yml` | Chỉ chạy tay. Cài PHP và Node thẳng lên máy GitHub, khác cách dev chạy trong container, nên hai nơi có thể cho kết quả khác nhau |
| So với `make verify` | GitHub thiếu lint + test của công cụ nhập ghi chú Obsidian; `make verify` thiếu build image production và kiểm tra lỗ hổng thư viện |
| `make verify` | Chạy trong container dev đang bật, dùng chung DB `testing`, kiểm tra cả file chưa commit |
| `.github/workflows/cd.yml` + `ci-cd/deploy.sh` | Chỉ chạy tay. Script deploy blue-green **đã hỏng**: truyền `API_IMAGE`, `FE_IMAGE`, `APP_PORT` mà `docker-compose.yml` không còn dùng, nên thực tế dựng stack dev; deploy vào một máy cố định |
| `infra/build-images.sh` | Build image production từ code đã commit (cách build hiện hành, ADR-0011) |

## Quyết định

1. **Kiểm tra chạy trong container riêng cho CI** (phương án A). Thư mục `ci/` chứa các script; `make ci` trên máy, GitHub và GitLab đều gọi đúng các script đó, nên kết quả giống nhau. Môi trường CI có Postgres + Redis riêng (project Docker riêng), không đụng DB dev hay DB `testing` của dev.
2. **Giữ cả hai lệnh**: `make verify` để kiểm tra nhanh lúc đang code (container dev, file chưa commit); `make ci` để kiểm tra giống hệt GitHub (container sạch, code đã commit).
3. **Xóa deploy blue-green đã hỏng** (`ci-cd/deploy.sh`, `ci-cd/nginx-proxy.conf.template`, `.github/workflows/cd.yml`). Code cũ vẫn còn trong lịch sử git. Deploy mới làm ở giai đoạn 6, dùng Terraform.
4. **Build image production chuyển vào `ci/build.sh`** (lấy từ `infra/build-images.sh`); `make tf-images` gọi sang đó. Một chỗ build duy nhất cho CI và Terraform.
5. **Viết sẵn `.gitlab-ci.yml`** mỏng, gọi cùng script. Chưa test được vì chưa có tài khoản GitLab, ghi rõ trong file.
6. **Kiểm tra lỗ hổng thư viện** (`composer audit`, `pnpm audit`) chạy trong `make ci` nhưng chỉ để báo, không làm hỏng lần chạy.
7. File riêng của GitHub (`cleanup-branch.yml`, `dependabot.yml`, mẫu PR, mẫu issue) giữ nguyên: đó không phải logic kiểm tra.

## Bổ sung 2026-10-14: CD trên máy, tắt trên GitHub

Chủ dự án muốn có bản demo trước, nên CI/CD **chạy và test trên máy**; chạy trên GitHub để làm sau.

1. `make cd` (`ci/deploy.sh`): `ci/check.sh` → `ci/build.sh` → Terraform dựng môi trường `prod-like` (phương án Terraform, không dùng Compose riêng cho demo, để giai đoạn "Staging, production" dùng lại) → test trình duyệt Playwright trên bản demo.
2. Môi trường `prod-like` thêm MinIO, việc nền, hẹn giờ, Reverb, đăng ký quyền và tài khoản `root` (chỉ demo).
3. GitHub, GitLab: không chạy gì khi push hay mở pull request; chỉ chạy tay. `cleanup-branch.yml` và Dependabot giữ nguyên. Cách bật lại: [ci/README.md](../../ci/README.md).

## Hệ quả

- Thêm thư mục `ci/`; bỏ thư mục `ci-cd/`.
- `make ci` lần đầu chậm hơn `make verify` (dựng container sạch, cài thư viện); các lần sau dùng bộ nhớ đệm.
- Tự động chạy khi mở pull request vẫn tắt; hướng dẫn bật lại ở [ci/README.md](../../ci/README.md).
