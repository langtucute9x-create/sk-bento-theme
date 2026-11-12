<?php
/**
 * Đo lượt bấm (không cần tài khoản ngoài): Gọi, Zalo, Đặt lịch, chọn gói, tìm xe, bản đồ đại lý, Gần tôi, xem mẫu xe, kích hoạt.
 * Lưu số đếm theo ngày vào bảng {prefix}skb_su_kien (ngày, loại, mục, số) — không lưu IP hay thông tin khách.
 * Trang xem: Bảng tin › Thống kê web. Tuỳ chọn: điền mã GA4 ở Tùy biến › SK: Thông tin liên hệ để gửi thêm sang Google Analytics.
 */
defined( 'ABSPATH' ) || exit;

const SKB_SK_DB = 1;

function skb_su_kien_loai() {
	return array(
		'goi'      => 'Bấm Gọi',
		'zalo'     => 'Bấm Zalo',
		'dat_lich' => 'Gửi đặt lịch',
		'mo_dl'    => 'Mở trang đặt lịch',
		'chon_goi' => 'Chọn gói',
		'xem_mau'  => 'Xem mẫu xe',
		'tim_xe'   => 'Tìm xe',
		'ban_do'   => 'Chạm bản đồ đại lý',
		'gan_toi'  => 'Bấm Đại lý gần tôi',
		'chi_duong'=> 'Bấm Chỉ đường',
		'kich_hoat'=> 'Kích hoạt thành công',
		'video'    => 'Xem video',
		'tu_catalog' => 'Khách đến từ catalog',
		'mo_catalog' => 'Bấm sang catalog',
	);
}

function skb_su_kien_bang() {
	global $wpdb;
	return $wpdb->prefix . 'skb_su_kien';
}

add_action( 'admin_init', function () {
	if ( (int) get_option( 'skb_su_kien_db' ) === SKB_SK_DB ) {
		return;
	}
	global $wpdb;
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$t = skb_su_kien_bang();
	dbDelta( "CREATE TABLE {$t} (
  ngay date NOT NULL,
  loai varchar(20) NOT NULL,
  muc varchar(190) NOT NULL DEFAULT '',
  so int unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (ngay,loai,muc),
  KEY loai_ngay (loai,ngay)
) {$wpdb->get_charset_collate()};" );
	update_option( 'skb_su_kien_db', SKB_SK_DB, false );
} );

/** Cộng một lượt (ngày theo giờ Việt Nam) */
function skb_su_kien_cong( $loai, $muc = '' ) {
	global $wpdb;
	if ( ! isset( skb_su_kien_loai()[ $loai ] ) || (int) get_option( 'skb_su_kien_db' ) !== SKB_SK_DB ) {
		return;
	}
	$muc  = mb_substr( trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $muc ) ) ), 0, 190 );
	$ngay = wp_date( 'Y-m-d', null, new DateTimeZone( 'Asia/Ho_Chi_Minh' ) );
	$wpdb->query( $wpdb->prepare( 'INSERT INTO ' . skb_su_kien_bang() . ' (ngay, loai, muc, so) VALUES (%s, %s, %s, 1) ON DUPLICATE KEY UPDATE so = so + 1', $ngay, $loai, $muc ) ); // phpcs:ignore
}

/* Trình duyệt gửi bằng sendBeacon → admin-ajax (không bị bộ nhớ đệm trang ảnh hưởng) */
function skb_su_kien_ajax() {
	$ua = isset( $_SERVER['HTTP_USER_AGENT'] ) ? (string) $_SERVER['HTTP_USER_AGENT'] : ''; // phpcs:ignore
	if ( '' === $ua || preg_match( '/bot|crawl|spider|slurp|preview|headless|lighthouse/i', $ua ) ) {
		wp_die( '', '', array( 'response' => 204 ) );
	}
	$ip  = isset( $_SERVER['REMOTE_ADDR'] ) ? (string) $_SERVER['REMOTE_ADDR'] : ''; // phpcs:ignore
	$key = 'skb_ev_' . md5( $ip . wp_salt( 'nonce' ) );
	$n   = (int) get_transient( $key );
	if ( $n >= 120 ) {
		wp_die( '', '', array( 'response' => 204 ) );
	}
	set_transient( $key, $n + 1, 10 * MINUTE_IN_SECONDS );
	$loai = isset( $_POST['l'] ) ? sanitize_key( wp_unslash( $_POST['l'] ) ) : ''; // phpcs:ignore
	$muc  = isset( $_POST['m'] ) ? sanitize_text_field( wp_unslash( $_POST['m'] ) ) : ''; // phpcs:ignore
	if ( in_array( $loai, array( 'dat_lich', 'kich_hoat' ), true ) ) {
		wp_die( '', '', array( 'response' => 204 ) ); // hai loại này máy chủ tự đếm khi gửi thành công
	}
	skb_su_kien_cong( $loai, $muc );
	wp_die( '', '', array( 'response' => 204 ) );
}
add_action( 'wp_ajax_skb_ev', 'skb_su_kien_ajax' );
add_action( 'wp_ajax_nopriv_skb_ev', 'skb_su_kien_ajax' );

