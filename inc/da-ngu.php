<?php
/**
 * Đa ngôn ngữ (0.14.0, 11/11/2026) — tự viết trong theme, không dùng plugin.
 *
 * - Tiếng Việt là bản gốc, không có tiền tố. Bản nước ngoài: /en/ /zh/ /ko/ /th/ /de/ /hi/.
 * - Trang có bản dịch: trang chủ, danh sách mẫu xe, hãng xe, từng mẫu xe, Giới thiệu, Liên hệ, 404.
 *   Trang khác (tin tức, đại lý, đặt lịch, kích hoạt…) mở bằng tiền tố nước ngoài → chuyển về bản tiếng Việt
 *   (đặt lịch, đại lý → trang Liên hệ của ngôn ngữ đó).
 * - Bản nước ngoài ẩn Đặt lịch và Đại lý lắp đặt toàn quốc, thay bằng nút nhắn WhatsApp.
 * - Nội dung mẫu xe bản nước ngoài là "bản gọn": tên xe (meta _skb_ten_xe), gói + giá, tóm tắt theo mẫu câu
 *   của từng ngôn ngữ (inc/da-ngu-tu-dien.php), thông số, ảnh, hộp 3D catalog.
 * - Chữ giao diện: skb__( 'chữ tiếng Việt' ) trả bản dịch theo ngôn ngữ đang xem (từ điển inc/da-ngu-tu-dien.php).
 */

defined( 'ABSPATH' ) || exit;

/** Các ngôn ngữ: mã → tên, nhãn ngắn, hreflang, og:locale, dấu tách nghìn */
function skb_ngu_ds() {
	return array(
		'vi' => array( 'ten' => 'Tiếng Việt', 'ngan' => 'VI', 'hl' => 'vi', 'og' => 'vi_VN', 'tach' => '.' ),
		'en' => array( 'ten' => 'English', 'ngan' => 'EN', 'hl' => 'en', 'og' => 'en_US', 'tach' => ',' ),
		'zh' => array( 'ten' => '简体中文', 'ngan' => '中文', 'hl' => 'zh-Hans', 'og' => 'zh_CN', 'tach' => ',' ),
		'ko' => array( 'ten' => '한국어', 'ngan' => 'KO', 'hl' => 'ko', 'og' => 'ko_KR', 'tach' => ',' ),
		'th' => array( 'ten' => 'ไทย', 'ngan' => 'TH', 'hl' => 'th', 'og' => 'th_TH', 'tach' => ',' ),
		'de' => array( 'ten' => 'Deutsch', 'ngan' => 'DE', 'hl' => 'de', 'og' => 'de_DE', 'tach' => '.' ),
		'hi' => array( 'ten' => 'हिन्दी', 'ngan' => 'HI', 'hl' => 'hi', 'og' => 'hi_IN', 'tach' => ',' ),
	);
}

/** Mã ngôn ngữ nước ngoài, dùng trong biểu thức đường dẫn */
function skb_ngu_ma_ngoai() {
	return array_values( array_diff( array_keys( skb_ngu_ds() ), array( 'vi' ) ) );
}

/* ---------- Nhận ngôn ngữ từ đường dẫn (chạy ngay khi nạp theme, trước khi WordPress đọc đường dẫn) ---------- */
$GLOBALS['skb_ngu']     = 'vi';
$GLOBALS['skb_ngu_tat'] = 0;
( function () {
	if ( is_admin() || wp_doing_ajax() || wp_doing_cron() || ( defined( 'WP_CLI' ) && WP_CLI ) || empty( $_SERVER['REQUEST_URI'] ) ) {
		return;
	}
	$uri  = (string) $_SERVER['REQUEST_URI']; // phpcs:ignore
	$path = (string) wp_parse_url( $uri, PHP_URL_PATH );
	$qs   = (string) wp_parse_url( $uri, PHP_URL_QUERY );
	$base = untrailingslashit( (string) wp_parse_url( get_option( 'home' ), PHP_URL_PATH ) );
	$rel  = ( '' !== $base && 0 === strpos( $path, $base ) ) ? substr( $path, strlen( $base ) ) : $path;
	if ( ! preg_match( '#^/(' . implode( '|', skb_ngu_ma_ngoai() ) . ')(/|$)#', $rel, $m ) ) {
		return;
	}
	if ( '' === $m[2] ) { // "/en" → "/en/"
		header( 'Location: ' . $base . '/' . $m[1] . '/' . ( '' !== $qs ? '?' . $qs : '' ), true, 301 );
		exit;
	}
	$GLOBALS['skb_ngu']          = $m[1];
	$GLOBALS['skb_ngu_uri_goc']  = $uri;
	$_SERVER['REQUEST_URI']      = $base . substr( $rel, strlen( $m[1] ) + 1 ) . ( '' !== $qs ? '?' . $qs : '' );
} )();

/** Ngôn ngữ đang xem: vi | en | zh | ko | th | de | hi */
function skb_ngu() {
	return $GLOBALS['skb_ngu'];
}

/** Đang xem bản nước ngoài? */
function skb_ngu_ngoai() {
	return 'vi' !== $GLOBALS['skb_ngu'];
}

