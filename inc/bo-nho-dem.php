<?php
/**
 * Bộ nhớ đệm trang của máy chủ LiteSpeed (không cần plugin).
 * Khách chưa đăng nhập xem trang (GET) → máy chủ lưu bản HTML 4 giờ, lần sau trả ngay không chạy PHP.
 * Tự xoá khi sửa nội dung, đổi Tùy biến, đổi menu, đổi tấm ốp đầu trang, hoặc khi nâng SKB_VER.
 * Người đã đăng nhập không dùng bản lưu (luật trong .htaccess của thư mục web).
 * Tắt nhanh: define( 'SKB_NO_PAGE_CACHE', true ); trong wp-config.php.
 */
defined( 'ABSPATH' ) || exit;

const SKB_CACHE_TAG = 'skbm';

function skb_cache_bat() {
	return ! ( defined( 'SKB_NO_PAGE_CACHE' ) && SKB_NO_PAGE_CACHE ) && isset( $_SERVER['SERVER_SOFTWARE'] ) && false !== stripos( (string) $_SERVER['SERVER_SOFTWARE'], 'litespeed' ); // phpcs:ignore
}

/* Trang công khai cho khách → cho phép lưu */
add_action( 'template_redirect', function () {
	if ( ! skb_cache_bat() || is_user_logged_in() || is_admin() || 'GET' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) { // phpcs:ignore
		return;
	}
	if ( is_404() || is_preview() || is_search() || post_password_required() || ( defined( 'DONOTCACHEPAGE' ) && DONOTCACHEPAGE ) ) {
		@header( 'X-LiteSpeed-Cache-Control: no-cache' ); // phpcs:ignore
		return;
	}
	// trang có thông báo sau khi gửi form (?datlich=ok…) chỉ lưu ngắn
	$ttl = isset( $_GET['datlich'] ) ? 300 : 4 * HOUR_IN_SECONDS; // phpcs:ignore
	@header( 'X-LiteSpeed-Cache-Control: public,max-age=' . $ttl ); // phpcs:ignore
	@header( 'X-LiteSpeed-Tag: ' . SKB_CACHE_TAG ); // phpcs:ignore
	// bản lưu tách theo cookie đăng nhập: người đã đăng nhập không bao giờ nhận bản lưu của khách
	@header( 'X-LiteSpeed-Vary: cookie=' . LOGGED_IN_COOKIE . ',cookie=wp-postpass_' . COOKIEHASH ); // phpcs:ignore
}, 0 );

/*
 * Người đã đăng nhập: gắn cookie _lscache_vary (LiteSpeed tự tách bản lưu theo cookie này),
 * nên trang họ xem luôn được dựng mới, có thanh quản trị. Đăng xuất thì xoá cookie.
 */
add_action( 'init', function () {
	if ( ! skb_cache_bat() || headers_sent() ) {
		return;
	}
	/*
	 * Bỏ qua yêu cầu REST API: hỏi is_user_logged_in() ở đây (trước khi WordPress biết đây là REST)
	 * làm WordPress chốt "chưa đăng nhập" và bỏ qua Mật khẩu ứng dụng (Javis gọi REST bằng cách này).
	 */
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore
	if ( isset( $_GET['rest_route'] ) || false !== strpos( $uri, '/' . rest_get_url_prefix() . '/' ) ) { // phpcs:ignore
		return;
	}
	if ( is_user_logged_in() ) {
		if ( empty( $_COOKIE['_lscache_vary'] ) ) {
			setcookie( '_lscache_vary', 'skb-' . substr( md5( (string) get_current_user_id() . wp_salt( 'nonce' ) ), 0, 8 ), array( 'expires' => 0, 'path' => COOKIEPATH, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
		}
	} elseif ( ! empty( $_COOKIE['_lscache_vary'] ) && 0 === strpos( (string) $_COOKIE['_lscache_vary'], 'skb-' ) ) { // phpcs:ignore
		setcookie( '_lscache_vary', '', array( 'expires' => time() - 3600, 'path' => COOKIEPATH, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
	}
}, 2 );
add_action( 'clear_auth_cookie', function () {
	setcookie( '_lscache_vary', '', array( 'expires' => time() - 3600, 'path' => COOKIEPATH, 'secure' => is_ssl(), 'httponly' => true, 'samesite' => 'Lax' ) );
} );

/* Danh sách xe cho ô tìm xe (REST) cũng lưu */
add_filter( 'rest_post_dispatch', function ( $res, $server, $req ) {
	if ( skb_cache_bat() && '/skb/v1/xe' === $req->get_route() && ! is_user_logged_in() ) {
		$res->header( 'X-LiteSpeed-Cache-Control', 'public,max-age=' . 4 * HOUR_IN_SECONDS );
		$res->header( 'X-LiteSpeed-Tag', SKB_CACHE_TAG );
	}
	return $res;
}, 10, 3 );

/** Xoá toàn bộ bản lưu của web này (theo thẻ, không đụng web khác cùng máy chủ) */
function skb_cache_xoa() {
	if ( ! skb_cache_bat() ) {
		return;
	}
	if ( ! headers_sent() ) {
		@header( 'X-LiteSpeed-Purge: tag=' . SKB_CACHE_TAG, false ); // phpcs:ignore
	} else {
		// đã gửi header (hiếm): nhờ lần tải trang quản trị kế tiếp xoá
		update_option( 'skb_cache_can_xoa', 1, false );
	}
}

foreach ( array( 'save_post', 'deleted_post', 'trashed_post' ) as $hook ) {
	add_action( $hook, function ( $post_id ) {
		// lịch hẹn khách gửi và bản lưu nháp tự động không làm đổi trang công khai
		if ( ! in_array( get_post_type( $post_id ), array( 'lich_hen', 'revision', 'customize_changeset', 'oembed_cache' ), true ) ) {
			skb_cache_xoa();
		}
	} );
}
foreach ( array( 'edited_term', 'created_term', 'delete_term', 'customize_save_after', 'wp_update_nav_menu', 'switch_theme', 'update_option_skb_hero_ops' ) as $hook ) {
	add_action( $hook, 'skb_cache_xoa' );
}

/* Nâng phiên bản giao diện (SKB_VER) hoặc có yêu cầu xoá còn treo → xoá ở lần tải kế tiếp */
add_action( 'init', function () {
	if ( ! skb_cache_bat() ) {
		return;
	}
	if ( get_option( 'skb_cache_ver' ) !== SKB_VER || get_option( 'skb_cache_can_xoa' ) ) {
		skb_cache_xoa();
		update_option( 'skb_cache_ver', SKB_VER, false );
		delete_option( 'skb_cache_can_xoa' );
	}
}, 1 );

/* Nút "Xoá bộ nhớ đệm" trên thanh quản trị */
add_action( 'admin_bar_menu', function ( $bar ) {
	if ( skb_cache_bat() && current_user_can( 'edit_posts' ) ) {
		$bar->add_node( array(
			'id'    => 'skb-xoa-dem',
			'title' => 'Xoá bộ nhớ đệm',
			'href'  => wp_nonce_url( admin_url( 'admin-post.php?action=skb_xoa_dem' ), 'skb_xoa_dem' ),
		) );
	}
}, 90 );
add_action( 'admin_post_skb_xoa_dem', function () {
	if ( ! current_user_can( 'edit_posts' ) || ! check_admin_referer( 'skb_xoa_dem' ) ) {
		wp_die( 'Không có quyền.' );
	}
	skb_cache_xoa();
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
	exit;
} );
