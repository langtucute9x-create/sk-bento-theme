<?php
/**
 * SEO gọn trong theme (không dùng plugin).
 *
 * - Ô SEO trong trình soạn bài (bài viết, trang, mẫu xe): từ khoá chính, tiêu đề SEO, mô tả, ẩn khỏi Google,
 *   xem trước kết quả Google và bảng chấm điểm (assets/seo-editor.js).
 * - Đầu trang: <title>, meta description, robots noindex, thẻ Open Graph / Twitter để chia sẻ Facebook, Zalo.
 * Meta: _skb_seo_title, _skb_seo_desc, _skb_seo_kw, _skb_seo_noindex.
 */

defined( 'ABSPATH' ) || exit;

function skb_seo_loai() {
	return array( 'post', 'page', 'mau_xe' );
}

add_action( 'init', function () {
	foreach ( skb_seo_loai() as $pt ) {
		add_post_type_support( $pt, 'custom-fields' ); // để trình soạn khối đọc/ghi meta qua REST
		foreach ( array( '_skb_seo_title', '_skb_seo_desc', '_skb_seo_kw' ) as $k ) {
			register_post_meta( $pt, $k, array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
				'auth_callback'     => function ( $allowed, $key, $post_id ) {
					return current_user_can( 'edit_post', $post_id );
				},
			) );
		}
		register_post_meta( $pt, '_skb_seo_noindex', array(
			'type'          => 'boolean',
			'single'        => true,
			'default'       => false,
			'show_in_rest'  => true,
			'auth_callback' => function ( $allowed, $key, $post_id ) {
				return current_user_can( 'edit_post', $post_id );
			},
		) );
	}
}, 20 );

add_action( 'enqueue_block_editor_assets', function () {
	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $s || ! in_array( $s->post_type, skb_seo_loai(), true ) ) {
		return;
	}
	wp_enqueue_script( 'skb-seo-editor', get_template_directory_uri() . '/assets/seo-editor.js', array( 'wp-plugins', 'wp-element', 'wp-components', 'wp-data', 'wp-editor', 'wp-edit-post' ), SKB_VER, true );
	wp_localize_script( 'skb-seo-editor', 'SKB_SEO', array(
		'site' => get_bloginfo( 'name' ),
		'home' => home_url( '/' ),
		'host' => wp_parse_url( home_url(), PHP_URL_HOST ),
		'sep'  => ' – ',
	) );
} );

/** Mô tả cho trang đang xem (rỗng nếu không có) */
function skb_seo_mo_ta() {
	if ( function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai() ) {
		return skb_ngu_mo_ta();
	}
	if ( is_front_page() ) {
		$d = function_exists( 'skb_opt' ) ? trim( (string) skb_opt( 'hero_sub', '' ) ) : '';
		return '' !== $d ? $d : 'Đèn LED nội thất ô tô cao cấp SK Ambient Light: ốp thay zin theo từng dòng xe, không cắt chích dây, lắp đặt tại hệ thống đại lý SK toàn quốc.';
	}
	if ( is_home() ) {
		return 'Tin tức, kinh nghiệm chọn và lắp đèn LED nội thất ô tô từ SK Ambient Light.';
	}
	if ( is_singular() ) {
		$id = get_queried_object_id();
		$d  = (string) get_post_meta( $id, '_skb_seo_desc', true );
		if ( '' === $d ) {
			$p = get_post( $id );
			$d = $p ? ( has_excerpt( $p ) ? $p->post_excerpt : wp_strip_all_tags( strip_shortcodes( $p->post_content ) ) ) : '';
			$d = wp_trim_words( $d, 30, '…' );
		}
		$d = trim( preg_replace( '/\s+/u', ' ', $d ) );
		if ( '' === $d && (int) get_option( 'page_for_posts' ) === $id ) {
			$d = 'Tin tức, kinh nghiệm chọn và lắp đèn LED nội thất ô tô từ SK Ambient Light.';
		}
		if ( is_singular( 'dai_ly' ) ) {
			$dc = trim( (string) get_post_meta( $id, '_skb_dia_chi', true ) );
			$dt = trim( (string) get_post_meta( $id, '_skb_dien_thoai', true ) );
			$d  = 'Đại lý lắp đèn LED nội thất SK Ambient Light ' . get_the_title( $id ) . ( $dc ? ', địa chỉ ' . $dc : '' ) . ( $dt ? ', điện thoại ' . $dt : '' ) . '. Xem giờ mở cửa và chỉ đường.';
		}
		if ( '' === $d ) {
			$d = get_the_title( $id ) . ' – SK Ambient Light: đèn LED nội thất ô tô ốp thay zin theo từng dòng xe, lắp đặt tại hệ thống đại lý SK toàn quốc.';
		}
		return $d;
	}
	if ( is_tax() || is_category() || is_tag() ) {
		$t = get_queried_object();
		$d = $t && ! empty( $t->description ) ? wp_strip_all_tags( $t->description ) : '';
		if ( '' === $d && $t && ( is_category() || is_tag() ) ) {
			$d = $t->name . ' – bài viết từ SK Ambient Light về đèn LED nội thất ô tô.';
		}
		if ( '' === $d && $t && is_tax( 'hang_xe' ) ) {
			$d = 'Đèn LED nội thất ốp thay zin cho xe ' . $t->name . ' — chọn đúng đời xe, lắp đặt tại hệ thống đại lý SK toàn quốc.';
		}
		return wp_trim_words( $d, 30, '…' );
	}
	if ( is_post_type_archive( 'mau_xe' ) ) {
		return 'Đèn LED nội thất ốp thay zin theo từng dòng xe: Toyota, Honda, Mazda, Hyundai, Kia, Ford, VinFast… Xem giá các gói và đặt lịch lắp đặt.';
	}
	if ( is_post_type_archive( 'dai_ly' ) ) {
		return 'Hệ thống đại lý lắp đặt đèn LED nội thất SK Ambient Light trên toàn quốc: địa chỉ, số điện thoại, giờ mở cửa và chỉ đường.';
	}
	return '';
}

