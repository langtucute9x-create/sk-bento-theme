# Kết nối sklight.vn vào Cloudflare

Cập nhật: 12/11/2026 tối. **Trạng thái: đã thêm tên miền vào Cloudflare, CHƯA đổi nameserver** (chờ chủ đổi ở nhà đăng ký).
Bản trong kho công khai: đã lược địa chỉ IP máy chủ và giá trị các bản ghi.

## Đã làm (12/11/2026)
- Thêm `sklight.vn` vào tài khoản Cloudflare của chủ (gói Free, DNS Setup: Full).
- Tắt "Bot Preference Sync" để Cloudflare không chèn thêm vào `robots.txt`. Chính sách bot AI (Search / Training / Agents) để mặc định Allow.
- Nhập đủ 11 bản ghi, khớp với danh sách ở nơi quản lý DNS hiện tại. **Tất cả đang ở chế độ chỉ DNS (đám mây xám).**

| Loại | Tên | Trỏ tới |
|---|---|---|
| A | `sklight.vn` | máy chủ hosting |
| A | `ftp` | máy chủ hosting |
| CNAME | `www` | sklight.vn |
| CNAME | `mail` | sklight.vn |
| CNAME | `core` | Vercel (SK Core) |
| CNAME | `catalog` | Vercel (catalog) |
| CNAME | `dich` | Vercel (app dịch) |
| MX | `sklight.vn` | 10 sklight.vn |
| TXT | `sklight.vn` | mã xác minh Search Console cũ (giữ) |
| TXT | `_vercel` | xác minh catalog.sklight.vn |
| TXT | `_vercel` | xác minh dich.sklight.vn |

## Nameserver
- Hiện tại: `ns1`–`ns4.zonedns.vn`.
- Cần đổi sang cặp nameserver Cloudflare đã cấp (xem trong trang Cloudflare của chủ).
- zonedns.vn chỉ quản lý bản ghi, **không đổi được nameserver**. Phải đổi ở nơi đăng ký tên miền (có thể tài khoản do công ty làm web cũ giữ).
- DNSSEC đang tắt. Giữ tắt khi đổi.

## Việc còn lại sau khi nameserver đã đổi
1. Chờ Cloudflare báo tên miền Active và chứng chỉ Universal SSL đã cấp.
2. Đặt SSL/TLS = Full (strict) nếu chứng chỉ trên hosting còn hạn, không thì Full.
3. Đổi `mail` thành bản ghi A trỏ máy chủ hosting (chỉ DNS) và MX trỏ sang `mail.sklight.vn`. Làm **trước** khi bật proxy, nếu không thư @sklight.vn sẽ mất.
4. Bật proxy (đám mây cam) cho `sklight.vn` và `www`. Giữ chỉ DNS cho `ftp`, `mail`, `core`, `catalog`, `dich`.
5. Kiểm tra: trang chủ, trang mẫu xe, đặt lịch, kích hoạt bảo hành, wp-admin, bộ nhớ đệm LiteSpeed, catalog, core, dich.
6. Lưu ý sau khi bật proxy: máy chủ sẽ thấy IP của Cloudflare thay vì IP khách. Cần khôi phục IP thật, vì `inc/bao-mat.php` khoá tạm đăng nhập theo IP.

## Cách quay lại
Đổi nameserver về `ns1`–`ns4.zonedns.vn`. Bản ghi ở zonedns.vn vẫn giữ nguyên, không ai sửa.