/** Chạy $f mà không thêm tiền tố ngôn ngữ vào đường dẫn (để lấy đường dẫn tiếng Việt, hoặc dữ liệu lưu tạm dùng chung) */
function skb_ngu_tat( $f ) {
	$GLOBALS['skb_ngu_tat']++;
	try {
		return $f();
	} finally {
		$GLOBALS['skb_ngu_tat']--;
	}
}

function skb_ngu_dang_them() {
	return skb_ngu_ngoai() && 0 === $GLOBALS['skb_ngu_tat'];
}

/** Gốc web không tiền tố: https://sklight.vn */
function skb_ngu_goc() {
	return untrailingslashit( (string) get_option( 'home' ) );
}

/** Đổi đường dẫn tiếng Việt (trong web) sang ngôn ngữ $lang. Đường dẫn ngoài web giữ nguyên. */
function skb_ngu_url( $url, $lang = null ) {
	$lang = $lang ? $lang : skb_ngu();
	$url  = skb_ngu_bo( $url );
	if ( 'vi' === $lang ) {
		return $url;
	}
	$goc = skb_ngu_goc();
	if ( 0 !== strpos( $url, $goc ) ) {
		return $url;
	}
	$con = substr( $url, strlen( $goc ) );
	if ( '' === $con || '?' === $con[0] ) {
		$con = '/' . $con;
	}
	return $goc . '/' . $lang . $con;
}

/** Bỏ tiền tố ngôn ngữ khỏi đường dẫn trong web */
function skb_ngu_bo( $url ) {
	$goc = skb_ngu_goc();
	if ( 0 !== strpos( (string) $url, $goc ) ) {
		return $url;
	}
	$con = substr( $url, strlen( $goc ) );
	$con = preg_replace( '#^/(' . implode( '|', skb_ngu_ma_ngoai() ) . ')(?=/|$|\?)#', '', $con );
	return $goc . $con;
}

/* ---------- Thêm tiền tố vào đường dẫn các trang có bản dịch ---------- */
add_filter( 'home_url', function ( $url, $path ) {
	if ( skb_ngu_dang_them() && in_array( (string) $path, array( '', '/' ), true ) ) {
		return skb_ngu_url( $url );
	}
	return $url;
}, 20, 2 );
add_filter( 'post_type_link', function ( $url, $post ) {
	return ( skb_ngu_dang_them() && 'mau_xe' === $post->post_type ) ? skb_ngu_url( $url ) : $url;
}, 20, 2 );
add_filter( 'post_type_archive_link', function ( $url, $type ) {
	return ( skb_ngu_dang_them() && 'mau_xe' === $type ) ? skb_ngu_url( $url ) : $url;
}, 20, 2 );
add_filter( 'term_link', function ( $url, $term, $tax ) {
	return ( skb_ngu_dang_them() && 'hang_xe' === $tax ) ? skb_ngu_url( $url ) : $url;
}, 20, 3 );
add_filter( 'page_link', function ( $url, $id ) {
	return ( skb_ngu_dang_them() && in_array( (int) $id, skb_ngu_trang_dich(), true ) ) ? skb_ngu_url( $url ) : $url;
}, 20, 2 );
add_filter( 'paginate_links', function ( $url ) {
	return skb_ngu_dang_them() ? skb_ngu_url( $url ) : $url;
} );

/** ID trang Giới thiệu và Liên hệ (có bản dịch) */
function skb_ngu_trang_dich() {
	static $ids = null;
	if ( null === $ids ) {
		$ids = array();
		foreach ( array( 'gioi-thieu', 'lien-he' ) as $slug ) {
			$p = get_page_by_path( $slug );
			if ( $p ) {
				$ids[ $slug ] = (int) $p->ID;
			}
		}
	}
	return array_values( $ids );
}

/** Trang Liên hệ của ngôn ngữ đang xem */
function skb_ngu_lien_he() {
	$p = get_page_by_path( 'lien-he' );
	return $p ? get_permalink( $p ) : home_url( '/' );
}

/** Trang đang xem có bản dịch không */
function skb_ngu_dich_duoc() {
	if ( is_404() || is_front_page() || is_post_type_archive( 'mau_xe' ) || is_tax( 'hang_xe' ) || is_singular( 'mau_xe' ) ) {
		return true;
	}
	return is_page() && in_array( (int) get_queried_object_id(), skb_ngu_trang_dich(), true );
}

/* Bản nước ngoài: trang không có bản dịch → chuyển về bản tiếng Việt; đặt lịch, đại lý, kích hoạt, tuyển đại lý → Liên hệ */
add_action( 'template_redirect', function () {
	if ( ! skb_ngu_ngoai() || skb_ngu_dich_duoc() ) {
		return;
	}
	if ( is_page( 'dat-lich' ) || is_post_type_archive( 'dai_ly' ) || is_singular( 'dai_ly' ) || is_page( 'kich-hoat-bao-hanh' ) || is_page( 'tuyen-dai-ly' ) ) {
		wp_safe_redirect( skb_ngu_lien_he(), 302 );
		exit;
	}
	wp_safe_redirect( skb_ngu_goc() . $_SERVER['REQUEST_URI'], 302 ); // phpcs:ignore
	exit;
}, 0 );

