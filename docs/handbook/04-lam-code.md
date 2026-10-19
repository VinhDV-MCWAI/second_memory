# 4. Làm code, commit, bảng việc

## Nhận và theo dõi việc

Mọi việc nằm trên [.claude/lab/BOARD.md](../../.claude/lab/BOARD.md) và chỉ đổi trạng thái qua `scripts/lane.sh`. Quy tắc đầy đủ: [.claude/rules/parallel-lanes.md](../../.claude/rules/parallel-lanes.md).

| Bước | Lệnh | Tác dụng |
|---|---|---|
| Xem việc | `scripts/lane.sh status` | Việc nào đang làm, việc nào nhận được ngay |
| Nhận việc | `scripts/lane.sh claim <mã>` | `todo` → `doing`; báo lỗi nếu việc đã có người nhận hoặc việc phụ thuộc chưa xong |
| Sửa file dùng chung | `lock <file>` → sửa → `commit` → `unlock <file>` | `Makefile`, `docs/plan/*`, `CLAUDE.md`…: giữ khóa vài phút, không giữ cả việc |
| Chạy kiểm tra | `scripts/lane.sh run make test f=…` | Hai phiên không chạy test cùng lúc trên DB `testing` |
| Commit | `scripts/lane.sh commit "<message>" <file…>` | Chỉ stage và commit đúng các file đó |
| Bị chặn | `scripts/lane.sh block <mã> "<lý do>"` | Chờ chủ dự án |
| Xong | `scripts/lane.sh done <mã> "<commit> — <kết quả>"` | Cập nhật bảng việc, danh sách việc, nhật ký tiến độ |

Không dùng các lệnh git đụng tới cả thư mục (`add -A`, `commit -a`, `stash`, `restore`, `reset --hard`, `clean`, rebase, amend, đổi branch): chúng chạm vào việc chưa commit của người khác.

## Branch

Hiện tại mọi việc trên **một branch** (`feature/p3-ledger-api`). Chủ dự án push và mở pull request `developer` → `main`. Trong doanh nghiệp thường gặp hơn:

| Branch | Dùng cho | Sống bao lâu |
|---|---|---|
| `main` | Code đã phát hành | Mãi mãi, chỉ merge qua pull request |
| `developer` | Gom việc đã xong | Mãi mãi |
| `feature/…`, `fix/…`, `refactor/…` | Một việc | Vài giờ tới vài ngày, xóa sau khi merge |
| `hotfix/…` | Sửa gấp production, tách từ `main`, merge về cả `main` và `developer` | Vài giờ |

Branch sống càng ngắn càng ít xung đột.

## Commit

Theo Conventional Commits:

```text
<loại>(<phạm vi>): <tóm tắt, thể mệnh lệnh, tiếng Anh, ≤ 72 ký tự>

<thân: vì sao làm, không phải làm gì; có thể tiếng Việt>
```

| Loại | Dùng khi |
|---|---|
| `feat` | Hành vi mới cho người dùng |
| `fix` | Sửa lỗi |
| `refactor` | Đổi code, không đổi hành vi |
| `perf` | Nhanh hơn |
| `test` | Chỉ test |
| `docs` | Chỉ tài liệu |
| `build`, `ci`, `chore` | Build, CI, công cụ |

Phạm vi: `api`, `fe`, `public`, `docker`, `ci`, `infra`, `tools`, `plan`, `lab`. Thay đổi phá vỡ tương thích: `feat(api)!: …` và dòng `BREAKING CHANGE:` trong thân.

Quy tắc: mỗi commit một mục đích; commit nào cũng build được; không có dòng ghi công AI; không commit bí mật, dump DB, file `.env`. Sửa lỗi thấy trong lúc refactor thì commit riêng và ghi rõ.

## Quy tắc viết code

Quy tắc nào máy kiểm được thì để máy kiểm (`make lint`, `make analyse`, `make verify`). Chi tiết theo từng phần: `.claude/rules/` ([backend](../../.claude/rules/backend-laravel.md), [giao diện](../../.claude/rules/frontend-nextjs.md), [hạ tầng](../../.claude/rules/infra.md)).