add_filter( 'pre_get_document_title', function ( $t ) {
	if ( is_singular( skb_seo_loai() ) ) {
		$s = trim( (string) get_post_meta( get_queried_object_id(), '_skb_seo_title', true ) );
		if ( '' !== $s ) {
			return $s;
		}
	}
	return $t;
} );

add_filter( 'wp_robots', function ( $r ) {
	if ( is_author() ) {
		$r['noindex'] = true;
		$r['follow']  = true;
	}
	if ( is_singular( skb_seo_loai() ) && get_post_meta( get_queried_object_id(), '_skb_seo_noindex', true ) ) {
		$r['noindex'] = true;
		$r['follow']  = true;
		unset( $r['max-image-preview'] );
	}
	return $r;
} );

add_action( 'wp_head', function () {
	if ( is_404() || is_search() ) {
		return;
	}
	$d = skb_seo_mo_ta();
	if ( '' !== $d ) {
		printf( '<meta name="description" content="%s">' . "\n", esc_attr( $d ) );
	}
	$title = wp_get_document_title();
	$url   = is_singular() ? get_permalink( get_queried_object_id() ) : ( is_front_page() ? home_url( '/' ) : '' );
	$img   = '';
	if ( is_singular() && has_post_thumbnail( get_queried_object_id() ) ) {
		$src = wp_get_attachment_image_src( get_post_thumbnail_id( get_queried_object_id() ), 'large' );
		$img = $src ? $src[0] : '';
	}
	if ( '' === $img && function_exists( 'skb_img' ) ) {
		$img = skb_img( 'hero_image', 'Camera_XHS_17088886025041040g00830nd7sijp6k3048veh9175o11g6a6mv0-1.webp' );
	}
	$og = array(
		'og:site_name'   => get_bloginfo( 'name' ),
		'og:locale'      => function_exists( 'skb_ngu_og' ) ? skb_ngu_og() : 'vi_VN',
		'og:type'        => is_singular( 'post' ) ? 'article' : 'website',
		'og:title'       => $title,
		'og:description' => $d,
		'og:url'         => $url,
		'og:image'       => $img,
	);
	foreach ( $og as $k => $v ) {
		if ( '' !== (string) $v ) {
			printf( '<meta property="%s" content="%s">' . "\n", esc_attr( $k ), esc_attr( $v ) );
		}
	}
	echo '<meta name="twitter:card" content="' . ( $img ? 'summary_large_image' : 'summary' ) . '">' . "\n";
}, 2 );
