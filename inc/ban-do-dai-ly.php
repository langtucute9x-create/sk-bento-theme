<?php
/**
 * Bản đồ đại lý 3D: toạ độ tỉnh/thành, phép chiếu, gom đại lý theo tỉnh.
 */
defined( 'ABSPATH' ) || exit;

/** Tọa độ (vĩ độ, kinh độ) trung tâm tỉnh/thành và miền: b = Bắc, t = Trung, n = Nam. */
function skb_vn_tinh_toa_do() {
	return array(
		'hanoi' => array( 21.03, 105.85, 'b' ), 'hagiang' => array( 22.82, 104.98, 'b' ), 'caobang' => array( 22.67, 106.26, 'b' ),
		'backan' => array( 22.15, 105.83, 'b' ), 'tuyenquang' => array( 21.82, 105.21, 'b' ), 'laocai' => array( 22.48, 103.97, 'b' ),
		'dienbien' => array( 21.39, 103.02, 'b' ), 'laichau' => array( 22.40, 103.46, 'b' ), 'sonla' => array( 21.33, 103.91, 'b' ),
		'yenbai' => array( 21.72, 104.90, 'b' ), 'hoabinh' => array( 20.81, 105.34, 'b' ), 'thainguyen' => array( 21.59, 105.85, 'b' ),
		'langson' => array( 21.85, 106.76, 'b' ), 'quangninh' => array( 20.95, 107.08, 'b' ), 'bacgiang' => array( 21.27, 106.19, 'b' ),
		'phutho' => array( 21.32, 105.40, 'b' ), 'vinhphuc' => array( 21.31, 105.60, 'b' ), 'bacninh' => array( 21.19, 106.08, 'b' ),
		'haiduong' => array( 20.94, 106.33, 'b' ), 'haiphong' => array( 20.84, 106.69, 'b' ), 'hungyen' => array( 20.65, 106.05, 'b' ),
		'thaibinh' => array( 20.45, 106.34, 'b' ), 'hanam' => array( 20.54, 105.92, 'b' ), 'namdinh' => array( 20.43, 106.17, 'b' ),
		'ninhbinh' => array( 20.25, 105.97, 'b' ),
		'thanhhoa' => array( 19.81, 105.78, 't' ), 'nghean' => array( 18.67, 105.68, 't' ), 'hatinh' => array( 18.34, 105.91, 't' ),
		'quangbinh' => array( 17.47, 106.62, 't' ), 'quangtri' => array( 16.75, 107.19, 't' ), 'thuathienhue' => array( 16.46, 107.59, 't' ),
		'hue' => array( 16.46, 107.59, 't' ), 'danang' => array( 16.05, 108.21, 't' ), 'quangnam' => array( 15.57, 108.47, 't' ),
		'quangngai' => array( 15.12, 108.80, 't' ), 'binhdinh' => array( 13.78, 109.22, 't' ), 'phuyen' => array( 13.09, 109.30, 't' ),
		'khanhhoa' => array( 12.24, 109.19, 't' ), 'ninhthuan' => array( 11.56, 108.99, 't' ), 'binhthuan' => array( 10.93, 108.10, 't' ),
		'kontum' => array( 14.35, 108.00, 't' ), 'gialai' => array( 13.98, 108.00, 't' ), 'daklak' => array( 12.67, 108.04, 't' ),
		'daknong' => array( 12.00, 107.69, 't' ), 'lamdong' => array( 11.94, 108.44, 't' ),
		'binhphuoc' => array( 11.54, 106.90, 'n' ), 'tayninh' => array( 11.31, 106.10, 'n' ), 'binhduong' => array( 10.98, 106.65, 'n' ),
		'dongnai' => array( 10.95, 106.82, 'n' ), 'bariavungtau' => array( 10.35, 107.08, 'n' ), 'vungtau' => array( 10.35, 107.08, 'n' ),
		'hochiminh' => array( 10.78, 106.70, 'n' ), 'saigon' => array( 10.78, 106.70, 'n' ), 'hcm' => array( 10.78, 106.70, 'n' ),
		'longan' => array( 10.54, 106.41, 'n' ), 'tiengiang' => array( 10.36, 106.36, 'n' ), 'bentre' => array( 10.24, 106.38, 'n' ),
		'travinh' => array( 9.93, 106.34, 'n' ), 'vinhlong' => array( 10.25, 105.97, 'n' ), 'dongthap' => array( 10.46, 105.63, 'n' ),
		'angiang' => array( 10.39, 105.43, 'n' ), 'kiengiang' => array( 10.01, 105.08, 'n' ), 'cantho' => array( 10.03, 105.78, 'n' ),
		'haugiang' => array( 9.78, 105.47, 'n' ), 'soctrang' => array( 9.60, 105.97, 'n' ), 'baclieu' => array( 9.29, 105.72, 'n' ),
		'camau' => array( 9.18, 105.15, 'n' ),
	);
}

