# Prompt để giao việc cho AI

Dùng được với Claude, GPT, Gemini hoặc bất kỳ trợ lý lập trình nào chạy được lệnh trong repo (Claude Code, Cursor, Codex CLI, Aider…). Copy nguyên khối prompt, dán vào phiên mới.

| Khi nào | Dùng prompt |
|---|---|
| Bắt đầu phiên mới hoặc làm việc tiếp theo | **1. Làm việc tiếp theo** |
| AI vừa gửi đề xuất, bạn đã quyết | **2. Xác nhận đề xuất** |
| Muốn kiểm tra AI có làm đủ, làm đúng không | **3. Kiểm tra tiến độ** |
| Kết thúc một giai đoạn | **4. Nghiệm thu giai đoạn** |

## 1. Làm việc tiếp theo

```text
Bạn làm việc trong repo Second Memory (thư mục hiện tại). Trả lời tôi bằng tiếng Việt, dễ hiểu, gọi tính năng bằng tên đầy đủ, không nói bằng mã việc trừ khi cần dẫn chứng.

BƯỚC 1 — Đọc trước khi làm, theo thứ tự:
1. CLAUDE.md (bản đồ repo và quy tắc chung)
2. .claude/lab/PROGRESS.md (trạng thái hiện tại, nhật ký)
3. docs/plan/01-goals-and-workflow.md (mục tiêu, luồng làm việc, mẫu đề xuất, quy ước tài liệu)
4. docs/plan/02-roadmap.md và docs/plan/03-backlog.md (lộ trình, danh sách việc)
5. docs/dev-guide.md (chạy, test, sửa lỗi trên môi trường dev)
6. Các file trong .claude/rules/ liên quan tới phần code bạn sẽ sửa

BƯỚC 2 — Nhận việc:
- Chạy `scripts/lane.sh status`. Nhận việc đầu tiên trong "Claimable now" bằng `scripts/lane.sh claim <mã>`.
- Nếu có việc đang ở trạng thái `doing` từ phiên trước, làm tiếp việc đó, không claim lại.
- Mỗi lúc chỉ làm MỘT việc.

BƯỚC 3 — Đề xuất nếu cần:
Việc có chữ [XÁC NHẬN] trên bảng, hoặc việc sẽ: xóa hay thay thế tính năng, xóa hay di chuyển thư mục, thêm thư viện, đổi đăng nhập / phân quyền / cấu trúc DB
→ viết đề xuất theo mẫu trong 01-goals-and-workflow.md: làm gì, lợi ích, tác hại so với mục tiêu, các phương án và phương án bạn khuyên, câu hỏi cần tôi quyết.
→ DỪNG LẠI, chờ tôi trả lời. Không tự quyết thay tôi.

BƯỚC 4 — Làm:
- Chỉ làm trên môi trường dev. Không đụng staging, production, Terraform (infra/), đo tải (perf/), không bật CI/CD tự động.
- Mọi lệnh PHP / Node chạy qua Docker và `make` (máy không cài PHP, Node). Lệnh test bọc trong `scripts/lane.sh run`, ví dụ `scripts/lane.sh run make test f=AdminMst`.
- Code từ tag v1.0.0 (`git show v1.0.0:<đường dẫn>`) chỉ để tham khảo; viết lại cho khớp nền hiện tại.
- Code, tên biến, comment bằng tiếng Anh. Không dùng magic value: dùng Enum / hằng số.

BƯỚC 5 — Test nhanh:
- Chạy test của phần vừa sửa theo mục "Test nhanh" trong 01-goals-and-workflow.md. Đổi route hoặc dữ liệu API thì chạy `make openapi`.
- Báo kết quả THẬT (số test, pass/fail). Không chạy được thì nói rõ lý do, không được nói là pass.
- Ghi cho tôi các bước thử trên trình duyệt tại http://localhost:81 (đăng nhập root / 12345678).

BƯỚC 6 — Tài liệu (tiếng Việt, dễ hiểu):
- Cập nhật hoặc tạo file mô tả tính năng trong docs/features/.
- Có lệnh mới hoặc lỗi mới kèm cách sửa thì thêm vào docs/dev-guide.md.

BƯỚC 7 — Commit và đánh dấu tiến độ:
- Commit bằng `scripts/lane.sh commit "<type>(<scope>): <mô tả>" <các đường dẫn>`, theo Conventional Commits, mỗi commit một mục đích.
- Xong việc: `scripts/lane.sh done <mã> "<sha ngắn> — <kết quả một dòng>"`. Script tự cập nhật bảng việc, danh sách việc và nhật ký. KHÔNG sửa tay cột trạng thái.
- Bị chặn: `scripts/lane.sh block <mã> "<lý do>"`. Phát sinh việc mới: `scripts/lane.sh add main <MÃ-MỚI> "<việc>" "<tài liệu>" "<phụ thuộc>"`.

CẤM:
- git push, git add -A, git commit -a, git stash, git reset --hard, git clean, rebase, amend, đổi branch
- thêm dòng "Co-Authored-By" hoặc ghi chú AI vào commit
- đọc, in ra hoặc commit file .env và bí mật
- migrate:rollback hoặc make fresh trên DB dev (thử rollback trên DB testing như dev-guide hướng dẫn)
- xóa tính năng, xóa test để cho pass, tắt kiểm tra lỗi

BƯỚC 8 — Báo cáo cuối phiên (tiếng Việt):
1. Đã làm gì (tên việc đầy đủ)
2. Đã test gì, kết quả ra sao
3. Tôi cần thử gì trên trình duyệt
4. Tôi cần tự làm gì (push, tạo PR…)
5. Việc tiếp theo là gì, có cần tôi xác nhận gì không
```

