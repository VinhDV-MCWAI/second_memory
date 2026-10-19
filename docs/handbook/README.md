# Sổ tay quy trình

Cập nhật: 2026-10-19. Lý do có sổ tay này: mục tiêu 5 trong [01-goals-and-workflow.md](../plan/01-goals-and-workflow.md) và [ADR-0018](../adr/0018-lifecycle-practice-track.md). Viết lại từ [sổ tay cũ](../archive/engineering-lab/handbook/README.md) (tiếng Anh, 10 chương), cho khớp cách làm hiện tại.

Sổ tay này có hai việc:

1. **Cách làm thật trong dự án này**: mỗi việc đi qua những bước nào, ra tài liệu gì, ai quyết.
2. **Kinh nghiệm làm việc với các vai trong doanh nghiệp**: người đặt yêu cầu, trưởng nhóm kỹ thuật, người kiểm thử, người vận hành, bảo mật, người dùng. Mỗi chương có mục "Làm việc với …" ghi lại một developer có kinh nghiệm sẽ làm gì, hỏi gì, tránh gì khi làm việc với vai đó. Đây là ghi chép kinh nghiệm, **không phải** quy trình để AI đóng giả các vai (chủ dự án quyết 2026-10-19).

## Vòng đời một việc

```
Yêu cầu ─► Làm rõ ─► Sẵn sàng? ─► Thiết kế ─► Làm code ─► Test, nghiệm thu ─► Phát hành, bàn giao ─► Vận hành
   ▲          ▲         │ chưa                                                                          │
   │          └─────────┘                                                                               │
   └──────────────────── phản hồi của người dùng, sự cố, nhìn lại ◄──────────────────────────────────────┘
```

| Bước | Chương | Kết quả chính |
|---|---|---|
| Hai tốc độ, ai làm gì, báo cái gì cho ai | [01-tong-quan.md](01-tong-quan.md) | Biết việc này đi đường tắt hay đủ quy trình |
| Yêu cầu → sẵn sàng làm | [02-yeu-cau.md](02-yeu-cau.md) | Đề xuất hoặc hồ sơ yêu cầu `REQ-…` có tiêu chí chấp nhận |
| Thiết kế | [03-thiet-ke.md](03-thiet-ke.md) | Phương án được duyệt, ADR |
| Làm code | [04-lam-code.md](04-lam-code.md) | Commit nhỏ qua `scripts/lane.sh`, code đúng quy tắc |
| Test, kiểm thử, nghiệm thu | [05-test-nghiem-thu.md](05-test-nghiem-thu.md) | Test tự động, kế hoạch test, báo lỗi, biên bản nghiệm thu |
| Phát hành, bàn giao | [06-phat-hanh-ban-giao.md](06-phat-hanh-ban-giao.md) | Tag, ghi chú phát hành, tài liệu bàn giao |
| Vận hành, sự cố, nhìn lại, tài liệu | [07-van-hanh-tai-lieu.md](07-van-hanh-tai-lieu.md) | Runbook, báo cáo sự cố, buổi nhìn lại |

## Sổ tay khác các tài liệu khác thế nào

| Tài liệu | Trả lời câu hỏi |
|---|---|
| Sổ tay này | Làm một việc **như thế nào**, theo thứ tự nào |
| [docs/plan/](../plan/) | Làm **việc gì**, việc nào trước |
| [docs/dev-guide.md](../dev-guide.md) | Chạy, test, sửa lỗi trên dev bằng **lệnh** gì |
| [docs/features/](../features/) | Mỗi tính năng **đang** làm gì |
| `.claude/rules/` | Quy tắc chi tiết cho Claude theo từng thư mục (tiếng Anh) |

## Sửa sổ tay

- Sổ tay được sửa như code: commit riêng, nêu lý do (thường từ một buổi nhìn lại).
- Quy tắc nào hai lần phát hành liền không ai dùng thì **xóa**, không giữ làm cảnh.
- Thêm kinh nghiệm của chủ dự án vào mục "Làm việc với …" của chương liên quan: một dòng bảng cho mỗi tình huống, nếu được thì kèm ví dụ thật.
