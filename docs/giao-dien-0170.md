# SK Bento 0.17.0 (12/11/2026): trang Tuyển đại lý

Trang `/tuyen-dai-ly/`, chỉ có bản tiếng Việt, chỉ nút **Gọi** và **Nhắn Zalo**, không form.

## Nội dung (theo brief chủ gửi 12/11/2026)
- Đại lý lắp đặt: lợi nhuận **20-50%**. Đại lý phân phối cấp 1: lợi nhuận **5-20%**. Chỉ dùng đúng hai con số này.
- Không ghi giá sản phẩm, không ghi % giảm giá, không chia hạng đại lý, không tự thêm cam kết hay thông số.
- Các khối: mở đầu, 4 lợi ích, lợi nhuận đại lý lắp đặt, mẫu mới (số mẫu xe tự đếm + 6 ảnh), đặc quyền cấp 1, lời kêu gọi cuối.
- **Chính sách đại lý chi tiết:** chủ gõ vào ô nội dung của trang "Tuyển đại lý" trong WordPress, trang tự hiện thêm khối "Chính sách đại lý". Để trống thì khối này ẩn.

## Tệp
- Mới: `page-tuyen-dai-ly.php` (giao diện và chữ), `inc/tuyen-dai-ly.php` (tự tạo trang một lần, mô tả SEO và ảnh chia sẻ mặc định), `assets/img/tuyen-dai-ly/` (13 ảnh).
- Sửa: `functions.php` (nạp tệp mới, `SKB_VER` 0.17.0), `style.css` (khối `.td-*`, đặt trước `@font-face`), `inc/seo.php` (mô tả và `og:image` của trang), `inc/da-ngu.php` (bản nước ngoài vào trang này thì chuyển sang Liên hệ), `footer.php` (liên kết "Tuyển đại lý" ở chân trang tiếng Việt).

## Ảnh
- Ảnh thật đã lắp trên xe do chủ gửi (VF6, VF7, Civic 11, Everest). Giấy dán trên kính và biển hiệu ở nền đã che mờ.
- WebP, hai cỡ cho ảnh lớn (điện thoại và máy tính). `chia-se.jpg` (1200x675) là ảnh hiện khi chia sẻ liên kết lên Zalo, Facebook.
- 5 poster (4 ngang 1920x1080, 1 dọc 1080x1620) đã giao cho chủ để đăng mạng xã hội, không nằm trong kho.

## Sau khi đưa lên hosting
1. Đưa các tệp ở mục "Tệp" lên (thư mục ảnh là tệp nhị phân).
2. Vào trang quản trị WordPress một lần: trang "Tuyển đại lý" tự được tạo nếu chưa có (đánh dấu ở option `skb_trang_tuyen_dl`; xoá trang sau đó thì không tự tạo lại).
3. Xoá bộ nhớ đệm, mở `/tuyen-dai-ly/` kiểm tra trên điện thoại.
4. Muốn có trong menu chính: Giao diện › Menu, thêm trang "Tuyển đại lý".
