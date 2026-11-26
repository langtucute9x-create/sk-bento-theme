<?php
/**
 * Tấm ốp theo từng mẫu xe (0.22.0).
 * - Meta _skb_tam_op của mẫu xe: danh sách ID ảnh tấm ốp ĐÃ TÁCH NỀN, cách nhau bởi dấu phẩy.
 * - Đầu trang chủ: mỗi lượt hiện bộ tấm ốp của một mẫu xe, kèm tên xe và liên kết tới mẫu đó.
 *   Chưa mẫu xe nào có tấm ốp thì đầu trang giữ cách cũ (Giao diện › Tấm ốp đầu trang).
 * - Trang mẫu xe: khối "Bộ tấm ốp" của riêng xe đó.
 * Phần chuyển động nằm ở assets/hero-anh.js.
 */

defined( 'ABSPATH' ) || exit;

/* ---------- Khung chọn ảnh trong trang sửa mẫu xe ---------- */
add_action( 'add_meta_boxes_mau_xe', function () {
	add_meta_box( 'skb_tam_op_xe', 'Tấm ốp 3D của xe này', 'skb_tam_op_xe_box', 'mau_xe', 'normal', 'default' );
} );

function skb_tam_op_xe_box( $post ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post->ID, '_skb_tam_op', true ) ) ) );
	wp_nonce_field( 'skb_tam_op_xe', 'skb_tam_op_xe_n' );
	?>
	<style>
		#skb-ox-list{display:flex;flex-wrap:wrap;gap:8px;margin:10px 0;padding:0;list-style:none;min-height:20px}
		#skb-ox-list li{position:relative;width:96px;height:96px;border-radius:8px;overflow:hidden;background:#1d2327;cursor:move;margin:0}
		#skb-ox-list img{width:100%;height:100%;object-fit:contain;display:block}
		#skb-ox-list .skb-x{position:absolute;top:4px;right:4px;width:22px;height:22px;border:0;border-radius:50%;background:rgba(0,0,0,.65);color:#fff;cursor:pointer;line-height:20px;padding:0}
		#skb-ox-list .ui-sortable-placeholder{visibility:visible!important;border:2px dashed #2271b1;background:transparent}
	</style>
	<p>Chọn các ảnh tấm ốp <strong>đã tách nền</strong> (PNG/WebP nền trong suốt) của riêng mẫu xe này. Từ 2 ảnh trở lên thì xe này hiện ở <strong>đầu trang chủ</strong> (mỗi lượt một xe) và có khối <strong>Bộ tấm ốp</strong> ở trang mẫu xe. Web tự xếp tấm theo hình dáng (dài ngang, vuông, đứng); nhiều tấm cùng dáng thì lần lượt thay nhau.</p>
	<input type="hidden" name="skb_tam_op" id="skb-ox-ids" value="<?php echo esc_attr( implode( ',', $ids ) ); ?>">
	<ul id="skb-ox-list">
		<?php foreach ( $ids as $id ) : $t = wp_get_attachment_image_src( $id, 'thumbnail' ); if ( ! $t ) { continue; } ?>
			<li data-id="<?php echo (int) $id; ?>"><img src="<?php echo esc_url( $t[0] ); ?>" alt=""><button type="button" class="skb-x" aria-label="Bỏ ảnh">×</button></li>
		<?php endforeach; ?>
	</ul>
	<p><button type="button" class="button button-primary" id="skb-ox-add">+ Thêm tấm ốp</button> <button type="button" class="button" id="skb-ox-clear">Bỏ hết</button> <span id="skb-ox-count" class="description"></span></p>
	<script>
	jQuery(function ($) {
		var $list = $('#skb-ox-list'), $ids = $('#skb-ox-ids'), frame;
		function dem() { var n = $list.children('li').length; $('#skb-ox-count').text(n ? n + ' tấm' + (n < 2 ? ' (cần từ 2 tấm để hiện)' : '') : 'Chưa có tấm ốp'); }
		function sync() {
			var ids = $list.children('li').map(function () { return $(this).data('id'); }).get();
			$ids.val(ids.join(',')).trigger('change');
			dem();
			// trình soạn khối: báo có thay đổi để nút Lưu sáng lên (dùng chung meta giả của Bộ ảnh)
			try { if (window.wp && wp.data && wp.data.dispatch('core/editor')) { wp.data.dispatch('core/editor').editPost({ meta: { _skb_bo_anh_doi: String(Date.now()) } }); } } catch (e) {}
		}
		$list.sortable({ update: sync, tolerance: 'pointer' });
		$list.on('click', '.skb-x', function () { $(this).closest('li').remove(); sync(); });
		$('#skb-ox-clear').on('click', function () { if (confirm('Bỏ hết tấm ốp của xe này?')) { $list.empty(); sync(); } });
		$('#skb-ox-add').on('click', function (e) {
			e.preventDefault();
			if (!frame) {
				frame = wp.media({ title: 'Chọn tấm ốp của xe này', button: { text: 'Thêm tấm ốp' }, library: { type: 'image' }, multiple: 'add' });
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
	</script>
	<?php
}

add_action( 'save_post_mau_xe', function ( $post_id ) {
	delete_transient( 'skb_hero_xe' );
	if ( ! isset( $_POST['skb_tam_op_xe_n'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['skb_tam_op_xe_n'] ) ), 'skb_tam_op_xe' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$ids = array_filter( array_map( 'absint', explode( ',', (string) wp_unslash( $_POST['skb_tam_op'] ?? '' ) ) ) ); // phpcs:ignore
	$ids = array_values( array_unique( array_filter( $ids, function ( $id ) {
		return 'attachment' === get_post_type( $id );
	} ) ) );
	if ( $ids ) {
		update_post_meta( $post_id, '_skb_tam_op', implode( ',', $ids ) );
	} else {
		delete_post_meta( $post_id, '_skb_tam_op' );
	}
} );
foreach ( array( 'deleted_post', 'trashed_post', 'untrashed_post' ) as $skb_h ) {
	add_action( $skb_h, function () {
		delete_transient( 'skb_hero_xe' );
	} );
}

/* ---------- Dữ liệu ---------- */

/** Thứ tự 5 vị trí trên sân khấu và độ sâu (độ nghiêng/trôi) của từng vị trí */
function skb_op_vi_tri() {
	return array( 'ngang' => '.35', 'loa' => '.25', 'doc' => '.55', 'cua' => '1', 'taplo' => '.8' );
}

/** Bộ tấm ốp của một mẫu xe: mỗi vị trí là danh sách ảnh (xếp theo hình dáng). Dưới 2 ảnh dùng được thì trả mảng rỗng. */
function skb_op_bo_xe( $post_id ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) get_post_meta( $post_id, '_skb_tam_op', true ) ) ) );
	$bo  = array();
	$n   = 0;
	foreach ( $ids as $id ) {
		$s = wp_get_attachment_image_src( $id, 'large' );
		if ( ! $s || empty( $s[2] ) ) {
			continue;
		}
		$r  = $s[1] / $s[2];
		$c  = $r >= 3.2 ? 'ngang' : ( $r >= 1.5 ? 'taplo' : ( $r >= 0.75 ? 'loa' : ( $r >= 0.38 ? 'cua' : 'doc' ) ) );
		$it = array( 'u' => $s[0], 'w' => (int) $s[1], 'h' => (int) $s[2] );
		$m  = wp_get_attachment_image_src( $id, 'medium_large' ); // bản nhỏ cho điện thoại
		if ( $m && $m[0] !== $s[0] && $m[1] < $s[1] ) {
			$it['m']  = $m[0];
			$it['mw'] = (int) $m[1];
		}
		$bo[ $c ][] = $it;
		$n++;
	}
	return $n >= 2 ? $bo : array();
}

/** Các mẫu xe có tấm ốp để hiện ở đầu trang chủ (tối đa 12). Lưu tạm 12 giờ theo ngôn ngữ; xoá khi lưu mẫu xe. */
function skb_hero_xe() {
	$lang = function_exists( 'skb_ngu' ) ? (string) skb_ngu() : 'vi';
	$all  = get_transient( 'skb_hero_xe' );
	if ( is_array( $all ) && isset( $all[ $lang ] ) ) {
		return $all[ $lang ];
	}
	$q = get_posts( array(
		'post_type'      => 'mau_xe',
		'post_status'    => 'publish',
		'posts_per_page' => 40,
		'meta_key'       => '_skb_tam_op', // phpcs:ignore WordPress.DB.SlowDBQuery
		'meta_compare'   => 'EXISTS',
		'orderby'        => array( 'menu_order' => 'ASC', 'title' => 'ASC' ),
		'fields'         => 'ids',
		'no_found_rows'  => true,
	) );
	$xe = array();
	foreach ( $q as $id ) {
		$bo = skb_op_bo_xe( $id );
		if ( ! $bo ) {
			continue;
		}
		$u    = get_permalink( $id );
		$xe[] = array(
			't' => skb_ten_xe( $id ),
			'u' => function_exists( 'skb_ngu_url' ) ? skb_ngu_url( $u ) : $u,
			's' => $bo,
		);
		if ( count( $xe ) >= 12 ) {
			break;
		}
	}
	$all          = is_array( $all ) ? $all : array();
	$all[ $lang ] = $xe;
	set_transient( 'skb_hero_xe', $all, 12 * HOUR_IN_SECONDS );
	return $xe;
}

/* ---------- Hiển thị ---------- */

/** Bộ lọc SVG nhuộm màu LED (mỗi trang chỉ in một lần) */
function skb_op_svg() {
	static $da = false;
	if ( $da ) {
		return;
	}
	$da = true;
	echo '<svg class="hsvg" width="0" height="0" aria-hidden="true" focusable="false"><filter id="ledTint" color-interpolation-filters="sRGB"><feColorMatrix id="ledMx" type="matrix" values="1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 1 0"/></filter></svg>' . "\n";
}

/** Sân khấu tấm ốp theo xe. $xe: danh sách xe như skb_hero_xe() trả về; xe đầu tiên được in sẵn, các xe sau do JS đổi. */
function skb_op_san_khau( $xe, $uu_tien = false ) {
	if ( ! $xe ) {
		return;
	}
	$dau = $xe[0]['s'];
	printf( '<div class="hstage" id="hstage" aria-hidden="true" data-xe="%s">' . "\n", esc_attr( wp_json_encode( $xe ) ) );
	echo '<div class="hglow"></div>' . "\n";
	foreach ( skb_op_vi_tri() as $slot => $sau ) {
		$it = isset( $dau[ $slot ][0] ) ? $dau[ $slot ][0] : null;
		printf( '<div class="hp hp-%1$s%2$s" data-d="%3$s" data-slot="%1$s"><div class="hp-in"><img', esc_attr( $slot ), $it ? '' : ' hp-off', esc_attr( $sau ) );
		if ( $it ) {
			printf( ' src="%s" width="%d" height="%d"', esc_url( $it['u'] ), (int) $it['w'], (int) $it['h'] );
			if ( ! empty( $it['m'] ) ) {
				printf( ' srcset="%s %dw, %s %dw"', esc_url( $it['m'] ), (int) $it['mw'], esc_url( $it['u'] ), (int) $it['w'] );
			}
		}
		printf( ' sizes="%s" alt="" decoding="async"%s></div></div>' . "\n", esc_attr( skb_hero_sizes( $slot ) ), ( $uu_tien && in_array( $slot, array( 'cua', 'ngang' ), true ) ) ? ' fetchpriority="high"' : '' );
	}
	echo '</div>' . "\n";
}

/** Dòng "Tấm ốp cho <xe> · Xem mẫu này" + các chấm chọn xe ở đầu trang chủ */
function skb_op_chu_thich( $xe ) {
	if ( ! $xe ) {
		return;
	}
	echo '<div class="hxe">';
	printf(
		'<a class="hxe-a" href="%s"><span class="hxe-t">%s</span> <span class="hxe-go">%s <span aria-hidden="true">→</span></span></a>',
		esc_url( $xe[0]['u'] ),
		sprintf( esc_html( skb__( 'Tấm ốp cho %s' ) ), '<b>' . esc_html( $xe[0]['t'] ) . '</b>' ), // phpcs:ignore
		esc_html( skb__( 'Xem mẫu này' ) )
	);
	if ( count( $xe ) > 1 ) {
		printf( '<div class="hxe-dots" role="group" aria-label="%s">', esc_attr( skb__( 'Chọn dòng xe để xem tấm ốp' ) ) );
		foreach ( $xe as $i => $x ) {
			printf( '<button type="button" aria-pressed="%s" aria-label="%s"></button>', 0 === $i ? 'true' : 'false', esc_attr( $x['t'] ) );
		}
		echo '</div>';
	}
	echo '</div>' . "\n";
}

/** Nút thử màu đèn (dùng chung đầu trang chủ và trang mẫu xe) */
function skb_op_thu_mau() {
	$mau = array(
		'rainbow' => array( 'Đa sắc', 'conic-gradient(#F49B3F,#DF1C4E,#8B2BB8,#3FA9FF,#F49B3F)' ),
		'tim'     => array( 'Tím', '#9B3CFF' ),
		'xanh'    => array( 'Xanh dương', '#2F7BFF' ),
		'do'      => array( 'Đỏ', '#FF2D55' ),
		'trang'   => array( 'Trắng ấm', '#FFE9D0' ),
	);
	printf( '<div class="led-pick" role="group" aria-label="%s"><span>%s</span>', esc_attr( skb__( 'Thử màu đèn LED' ) ), esc_html( skb__( 'Thử màu đèn' ) ) );
	foreach ( $mau as $k => $m ) {
		printf( '<button type="button" class="led-swatch" data-led="%s" aria-pressed="%s" aria-label="%s" style="background:%s"></button>', esc_attr( $k ), 'rainbow' === $k ? 'true' : 'false', esc_attr( skb__( $m[0] ) ), esc_attr( $m[1] ) );
	}
	echo '</div>' . "\n";
}

/** Khối "Bộ tấm ốp" ở trang mẫu xe (chỉ khi xe có từ 2 tấm ốp) */
function skb_op_khoi_mau_xe( $post_id ) {
	$bo = skb_op_bo_xe( $post_id );
	if ( ! $bo ) {
		return;
	}
	$ten = skb_ten_xe( $post_id );
	echo '<section class="mx-op" id="hero" data-led="rainbow" aria-labelledby="mx-op-t">' . "\n";
	printf( '<h2 id="mx-op-t">%s</h2>' . "\n", esc_html( sprintf( skb__( 'Bộ tấm ốp của %s' ), $ten ) ) );
	skb_op_svg();
	skb_op_san_khau( array( array( 't' => $ten, 'u' => get_permalink( $post_id ), 's' => $bo ) ) );
	skb_op_thu_mau();
	echo '</section>' . "\n";
}

/**
 * Chèn một khối vào giữa bài giới thiệu: trước tiêu đề H2 nằm giữa bài; bài không đủ 2 tiêu đề H2 thì sau đoạn văn nằm giữa.
 * Bài rỗng hoặc không có đoạn nào thì đặt khối lên trước.
 */
function skb_op_chen_giua( $html, $khoi ) {
	if ( preg_match_all( '/<h2[\s>]/i', $html, $m, PREG_OFFSET_CAPTURE ) && count( $m[0] ) >= 2 ) {
		$vt = $m[0][ (int) ceil( count( $m[0] ) / 2 ) ][1] ?? $m[0][ count( $m[0] ) - 1 ][1];
		return substr( $html, 0, $vt ) . $khoi . substr( $html, $vt );
	}
	if ( preg_match_all( '/<\/p>/i', $html, $m, PREG_OFFSET_CAPTURE ) && count( $m[0] ) >= 2 ) {
		$vt = $m[0][ (int) floor( ( count( $m[0] ) - 1 ) / 2 ) ][1] + 4;
		return substr( $html, 0, $vt ) . "\n" . $khoi . substr( $html, $vt );
	}
	return $khoi . $html;
}

/** Bài giới thiệu của mẫu xe, có khối "Bộ tấm ốp" nằm giữa bài (xe chưa có tấm ốp thì in bài như cũ) */
function skb_op_noi_dung( $post_id ) {
	ob_start();
	the_content();
	$bai = (string) ob_get_clean();
	ob_start();
	skb_op_khoi_mau_xe( $post_id );
	$khoi = (string) ob_get_clean();
	echo '' !== $khoi ? skb_op_chen_giua( $bai, $khoi ) : $bai; // phpcs:ignore WordPress.Security.EscapeOutput
}

/* Trang mẫu xe có tấm ốp cũng nạp script chuyển động (trang chủ đã nạp ở functions.php) */
add_action( 'wp_footer', function () {
	if ( is_singular( 'mau_xe' ) && skb_op_bo_xe( get_queried_object_id() ) ) {
		printf( '<script src="%s" defer></script>' . "\n", esc_url( get_template_directory_uri() . '/assets/hero-anh.js?ver=' . SKB_VER ) );
	}
}, 20 );
