<?php
/**
 * Ô "Xe của bạn là gì?": gõ tên xe (không dấu, có/không gạch như CR-V / crv) ra ngay mẫu phù hợp.
 * Danh sách mẫu lấy một lần từ /wp-json/skb/v1/xe (lưu tạm, tự xoá khi sửa mẫu xe / hãng xe).
 */
defined( 'ABSPATH' ) || exit;

/** "LED Viền Nội Thất Honda CRV (2017–2022)" → "Honda CRV (2017–2022)" */
function skb_ten_ngan( $title ) {
	$t = preg_replace( '/^\s*(led\s+)?(viền\s+)?(nội\s+thất|noi\s+that)\s+/iu', '', (string) $title );
	return '' !== trim( $t ) ? trim( $t ) : (string) $title;
}

function skb_tim_xe_du_lieu() {
	$cached = get_transient( 'skb_tim_xe' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$out = array();
	$q   = get_posts( array( 'post_type' => 'mau_xe', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	foreach ( $q as $p ) {
		$brands = wp_get_post_terms( $p->ID, 'hang_xe', array( 'fields' => 'names' ) );
		$goi    = function_exists( 'skb_goi' ) ? skb_goi( $p->ID ) : array();
		$thumb  = get_the_post_thumbnail_url( $p, 'thumbnail' );
		$out[]  = array(
			't' => skb_ten_ngan( get_the_title( $p ) ),
			'u' => get_permalink( $p ),
			'p' => skb_gia( $p->ID ),
			'i' => $thumb ? $thumb : '',
			'g' => count( $goi ),
			'k' => implode( ' ', array_merge( array( get_the_title( $p ), $p->post_name ), is_array( $brands ) ? $brands : array() ) ),
		);
	}
	$terms = get_terms( array( 'taxonomy' => 'hang_xe', 'hide_empty' => true ) );
	foreach ( is_wp_error( $terms ) ? array() : $terms as $t ) {
		$out[] = array(
			't' => ( $t->parent ? '' : 'Hãng ' ) . $t->name,
			'u' => get_term_link( $t ),
			'p' => '',
			'i' => '',
			'g' => 0,
			'n' => (int) $t->count,
			'k' => $t->name . ' ' . $t->slug,
			'h' => 1,
		);
	}
	set_transient( 'skb_tim_xe', $out, DAY_IN_SECONDS );
	return $out;
}

$skb_tim_xe_xoa = function () {
	delete_transient( 'skb_tim_xe' );
};
add_action( 'save_post_mau_xe', $skb_tim_xe_xoa );
add_action( 'deleted_post', $skb_tim_xe_xoa );
add_action( 'edited_hang_xe', $skb_tim_xe_xoa );
add_action( 'created_hang_xe', $skb_tim_xe_xoa );
add_action( 'delete_hang_xe', $skb_tim_xe_xoa );
add_action( 'updated_post_meta', function ( $mid, $pid, $key ) use ( $skb_tim_xe_xoa ) {
	if ( in_array( $key, array( '_skb_gia', '_skb_goi', '_thumbnail_id' ), true ) ) {
		$skb_tim_xe_xoa();
	}
}, 10, 3 );

add_action( 'rest_api_init', function () {
	register_rest_route( 'skb/v1', '/xe', array(
		'methods'             => 'GET',
		'permission_callback' => '__return_true',
		'callback'            => function () {
			$res = new WP_REST_Response( skb_tim_xe_du_lieu() );
			$res->header( 'Cache-Control', 'public, max-age=3600' );
			return $res;
		},
	) );
} );

/** Ô tìm xe. $variant: 'hero' (trang chủ) hoặc 'page' (trang Mẫu xe) */
function skb_tim_xe_box( $variant = 'page' ) {
	static $n = 0;
	$n++;
	$id = 'tx' . $n;
	?>
	<div class="tim-xe tim-xe-<?php echo esc_attr( $variant ); ?>" data-tim-xe data-src="<?php echo esc_url( rest_url( 'skb/v1/xe' ) ); ?>">
		<label class="sr-only" for="<?php echo esc_attr( $id ); ?>"><?php skb_e( 'Xe của bạn là gì?' ); ?></label>
		<div class="tim-xe-box">
			<svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
			<input id="<?php echo esc_attr( $id ); ?>" type="search" role="combobox" aria-expanded="false" aria-controls="<?php echo esc_attr( $id ); ?>-list" aria-autocomplete="list" autocomplete="off" enterkeyhint="search" placeholder="<?php echo esc_attr( skb__( 'Xe của bạn là gì? Ví dụ: CRV, Mazda 3, VF8' ) ); ?>">
		</div>
		<ul class="tim-xe-list" id="<?php echo esc_attr( $id ); ?>-list" role="listbox" hidden></ul>
	</div>
	<?php
}
