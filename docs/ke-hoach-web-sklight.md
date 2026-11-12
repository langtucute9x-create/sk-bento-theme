# Kế hoạch làm mới web sklight.vn

Cập nhật: 10/11/2026 tối (các đợt sau xem `giao-dien-0150.md`, `giao-dien-0160.md`, `bao-mat-sklight.md`).
Bản trong kho công khai: đã lược địa chỉ máy chủ, tên database, tên tài khoản và mã dự án của dịch vụ ngoài.

- **Web mới đã chạy chính thức ở https://sklight.vn từ 19:56 ngày 10/11/2026**, lúc đó là theme SK Bento 0.13.11.
- Chi tiết lần chuyển và cách quay lại: `chuyen-web-chinh-thuc.md`.
- SK Core: tiện ích Kích hoạt bảo hành (d18bc02), Lịch hẹn lắp đặt (f8188c9).
- Catalog: commit 75c8e40.

## Quyết định đã chốt
- **Cách làm:** một bản **WordPress sạch**, tự viết theme nhẹ, bỏ WooCommerce và các plugin quảng cáo, vì nhân viên cập nhật nội dung hằng ngày.
- **Chuyển web:** dựng thử tại `/moitest`, rồi **chuyển ra tên miền gốc ngày 10/11/2026**. Chủ chốt hai điều:
  - không còn "/moitest/" ở đâu cả;
  - chuyển luôn, không đợi tổng duyệt.
