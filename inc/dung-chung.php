<?php
/**
 * Dòng sản phẩm "Dùng chung" (lắp được cho mọi dòng xe) — 0.24.0.
 * - Hai loại: S2Pro và S3Pro, mỗi loại có nhiều gói theo số chi tiết. Nhập MỘT lần ở Mẫu xe › Dòng dùng chung
 *   (option skb_dung_chung); chưa nhập thì dùng bảng giá lấy từ catalog.sklight.vn ngày 27/11/2026.
 * - Trang mẫu xe (bản tiếng Việt):
 *     · xe có gói ốp riêng  → công tắc "Ốp theo xe | Dùng chung"; trong Dùng chung chọn S2Pro / S3Pro rồi chọn số chi tiết;
 *     · xe chỉ có các gói dùng chung S2Pro (mọi gói từ 10 chi tiết của xe đều khớp bảng S2Pro) → công tắc "S2Pro | S3Pro":
 *       S2Pro là các gói đang nhập ở xe đó (giữ nguyên thứ tự để mã ưu đãi và liên kết ?goi= không đổi), S3Pro lấy từ bảng chung.
 * - Gói dùng chung in ra dưới dạng nút chọn cùng nhóm với gói của xe (name="skb_goi"), nên đoạn script sẵn có của trang
 *   tự đổi giá, nút Đặt lịch và thanh giá. Giá theo đúng luật khuyến mại (skb_km_gia). Catalog là nguồn giá đúng.
 */

defined( 'ABSPATH' ) || exit;

/** Bảng mặc định (giá bán | giá niêm yết, đồng) — catalog.sklight.vn/c/dung-chung-moi-xe, 27/11/2026 */
function skb_dc_mac_dinh() {
	return array(
		's2pro' => array(
			'ten'   => 'S2Pro',
			'mo_ta' => 'Thanh LED cánh cửa màu đen, tắt đèn không bị lộ.',
			'url'   => '/c/dung-chung-moi-xe/s2pro',
			'goi'   => array(
				array( 18, 4800000, 6000000 ),
				array( 20, 5400000, 6900000 ),
				array( 22, 5900000, 7100000 ),
				array( 24, 6500000, 7900000 ),
				array( 26, 7200000, 8700000 ),
				array( 28, 8000000, 9600000 ),
				array( 30, 8900000, 10400000 ),
				array( 32, 9800000, 11400000 ),
			),
		),
		's3pro' => array(
			'ten'   => 'S3Pro',
			'mo_ta' => 'Thanh LED tapli cửa có họa tiết chấm sao.',
			'url'   => '/c/dung-chung-moi-xe/s3pro',
			'goi'   => array(
				array( 18, 6000000, 7000000 ),
				array( 20, 6900000, 8000000 ),
				array( 22, 7000000, 9000000 ),
				array( 24, 7900000, 10000000 ),
			),
		),
	);
}

/** Bảng đang dùng: bảng mặc định, phần nào đã nhập ở trang quản trị thì lấy phần đã nhập */
function skb_dc_bang() {
	static $bang = null;
	if ( null !== $bang ) {
		return $bang;
	}
	$bang = skb_dc_mac_dinh();
	$luu  = get_option( 'skb_dung_chung', array() );
	foreach ( $bang as $k => $b ) {
		if ( empty( $luu[ $k ] ) || ! is_array( $luu[ $k ] ) ) {
			continue;
		}
		if ( isset( $luu[ $k ]['mo_ta'] ) ) {
			$bang[ $k ]['mo_ta'] = trim( (string) $luu[ $k ]['mo_ta'] );
		}
		$goi = skb_dc_doc_goi( isset( $luu[ $k ]['goi'] ) ? (string) $luu[ $k ]['goi'] : '' );
		if ( $goi ) {
			$bang[ $k ]['goi'] = $goi;
		}
	}
	return $bang;
}

/** "18 | 4800000 | 6000000" mỗi dòng → mảng gói; dòng sai bỏ qua */
function skb_dc_doc_goi( $chu ) {
	$out = array();
	foreach ( preg_split( '/\r\n|\r|\n/', (string) $chu ) as $dong ) {
		$p = array_map( 'trim', explode( '|', $dong ) );
		if ( count( $p ) < 2 ) {
			continue;
		}
		$ct  = (int) preg_replace( '/\D/', '', $p[0] );
		$gia = (int) preg_replace( '/\D/', '', $p[1] );
		$goc = isset( $p[2] ) ? (int) preg_replace( '/\D/', '', $p[2] ) : 0;
		if ( $ct > 0 && $gia > 0 ) {
			$out[] = array( $ct, $gia, $goc );
		}
	}
	return $out;
}

function skb_dc_ghi_goi( $goi ) {
	$d = array();
	foreach ( $goi as $g ) {
		$d[] = $g[0] . ' | ' . $g[1] . ' | ' . $g[2];
	}
	return implode( "\n", $d );
}

/**
 * Xe này hiện dòng dùng chung kiểu nào?
 * Trả về null (không hiện), hoặc array( 'kieu' => 'ca-hai' | 'chung', 'loai' => các loại lấy từ bảng chung ).
 */
