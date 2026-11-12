# Chuyển sklight.vn sang web mới — nhật ký và cách quay lại

Cập nhật 10/11/2026 20:25. Chủ chốt: **sau khi chuyển không còn "/moitest/" ở bất kỳ đâu** (trang, ảnh, file, trang quản trị).
Bản trong kho công khai: đã lược tên database, tên tài khoản và mã kích hoạt cụ thể.

## ĐÃ CHUYỂN CHÍNH THỨC — 10/11/2026 lúc 19:56

Chủ quyết định chuyển thật luôn, bỏ qua buổi tổng duyệt.

- **Sao lưu trước khi chuyển** (trong thư mục sao lưu `chuyen-web-20261110` trên hosting, ngoài thư mục web):
  - bản database lúc 17:17 (đủ 123 bảng, một bản đã tải về máy chủ) và lúc 19:55 (so từng byte, khớp);
  - 6 file cấu hình: `wp-config-goc.php.txt`, `htaccess-goc.txt`, `htaccess-moitest.txt` và các bản `-moi`.
- **Đổi file:** script chạy 6,9 giây; kiểm CRC 3 file, thư mục và theme đều đạt.
- **Đổi đường dẫn trong database:** đổi 140/140 ô, 0 ô bỏ qua.
  - Mã lần đổi là `202611101306-goc`; bản cũ lưu trong bảng `…skb_chuyen_web`, hoàn tác được.
  - Sau khi đổi, còn 0 ô chứa "moitest".
  - home và siteurl đều là https://sklight.vn; môi trường production.
- **Bật cho Google lập chỉ mục:** đã bật; đã lưu lại đường dẫn tĩnh.
  - robots.txt cho phép thu thập và trỏ tới wp-sitemap.xml.
  - Không còn header noindex.
- **Bộ nhớ đệm:** đã xoá toàn bộ và xoá 25 dữ liệu tạm (transient).
  - Trang chủ trước đó còn 8 link /moitest trong khối video, do dữ liệu tạm còn giữ link cũ.
  - Công cụ đã được sửa (`inc/chuyen-web.php`): nút "Xoá toàn bộ bộ nhớ đệm máy chủ" giờ xoá luôn dữ liệu tạm.
  - Lần tải thứ hai đã trả từ bộ nhớ đệm.
- **Kiểm tra sau khi chuyển:**
  - **Trang trong sitemap:** cả 177 đường dẫn trả 200, đúng theme, không còn chữ "moitest".
  - **Đường dẫn cũ:** cả 77 đường dẫn sản phẩm/bài/trang cũ chuyển 301 đúng trang. Các nhóm sau cũng chuyển đúng:
    - danh mục, cửa hàng, giỏ hàng;
    - các đường dẫn kích hoạt cũ (/kich-hoat/ và /kich-hoat-san-pham/);
    - sitemap cũ;
    - mọi đường dẫn /moitest/….
  - **Ảnh web cũ:** /wp-content/uploads/… vẫn mở được; link .jpg cũ trả về bản .webp tương ứng.
  - **Thư mục cũ:** PHP trong `wp-content-cu` bị chặn (403).
  - **File của trang:** CSS/JS/ảnh trên trang mẫu xe đều trả 200.
  - **Kết nối SK Core:** tra cứu kích hoạt bằng SĐT gọi SK Core được (số thử trả "không tìm thấy", đúng như mong đợi).
  - **Nội dung `.htaccess` gốc sau khi lưu đường dẫn tĩnh:** vẫn còn SKB_LSCACHE và SKB_ANH_WEB_CU, không còn DAWP_LSCACHE.
- **Mã kích hoạt:** web cũ và SK Core cùng có 3.093 mã. Lúc chuyển chênh 1 mã đã kích hoạt (kích hoạt trên web cũ sau lần nhập vào SK Core); đã cập nhật vào SK Core tối 10/11.
- **Không thử đặt lịch thật** để tránh tạo dữ liệu thử trong SK Core. Đặt lịch dùng chung địa chỉ và khoá SK Core với tra cứu kích hoạt, mà tra cứu đã chạy được.

### Việc còn lại sau khi chuyển (tình trạng 12/11/2026)
1. ~~Mã kích hoạt chênh~~: đã xong.
2. ~~Lịch chạy định kỳ (cron) mỗi 5 phút~~: đã thêm 10/11.
3. **Mã theo dõi:** GA, Pixel, Zalo chat.
4. **Tài khoản quản trị** cho nhân viên; xác nhận chủ của một tài khoản quản trị cũ.
5. **Toạ độ Hoa Mai Auto.**
6. ~~Google Search Console: khai báo sitemap~~: đã xong; theo dõi lỗi 404 trong 2 tuần.
7. **Catalog:** thêm nút Đặt lịch lắp và Đại lý gần tôi; đổi `detailUrl` sang `/mau-xe/{slug}/`.
8. **Thêm 12 mẫu xe** chỉ catalog có.
9. **Sau ít nhất 3 tháng:** dọn `moitest/`, `wp-content-cu` (gộp ảnh cũ vào `wp-content/uploads` trước) và các bảng web cũ.

## Cách chuyển (để tra lại / quay lại)

Lõi WordPress ở thư mục gốc và ở /moitest giống hệt nhau (7.1.4), hai bên dùng chung một database. Web mới được chuyển ra gốc bằng 6 bước:

1. Đổi tên `wp-content` của web cũ → `wp-content-cu`.
2. Chuyển `moitest/wp-content` → `wp-content` ở thư mục gốc.
3. Thay `wp-config.php` gốc bằng bản của web mới:
   - WP_HOME/WP_SITEURL = https://sklight.vn;
   - môi trường production;
   - WP_CACHE_KEY_SALT `sklight-`.
4. Thay `.htaccess` gốc:
   - bỏ khối DAWP_LSCACHE;
   - thêm khối SKB_LSCACHE;
   - thêm khối SKB_ANH_WEB_CU, đặt trước khối DuyAnhWeb Image Optimizer.
5. Thay `moitest/.htaccess` bằng luật chuyển 301 mọi đường dẫn về gốc.
6. Đổi đường dẫn trong database bằng Công cụ › Chuyển web chính thức.

Script chuyển và script quay lại (`chuyen.js`, `quay-lai.js`) chỉ lưu ở bản gốc trong dự án Claude, tài liệu `chuyen-web-script.md`.

### Quay lại web cũ (khoảng 5 phút, chỉ khi có sự cố lớn)
1. Đăng nhập wp-admin › Công cụ › Chuyển web chính thức › **Hoàn tác** (lần `202611101306-goc`).
2. Chạy `quay-lai.js` với `THU = false` trong tab quản trị hosting. Script này:
   - chuyển `wp-content` về `moitest/`;
   - đổi `wp-content-cu` → `wp-content`;
   - khôi phục 3 file từ thư mục sao lưu.
3. Xoá bộ nhớ đệm, rồi kiểm web cũ và /moitest.

Các bảng của web cũ chưa bị sửa gì. Lịch hẹn và kích hoạt nhận trên web mới đã nằm trong SK Core.
