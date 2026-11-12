# SK Bento 0.15.0 – 0.15.1 — đợt sửa giao diện 12/11/2026

Chủ yêu cầu áp các điểm học từ trang hocvalamai.net ("làm hết Nên học"), đổi biểu tượng tab trình duyệt sang logo SK, rồi đưa khối hệ sinh thái lên các bản dịch.
Đã chạy trên https://sklight.vn tối 12/11/2026. Sau bản 0.15.1 đã kiểm 1.051/1.051 trang trong sitemap (181 tiếng Việt, 870 nước ngoài): đều 200, không lỗi PHP, khối hệ sinh thái đủ 5 thẻ, bản nước ngoài không còn chữ tiếng Việt trong khối.

## Đã đổi
- **Biểu tượng tab (favicon):** ảnh vuông 512px cắt chữ "SK" từ logo, nền tối. Thư viện ID **1535** (`sk-ambient-light-bieu-tuong`), đặt ở Cài đặt › Tổng quan › Biểu tượng trang. Trước đó web chưa có biểu tượng.
- **Biến màu (`style.css`, khối `:root`):** 121 chỗ màu viết cứng chuyển về biến. Biến mới: `--bg-2`, `--pop`, `--line-2`, `--border`, `--text-2`, `--dim`, `--white`, `--accent-soft`, `--accent-pale`, `--on-accent`, `--brand-mid`, `--brand-1d/2d/3d`, `--grad-cta`, `--link`, `--link-hover`, `--err`, `--err-soft`, `--ok-soft`, `--zalo`, `--wa`, `--font`, `--font-head`. Còn 28 sắc trang trí dùng một lần (nền chuyển màu, bản đồ) để nguyên tại chỗ.
  - Gộp sắc gần nhau: `#8b8b96` → `--dim`, `#c9c9d1` → `--soft`, `#e8e8ee` → `--text-2`, `#141418` → `--tile`.
- **Nút chính:** `.btn-light`, `.mbar a.main`, nút đầu tiên trong nội dung bài (`.wp-block-button__link`) dùng `--grad-cta` (tím → đỏ → cam đậm, chữ trắng) thay nền trắng. Liên kết phụ dùng một màu `--link`.
- **Thẻ:** `.tile`, `.page-card`, `.post-card`, `.dealer`, `.brand`, `.dl-side`, `.vnmap` có viền mảnh `--border`.
- **Phông chữ:** Be Vietnam Pro (nội dung, 400–700) + Montserrat (tiêu đề, 700–800) thay Inter. Bản Trung / Hàn / Thái / Hindi: tiêu đề dùng phông của ngôn ngữ đó. (Từ 0.16.1 phông đặt trong theme, không gọi Google Fonts.)
- **Đầu trang chủ:** nhãn nhỏ thành viên bo tròn có chấm sáng; ô "Xe của bạn là gì?" to hơn và chuyển lên trên hai nút (`front-page.php`).
- **Khối "Hệ sinh thái SK Ambient Light"** (`footer.php`, class `.hst`), trên chân trang mọi trang: Website chính (nhãn "Bạn đang ở đây"), Catalog phụ kiện 3D, YouTube, TikTok, Facebook (3 địa chỉ chính thức chủ gửi 11/11).
  - **0.15.1:** hiện ở cả 6 bản nước ngoài (chủ yêu cầu). 9 câu mới ở cuối `inc/da-ngu-tu-dien.php` (mục "0.15.1 — khối hệ sinh thái ở chân trang"). Ở bản nước ngoài, mô tả thẻ Website là "Mẫu xe, gói sản phẩm và giá" (không nhắc đại lý và kích hoạt, vì hai mục này ẩn ở bản nước ngoài).
- **Nút Zalo nổi:** máy tính hiện "Tư vấn Zalo" kèm biểu tượng (`.zalo-pill`); điện thoại giữ hình tròn chữ "Zalo" để không che nút bấm.
- **Liên kết bỏ qua đến nội dung:** đã có sẵn từ trước, chỉ chuyển màu sang biến.

## Tệp đã sửa
`style.css`, `footer.php`, `front-page.php`, `functions.php` (`SKB_VER` = 0.15.1), `inc/da-ngu-tu-dien.php`.

## Quay lại
- Bản 0.14.0 của các tệp trên và `footer-0.15.0.php` được lưu trong thư mục sao lưu trên hosting (`theme-0.14.0/`); từ 0.15.1 trở đi dùng các mốc trong kho GitHub.
- Chép đè vào thư mục theme rồi bấm "Xoá bộ nhớ đệm".
- Bỏ biểu tượng tab: Cài đặt › Tổng quan › Biểu tượng trang › Gỡ.

## Chưa làm
- Khối hệ sinh thái và nút tương ứng bên catalog.sklight.vn (dự án Lovable, chủ tự đẩy lên Vercel).