| Quy tắc | Vì sao |
|---|---|
| Code, tên biến, comment bằng tiếng Anh | Ai cũng đọc được, công cụ hiểu được |
| Không có giá trị "ma thuật": dùng Enum, hằng số, config, khóa thông báo | Sửa một chỗ; ý nghĩa có tên |
| Tên nói *làm gì*, comment nói *vì sao* | Comment lặp lại code sẽ sai dần |
| Hàm nhỏ một việc; return sớm thay vì lồng nhau | Dễ test, dễ đọc |
| Kiểm tra đầu vào ở biên; không nuốt lỗi | Lỗi im lặng thành sự cố |
| Không để code chết, code comment lại, file `.bak` | Git đã giữ lịch sử |
| Ghi log có ngữ cảnh (mã bản ghi), không ghi bí mật hay dữ liệu cá nhân | Log được đọc lúc sự cố và có thể bị lộ |
| Thêm thư viện phải nêu lý do | Mỗi thư viện là chi phí bảo mật và nâng cấp |
| Đơn giản hơn khéo léo; chỉ trừu tượng hóa khi có ít nhất hai chỗ dùng | Trừu tượng sớm là kiểu làm quá phổ biến nhất |

Backend đi theo tầng: Route → FormRequest (kiểm tra đầu vào) → Controller (mỏng) → Service (quy tắc nghiệp vụ) → Repository (truy vấn) → Model; Resource định dạng JSON; Job / Event cho việc chạy ngầm.

## Đổi thứ mà nơi khác đang gọi

Đổi cổng, tên host, biến môi trường, route, dạng dữ liệu API: tìm mọi chỗ dùng trong cả repo (`git grep <tên>`) và ghi vào ghi chú của việc. Đổi API thì chạy `make openapi` để `nextjs-fe` và `public-site` có kiểu dữ liệu mới, và sửa cả hai trong cùng thay đổi.

## Danh sách kiểm khi review

| Mặt | Hỏi |
|---|---|
| Đúng | Có làm đúng tiêu chí chấp nhận không? Trường hợp biên, lỗi, rỗng? |
| Thiết kế | Đúng tầng chưa? Có lặp code không? Có cách đơn giản hơn không? |
| Hợp đồng | Đổi API đã cập nhật OpenAPI, kiểu dữ liệu giao diện, trang public chưa? Còn tương thích không? |
| Dữ liệu | Migration quay lui được không? Có index không? Có truy vấn lặp N+1 không? |
| Bảo mật | Mỗi API có kiểm quyền không? Đầu vào có kiểm tra không? Không có bí mật, không ghi dữ liệu nhạy cảm vào log? |
| Test | Có test đúng mức? Bỏ thay đổi đi thì test có fail không? |
| Vận hành | Log có ích không? Thông báo lỗi rõ không? Cấu hình qua `config()`? |
| Dễ đọc | Tên, không có giá trị ma thuật, comment nói vì sao |

## Xử lý xung đột

1. Hiểu **cả hai phía** trước khi chọn: đọc commit bên kia (`git log -p <branch> -- <file>`).
2. File sinh tự động (`pnpm-lock.yaml`, `openapi.json`, file kiểu dữ liệu): không sửa tay, lấy một bên rồi sinh lại (`pnpm install`, `make openapi`).
3. Chạy test của phần bị đụng.
4. Xung đột về **hành vi** (hai người đổi cùng một quy tắc): dừng lại hỏi người kia hoặc trưởng nhóm.

## Làm việc với developer khác và người review

| Tình huống | Developer có kinh nghiệm làm gì | Hay sai ở đâu |
|---|---|---|
| Mở pull request | Nhỏ (dưới khoảng 400 dòng), mô tả làm gì, vì sao, đã test thế nào, rủi ro, cách quay lui; tự review một lượt trước | Gửi một pull request khổng lồ không mô tả, người review đành bấm duyệt cho qua |
| Nhận góp ý | Trả lời mọi góp ý: sửa, hoặc giải thích vì sao không sửa. Không tự đóng góp ý đang còn tranh luận | Coi góp ý là bị chê; sửa im lặng hoặc bỏ qua |
| Review code người khác | Góp ý vào code, không vào người; góp ý không bắt buộc ghi `nit:`; duyệt khi chỉ còn `nit:` | Bắt bẻ phong cách mà linter đã lo; giữ pull request vài ngày không xem |
| Hai người sửa cùng chỗ | Báo sớm, thống nhất ai làm phần nào, hoặc làm tuần tự | Mỗi người sửa một kiểu rồi để người merge sau gánh xung đột |
| Phải sửa code của người khác | Hỏi người viết lý do của cách làm hiện tại trước | Xóa đi viết lại, làm mất một trường hợp đặc biệt mà code cũ đang xử lý |
| Kiểm tra chung bị đỏ vì việc của người khác | Báo cho người đó, kèm lỗi; không tự sửa phần của họ | Sửa giùm làm lẫn hai thay đổi, hoặc tắt test cho qua |
