# Bàn giao web sklight.vn cho Javis (SEO)

Cập nhật: 11/11/2026. Theme SK Bento **0.14.0** (có đa ngôn ngữ).
Tài liệu này để Javis (hoặc người khác) tiếp quản việc SEO và cập nhật nội dung web sklight.vn mà không cần hỏi lại.

---

## 1. Tóm tắt nhanh

- **Web:** https://sklight.vn, chạy chính thức từ 19:56 ngày 10/11/2026. WordPress 7.1.4, theme tự viết **SK Bento**, không dùng WooCommerce, không cài plugin SEO.
- **SEO làm trong theme:** khung "SEO · điểm/100" trong trình soạn, thẻ `<title>`, meta description, Open Graph, robots. Sitemap là sitemap gốc của WordPress: `https://sklight.vn/wp-sitemap.xml`.
- **Google Search Console:** đã xác minh và đã gửi sitemap (177 trang). Tài khoản Google của chủ web quản lý.
- **Đã điền SEO cho toàn bộ nội dung:** 69 mẫu xe, 6 bài viết, 8 trang. Mỗi mục có từ khoá chính, tiêu đề SEO (≤ 60 ký tự) và mô tả (120–160 ký tự). Đã kiểm 83/83 trang hiển thị đúng.

---

## 2. Quyền truy cập Javis cần

Không có mật khẩu nào trong tài liệu này. Chủ web tự cấp quyền.

1. **Tài khoản WordPress riêng cho Javis**, vai trò **Biên tập viên (Editor)**, không dùng tài khoản quản trị.
   - Chủ tạo ở Thành viên › Thêm mới, rồi tạo **Mật khẩu ứng dụng** (Application Password) trong hồ sơ của tài khoản đó.
   - Javis gọi REST API bằng tên đăng nhập + mật khẩu ứng dụng (HTTP Basic). Thu hồi được bất cứ lúc nào.
   - Máy chủ đã sẵn sàng cho Mật khẩu ứng dụng (10/11/2026). Đã làm hai việc: thêm khối `SKB_AUTH` trên cùng `.htaccess` để chuyển tiếp header Authorization, và thêm bộ lọc `application_password_is_api_request` trong `functions.php`.
   - Thử ngay sau khi tạo mật khẩu: `GET /wp-json/wp/v2/users/me` kèm Basic auth phải trả về tài khoản Javis.
   - Trước khi trên web có mật khẩu ứng dụng đầu tiên, WordPress bỏ qua mọi header Basic auth (trả `rest_not_logged_in`). Đó là cách WordPress chạy, không phải lỗi.
   - Quyền Biên tập viên đủ để sửa bài, trang, mẫu xe và các trường SEO. Không sửa được theme, cài đặt hay người dùng. Như vậy là đúng ý: SEO không cần những quyền đó.
2. **Google Search Console:** chủ thêm Javis (hoặc tài khoản Google Javis dùng) làm người dùng **Toàn quyền** hoặc **Bị hạn chế** cho tài sản `https://sklight.vn/`.
3. **Không cấp cho Javis:**
   - trang quản trị hosting, database, file `wp-config.php`;
   - SK Core;
   - khoá kích hoạt bảo hành.

---

## 3. Cấu trúc nội dung

| Loại | REST | Đường dẫn trên web | Ghi chú |
|---|---|---|---|
| Mẫu xe (sản phẩm) | `/wp-json/wp/v2/mau_xe` | `/mau-xe/{slug}/` | 69 mẫu. Gói giá ở meta box "Gói sản phẩm", ảnh ở "Bộ ảnh sản phẩm" |
| Hãng xe / dòng xe | `/wp-json/wp/v2/hang_xe` | `/hang-xe/{slug}/` | Hãng là mục gốc (parent 0), dòng xe là mục con |
| Bài viết | `/wp-json/wp/v2/posts` | `/{slug}/` | Tin tức, kinh nghiệm |
| Trang | `/wp-json/wp/v2/pages` | `/{slug}/` | Giới thiệu, Liên hệ, Hướng dẫn, chính sách… |
| Đại lý | `dai_ly` | `/dai-ly/…` | 17 đại lý. **Không sửa** nếu chủ chưa yêu cầu |