/* Bản nước ngoài không để WordPress tự "sửa" đường dẫn (tránh vòng lặp chuyển hướng) */
add_filter( 'redirect_canonical', function ( $r ) {
	return skb_ngu_ngoai() ? false : $r;
} );

/* <html lang="…"> và lớp body */
add_filter( 'language_attributes', function ( $out ) {
	if ( ! skb_ngu_ngoai() ) {
		return $out;
	}
	$ds = skb_ngu_ds();
	return preg_replace( '/lang="[^"]*"/', 'lang="' . esc_attr( $ds[ skb_ngu() ]['hl'] ) . '"', $out );
} );
add_filter( 'body_class', function ( $c ) {
	$c[] = 'ngu-' . skb_ngu();
	return $c;
} );

/** og:locale của ngôn ngữ đang xem */
function skb_ngu_og() {
	$ds = skb_ngu_ds();
	return $ds[ skb_ngu() ]['og'];
}

/* ---------- Dịch chữ ---------- */

/** Từ điển (nạp một lần): array( 'chữ tiếng Việt' => array( 'en' => …, 'zh' => …, … ) ) */
function skb_ngu_tu_dien() {
	static $d = null;
	if ( null === $d ) {
		$d = require get_template_directory() . '/inc/da-ngu-tu-dien.php';
	}
	return $d;
}

/** Dịch một chữ tiếng Việt sang ngôn ngữ đang xem (không có trong từ điển → giữ nguyên) */
function skb__( $vi, $lang = null ) {
	$lang = $lang ? $lang : skb_ngu();
	if ( 'vi' === $lang ) {
		return $vi;
	}
	$d = skb_ngu_tu_dien();
	return isset( $d[ $vi ][ $lang ] ) && '' !== $d[ $vi ][ $lang ] ? $d[ $vi ][ $lang ] : $vi;
}

/** Như skb__() rồi in ra (đã escape) */
function skb_e( $vi ) {
	echo esc_html( skb__( $vi ) );
}

/* ---------- Mẫu xe bản nước ngoài ---------- */

/** Tên xe ngắn để ghép câu: meta _skb_ten_xe, không có thì bỏ chữ "LED viền nội thất" khỏi tiêu đề */
function skb_ten_xe( $post_id = 0 ) {
	$post_id = $post_id ? $post_id : get_the_ID();
	$t       = trim( (string) get_post_meta( $post_id, '_skb_ten_xe', true ) );
	if ( '' !== $t ) {
		return $t;
	}
	return function_exists( 'skb_ten_ngan' ) ? skb_ten_ngan( get_the_title( $post_id ) ) : get_the_title( $post_id );
}

/* Meta tên xe sửa được trong trình soạn / REST (Javis điền) */
add_action( 'init', function () {
	register_post_meta( 'mau_xe', '_skb_ten_xe', array(
		'type'          => 'string',
		'single'        => true,
		'show_in_rest'  => true,
		'auth_callback' => function ( $a, $k, $id ) {
			return current_user_can( 'edit_post', $id );
		},
	) );
}, 20 );

/** Tiêu đề mẫu xe theo ngôn ngữ đang xem */
function skb_ngu_tieu_de_xe( $post_id = 0 ) {
	return sprintf( skb__( 'LED nội thất %s' ), skb_ten_xe( $post_id ) );
}

/** 10700000 → "10,700,000 ₫" (dấu tách theo ngôn ngữ) */
function skb_ngu_tien( $v ) {
	if ( ! skb_ngu_ngoai() ) {
		return function_exists( 'skb_tien' ) ? skb_tien( $v ) : '';
	}
	$ds = skb_ngu_ds();
	return $v > 0 ? number_format( (float) $v, 0, '', $ds[ skb_ngu() ]['tach'] ) . ' ₫' : '';
}

/** Gói "bộ đèn" (từ 10 chi tiết) — dùng cho "giá từ" ở câu tóm tắt và SEO */
function skb_ngu_la_bo( $ten ) {
	return (bool) preg_match( '/^\s*(\d+)\s*chi\s*ti[eế]t/iu', skb_ngu_nfc( $ten ), $m ) && (int) $m[1] >= 10;
}

/** array( số bộ, giá thấp nhất của bộ ) */
function skb_ngu_bo_gia( $post_id = 0 ) {
	$goi = function_exists( 'skb_goi' ) ? skb_goi( $post_id ) : array();
	$bo  = array_filter( $goi, function ( $g ) {
		return skb_ngu_la_bo( $g['ten'] );
	} );
	if ( ! $bo ) {
		$bo = $goi;
	}
	$gia = array_filter( wp_list_pluck( $bo, 'gia' ) );
	return array( count( $bo ), $gia ? min( $gia ) : 0 );
}

