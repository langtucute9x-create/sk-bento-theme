<?php
/**
 * Chương trình khuyến mại (chỉ bản tiếng Việt).
 *
 * - Loại nội dung `khuyen_mai` (Biên tập viên sửa được, có REST: /wp-json/wp/v2/khuyen-mai).
 *   Trường: ảnh máy tính `_skb_km_anh_pc`, ảnh điện thoại `_skb_km_anh_mobile` (tuỳ chọn),
 *   giờ bắt đầu `_skb_km_bat_dau`, giờ kết thúc `_skb_km_ket_thuc` (dạng "2026-12-01 00:00:00", giờ Việt Nam),
 *   công tắc `_skb_km_bat`. Chương trình chạy khi: đã đăng (publish) + bật + đang trong khoảng giờ.
 * - Giá: mỗi gói trong `_skb_goi` có `gia` (giá bán = giá khuyến mại) và `goc` (giá niêm yết).
 *   Đang có chương trình: giá khuyến mại to, giá niêm yết gạch ngang, nhãn -n%.
 *   Ngoài chương trình: chỉ giá niêm yết. Gói một giá: luôn giá đó. Bản nước ngoài: giữ như cũ (hai giá, nhãn %).
 * - Bộ nhớ đệm: hạn lưu trang không vượt mốc giờ kế tiếp; wp-cron xoá bộ nhớ đệm đúng giờ bắt đầu và kết thúc;
 *   JS phía khách là lớp cuối (quá giờ kết thúc thì ẩn banner, nhãn %, đổi giá về niêm yết).
 * - Xem thử (chỉ người đăng nhập có quyền sửa bài): thêm ?km_thu=2026-12-01T08:00 vào đường dẫn bất kỳ.
 *   Chế độ này tính cả chương trình còn nháp hoặc đang tắt, và không lưu bộ nhớ đệm.
 */
defined( 'ABSPATH' ) || exit;

const SKB_KM_TZ = 'Asia/Ho_Chi_Minh';

/* ---------- Loại nội dung và trường ---------- */
add_action( 'init', function () {
	register_post_type( 'khuyen_mai', array(
		'labels'              => array(
			'name'          => 'Khuyến mại',
			'singular_name' => 'Chương trình khuyến mại',
			'add_new'       => 'Thêm chương trình',
			'add_new_item'  => 'Thêm chương trình khuyến mại',
			'edit_item'     => 'Sửa chương trình khuyến mại',
			'all_items'     => 'Tất cả chương trình',
			'menu_name'     => 'Khuyến mại',
		),
		'public'              => false,
		'publicly_queryable'  => false,
		'exclude_from_search' => true,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_rest'        => true,
		'rest_base'           => 'khuyen-mai',
		'menu_position'       => 24,
		'menu_icon'           => 'dashicons-megaphone',
		'capability_type'     => 'post',
		'map_meta_cap'        => true,
		'supports'            => array( 'title', 'custom-fields' ),
		'rewrite'             => false,
		'query_var'           => false,
		'has_archive'         => false,
	) );

	$auth = function ( $allowed, $key, $post_id ) {
		return current_user_can( 'edit_post', $post_id );
	};
	$truong = array(
		'_skb_km_anh_pc'     => array( 'integer', 0, 'absint', 'ID ảnh banner máy tính (1600x500)' ),
		'_skb_km_anh_mobile' => array( 'integer', 0, 'absint', 'ID ảnh banner điện thoại (800x600), tuỳ chọn' ),
		'_skb_km_bat_dau'    => array( 'string', '', 'skb_km_chuan_gio', 'Giờ bắt đầu, giờ Việt Nam, dạng 2026-12-01 00:00:00' ),
		'_skb_km_ket_thuc'   => array( 'string', '', 'skb_km_chuan_gio', 'Giờ kết thúc, giờ Việt Nam, dạng 2026-12-25 23:59:59' ),
		'_skb_km_bat'        => array( 'boolean', false, 'rest_sanitize_boolean', 'Bật chương trình' ),
		'_skb_km_qua'        => array( 'string', '', 'skb_km_chuan_qua', 'Quà tặng hoặc ưu đãi riêng của đợt này, mỗi dòng một ý, tối đa 6 dòng' ),
	);
	foreach ( $truong as $k => $t ) {
		register_post_meta( 'khuyen_mai', $k, array(
			'type'              => $t[0],
			'default'           => $t[1],
			'single'            => true,
			'sanitize_callback' => $t[2],
			'description'       => $t[3],
			'show_in_rest'      => true,
			'auth_callback'     => $auth,
		) );
	}
} );

/* ---------- Giờ (luôn theo giờ Việt Nam) ---------- */
function skb_km_tz() {
	static $tz = null;
	return $tz ? $tz : ( $tz = new DateTimeZone( SKB_KM_TZ ) );
}

/** "2026-12-01T00:00", "2026-12-01 00:00", "2026-12-01" → "2026-12-01 00:00:00"; sai dạng → "" */
function skb_km_chuan_gio( $v ) {
	$v = trim( str_replace( 'T', ' ', (string) $v ) );
	if ( preg_match( '/^\d{4}-\d{2}-\d{2}$/', $v ) ) {
		$v .= ' 00:00:00';
	} elseif ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $v ) ) {
		$v .= ':00';
	}
	$d = DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $v, skb_km_tz() );
	return ( $d && $d->format( 'Y-m-d H:i:s' ) === $v ) ? $v : '';
}

