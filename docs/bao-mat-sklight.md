# Bảo mật sklight.vn

Cập nhật: 12/11/2026 tối, theme SK Bento **0.16.2** (mốc GitHub `e8be99e`).
Bản trong kho công khai: đã lược phần về tài khoản và trang quản trị hosting.

## Phát hiện quan trọng
**Máy chủ không áp dụng các dòng `Header`, `FilesMatch`, `Require`, `mod_expires` trong `.htaccess`; chỉ luật rewrite có tác dụng.** Vì vậy các khối "Security headers", "Chặn xmlrpc", cache tĩnh trong `.htaccess` gốc (do bên làm web cũ để lại) không chạy: trước 12/11 web không có header bảo mật nào và XML-RPC vẫn mở. Muốn chặn gì ở `.htaccess` thì viết bằng `RewriteRule … - [F]`; header thì gửi bằng PHP.

## Đã làm 12/11/2026
### Trong theme (`inc/bao-mat.php`, nạp đầu `functions.php`)
- **Tắt XML-RPC** (trả 403). Javis dùng REST + mật khẩu ứng dụng nên không ảnh hưởng.
- **Header bảo mật** cho mọi trang (có cả khi lấy từ bộ nhớ đệm LiteSpeed): HSTS 180 ngày (chỉ tên miền chính, không gồm tên miền con), `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy` (giữ định vị cho "Đại lý gần tôi"), `Cross-Origin-Opener-Policy`, và CSP mức an toàn (`frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'; upgrade-insecure-requests`). Trang đăng nhập và quản trị không gửi CSP.
- **Không lộ tên đăng nhập:** `/wp-json/wp/v2/users` trả 404 với khách (người đã đăng nhập vẫn dùng được, `/users/me` giữ nguyên); `?author=1` và `/author/…` chuyển về trang chủ; bỏ sitemap người dùng (sitemap còn 7 tệp).
- **Khoá tạm đăng nhập:** một IP sai nhiều lần trong thời gian ngắn thì phải chờ. Lưu bằng transient `skb_bm_<md5 IP>`.
- Tắt nhanh toàn bộ khi cần gỡ lỗi: hằng `SKB_BM_OFF` trong `wp-config.php`.

### `.htaccess` gốc, khối `SKB_BAOMAT` (ngay sau `SKB_AUTH`)
Trả 403 cho: `xmlrpc.php`; `readme.html`, `license.txt`, `wp-config-sample.php`, `wp-admin/install.php`; tệp PHP gọi thẳng trong `themes/sk-bento/inc` và `template-parts`; mọi tệp/thư mục bắt đầu bằng dấu chấm (trừ `/.well-known/`).
Bản `.htaccess` trước khi sửa được lưu trong thư mục sao lưu trên hosting.

### Đã kiểm
Trang chủ, mẫu xe, bản tiếng Anh, đăng nhập, quản trị, REST `skb/v1/xe`, css/js/phông/ảnh, tệp xác minh Google, robots, sitemap: đều 200. Các đường dẫn bị chặn: đều 403. Form trên web chỉ gửi về chính sklight.vn nên không vướng CSP.
**Chưa kiểm:** Javis gọi REST bằng mật khẩu ứng dụng. Nếu Javis báo lỗi, xem lại bộ lọc `rest_endpoints` trong `inc/bao-mat.php`.

## Việc chỉ chủ làm được
Danh sách chi tiết (tài khoản quản trị, trang quản trị hosting, xác thực 2 bước cho các dịch vụ) nằm ở bản gốc trong dự án Claude. Riêng **Cloudflare**: sau khi bật proxy, máy chủ thấy IP Cloudflare thay vì IP khách; phải khôi phục IP thật, nếu không khoá tạm đăng nhập sẽ khoá nhầm cả nhóm người (xem `cloudflare-sklight.md`).

## Có thể làm thêm (cần sửa theme)
- Xác thực 2 bước cho đăng nhập WordPress.
- Giới hạn tần suất gửi form đặt lịch / kích hoạt bảo hành theo IP.
- CSP đầy đủ (giới hạn nguồn script); cần rà hết script nội tuyến trước.

## Tình trạng khác
- Hạn bộ nhớ đệm tệp tĩnh do luật rewrite `E=Cache-Control` trong khối `DAWP_OPTIMIZATIONS` của `.htaccess` gốc quyết định.
