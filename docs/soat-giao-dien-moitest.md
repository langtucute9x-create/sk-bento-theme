# Soát giao diện /moitest (theme SK Bento 0.13.1) — 10/11/2026

Tiêu chí: Impeccable `audit` (pbakaus/impeccable, Apache-2.0, 4.8.2) + Vercel Web Interface Guidelines (vercel-labs, MIT).
Cách soát: đọc mã theme + đo trực tiếp 6 trang ở 375px, 1009px, 1440px. Bộ dò tự động của Impeccable không chạy được (hệ thống chặn chạy chương trình tải về).

## Điểm lúc soát (0–4 mỗi mục): 13/20
Tiếp cận 3 · Hiệu năng 2 · Màu/token 2 · Điện thoại/màn hình 3 · Tính nhất quán 3.

## Trạng thái các lỗi (cập nhật 10/11/2026 chiều)
- **P1 Ảnh gốc trong mô tả mẫu xe — ĐÃ SỬA** (Công cụ › Tối ưu ảnh nội dung, `inc/toi-uu-anh.php`): 496 thẻ ảnh / 67 bài → 381 file Thư viện, WebP 300/768/1024/1536, srcset + lazy; ảnh mặc định trung bình 58 KB; VF8 điện thoại 10,8 MB → 232 KB.
- **P2 Focus ô nhập yếu — ĐÃ SỬA 0.13.2** (viền cam 2px; cột bản đồ vòng trắng khi chọn bằng bàn phím).
- **P2 Giá gốc gạch ngang 4,02:1 — ĐÃ SỬA 0.13.2** (`var(--muted)`, 7,16:1).
- **P2 Bản đồ đại lý trên điện thoại (điểm chạm chồng nhau) — ĐÃ SỬA 0.13.3:** hàng nút chọn tỉnh `.vn-chip` (tên + số đại lý, cao 44px, xếp Bắc → Nam) trong chú thích bản đồ ở cả trang chủ và trang Đại lý. Trang Đại lý: nút lọc như cột sáng (đồng bộ ô chọn tỉnh, `aria-current`), bấm lại để bỏ lọc; trang chủ: mở `/dai-ly/?tinh=`. Lượt bấm nút tính vào sự kiện `ban_do`.
- **P2 Thẻ đại lý chưa có ảnh — ĐÃ SỬA 0.13.3:** ẩn khung logo giữ chỗ ở ≤760px; danh sách 17 đại lý trên điện thoại ~4.870px (trước ~6.970px với 18 đại lý).
- **P2 Đầu trang 900–1280px chữ bị tấm ốp đè — ĐÃ SỬA 0.13.3:** 761–1100px (hoặc màn hình dọc): chữ bắt đầu dưới cụm tấm ốp (`padding-top: calc(60px + 46svh)`); máy tính ≥1101px: tiêu đề `clamp(40px,4.8vw,84px)`, cột chữ ≤46vw, cụm tấm ốp 56% bên phải, lớp tối sau chữ đậm hơn. Đã xem ở 1024×768, 1280×800, 1440×900, 375×812.
- **P3 — ĐÃ SỬA:** `color-scheme: dark` (0.13.2); ô "Ngày mong muốn" có `min` = hôm nay; "…" thay "..." ở ô ghi chú; ảnh nền mờ đầu trang `fetchpriority="low"` khi dùng ảnh tấm ốp (0.13.3).
- **Màu viết cứng trong style.css — ĐÃ SỬA 0.15.0** (gom về biến; xem `giao-dien-0150.md`).

## Điểm tốt nên giữ
Skip link, mỗi trang 1 H1, mọi ảnh có alt, mọi ô có nhãn, nút biểu tượng có tên; giảm chuyển động xử lý đủ; form có autocomplete, bàn phím số, báo lỗi tại ô; ô tìm xe đúng chuẩn combobox; không tràn ngang ở 375px; thanh dưới có safe-area; nút chính ≥44px; ảnh đầu trang preload + srcset; video bấm mới tải. Thiết kế mang chất riêng của SK.
