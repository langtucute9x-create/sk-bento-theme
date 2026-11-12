<?php
/**
 * Chuyển hướng 301 từ đường dẫn của web cũ (WooCommerce + Flatsome) sang web mới, để Google và link khách đã lưu không bị lỗi 404.
 * Chỉ chạy khi trang được yêu cầu không tồn tại (404), nên không ảnh hưởng trang đang có.
 *
 *   /san-pham/{slug}                    → Mẫu xe có _skb_old_url = /san-pham/{slug} (hoặc cùng slug)
 *   /san-pham, /cua-hang                → /mau-xe/
 *   /danh-muc-san-pham/{hãng}/{dòng}    → trang Hãng xe tương ứng (hoặc /mau-xe/)
 *   /gio-hang, /thanh-toan, /tai-khoan  → trang chủ
 *   /kich-hoat, /kich-hoat-san-pham/... → /kich-hoat-bao-hanh/
 *   /{slug cũ của bài viết}             → bài có _skb_old_url = /{slug} (vd. /2015-2)
 *   /category/{chuyên mục}              → /tin-tuc/ hoặc /huong-dan-su-dung/
 *   /sitemap_index.xml, /*-sitemap.xml  → /wp-sitemap.xml
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Đường dẫn yêu cầu, bỏ phần thư mục của web (vd. /moitest), không có dấu / ở cuối */
function skb_redirect_path() {
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore
	$path = rawurldecode( (string) wp_parse_url( $uri, PHP_URL_PATH ) );
	$base = untrailingslashit( (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH ) );
	if ( '' !== $base && 0 === strpos( $path, $base . '/' ) ) {
		$path = substr( $path, strlen( $base ) );
	}
	$path = '/' . trim( $path, '/' );
	return strtolower( $path );
}

function skb_post_by_old_url( $old ) {
	$ids = get_posts(
		array(
			'post_type'   => array( 'mau_xe', 'post', 'page' ),
			'post_status' => 'publish',
			'numberposts' => 1,
			'fields'      => 'ids',
			'meta_key'    => '_skb_old_url', // phpcs:ignore
			'meta_value'  => $old, // phpcs:ignore
		)
	);
	return $ids ? (int) $ids[0] : 0;
}

/** Danh mục sản phẩm cũ → trang Hãng xe: tìm theo tên danh mục ở web cũ, rồi theo slug */
function skb_redirect_category( array $segs ) {
	global $wpdb;
	$segs = array_values( array_filter( $segs ) );
	while ( $segs ) {
		$slug = sanitize_title( end( $segs ) );
		$name   = '';
		$prefix = defined( 'SKB_OLD_PREFIX' ) ? SKB_OLD_PREFIX : 'dawtt_'; // bảng web cũ còn trong cùng database
		$table  = $prefix . 'terms';
		if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) ) === $table ) {
			$name = (string) $wpdb->get_var( $wpdb->prepare( "SELECT t.name FROM {$table} t JOIN {$prefix}term_taxonomy tt ON tt.term_id = t.term_id WHERE tt.taxonomy = 'product_cat' AND t.slug = %s LIMIT 1", $slug ) ); // phpcs:ignore
		}
		$cands = array();
		if ( '' !== $name ) {
			$alias   = array( 'vinfast' => 'VinFast', 'huyndai' => 'Hyundai', 'kia' => 'Kia', 'mg' => 'MG', 'bmw' => 'BMW' );
			$k       = strtolower( trim( $name ) );
			$cands[] = isset( $alias[ $k ] ) ? $alias[ $k ] : trim( $name );
		}
		foreach ( $cands as $n ) {
			$terms = get_terms( array( 'taxonomy' => 'hang_xe', 'hide_empty' => false, 'name' => $n ) );
			if ( ! is_wp_error( $terms ) && $terms ) {
				return get_term_link( $terms[0] );
			}
		}
		$t = get_term_by( 'slug', 'huyndai' === $slug ? 'hyundai' : $slug, 'hang_xe' );
		if ( $t ) {
			return get_term_link( $t );
		}
		array_pop( $segs ); // không thấy dòng xe → thử hãng
	}
	return get_post_type_archive_link( 'mau_xe' );
}

function skb_redirect_target( $path ) {
	if ( '/' === $path ) {
		return '';
	}
	$segs = explode( '/', trim( $path, '/' ) );
	$head = $segs[0];

	if ( in_array( $path, array( '/sitemap_index.xml', '/sitemap.xml' ), true ) || preg_match( '#^/[a-z0-9_-]+-sitemap[0-9]*\.xml$#', $path ) ) {
		return home_url( '/wp-sitemap.xml' );
	}
	if ( 'san-pham' === $head ) {
		if ( isset( $segs[1] ) && '' !== $segs[1] ) {
			$id = skb_post_by_old_url( '/san-pham/' . $segs[1] );
			if ( ! $id ) {
				$p  = get_page_by_path( $segs[1], OBJECT, 'mau_xe' );
				$id = $p ? $p->ID : 0;
			}
			return $id ? get_permalink( $id ) : get_post_type_archive_link( 'mau_xe' );
		}
		return get_post_type_archive_link( 'mau_xe' );
	}
	if ( in_array( $head, array( 'cua-hang', 'shop', 'product', 'products' ), true ) ) {
		return get_post_type_archive_link( 'mau_xe' );
	}
	if ( in_array( $head, array( 'danh-muc-san-pham', 'product-category' ), true ) ) {
		return skb_redirect_category( array_slice( $segs, 1 ) );
	}
	if ( in_array( $head, array( 'gio-hang', 'thanh-toan', 'tai-khoan', 'cart', 'checkout', 'my-account', 'wishlist' ), true ) ) {
		return home_url( '/' );
	}
	if ( in_array( $head, array( 'kich-hoat', 'kich-hoat-san-pham', 'kich-hoat-sk' ), true ) ) {
		$p = get_page_by_path( 'kich-hoat-bao-hanh' );
		return $p ? get_permalink( $p ) : '';
	}
	if ( 'category' === $head && isset( $segs[1] ) ) {
		$p = get_page_by_path( 'huong-dan-su-dung' === $segs[1] ? 'huong-dan-su-dung' : 'tin-tuc' );
		return $p ? get_permalink( $p ) : '';
	}
	if ( 1 === count( $segs ) ) {
		$id = skb_post_by_old_url( '/' . $segs[0] );
		if ( $id ) {
			return get_permalink( $id );
		}
	}
	return '';
}

add_action( 'template_redirect', function () {
	if ( ! is_404() ) {
		return;
	}
	$target = skb_redirect_target( skb_redirect_path() );
	if ( $target && ! is_wp_error( $target ) ) {
		wp_safe_redirect( $target, 301, 'SK Bento' );
		exit;
	}
}, 1 );