/** Chuẩn hoá tên tỉnh: "TP. Hồ Chí Minh" → "hochiminh". */
function skb_vn_tinh_key( $name ) {
	$s = strtolower( remove_accents( (string) $name ) );
	$s = preg_replace( '/^\s*(tp\.?|thanh pho|tinh)\s+/', '', $s );
	return preg_replace( '/[^a-z]/', '', $s );
}

/** Chiếu (vĩ độ, kinh độ) lên khung SVG — đúng phép chiếu trong inc/vn-map-data.php. */
function skb_vn_proj( $lat, $lon, $p ) {
	$merc = rad2deg( log( tan( M_PI / 4 + deg2rad( $lat ) / 2 ) ) );
	$u    = ( $lon - $p['lonMin'] ) * $p['k'] - $p['W'] / 2;
	$v    = ( $p['yTop'] - $merc ) * $p['k'] - $p['H'] / 2;
	$a    = deg2rad( $p['tilt'] );
	$s    = $p['D'] / ( $p['D'] - $v * sin( $a ) );
	return array( $u * $s + $p['ox'], $v * cos( $a ) * $s + $p['oy'], $s );
}

/** Gom đại lý đang đăng theo tỉnh: [ tên => [n, lat, lon, miền] ], lưu tạm 1 giờ. */
function skb_vn_dai_ly_theo_tinh() {
	$cached = get_transient( 'skb_dl_map' );
	if ( is_array( $cached ) ) {
		return $cached;
	}
	$ids = get_posts( array( 'post_type' => 'dai_ly', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
	$td  = skb_vn_tinh_toa_do();
	$out = array();
	foreach ( $ids as $id ) {
		$t = trim( (string) get_post_meta( $id, '_skb_tinh', true ) );
		$k = skb_vn_tinh_key( $t );
		if ( '' === $t || ! isset( $td[ $k ] ) ) {
			continue;
		}
		if ( ! isset( $out[ $t ] ) ) {
			$out[ $t ] = array( 0, $td[ $k ][0], $td[ $k ][1], $td[ $k ][2] );
		}
		$out[ $t ][0]++;
	}
	set_transient( 'skb_dl_map', $out, HOUR_IN_SECONDS );
	return $out;
}

/** Xoá bộ nhớ tạm khi sửa đại lý. */
function skb_vn_map_flush( $post_id ) {
	if ( 'dai_ly' === get_post_type( $post_id ) ) {
		delete_transient( 'skb_dl_map' );
	}
}
add_action( 'save_post', 'skb_vn_map_flush' );
add_action( 'deleted_post', 'skb_vn_map_flush' );
add_action( 'trashed_post', 'skb_vn_map_flush' );
add_action( 'updated_post_meta', function ( $mid, $post_id ) { skb_vn_map_flush( $post_id ); }, 10, 2 );

/* ---------- Toạ độ đại lý cho nút "Đại lý gần tôi" ---------- */

/** Đọc "lat,lng" từ một đường dẫn Google Maps (…@21.03,105.85…, ?q=21.03,105.85, !3d21.03!4d105.85) */
function skb_dl_toa_do_tu_link( $url ) {
	$u = rawurldecode( (string) $url );
	if ( preg_match( '/!3d(-?\d{1,2}\.\d+)!4d(-?\d{1,3}\.\d+)/', $u, $m ) || preg_match( '/@(-?\d{1,2}\.\d+),(-?\d{1,3}\.\d+)/', $u, $m ) || preg_match( '/[?&](?:q|query|ll|center|destination)=(-?\d{1,2}\.\d+),\s*(-?\d{1,3}\.\d+)/', $u, $m ) ) {
		$lat = (float) $m[1];
		$lng = (float) $m[2];
		if ( $lat > 7 && $lat < 24 && $lng > 101 && $lng < 118 ) {
			return array( $lat, $lng );
		}
	}
	return null;
}

/** Mở link rút gọn (maps.app.goo.gl, goo.gl, share.google) để lấy đường dẫn đầy đủ có toạ độ */
function skb_dl_mo_link( $url ) {
	$seen = array( (string) $url );
	for ( $i = 0; $i < 6; $i++ ) {
		$r = wp_remote_get( $url, array( 'redirection' => 0, 'timeout' => 12, 'user-agent' => 'Mozilla/5.0 (compatible; SKBentoTheme/1.0; +' . home_url( '/' ) . ')' ) );
		if ( is_wp_error( $r ) ) {
			break;
		}
		$loc = wp_remote_retrieve_header( $r, 'location' );
		if ( ! $loc ) {
			// trang Google đôi khi nhúng toạ độ trong HTML
			$html = (string) wp_remote_retrieve_body( $r );
			if ( preg_match( '/\[null,null,(-?\d{1,2}\.\d{3,}),(-?\d{1,3}\.\d{3,})\]/', $html, $m ) ) {
				$seen[] = '@' . $m[1] . ',' . $m[2];
			}
			break;
		}
		$url    = is_array( $loc ) ? end( $loc ) : $loc;
		$seen[] = $url;
	}
	return $seen;
}

/** Tìm toạ độ theo địa chỉ (OpenStreetMap Nominatim, tối đa 1 lần/giây) */
function skb_dl_toa_do_dia_chi( $addr ) {
	$addr = trim( (string) $addr );
	if ( '' === $addr ) {
		return null;
	}
	usleep( 1100000 ); // quy định của Nominatim: tối đa 1 lần mỗi giây
	$r = wp_remote_get(
		add_query_arg( array( 'format' => 'json', 'limit' => 1, 'countrycodes' => 'vn', 'q' => $addr ), 'https://nominatim.openstreetmap.org/search' ),
		array( 'timeout' => 15, 'user-agent' => 'SKBentoTheme/1.0 (+' . home_url( '/' ) . ')', 'headers' => array( 'Accept-Language' => 'vi' ) )
	);
	if ( is_wp_error( $r ) ) {
		return null;
	}
	$j = json_decode( wp_remote_retrieve_body( $r ), true );
	if ( ! empty( $j[0]['lat'] ) ) {
		return array( (float) $j[0]['lat'], (float) $j[0]['lon'] );
	}
	return null;
}

/**
 * Lấy và lưu toạ độ một đại lý. Thứ tự: toạ độ nhập tay → link chỉ đường → địa chỉ → giữa tỉnh.
 * Lưu vào _skb_toa_do_tu_dong ("lat,lng") và _skb_toa_do_nguon (tay | link | dia_chi | tinh).
 */
function skb_dl_lay_toa_do( $id, $mang = true ) {
	$tay = trim( (string) get_post_meta( $id, '_skb_toa_do', true ) );
	if ( preg_match( '/^(-?\d{1,2}(?:\.\d+)?)\s*[,; ]\s*(-?\d{1,3}(?:\.\d+)?)$/', $tay, $m ) ) {
		update_post_meta( $id, '_skb_toa_do_tu_dong', $m[1] . ',' . $m[2] );
		update_post_meta( $id, '_skb_toa_do_nguon', 'tay' );
		return 'tay';
	}
	$link = (string) get_post_meta( $id, '_skb_ban_do', true );
	$ll   = $link ? skb_dl_toa_do_tu_link( $link ) : null;
	if ( ! $ll && $link && $mang && preg_match( '#^https?://(maps\.app\.goo\.gl|goo\.gl|share\.google|g\.co|maps\.google\.|www\.google\.)#i', $link ) ) {
		foreach ( skb_dl_mo_link( $link ) as $u ) {
			$ll = skb_dl_toa_do_tu_link( $u );
			if ( $ll ) {
				break;
			}
		}
	}
	$nguon = 'link';
	if ( ! $ll && $mang ) {
		$addr = (string) get_post_meta( $id, '_skb_dia_chi', true );
		$tinh = (string) get_post_meta( $id, '_skb_tinh', true );
		$full = $addr . ( $tinh && false === stripos( $addr, $tinh ) ? ', ' . $tinh : '' );
		$ll   = skb_dl_toa_do_dia_chi( $full );
		if ( ! $ll ) {
			// OpenStreetMap ít số nhà Việt Nam: thử lại theo tên đường / phường (bỏ số nhà, "Số", "Lô", "Kiot"…)
			$bot = preg_replace( '/^\s*(?:(?:số|so|sn|lô|lo|kiot|ki-ốt|ngõ|ngách|hẻm)\s*)?[\d\/\-a-z]*\d[\d\/\-a-z]*\s*,?\s*/iu', '', $full );
			if ( $bot && $bot !== $full ) {
				$ll = skb_dl_toa_do_dia_chi( $bot );
			}
		}
		$nguon = 'dia_chi';
	}
	if ( ! $ll ) {
		$td = skb_vn_tinh_toa_do();
		$k  = skb_vn_tinh_key( (string) get_post_meta( $id, '_skb_tinh', true ) );
		if ( isset( $td[ $k ] ) ) {
			$ll    = array( $td[ $k ][0], $td[ $k ][1] );
			$nguon = 'tinh';
		}
	}
	if ( ! $ll ) {
		delete_post_meta( $id, '_skb_toa_do_tu_dong' );
		delete_post_meta( $id, '_skb_toa_do_nguon' );
		return '';
	}
	update_post_meta( $id, '_skb_toa_do_tu_dong', round( $ll[0], 6 ) . ',' . round( $ll[1], 6 ) );
	update_post_meta( $id, '_skb_toa_do_nguon', $nguon );
	return $nguon;
}

/* Sửa đại lý → lấy lại toạ độ sau khi lưu (chạy nền, không bắt người sửa chờ) */
add_action( 'save_post_dai_ly', function ( $id ) {
	if ( wp_is_post_revision( $id ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) ) {
		return;
	}
	wp_schedule_single_event( time() + 5, 'skb_dl_toa_do_mot', array( (int) $id ) );
}, 30 );
add_action( 'skb_dl_toa_do_mot', function ( $id ) {
	skb_dl_lay_toa_do( (int) $id );
} );

/** "lat,lng" của đại lý (rỗng nếu chưa có) */
function skb_dl_toa_do( $id ) {
	return (string) get_post_meta( $id, '_skb_toa_do_tu_dong', true );
}

/* Công cụ: lấy toạ độ cho mọi đại lý (nút ở Công cụ › Chuyển sản phẩm web cũ) */
add_action( 'wp_ajax_skb_dl_toa_do_list', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'skb_mig', '_n', false ) ) {
		wp_send_json_error( 'Không có quyền.' );
	}
	wp_send_json_success( get_posts( array( 'post_type' => 'dai_ly', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) ) );
} );
add_action( 'wp_ajax_skb_dl_toa_do_one', function () {
	if ( ! current_user_can( 'manage_options' ) || ! check_ajax_referer( 'skb_mig', '_n', false ) ) {
		wp_send_json_error( 'Không có quyền.' );
	}
	$id    = isset( $_POST['id'] ) ? absint( $_POST['id'] ) : 0; // phpcs:ignore
	$nguon = skb_dl_lay_toa_do( $id );
	if ( function_exists( 'skb_cache_xoa' ) ) {
		skb_cache_xoa();
	}
	wp_send_json_success( array( 'title' => get_the_title( $id ), 'nguon' => $nguon, 'toa_do' => skb_dl_toa_do( $id ) ) );
} );
