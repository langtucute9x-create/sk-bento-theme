<?php
/**
 * Dữ liệu cấu trúc (JSON-LD) trong <head>:
 * - Trang mẫu xe: Product + AggregateOffer, giá lấy đúng hàm hiện giá trên trang (skb_km_gia ở inc/khuyen-mai.php),
 *   nên giá khai báo luôn khớp giá đang hiện: trong chương trình = giá khuyến mại, ngoài chương trình (bản Việt) = giá niêm yết,
 *   bản nước ngoài = giá bán như cũ. Gói "Liên hệ" (giá 0) không khai báo. priceValidUntil chỉ in khi đang chương trình.
 *   Chưa in availability (chờ chủ chốt), không in aggregateRating/review (chưa có đánh giá thật).
 * - Trang mẫu xe: BreadcrumbList (Trang chủ › Mẫu xe › Hãng › Xe).
 * - Trang chủ: Organization.
 */
defined( 'ABSPATH' ) || exit;

function skb_ld_in( $ld ) {
	echo '<script type="application/ld+json">' . wp_json_encode( $ld, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore
}

/** Hãng gốc của mẫu xe (mục không có cha), null nếu chưa gắn */
function skb_ld_hang( $post_id ) {
	$t = get_the_terms( $post_id, 'hang_xe' );
	if ( ! $t || is_wp_error( $t ) ) {
		return null;
	}
	foreach ( $t as $b ) {
		if ( ! $b->parent ) {
			return $b;
		}
	}
	return $t[0];
}

/** Mảng Product cho một mẫu xe (dùng được ở kiểm thử) */
function skb_ld_san_pham( $post_id ) {
	$ngoai = function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai();
	$url   = get_permalink( $post_id );
	$ld    = array(
		'@context' => 'https://schema.org',
		'@type'    => 'Product',
		'@id'      => $url . '#product',
		'name'     => $ngoai ? skb_ngu_tieu_de_xe( $post_id ) : get_the_title( $post_id ),
		'url'      => $url,
		'brand'    => array( '@type' => 'Brand', 'name' => 'SK Ambient Light' ),
		'category' => 'Đèn LED nội thất ô tô',
	);
	$mo_ta = function_exists( 'skb_seo_mo_ta' ) ? skb_seo_mo_ta() : '';
	if ( '' !== $mo_ta ) {
		$ld['description'] = $mo_ta;
	}
	$anh = array();
	$ids = array_filter( array_map( 'intval', explode( ',', (string) get_post_meta( $post_id, '_skb_gallery', true ) ) ) );
	if ( has_post_thumbnail( $post_id ) ) {
		array_unshift( $ids, (int) get_post_thumbnail_id( $post_id ) );
	}
	foreach ( array_slice( array_unique( $ids ), 0, 5 ) as $aid ) {
		$s = wp_get_attachment_image_src( $aid, 'large' );
		if ( $s ) {
			$anh[] = $s[0];
		}
	}
	if ( $anh ) {
		$ld['image'] = $anh;
	}

	$km     = function_exists( 'skb_km_dang_chay' ) ? skb_km_dang_chay() : null;
	$han    = $km ? wp_date( 'Y-m-d', (int) $km['kt'], new DateTimeZone( 'Asia/Ho_Chi_Minh' ) ) : '';
	$offers = array();
	$gias   = array();
	foreach ( skb_goi( $post_id ) as $i => $g ) {
		$p = skb_km_gia( $g['gia'], $g['goc'] );
		if ( $p['ban'] <= 0 ) {
			continue;
		}
		$o = array(
			'@type'         => 'Offer',
			'name'          => $ngoai && function_exists( 'skb_ngu_ten_goi' ) ? skb_ngu_ten_goi( $g['ten'] ) : $g['ten'],
			'price'         => (int) $p['ban'],
			'priceCurrency' => 'VND',
			'url'           => add_query_arg( 'goi', $i + 1, $url ),
		);
		if ( $han ) {
			$o['priceValidUntil'] = $han;
		}
		$offers[] = $o;
		$gias[]   = (int) $p['ban'];
	}
	if ( ! $offers ) { // mẫu xe không có gói: một giá ở _skb_gia
		$p = skb_km_gia( (int) get_post_meta( $post_id, '_skb_gia', true ), (int) get_post_meta( $post_id, '_skb_gia_goc', true ) );
		if ( $p['ban'] > 0 ) {
			$o = array( '@type' => 'Offer', 'price' => (int) $p['ban'], 'priceCurrency' => 'VND', 'url' => $url );
			if ( $han ) {
				$o['priceValidUntil'] = $han;
			}
			$ld['offers'] = $o;
		}
		return $ld;
	}
	$ag = array(
		'@type'         => 'AggregateOffer',
		'priceCurrency' => 'VND',
		'lowPrice'      => min( $gias ),
		'highPrice'     => max( $gias ),
		'offerCount'    => count( $offers ),
		'offers'        => $offers,
	);
	if ( $han ) {
		$ag['priceValidUntil'] = $han;
	}
	$ld['offers'] = $ag;
	return $ld;
}

/** BreadcrumbList cho mẫu xe */
function skb_ld_duong_dan( $post_id ) {
	$ngoai = function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai();
	$ds    = array(
		array( skb__( 'Trang chủ' ), home_url( '/' ) ),
		array( skb__( 'Chọn dòng xe' ), get_post_type_archive_link( 'mau_xe' ) ),
	);
	$h = skb_ld_hang( $post_id );
	if ( $h ) {
		$l = get_term_link( $h );
		if ( ! is_wp_error( $l ) ) {
			$ds[] = array( skb__( $h->name ), $l );
		}
	}
	$ds[]  = array( $ngoai ? skb_ngu_tieu_de_xe( $post_id ) : get_the_title( $post_id ), get_permalink( $post_id ) );
	$items = array();
	foreach ( $ds as $i => $d ) {
		$items[] = array( '@type' => 'ListItem', 'position' => $i + 1, 'name' => wp_strip_all_tags( $d[0] ), 'item' => $d[1] );
	}
	return array( '@context' => 'https://schema.org', '@type' => 'BreadcrumbList', 'itemListElement' => $items );
}

/** Organization (trang chủ mọi ngôn ngữ) */
function skb_ld_to_chuc() {
	$goc     = trailingslashit( function_exists( 'skb_ngu_goc' ) ? skb_ngu_goc() : home_url() );
	$logo_id = (int) get_theme_mod( 'custom_logo' );
	$logo    = $logo_id ? wp_get_attachment_image_url( $logo_id, 'full' ) : '';
	if ( ! $logo ) {
		$logo = get_template_directory_uri() . '/assets/img/logo-full-png-1.webp';
	}
	return array(
		'@context'     => 'https://schema.org',
		'@type'        => 'Organization',
		'@id'          => $goc . '#organization',
		'name'         => 'SK Ambient Light',
		'legalName'    => 'SON DUONG ACCESSORIES COMPANY LIMITED',
		'url'          => $goc,
		'logo'         => $logo,
		'description'  => 'LED nội thất ô tô ốp thay zin theo từng dòng xe, không cắt dây, đổi màu qua app. Bảo hành 24 tháng.',
		'telephone'    => '+84868055555',
		'contactPoint' => array(
			'@type'             => 'ContactPoint',
			'telephone'         => '+84868055555',
			'contactType'       => 'customer service',
			'areaServed'        => 'VN',
			'availableLanguage' => 'Vietnamese',
		),
		'sameAs'       => array(
			'https://www.facebook.com/SK.Ambient.Light.Luxury',
			'https://www.tiktok.com/@sk.ambient.light.luxury',
			'https://www.youtube.com/@SKAmbientLightLuxury',
		),
	);
}

add_action( 'wp_head', function () {
	if ( is_singular( 'mau_xe' ) ) {
		$id = get_queried_object_id();
		skb_ld_in( skb_ld_san_pham( $id ) );
		skb_ld_in( skb_ld_duong_dan( $id ) );
	} elseif ( is_front_page() ) {
		skb_ld_in( skb_ld_to_chuc() );
	}
}, 3 );