## 2. Xác nhận đề xuất

Dán sau khi AI gửi đề xuất. Sửa phần trong ngoặc nhọn.

```text
Tôi xác nhận đề xuất cho việc <tên việc>:
- Chọn phương án: <phương án>
- Thay đổi so với đề xuất: <không có / ghi rõ>
- Trả lời các câu hỏi: <...>

Ghi lại quyết định này vào tài liệu của tính năng (docs/features/) hoặc một ADR nếu đó là quyết định kỹ thuật lớn, rồi làm tiếp theo luồng trong docs/plan/01-goals-and-workflow.md: làm → test nhanh → tài liệu → commit → scripts/lane.sh done.
```

## 3. Kiểm tra tiến độ

```text
Không sửa code. Kiểm tra repo Second Memory rồi báo cáo bằng tiếng Việt:

1. Chạy `scripts/lane.sh status` và `git log --oneline -20`. Liệt kê việc đã xong, đang làm, bị chặn.
2. Với mỗi việc đánh dấu done gần đây, đối chiếu cột "Kết quả" trong docs/plan/03-backlog.md với code và tài liệu thật:
   - kết quả đã có thật chưa (file, API, màn hình, test)?
   - có commit tương ứng không?
   - tài liệu tiếng Việt đã cập nhật chưa (docs/features/, docs/dev-guide.md)?
3. Chạy `scripts/lane.sh run make verify` và báo số test pass/fail thật.
4. Kiểm tra vi phạm quy tắc: có tính năng nào bị xóa mà không có xác nhận của tôi không? có thư viện mới không được duyệt không? có commit chứa .env hoặc bí mật không?
5. Kết luận dạng bảng: Việc | Trạng thái trên bảng | Thực tế | Thiếu gì.
```

## 4. Nghiệm thu giai đoạn

Dùng khi mọi việc của một giai đoạn đã `done`.

```text
Nghiệm thu giai đoạn "<tên giai đoạn>" trong docs/plan/02-roadmap.md. Không sửa code.

1. Đọc mục "Xong khi" của giai đoạn đó. Với từng điều kiện, kiểm tra bằng chứng thật: chạy test, gọi API bằng curl qua http://localhost:81 (cách lấy CSRF có trong docs/dev-guide.md), mở code liên quan.
2. Chạy `scripts/lane.sh run make verify` và `make e2e`.
3. Viết ra các bước để tôi tự thử trên trình duyệt (tối đa 10 bước, mỗi bước ghi kết quả mong đợi).
4. Báo cáo bảng: Điều kiện | Đạt / Chưa đạt | Bằng chứng. Điều kiện chưa đạt thì đề xuất việc cần thêm (dạng lệnh scripts/lane.sh add) nhưng KHÔNG tự thêm.
```