/** Chuỗi giờ Việt Nam → mốc Unix (0 nếu trống/sai) */
function skb_km_ts( $s ) {
	$s = skb_km_chuan_gio( $s );
	if ( '' === $s ) {
		return 0;
	}
	return DateTimeImmutable::createFromFormat( '!Y-m-d H:i:s', $s, skb_km_tz() )->getTimestamp();
}

/** Giờ xem thử từ ?km_thu= (chỉ người đăng nhập có quyền sửa bài); 0 = không xem thử */
function skb_km_thu() {
	static $t = null;
	if ( null !== $t ) {
		return $t;
	}
	$t = 0;
	if ( isset( $_GET['km_thu'] ) && function_exists( 'is_user_logged_in' ) && is_user_logged_in() && current_user_can( 'edit_posts' ) ) { // phpcs:ignore
		$t = skb_km_ts( sanitize_text_field( wp_unslash( $_GET['km_thu'] ) ) ); // phpcs:ignore
		if ( $t && ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', true );
		}
	}
	return $t;
}

/** Quà của đợt: tối đa 6 dòng, mỗi dòng tối đa 120 ký tự, bỏ dòng trống và thẻ HTML */
function skb_km_chuan_qua( $v ) {
	$dong = preg_split( '/\r\n|\r|\n/', (string) $v );
	$out  = array();
	foreach ( $dong as $d ) {
		$d = trim( wp_strip_all_tags( (string) $d ) );
		if ( '' === $d ) {
			continue;
		}
		$out[] = function_exists( 'mb_substr' ) ? mb_substr( $d, 0, 120 ) : substr( $d, 0, 120 );
		if ( count( $out ) >= 6 ) {
			break;
		}
	}
	return implode( "\n", $out );
}

/** Giờ hiện tại dùng để so chương trình. Bộ lọc `skb_km_bay_gio` cho phép giả giờ khi kiểm thử. */
function skb_km_bay_gio() {
	$thu = skb_km_thu();
	return (int) apply_filters( 'skb_km_bay_gio', $thu ? $thu : time() );
}

/* ---------- Danh sách chương trình (lưu tạm, tự xoá khi sửa) ---------- */
function skb_km_ds() {
	$c = get_transient( 'skb_km_ds' );
	if ( is_array( $c ) ) {
		return $c;
	}
	$out = array();
	$q   = get_posts( array(
		'post_type'        => 'khuyen_mai',
		'post_status'      => array( 'publish', 'draft', 'pending', 'future', 'private' ),
		'numberposts'      => 50,
		'orderby'          => 'date',
		'order'            => 'DESC',
		'suppress_filters' => true,
	) );
	foreach ( $q as $p ) {
		$bd = skb_km_ts( get_post_meta( $p->ID, '_skb_km_bat_dau', true ) );
		$kt = skb_km_ts( get_post_meta( $p->ID, '_skb_km_ket_thuc', true ) );
		if ( ! $bd || ! $kt || $kt <= $bd ) {
			continue;
		}
		$out[] = array(
			'id'  => (int) $p->ID,
			'st'  => $p->post_status,
			'bat' => (bool) get_post_meta( $p->ID, '_skb_km_bat', true ),
			'bd'  => $bd,
			'kt'  => $kt,
			'pc'  => (int) get_post_meta( $p->ID, '_skb_km_anh_pc', true ),
			'mb'  => (int) get_post_meta( $p->ID, '_skb_km_anh_mobile', true ),
			'qua' => skb_km_chuan_qua( get_post_meta( $p->ID, '_skb_km_qua', true ) ),
		);
	}
	set_transient( 'skb_km_ds', $out, DAY_IN_SECONDS );
	return $out;
}

/** Chương trình có hiệu lực thật (đã đăng + bật) */
function skb_km_hop_le( $k ) {
	return 'publish' === $k['st'] && $k['bat'];
}

/** Chương trình chạy tại mốc $now (xem thử thì tính cả nháp/tắt) */
function skb_km_tai( $now, $ca_nhap = false ) {
	foreach ( skb_km_ds() as $k ) {
		if ( ! $ca_nhap && ! skb_km_hop_le( $k ) ) {
			continue;
		}
		if ( $now >= $k['bd'] && $now <= $k['kt'] ) {
			return $k;
		}
	}
	return null;
}

/** Chương trình đang chạy cho trang hiện tại; bản nước ngoài luôn null */
function skb_km_dang_chay() {
	if ( function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai() ) {
		return null;
	}
	return skb_km_tai( skb_km_bay_gio(), (bool) skb_km_thu() );
}

/** Số giây tới mốc bắt đầu/kết thúc kế tiếp của chương trình hợp lệ (0 = không có mốc) */
function skb_km_giay_toi_moc( $now = null ) {
	$now = null === $now ? time() : (int) $now;
	$gan = 0;
	foreach ( skb_km_ds() as $k ) {
		if ( ! skb_km_hop_le( $k ) ) {
			continue;
		}
		foreach ( array( $k['bd'], $k['kt'] + 1 ) as $m ) {
			if ( $m > $now && ( ! $gan || $m < $gan ) ) {
				$gan = $m;
			}
		}
	}
	return $gan ? $gan - $now : 0;
}

/* ---------- Giá ---------- */

/**
 * Giá hiển thị của một gói.
 * $km: null = theo chương trình đang chạy; true/false = ép trong/ngoài chương trình (bản Việt).
 * Trả về: ban (giá hiện to), goc (giá gạch, 0 = không gạch), off (% giảm, 0 = không nhãn), niem (giá ngoài chương trình).
 */