function skb_dc_cho_xe( $goi ) {
	if ( ! $goi ) {
		return null;
	}
	$bang = skb_dc_bang();
	$s2   = array();
	foreach ( $bang['s2pro']['goi'] as $g ) {
		$s2[ $g[0] ] = $g;
	}
	$lon   = 0;
	$trung = 0;
	foreach ( $goi as $g ) {
		if ( ! preg_match( '/^\s*(\d+)\s*chi\s*tiết/iu', $g['ten'], $m ) ) {
			continue;
		}
		$ct = (int) $m[1];
		if ( $ct < 10 ) {
			continue; // phụ kiện lẻ (vành loa 4 chi tiết…) không tính
		}
		$lon++;
		$ten_tron = (bool) preg_match( '/^\s*\d+\s*chi\s*tiết\s*$/iu', $g['ten'] );
		if ( $ten_tron && isset( $s2[ $ct ] ) && (int) $g['gia'] === $s2[ $ct ][1] && (int) $g['goc'] === $s2[ $ct ][2] ) {
			$trung++;
		}
	}
	if ( $lon && $trung === $lon ) {
		return array( 'kieu' => 'chung', 'loai' => array( 's3pro' ) );
	}
	return array( 'kieu' => 'ca-hai', 'loai' => array( 's2pro', 's3pro' ) );
}

/** "4.800.000đ – 9.800.000đ" của một loại; $cot = 'ban' (đang bán) hoặc 'niem' (niêm yết) */
function skb_dc_khoang( $loai, $cot = 'ban' ) {
	$bang = skb_dc_bang();
	$v    = array();
	foreach ( $bang[ $loai ]['goi'] as $g ) {
		$p   = skb_km_gia( $g[1], $g[2] );
		$v[] = $p[ $cot ];
	}
	$v = array_filter( $v );
	if ( ! $v ) {
		return '';
	}
	return min( $v ) === max( $v ) ? skb_tien( min( $v ) ) : skb_tien( min( $v ) ) . ' – ' . skb_tien( max( $v ) );
}

/** Công tắc phía trên danh sách gói */
function skb_dc_cong_tac( $dc ) {
	$bang = skb_dc_bang();
	if ( 'chung' === $dc['kieu'] ) {
		echo '<p class="dc-note">Xe này lắp dòng <strong>Dùng chung</strong>. Chọn loại rồi chọn số chi tiết.</p>';
		echo '<div class="dc-loai" role="group" aria-label="Chọn loại">';
		skb_dc_the_loai( 's2pro', 'rieng', true );
		skb_dc_the_loai( 's3pro', 's3pro', false );
		echo '</div>';
		return;
	}
	echo '<div class="dc-tab" role="group" aria-label="Chọn dòng sản phẩm">';
	echo '<button type="button" class="dc-tab-b" data-dc-mo="rieng" aria-pressed="true"><b>Ốp theo xe</b><small>Làm riêng cho xe này</small></button>';
	echo '<button type="button" class="dc-tab-b" data-dc-mo="chung" aria-pressed="false"><b>Dùng chung</b><small>' . esc_html( $bang['s2pro']['ten'] . ' và ' . $bang['s3pro']['ten'] ) . ', lắp được mọi xe</small></button>';
	echo '</div>';
}

function skb_dc_the_loai( $loai, $mo, $bat ) {
	$b = skb_dc_bang()[ $loai ];
	printf(
		'<button type="button" class="dc-loai-b" data-dc-mo="%s" aria-pressed="%s"><b>%s</b><small>%s</small></button>',
		esc_attr( $mo ),
		$bat ? 'true' : 'false',
		esc_html( $b['ten'] ),
		esc_html( $b['mo_ta'] )
	);
}

/** Danh sách gói của một loại, cùng kiểu thẻ với gói của xe */
function skb_dc_ds_goi( $loai ) {
	$b  = skb_dc_bang()[ $loai ];
	$km = (bool) skb_km_dang_chay();
	printf(
		'<div class="goi-list" data-dc-pan="%s" data-khoang="%s" data-khoang-niem="%s" hidden>',
		esc_attr( $loai ),
		esc_attr( skb_dc_khoang( $loai, 'ban' ) ),
		esc_attr( skb_dc_khoang( $loai, 'niem' ) )
	);
	foreach ( $b['goi'] as $g ) {
		$p   = skb_km_gia( $g[1], $g[2] );
		$p_t = skb_tien( $p['ban'] );
		$p_n = skb_tien( $p['niem'] );
		$ten = $g[0] . ' chi tiết';
		printf(
			'<label class="goi"><input type="radio" name="skb_goi" value="dc-%1$s-%2$d" data-ten="%3$s" data-gia="%4$s" data-goc="%5$s"%6$s><span class="goi-ten">%7$s</span><span class="goi-gia"><span%8$s>%4$s</span>%9$s</span>%10$s</label>',
			esc_attr( $loai ),
			(int) $g[0],
			esc_attr( 'Dùng chung ' . $b['ten'] . ' ' . $ten ),
			esc_attr( $p_t ),
			esc_attr( $p['goc'] ? skb_tien( $p['goc'] ) : '' ),
			( $km && $p_n !== $p_t ) ? ' data-niem="' . esc_attr( $p_n ) . '"' : '',
			esc_html( $ten ),
			$km ? skb_km_niem_attr( $p_t, $p_n ) : '', // phpcs:ignore
			$p['off'] ? ' <em class="goi-off" data-km-an>-' . (int) $p['off'] . '%</em>' : '',
			( $km && $p['goc'] ) ? '<s class="goi-goc" data-km-an>' . esc_html( skb_tien( $p['goc'] ) ) . '</s>' : ''
		);
	}
	echo '</div>';
}