- **Trang chủ:** hiển thị bài mới nhất (không phải trang tĩnh). Tiêu đề trang chủ = Tên web + Khẩu hiệu: "SK Ambient Light – Đèn LED nội thất ô tô ốp zin". Khẩu hiệu sửa ở Cài đặt › Tổng quan (cần quyền quản trị).
- **Trang Tin tức** là trang ID 18 (`page_for_posts`).
- **Hãng xe hiện có:** VinFast, Honda, Toyota, Mazda, Ford, Hyundai, Kia, Isuzu (chỉ còn D-Max), BMW, Lexus, MG, Mitsubishi, Suzuki, **Nissan** (Navara là dòng con; đã tách khỏi Isuzu ngày 10/11), Dòng xe khác.

---

## 4. Các trường SEO (meta) và cách ghi

Có cho **mẫu xe, bài viết, trang** (không có cho hãng xe):

| Meta | Kiểu | Ý nghĩa |
|---|---|---|
| `_skb_seo_kw` | chuỗi | Từ khoá chính (chỉ dùng để chấm điểm trong trình soạn) |
| `_skb_seo_title` | chuỗi | Thẻ `<title>`. Để trống = tiêu đề bài + " – SK Ambient Light" |
| `_skb_seo_desc` | chuỗi | Meta description + og:description. Để trống = tự sinh từ tóm tắt/nội dung (30 chữ đầu) |
| `_skb_seo_noindex` | true/false | Ẩn khỏi Google (robots noindex) |

**Đọc:**
```
GET https://sklight.vn/wp-json/wp/v2/mau_xe?context=edit&per_page=100&_fields=id,slug,link,title,meta
```

**Ghi** (chỉ gửi phần meta, không động vào nội dung):
```
POST https://sklight.vn/wp-json/wp/v2/mau_xe/{id}
Content-Type: application/json
{"meta":{"_skb_seo_kw":"led nội thất toyota fortuner","_skb_seo_title":"…","_skb_seo_desc":"…"}}
```
Thay `mau_xe` bằng `posts` hoặc `pages` cho bài viết, trang.

- **Bộ nhớ đệm:** lưu qua REST thì LiteSpeed tự xoá bộ nhớ đệm của trang đó, khách thấy ngay.
- **Kiểm sau khi ghi:** mở trang (không đăng nhập), đọc `<title>` và `<meta name="description">`.
- **Ghi an toàn:** các hàm lưu của theme cho gói giá, bộ ảnh và link catalog đều kiểm nonce của màn hình sửa. Ghi meta SEO qua REST **không làm mất** gói giá hay bộ ảnh.

---

## 5. Quy tắc SEO đã áp dụng (giữ nguyên khi viết thêm)

### Mẫu xe
- **Từ khoá:** `led nội thất {hãng} {dòng}`, viết thường, có dấu. Đời xe cũ thêm năm bắt đầu nếu cần phân biệt (ví dụ `led nội thất honda cr-v 2017`).
- **Tiêu đề (≤ 60 ký tự):** `LED nội thất {Tên xe} – ốp zin, giá từ X triệu`. Quá dài thì bỏ "ốp zin,": `LED nội thất {Tên xe} – giá từ X triệu`.
- **Mô tả (120–160 ký tự):** `LED nội thất {Tên xe} ốp thay zin, không cắt dây, đổi màu qua app và nút bấm. {n} gói từ X triệu, đã gồm lắp đặt tại đại lý SK toàn quốc.`. Nếu chỉ có 1 gói thì viết "Trọn gói X triệu".
- **"Giá từ"** lấy gói rẻ nhất **có từ 10 chi tiết trở lên**, không lấy phụ kiện lẻ. Ghi kiểu Việt: `9,2 triệu`, `10,7 triệu`.
- **Không hứa** điều không có trong gói. Ví dụ điều khiển giọng nói là phụ kiện mua thêm, không đưa vào mô tả chung.

### Bài viết và trang
- Từ khoá chính là cụm khách hay tìm (ví dụ `cách lắp led nội thất ô tô`, `bảo hành led nội thất`).
- Tiêu đề 30–60 ký tự, có từ khoá, thường kết thúc bằng "– SK" hoặc "| SK Ambient Light" nếu còn chỗ.
- Mô tả 120–160 ký tự, có từ khoá, có lời mời hành động (xem, đặt lịch, kích hoạt…).