/** Chuẩn hoá chữ Việt về dạng dựng sẵn (NFC): dữ liệu web cũ có chữ "tổ hợp" (ê + dấu sắc rời) */
function skb_ngu_nfc( $s ) {
	$s = (string) $s;
	if ( class_exists( 'Normalizer' ) ) {
		$n = Normalizer::normalize( $s, Normalizer::FORM_C );
		return false === $n ? $s : $n;
	}
	static $map = null;
	if ( null === $map ) {
		$map = array( "\u{61}\u{306}\u{300}" => 'ằ', "\u{61}\u{306}\u{301}" => 'ắ', "\u{61}\u{306}\u{303}" => 'ẵ', "\u{61}\u{306}\u{309}" => 'ẳ', "\u{61}\u{323}\u{306}" => 'ặ', "\u{61}\u{302}\u{300}" => 'ầ', "\u{61}\u{302}\u{301}" => 'ấ', "\u{61}\u{302}\u{303}" => 'ẫ', "\u{61}\u{302}\u{309}" => 'ẩ', "\u{61}\u{323}\u{302}" => 'ậ', "\u{65}\u{302}\u{300}" => 'ề', "\u{65}\u{302}\u{301}" => 'ế', "\u{65}\u{302}\u{303}" => 'ễ', "\u{65}\u{302}\u{309}" => 'ể', "\u{65}\u{323}\u{302}" => 'ệ', "\u{6f}\u{302}\u{300}" => 'ồ', "\u{6f}\u{302}\u{301}" => 'ố', "\u{6f}\u{302}\u{303}" => 'ỗ', "\u{6f}\u{302}\u{309}" => 'ổ', "\u{6f}\u{323}\u{302}" => 'ộ', "\u{6f}\u{31b}\u{300}" => 'ờ', "\u{6f}\u{31b}\u{301}" => 'ớ', "\u{6f}\u{31b}\u{303}" => 'ỡ', "\u{6f}\u{31b}\u{309}" => 'ở', "\u{6f}\u{31b}\u{323}" => 'ợ', "\u{75}\u{31b}\u{300}" => 'ừ', "\u{75}\u{31b}\u{301}" => 'ứ', "\u{75}\u{31b}\u{303}" => 'ữ', "\u{75}\u{31b}\u{309}" => 'ử', "\u{75}\u{31b}\u{323}" => 'ự', "\u{41}\u{306}\u{300}" => 'Ằ', "\u{41}\u{306}\u{301}" => 'Ắ', "\u{41}\u{306}\u{303}" => 'Ẵ', "\u{41}\u{306}\u{309}" => 'Ẳ', "\u{41}\u{323}\u{306}" => 'Ặ', "\u{41}\u{302}\u{300}" => 'Ầ', "\u{41}\u{302}\u{301}" => 'Ấ', "\u{41}\u{302}\u{303}" => 'Ẫ', "\u{41}\u{302}\u{309}" => 'Ẩ', "\u{41}\u{323}\u{302}" => 'Ậ', "\u{45}\u{302}\u{300}" => 'Ề', "\u{45}\u{302}\u{301}" => 'Ế', "\u{45}\u{302}\u{303}" => 'Ễ', "\u{45}\u{302}\u{309}" => 'Ể', "\u{45}\u{323}\u{302}" => 'Ệ', "\u{4f}\u{302}\u{300}" => 'Ồ', "\u{4f}\u{302}\u{301}" => 'Ố', "\u{4f}\u{302}\u{303}" => 'Ỗ', "\u{4f}\u{302}\u{309}" => 'Ổ', "\u{4f}\u{323}\u{302}" => 'Ộ', "\u{4f}\u{31b}\u{300}" => 'Ờ', "\u{4f}\u{31b}\u{301}" => 'Ớ', "\u{4f}\u{31b}\u{303}" => 'Ỡ', "\u{4f}\u{31b}\u{309}" => 'Ở', "\u{4f}\u{31b}\u{323}" => 'Ợ', "\u{55}\u{31b}\u{300}" => 'Ừ', "\u{55}\u{31b}\u{301}" => 'Ứ', "\u{55}\u{31b}\u{303}" => 'Ữ', "\u{55}\u{31b}\u{309}" => 'Ử', "\u{55}\u{31b}\u{323}" => 'Ự', "\u{61}\u{300}" => 'à', "\u{61}\u{301}" => 'á', "\u{61}\u{303}" => 'ã', "\u{61}\u{309}" => 'ả', "\u{61}\u{323}" => 'ạ', "\u{103}\u{300}" => 'ằ', "\u{103}\u{301}" => 'ắ', "\u{103}\u{303}" => 'ẵ', "\u{103}\u{309}" => 'ẳ', "\u{103}\u{323}" => 'ặ', "\u{e2}\u{300}" => 'ầ', "\u{e2}\u{301}" => 'ấ', "\u{e2}\u{303}" => 'ẫ', "\u{e2}\u{309}" => 'ẩ', "\u{e2}\u{323}" => 'ậ', "\u{65}\u{300}" => 'è', "\u{65}\u{301}" => 'é', "\u{65}\u{303}" => 'ẽ', "\u{65}\u{309}" => 'ẻ', "\u{65}\u{323}" => 'ẹ', "\u{ea}\u{300}" => 'ề', "\u{ea}\u{301}" => 'ế', "\u{ea}\u{303}" => 'ễ', "\u{ea}\u{309}" => 'ể', "\u{ea}\u{323}" => 'ệ', "\u{69}\u{300}" => 'ì', "\u{69}\u{301}" => 'í', "\u{69}\u{303}" => 'ĩ', "\u{69}\u{309}" => 'ỉ', "\u{69}\u{323}" => 'ị', "\u{6f}\u{300}" => 'ò', "\u{6f}\u{301}" => 'ó', "\u{6f}\u{303}" => 'õ', "\u{6f}\u{309}" => 'ỏ', "\u{6f}\u{323}" => 'ọ', "\u{f4}\u{300}" => 'ồ', "\u{f4}\u{301}" => 'ố', "\u{f4}\u{303}" => 'ỗ', "\u{f4}\u{309}" => 'ổ', "\u{f4}\u{323}" => 'ộ', "\u{1a1}\u{300}" => 'ờ', "\u{1a1}\u{301}" => 'ớ', "\u{1a1}\u{303}" => 'ỡ', "\u{1a1}\u{309}" => 'ở', "\u{1a1}\u{323}" => 'ợ', "\u{75}\u{300}" => 'ù', "\u{75}\u{301}" => 'ú', "\u{75}\u{303}" => 'ũ', "\u{75}\u{309}" => 'ủ', "\u{75}\u{323}" => 'ụ', "\u{1b0}\u{300}" => 'ừ', "\u{1b0}\u{301}" => 'ứ', "\u{1b0}\u{303}" => 'ữ', "\u{1b0}\u{309}" => 'ử', "\u{1b0}\u{323}" => 'ự', "\u{79}\u{300}" => 'ỳ', "\u{79}\u{301}" => 'ý', "\u{79}\u{303}" => 'ỹ', "\u{79}\u{309}" => 'ỷ', "\u{79}\u{323}" => 'ỵ', "\u{41}\u{300}" => 'À', "\u{41}\u{301}" => 'Á', "\u{41}\u{303}" => 'Ã', "\u{41}\u{309}" => 'Ả', "\u{41}\u{323}" => 'Ạ', "\u{102}\u{300}" => 'Ằ', "\u{102}\u{301}" => 'Ắ', "\u{102}\u{303}" => 'Ẵ', "\u{102}\u{309}" => 'Ẳ', "\u{102}\u{323}" => 'Ặ', "\u{c2}\u{300}" => 'Ầ', "\u{c2}\u{301}" => 'Ấ', "\u{c2}\u{303}" => 'Ẫ', "\u{c2}\u{309}" => 'Ẩ', "\u{c2}\u{323}" => 'Ậ', "\u{45}\u{300}" => 'È', "\u{45}\u{301}" => 'É', "\u{45}\u{303}" => 'Ẽ', "\u{45}\u{309}" => 'Ẻ', "\u{45}\u{323}" => 'Ẹ', "\u{ca}\u{300}" => 'Ề', "\u{ca}\u{301}" => 'Ế', "\u{ca}\u{303}" => 'Ễ', "\u{ca}\u{309}" => 'Ể', "\u{ca}\u{323}" => 'Ệ', "\u{49}\u{300}" => 'Ì', "\u{49}\u{301}" => 'Í', "\u{49}\u{303}" => 'Ĩ', "\u{49}\u{309}" => 'Ỉ', "\u{49}\u{323}" => 'Ị', "\u{4f}\u{300}" => 'Ò', "\u{4f}\u{301}" => 'Ó', "\u{4f}\u{303}" => 'Õ', "\u{4f}\u{309}" => 'Ỏ', "\u{4f}\u{323}" => 'Ọ', "\u{d4}\u{300}" => 'Ồ', "\u{d4}\u{301}" => 'Ố', "\u{d4}\u{303}" => 'Ỗ', "\u{d4}\u{309}" => 'Ổ', "\u{d4}\u{323}" => 'Ộ', "\u{1a0}\u{300}" => 'Ờ', "\u{1a0}\u{301}" => 'Ớ', "\u{1a0}\u{303}" => 'Ỡ', "\u{1a0}\u{309}" => 'Ở', "\u{1a0}\u{323}" => 'Ợ', "\u{55}\u{300}" => 'Ù', "\u{55}\u{301}" => 'Ú', "\u{55}\u{303}" => 'Ũ', "\u{55}\u{309}" => 'Ủ', "\u{55}\u{323}" => 'Ụ', "\u{1af}\u{300}" => 'Ừ', "\u{1af}\u{301}" => 'Ứ', "\u{1af}\u{303}" => 'Ữ', "\u{1af}\u{309}" => 'Ử', "\u{1af}\u{323}" => 'Ự', "\u{59}\u{300}" => 'Ỳ', "\u{59}\u{301}" => 'Ý', "\u{59}\u{303}" => 'Ỹ', "\u{59}\u{309}" => 'Ỷ', "\u{59}\u{323}" => 'Ỵ', "\u{61}\u{306}" => 'ă', "\u{61}\u{302}" => 'â', "\u{65}\u{302}" => 'ê', "\u{6f}\u{302}" => 'ô', "\u{6f}\u{31b}" => 'ơ', "\u{75}\u{31b}" => 'ư', "\u{41}\u{306}" => 'Ă', "\u{41}\u{302}" => 'Â', "\u{45}\u{302}" => 'Ê', "\u{4f}\u{302}" => 'Ô', "\u{4f}\u{31b}" => 'Ơ', "\u{55}\u{31b}" => 'Ư' );
	}
	return strtr( $s, $map );
}