function skb_km_gia( $gia, $goc, $km = null ) {
	$gia = (int) $gia;
	$goc = (int) $goc;
	if ( ! ( $goc > $gia && $gia > 0 ) ) {
		return array( 'ban' => $gia, 'goc' => 0, 'off' => 0, 'niem' => $gia );
	}
	$off   = (int) round( 100 - $gia * 100 / $goc );
	$ngoai = function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai();
	if ( null === $km ) {
		$km = (bool) skb_km_dang_chay();
	}
	if ( $ngoai || $km ) {
		return array( 'ban' => $gia, 'goc' => $goc, 'off' => $off, 'niem' => $goc );
	}
	return array( 'ban' => $goc, 'goc' => 0, 'off' => 0, 'niem' => $goc );
}

/** Giá "từ" của mẫu xe = gói có giá hiển thị thấp nhất (cùng cấu trúc skb_km_gia), null nếu chưa có giá */
function skb_km_tu( $post_id, $km = null ) {
	$best = null;
	foreach ( function_exists( 'skb_goi' ) ? skb_goi( $post_id ) : array() as $g ) {
		$p = skb_km_gia( $g['gia'], $g['goc'], $km );
		if ( $p['ban'] > 0 && ( ! $best || $p['ban'] < $best['ban'] ) ) {
			$best = $p;
		}
	}
	if ( ! $best ) {
		$v = (int) get_post_meta( $post_id, '_skb_gia', true );
		$o = (int) get_post_meta( $post_id, '_skb_gia_goc', true );
		$best = $v > 0 ? skb_km_gia( $v, $o, $km ) : null;
	}
	return $best;
}

/** 8100000 → "8,1 triệu"; 8150000 → "8,15 triệu" (không làm tròn xuống, tránh ghi thấp hơn giá thật) */
function skb_km_trieu( $v ) {
	$s = number_format( ceil( (int) $v / 10000 ) / 100, 2, ',', '' );
	return rtrim( rtrim( $s, '0' ), ',' ) . ' triệu';
}

/** Thuộc tính data-km-niem="…" để JS đổi về giá niêm yết khi hết giờ (chỉ khi đang chạy và chữ khác nhau) */
function skb_km_niem_attr( $hien, $niem ) {
	if ( ! skb_km_dang_chay() || (string) $hien === (string) $niem ) {
		return '';
	}
	return ' data-km-niem="' . esc_attr( $niem ) . '"';
}

/** Dòng giá trên thẻ mẫu xe bản Việt: "Từ 7.200.000đ 9.000.000đ -20%" */
function skb_km_gia_the( $post_id ) {
	$p = skb_km_tu( $post_id );
	if ( ! $p ) {
		return '';
	}
	$hien = skb_tien( $p['ban'] );
	$n    = skb_km_tu( $post_id, false );
	$html = '<p class="mx-card-price">Từ <strong' . skb_km_niem_attr( $hien, $n ? skb_tien( $n['ban'] ) : $hien ) . '>' . esc_html( $hien ) . '</strong>';
	if ( $p['goc'] ) {
		$html .= ' <s data-km-an>' . esc_html( skb_tien( $p['goc'] ) ) . '</s>';
	}
	if ( $p['off'] ) {
		$html .= ' <em class="km-off" data-km-an>-' . (int) $p['off'] . '%</em>';
	}
	return $html . '</p>';
}

/* ---------- Bảng mẫu xe có giảm giá (lưu tạm) ---------- */
function skb_km_bang_xe() {
	$c = get_transient( 'skb_km_xe' );
	if ( is_array( $c ) ) {
		return $c;
	}
	$out = array();
	$q   = get_posts( array( 'post_type' => 'mau_xe', 'post_status' => 'publish', 'numberposts' => -1, 'orderby' => 'menu_order title', 'order' => 'ASC', 'fields' => 'ids' ) );
	foreach ( $q as $id ) {
		$bo = 0;
		$mi = 0;
		$of = 0;
		foreach ( skb_goi( $id ) as $g ) {
			if ( ! ( $g['goc'] > $g['gia'] && $g['gia'] > 0 ) ) {
				continue;
			}
			$mi = $mi ? min( $mi, $g['gia'] ) : $g['gia'];
			$of = max( $of, (int) round( 100 - $g['gia'] * 100 / $g['goc'] ) );
			if ( function_exists( 'skb_ngu_la_bo' ) && skb_ngu_la_bo( $g['ten'] ) ) {
				$bo = $bo ? min( $bo, $g['gia'] ) : $g['gia'];
			}
		}
		if ( $mi ) {
			$out[] = array( 'id' => (int) $id, 'bo' => $bo, 'min' => $mi, 'off' => $of );
		}
	}
	set_transient( 'skb_km_xe', $out, DAY_IN_SECONDS );
	return $out;
}

/** Giá khuyến mại thấp nhất cho thanh trên cùng: ưu tiên bộ từ 10 chi tiết, không có thì mọi gói */
function skb_km_gia_thap_nhat() {
	$bo = array_filter( wp_list_pluck( skb_km_bang_xe(), 'bo' ) );
	if ( $bo ) {
		return min( $bo );
	}
	$mi = array_filter( wp_list_pluck( skb_km_bang_xe(), 'min' ) );
	return $mi ? min( $mi ) : 0;
}

/* ---------- Làm mới khi sửa chương trình / mẫu xe ---------- */
function skb_km_xoa_tam() {
	delete_transient( 'skb_km_ds' );
	delete_transient( 'skb_km_xe' );
}