/* Địa chỉ gửi + mã GA4 cho main.js */
add_action( 'wp_enqueue_scripts', function () {
	$cfg = array( 'ev' => admin_url( 'admin-ajax.php' ), 'home' => home_url( '/' ), 'tel' => skb_tel( skb_opt( 'hotline', '0868 055 555' ) ) );
	if ( function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai() ) {
		$ds  = skb_ngu_ds();
		$cfg = array_merge( $cfg, array(
			'lang' => skb_ngu(),
			'hl'   => $ds[ skb_ngu() ]['hl'],
			'tach' => $ds[ skb_ngu() ]['tach'],
			'goc'  => skb_ngu_goc(),
			'wa'   => skb_wa_url(),
			't'    => skb_ngu_js_chu(),
		) );
	}
	wp_add_inline_script( 'skb-main', 'window.SKB=Object.assign(window.SKB||{},' . wp_json_encode( $cfg ) . ');', 'before' );
}, 20 );

/* GA4 (chỉ khi đã điền mã G-XXXX) */
add_action( 'wp_head', function () {
	$id = trim( (string) skb_opt( 'ga4', '' ) );
	if ( ! preg_match( '/^G-[A-Z0-9]{4,16}$/', $id ) || is_user_logged_in() ) {
		return;
	}
	printf( "<script async src=\"https://www.googletagmanager.com/gtag/js?id=%1\$s\"></script>\n<script>window.dataLayer=window.dataLayer||[];function gtag(){dataLayer.push(arguments);}gtag('js',new Date());gtag('config','%1\$s');</script>\n", esc_attr( $id ) );
}, 5 );

add_action( 'customize_register', function ( $wp ) {
	$wp->add_setting( 'skb_ga4', array( 'default' => '', 'sanitize_callback' => function ( $v ) {
		$v = strtoupper( trim( (string) $v ) );
		return preg_match( '/^G-[A-Z0-9]{4,16}$/', $v ) ? $v : '';
	} ) );
	$wp->add_control( 'skb_ga4', array( 'label' => 'Mã Google Analytics 4 (G-XXXXXXX, để trống nếu không dùng)', 'section' => 'skb_info', 'type' => 'text' ) );
}, 20 );

/* ---------- Trang Bảng tin › Thống kê web ---------- */
add_action( 'admin_menu', function () {
	add_dashboard_page( 'Thống kê web', 'Thống kê web', 'edit_posts', 'skb-thong-ke', 'skb_su_kien_trang' );
} );