/** Dịch tên gói: "18 Chi tiết (Ver.2)" → "18-piece kit (Ver.2)"; "Vành loa" → "Speaker ring" */
function skb_ngu_ten_goi( $ten ) {
	if ( ! skb_ngu_ngoai() ) {
		return $ten;
	}
	$ten = trim( preg_replace( '/\s+/u', ' ', skb_ngu_nfc( $ten ) ) );
	if ( preg_match( '/^(\d+)\s*chi\s*ti[eế]t\s*(.*)$/iu', $ten, $m ) ) {
		$them = trim( $m[2] );
		$them = trim( preg_replace( '/^[–\-]\s*/u', '', $them ) );
		$dich = '';
		if ( '' !== $them ) {
			$k    = mb_strtolower( trim( $them, '() ' ) );
			$dich = skb__( $k );
			$dich = $dich === $k ? trim( $them, '() ' ) : $dich;
			$dich = ' (' . $dich . ')';
		}
		return sprintf( skb__( 'Bộ %d chi tiết' ), (int) $m[1] ) . $dich;
	}
	$k = mb_strtolower( $ten );
	$d = skb__( $k );
	return $d === $k ? $ten : $d;
}

/** Thay {xe} {n} {gia} trong mẫu câu */
function skb_ngu_dien( $mau, $post_id = 0 ) {
	list( $n, $gia ) = skb_ngu_bo_gia( $post_id );
	return strtr( $mau, array(
		'{xe}'  => skb_ten_xe( $post_id ),
		'{n}'   => (string) max( 1, $n ),
		'{gia}' => $gia ? skb_ngu_tien( $gia ) : skb__( 'Liên hệ' ),
	) );
}

