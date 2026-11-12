# SK Bento — hướng dẫn cho Claude

Theme WordPress tự viết của **https://sklight.vn** (SK Ambient Light: LED nội thất ô tô ốp thay zin theo từng dòng xe). Web chạy chính thức từ 10/11/2026. Chủ web không phải lập trình viên: trả lời bằng **tiếng Việt**, giải thích dễ hiểu, tránh thuật ngữ khi không cần.

Kho này **công khai**. Không ghi mật khẩu, khoá, địa chỉ máy chủ, tên đăng nhập hay dữ liệu khách hàng vào bất kỳ tệp nào. Khoá nằm trong `wp-config.php` trên hosting.

## Đọc trước khi làm
Tài liệu dự án nằm trong `docs/` (xem `docs/README.md`). Quan trọng nhất:
- `docs/ke-hoach-web-sklight.md`: toàn cảnh, các quyết định chủ đã chốt, việc tiếp theo.
- `docs/giao-dien-0170.md`, `docs/giao-dien-0160.md`, `docs/giao-dien-0150.md`: các đợt sửa gần nhất.
- `docs/bao-mat-sklight.md`: phần bảo mật trong theme.
- `docs/ban-giao-web-sklight-cho-javis.md`: cấu trúc nội dung, trường SEO, đa ngôn ngữ, quy tắc cho Javis (tác tử SEO của chủ).

## Nền tảng
- WordPress 7.1.4, PHP 8.5, hosting DirectAdmin + LiteSpeed. Không WooCommerce, không plugin SEO, không plugin đa ngôn ngữ: tất cả làm trong theme.
- Loại nội dung: `mau_xe` (sản phẩm), `hang_xe` (hãng gốc, dòng xe là mục con), `dai_ly`, `lich_hen`.
- Đa ngôn ngữ: tiếng Việt là gốc; `/en/ /zh/ /ko/ /th/ /de/ /hi/` do `inc/da-ngu.php` + `inc/da-ngu-tu-dien.php`. Bản nước ngoài ẩn Đặt lịch, Đại lý, Kích hoạt; dùng WhatsApp.
- Đặt lịch và kích hoạt bảo hành gọi sang SK Core (core.sklight.vn) qua `inc/dat-lich-core.php`, `inc/kich-hoat.php`.
- Catalog phụ kiện (catalog.sklight.vn) là dự án riêng, không nằm trong kho này. **Giá: catalog là nguồn đúng.**

## Sơ đồ mã
- `functions.php`: khai báo chung, `SKB_VER`, nạp `inc/*`.
- `style.css`: toàn bộ CSS; màu dùng biến trong `:root`; `@font-face` ở cuối (phông trong `assets/fonts`).
- `front-page.php` + `template-parts/trang-chu-vi.php`: trang chủ (4 khối riêng của bản tiếng Việt).
- `single-mau_xe.php`, `inc/goi-san-pham.php`, `inc/bo-anh.php`: trang mẫu xe, gói giá, bộ ảnh.
- `inc/seo.php` + `assets/seo-editor.js`: SEO trong theme (meta `_skb_seo_*`).
- `inc/bao-mat.php`, `inc/bo-nho-dem.php`, `inc/chuyen-huong.php`, `inc/toi-uu-anh.php`, `inc/catalog.php`, `inc/tim-xe.php`, `inc/ban-do-dai-ly.php`, `inc/khach-that.php`, `inc/su-kien.php`.
- `page-tuyen-dai-ly.php` + `inc/tuyen-dai-ly.php`: trang Tuyển đại lý (chỉ tiếng Việt; lợi nhuận 20-50% và 5-20%, không ghi giá).

## Quy tắc khi sửa
1. Đổi tệp JS/CSS thì nâng `SKB_VER` trong `functions.php`; ghi phiên bản vào đầu thông điệp commit (ví dụ `0.16.3: …`).
2. Chạy `php -l` cho mọi tệp PHP đã sửa trước khi commit.
3. Chữ hiển thị mới ở phần dùng chung phải thêm bản dịch vào `inc/da-ngu-tu-dien.php` (mỗi dòng: tiếng Việt → 6 bản dịch).
4. Máy chủ **không áp dụng** `Header`, `FilesMatch`, `Require`, `mod_expires` trong `.htaccess`; chỉ luật rewrite chạy. Header gửi bằng PHP; chặn bằng `RewriteRule … - [F]`.
5. Ưu tiên điện thoại (phần lớn khách dùng điện thoại). Nền tối, màu nhấn theo logo (tím #7D2566 → đỏ #DF1C4E → cam #F49B3F), sang trọng, không loè loẹt.
6. Thông tin phải đúng: bảo hành **24 tháng** từ ngày kích hoạt; hotline 0868 055 555; Navara là xe Nissan. Không tự sửa giá.
7. Không thêm `aggregateRating`/`review` khi chưa có đánh giá thật. Không cài plugin SEO.

## Đưa lên web
**Đẩy lên GitHub không cập nhật web.** Sau khi commit, từng tệp đã sửa phải được đưa lên thư mục `wp-content/themes/sk-bento` trên hosting qua DirectAdmin (chủ đăng nhập; phiên hết hạn sau 1–2 giờ), rồi bấm Công cụ › "Xoá toàn bộ bộ nhớ đệm máy chủ". Trước khi ghi đè, so tệp trên hosting với mốc trước trong kho để chắc không ai sửa tay. Quay lại: đưa tệp ở mốc cũ lên. Chi tiết: `docs/giao-dien-0160.md`.

Thư mục `docs/` và tệp này chỉ để trong kho, không đưa lên hosting.

## Việc đang mở (12/11/2026)
- Trang tuyển đại lý `/tuyen-dai-ly/`: đã làm ở 0.17.0 (`docs/giao-dien-0170.md`), **chưa đưa lên hosting**. Chính sách chi tiết chủ gõ vào nội dung trang.
- Bản dịch 4 khối trang chủ mới cho 6 ngôn ngữ.
- Tốc độ: ảnh tấm ốp đầu trang quá cỡ trên điện thoại; hạn bộ nhớ đệm js và tệp tĩnh.
- Dữ liệu cấu trúc và `llms.txt`: xem `docs/yeu-cau-theme-du-lieu-cau-truc-va-llms-txt.md` (kiểm lại trong mã xem phần nào đã làm).
- Cloudflare: đã thêm tên miền, chờ chủ đổi nameserver (`docs/cloudflare-sklight.md`). Sau khi bật proxy phải khôi phục IP thật của khách, nếu không khoá tạm đăng nhập sẽ khoá nhầm.
- Có thể làm thêm: xác thực 2 bước đăng nhập WordPress, giới hạn tần suất gửi form, CSP đầy đủ.
