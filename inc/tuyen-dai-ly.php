<?php
/**
 * Trang Tuyển đại lý (/tuyen-dai-ly/), giao diện ở page-tuyen-dai-ly.php.
 *
 * - Tự tạo trang một lần nếu chưa có (đánh dấu option skb_trang_tuyen_dl; xoá trang sau đó thì không tạo lại).
 * - Mô tả SEO và ảnh chia sẻ mặc định khi chủ chưa điền (meta _skb_seo_desc, ảnh đại diện).
 * - Chỉ có bản tiếng Việt: bản nước ngoài chuyển sang Liên hệ (inc/da-ngu.php).
 * Nội dung chính sách đại lý: chủ gõ vào ô nội dung của trang, hiện ở khối "Chính sách đại lý".
 */

defined( 'ABSPATH' ) || exit;

const SKB_TDL_SLUG = 'tuyen-dai-ly';

add_action( 'admin_init', function () {
	if ( get_option( 'skb_trang_tuyen_dl' ) || ! current_user_can( 'publish_pages' ) ) {
		return;
	}
	if ( ! get_page_by_path( SKB_TDL_SLUG, OBJECT, 'page' ) ) {
		wp_insert_post( array(
			'post_type'   => 'page',
			'post_status' => 'publish',
			'post_title'  => 'Tuyển đại lý',
			'post_name'   => SKB_TDL_SLUG,
		) );
	}
	update_option( 'skb_trang_tuyen_dl', 1, false );
} );

/** Đường dẫn ảnh của trang trong theme */
function skb_tdl_anh( $file ) {
	return get_template_directory_uri() . '/assets/img/tuyen-dai-ly/' . $file;
}

/** Mô tả SEO mặc định */
function skb_tdl_mo_ta() {
	return 'SK Ambient Light tuyển đại lý toàn quốc: đại lý lắp đặt lợi nhuận 20-50% mỗi bộ, đại lý phân phối cấp 1 lợi nhuận 5-20%. Giá được giữ, bảo hành 24 tháng, mẫu mới liên tục. Gọi hoặc Zalo 0868 055 555.';
}
