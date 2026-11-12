<?php
/**
 * Lịch hẹn lắp đặt → SK Core (tiện ích "Lịch hẹn lắp đặt", release/239).
 * Web vẫn lưu bản dự phòng (loại nội dung lich_hen) và gửi email như cũ; sau khi trả trang cho khách,
 * máy chủ web gửi sang SK Core: POST {SKB_CORE_URL}/api/public/dat-lich (cùng khóa với cổng kích hoạt).
 * Gửi lỗi thì đánh dấu chờ và tự gửi lại mỗi giờ (web_ref = wp-<ID> nên không tạo trùng bên SK Core).
 */
defined( 'ABSPATH' ) || exit;

/** Gửi một lịch hẹn; trả mã LH-xxxxx hoặc '' nếu chưa gửi được */
function skb_lh_gui_core( $post_id ) {
	if ( ! function_exists( 'skb_kh_ready' ) || ! skb_kh_ready() || 'lich_hen' !== get_post_type( $post_id ) ) {
		return '';
	}
	$code = (string) get_post_meta( $post_id, '_skb_core_ma', true );
	if ( '' !== $code ) {
		return $code;
	}
	$m    = function ( $k ) use ( $post_id ) {
		return (string) get_post_meta( $post_id, '_skb_' . $k, true );
	};
	$body = array(
		'name'     => $m( 'ho_ten' ),
		'phone'    => $m( 'dien_thoai' ),
		'car'      => $m( 'dong_xe' ),
		'dealer'   => $m( 'dai_ly' ),
		'date'     => $m( 'ngay' ),
		'note'     => $m( 'ghi_chu' ),
		'page_url' => $m( 'trang' ),
		'web_ref'  => 'wp-' . $post_id . '-' . substr( md5( home_url() ), 0, 6 ),
	);
	$res  = wp_remote_post(
		untrailingslashit( SKB_CORE_URL ) . '/api/public/dat-lich',
		array(
			'timeout' => 20,
			'headers' => array(
				'Content-Type'           => 'application/json',
				'x-sk-activation-secret' => SKB_CORE_ACT_SECRET,
			),
			'body'    => wp_json_encode( $body ),
		)
	);
	$data = is_wp_error( $res ) ? null : json_decode( wp_remote_retrieve_body( $res ), true );
	if ( is_array( $data ) && ! empty( $data['ok'] ) && ! empty( $data['code'] ) ) {
		update_post_meta( $post_id, '_skb_core_ma', sanitize_text_field( $data['code'] ) );
		delete_post_meta( $post_id, '_skb_core_cho' );
		delete_post_meta( $post_id, '_skb_core_loi' );
		return (string) $data['code'];
	}
	$err = is_wp_error( $res ) ? $res->get_error_message() : 'HTTP ' . wp_remote_retrieve_response_code( $res ) . ' ' . substr( (string) wp_remote_retrieve_body( $res ), 0, 160 );
	update_post_meta( $post_id, '_skb_core_cho', time() );
	update_post_meta( $post_id, '_skb_core_loi', sanitize_text_field( $err ) );
	return '';
}

/** Gửi sau khi đã trả trang cho khách (không bắt khách chờ SK Core) */
function skb_lh_gui_sau( $post_id ) {
	update_post_meta( $post_id, '_skb_core_cho', time() );
	register_shutdown_function( function () use ( $post_id ) {
		if ( function_exists( 'litespeed_finish_request' ) ) {
			litespeed_finish_request();
		} elseif ( function_exists( 'fastcgi_finish_request' ) ) {
			fastcgi_finish_request();
		}
		skb_lh_gui_core( $post_id );
	} );
}

/* Gửi lại lịch hẹn chưa gửi được: mỗi giờ, tối đa 20 cái */
add_action( 'init', function () {
	if ( ! wp_next_scheduled( 'skb_lh_gui_lai' ) ) {
		wp_schedule_event( time() + 300, 'hourly', 'skb_lh_gui_lai' );
	}
} );
add_action( 'skb_lh_gui_lai', function () {
	$ids = get_posts( array(
		'post_type'   => 'lich_hen',
		'post_status' => 'any',
		'numberposts' => 20,
		'fields'      => 'ids',
		'meta_key'    => '_skb_core_cho', // phpcs:ignore
		'orderby'     => 'date',
		'order'       => 'ASC',
	) );
	foreach ( $ids as $id ) {
		skb_lh_gui_core( $id );
	}
} );

/* Cột "SK Core" trong danh sách lịch hẹn + nút gửi lại */
add_filter( 'manage_lich_hen_posts_columns', function ( $cols ) {
	$cols['skb_core'] = 'SK Core';
	return $cols;
}, 20 );
add_action( 'manage_lich_hen_posts_custom_column', function ( $col, $id ) {
	if ( 'skb_core' !== $col ) {
		return;
	}
	$code = get_post_meta( $id, '_skb_core_ma', true );
	if ( $code ) {
		echo '<strong>' . esc_html( $code ) . '</strong>';
		return;
	}
	if ( ! skb_kh_ready() ) {
		echo '<span style="color:#888">Chưa nối SK Core</span>';
		return;
	}
	$url = wp_nonce_url( admin_url( 'admin-post.php?action=skb_lh_gui&id=' . (int) $id ), 'skb_lh_gui_' . $id );
	$err = get_post_meta( $id, '_skb_core_loi', true );
	echo '<span style="color:#b32d2e">Chờ gửi</span>' . ( $err ? '<br><small title="' . esc_attr( $err ) . '">lỗi lần trước</small>' : '' ) . '<br><a href="' . esc_url( $url ) . '">Gửi lại ngay</a>';
}, 10, 2 );
add_action( 'admin_post_skb_lh_gui', function () {
	$id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0; // phpcs:ignore
	if ( ! $id || ! current_user_can( 'edit_post', $id ) || ! check_admin_referer( 'skb_lh_gui_' . $id ) ) {
		wp_die( 'Không có quyền.' );
	}
	skb_lh_gui_core( $id );
	wp_safe_redirect( admin_url( 'edit.php?post_type=lich_hen' ) );
	exit;
} );