/** Phần dùng chung đặt sau danh sách gói của xe */
function skb_dc_pan( $dc ) {
	$bang = skb_dc_bang();
	if ( 'ca-hai' === $dc['kieu'] ) {
		echo '<div class="dc-chung" data-dc-pan="chung" hidden>';
		echo '<div class="dc-loai" role="group" aria-label="Chọn loại">';
		skb_dc_the_loai( 's2pro', 's2pro', true );
		skb_dc_the_loai( 's3pro', 's3pro', false );
		echo '</div>';
		foreach ( $dc['loai'] as $loai ) {
			skb_dc_ds_goi( $loai );
		}
		echo '</div>';
	} else {
		foreach ( $dc['loai'] as $loai ) {
			skb_dc_ds_goi( $loai );
		}
	}
	printf(
		'<p class="dc-cat" data-dc-cat hidden><a class="cat-link" href="%s" target="_blank" rel="noopener">Xem chi tiết dòng Dùng chung trên catalog <span aria-hidden="true">↗</span><span class="sr-only"> (mở catalog ở tab mới)</span></a></p>',
		esc_url( add_query_arg( 'nguon', 'web', SKB_CATALOG_URL . '/c/dung-chung-moi-xe' ) )
	);
	echo '<noscript><style>[data-dc-pan][hidden],[data-dc-cat][hidden]{display:grid!important}</style></noscript>';
}

/* ---------- Trang quản trị: Mẫu xe › Dòng dùng chung ---------- */
add_action( 'admin_menu', function () {
	add_submenu_page( 'edit.php?post_type=mau_xe', 'Dòng dùng chung', 'Dòng dùng chung', 'edit_others_posts', 'skb-dung-chung', 'skb_dc_trang' );
} );
add_action( 'admin_init', function () {
	register_setting( 'skb_dung_chung', 'skb_dung_chung', array( 'type' => 'array', 'default' => array(), 'sanitize_callback' => 'skb_dc_lam_sach' ) );
} );
add_filter( 'option_page_capability_skb_dung_chung', function () {
	return 'edit_others_posts';
} );
function skb_dc_lam_sach( $v ) {
	$out = array();
	foreach ( array_keys( skb_dc_mac_dinh() ) as $k ) {
		$out[ $k ] = array(
			'mo_ta' => isset( $v[ $k ]['mo_ta'] ) ? sanitize_text_field( wp_unslash( $v[ $k ]['mo_ta'] ) ) : '',
			'goi'   => skb_dc_ghi_goi( skb_dc_doc_goi( isset( $v[ $k ]['goi'] ) ? wp_unslash( $v[ $k ]['goi'] ) : '' ) ),
		);
	}
	if ( function_exists( 'skb_cache_xoa' ) ) {
		skb_cache_xoa();
	}
	return $out;
}
function skb_dc_trang() {
	$bang = skb_dc_bang();
	?>
	<div class="wrap">
		<h1>Dòng dùng chung</h1>
		<p>Dòng sản phẩm lắp được cho mọi dòng xe. Nhập ở đây <strong>một lần</strong>, các trang mẫu xe tự hiện. Mỗi dòng là một gói: <code>số chi tiết | giá bán | giá niêm yết</code> (đồng, không cần dấu chấm). Giá phải khớp catalog.sklight.vn. Ngoài chương trình khuyến mại, web chỉ hiện giá niêm yết.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'skb_dung_chung' ); ?>
			<?php foreach ( $bang as $k => $b ) : ?>
				<h2><?php echo esc_html( $b['ten'] ); ?></h2>
				<p><label>Một câu mô tả (hiện trên thẻ chọn loại)<br><input type="text" class="large-text" name="skb_dung_chung[<?php echo esc_attr( $k ); ?>][mo_ta]" value="<?php echo esc_attr( $b['mo_ta'] ); ?>"></label></p>
				<p><label>Các gói<br><textarea class="large-text code" rows="9" name="skb_dung_chung[<?php echo esc_attr( $k ); ?>][goi]"><?php echo esc_textarea( skb_dc_ghi_goi( $b['goi'] ) ); ?></textarea></label></p>
			<?php endforeach; ?>
			<?php submit_button( 'Lưu' ); ?>
		</form>
	</div>
	<?php
}
