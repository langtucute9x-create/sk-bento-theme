# SK Bento 0.16.0 – 0.16.1 (12/11/2026)

## 0.16.0 — trang chủ tiếng Việt thêm 4 khối
Chủ chọn 4 mục học từ javisos.com. Chỉ ở **trang chủ tiếng Việt**.
- **Khối nỗi lo** (`.lo`), ngay trên 4 ưu điểm: "Ngại lắp đèn nội thất vì những điều này?" + 3 thẻ.
- **Dải số liệu** (`.so`), ngay dưới 4 ưu điểm: số mẫu xe, số hãng, số đại lý (tự đếm) và 24 tháng bảo hành. Số in sẵn trong trang.
- **Câu hỏi thường gặp** (`.hd`): 6 câu, kèm JSON-LD `FAQPage` khớp chữ hiển thị. Chỉ dùng thông tin web đã công bố; chưa có câu về ắc quy và thời gian lắp vì chưa có câu trả lời chính thức của chủ.
- **Bài viết mới**: 3 bài mới nhất (không gồm chuyên mục Hướng dẫn sử dụng), có ngày đăng và số phút đọc.
- Tệp: mới `template-parts/trang-chu-vi.php` (cả 4 khối; sửa chữ ở đây); sửa `front-page.php`, `style.css`, `functions.php`.
- Ảnh đại diện: bài 1527 → ảnh 1529, bài 1526 → ảnh 1528 (ảnh minh hoạ có sẵn trong Thư viện).

## 0.16.1 — tốc độ (sau khi PageSpeed điện thoại báo 80 điểm)
Báo cáo 21:50 ngày 12/11: FCP 1,7 s; **LCP 4,4 s** (phần tử là dòng tiêu đề lớn đầu trang, trễ hiển thị 2,5 s); TBT 10 ms; CLS 0; Speed Index 5,3 s. Google Fonts chặn hiển thị 750 ms và tải 224 KiB phông.
- **Phông chữ đặt trong theme** (`assets/fonts`, 10 tệp woff2 lấy từ gói Fontsource; khai báo `@font-face` ở cuối `style.css`). Bỏ hẳn lệnh gọi Google Fonts. Tải trước 2 tệp Montserrat (`functions.php`, hook `wp_head`).
- **Logo** có `width`/`height` (`header.php`).
- **`.htaccess` trong thư mục theme** (đặt hạn bộ nhớ đệm 1 năm cho css/js/woff2): **không có tác dụng** (đã bỏ ở 0.16.2), máy chủ vẫn trả 7 ngày cho css/ảnh/phông và không có hạn cho js. Luật đang thắng nằm ở `.htaccess` gốc, cần xử lý ở đó.
- Chưa đo lại được PageSpeed sau khi sửa.

### Việc tốc độ còn lại
- Ảnh tấm ốp đầu trang tải lớn hơn cỡ hiển thị (PageSpeed ước tính tiết kiệm 342 KiB): sửa `sizes` và thêm cỡ ảnh nhỏ cho điện thoại.
- Hạn bộ nhớ đệm cho js và tăng hạn cho tệp tĩnh.
- Speed Index cao một phần do hiệu ứng hiện dần ở đầu trang.

## Kho mã và cách đưa lên hosting
- Kho GitHub **`langtucute9x-create/sk-bento-theme`** (công khai), nhánh `main`. Mốc: `0b23004` = 0.15.1, `ae4349a` = 0.16.0, `ad29082` = 0.16.1, `e8be99e` = 0.16.2.
- Quy trình: sửa trong kho → `php -l` → đẩy lên GitHub → ở tab DirectAdmin (chủ đã đăng nhập), lấy tệp từ `raw.githubusercontent.com/<kho>/<mã mốc>/<tệp>` rồi lưu qua `CMD_FILE_MANAGER` (tệp nhị phân: `CMD_API_FILE_MANAGER` `action=upload`) → bấm "Xoá bộ nhớ đệm".
- Trước khi ghi đè, so tệp trên hosting với mốc trước trong kho để chắc không ai sửa tay trên hosting.
- Quay lại: lấy các tệp ở mốc cũ đưa lên hosting.

## Chưa làm
- Bản dịch 4 khối trang chủ cho 6 ngôn ngữ.