### Thông tin phải đúng
- **Bảo hành 24 tháng** kể từ ngày kích hoạt. Đã sửa hết 84 chỗ "18 tháng" trên 56 mẫu xe ngày 10/11. Thấy "18 tháng" ở đâu thì báo chủ hoặc sửa thành 24.
- **Hotline:** 0868 055 555.
- **Giá:** catalog (catalog.sklight.vn) là nguồn đúng. Giá trên web lệch catalog thì báo chủ, **không tự sửa giá**.
- **Navara là xe Nissan**, không phải Isuzu.

### Kiểm tra trong trình soạn
Khung "SEO · điểm/100" ở cột phải trình soạn chấm:
- từ khoá có trong tiêu đề, mô tả, đường dẫn, đoạn đầu và H2/H3;
- mật độ từ khoá, độ dài tiêu đề và mô tả;
- số chữ, có H2, ảnh đại diện, Alt ảnh, liên kết nội bộ.

Bài mới nên đạt từ 80 điểm.

---

## 6. Việc Javis được làm và không được làm

**Được làm:**
- Đọc toàn bộ nội dung, Search Console, sitemap.
- Sửa các trường SEO (mục 4) của mẫu xe, bài viết, trang.
- Viết bài mới dạng **Bản nháp** (status `draft`) để chủ duyệt rồi mới đăng.
- Sửa lỗi chính tả, thêm Alt ảnh, thêm liên kết nội bộ giữa bài viết và trang mẫu xe.
- Báo cáo định kỳ cho chủ.

**Phải hỏi chủ trước:**
- Đăng bài (chuyển sang `publish`), xoá bất cứ thứ gì, đổi đường dẫn (slug).
- Sửa giá, gói, thông tin đại lý, trang Kích hoạt bảo hành.
- Đổi tên hoặc chuyển hãng/dòng xe.

**Không làm:**
- Cài plugin SEO (Yoast, Rank Math…). Chủ đã chọn làm SEO trong theme, cài thêm sẽ ra hai thẻ title/description trùng nhau.
- Sửa theme, `.htaccess`, `wp-config.php`, database.
- Xoá tệp xác minh Search Console ở gốc web.
- Đụng tới dữ liệu web cũ (thư mục `wp-content-cu`, các bảng của web cũ).

**Đổi đường dẫn:** nếu buộc phải đổi slug, WordPress tự chuyển hướng 301 từ đường dẫn cũ. Sau khi đổi vẫn phải kiểm đường dẫn cũ còn chuyển hướng không.

---

## 7. Lịch làm SEO gợi ý cho Javis

**Hằng tuần**
1. **Search Console › Hiệu suất:**
   - Ghi 20 từ khoá có nhiều lượt hiển thị nhất.
   - Tìm trang có CTR thấp (< 2%) mà vị trí trong top 10, rồi viết lại tiêu đề và mô tả cho trang đó.
2. **Search Console › Trang:** tìm trang lỗi 404 hoặc "Đã thu thập dữ liệu – chưa lập chỉ mục" và báo chủ. Lỗi 404 của đường dẫn web cũ cần thêm chuyển hướng: theme có bộ chuyển hướng trong `inc/chuyen-huong.php`, phần này do người có quyền sửa theme làm.
3. **Mẫu xe mới:** điền SEO theo mục 5.

**Hằng tháng**
1. **Viết bài:** 2–4 bài, nhắm từ khoá dạng hỏi. Ví dụ:
   - "LED nội thất có hại ắc quy không"
   - "lắp LED nội thất có mất bảo hành xe không"
   - "LED nội thất Mazda CX-5 loại nào tốt"

   Mỗi bài dẫn link về 1–3 trang mẫu xe liên quan và trang Đặt lịch.
2. **Kiểm khớp giá:** so giá web với catalog, nếu lệch thì báo chủ.
3. **Báo cáo:** lượt nhấp, lượt hiển thị, vị trí trung bình, top trang, việc đã làm, việc cần chủ duyệt.

**Mốc gần**
- **Khoảng 24/11/2026:** lần xem Search Console đầu tiên sau khi chuyển web, chú ý lỗi 404 từ đường dẫn web cũ.

---

## 8. Những gì đã làm cho web (10/11/2026)

