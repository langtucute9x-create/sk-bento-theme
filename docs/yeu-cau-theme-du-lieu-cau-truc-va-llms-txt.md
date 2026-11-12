---
type: request
status: ready-to-send
agent: seo-web-sklight
created: 2026-11-10
updated: 2026-11-11
for: người có quyền sửa theme SK Bento và thư mục gốc sklight.vn
---

# Yêu cầu kỹ thuật SEO cho sklight.vn (gửi người có quyền theme)

Mục đích: giúp Google hiểu trang rõ hơn và giúp ChatGPT, Gemini, Perplexity trích dẫn đúng SK Ambient Light. Javis không được sửa theme, nên nhờ bạn làm giúp.

**Kiểm ngày 11/11/2026 trên site thật (trình duyệt tích hợp):**
- `https://sklight.vn/llms.txt` → 404. Cần làm.
- Trang chủ và trang mẫu xe (Toyota Camry 2018–2024) → **0 thẻ** `application/ld+json`. Cần làm.
- Ảnh chính và ảnh trong bộ ảnh trang mẫu xe → **đã có alt** ("LED nội thất Toyota Camry 2018–2024 ốp zin, ảnh N"). Chỉ còn 3 ảnh video (xem mục 2).
- `robots.txt` có sẵn và trỏ đúng tới `wp-sitemap.xml` (trả 200). Không cần sửa.
- Trang chủ chưa hiện liên kết mạng xã hội. Chủ đã cung cấp 3 địa chỉ chính thức (11/11), đã điền vào mục 1.1. Có thể thêm các biểu tượng này vào chân trang nếu muốn, không bắt buộc.

> Ghi chú 12/11/2026: tình trạng trên là của ngày 11/11. Từ đó theme đã có thêm khối hệ sinh thái ở chân trang (0.15.0) và `FAQPage` ở trang chủ tiếng Việt (0.16.0). Kiểm lại trong mã trước khi làm từng mục.

## Thứ tự ưu tiên
1. `Product` cho mẫu xe (Google có thể hiện giá).
2. `Organization` (trang chủ) + `BreadcrumbList` (mọi trang trong).
3. `LocalBusiness` cho đại lý.
4. `llms.txt` (chủ đã duyệt nội dung ngày 11/11).
5. Alt ảnh video; `FAQPage` (chỉ khi có mục hỏi đáp).

---

## 1. Dữ liệu cấu trúc (JSON-LD) trong theme

In bằng `<script type="application/ld+json">` trong `<head>` (hoặc cuối `<body>`), dùng `wp_json_encode()` với cờ `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES`. Mỗi trang một khối cho mỗi loại, không in trùng.

### 1.1 Trang chủ (và trang Giới thiệu nếu có): `Organization`
Chỗ có dấu `{...}` lấy từ dữ liệu có sẵn.

```json
{
  "@context": "https://schema.org",
  "@type": "Organization",
  "@id": "https://sklight.vn/#organization",
  "name": "SK Ambient Light",
  "url": "https://sklight.vn/",
  "logo": "{URL logo trong Thư viện, ví dụ logo-full-png-1.webp}",
  "description": "LED nội thất ô tô ốp thay zin theo từng dòng xe, không cắt dây, đổi màu qua app. Bảo hành 24 tháng.",
  "telephone": "+84868055555",
  "contactPoint": {
    "@type": "ContactPoint",
    "telephone": "+84868055555",
    "contactType": "customer service",
    "areaServed": "VN",
    "availableLanguage": "Vietnamese"
  },
  "sameAs": [
    "https://www.facebook.com/SK.Ambient.Light.Luxury",
    "https://www.tiktok.com/@sk.ambient.light.luxury",
    "https://www.youtube.com/@SKAmbientLightLuxury"
  ]
}
```