function skb_su_kien_trang() {
	global $wpdb;
	$t     = skb_su_kien_bang();
	$days  = isset( $_GET['ngay'] ) ? max( 1, min( 365, absint( $_GET['ngay'] ) ) ) : 30; // phpcs:ignore
	$tz    = new DateTimeZone( 'Asia/Ho_Chi_Minh' );
	$from  = ( new DateTime( 'now', $tz ) )->modify( '-' . ( $days - 1 ) . ' days' )->format( 'Y-m-d' );
	$names = skb_su_kien_loai();
	$tot   = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT loai, SUM(so) n FROM {$t} WHERE ngay >= %s GROUP BY loai", $from ) ) as $r ) { // phpcs:ignore
		$tot[ $r->loai ] = (int) $r->n;
	}
	$daily = array();
	foreach ( $wpdb->get_results( $wpdb->prepare( "SELECT ngay, loai, SUM(so) n FROM {$t} WHERE ngay >= %s AND loai IN ('goi','zalo','dat_lich','chon_goi') GROUP BY ngay, loai", $from ) ) as $r ) { // phpcs:ignore
		$daily[ $r->ngay ][ $r->loai ] = (int) $r->n;
	}
	$top = function ( $loai, $n = 10 ) use ( $wpdb, $t, $from ) {
		return $wpdb->get_results( $wpdb->prepare( "SELECT muc, SUM(so) n FROM {$t} WHERE ngay >= %s AND loai = %s GROUP BY muc ORDER BY n DESC LIMIT %d", $from, $loai, $n ) ); // phpcs:ignore
	};
	$contact = ( $tot['goi'] ?? 0 ) + ( $tot['zalo'] ?? 0 ) + ( $tot['dat_lich'] ?? 0 );
	?>
	<div class="wrap">
		<h1>Thống kê web</h1>
		<p>
			<?php foreach ( array( 7, 30, 90 ) as $d ) : ?>
				<a class="button<?php echo $d === $days ? ' button-primary' : ''; ?>" href="<?php echo esc_url( admin_url( 'index.php?page=skb-thong-ke&ngay=' . $d ) ); ?>"><?php echo (int) $d; ?> ngày</a>
			<?php endforeach; ?>
			<span class="description" style="margin-left:8px">Từ <?php echo esc_html( wp_date( 'd/m/Y', strtotime( $from ) ) ); ?>. Không tính lượt của người đang đăng nhập trang quản trị; không lưu thông tin cá nhân của khách.</span>
		</p>
		<style>.skb-k{display:grid;grid-template-columns:repeat(auto-fill,minmax(170px,1fr));gap:10px;margin:14px 0}.skb-k div{background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px}.skb-k b{display:block;font-size:24px;margin-top:4px}.skb-two{display:grid;grid-template-columns:repeat(auto-fill,minmax(340px,1fr));gap:16px}.skb-two table{background:#fff}.skb-bar{height:8px;background:#DF1C4E;border-radius:4px;display:inline-block;vertical-align:middle}</style>
		<div class="skb-k">
			<div>Khách liên hệ (Gọi + Zalo + Đặt lịch)<b><?php echo esc_html( number_format_i18n( $contact ) ); ?></b></div>
			<?php foreach ( $names as $k => $label ) : ?>
				<div><?php echo esc_html( $label ); ?><b><?php echo esc_html( number_format_i18n( $tot[ $k ] ?? 0 ) ); ?></b></div>
			<?php endforeach; ?>
		</div>
		<div class="skb-two">
			<?php
			$blocks = array(
				'chon_goi' => 'Gói được chọn nhiều nhất',
				'xem_mau'  => 'Mẫu xe được xem nhiều nhất',
				'dat_lich' => 'Dòng xe khách đặt lịch',
				'tim_xe'   => 'Khách gõ tìm gì',
				'goi'      => 'Bấm Gọi ở trang nào',
				'ban_do'   => 'Tỉnh được chạm trên bản đồ',
				'tu_catalog' => 'Trang khách vào từ catalog',
				'mo_catalog' => 'Bấm sang catalog ở trang nào',
			);
			foreach ( $blocks as $loai => $title ) :
				$rows = $top( $loai );
				$max  = $rows ? max( 1, (int) $rows[0]->n ) : 1;
				?>
				<div>
					<h2><?php echo esc_html( $title ); ?></h2>
					<table class="widefat striped"><tbody>
						<?php if ( ! $rows ) : ?><tr><td>Chưa có số liệu.</td></tr><?php endif; ?>
						<?php foreach ( $rows as $r ) : ?>
							<tr><td><?php echo esc_html( '' !== $r->muc ? $r->muc : '—' ); ?></td><td style="width:140px;white-space:nowrap"><span class="skb-bar" style="width:<?php echo (int) round( 80 * $r->n / $max ); ?>px"></span> <?php echo (int) $r->n; ?></td></tr>
						<?php endforeach; ?>
					</tbody></table>
				</div>
			<?php endforeach; ?>
		</div>
		<h2>Theo ngày</h2>
		<table class="widefat striped" style="max-width:640px"><thead><tr><th>Ngày</th><th>Gọi</th><th>Zalo</th><th>Đặt lịch</th><th>Chọn gói</th></tr></thead><tbody>
			<?php
			krsort( $daily );
			foreach ( $daily as $d => $v ) :
				?>
				<tr><td><?php echo esc_html( wp_date( 'd/m', strtotime( $d ) ) ); ?></td><td><?php echo (int) ( $v['goi'] ?? 0 ); ?></td><td><?php echo (int) ( $v['zalo'] ?? 0 ); ?></td><td><?php echo (int) ( $v['dat_lich'] ?? 0 ); ?></td><td><?php echo (int) ( $v['chon_goi'] ?? 0 ); ?></td></tr>
			<?php endforeach; ?>
			<?php if ( ! $daily ) : ?><tr><td colspan="5">Chưa có số liệu.</td></tr><?php endif; ?>
		</tbody></table>
	</div>
	<?php
}