### Chuyển web chính thức
- **Chuyển từ `/moitest` ra tên miền gốc:** đổi 140 ô dữ liệu, không còn "/moitest/" ở đâu. Đường dẫn `/moitest/...` cũ chuyển 301 về gốc.
- **Đường dẫn web cũ:**
  - 77 đường dẫn sản phẩm cũ (`/san-pham/...`) chuyển 301 sang `/mau-xe/...`;
  - ảnh web cũ vẫn hiện, lấy từ `wp-content-cu`.
- **Máy chủ:**
  - bộ nhớ đệm LiteSpeed hoạt động;
  - lịch chạy `wp-cron` mỗi 5 phút;
  - đã gửi sitemap lên Google Search Console.
- **Kích hoạt bảo hành:** chạy qua SK Core, 3.093 mã, khớp web cũ.

### Giao diện
- **Ô tìm xe:** sửa lỗi không bấm được ô "Xe của bạn là gì?" ở đầu trang chủ.
- **Trang mẫu xe nhiều gói:** gọn hơn, mỗi ô gói chỉ có tên, giá và % giảm; chi tiết của gói đang chọn hiện thành danh sách bên dưới.
- **4 ô ưu điểm trang chủ:** làm lại thành thẻ có dải sáng LED ở mép trên, màu theo logo.
- **Nút chính đầu trang chủ:** "Xem catalog sản phẩm ↗" thay cho "Đặt lịch lắp đặt". Đặt lịch vẫn có ở thanh dưới trên điện thoại và trong trang mẫu xe.
- **Trang mẫu xe:** có hộp nổi bật **"Trải nghiệm toàn diện chi tiết sản phẩm · 3D"** ngay dưới tên xe, mở trang xe đó trên catalog. Đã bỏ khối "Phụ kiện khác…" ở cuối trang.
- **Khung "Bộ ảnh sản phẩm"** trong trang sửa mẫu xe: chọn nhiều ảnh, kéo để đổi thứ tự. Đã thử lưu thật.

### SEO
- **Khung SEO trong trình soạn:**
  - chấm điểm theo các mục ở cuối mục 5;
  - xem trước kết quả Google.
- **Trên trang khách xem:** title, meta description (tự sinh nếu trống), Open Graph, Twitter card, robots. Trang tác giả để noindex.
- **Điền SEO:** cho 69 mẫu xe, 6 bài viết, 8 trang. Giá trị cũ được lưu trong thư mục sao lưu trên hosting (`seo-truoc-20261110.json`).
- **Khẩu hiệu web:** "Đèn LED nội thất ô tô ốp zin".

### Nội dung
- **Bảo hành:** "18 tháng" → "24 tháng" (84 chỗ, 56 mẫu xe).
- **Hãng Nissan:** tách khỏi Isuzu. Mẫu "Led viền nội thất Isuzu Navara" đổi thành "Led viền nội thất **Nissan** Navara", đường dẫn mới `/mau-xe/led-vien-noi-that-nissan-navara/`; đường dẫn cũ tự chuyển.
- **Sample Page:** đã xoá (vào Thùng rác).

---

## 9. Việc còn mở (không thuộc SEO, để chủ quyết)

1. **Mã theo dõi:** GA4, Facebook Pixel, Zalo chat. Chủ gửi mã thì người có quyền sửa theme gắn vào.
2. **Toạ độ bản đồ** đại lý Hoa Mai Auto.
3. **Catalog:**
   - chưa có trang hãng Nissan, nên hai mẫu Nissan đang mở trang "Dùng chung mọi xe";
   - còn hai chỗ lệch: Mazda 3 có hai gói cùng tên "21 chi tiết"; Điều khiển giọng nói AI có hai mức giá 0,7 và 1,0 triệu;
   - thêm nút Đặt lịch / Đại lý gần tôi.
4. **12 mẫu xe** chỉ catalog có, chưa có trên web.
5. **Sau 3 tháng (khoảng tháng 2/2027):** dọn web cũ (`wp-content-cu`, các bảng web cũ, thư mục `moitest`). Giữ tệp xác minh Google.
6. **Có thể làm thêm:**
   - dữ liệu cấu trúc (Product cho mẫu xe, LocalBusiness cho đại lý), giúp Google hiện giá và địa chỉ;
   - khung SEO cho trang Hãng xe.

   Hai việc này cần sửa theme.

---

## 10. Sao lưu và quay lại