### 1.2 Trang mẫu xe (`mau_xe`): `Product`
```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "@id": "{permalink}#product",
  "name": "{tiêu đề mẫu xe, ví dụ Toyota Camry 2018-2024}",
  "description": "{meta description (_skb_seo_desc); trống thì dùng mô tả tự sinh}",
  "image": ["{URL ảnh #mx-main}", "{URL các ảnh trong bộ ảnh}"],
  "url": "{permalink}",
  "brand": { "@type": "Brand", "name": "SK Ambient Light" },
  "offers": {
    "@type": "AggregateOffer",
    "priceCurrency": "VND",
    "lowPrice": "{giá thấp nhất}",
    "highPrice": "{giá cao nhất}",
    "offerCount": "{số gói}",
    "availability": "https://schema.org/InStock",
    "url": "{permalink}"
  }
}
```
Quy tắc cho `offers` (quan trọng, để Google không coi là lệch với nội dung hiển thị):
- Lấy giá từ meta `_skb_goi` (cùng nguồn với bảng gói trên trang), **không điền giá cứng trong code**.
- `lowPrice` theo **cùng quy tắc với "giá từ" ở tiêu đề**: gói rẻ nhất có từ 10 chi tiết trở lên, không lấy phụ kiện lẻ. `highPrice` là gói đắt nhất. `offerCount` là số gói tính trong khoảng đó.
- Giá là số thuần, không dấu chấm, ví dụ `9600000`.
- Chỉ in `offers` khi trang đang hiển thị giá. Mẫu không có gói/giá thì in `Product` không có `offers`.
- Ví dụ trang Camry 2018–2024 hiện: 3 gói 9.600.000đ / 9.800.000đ / 21.500.000đ → `lowPrice 9600000`, `highPrice 21500000`, `offerCount 3`. Đối chiếu lại lúc kiểm.
- Không thêm `aggregateRating` hay `review` (web chưa có đánh giá thật; thêm khống bị Google phạt).

### 1.3 Trang đại lý (`dai_ly`): `LocalBusiness`
Áp dụng cho trang chi tiết từng đại lý, nếu có; trang danh sách `/dai-ly/` không cần.
```json
{
  "@context": "https://schema.org",
  "@type": "AutomotiveBusiness",
  "name": "{tên đại lý}",
  "url": "{permalink}",
  "telephone": "{SĐT đại lý, dạng +84…}",
  "address": {
    "@type": "PostalAddress",
    "streetAddress": "{địa chỉ}",
    "addressRegion": "{tỉnh/thành}",
    "addressCountry": "VN"
  },
  "geo": { "@type": "GeoCoordinates", "latitude": "{vĩ độ}", "longitude": "{kinh độ}" },
  "openingHours": "{giờ mở cửa nếu có, ví dụ Mo-Su 08:00-18:00}",
  "parentOrganization": { "@id": "https://sklight.vn/#organization" }
}
```
Chỉ in khoá nào đại lý có dữ liệu thật; thiếu thì bỏ khoá. Riêng `geo`: Hoa Mai Auto chưa có toạ độ (việc còn mở), bỏ qua đến khi có.

### 1.4 Mọi trang trong: `BreadcrumbList`
```json
{
  "@context": "https://schema.org",
  "@type": "BreadcrumbList",
  "itemListElement": [
    { "@type": "ListItem", "position": 1, "name": "Trang chủ", "item": "https://sklight.vn/" },
    { "@type": "ListItem", "position": 2, "name": "Mẫu xe", "item": "https://sklight.vn/mau-xe/" },
    { "@type": "ListItem", "position": 3, "name": "Toyota Camry 2018-2024" }
  ]
}
```
Đường đi theo loại trang:
- Mẫu xe: Trang chủ › Mẫu xe › {tên xe}
- Hãng xe: Trang chủ › Mẫu xe › {hãng}
- Bài viết: Trang chủ › Tin tức › {tiêu đề}
- Trang thường: Trang chủ › {tiêu đề}
Mục cuối không cần `item`. Hiện trang chưa có breadcrumb hiển thị; chỉ thêm dữ liệu ẩn là đủ, không bắt buộc thêm giao diện.

### 1.5 `FAQPage` (ưu tiên thấp)
Chỉ thêm khi có bài/trang có phần hỏi đáp thật, và nội dung trong JSON-LD phải giống hệt chữ đang hiển thị. Google đã hạn chế hiện kết quả FAQ cho hầu hết site, lợi ích chính là cho AI đọc.

### Kiểm sau khi thêm
- Rich Results Test của Google với 3 địa chỉ: trang chủ, một trang mẫu xe, một trang đại lý.
- Kiểm bằng Schema Markup Validator (validator.schema.org) cho `Organization` và `BreadcrumbList`.
- Gửi Javis ảnh chụp kết quả hoặc báo lại "không lỗi, không cảnh báo".

## 2. Alt ảnh
- **Đã xong:** ảnh chính `#mx-main` và ảnh trong bộ ảnh trang mẫu xe đã có alt theo mẫu "LED nội thất {tên xe} ốp zin, ảnh N". Giữ nguyên.
- **Còn lại:** 3 ảnh video "Xe thật, khách thật" (thumbnail YouTube `hqdefault.jpg`) đang `alt=""`. Nhờ theme đặt alt theo tiêu đề video, ví dụ "Video khách lắp LED nội thất SK Ambient Light". Nếu ảnh chỉ trang trí và nút đã có chữ kề bên thì để `alt=""` cũng được.
- Ảnh avatar (`avatar-28`, `avatar-64`) chỉ thấy khi đăng nhập quản trị, bỏ qua.
- Việc nhỏ cho nhân viên nội dung (không phải theme): điền alt thật trong Thư viện › Media khi có thể, để ưu tiên hơn alt tự sinh.