/** Đặt lại hai sự kiện wp-cron (giờ bắt đầu, giờ kết thúc) cho mọi chương trình hợp lệ */
function skb_km_dat_lich() {
	if ( function_exists( 'wp_unschedule_hook' ) ) {
		wp_unschedule_hook( 'skb_km_moc' );
	}
	$now = time();
	foreach ( skb_km_ds() as $k ) {
		if ( ! skb_km_hop_le( $k ) ) {
			continue;
		}
		foreach ( array( $k['bd'], $k['kt'] + 1 ) as $m ) {
			if ( $m > $now ) {
				wp_schedule_single_event( $m, 'skb_km_moc', array( $m ) );
			}
		}
	}
}

/* Gom nhiều lần sửa trong một yêu cầu (lưu bài + từng trường meta) thành một lần làm mới */
function skb_km_can_lam_moi() {
	static $hen = false;
	skb_km_xoa_tam();
	if ( $hen ) {
		return;
	}
	$hen = true;
	add_action( 'shutdown', function () {
		skb_km_xoa_tam();
		skb_km_dat_lich();
		if ( function_exists( 'skb_cache_xoa' ) ) {
			skb_cache_xoa();
		}
		// đã gửi header thì skb_cache_xoa() tự hẹn xoá ở lần tải kế tiếp
	} );
}
add_action( 'save_post_khuyen_mai', 'skb_km_can_lam_moi' );
add_action( 'rest_after_insert_khuyen_mai', 'skb_km_can_lam_moi' );
add_action( 'deleted_post', function ( $id ) {
	skb_km_xoa_tam();
} );
add_action( 'trashed_post', function ( $id ) {
	if ( 'khuyen_mai' === get_post_type( $id ) ) {
		skb_km_can_lam_moi();
	}
} );
add_action( 'transition_post_status', function ( $new, $old, $post ) {
	if ( $post && 'khuyen_mai' === $post->post_type && $new !== $old ) {
		skb_km_can_lam_moi();
	}
}, 10, 3 );
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $skb_km_h ) {
	add_action( $skb_km_h, function ( $mid, $pid, $key ) {
		if ( 0 === strpos( (string) $key, '_skb_km_' ) ) {
			skb_km_can_lam_moi();
		} elseif ( in_array( $key, array( '_skb_goi', '_skb_gia', '_skb_gia_goc' ), true ) ) {
			delete_transient( 'skb_km_xe' );
		}
	}, 10, 3 );
}
add_action( 'save_post_mau_xe', function () {
	delete_transient( 'skb_km_xe' );
} );

/* Đúng giờ bắt đầu / kết thúc: xoá bộ nhớ đệm (cron chạy theo lượt truy cập nên có thể trễ vài phút) */
add_action( 'skb_km_moc', function () {
	skb_km_xoa_tam();
	update_option( 'skb_cache_can_xoa', 1, false );
	if ( function_exists( 'skb_cache_xoa' ) ) {
		skb_cache_xoa();
	}
} );

/** Hạn lưu trang tối đa (giây) để bản lưu không vượt mốc bắt đầu/kết thúc; 0 = không giới hạn */
function skb_km_han_luu() {
	$s = skb_km_giay_toi_moc();
	return $s ? max( 60, $s ) : 0;
}

/* ---------- Đầu trang: lớp CSS, dữ liệu cho đồng hồ ---------- */
add_filter( 'body_class', function ( $c ) {
	if ( skb_km_dang_chay() ) {
		$c[] = 'km-co';
	}
	return $c;
} );

add_action( 'wp_head', function () {
	$k = skb_km_dang_chay();
	if ( ! $k ) {
		return;
	}
	$lech = skb_km_thu() ? ( skb_km_bay_gio() - time() ) * 1000 : 0;
	$cfg  = array(
		'bd'   => $k['bd'] * 1000,
		'kt'   => ( $k['kt'] + 1 ) * 1000,
		'lech' => $lech,
		'anh'  => (string) $k['pc'],
	);
	// đã đóng thanh ưu đãi của đúng ảnh này → ẩn ngay trước khi vẽ (không giật trang)
	echo '<script>window.SKB_KM=' . wp_json_encode( $cfg ) . ';try{if(localStorage.getItem("skb_km_dong")===SKB_KM.anh)document.documentElement.classList.add("km-dong");if(Date.now()+SKB_KM.lech>=SKB_KM.kt)document.documentElement.classList.add("km-het")}catch(e){}</script>' . "\n"; // phpcs:ignore
}, 2 );

/* /mau-xe/?uu-dai=1: không lập chỉ mục (trang gốc là /mau-xe/) */
add_filter( 'wp_robots', function ( $r ) {
	if ( isset( $_GET['uu-dai'] ) && is_post_type_archive( 'mau_xe' ) ) { // phpcs:ignore
		$r['noindex'] = true;
		$r['follow']  = true;
		unset( $r['index'] );
	}
	return $r;
} );

function skb_km_url_uu_dai() {
	return add_query_arg( 'uu-dai', '1', get_post_type_archive_link( 'mau_xe' ) );
}

/* ---------- Hiển thị ---------- */

/** Đồng hồ: $kieu 'gon' (thanh trên cùng, "4 ngày 12:03:45") hoặc 'o' (ô ngày/giờ/phút/giây) */
function skb_km_dong_ho( $k, $kieu = 'gon' ) {
	$con = max( 0, $k['kt'] + 1 - skb_km_bay_gio() );
	$d   = intdiv( $con, 86400 );
	$h   = intdiv( $con % 86400, 3600 );
	$m   = intdiv( $con % 3600, 60 );
	$s   = $con % 60;
	if ( 'o' === $kieu ) {
		$o = '';
		foreach ( array( array( $d, 'ngày' ), array( $h, 'giờ' ), array( $m, 'phút' ), array( $s, 'giây' ) ) as $i => $x ) {
			$o .= '<span class="km-o"><b data-km-o="' . (int) $i . '">' . esc_html( $i ? sprintf( '%02d', $x[0] ) : (string) $x[0] ) . '</b><small>' . esc_html( $x[1] ) . '</small></span>';
		}
		return '<span class="km-dem km-dem-o" data-km-dem="o" role="timer" aria-live="off">' . $o . '</span>';
	}
	$txt = ( $d ? $d . ' ngày ' : '' ) . sprintf( '%02d:%02d:%02d', $h, $m, $s );
	return '<span class="km-dem" data-km-dem="gon" role="timer" aria-live="off">' . esc_html( $txt ) . '</span>';
}

