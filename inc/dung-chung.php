<?php
/**
 * Dòng sản phẩm "Dùng chung" (lắp được cho mọi dòng xe) và bố cục "chọn kiểu lắp" ở trang mẫu xe — 0.25.0.
 * - Hai loại dùng chung: S2Pro và S3Pro, mỗi loại nhiều gói theo số chi tiết. Nhập MỘT lần ở Mẫu xe › Dòng dùng chung
 *   (option skb_dung_chung); chưa nhập thì dùng bảng giá lấy từ catalog.sklight.vn ngày 27/11/2026.
 * - Trang mẫu xe (bản tiếng Việt) có các thẻ kiểu lắp ngang hàng:
 *     · xe có gói ốp riêng → 3 thẻ: Ốp theo xe | S2Pro | S3Pro;
 *     · xe chỉ có các gói dùng chung S2Pro (mọi gói từ 10 chi tiết của xe đều khớp bảng S2Pro) → 2 thẻ: S2Pro | S3Pro,
 *       S2Pro là các gói đang nhập ở xe đó (giữ thứ tự để mã ưu đãi và liên kết ?goi= không đổi), S3Pro lấy từ bảng chung.
 * - Gói dùng chung in ra cùng nhóm nút chọn với gói của xe (name="skb_goi"), nên script sẵn có của trang tự đổi giá và
 *   nút Đặt lịch. Giá theo đúng luật khuyến mại (skb_km_gia). Catalog là nguồn giá đúng.
 * - Dưới phần chọn: khối "Về kiểu … trên <xe>" (điểm khác biệt + các món trong gói đang chọn) và bảng so sánh các kiểu.
 *   Toàn bộ chữ in sẵn trong HTML (Google và các trình thu thập AI không chạy script vẫn đọc được); script chỉ ẩn/hiện.
 * - Thành phần từng gói dùng chung đếm theo ẢNH trong file báo giá T12-2026 của chủ (xem docs/dung-chung-thanh-phan-goi.md).
 * - Mỗi xe có thể gắn ảnh riêng cho S2Pro và S3Pro (khung "Ảnh dòng dùng chung trên xe này"): chọn thẻ nào thì bộ ảnh đổi theo.
 * Script: khối cuối assets/main.js.
 */

defined( 'ABSPATH' ) || exit;