## 3. Tệp `llms.txt` ở gốc web
Ngày 11/11 `https://sklight.vn/llms.txt` trả 404 (trang 404 của theme).

**Cách đặt:** tạo tệp văn bản thật `public_html/llms.txt`, mã hoá UTF-8 không BOM. Máy chủ thường trả `text/plain` cho tệp `.txt`; nếu thấy bị tải về hoặc hỏng dấu thì đặt header `Content-Type: text/plain; charset=utf-8` (lưu ý máy chủ này không áp dụng dòng `Header` trong `.htaccess`). Nhớ xoá bộ nhớ đệm máy chủ (Công cụ › Xoá toàn bộ bộ nhớ đệm máy chủ) sau khi thêm. Kiểm bằng cách mở `https://sklight.vn/llms.txt` thấy tiếng Việt đúng dấu.

**Nội dung (chủ đã duyệt 11/11/2026, gồm câu "hơn 60 mẫu xe" và danh sách 14 hãng):**

```
# SK Ambient Light

> SK Ambient Light là LED nội thất ô tô ốp thay zin theo từng dòng xe: không cắt chích dây, đổi màu qua app và nút bấm, lắp đặt tại hệ thống đại lý SK toàn quốc. Bảo hành 24 tháng tính từ ngày kích hoạt. Hotline và Zalo: 0868 055 555.

Website này có hơn 60 mẫu xe của các hãng VinFast, Honda, Toyota, Mazda, Ford, Hyundai, Kia, Isuzu, BMW, Lexus, MG, Mitsubishi, Suzuki, Nissan. Mỗi mẫu xe có một trang riêng với các gói và giá. Giá hiển thị trên từng trang mẫu xe là giá đã gồm lắp đặt tại đại lý.

## Sản phẩm và đại lý
- [Mẫu xe](https://sklight.vn/mau-xe/): danh sách mẫu xe được hỗ trợ, theo hãng và dòng xe
- [Đại lý](https://sklight.vn/dai-ly/): địa chỉ, số điện thoại, giờ mở cửa các đại lý lắp đặt, lọc theo tỉnh/thành
- [Catalog phụ kiện](https://catalog.sklight.vn/): phụ kiện và trải nghiệm 3D chi tiết sản phẩm

## Dịch vụ cho khách
- [Đặt lịch lắp đặt](https://sklight.vn/dat-lich/): hẹn lịch lắp tại đại lý
- [Kích hoạt bảo hành](https://sklight.vn/kich-hoat-bao-hanh/): nhập mã trên hộp để kích hoạt bảo hành 24 tháng và nhận mã kích hoạt app
- [Hướng dẫn sử dụng](https://sklight.vn/huong-dan-su-dung/): cách dùng app và nút bấm đổi màu đèn

## Thông tin khác
- [Tin tức](https://sklight.vn/tin-tuc/): bài viết, kinh nghiệm
- [Liên hệ](https://sklight.vn/lien-he/)
- [Chính sách bảo mật](https://sklight.vn/chinh-sach-bao-mat/)
- [Điều khoản sử dụng](https://sklight.vn/dieu-khoan-su-dung/)

## Optional
- [Sơ đồ trang web](https://sklight.vn/wp-sitemap.xml)
```
Ghi chú:
- Mọi đường dẫn trên đã thấy trong menu/chân trang của web ngày 11/11. Nếu đổi đường dẫn thì sửa tệp theo.
- Không nên ghi số cụ thể về giá hay khuyến mãi trong tệp này vì dễ cũ. Khi thêm hoặc bớt hãng xe thì sửa câu danh sách hãng.
- `llms.txt` là chuẩn đề xuất, **chưa có công cụ tìm kiếm lớn nào cam kết dùng**. Làm vì rẻ và vô hại, không kỳ vọng tăng thứ hạng Google.

## 4. Tuỳ chọn: Search Console
Sau khi xong các mục trên, Javis gửi lại sitemap `https://sklight.vn/wp-sitemap.xml` và xin lập chỉ mục các trang chính (cần chủ đồng ý). Tệp xác minh Google ở gốc web **không được xoá**.

## 5. Báo lại cho Javis khi xong
- Phiên bản theme mới và tệp đã sửa (nên là `inc/seo.php` hoặc tệp mới `inc/schema.php`).
- Kết quả Rich Results Test của 3 trang mẫu.
- Xác nhận `llms.txt` mở được, đúng dấu tiếng Việt.