/** Còn dưới 24 giờ lúc dựng trang? */
function skb_km_sap_het( $k ) {
	return ( $k['kt'] + 1 - skb_km_bay_gio() ) < DAY_IN_SECONDS;
}

/** Thanh trên cùng, mọi trang (gọi trong header.php) */
function skb_km_thanh() {
	$k = skb_km_dang_chay();
	if ( ! $k ) {
		return;
	}
	$tu  = skb_km_gia_thap_nhat();
	$sap = skb_km_sap_het( $k );
	?>
	<div class="km-thanh<?php echo $sap ? ' km-gap' : ''; ?>" data-km-banner role="region" aria-label="Ưu đãi">
		<div class="km-thanh-in">
			<p class="km-thanh-t"><span class="km-pc">Đang có ưu đãi · </span><?php if ( $tu ) : ?>giá từ <strong><?php echo esc_html( skb_km_trieu( $tu ) ); ?></strong><?php else : ?>Đang có ưu đãi<?php endif; ?></p>
			<span class="km-thanh-con"><span class="km-sap" data-km-sap<?php echo $sap ? '' : ' hidden'; ?>>Sắp kết thúc</span><span class="km-con" data-km-con<?php echo $sap ? ' hidden' : ''; ?>>Còn</span> <?php echo skb_km_dong_ho( $k, 'gon' ); // phpcs:ignore ?></span>
			<a class="km-thanh-nut" href="<?php echo esc_url( skb_km_url_uu_dai() ); ?>" data-km-nut="thanh">Xem ưu đãi</a>
		</div>
		<button class="km-thanh-x" type="button" data-km-dong aria-label="Đóng thanh ưu đãi"><svg aria-hidden="true" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round"><path d="M6 6l12 12M18 6 6 18"/></svg></button>
	</div>
	<?php
}

/** Thẻ <picture> của banner (lazy, có width/height và srcset, không phải phần tử LCP) */
function skb_km_anh( $k ) {
	$pc = $k['pc'] ? wp_get_attachment_image_src( $k['pc'], 'full' ) : false;
	$mb = $k['mb'] ? wp_get_attachment_image_src( $k['mb'], 'full' ) : false;
	if ( ! $pc && ! $mb ) {
		return '';
	}
	$chinh = $pc ? $k['pc'] : $k['mb'];
	$src   = $pc ? $pc : $mb;
	$alt   = trim( wp_strip_all_tags( get_the_title( $chinh ) ) );
	$html  = '<picture>';
	if ( $pc && $mb ) {
		$ss    = wp_get_attachment_image_srcset( $k['mb'], 'full' );
		$html .= sprintf( '<source media="(max-width:760px)" srcset="%s" sizes="100vw" width="%d" height="%d">', esc_attr( $ss ? $ss : $mb[0] ), (int) $mb[1], (int) $mb[2] );
	}
	$ss    = wp_get_attachment_image_srcset( $chinh, 'full' );
	$html .= sprintf(
		'<img src="%s"%s sizes="(max-width:1320px) 100vw, 1320px" width="%d" height="%d" alt="%s" loading="lazy" decoding="async" fetchpriority="low">',
		esc_url( $src[0] ),
		$ss ? ' srcset="' . esc_attr( $ss ) . '"' : '',
		(int) $src[1],
		(int) $src[2],
		esc_attr( $alt )
	);
	return $html . '</picture>';
}

/** Dải banner trang chủ: ảnh + đồng hồ (chữ thật) + nút "Chọn xe của bạn" */
function skb_km_bang_trang_chu() {
	$k = skb_km_dang_chay();
	if ( ! $k ) {
		return;
	}
	$sap = skb_km_sap_het( $k );
	$anh = skb_km_anh( $k );
	?>
	<section class="km-bang<?php echo $sap ? ' km-gap' : ''; ?><?php echo $anh ? '' : ' km-bang-chu-only'; ?>" data-km-banner aria-label="Chương trình ưu đãi">
		<?php if ( $anh ) : ?><a class="km-bang-anh" href="<?php echo esc_url( skb_km_url_uu_dai() ); ?>" data-km-nut="banner-anh" tabindex="-1" aria-hidden="true"><?php echo $anh; // phpcs:ignore ?></a><?php endif; ?>
		<div class="km-bang-chu">
			<p class="km-bang-t"><span class="km-sap" data-km-sap<?php echo $sap ? '' : ' hidden'; ?>>Sắp kết thúc</span><span class="km-con" data-km-con<?php echo $sap ? ' hidden' : ''; ?>>Ưu đãi còn</span></p>
			<?php echo skb_km_dong_ho( $k, 'o' ); // phpcs:ignore ?>
			<a class="btn btn-light km-bang-nut" href="<?php echo esc_url( get_post_type_archive_link( 'mau_xe' ) ); ?>" data-km-nut="banner">Chọn xe của bạn</a>
		</div>
	</section>
	<?php
}