/* ---------- WhatsApp ---------- */

/** Số WhatsApp dạng quốc tế không dấu: 84868055555 */
function skb_wa_so() {
	$so = preg_replace( '/\D/', '', (string) ( function_exists( 'skb_opt' ) ? skb_opt( 'whatsapp', '' ) : '' ) );
	if ( '' === $so ) {
		$so = preg_replace( '/\D/', '', (string) ( function_exists( 'skb_opt' ) ? skb_opt( 'hotline', '0868 055 555' ) : '0868055555' ) );
	}
	return preg_replace( '/^0/', '84', $so );
}

/** Đường dẫn nhắn WhatsApp, có sẵn câu chào (theo ngôn ngữ) */
function skb_wa_url( $chu = '' ) {
	$cau = '' !== $chu ? sprintf( skb__( 'Xin chào SK Ambient Light, tôi quan tâm: %s' ), $chu ) : skb__( 'Xin chào SK Ambient Light, tôi cần tư vấn đèn LED nội thất.' );
	return 'https://wa.me/' . skb_wa_so() . '?text=' . rawurlencode( $cau );
}

/** Số gọi dạng quốc tế để hiển thị: +84 868 055 555 */
function skb_tel_quoc_te() {
	$so = skb_wa_so();
	return '+' . substr( $so, 0, 2 ) . ' ' . trim( chunk_split( substr( $so, 2 ), 3, ' ' ) );
}

/** Biểu tượng WhatsApp */
function skb_wa_icon( $size = 18 ) {
	return '<svg aria-hidden="true" width="' . (int) $size . '" height="' . (int) $size . '" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5-1.3A10 10 0 1 0 12 2Zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-2.9-.2-.3A8.2 8.2 0 1 1 12 20.2Zm4.5-6.1c-.2-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1l-.8 1c-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.2-.4.2-.4.7-1.3.1-.2 0-.3 0-.4l-.8-1.8c-.2-.5-.4-.4-.6-.4h-.5a1 1 0 0 0-.7.3 3 3 0 0 0-.9 2.2 5.2 5.2 0 0 0 1.1 2.7 11.9 11.9 0 0 0 4.6 4c1.7.7 2.3.8 3.2.7a2.7 2.7 0 0 0 1.8-1.3 2.2 2.2 0 0 0 .2-1.3c-.1-.1-.3-.2-.5-.3Z"/></svg>';
}

/* ---------- Chọn ngôn ngữ, hreflang ---------- */

/** Đường dẫn tiếng Việt của trang đang xem (rỗng nếu trang không có bản dịch) */
function skb_ngu_url_vi_hien_tai() {
	if ( ! skb_ngu_dich_duoc() || is_404() || is_paged() ) {
		return '';
	}
	return skb_ngu_tat( function () {
		if ( is_singular() ) {
			return get_permalink( get_queried_object_id() );
		}
		if ( is_tax( 'hang_xe' ) ) {
			$l = get_term_link( get_queried_object() );
			return is_wp_error( $l ) ? '' : $l;
		}
		if ( is_post_type_archive( 'mau_xe' ) ) {
			return get_post_type_archive_link( 'mau_xe' );
		}
		return home_url( '/' );
	} );
}