- **Nền tảng:** web chính không làm bằng Lovable (Lovable chỉ dùng cho catalog và SK Core).
- **SEO:** làm trong theme, không cài plugin (chủ chọn 10/11/2026, thay vì Rank Math hay Yoast).
- **Giao diện:**
  - Nền tối, ưu tiên điện thoại, màu nhấn theo logo (tím #7D2566 → đỏ #DF1C4E → cam #F49B3F).
  - Trang chủ gồm:
    - đầu trang tràn màn hình với tấm ốp LED thật nổi, "Thử màu đèn", ô tìm xe;
    - 4 ưu điểm; bộ ảnh; "Xe thật, khách thật" (video);
    - dải kích hoạt; chọn dòng xe; đại lý (bản đồ 3D); đặt lịch.
  - Thanh Gọi / Đặt lịch / Kích hoạt cố định trên điện thoại.
- **Kích hoạt và đại lý:** trang chính phải có nút **Kích hoạt sản phẩm**. Phần đại lý hiển thị giống web cũ: tên, địa chỉ, SĐT, giờ, chỉ đường.
- **Ảnh đầu trang:** dùng **ảnh tấm ốp thật đã tách nền**, quản lý trong Thư viện WordPress để nhân viên tự đổi.
- **Bài hướng dẫn:** bài "Hướng dẫn sử dụng" chỉ nằm ở trang Hướng dẫn, không lặp lại ở Tin tức.
- **Kích hoạt bảo hành (chốt 09/11/2026):**
  - Mã giới thiệu 4 chữ dán trên hộp. Khách cuối nhập họ tên, SĐT, biển số và mã, rồi nhận mã kích hoạt app. Quên mã thì tra bằng SĐT.
  - Kho mã ở **SK Core**.
  - Người kích hoạt là người dùng cuối do gara lắp, **không** phải khách hàng SK Core.
  - Bảo hành **24 tháng từ ngày kích hoạt**. Nội dung web ghi 24 tháng ở mọi nơi (đã sửa 84 chỗ "18 tháng" trên 56 mẫu xe, 10/11/2026).
- **App SK Ambient Light** không gọi hay liên kết gì với web và SK Core (chủ xác nhận 10/11).
- **Phần đại lý (10/11/2026):** hình 3D có bản đồ Việt Nam (có Hoàng Sa, Trường Sa).
- **Gói / biến thể (10/11/2026):**
  - Trang mẫu xe có các gói như web cũ.
  - Hiển thị gọn: ô nhỏ chỉ có tên và giá; chi tiết của gói đang chọn hiện thành danh sách bên dưới (0.13.7).
- **10/11/2026 chủ duyệt "làm hết 6 mục":** tìm xe nhanh, đại lý gần tôi, lịch hẹn vào SK Core, ảnh/video khách thật, đo lượt bấm, bộ nhớ đệm LiteSpeed.
- **ChungAuto.vn Bắc Ninh và Minh Tân Auto là một đại lý**, số đúng **0913 259 779** (chủ chốt 10/11). Giữ thẻ "ChungAuto.vn Bắc Ninh"; thẻ "Minh Tân Auto" (ID 10) đã vào thùng rác → còn 17 đại lý.
- **Giá: catalog là đúng** (chủ chốt 10/11).
  - Đã sửa 13 mẫu trên web (chi tiết `so-gia-web-catalog.md`).
  - Gói chỉ web có thì giữ nguyên.
  - 12 mẫu chỉ catalog có sẽ thêm sau khi chuyển web.
- **Khoảng giá trên trang mẫu xe tính cả phụ kiện lẻ** (chủ chốt 10/11).
- **Ảnh mô tả: phương án A** (đưa vào Thư viện + bản WebP nhỏ), đã chạy xong 10/11.
- **Nối web với catalog:** phía web đã làm (0.13.4). Phía catalog (nút Đặt lịch / Đại lý gần tôi) làm sau ngày chuyển web.
- **Hãng Nissan (10/11/2026, chủ yêu cầu đổi cho đúng):**
  - Hãng xe "Nissan" (ID 67) là hãng gốc; "Navara" (ID 66) là dòng con của Nissan. Isuzu chỉ còn D-Max.
  - Mẫu 961 đổi tên "Led viền nội thất Nissan Navara", đường dẫn `/mau-xe/led-vien-noi-that-nissan-navara/` (đường dẫn cũ `…-isuzu-navara` và `/san-pham/…` tự chuyển về).
  - Mẫu 962 "Led viền nội thất Nissan" (chung các dòng Nissan) nằm trong hãng Nissan.
  - Hãng mới chưa đặt thứ tự thì đứng sau Suzuki, trước "Dòng xe khác" (hàm `skb_brands()`).

## SK Core (kho riêng `Business-Core-ERP`, Lovable, Vercel core.sklight.vn, Supabase)
- File `.env` trong kho SK Core trỏ một project Supabase của Lovable, **không phải** project thật. Mã project thật: hỏi chủ.
  - Chủ cho phép Claude tự chạy SQL release (10/11).
  - Lệnh xoá và lệnh sửa dữ liệu (UPDATE) cần chủ duyệt.
- **Khoá chung với máy chủ web:**
  - Phía Vercel: một biến môi trường.
  - Phía web: hằng `SKB_CORE_ACT_SECRET` và `SKB_CORE_URL` = https://core.sklight.vn, trong `wp-config.php` ở thư mục gốc.
  - Không ghi khoá vào tài liệu.
- **Kích hoạt bảo hành:**
  - Bảng `ext_act_batches`, `ext_act_codes`.
  - RPC: `ext_act_activate`, `ext_act_lookup`, `ext_act_import`, `ext_act_set_status`. `ext_act_import` cần quyền `activation.create`.
  - API `POST /api/public/kich-hoat`; trang Tiện ích › Kích hoạt bảo hành.
  - Số mã: 3.093 mã, 184 đã kích hoạt, **khớp web cũ**.
  - Còn lô rỗng "THỬ NGHIỆM (Claude, xoá sau)".
- **Lịch hẹn lắp đặt:**
  - Bảng `ext_bk_requests` (mã LH-xxxxx).
  - RPC `ext_bk_submit` (chỉ service_role), `ext_bk_update`.
  - API `POST /api/public/dat-lich`; trang Tiện ích › Lịch hẹn lắp đặt.

## Catalog phụ kiện (catalog.sklight.vn)
- **Dự án Lovable** "Car Accessory Catalog".
  - Code sửa trong Lovable; **chủ tự đẩy bản mới lên Vercel**.
  - Database của bản chạy thật khác database Lovable Cloud.
- **Đường dẫn:**
  - Cấu trúc `/c/{hãng}` → `/c/{hãng}/{dòng}`; không có `/c/kia`.
  - `detailUrl` trỏ `sklight.vn/san-pham/{slug}`; sau khi chuyển web, các link này chuyển 301 sang `/mau-xe/{slug}/` (đã kiểm 77/77).
- **Commit 75c8e40:** CONTACT = hotline 0868 055 555; link sang sklight.vn có `?nguon=catalog`.
- **Web → catalog (0.13.4, `inc/catalog.php`):**
  - Khối "Phụ kiện khác cho xe này" trên 69/69 trang mẫu xe.
  - Ô "Link catalog phụ kiện" (meta `_skb_catalog`).
  - Link catalog ở trang Hãng xe và chân trang.
  - Sự kiện `tu_catalog` / `mo_catalog`.
- **Catalog tự lệch** (chờ chủ sửa): Mazda 3 2020–2025 hai gói "21 chi tiết"; Điều khiển giọng nói AI 0,7 và 1,0 triệu.

## Web chính thức (sklight.vn, từ 10/11/2026)
- **Nền tảng:**
  - WordPress 7.1.4 ở `public_html`, bảng tiền tố `skn_` trong database dùng chung với web cũ, môi trường production, đã cho Google lập chỉ mục.
  - Máy chủ: PHP 8.5, giới hạn 30 giây/lần, bộ nhớ 256M, GD 2.3.3 (WebP, AVIF).
- **Thư mục:**
  - `public_html/wp-content`: web mới.
  - `public_html/wp-content-cu`: web cũ, giữ lại để lấy ảnh cũ, PHP bị chặn.
  - `public_html/moitest/`: chỉ còn lõi WordPress và luật 301 về gốc.
  - Sao lưu lúc chuyển: thư mục `chuyen-web-20261110` trên hosting, ngoài thư mục web.
- **Lịch chạy định kỳ** (thêm 10/11): cron của hosting chạy `wp-cron.php` mỗi 5 phút; wp-config đặt `DISABLE_WP_CRON = true`.
- **Google Search Console (10/11/2026):**
  - Tài khoản của chủ quản lý tài sản tiền tố URL `https://sklight.vn/`.
  - Xác minh bằng một tệp HTML ở gốc web. **Không xoá tệp này.**
  - Đã gửi sitemap `wp-sitemap.xml` (177 trang).
  - Tên miền còn một bản ghi TXT xác minh cũ của một tài khoản Search Console khác có từ trước. Giữ nguyên.
- **Theme SK Bento** (`wp-content/themes/sk-bento`):
  - **Loại nội dung:** Đại lý (dai_ly), Mẫu xe (mau_xe) + Hãng xe (hang_xe), Lịch hẹn lắp đặt (lich_hen).
    - **Sản phẩm = Mẫu xe** (menu Mẫu xe › Thêm mẫu xe). Gói ở khung "Gói sản phẩm"; ảnh ở khung "Bộ ảnh sản phẩm" (0.13.11, `inc/bo-anh.php`, meta `_skb_gallery`, chọn nhiều ảnh, kéo để sắp xếp).
    - Hãng xe là mục gốc (parent 0), dòng xe là mục con; thứ tự hãng theo term meta `skb_order`.
  - **Form đặt lịch** `[sk_dat_lich]`:
    - lưu vào WordPress, gửi email quản trị và gửi sang SK Core (`inc/dat-lich-core.php`);
    - gửi lỗi thì lịch hẹn ở trạng thái "Chờ gửi", cron gửi lại mỗi giờ.
  - **Đầu trang:** Giao diện > Tấm ốp đầu trang (`skb_hero_ops`, 48 ảnh).
    - `.hero2-inner` đặt `pointer-events:none`, chỉ a, button và `.tim-xe` nhận bấm.
    - 0.13.6 sửa lỗi không bấm được ô "Xe của bạn là gì?".
  - **Gói** (`inc/goi-san-pham.php`):
    - meta `_skb_goi`;
    - hàm `skb_goi_muc()` tách mô tả gói thành danh sách món (tách trước các cụm "số + LED/đèn/bộ…");
    - sửa gói không qua màn hình: POST `_wpMetaBoxUrl` với `skb_goi_json`.
  - **SEO** (0.13.8–0.13.10, `inc/seo.php` + `assets/seo-editor.js`):
    - **Trong trình soạn** (bài viết, trang, mẫu xe), khung "SEO · điểm/100" gồm:
      - từ khoá chính, tiêu đề SEO, mô tả, ẩn khỏi Google;
      - xem trước trên Google;
      - chấm điểm: từ khoá trong tiêu đề, mô tả, đường dẫn, đoạn đầu và H2/H3; mật độ từ khoá; độ dài tiêu đề và mô tả; số chữ; H2; ảnh đại diện; Alt ảnh; liên kết nội bộ.
    - **Meta lưu:** `_skb_seo_title`, `_skb_seo_desc`, `_skb_seo_kw`, `_skb_seo_noindex`.
    - **Ở trang khách xem:** `<title>`, meta description (tự sinh nếu để trống, kể cả trang chủ, hãng xe, đại lý, danh mục), robots noindex, Open Graph và Twitter card.
    - Trang tác giả noindex. Đã kiểm 177/177 trang trong sitemap có mô tả, không lỗi PHP.
    - **Đợt điền SEO hàng loạt (10/11/2026, chủ yêu cầu "seo tất cả"):**
      - 69 mẫu xe + 6 bài viết + 8 trang đã có từ khoá, tiêu đề SEO (≤ 60 ký tự) và mô tả (120–160 ký tự). Đã kiểm 83/83 trang hiển thị đúng.
      - Mẫu xe theo khuôn: từ khoá "led nội thất {hãng} {dòng}"; tiêu đề "LED nội thất {xe} – ốp zin, giá từ X triệu"; mô tả nêu ốp thay zin, không cắt dây, app + nút bấm, số gói và giá từ, đã gồm lắp đặt tại đại lý. Giá từ chỉ tính gói ≥ 10 chi tiết.
      - Khẩu hiệu web (Cài đặt › Tổng quan) = "Đèn LED nội thất ô tô ốp zin" → tiêu đề trang chủ "SK Ambient Light – Đèn LED nội thất ô tô ốp zin".
      - Giá trị SEO cũ đã lưu trong thư mục sao lưu (`seo-truoc-20261110.json`).
      - Đổi giá gói thì tiêu đề/mô tả SEO không tự đổi theo: sửa tay trong khung SEO.
  - **v0.13.5:** Công cụ › Chuyển web chính thức (`inc/chuyen-web.php`).
    - Hoàn tác được lần đổi `202611101306-goc`.
    - Nút "Xoá toàn bộ bộ nhớ đệm máy chủ" xoá luôn transient. Dùng nút này sau mỗi lần sửa theme.
  - **Khác:** tìm xe, đại lý gần tôi, khách thật, đo lượt bấm, bộ nhớ đệm LiteSpeed (`inc/bo-nho-dem.php`, khối SKB_LSCACHE trong `.htaccess` gốc), tối ưu ảnh, chuyển hướng 301 (`inc/chuyen-huong.php`).
- **Đưa file lên** qua trang quản trị hosting DirectAdmin (địa chỉ: hỏi chủ; chủ tự đăng nhập, phiên hết hạn sau khoảng 1–2 giờ):
  - Theme ở `public_html/wp-content/themes/sk-bento`.
  - Đọc: `GET /CMD_FILE_MANAGER?action=edit&path=…`.
  - Lưu: `POST /CMD_FILE_MANAGER` với `action=edit`, `path` (không có `/` đầu), `filename`, `text`.
  - Tải tệp lên: `POST /CMD_API_FILE_MANAGER` (multipart) với `action=upload`, `path`, `file1`.
  - Đổi tên / chuyển thư mục: `action=rename`, hoặc `action=multiple` (`add` rồi `move`).
  - Luôn so CRC trước và sau khi lưu; đổi file JS/CSS thì nâng `SKB_VER`.
- **Tên miền:** bản ghi DNS quản lý ở zonedns.vn (đang chuyển sang Cloudflare, xem `cloudflare-sklight.md`). Bản ghi TXT `_vercel` dùng cho catalog.sklight.vn và dich.sklight.vn.

## Chuyển dữ liệu từ web cũ (chỉ đọc các bảng web cũ)
- 69/69 sản phẩm → Mẫu xe (giữ slug và `_skb_old_url`).
- 300 gói; 6 bài viết + 2 trang chính sách; 1.305 ảnh nội dung.
- 17 đại lý; trang Giới thiệu và Liên hệ.

## Tài nguyên trên máy tính của chủ
- Logo: S:\SK\Logo\sk.
- Ảnh nội thất: S:\ảnh đẹp (một số nguồn Xiaohongshu, cần xác nhận quyền dùng).
- CAD tấm ốp: S:\SK\File thiết kế xe\file scan.
- Ảnh tấm ốp đã lọc: S:\SK\psxp\_loc-tam-op.

## Sao lưu
- **09/11/2026:** bản sao lưu database và public_html trên hosting; tệp mã kích hoạt có số điện thoại khách, giữ kín (vị trí: hỏi chủ).
- **10/11/2026:** thư mục `chuyen-web-20261110` trên hosting.
  - Database lúc 17:17 và 19:55, đều đủ 123 bảng (bản 19:55 còn nội dung "18 tháng" và hãng Nissan cũ trước khi sửa).
  - 6 file cấu hình; giá trị SEO cũ (`seo-truoc-20261110.json`).
- **Từ 12/11/2026:** mã theme có lịch sử trong kho GitHub này.

## Web cũ (đã thay ngày 10/11/2026)
- WordPress + WooCommerce, Flatsome + child theme của bên làm web cũ, kèm plugin riêng của họ.
- Nay nằm ở `wp-content-cu` + các bảng web cũ, không còn chạy.
- Còn 2 tài khoản quản trị cũ, một tài khoản cần xác nhận của ai.

## Việc tiếp theo
1. **Khoảng 24/11/2026:** xem Search Console › Trang; thêm chuyển hướng cho các đường dẫn 404.
2. Mã theo dõi (GA4, Pixel, Zalo chat); tài khoản quản trị cho nhân viên; toạ độ Hoa Mai Auto.
3. Catalog:
   - thêm nút Đặt lịch / Đại lý gần tôi;
   - đổi `detailUrl` sang `/mau-xe/{slug}/`;
   - sửa hai chỗ tự lệch.
4. Thêm 12 mẫu xe chỉ catalog có; bổ sung ảnh/video khách thật.
5. Sau ít nhất 3 tháng: dọn `moitest/`, `wp-content-cu` (gộp ảnh cũ trước) và các bảng web cũ. Giữ tệp xác minh Google.
6. Có thể làm thêm:
   - dữ liệu cấu trúc SEO (Product, LocalBusiness);
   - đưa ô SEO vào trang sửa Hãng xe.