/**
 * "Hộp quà ưu đãi" ở trang mẫu xe (bản Việt, chỉ khi có chương trình đang chạy).
 * Không phải rút thăm: mọi khách trong đợt nhận đúng ưu đãi của gói đang chọn (mức giảm tính từ giá niêm yết và giá bán của gói,
 * cộng quà ghi ở chương trình). Khách bấm để mở, kèm mã ưu đãi (KM<chương trình>-<mẫu xe>-<số gói>) để đọc cho SK Light khi báo giá.
 * $gp = mảng skb_km_gia() của từng gói, cùng thứ tự với $goi.
 */
function skb_km_hop_qua( $xe_id, $goi, $gp ) {
	$k = skb_km_dang_chay();
	if ( ! $k || ! $goi ) {
		return;
	}
	$qua  = isset( $k['qua'] ) ? preg_split( '/\n/', (string) $k['qua'], -1, PREG_SPLIT_NO_EMPTY ) : array();
	$co_g = false;
	foreach ( $gp as $p ) {
		if ( ! empty( $p['goc'] ) && ! empty( $p['off'] ) ) {
			$co_g = true;
			break;
		}
	}
	if ( ! $co_g && ! $qua ) {
		return;
	}
	?>
	<section class="km-hq" data-km-an data-km-hq aria-label="Hộp quà ưu đãi">
		<button type="button" class="km-hq-nut" data-km-hq-mo aria-expanded="false" aria-controls="km-hq-nd">
			<svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M5 12v7a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-7M7.5 8a2.5 2.5 0 0 1 0-5C10 3 12 8 12 8s2-5 4.5-5a2.5 2.5 0 0 1 0 5"/></svg>
			<span>Mở hộp quà ưu đãi</span>
		</button>
		<div class="km-hq-nd" id="km-hq-nd" hidden>
			<p class="km-hq-chua" data-hq="0">Chọn một gói ở trên để xem ưu đãi của gói đó.</p>
			<?php foreach ( $goi as $i => $g ) : $p = isset( $gp[ $i ] ) ? $gp[ $i ] : array( 'ban' => 0, 'goc' => 0, 'off' => 0 ); ?>
				<div class="km-hq-ct" data-hq="<?php echo (int) $i + 1; ?>" hidden>
					<p class="km-hq-ten"><?php echo esc_html( $g['ten'] ); ?></p>
					<?php if ( $p['goc'] && $p['off'] ) : ?>
						<p class="km-hq-gia"><strong>Giảm <?php echo (int) $p['off']; ?>%</strong>: còn <strong><?php echo esc_html( skb_tien( $p['ban'] ) ); ?></strong> <s><?php echo esc_html( skb_tien( $p['goc'] ) ); ?></s><br>Tiết kiệm <?php echo esc_html( skb_tien( $p['goc'] - $p['ban'] ) ); ?></p>
					<?php endif; ?>
					<?php if ( $qua ) : ?>
						<ul class="km-hq-qua"><?php foreach ( $qua as $q ) { echo '<li>' . esc_html( $q ) . '</li>'; } ?></ul>
					<?php elseif ( ! ( $p['goc'] && $p['off'] ) ) : ?>
						<p>Gói này chưa có ưu đãi riêng trong đợt này.</p>
					<?php endif; ?>
					<p class="km-hq-ma">Mã ưu đãi: <code><?php echo esc_html( 'KM' . (int) $k['id'] . '-' . (int) $xe_id . '-' . ( (int) $i + 1 ) ); ?></code> Đọc mã này cho SK Light khi nhận báo giá để được áp đúng ưu đãi.</p>
				</div>
			<?php endforeach; ?>
			<p class="km-hq-han"><span>Ưu đãi của đợt này còn</span><?php echo skb_km_dong_ho( $k, 'gon' ); // phpcs:ignore ?></p>
			<p class="km-hq-ghi">Ưu đãi dành cho mọi khách trong đợt này, không phải rút thăm. Đợt sau có thể có quà và mức giảm khác.</p>
		</div>
	</section>
	<script>
	(function () {
		var b = document.querySelector('[data-km-hq]'); if (!b) return;
		var nut = b.querySelector('[data-km-hq-mo]'), nd = b.querySelector('.km-hq-nd');
		var hien = function () {
			var r = document.querySelector('.mx-goi input:checked'), v = r ? r.value : '0';
			b.querySelectorAll('[data-hq]').forEach(function (c) { c.hidden = c.getAttribute('data-hq') !== v; });
		};
		nut.addEventListener('click', function () {
			var mo = nd.hidden; nd.hidden = !mo; nut.setAttribute('aria-expanded', mo ? 'true' : 'false'); b.classList.toggle('mo', mo);
			if (mo) { hien(); try { if (window.skbEv) window.skbEv('uu_dai', 'hop-qua · ' + location.pathname); } catch (e) {} }
		});
		document.addEventListener('change', function (e) { if (!nd.hidden && e.target.matches && e.target.matches('.mx-goi input')) hien(); });
	})();
	</script>
	<?php
}

/** Thẻ mẫu xe (khối trang chủ và danh sách ?uu-dai=1) */
function skb_km_the_xe( $id, $h = 'h3' ) {
	$h = in_array( $h, array( 'h2', 'h3' ), true ) ? $h : 'h3';
	?>
	<a class="post-card" href="<?php echo esc_url( get_permalink( $id ) ); ?>">
		<div class="thumb"><?php echo get_the_post_thumbnail( $id, 'medium_large', array( 'loading' => 'lazy' ) ); // phpcs:ignore ?></div>
		<div class="body"><<?php echo $h; // phpcs:ignore ?>><?php echo esc_html( get_the_title( $id ) ); ?></<?php echo $h; // phpcs:ignore ?>><?php echo skb_km_gia_the( $id ); // phpcs:ignore ?></div>
	</a>
	<?php
}