/** Ô chọn ngôn ngữ (đầu trang) */
function skb_ngu_chon() {
	$vi = skb_ngu_url_vi_hien_tai();
	$ds = skb_ngu_ds();
	$cu = skb_ngu();
	?>
	<details class="ngu-chon">
		<summary aria-label="<?php echo esc_attr( skb__( 'Ngôn ngữ' ) . ': ' . $ds[ $cu ]['ten'] ); ?>"><svg aria-hidden="true" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a14 14 0 0 1 0 18M12 3a14 14 0 0 0 0 18"/></svg><span><?php echo esc_html( $ds[ $cu ]['ngan'] ); ?></span></summary>
		<ul>
			<?php foreach ( $ds as $ma => $n ) : $u = $vi ? skb_ngu_url( $vi, $ma ) : skb_ngu_url( skb_ngu_goc() . '/', $ma ); ?>
				<li><a href="<?php echo esc_url( $u ); ?>" hreflang="<?php echo esc_attr( $n['hl'] ); ?>" lang="<?php echo esc_attr( $n['hl'] ); ?>"<?php echo $ma === $cu ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $n['ten'] ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</details>
	<?php
}

add_action( 'wp_head', function () {
	$vi = skb_ngu_url_vi_hien_tai();
	if ( '' === $vi ) {
		return;
	}
	foreach ( skb_ngu_ds() as $ma => $n ) {
		printf( '<link rel="alternate" hreflang="%s" href="%s">' . "\n", esc_attr( $n['hl'] ), esc_url( skb_ngu_url( $vi, $ma ) ) );
	}
	printf( '<link rel="alternate" hreflang="x-default" href="%s">' . "\n", esc_url( $vi ) );
}, 1 );

/* ---------- SEO bản nước ngoài ---------- */

/** Chọn câu đầu tiên vừa độ dài (đếm theo ký tự) */
function skb_ngu_vua( $cau, $max ) {
	foreach ( $cau as $c ) {
		if ( mb_strlen( $c ) <= $max ) {
			return $c;
		}
	}
	return end( $cau );
}

/** Tiêu đề trang (thẻ title) bản nước ngoài */
function skb_ngu_title() {
	$site = 'SK Ambient Light';
	if ( is_404() ) {
		return skb__( 'Không tìm thấy trang' ) . ' – ' . $site;
	}
	if ( is_front_page() ) {
		return $site . ' – ' . skb__( 'Đèn LED nội thất ô tô ốp zin' );
	}
	if ( is_singular( 'mau_xe' ) ) {
		$id = get_queried_object_id();
		return skb_ngu_vua( array(
			skb_ngu_dien( skb__( '@seo_xe_t1' ), $id ),
			skb_ngu_dien( skb__( '@seo_xe_t2' ), $id ),
			skb_ngu_dien( skb__( '@seo_xe_t3' ), $id ),
		), 60 );
	}
	if ( is_tax( 'hang_xe' ) ) {
		$t = get_queried_object();
		return sprintf( skb__( 'Đèn nội thất cho %s' ), skb__( $t->name ) ) . ' – ' . $site;
	}
	if ( is_post_type_archive( 'mau_xe' ) ) {
		return skb__( 'Chọn dòng xe' ) . ' – ' . $site;
	}
	if ( is_page( 'gioi-thieu' ) ) {
		return skb__( 'Giới thiệu SK Ambient Light' );
	}
	if ( is_page( 'lien-he' ) ) {
		return skb__( 'Liên hệ SK Ambient Light' ) . ' – WhatsApp ' . skb_tel_quoc_te();
	}
	return $site;
}

/** Mô tả (meta description) bản nước ngoài */
function skb_ngu_mo_ta() {
	if ( is_singular( 'mau_xe' ) ) {
		$id = get_queried_object_id();
		return skb_ngu_vua( array(
			skb_ngu_dien( skb__( '@seo_xe_d1' ), $id ),
			skb_ngu_dien( skb__( '@seo_xe_d2' ), $id ),
		), 160 );
	}
	if ( is_tax( 'hang_xe' ) ) {
		return sprintf( skb__( '@seo_hang_d' ), skb__( get_queried_object()->name ) );
	}
	if ( is_post_type_archive( 'mau_xe' ) ) {
		return skb__( '@seo_ds_d' );
	}
	if ( is_page( 'gioi-thieu' ) ) {
		return skb__( '@seo_gt_d' );
	}
	if ( is_page( 'lien-he' ) ) {
		return skb__( '@seo_lh_d' );
	}
	if ( is_404() ) {
		return '';
	}
	return skb__( '@seo_home_d' );
}

add_filter( 'pre_get_document_title', function ( $t ) {
	return skb_ngu_ngoai() ? skb_ngu_title() : $t;
}, 30 );

