<?php
/**
 * Bảo mật (0.16.2). Máy chủ không áp dụng các dòng Header / FilesMatch trong .htaccess,
 * nên các lớp bảo vệ dưới đây làm bằng PHP; phần chặn theo đường dẫn nằm ở khối SKB_BAOMAT của .htaccess gốc.
 *
 * 1. Tắt hẳn XML-RPC (cửa dò mật khẩu hàng loạt). Web không dùng: Javis đi qua REST + mật khẩu ứng dụng.
 * 2. Header bảo mật cho mọi trang.
 * 3. Không lộ tên đăng nhập: REST /users với khách, trang tác giả, sitemap người dùng.
 * 4. Khoá tạm đăng nhập sau nhiều lần sai.
 *
 * Tắt nhanh từng phần khi cần gỡ lỗi: define( 'SKB_BM_OFF', true ); trong wp-config.php.
 */
defined( 'ABSPATH' ) || exit;

if ( defined( 'SKB_BM_OFF' ) && SKB_BM_OFF ) {
	return;
}

/* ---------- 1. XML-RPC ---------- */
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	header( 'Content-Type: text/plain; charset=utf-8' );
	echo 'XML-RPC is disabled.';
	exit;
}
add_filter( 'xmlrpc_enabled', '__return_false' );
add_filter( 'pings_open', '__return_false', 99 );
remove_action( 'wp_head', 'rsd_link' );
remove_action( 'wp_head', 'wlwmanifest_link' );
remove_action( 'wp_head', 'wp_generator' );
add_filter( 'wp_headers', function ( $h ) {
	unset( $h['X-Pingback'] );
	return $h;
} );

/* ---------- 2. Header bảo mật ---------- */
function skb_bm_headers( $csp = true ) {
	if ( headers_sent() ) {
		return;
	}
	if ( is_ssl() ) {
		/* 180 ngày, chỉ tên miền chính (không gồm tên miền con: ftp, mail chưa chắc có HTTPS) */
		header( 'Strict-Transport-Security: max-age=15552000' );
	}
	header( 'X-Content-Type-Options: nosniff' );
	header( 'X-Frame-Options: SAMEORIGIN' );
	header( 'Referrer-Policy: strict-origin-when-cross-origin' );
	/* geolocation giữ cho chính web: nút "Đại lý gần tôi" */
	header( 'Permissions-Policy: camera=(), microphone=(), payment=(), usb=(), geolocation=(self)' );
	header( 'Cross-Origin-Opener-Policy: same-origin-allow-popups' );
	if ( $csp ) {
		/* CSP mức an toàn: không cho web khác nhúng trang, không cho đổi gốc đường dẫn, không plugin, form chỉ gửi về chính web */
		header( "Content-Security-Policy: frame-ancestors 'self'; base-uri 'self'; object-src 'none'; form-action 'self'; upgrade-insecure-requests" );
	}
}
add_action( 'send_headers', function () {
	skb_bm_headers( true );
} );
add_action( 'login_init', function () {
	skb_bm_headers( false );
} );
add_action( 'admin_init', function () {
	skb_bm_headers( false );
} );

/* ---------- 3. Không lộ tên đăng nhập ---------- */
add_filter( 'rest_endpoints', function ( $endpoints ) {
	if ( ! is_user_logged_in() ) {
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) && '/wp/v2/users/me' !== $route ) {
				unset( $endpoints[ $route ] );
			}
		}
	}
	return $endpoints;
} );
add_action( 'template_redirect', function () {
	if ( is_author() || ( isset( $_GET['author'] ) && ! is_admin() ) ) { // phpcs:ignore
		wp_safe_redirect( home_url( '/' ), 301 );
		exit;
	}
}, 1 );
add_filter( 'wp_sitemaps_add_provider', function ( $provider, $name ) {
	return 'users' === $name ? false : $provider;
}, 10, 2 );

/* ---------- 4. Khoá tạm đăng nhập: sai 8 lần trong 15 phút thì IP đó phải chờ 15 phút ---------- */
function skb_bm_khoa() {
	$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : '';
	return 'skb_bm_' . md5( $ip );
}
add_action( 'wp_login_failed', function () {
	$k = skb_bm_khoa();
	set_transient( $k, (int) get_transient( $k ) + 1, 15 * MINUTE_IN_SECONDS );
} );
add_filter( 'authenticate', function ( $user ) {
	if ( (int) get_transient( skb_bm_khoa() ) >= 8 ) {
		return new WP_Error( 'skb_bm_khoa', 'Đăng nhập sai quá nhiều lần. Vui lòng thử lại sau 15 phút.' );
	}
	return $user;
}, 99 );
add_action( 'wp_login', function () {
	delete_transient( skb_bm_khoa() );
} );