/** Bảng mặc định (giá bán | giá niêm yết, đồng) — catalog.sklight.vn/c/dung-chung-moi-xe, 27/11/2026 */
function skb_dc_mac_dinh() {
	return array(
		's2pro' => array(
			'ten'   => 'S2Pro',
			'mo_ta' => 'Dây LED cửa vỏ đen, tắt đèn không lộ.',
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
			'mo_ta' => 'Thanh LED cửa họa tiết chấm sao.',
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
	$so    = array();
	foreach ( $goi as $i => $g ) {
		if ( ! preg_match( '/^\s*(\d+)\s*chi\s*tiết/iu', $g['ten'], $m ) ) {
			continue;
		}
		$ct = (int) $m[1];
		if ( $ct < 10 ) {
			continue; // phụ kiện lẻ (vành loa 4 chi tiết…) không tính
		}
		$lon++;
		$so[ $i + 1 ] = $ct;
		$ten_tron = (bool) preg_match( '/^\s*\d+\s*chi\s*tiết\s*$/iu', $g['ten'] );
		if ( $ten_tron && isset( $s2[ $ct ] ) && (int) $g['gia'] === $s2[ $ct ][1] && (int) $g['goc'] === $s2[ $ct ][2] ) {
			$trung++;
		}
	}
	if ( $lon && $trung === $lon ) {
		return array( 'kieu' => 'chung', 'loai' => array( 's3pro' ), 'so' => $so );
	}
	return array( 'kieu' => 'ca-hai', 'loai' => array( 's2pro', 's3pro' ), 'so' => array() );
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

/* ---------- Thành phần gói và chữ mô tả ---------- */

/** Số món theo gói dùng chung: [taplo, tapli cửa, yên ngựa, vành loa, trần]; 12 món nền (gầm, tay mở cửa, hộc cốc) gói nào cũng có */
function skb_dc_thanh_phan() {
	return array(
		18 => array( 2, 4, 0, 0, 0 ),
		20 => array( 2, 4, 2, 0, 0 ),
		22 => array( 2, 4, 0, 4, 0 ),
		24 => array( 2, 4, 2, 4, 0 ),
		26 => array( 2, 8, 0, 4, 0 ),
		28 => array( 2, 8, 2, 4, 0 ),
		30 => array( 4, 8, 2, 4, 0 ),
		32 => array( 4, 8, 2, 4, 2 ),
	);
}

/** Gói này bằng gói nào cộng thêm gì */
function skb_dc_truoc() {
	return array(
		20 => array( 18, 'thêm 2 thanh LED yên ngựa' ),
		22 => array( 18, 'thêm 4 đèn vành loa' ),
		24 => array( 22, 'thêm 2 thanh LED yên ngựa' ),
		26 => array( 22, 'thêm 4 đèn tapli cửa (thành 8)' ),
		28 => array( 26, 'thêm 2 thanh LED yên ngựa' ),
		30 => array( 28, 'thêm 2 thanh LED taplo (thành 2 dài, 2 ngắn)' ),
		32 => array( 30, 'thêm 2 thanh LED trần' ),
	);
}

/** Câu "Gói 30 = gói 28 thêm …" (gói 18: câu gói cơ bản; gói lạ: rỗng) */
function skb_dc_so_voi( $ct ) {
	$tp = skb_dc_thanh_phan();
	if ( ! isset( $tp[ $ct ] ) ) {
		return '';
	}
	$tr = skb_dc_truoc();
	if ( isset( $tr[ $ct ] ) ) {
		return sprintf( 'Gói %d = gói %d %s.', $ct, $tr[ $ct ][0], $tr[ $ct ][1] );
	}
	return 'Gói cơ bản: 2 thanh LED taplo, 4 đèn tapli cửa, 4 đèn gầm, 4 đèn tay mở cửa, 4 đèn hộc để cốc.';
}

/** Tên món đèn tapli cửa của từng loại (chỗ duy nhất hai loại khác nhau) */
function skb_dc_ten_cua( $loai ) {
	return 's3pro' === $loai ? 'thanh LED tapli cửa họa tiết chấm sao' : 'dây LED tapli cửa vỏ đen, dài 90 cm';
}

/** Các món của một gói: mảng array( chữ, có phải món mới so với gói trước ) */
function skb_dc_mon( $loai, $ct ) {
	$tp = skb_dc_thanh_phan();
	if ( ! isset( $tp[ $ct ] ) ) {
		return array();
	}
	$tr  = skb_dc_truoc();
	$cu  = isset( $tr[ $ct ] ) ? $tp[ $tr[ $ct ][0] ] : null;
	$ten = array(
		4 === $tp[ $ct ][0] ? 'thanh LED taplo (2 dài, 2 ngắn)' : 'thanh LED taplo (1 dài, 1 ngắn)',
		skb_dc_ten_cua( $loai ),
		'thanh LED yên ngựa',
		'đèn vành loa',
		'thanh LED trần',
	);
	$out = array();
	foreach ( $tp[ $ct ] as $k => $v ) {
		if ( $v ) {
			$out[] = array( $v . ' ' . $ten[ $k ], $cu && $v > $cu[ $k ] );
		}
	}
	foreach ( array( '4 đèn gầm', '4 đèn tay mở cửa', '4 đèn hộc để cốc', 'Dây nguồn, công tắc và bộ hộp điều khiển' ) as $m ) {
		$out[] = array( $m, false );
	}
	return $out;
}

/** Điểm khác biệt của từng kiểu lắp (chỉ dùng thông tin chủ đã công bố hoặc ghi trên tờ sản phẩm) */
function skb_dc_diem( $kieu ) {
	$d = array(
		'rieng' => array(
			'Tấm ốp làm riêng theo đúng đời xe, thay đúng vị trí ốp zin và giữ dáng nguyên bản.',
			'Đấu bằng giắc chờ, không cắt chích dây.',
			'Đổi màu và hiệu ứng bằng app và nút bấm.',
		),
		's2pro' => array(
			'Thanh LED cánh cửa màu đen nên khi tắt đèn sẽ không bị lộ.',
			'Thanh LED được nâng cấp dài hơn, không bị hụt khi lắp đặt.',
			'Có thể tắt riêng vùng ánh sáng bị chói ngay trên công tắc.',
			'Có nhiều chế độ chào mừng tuỳ chỉnh trên app điện thoại.',
		),
		's3pro' => array(
			'Đèn tapli cửa là thanh LED có họa tiết chấm sao, gồm 2 thanh dài và 2 thanh ngắn hơn.',
			'Các món còn lại giống S2Pro: taplo, yên ngựa, vành loa, đèn gầm, tay mở cửa, hộc để cốc.',
			'Đổi màu và hiệu ứng bằng app và nút bấm.',
		),
	);
	return isset( $d[ $kieu ] ) ? $d[ $kieu ] : array();
}

/** Giá bán thấp nhất của một loại dùng chung (đồng) */
function skb_dc_tu( $loai ) {
	$v = array();
	foreach ( skb_dc_bang()[ $loai ]['goi'] as $g ) {
		$p   = skb_km_gia( $g[1], $g[2] );
		$v[] = $p['ban'];
	}
	$v = array_filter( $v );
	return $v ? min( $v ) : 0;
}

/** Giá bán thấp nhất trong các gói của xe; ưu tiên gói từ 10 chi tiết (không lấy phụ kiện lẻ) */
function skb_dc_tu_xe( $goi, $gp ) {
	$lon = array();
	$all = array();
	foreach ( $goi as $i => $g ) {
		$ban = isset( $gp[ $i ]['ban'] ) ? (int) $gp[ $i ]['ban'] : 0;
		if ( ! $ban ) {
			continue;
		}
		$all[] = $ban;
		if ( preg_match( '/^\s*(\d+)\s*chi\s*tiết/iu', $g['ten'], $m ) && (int) $m[1] >= 10 ) {
			$lon[] = $ban;
		}
	}
	return $lon ? min( $lon ) : ( $all ? min( $all ) : 0 );
}

/** 4800000 → "4,8 tr" (dùng trên thẻ kiểu lắp) */
function skb_dc_trieu( $v ) {
	return $v ? rtrim( rtrim( number_format( $v / 1000000, 1, ',', '.' ), '0' ), ',' ) . ' tr' : '';
}

/** Các kiểu lắp của xe này theo thứ tự hiện: khoá = tên khối (data-dc-pan), giá trị = 'rieng' | 's2pro' | 's3pro' (nội dung) */
function skb_dc_kieu( $dc ) {
	return 'chung' === $dc['kieu'] ? array( 'rieng' => 's2pro', 's3pro' => 's3pro' ) : array( 'rieng' => 'rieng', 's2pro' => 's2pro', 's3pro' => 's3pro' );
}

function skb_dc_ten_kieu( $nd ) {
	$bang = skb_dc_bang();
	return 'rieng' === $nd ? 'Ốp theo xe' : $bang[ $nd ]['ten'];
}

/* ---------- Hiển thị trong ô chọn gói ---------- */

/** Bước 1: các thẻ kiểu lắp (đặt trên danh sách gói) */
function skb_dc_cong_tac( $dc, $goi = array(), $gp = array() ) {
	$bang = skb_dc_bang();
	$dir  = get_template_directory_uri() . '/assets/img/dc/';
	$n    = count( skb_dc_kieu( $dc ) );
	printf( '<p class="dc-gt">Xe này lắp được %d kiểu. Chọn một kiểu để xem gói và giá.</p>', (int) $n );
	echo '<p class="dc-buoc"><b>1</b>Chọn kiểu lắp</p>';
	printf( '<div class="dc-kieu" role="group" aria-label="Chọn kiểu lắp" data-dc-so="%s">', esc_attr( wp_json_encode( (object) $dc['so'] ) ) );
	$dau = true;
	foreach ( skb_dc_kieu( $dc ) as $pan => $nd ) {
		if ( 'rieng' === $nd ) {
			$anh = get_the_post_thumbnail_url( get_the_ID(), 'thumbnail' );
			$mo  = 'Tấm ốp làm riêng cho xe này, thay đúng vị trí ốp zin';
			$tu  = skb_dc_tu_xe( $goi, $gp );
		} else {
			$anh = $dir . $nd . '.webp';
			$mo  = 'Dùng chung mọi xe. ' . $bang[ $nd ]['mo_ta'];
			$tu  = 'rieng' === $pan ? skb_dc_tu_xe( $goi, $gp ) : skb_dc_tu( $nd );
		}
		printf(
			'<button type="button" class="dc-k" data-dc-mo="%s" data-dc-nd="%s" aria-pressed="%s">%s<span><b>%s</b><small>%s</small></span>%s</button>',
			esc_attr( $pan ),
			esc_attr( $nd ),
			$dau ? 'true' : 'false',
			$anh ? '<img src="' . esc_url( $anh ) . '" alt="" width="84" height="58" loading="lazy" decoding="async">' : '',
			esc_html( skb_dc_ten_kieu( $nd ) ),
			esc_html( $mo ),
			$tu ? '<em>từ <strong>' . esc_html( skb_dc_trieu( $tu ) ) . '</strong></em>' : ''
		);
		$dau = false;
	}
	echo '</div>';
	echo '<p class="dc-buoc"><b>2</b>Chọn gói theo số chi tiết đèn</p>';
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

/** Phần đặt sau danh sách gói của xe: danh sách gói dùng chung, dòng "so với gói trước", liên kết catalog */
function skb_dc_pan( $dc ) {
	foreach ( $dc['loai'] as $loai ) {
		skb_dc_ds_goi( $loai );
	}
	echo '<p class="dc-sv" data-dc-sv hidden></p>';
	printf(
		'<p class="dc-cat" data-dc-cat%s><a class="cat-link" href="%s" target="_blank" rel="noopener">Xem dòng Dùng chung trên catalog <span aria-hidden="true">↗</span><span class="sr-only"> (mở catalog ở tab mới)</span></a></p>',
		'chung' === $dc['kieu'] ? '' : ' hidden',
		esc_url( add_query_arg( 'nguon', 'web', SKB_CATALOG_URL . '/c/dung-chung-moi-xe' ) )
	);
	echo '<noscript><style>.mx-goi [data-dc-pan][hidden]{display:grid!important}</style></noscript>';
}

/* ---------- Dưới phần chọn: "Về kiểu …" và bảng so sánh ---------- */

function skb_dc_duoi( $dc, $goi = array(), $gp = array(), $bai = '' ) {
	$bang = skb_dc_bang();
	$xe   = skb_ten_xe();
	$kieu = skb_dc_kieu( $dc );
	$tr   = skb_dc_truoc();
	$cat  = add_query_arg( 'nguon', 'web', SKB_CATALOG_URL . '/c/dung-chung-moi-xe' );
	echo '<section class="dc-duoi" aria-label="Các kiểu lắp cho xe này">' . "\n";
	/* 0.26.0 — bài giới thiệu chia theo kiểu lắp: mỗi kiểu một phần <details>, tất cả in sẵn trong HTML.
	   Phần của kiểu đang chọn mở và đứng đầu (script đổi open + order); các phần khác thu gọn, bấm tiêu đề là mở. */
	printf( '<p class="dc-bai-gt">%s</p>', esc_html( sprintf( 'Phần giới thiệu bên dưới chia theo %d kiểu lắp của %s. Phần của kiểu anh chị đang chọn được mở sẵn; bấm vào tên kiểu khác để xem thêm.', count( $kieu ), $xe ) ) );
	echo '<div class="dc-bai">' . "\n";
	$thu = 0;
	foreach ( $kieu as $pan => $nd ) {
		$dau = 0 === $thu;
		printf(
			'<details class="dc-ve%s" data-dc-ve="%s" style="order:%d"%s><summary><h2>%s</h2><span class="dc-ve-dc">Đang chọn</span><span class="dc-ve-mt" aria-hidden="true"></span></summary><div class="dc-ve-nd">',
			$dau ? ' on' : '',
			esc_attr( $pan ),
			(int) $thu,
			$dau ? ' open' : '',
			esc_html( sprintf( 'rieng' === $nd ? '%s cho %s' : '%s trên %s', skb_dc_ten_kieu( $nd ), $xe ) )
		);
		echo '<div class="dc-ve-l"><div><h3>Điểm khác biệt</h3><ul class="dc-ds">';
		foreach ( skb_dc_diem( $nd ) as $d ) {
			echo '<li>' . esc_html( $d ) . '</li>';
		}
		echo '</ul></div>';
		if ( 'rieng' !== $nd ) {
			echo '<div>';
			$dau_goi = true;
			foreach ( $bang[ $nd ]['goi'] as $g ) {
				$mon = skb_dc_mon( $nd, (int) $g[0] );
				if ( ! $mon ) {
					continue;
				}
				printf( '<div data-dc-tp="%s-%d"%s><h3>Gói %d chi tiết gồm</h3><ul class="dc-ds">', esc_attr( $nd ), (int) $g[0], $dau_goi ? '' : ' hidden', (int) $g[0] );
				foreach ( $mon as $m ) {
					echo '<li>' . esc_html( $m[0] ) . ( $m[1] ? ' <span class="dc-moi">thêm so với gói ' . (int) $tr[ (int) $g[0] ][0] . '</span>' : '' ) . '</li>';
				}
				echo '</ul></div>';
				$dau_goi = false;
			}
			echo '</div>';
		} else {
			echo '<div><h3>Các món trong gói</h3><p class="dc-chu">Chọn một gói ở trên để xem danh sách các món đi kèm của gói đó.</p></div>';
		}
		echo '</div>';
		/* Chữ riêng của kiểu này trên xe này: phần đầu là bài viết của xe; S2Pro, S3Pro là đoạn ngắn nhập ở khung "Dòng dùng chung trên xe này" */
		if ( $dau && '' !== trim( (string) $bai ) ) {
			echo '<div class="entry mx-desc dc-ve-bai">' . $bai . '</div>'; // phpcs:ignore -- đã qua bộ lọc the_content
		}
		if ( 'rieng' !== $nd ) {
			$chu = $dau ? '' : trim( (string) get_post_meta( get_the_ID(), '_skb_dc_chu_' . $nd, true ) );
			if ( '' !== $chu ) {
				echo '<div class="entry dc-ve-chu">';
				foreach ( preg_split( '/\R{2,}/u', $chu ) as $doan ) {
					if ( '' !== trim( $doan ) ) {
						echo '<p>' . nl2br( esc_html( trim( $doan ) ) ) . '</p>';
					}
				}
				echo '</div>';
			}
			printf(
				'<p class="dc-ve-them">%s <a class="cat-link" href="%s" target="_blank" rel="noopener">Xem dòng Dùng chung trên catalog <span aria-hidden="true">↗</span><span class="sr-only"> (mở catalog ở tab mới)</span></a></p>',
				esc_html( sprintf( '%s giống nhau ở mọi dòng xe: cùng các món, cùng giá, cùng bảo hành 24 tháng.', $bang[ $nd ]['ten'] ) ),
				esc_url( $cat )
			);
		}
		echo '</div></details>' . "\n";
		$thu++;
	}
	echo '</div>' . "\n";

	/* Bảng so sánh: chữ thật, luôn hiện */
	$cot = array();
	foreach ( $kieu as $pan => $nd ) {
		if ( 'rieng' === $nd ) {
			$n     = 0;
			foreach ( $goi as $g ) {
				if ( preg_match( '/^\s*(\d+)\s*chi\s*tiết/iu', $g['ten'], $m ) && (int) $m[1] >= 10 ) {
					$n++;
				}
			}
			$cot[ $pan ] = array( 'Ốp theo xe', 'Riêng ' . $xe, 'Tấm ốp thay đúng vị trí ốp zin của xe', 'Theo từng gói của xe', ( $n ? $n : count( $goi ) ) . ' gói', skb_dc_tu_xe( $goi, $gp ) );
		} else {
			$ct = array();
			foreach ( $bang[ $nd ]['goi'] as $g ) {
				$ct[] = (int) $g[0];
			}
			$cot[ $pan ] = array(
				$bang[ $nd ]['ten'],
				'Mọi dòng xe',
				's3pro' === $nd ? 'Thanh LED họa tiết chấm sao, 2 dài và 2 ngắn' : 'Dây LED vỏ đen dài 90 cm, tắt đèn không lộ',
				's3pro' === $nd ? 'Giống S2Pro' : 'Taplo, yên ngựa, vành loa, đèn gầm, tay mở cửa, hộc để cốc',
				count( $ct ) . ' gói, ' . min( $ct ) . ' đến ' . max( $ct ) . ' chi tiết',
				'rieng' === $pan ? skb_dc_tu_xe( $goi, $gp ) : skb_dc_tu( $nd ),
			);
		}
	}
	$hang = array( 1 => 'Dành cho', 2 => 'Đèn tapli cửa', 3 => 'Các món khác', 4 => 'Các gói' );
	printf( '<div class="dc-ss"><h2>%s</h2><div class="dc-ss-k"><table class="dc-bang"><thead><tr><th scope="col"><span class="sr-only">Tiêu chí</span></th>', esc_html( sprintf( 'So sánh %d kiểu lắp cho %s', count( $cot ), $xe ) ) );
	$dau = true;
	foreach ( $cot as $pan => $c ) {
		printf( '<th scope="col" data-dc-cot="%s"%s>%s</th>', esc_attr( $pan ), $dau ? ' class="on"' : '', esc_html( $c[0] ) );
		$dau = false;
	}
	echo '</tr></thead><tbody>';
	foreach ( $hang as $i => $h ) {
		echo '<tr><th scope="row">' . esc_html( $h ) . '</th>';
		$dau = true;
		foreach ( $cot as $pan => $c ) {
			printf( '<td data-dc-cot="%s"%s>%s</td>', esc_attr( $pan ), $dau ? ' class="on"' : '', esc_html( $c[ $i ] ) );
			$dau = false;
		}
		echo '</tr>';
	}
	echo '<tr><th scope="row">Giá từ</th>';
	$dau = true;
	foreach ( $cot as $pan => $c ) {
		printf( '<td data-dc-cot="%s"%s>%s</td>', esc_attr( $pan ), $dau ? ' class="on"' : '', $c[5] ? esc_html( skb_tien( $c[5] ) ) : 'Liên hệ' );
		$dau = false;
	}
	echo '</tr><tr><th scope="row">Bảo hành</th>';
	$dau = true;
	foreach ( $cot as $pan => $c ) {
		printf( '<td data-dc-cot="%s"%s>24 tháng</td>', esc_attr( $pan ), $dau ? ' class="on"' : '' );
		$dau = false;
	}
	echo '</tr></tbody></table></div></div>' . "\n" . '</section>' . "\n";
}

/* ---------- Ảnh riêng của S2Pro, S3Pro trên từng xe ---------- */

/** Bộ ảnh nhỏ thay thế, in sau bộ ảnh chính của trang mẫu xe; chọn thẻ nào thì script hiện bộ của thẻ đó */
function skb_dc_anh_xe( $post_id, $dc ) {
	foreach ( skb_dc_kieu( $dc ) as $pan => $nd ) {
		if ( 'rieng' === $nd ) {
			continue;
		}
		$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_skb_dc_anh_' . $nd, true ) ) ) );
		if ( ! $ids ) {
			/* 0.26.0 — kiểu này chưa có ảnh riêng trên xe: không để album của kiểu khác đứng thay, hiện ảnh sản phẩm của chính kiểu đó
			   kèm dòng chú thích. Thẻ đầu tiên (pan "rieng") dùng bộ ảnh chính của xe nên không cần. */
			if ( 'rieng' === $pan ) {
				continue;
			}
			$u = get_template_directory_uri() . '/assets/img/dc/' . $nd . '.webp';
			printf(
				'<div class="mx-thumbs" data-dc-anh="%1$s" data-dc-chu="%2$s" role="list" hidden><button type="button" class="mx-th" data-src="%3$s" data-srcset="" aria-label="%4$s"><img src="%3$s" alt="" loading="lazy" decoding="async"></button></div>',
				esc_attr( $pan ),
				esc_attr( sprintf( 'Ảnh sản phẩm %s. Ảnh lắp trên %s đang được cập nhật.', skb_dc_ten_kieu( $nd ), skb_ten_xe() ) ),
				esc_url( $u ),
				esc_attr( 'Ảnh sản phẩm ' . skb_dc_ten_kieu( $nd ) )
			);
			continue;
		}
		printf( '<div class="mx-thumbs" data-dc-anh="%s" role="list" hidden>', esc_attr( $pan ) );
		foreach ( $ids as $i => $aid ) {
			$full = wp_get_attachment_image_src( $aid, 'large' );
			if ( ! $full ) {
				continue;
			}
			printf(
				'<button type="button" class="mx-th" data-src="%s" data-srcset="%s" aria-label="%s">%s</button>',
				esc_url( $full[0] ),
				esc_attr( (string) wp_get_attachment_image_srcset( $aid, 'large' ) ),
				esc_attr( 'Ảnh ' . skb_dc_ten_kieu( $nd ) . ' ' . ( $i + 1 ) ),
				wp_get_attachment_image( $aid, 'thumbnail', false, array( 'loading' => 'lazy' ) )
			);
		}
		echo '</div>';
	}
}

add_action( 'add_meta_boxes_mau_xe', function () {
	add_meta_box( 'skb_dc_anh', 'Dòng dùng chung trên xe này (S2Pro, S3Pro): ảnh và đoạn giới thiệu', 'skb_dc_anh_box', 'mau_xe', 'normal', 'default' );
} );

function skb_dc_anh_box( $post ) {
	wp_nonce_field( 'skb_dc_anh', 'skb_dc_anh_n' );
	?>
	<style>
		.skb-dca-list{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0;padding:0;list-style:none;min-height:20px}
		.skb-dca-list li{position:relative;width:96px;height:96px;border-radius:8px;overflow:hidden;background:#f0f0f1;cursor:move;margin:0}
		.skb-dca-list img{width:100%;height:100%;object-fit:cover;display:block}
		.skb-dca-list .skb-x{position:absolute;top:4px;right:4px;width:22px;height:22px;border:0;border-radius:50%;background:rgba(0,0,0,.65);color:#fff;cursor:pointer;line-height:20px;padding:0}
		.skb-dca-list .ui-sortable-placeholder{visibility:visible!important;border:2px dashed #2271b1;background:transparent}
	</style>
	<p>Ảnh chụp <strong>S2Pro</strong> và <strong>S3Pro</strong> lắp trên chính mẫu xe này. Ở trang mẫu xe, khách bấm thẻ S2Pro hoặc S3Pro thì bộ ảnh phía trên đổi sang ảnh tương ứng. Để trống thì trang hiện ảnh sản phẩm của kiểu đó kèm dòng "ảnh lắp trên xe đang được cập nhật". (Ảnh của kiểu Ốp theo xe vẫn là khung "Bộ ảnh sản phẩm".)</p>
	<?php foreach ( array( 's2pro' => 'S2Pro', 's3pro' => 'S3Pro' ) as $k => $ten ) :
		$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_skb_dc_anh_' . $k, true ) ) ) );
		?>
		<div class="skb-dca" data-ten="<?php echo esc_attr( $ten ); ?>">
			<p><strong><?php echo esc_html( $ten ); ?></strong> <span class="description skb-dca-dem"></span></p>
			<input type="hidden" class="skb-dca-ids" name="skb_dc_anh[<?php echo esc_attr( $k ); ?>]" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
			<ul class="skb-dca-list">
				<?php foreach ( $ids as $id ) : $t = wp_get_attachment_image_src( $id, 'thumbnail' ); if ( ! $t ) { continue; } ?>
					<li data-id="<?php echo (int) $id; ?>"><img src="<?php echo esc_url( $t[0] ); ?>" alt=""><button type="button" class="skb-x" aria-label="Bỏ ảnh">×</button></li>
				<?php endforeach; ?>
			</ul>
			<p><button type="button" class="button skb-dca-add">+ Thêm ảnh <?php echo esc_html( $ten ); ?></button></p>
			<p><label for="skb-dc-chu-<?php echo esc_attr( $k ); ?>"><strong>Đoạn giới thiệu <?php echo esc_html( $ten ); ?> trên xe này</strong></label><br>
			<textarea id="skb-dc-chu-<?php echo esc_attr( $k ); ?>" name="skb_dc_chu[<?php echo esc_attr( $k ); ?>]" rows="4" class="large-text" placeholder="Viết ngắn, chỉ điều riêng của xe này: lắp ở những vị trí nào, có gì cần lưu ý. Phần giống nhau ở mọi xe đã có sẵn, không cần chép lại."><?php echo esc_textarea( (string) get_post_meta( $post->ID, '_skb_dc_chu_' . $k, true ) ); ?></textarea>
			<span class="description">Hiện trong phần "<?php echo esc_html( $ten ); ?> trên xe này" của bài giới thiệu. Để trống thì phần đó chỉ có nội dung chung. Xuống dòng hai lần để tách đoạn.</span></p>
		</div>
	<?php endforeach; ?>
	<script>
	jQuery(function ($) {
		$('.skb-dca').each(function () {
			var $box = $(this), $list = $box.find('.skb-dca-list'), $ids = $box.find('.skb-dca-ids'), frame;
			function dem() { var n = $list.children('li').length; $box.find('.skb-dca-dem').text(n ? n + ' ảnh' : 'Chưa có ảnh'); }
			function sync() {
				var ids = $list.children('li').map(function () { return $(this).data('id'); }).get();
				$ids.val(ids.join(',')).trigger('change');
				dem();
				try { if (window.wp && wp.data && wp.data.dispatch('core/editor')) { wp.data.dispatch('core/editor').editPost({ meta: { _skb_bo_anh_doi: String(Date.now()) } }); } } catch (e) {}
			}
			$list.sortable({ update: sync, tolerance: 'pointer' });
			$list.on('click', '.skb-x', function () { $(this).closest('li').remove(); sync(); });
			$box.find('.skb-dca-add').on('click', function (e) {
				e.preventDefault();
				if (!frame) {
					frame = wp.media({ title: 'Chọn ảnh ' + $box.data('ten') + ' trên xe này', button: { text: 'Thêm ảnh' }, library: { type: 'image' }, multiple: 'add' });
					frame.on('select', function () {
						var have = $ids.val().split(',');
						frame.state().get('selection').each(function (a) {
							var id = String(a.id); if (have.indexOf(id) >= 0) return;
							var s = a.get('sizes'), u = (s && s.thumbnail ? s.thumbnail.url : a.get('url'));
							$list.append($('<li>').attr('data-id', id).data('id', id).append($('<img alt="">').attr('src', u), '<button type="button" class="skb-x" aria-label="Bỏ ảnh">×</button>'));
						});
						sync();
					});
				}
				frame.open();
			});
			dem();
		});
	});
	</script>
	<?php
}

add_action( 'save_post_mau_xe', function ( $post_id ) {
	if ( ! isset( $_POST['skb_dc_anh_n'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['skb_dc_anh_n'] ) ), 'skb_dc_anh' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$v = isset( $_POST['skb_dc_anh'] ) && is_array( $_POST['skb_dc_anh'] ) ? wp_unslash( $_POST['skb_dc_anh'] ) : array(); // phpcs:ignore
	foreach ( array( 's2pro', 's3pro' ) as $k ) {
		$ids = array_filter( array_map( 'absint', explode( ',', isset( $v[ $k ] ) ? (string) $v[ $k ] : '' ) ) );
		$ids = array_values( array_unique( array_filter( $ids, function ( $id ) {
			return 'attachment' === get_post_type( $id );
		} ) ) );
		if ( $ids ) {
			update_post_meta( $post_id, '_skb_dc_anh_' . $k, implode( ',', $ids ) );
		} else {
			delete_post_meta( $post_id, '_skb_dc_anh_' . $k );
		}
		$chu = isset( $_POST['skb_dc_chu'][ $k ] ) ? trim( sanitize_textarea_field( wp_unslash( $_POST['skb_dc_chu'][ $k ] ) ) ) : ''; // phpcs:ignore
		if ( '' !== $chu ) {
			update_post_meta( $post_id, '_skb_dc_chu_' . $k, $chu );
		} else {
			delete_post_meta( $post_id, '_skb_dc_chu_' . $k );
		}
	}
} );

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