/** Khối "Mẫu xe đang ưu đãi" ở trang chủ (6 thẻ) */
function skb_km_khoi_trang_chu( $so = 6 ) {
	if ( ! skb_km_dang_chay() ) {
		return;
	}
	$xe = array_slice( skb_km_bang_xe(), 0, max( 4, min( 6, (int) $so ) ) );
	if ( ! $xe ) {
		return;
	}
	?>
	<section class="sec km-xe" data-km-banner aria-labelledby="km-xe-title">
		<div class="sec-head">
			<h2 id="km-xe-title">Mẫu xe đang ưu đãi</h2>
			<p><a href="<?php echo esc_url( skb_km_url_uu_dai() ); ?>" data-km-nut="khoi">Xem tất cả ưu đãi →</a></p>
		</div>
		<div class="posts">
			<?php foreach ( $xe as $x ) { skb_km_the_xe( $x['id'], 'h3' ); } ?>
		</div>
	</section>
	<?php
}

/** Trang /mau-xe/?uu-dai=1 (bản Việt). Trả false nếu không phải trang này. */
function skb_km_trang_uu_dai() {
	if ( ! isset( $_GET['uu-dai'] ) || ( function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai() ) ) { // phpcs:ignore
		return false;
	}
	$k = skb_km_dang_chay();
	if ( ! $k ) {
		echo '<p class="tile-text">Hiện chưa có chương trình ưu đãi. Mời bạn chọn dòng xe để xem giá.</p>';
		return false; // vẫn hiện lưới hãng xe như /mau-xe/
	}
	$xe = skb_km_bang_xe();
	?>
	<div class="km-ds" data-km-banner>
		<p class="km-ds-t"><span class="km-sap" data-km-sap<?php echo skb_km_sap_het( $k ) ? '' : ' hidden'; ?>>Sắp kết thúc</span><span class="km-con" data-km-con<?php echo skb_km_sap_het( $k ) ? ' hidden' : ''; ?>>Ưu đãi còn</span> <?php echo skb_km_dong_ho( $k, 'gon' ); // phpcs:ignore ?></p>
	</div>
	<?php if ( $xe ) : ?>
		<div class="posts">
			<?php foreach ( $xe as $x ) { skb_km_the_xe( $x['id'], 'h2' ); } ?>
		</div>
	<?php endif; ?>
	<?php
	return true;
}

/* ---------- Trang quản trị: khung nhập, cột trạng thái ---------- */
add_action( 'add_meta_boxes_khuyen_mai', function () {
	add_meta_box( 'skb_km_box', 'Ảnh banner và thời gian', 'skb_km_box', 'khuyen_mai', 'normal', 'high' );
	remove_meta_box( 'postcustom', 'khuyen_mai', 'normal' ); /* custom-fields chỉ để REST đọc ghi meta, ẩn khung mặc định */
} );

add_action( 'admin_enqueue_scripts', function () {
	$s = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( $s && 'khuyen_mai' === $s->post_type && 'post' === $s->base ) {
		wp_enqueue_media();
	}
} );

/** Trạng thái để hiện cho người quản trị */
function skb_km_trang_thai( $post_id ) {
	foreach ( skb_km_ds() as $k ) {
		if ( (int) $k['id'] !== (int) $post_id ) {
			continue;
		}
		$now = time();
		if ( ! skb_km_hop_le( $k ) ) {
			return 'Tắt (chưa đăng hoặc chưa bật)';
		}
		if ( $now < $k['bd'] ) {
			return 'Sắp chạy';
		}
		return $now > $k['kt'] ? 'Đã kết thúc' : 'Đang chạy';
	}
	return 'Thiếu giờ bắt đầu/kết thúc hoặc giờ kết thúc không sau giờ bắt đầu';
}

