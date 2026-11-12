<?php
/**
 * Kích hoạt bảo hành điện tử: form trên web, dữ liệu nằm ở SK Core.
 *
 * Trình duyệt khách → admin-ajax (skb_kh_activate / skb_kh_lookup) → máy chủ web gọi SK Core
 * POST {SKB_CORE_URL}/api/public/kich-hoat với header x-sk-activation-secret.
 * Khóa bí mật chỉ nằm trong wp-config.php (không lưu database, không gửi ra trình duyệt):
 *   define( 'SKB_CORE_URL', 'https://core.sklight.vn' );
 *   define( 'SKB_CORE_ACT_SECRET', '...' );
 * Giới hạn 5 lần sai / 15 phút / IP cho mỗi loại (kích hoạt, tra cứu).
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function skb_kh_ready() {
	return defined( 'SKB_CORE_URL' ) && defined( 'SKB_CORE_ACT_SECRET' ) && strlen( (string) SKB_CORE_ACT_SECRET ) >= 24;
}

/** Link nút "Kích hoạt": ưu tiên link nhập tay ở Tùy biến, rồi trang kích hoạt mới (khi đã nối SK Core), cuối cùng là trang web cũ */
function skb_activate_url() {
	$custom = get_theme_mod( 'skb_activate_url', '' );
	if ( $custom && untrailingslashit( $custom ) !== 'https://sklight.vn/kich-hoat' ) {
		return $custom;
	}
	if ( skb_kh_ready() ) {
		$page = get_page_by_path( 'kich-hoat-bao-hanh' );
		if ( $page && 'publish' === $page->post_status ) {
			return get_permalink( $page );
		}
	}
	return 'https://sklight.vn/kich-hoat';
}

function skb_kh_ip() {
	return isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
}

function skb_kh_rate_key( $kind ) {
	return 'skb_kh_' . $kind . '_' . md5( skb_kh_ip() . wp_salt( 'nonce' ) );
}

function skb_kh_blocked( $kind ) {
	return (int) get_transient( skb_kh_rate_key( $kind ) ) >= 5;
}

function skb_kh_fail( $kind ) {
	$key = skb_kh_rate_key( $kind );
	set_transient( $key, (int) get_transient( $key ) + 1, 15 * MINUTE_IN_SECONDS );
}

/** Gọi SK Core; trả mảng kết quả hoặc null khi không kết nối được */
function skb_kh_call( array $body ) {
	if ( ! skb_kh_ready() ) {
		return null;
	}
	$args = array(
		'timeout' => 25,
		'headers' => array(
			'Content-Type'           => 'application/json',
			'x-sk-activation-secret' => SKB_CORE_ACT_SECRET,
		),
		'body'    => wp_json_encode( $body ),
	);
	$url = untrailingslashit( SKB_CORE_URL ) . '/api/public/kich-hoat';
	$res = wp_remote_post( $url, $args );
	// Máy chủ SK Core vừa khởi động lại có thể trả lời chậm hoặc lỗi tạm: thử lại một lần
	if ( is_wp_error( $res ) || (int) wp_remote_retrieve_response_code( $res ) >= 500 ) {
		$res = wp_remote_post( $url, $args );
	}
	if ( is_wp_error( $res ) ) {
		set_transient( 'skb_kh_last_error', gmdate( 'c' ) . ' ' . $res->get_error_message(), DAY_IN_SECONDS );
		return null;
	}
	$data = json_decode( wp_remote_retrieve_body( $res ), true );
	if ( ! is_array( $data ) || ! isset( $data['ok'] ) ) {
		set_transient( 'skb_kh_last_error', gmdate( 'c' ) . ' HTTP ' . wp_remote_retrieve_response_code( $res ) . ' ' . substr( wp_remote_retrieve_body( $res ), 0, 200 ), DAY_IN_SECONDS );
		return null;
	}
	if ( 200 !== (int) wp_remote_retrieve_response_code( $res ) && empty( $data['ok'] ) && in_array( (string) ( $data['error'] ?? '' ), array( 'server', 'server-no-secret', 'secret-mismatch', 'bad-request' ), true ) ) {
		return null;
	}
	return $data;
}