/* Bản nước ngoài dùng mẫu trang riêng cho Giới thiệu / Liên hệ */
add_filter( 'template_include', function ( $tpl ) {
	if ( skb_ngu_ngoai() && is_page() && in_array( (int) get_queried_object_id(), skb_ngu_trang_dich(), true ) ) {
		$f = locate_template( 'ngoai-trang.php' );
		return $f ? $f : $tpl;
	}
	return $tpl;
}, 20 );

/* ---------- Sitemap: thêm đường dẫn các bản nước ngoài ---------- */
if ( class_exists( 'WP_Sitemaps_Provider' ) ) {
	class SKB_Ngu_Sitemap extends WP_Sitemaps_Provider {
		public function __construct() {
			$this->name        = 'ngonngu';
			$this->object_type = 'ngonngu';
		}
		private function vi_urls() {
			return skb_ngu_tat( function () {
				$u   = array( home_url( '/' ), get_post_type_archive_link( 'mau_xe' ) );
				$tms = get_terms( array( 'taxonomy' => 'hang_xe', 'hide_empty' => true ) );
				foreach ( is_wp_error( $tms ) ? array() : $tms as $t ) {
					$l = get_term_link( $t );
					if ( ! is_wp_error( $l ) ) {
						$u[] = $l;
					}
				}
				foreach ( get_posts( array( 'post_type' => 'mau_xe', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids', 'orderby' => 'ID', 'order' => 'ASC' ) ) as $id ) {
					if ( ! get_post_meta( $id, '_skb_seo_noindex', true ) ) {
						$u[] = get_permalink( $id );
					}
				}
				foreach ( skb_ngu_trang_dich() as $id ) {
					$u[] = get_permalink( $id );
				}
				return $u;
			} );
		}
		public function get_url_list( $page_num, $object_subtype = '' ) {
			if ( 1 !== (int) $page_num ) {
				return array();
			}
			$out = array();
			foreach ( skb_ngu_ma_ngoai() as $ma ) {
				foreach ( $this->vi_urls() as $u ) {
					$out[] = array( 'loc' => skb_ngu_url( $u, $ma ) );
				}
			}
			return $out;
		}
		public function get_max_num_pages( $object_subtype = '' ) {
			return 1;
		}
	}
	add_action( 'init', function () {
		if ( function_exists( 'wp_register_sitemap_provider' ) ) {
			wp_register_sitemap_provider( 'ngonngu', new SKB_Ngu_Sitemap() );
		}
	} );
}

/** Menu chính bản nước ngoài: Dòng xe, Giới thiệu, Liên hệ, Catalog */
function skb_ngu_menu() {
	$items = array( array( get_post_type_archive_link( 'mau_xe' ), skb__( 'Dòng xe' ), false ) );
	foreach ( array( 'gioi-thieu' => 'Giới thiệu', 'lien-he' => 'Liên hệ' ) as $slug => $nhan ) {
		$p = get_page_by_path( $slug );
		if ( $p ) {
			$items[] = array( get_permalink( $p ), skb__( $nhan ), false );
		}
	}
	$items[] = array( 'https://catalog.sklight.vn/?nguon=web', skb__( 'Catalog phụ kiện' ), true );
	echo '<ul>';
	foreach ( $items as $it ) {
		printf(
			'<li><a href="%s"%s>%s%s</a></li>',
			esc_url( $it[0] ),
			$it[2] ? ' class="cat-link" target="_blank" rel="noopener"' : '',
			esc_html( $it[1] ),
			$it[2] ? ' <span aria-hidden="true">↗</span>' : ''
		);
	}
	echo '</ul>';
}

/** Chữ cho main.js (ô tìm xe, menu, xem ảnh) theo ngôn ngữ đang xem */
function skb_ngu_js_chu() {
	$out = array();
	foreach ( array( 'Đóng menu', 'Mở menu', 'Đóng', 'Ảnh trước', 'Ảnh sau', 'Hãng', 'Dòng xe khác', '%d mẫu', 'Xem các mẫu', 'Từ %s', 'Liên hệ', '%d gói', 'Chưa có mẫu cho "%s".', 'Nhắn WhatsApp' ) as $k ) {
		$out[ $k ] = skb__( $k );
	}
	return $out;
}

/* Bản nước ngoài: chữ "alt" của ảnh mẫu xe (tiếng Việt) → tên mẫu theo ngôn ngữ đang xem */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr ) {
	if ( skb_ngu_ngoai() && ! empty( $attr['alt'] ) && preg_match( '/[\x{00C0}-\x{1EF9}]/u', $attr['alt'] ) ) {
		$id          = get_the_ID();
		$attr['alt'] = ( $id && 'mau_xe' === get_post_type( $id ) ) ? skb_ngu_tieu_de_xe( $id ) : '';
	}
	return $attr;
}, 20 );

/* Bản nước ngoài: bỏ các link RSS / oEmbed (tiêu đề tiếng Việt) ở <head> */
add_action( 'wp', function () {
	if ( ! skb_ngu_ngoai() ) {
		return;
	}
	remove_action( 'wp_head', 'feed_links', 2 );
	remove_action( 'wp_head', 'feed_links_extra', 3 );
	remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
	remove_action( 'wp_head', 'rsd_link' );
} );