function skb_km_box( $post ) {
	wp_nonce_field( 'skb_km_luu', 'skb_km_nonce' );
	$v = function ( $k ) use ( $post ) {
		return get_post_meta( $post->ID, $k, true );
	};
	$gio = function ( $s ) {
		$s = skb_km_chuan_gio( $s );
		return $s ? str_replace( ' ', 'T', $s ) : '';
	};
	?>
	<p style="margin-top:0">Chỉ hiện ở bản tiếng Việt. Chữ trên banner nằm trong giao diện, ảnh không in chữ. Chữ thay thế (alt) của ảnh lấy từ <b>tiêu đề ảnh</b> trong Thư viện. Giờ tính theo giờ Việt Nam.</p>
	<table class="form-table" role="presentation">
		<?php foreach ( array( '_skb_km_anh_pc' => 'Ảnh máy tính (1600x500)', '_skb_km_anh_mobile' => 'Ảnh điện thoại (800x600, tuỳ chọn)' ) as $k => $nhan ) : $id = (int) $v( $k ); $u = $id ? wp_get_attachment_image_url( $id, 'medium' ) : ''; ?>
		<tr>
			<th scope="row"><?php echo esc_html( $nhan ); ?></th>
			<td class="skb-km-anh">
				<input type="hidden" name="<?php echo esc_attr( ltrim( $k, '_' ) ); ?>" value="<?php echo $id ? (int) $id : ''; ?>">
				<img src="<?php echo esc_url( $u ); ?>" alt="" style="max-width:320px;height:auto;display:<?php echo $u ? 'block' : 'none'; ?>;margin-bottom:8px">
				<button type="button" class="button skb-km-chon">Chọn ảnh</button>
				<button type="button" class="button-link skb-km-bo" style="margin-left:8px">Bỏ ảnh</button>
			</td>
		</tr>
		<?php endforeach; ?>
		<tr>
			<th scope="row"><label for="skb-km-qua">Quà và ưu đãi của đợt</label></th>
			<td><textarea id="skb-km-qua" name="skb_km_qua" rows="4" cols="60" maxlength="760" placeholder="Mỗi dòng một ý, ví dụ: Tặng kèm ..."><?php echo esc_textarea( (string) $v( '_skb_km_qua' ) ); ?></textarea><p class="description">Hiện trong "Hộp quà ưu đãi" ở trang mẫu xe, bên cạnh mức giảm của gói. Chỉ ghi những gì thật sự giao cho khách trong đợt này (tối đa 6 dòng, mỗi dòng 120 ký tự). Để trống thì hộp quà chỉ hiện mức giảm.</p></td>
		</tr>
		<tr>
			<th scope="row"><label for="skb-km-bd">Bắt đầu</label></th>
			<td><input id="skb-km-bd" type="datetime-local" step="1" name="skb_km_bat_dau" value="<?php echo esc_attr( $gio( $v( '_skb_km_bat_dau' ) ) ); ?>"> <span class="description">ví dụ 01/12/2026 00:00:00</span></td>
		</tr>
		<tr>
			<th scope="row"><label for="skb-km-kt">Kết thúc</label></th>
			<td><input id="skb-km-kt" type="datetime-local" step="1" name="skb_km_ket_thuc" value="<?php echo esc_attr( $gio( $v( '_skb_km_ket_thuc' ) ) ); ?>"> <span class="description">ví dụ 25/12/2026 23:59:59</span></td>
		</tr>
		<tr>
			<th scope="row">Bật</th>
			<td><label><input type="checkbox" name="skb_km_bat" value="1"<?php checked( (bool) $v( '_skb_km_bat' ) ); ?>> Bật chương trình (vẫn phải bấm Đăng)</label></td>
		</tr>
		<tr>
			<th scope="row">Trạng thái</th>
			<td><?php echo esc_html( skb_km_trang_thai( $post->ID ) ); ?>. <span class="description">Xem thử: mở trang bất kỳ khi đang đăng nhập và thêm <code>?km_thu=<?php echo esc_html( wp_date( 'Y-m-d\TH:i', null, skb_km_tz() ) ); ?></code> (đổi giờ tuỳ ý).</span></td>
		</tr>
	</table>
	<script>
	(function () {
		document.querySelectorAll('.skb-km-anh').forEach(function (td) {
			var inp = td.querySelector('input'), img = td.querySelector('img'), frame = null;
			td.querySelector('.skb-km-chon').addEventListener('click', function () {
				if (!window.wp || !wp.media) return;
				if (!frame) {
					frame = wp.media({ title: 'Chọn ảnh banner', library: { type: 'image' }, multiple: false });
					frame.on('select', function () {
						var a = frame.state().get('selection').first().toJSON();
						inp.value = a.id; img.src = (a.sizes && a.sizes.medium ? a.sizes.medium.url : a.url); img.style.display = 'block';
					});
				}
				frame.open();
			});
			td.querySelector('.skb-km-bo').addEventListener('click', function () { inp.value = ''; img.style.display = 'none'; img.removeAttribute('src'); });
		});
	})();
	</script>
	<?php
}

add_action( 'save_post_khuyen_mai', function ( $post_id ) {
	if ( ! isset( $_POST['skb_km_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skb_km_nonce'] ) ), 'skb_km_luu' ) ) { // phpcs:ignore
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array( '_skb_km_anh_pc', '_skb_km_anh_mobile' ) as $k ) {
		$id = absint( $_POST[ ltrim( $k, '_' ) ] ?? 0 ); // phpcs:ignore
		if ( $id ) {
			update_post_meta( $post_id, $k, $id );
		} else {
			delete_post_meta( $post_id, $k );
		}
	}
	foreach ( array( '_skb_km_bat_dau', '_skb_km_ket_thuc' ) as $k ) {
		update_post_meta( $post_id, $k, skb_km_chuan_gio( sanitize_text_field( wp_unslash( $_POST[ ltrim( $k, '_' ) ] ?? '' ) ) ) ); // phpcs:ignore
	}
	update_post_meta( $post_id, '_skb_km_bat', ! empty( $_POST['skb_km_bat'] ) ); // phpcs:ignore
	update_post_meta( $post_id, '_skb_km_qua', skb_km_chuan_qua( sanitize_textarea_field( wp_unslash( $_POST['skb_km_qua'] ?? '' ) ) ) ); // phpcs:ignore
}, 5 );

add_filter( 'manage_khuyen_mai_posts_columns', function ( $c ) {
	$d = isset( $c['date'] ) ? $c['date'] : null;
	unset( $c['date'] );
	$c['skb_km_bd'] = 'Bắt đầu';
	$c['skb_km_kt'] = 'Kết thúc';
	$c['skb_km_tt'] = 'Trạng thái';
	if ( $d ) {
		$c['date'] = $d;
	}
	return $c;
} );
add_action( 'manage_khuyen_mai_posts_custom_column', function ( $col, $id ) {
	if ( 'skb_km_bd' === $col ) {
		echo esc_html( (string) get_post_meta( $id, '_skb_km_bat_dau', true ) );
	} elseif ( 'skb_km_kt' === $col ) {
		echo esc_html( (string) get_post_meta( $id, '_skb_km_ket_thuc', true ) );
	} elseif ( 'skb_km_tt' === $col ) {
		echo esc_html( skb_km_trang_thai( $id ) );
	}
}, 10, 2 );