- **Database:** có bản sao lưu trên hosting lúc 17:17 và 19:55 ngày 10/11/2026, kèm 6 file cấu hình và giá trị SEO cũ.
- **Sửa nhầm nội dung bài viết hoặc trang:** mở trong trình soạn › Bản sửa đổi, chọn bản trước để khôi phục.
- **Mẫu xe không lưu bản sửa đổi.** Vì vậy trước khi sửa nội dung (không phải meta SEO) của mẫu xe, Javis lưu lại bản cũ (`content.raw`, `excerpt.raw`) vào file của mình để còn quay lại được.
- **Chi tiết kỹ thuật** nằm trong `ke-hoach-web-sklight.md` và `chuyen-web-chinh-thuc.md`. Javis không cần đọc nếu chỉ làm SEO.

---

## 11. Đa ngôn ngữ (0.14.0, 11/11/2026)

### Cách hoạt động
- **Ngôn ngữ và đường dẫn:** tiếng Việt là bản gốc, không có tiền tố. Bản nước ngoài:
  - `/en/` tiếng Anh, `/zh/` tiếng Trung giản thể, `/ko/` tiếng Hàn;
  - `/th/` tiếng Thái, `/de/` tiếng Đức, `/hi/` tiếng Hindi.
- **Viết trong theme, không dùng plugin:** `inc/da-ngu.php` (lõi) và `inc/da-ngu-tu-dien.php` (từ điển).
- **Trang có bản dịch:** trang chủ, Dòng xe, Hãng xe, từng mẫu xe, Giới thiệu, Liên hệ, trang 404.
  - Đặt lịch, Đại lý và Kích hoạt chuyển về trang Liên hệ của ngôn ngữ đó.
  - Trang khác (tin tức, chính sách…) chuyển về bản tiếng Việt.
- **Ẩn ở bản nước ngoài** (chủ chốt 11/11): Đặt lịch, Đại lý lắp đặt toàn quốc, Kích hoạt bảo hành. Thay bằng nút **WhatsApp +84 868 055 555**, câu chào điền sẵn theo ngôn ngữ, có tên xe và gói.
- **Nội dung mẫu xe bản nước ngoài là "bản gọn"** (chủ chốt 11/11):
  - tiêu đề "Ambient Lighting for {tên xe}";
  - gói và giá (VND, ghi "giá bán tại Việt Nam");
  - 2 đoạn tóm tắt và bảng thông số theo mẫu câu của từng ngôn ngữ;
  - ảnh, video khách, hộp 3D catalog.
  - Mô tả dài tiếng Việt không hiện ở bản nước ngoài.
- **Tên xe dùng ghép câu:** meta `_skb_ten_xe` (ví dụ `Hyundai Santa Fe 2024–2026`). Đã điền cho 69 mẫu, sửa được qua REST như các trường SEO. **Thêm mẫu xe mới thì điền luôn trường này.**
- **Tên gói tự dịch:** "18 Chi tiết" → "18-piece kit"… Tên gói lạ chưa có trong từ điển thì giữ nguyên tiếng Việt.

### SEO bản nước ngoài
- **Title và meta description:** tự sinh theo mẫu câu của từng ngôn ngữ, không dùng `_skb_seo_title` / `_skb_seo_desc` (các trường đó chỉ cho bản tiếng Việt).
- **hreflang:** mọi trang có bản dịch đều có thẻ `hreflang` cho 7 ngôn ngữ, cộng `x-default` = tiếng Việt.
- **Sitemap riêng:** `https://sklight.vn/wp-sitemap-ngonngu-1.xml`, gồm 870 đường dẫn, đã nằm trong `wp-sitemap.xml`.
- **Kiểm tra 11/11/2026:**
  - 870/870 trang nước ngoài trả 200, không còn chữ tiếng Việt, không có nút Đặt lịch / Đại lý.
  - 178/178 trang tiếng Việt vẫn trả 200, không lỗi; nội dung tiếng Việt giữ nguyên (đã so trước/sau trên bản thử).
- **Việc cho Javis:**
  - Search Console › Trang: theo dõi các đường dẫn `/en/…`, `/zh/…` được lập chỉ mục.
  - Muốn sửa câu chữ một ngôn ngữ thì báo người sửa theme: chữ nằm trong `inc/da-ngu-tu-dien.php` (mỗi dòng: chữ tiếng Việt → 6 bản dịch).

### Sao lưu
- Theme 0.13.14 (trước khi làm đa ngôn ngữ) được lưu trong thư mục sao lưu trên hosting (17 file).