function skb_kh_guard( $nonce_action ) {
	if ( ! check_ajax_referer( $nonce_action, 'nonce', false ) ) {
		wp_send_json_error( array( 'message' => 'Phiên làm việc đã hết hạn. Vui lòng tải lại trang và thử lại.' ), 403 );
	}
	if ( ! empty( $_POST['website'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
		wp_send_json_error( array( 'message' => 'Không thể xử lý yêu cầu.' ), 400 );
	}
}

function skb_kh_down() {
	$hotline = skb_opt( 'hotline', '0868 055 555' );
	wp_send_json_error( array( 'message' => 'Hệ thống kích hoạt đang bận. Vui lòng thử lại sau ít phút hoặc gọi hotline ' . $hotline . '.' ), 503 );
}

function skb_kh_date( $iso ) {
	if ( ! $iso ) {
		return '';
	}
	$ts = strtotime( (string) $iso );
	return $ts ? wp_date( 'd/m/Y', $ts ) : '';
}

add_action( 'wp_ajax_skb_kh_activate', 'skb_kh_ajax_activate' );
add_action( 'wp_ajax_nopriv_skb_kh_activate', 'skb_kh_ajax_activate' );
function skb_kh_ajax_activate() {
	skb_kh_guard( 'skb_kh_activate' );
	if ( skb_kh_blocked( 'act' ) ) {
		wp_send_json_error( array( 'message' => 'Bạn đã thử quá nhiều lần. Vui lòng đợi 15 phút hoặc liên hệ hỗ trợ.' ), 429 );
	}
	// phpcs:disable WordPress.Security.NonceVerification
	$name  = isset( $_POST['customer_name'] ) ? trim( preg_replace( '/\s+/u', ' ', sanitize_text_field( wp_unslash( $_POST['customer_name'] ) ) ) ) : '';
	$phone = isset( $_POST['customer_phone'] ) ? preg_replace( '/[^\d+]/', '', sanitize_text_field( wp_unslash( $_POST['customer_phone'] ) ) ) : '';
	$plate = isset( $_POST['vehicle_plate'] ) ? strtoupper( trim( preg_replace( '/\s+/', ' ', sanitize_text_field( wp_unslash( $_POST['vehicle_plate'] ) ) ) ) ) : '';
	$code  = isset( $_POST['referral_code'] ) ? strtoupper( preg_replace( '/\s+/', '', sanitize_text_field( wp_unslash( $_POST['referral_code'] ) ) ) ) : '';
	// phpcs:enable
	$len = function_exists( 'mb_strlen' ) ? mb_strlen( $name, 'UTF-8' ) : strlen( $name );
	if ( $len < 2 || $len > 100 ) {
		wp_send_json_error( array( 'message' => 'Vui lòng nhập họ và tên.', 'field' => 'customer_name' ), 400 );
	}
	if ( ! preg_match( '/^\+?[0-9]{9,15}$/', $phone ) ) {
		wp_send_json_error( array( 'message' => 'Vui lòng nhập số điện thoại hợp lệ (9–15 chữ số).', 'field' => 'customer_phone' ), 400 );
	}
	if ( strlen( $plate ) < 4 || strlen( $plate ) > 20 || ! preg_match( '/^[A-Z0-9.\-\s]+$/', $plate ) ) {
		wp_send_json_error( array( 'message' => 'Vui lòng nhập biển số xe hợp lệ, ví dụ 30A-123.45.', 'field' => 'vehicle_plate' ), 400 );
	}
	if ( ! preg_match( '/^[A-Z]{4}$/', $code ) ) {
		skb_kh_fail( 'act' );
		wp_send_json_error( array( 'message' => 'Mã giới thiệu gồm 4 chữ cái in trên tem hộp sản phẩm.', 'field' => 'referral_code' ), 400 );
	}
	$r = skb_kh_call( array( 'action' => 'activate', 'code' => $code, 'name' => $name, 'phone' => $phone, 'plate' => $plate ) );
	if ( null === $r ) {
		skb_kh_down();
	}
	if ( empty( $r['ok'] ) ) {
		$err = (string) ( $r['error'] ?? '' );
		if ( 'code' === $err ) {
			skb_kh_fail( 'act' );
			wp_send_json_error( array( 'message' => 'Mã giới thiệu không đúng hoặc đã được kích hoạt. Kiểm tra lại tem trên hộp; nếu đã kích hoạt trước đó, bấm "Tra cứu mã đã kích hoạt".', 'field' => 'referral_code' ), 400 );
		}
		$fields = array( 'name' => 'customer_name', 'phone' => 'customer_phone', 'plate' => 'vehicle_plate' );
		wp_send_json_error( array( 'message' => 'Thông tin chưa hợp lệ, vui lòng kiểm tra lại.', 'field' => $fields[ $err ] ?? '' ), 400 );
	}
	delete_transient( skb_kh_rate_key( 'act' ) );
	if ( function_exists( 'skb_su_kien_cong' ) ) {
		skb_su_kien_cong( 'kich_hoat', 'web' );
	}
	wp_send_json_success(
		array(
			'activation_code' => (string) $r['activation_code'],
			'referral_code'   => (string) $r['referral_code'],
			'activated'       => skb_kh_date( $r['activated_at'] ?? '' ),
			'warranty_until'  => skb_kh_date( $r['warranty_until'] ?? '' ),
			'name'            => (string) ( $r['customer_name'] ?? '' ),
			'plate'           => (string) ( $r['vehicle_plate'] ?? '' ),
		)
	);
}

add_action( 'wp_ajax_skb_kh_lookup', 'skb_kh_ajax_lookup' );
add_action( 'wp_ajax_nopriv_skb_kh_lookup', 'skb_kh_ajax_lookup' );
function skb_kh_ajax_lookup() {
	skb_kh_guard( 'skb_kh_lookup' );
	if ( skb_kh_blocked( 'look' ) ) {
		wp_send_json_error( array( 'message' => 'Bạn đã tra cứu quá nhiều lần. Vui lòng đợi 15 phút hoặc liên hệ hỗ trợ.' ), 429 );
	}
	skb_kh_fail( 'look' ); // tra cứu nào cũng tính một lượt, giống web cũ
	$phone = isset( $_POST['lookup_phone'] ) ? preg_replace( '/[^\d+]/', '', sanitize_text_field( wp_unslash( $_POST['lookup_phone'] ) ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
	if ( ! preg_match( '/^\+?[0-9]{9,15}$/', $phone ) ) {
		wp_send_json_error( array( 'message' => 'Vui lòng nhập số điện thoại hợp lệ (9–15 chữ số).', 'field' => 'lookup_phone' ), 400 );
	}
	$r = skb_kh_call( array( 'action' => 'lookup', 'phone' => $phone ) );
	if ( null === $r ) {
		skb_kh_down();
	}
	$codes = ( ! empty( $r['ok'] ) && ! empty( $r['codes'] ) && is_array( $r['codes'] ) ) ? $r['codes'] : array();
	if ( ! $codes ) {
		wp_send_json_error( array( 'message' => 'Không tìm thấy mã kích hoạt với số điện thoại này. Kiểm tra lại số đã dùng khi kích hoạt hoặc gọi hotline.', 'field' => 'lookup_phone' ), 404 );
	}
	$out = array();
	foreach ( $codes as $c ) {
		$out[] = array(
			'code'           => (string) ( $c['activation_code'] ?? '' ),
			'activated'      => skb_kh_date( $c['activated_at'] ?? '' ),
			'warranty_until' => skb_kh_date( $c['warranty_until'] ?? '' ),
		);
	}
	wp_send_json_success( array( 'codes' => $out ) );
}
