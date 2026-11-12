<?php
/**
 * SK Bento - functions
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'SKB_VER', '0.15.1' );

/* Đa ngôn ngữ: phải nạp sớm để nhận /en/ /zh/… trước khi WordPress đọc đường dẫn */
require_once get_template_directory() . '/inc/da-ngu.php';

/* ---------------------------------------------------------------
 * Cài đặt giao diện
 * ------------------------------------------------------------- */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support( 'custom-logo', array( 'height' => 120, 'width' => 800, 'flex-width' => true, 'flex-height' => true ) );
	add_theme_support( 'html5', array( 'search-form', 'gallery', 'caption', 'style', 'script' ) );
	add_theme_support( 'responsive-embeds' );
	register_nav_menus( array(
		'primary' => 'Menu chính',
		'footer'  => 'Menu chân trang',
	) );
} );

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'skb-fonts', 'https://fonts.googleapis.com/css2?family=Be+Vietnam+Pro:wght@400;500;600;700&family=Montserrat:wght@700;800&display=swap', array(), null );
	wp_enqueue_style( 'skb-style', get_stylesheet_uri(), array(), SKB_VER );
	wp_enqueue_script( 'skb-main', get_template_directory_uri() . '/assets/main.js', array(), SKB_VER, true );
} );

add_filter( 'wp_resource_hints', function ( $urls, $relation ) {
	if ( 'preconnect' === $relation ) {
		$urls[] = array( 'href' => 'https://fonts.gstatic.com', 'crossorigin' );
	}
	return $urls;
}, 10, 2 );

/* Cảnh 3D đầu trang chủ (Three.js từ CDN, tải dạng module) */
/* Kiểu đầu trang: 'anh' (ảnh tấm ốp thật, mặc định) hoặc '3d' (xem lại bản cũ bằng ?hero=3d) */
function skb_hero_mode() {
	return ( isset( $_GET['hero'] ) && '3d' === $_GET['hero'] ) ? '3d' : 'anh'; // phpcs:ignore WordPress.Security.NonceVerification
}

/* Công cụ chuyển sản phẩm web cũ (chỉ nạp trong trang quản trị) */
if ( is_admin() ) {
	require_once get_template_directory() . '/inc/chuyen-du-lieu.php';
	/* Chuyển web chính thức: đổi đường dẫn /moitest → gốc (Công cụ › Chuyển web chính thức) */
	require_once get_template_directory() . '/inc/chuyen-web.php';
}
/* Kích hoạt bảo hành điện tử (dữ liệu ở SK Core) */
require_once get_template_directory() . '/inc/kich-hoat.php';
/* Chuyển hướng 301 từ đường dẫn web cũ */
require_once get_template_directory() . '/inc/chuyen-huong.php';
require_once get_template_directory() . '/inc/ban-do-dai-ly.php';
require_once get_template_directory() . '/inc/goi-san-pham.php';
require_once get_template_directory() . '/inc/bo-anh.php';
require_once get_template_directory() . '/inc/tim-xe.php';
require_once get_template_directory() . '/inc/su-kien.php';
require_once get_template_directory() . '/inc/khach-that.php';
require_once get_template_directory() . '/inc/dat-lich-core.php';
require_once get_template_directory() . '/inc/bo-nho-dem.php';
require_once get_template_directory() . '/inc/catalog.php';
/* SEO: ô SEO trong trình soạn bài + thẻ meta / Open Graph */
require_once get_template_directory() . '/inc/seo.php';

/* Giá mẫu xe: "8.700.000đ" hoặc rỗng nếu chưa có */
function skb_gia( $post_id = 0 ) {
	$v = (float) get_post_meta( $post_id ? $post_id : get_the_ID(), '_skb_gia', true );
	return $v > 0 ? number_format( $v, 0, ',', '.' ) . 'đ' : '';
}

/* Ảnh tải lên: các cỡ thu nhỏ tạo dạng WebP (nhẹ hơn, giữ nền trong suốt) */
add_filter( 'image_editor_output_format', function ( $formats ) {
	$formats['image/png']  = 'image/webp';
	$formats['image/jpeg'] = 'image/webp';
	return $formats;
} );

/* Trang quản trị: Giao diện > Tấm ốp đầu trang (chọn ảnh từ Thư viện) */
add_action( 'admin_menu', function () {
	add_theme_page( 'Tấm ốp đầu trang', 'Tấm ốp đầu trang', 'edit_theme_options', 'skb-tam-op', 'skb_tam_op_page' );
} );
add_action( 'admin_init', function () {
	register_setting( 'skb_tam_op', 'skb_hero_ops', array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'skb_sanitize_ids' ) );
} );
function skb_sanitize_ids( $v ) {
	$ids = array_filter( array_map( 'absint', explode( ',', (string) $v ) ) );
	return implode( ',', array_values( array_unique( $ids ) ) );
}
add_action( 'admin_enqueue_scripts', function ( $hook ) {
	if ( 'appearance_page_skb-tam-op' === $hook ) {
		wp_enqueue_media();
		wp_enqueue_script( 'jquery-ui-sortable' );
	}
} );
function skb_tam_op_page() {
	$ids = (string) get_option( 'skb_hero_ops', '' );
	?>
	<div class="wrap">
		<h1>Tấm ốp đầu trang</h1>
		<p>Chọn các ảnh tấm ốp <strong>đã tách nền</strong> (PNG/WebP nền trong suốt) trong Thư viện. Đầu trang tự xếp tấm theo hình dáng (dài ngang, vuông, đứng) và đổi sang bộ khác sau vài giây. Kéo để đổi thứ tự, bấm × để bỏ một tấm, rồi bấm Lưu.</p>
		<form method="post" action="options.php">
			<?php settings_fields( 'skb_tam_op' ); ?>
			<input type="hidden" id="skb_hero_ops" name="skb_hero_ops" value="<?php echo esc_attr( $ids ); ?>">
			<p><button type="button" class="button button-primary" id="skb-op-pick">Chọn ảnh từ Thư viện</button> <button type="button" class="button" id="skb-op-clear">Bỏ hết</button> <span id="skb-op-count"></span></p>
			<ul id="skb-op-list">
			<?php foreach ( array_filter( array_map( 'absint', explode( ',', $ids ) ) ) as $id ) : $t = wp_get_attachment_image_src( $id, 'thumbnail' ); if ( ! $t ) { continue; } ?>
				<li data-id="<?php echo (int) $id; ?>"><img src="<?php echo esc_url( $t[0] ); ?>" alt=""><button type="button" class="skb-x" aria-label="Bỏ">×</button></li>
			<?php endforeach; ?>
			</ul>
			<?php submit_button( 'Lưu' ); ?>
		</form>
	</div>
	<style>#skb-op-list{display:flex;flex-wrap:wrap;gap:8px;list-style:none;padding:0;margin:12px 0}#skb-op-list li{position:relative;width:120px;height:120px;background:#18181d;border-radius:8px;display:flex;align-items:center;justify-content:center;cursor:move;margin:0}#skb-op-list img{max-width:108px;max-height:108px}.skb-x{position:absolute;top:2px;right:2px;border:0;border-radius:50%;width:22px;height:22px;background:#d63638;color:#fff;cursor:pointer;line-height:20px}</style>
	<script>
	jQuery(function ($) {
		var $in = $('#skb_hero_ops'), $ul = $('#skb-op-list'), frame;
		function sync() { $in.val($ul.children().map(function () { return $(this).data('id'); }).get().join(',')); $('#skb-op-count').text($ul.children().length + ' ảnh'); }
		function add(a) {
			if ($ul.children('[data-id="' + a.id + '"]').length) return;
			var u = (a.sizes && a.sizes.thumbnail) ? a.sizes.thumbnail.url : a.url;
			$ul.append($('<li>').attr('data-id', a.id).append($('<img alt="">').attr('src', u), '<button type="button" class="skb-x" aria-label="Bỏ">×</button>'));
		}
		$ul.sortable({ update: sync });
		$ul.on('click', '.skb-x', function () { $(this).parent().remove(); sync(); });
		$('#skb-op-clear').on('click', function () { $ul.empty(); sync(); });
		$('#skb-op-pick').on('click', function () {
			if (!frame) {
				frame = wp.media({ title: 'Chọn tấm ốp', button: { text: 'Thêm vào đầu trang' }, multiple: 'add', library: { type: 'image' } });
				frame.on('select', function () { frame.state().get('selection').each(function (m) { add(m.toJSON()); }); sync(); });
			}
			frame.open();
		});
		sync();
	});
	</script>
	<?php
}

/* Gom ảnh tấm ốp thành các bộ: mỗi bộ có tối đa 5 vị trí, xếp theo hình dáng ảnh */
function skb_hero_sets() {
	$cats = array( 'ngang' => array(), 'loa' => array(), 'doc' => array(), 'cua' => array(), 'taplo' => array() );
	$ids  = array_filter( array_map( 'absint', explode( ',', (string) get_option( 'skb_hero_ops', '' ) ) ) );
	foreach ( $ids as $id ) {
		$s = wp_get_attachment_image_src( $id, 'large' );
		if ( ! $s || empty( $s[2] ) ) {
			continue;
		}
		$r = $s[1] / $s[2];
		$c = $r >= 3.2 ? 'ngang' : ( $r >= 1.5 ? 'taplo' : ( $r >= 0.75 ? 'loa' : ( $r >= 0.38 ? 'cua' : 'doc' ) ) );
		$it = array( 'u' => $s[0], 'w' => (int) $s[1], 'h' => (int) $s[2] );
		$m  = wp_get_attachment_image_src( $id, 'medium_large' ); // bản nhỏ cho điện thoại
		if ( $m && $m[0] !== $s[0] && $m[1] < $s[1] ) {
			$it['m']  = $m[0];
			$it['mw'] = (int) $m[1];
		}
		$cats[ $c ][] = $it;
	}
	$n = min( 12, max( array_map( 'count', $cats ) ) );
	if ( ! $n ) {
		$op = get_template_directory_uri() . '/assets/img/op/';
		return array( array(
			'ngang' => array( 'u' => $op . 'op-ngang.webp', 'w' => 1200, 'h' => 217 ),
			'loa'   => array( 'u' => $op . 'vong-loa.webp', 'w' => 400, 'h' => 374 ),
			'doc'   => array( 'u' => $op . 'op-doc-2.webp', 'w' => 114, 'h' => 760 ),
			'cua'   => array( 'u' => $op . 'op-cua.webp', 'w' => 388, 'h' => 720 ),
			'taplo' => array( 'u' => $op . 'op-taplo.webp', 'w' => 880, 'h' => 447 ),
		) );
	}
	$sets = array();
	for ( $i = 0; $i < $n; $i++ ) {
		$set = array();
		foreach ( $cats as $c => $list ) {
			$set[ $c ] = $list ? $list[ $i % count( $list ) ] : null;
		}
		$sets[] = $set;
	}
	return $sets;
}

/* Tải trước 2 tấm ốp lớn nhất của bộ đầu tiên để đầu trang không trống lúc mở */
add_action( 'wp_head', function () {
	if ( ! is_front_page() || 'anh' !== skb_hero_mode() ) {
		return;
	}
	$sets = skb_hero_sets();
	foreach ( array( 'ngang', 'cua' ) as $slot ) {
		$it = $sets[0][ $slot ] ?? null;
		if ( ! $it ) {
			continue;
		}
		if ( ! empty( $it['m'] ) ) {
			printf( '<link rel="preload" as="image" href="%1$s" imagesrcset="%2$s %3$dw, %1$s %4$dw" imagesizes="%5$s" fetchpriority="high">' . "\n", esc_url( $it['u'] ), esc_url( $it['m'] ), (int) $it['mw'], (int) $it['w'], esc_attr( skb_hero_sizes( $slot ) ) );
		} else {
			printf( '<link rel="preload" as="image" href="%s" fetchpriority="high">' . "\n", esc_url( $it['u'] ) );
		}
	}
}, 2 );
function skb_hero_sizes( $slot ) {
	$map = array( 'ngang' => '(max-width: 760px) 92vw, 52vw', 'taplo' => '(max-width: 760px) 46vw, 26vw', 'loa' => '(max-width: 760px) 34vw, 16vw', 'cua' => '(max-width: 760px) 35vw, 16vw', 'doc' => '(max-width: 760px) 15vw, 8vw' );
	return $map[ $slot ] ?? '50vw';
}
add_action( 'wp_head', function () {
	if ( ! is_front_page() || '3d' !== skb_hero_mode() ) {
		return;
	}
	echo '<script type="importmap">{"imports":{"three":"https://cdn.jsdelivr.net/npm/three@0.170.0/build/three.module.js","three/addons/":"https://cdn.jsdelivr.net/npm/three@0.170.0/examples/jsm/"}}</script>' . "\n";
}, 1 );
add_action( 'wp_footer', function () {
	if ( ! is_front_page() ) {
		return;
	}
	if ( '3d' === skb_hero_mode() ) {
		printf( '<script type="module" src="%s"></script>' . "\n", esc_url( get_template_directory_uri() . '/assets/hero3d.js?ver=' . SKB_VER ) );
	} else {
		printf( '<script src="%s" defer></script>' . "\n", esc_url( get_template_directory_uri() . '/assets/hero-anh.js?ver=' . SKB_VER ) );
	}
}, 20 );

/* Bỏ bớt thứ nặng không cần thiết */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'wp_head', 'wp_generator' );

/* ---------------------------------------------------------------
 * Loại nội dung: Đại lý, Mẫu xe, Hãng xe, Lịch hẹn
 * ------------------------------------------------------------- */
add_action( 'init', function () {
	register_post_type( 'dai_ly', array(
		'labels'       => array(
			'name'          => 'Đại lý',
			'singular_name' => 'Đại lý',
			'add_new'       => 'Thêm đại lý',
			'add_new_item'  => 'Thêm đại lý mới',
			'edit_item'     => 'Sửa đại lý',
			'all_items'     => 'Tất cả đại lý',
		),
		'public'       => true,
		'has_archive'  => 'dai-ly',
		'rewrite'      => array( 'slug' => 'dai-ly' ),
		'menu_icon'    => 'dashicons-location',
		'menu_position'=> 21,
		'supports'     => array( 'title', 'thumbnail', 'page-attributes' ),
		'show_in_rest' => true,
	) );

	register_post_type( 'mau_xe', array(
		'labels'       => array(
			'name'          => 'Mẫu xe',
			'singular_name' => 'Mẫu xe',
			'add_new'       => 'Thêm mẫu xe',
			'add_new_item'  => 'Thêm mẫu xe mới',
			'edit_item'     => 'Sửa mẫu xe',
			'all_items'     => 'Tất cả mẫu xe',
		),
		'public'       => true,
		'has_archive'  => 'mau-xe',
		'rewrite'      => array( 'slug' => 'mau-xe' ),
		'menu_icon'    => 'dashicons-car',
		'menu_position'=> 20,
		'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
		'show_in_rest' => true,
	) );

	register_taxonomy( 'hang_xe', array( 'mau_xe' ), array(
		'labels'            => array(
			'name'          => 'Hãng xe',
			'singular_name' => 'Hãng xe',
			'add_new_item'  => 'Thêm hãng xe',
			'edit_item'     => 'Sửa hãng xe',
		),
		'hierarchical'      => true,
		'public'            => true,
		'rewrite'           => array( 'slug' => 'hang-xe' ),
		'show_admin_column' => true,
		'show_in_rest'      => true,
	) );

	register_post_type( 'lich_hen', array(
		'labels'          => array(
			'name'          => 'Lịch hẹn lắp đặt',
			'singular_name' => 'Lịch hẹn',
			'all_items'     => 'Tất cả lịch hẹn',
			'edit_item'     => 'Xem lịch hẹn',
		),
		'public'          => false,
		'show_ui'         => true,
		'menu_icon'       => 'dashicons-calendar-alt',
		'menu_position'   => 22,
		'supports'        => array( 'title' ),
		'capability_type' => 'post',
		'capabilities'    => array( 'create_posts' => 'do_not_allow' ),
		'map_meta_cap'    => true,
	) );
} );

/* ---------------------------------------------------------------
 * Ô nhập thông tin đại lý
 * ------------------------------------------------------------- */
function skb_dealer_fields() {
	return array(
		'dia_chi'    => 'Địa chỉ',
		'dien_thoai' => 'Số điện thoại',
		'gio_mo'     => 'Giờ mở cửa (ví dụ 08:00 – 21:00)',
		'tinh'       => 'Tỉnh / thành phố',
		'ban_do'     => 'Link Google Maps (để trống sẽ tự tạo theo địa chỉ)',
		'toa_do'     => 'Toạ độ cho nút "Gần tôi" (vĩ độ, kinh độ — để trống sẽ tự lấy từ link Google Maps hoặc địa chỉ)',
	);
}

add_action( 'add_meta_boxes', function () {
	add_meta_box( 'skb_dealer', 'Thông tin đại lý', function ( $post ) {
		wp_nonce_field( 'skb_dealer_save', 'skb_dealer_nonce' );
		echo '<table class="form-table"><tbody>';
		foreach ( skb_dealer_fields() as $key => $label ) {
			$val = get_post_meta( $post->ID, '_skb_' . $key, true );
			printf(
				'<tr><th><label for="skb_%1$s">%2$s</label></th><td><input type="text" class="widefat" id="skb_%1$s" name="skb_%1$s" value="%3$s"></td></tr>',
				esc_attr( $key ),
				esc_html( $label ),
				esc_attr( $val )
			);
		}
		echo '</tbody></table><p class="description">Thứ tự hiển thị: dùng ô "Thứ tự" ở khung Thuộc tính bên phải (số nhỏ hiện trước).</p>';
	}, 'dai_ly', 'normal', 'high' );

	add_meta_box( 'skb_booking', 'Chi tiết lịch hẹn', function ( $post ) {
		$fields = array( 'ho_ten' => 'Họ tên', 'dien_thoai' => 'Số điện thoại', 'dong_xe' => 'Dòng xe', 'dai_ly' => 'Đại lý mong muốn', 'ngay' => 'Ngày mong muốn', 'ghi_chu' => 'Ghi chú' );
		echo '<table class="form-table"><tbody>';
		foreach ( $fields as $k => $label ) {
			printf( '<tr><th>%s</th><td>%s</td></tr>', esc_html( $label ), nl2br( esc_html( (string) get_post_meta( $post->ID, '_skb_' . $k, true ) ) ) );
		}
		echo '</tbody></table>';
	}, 'lich_hen', 'normal', 'high' );
} );

add_action( 'save_post_dai_ly', function ( $post_id ) {
	if ( ! isset( $_POST['skb_dealer_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skb_dealer_nonce'] ) ), 'skb_dealer_save' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	foreach ( array_keys( skb_dealer_fields() ) as $key ) {
		if ( isset( $_POST[ 'skb_' . $key ] ) ) {
			$val = wp_unslash( $_POST[ 'skb_' . $key ] );
			$val = ( 'ban_do' === $key ) ? esc_url_raw( $val ) : sanitize_text_field( $val );
			update_post_meta( $post_id, '_skb_' . $key, $val );
		}
	}
} );

/* Cột trong danh sách đại lý */
add_filter( 'manage_dai_ly_posts_columns', function ( $cols ) {
	$cols['skb_phone'] = 'Điện thoại';
	$cols['skb_addr']  = 'Địa chỉ';
	return $cols;
} );
add_action( 'manage_dai_ly_posts_custom_column', function ( $col, $id ) {
	if ( 'skb_phone' === $col ) {
		echo esc_html( get_post_meta( $id, '_skb_dien_thoai', true ) );
	}
	if ( 'skb_addr' === $col ) {
		echo esc_html( get_post_meta( $id, '_skb_dia_chi', true ) );
	}
}, 10, 2 );

/* Cột trong danh sách lịch hẹn */
add_filter( 'manage_lich_hen_posts_columns', function ( $cols ) {
	$new = array( 'cb' => $cols['cb'], 'title' => 'Khách hàng', 'skb_phone' => 'Điện thoại', 'skb_car' => 'Dòng xe', 'skb_dealer' => 'Đại lý', 'date' => 'Gửi lúc' );
	return $new;
} );
add_action( 'manage_lich_hen_posts_custom_column', function ( $col, $id ) {
	$map = array( 'skb_phone' => 'dien_thoai', 'skb_car' => 'dong_xe', 'skb_dealer' => 'dai_ly' );
	if ( isset( $map[ $col ] ) ) {
		echo esc_html( get_post_meta( $id, '_skb_' . $map[ $col ], true ) );
	}
}, 10, 2 );

/* ---------------------------------------------------------------
 * Tùy biến (Giao diện > Tùy biến)
 * ------------------------------------------------------------- */
function skb_opt( $key, $default = '' ) {
	$val = get_theme_mod( 'skb_' . $key, '' );
	return ( '' === $val || null === $val ) ? $default : $val;
}

add_action( 'customize_register', function ( $wp ) {
	$wp->add_section( 'skb_info', array( 'title' => 'SK: Thông tin liên hệ', 'priority' => 30 ) );
	$wp->add_section( 'skb_home', array( 'title' => 'SK: Trang chủ', 'priority' => 31 ) );

	$text = array(
		'skb_info' => array(
			'hotline'      => array( 'Hotline', '0868 055 555' ),
			'zalo'         => array( 'Số Zalo (để trống nếu không dùng)', '0868055555' ),
			'address'      => array( 'Địa chỉ', '197 Thanh Am, Việt Hưng, Long Biên, Hà Nội' ),
			'email'        => array( 'Email liên hệ', 'sklight@gmail.com' ),
			'activate_url' => array( 'Link trang kích hoạt sản phẩm (để trống = trang Kích hoạt bảo hành của web này)', '' ),
		),
		'skb_home' => array(
			'hero_eyebrow' => array( 'Dòng chữ nhỏ trên tiêu đề', 'Đèn nội thất ô tô cao cấp' ),
			'hero_title_1' => array( 'Tiêu đề dòng 1-2', 'Ánh sáng thiết kế riêng' ),
			'hero_title_2' => array( 'Tiêu đề dòng màu', 'cho xe bạn.' ),
			'hero_sub'     => array( 'Mô tả ngắn', 'Ốp thay zin theo từng dòng xe, không cắt chích dây, lắp đặt tại hệ thống đại lý SK toàn quốc.' ),
		),
	);
	foreach ( $text as $section => $items ) {
		foreach ( $items as $key => $cfg ) {
			$wp->add_setting( 'skb_' . $key, array( 'default' => $cfg[1], 'sanitize_callback' => ( 'activate_url' === $key ) ? 'esc_url_raw' : ( ( 'email' === $key ) ? 'sanitize_email' : 'sanitize_text_field' ) ) );
			$wp->add_control( 'skb_' . $key, array( 'label' => $cfg[0], 'section' => $section, 'type' => ( 'hero_sub' === $key ) ? 'textarea' : 'text' ) );
		}
	}
	foreach ( array( 'hero_image' => 'Ảnh lớn đầu trang', 'img_door' => 'Ảnh ô "Ốp thay zin"', 'img_curve' => 'Ảnh ô "Hiệu ứng"', 'gal_1' => 'Ảnh bộ sưu tập 1 (lớn)', 'gal_2' => 'Ảnh bộ sưu tập 2', 'gal_3' => 'Ảnh bộ sưu tập 3' ) as $key => $label ) {
		$wp->add_setting( 'skb_' . $key, array( 'default' => '', 'sanitize_callback' => 'esc_url_raw' ) );
		$wp->add_control( new WP_Customize_Image_Control( $wp, 'skb_' . $key, array( 'label' => $label, 'section' => 'skb_home' ) ) );
	}
} );

/* Ảnh mặc định đi kèm theme */
function skb_img( $key, $file ) {
	$url = skb_opt( $key, '' );
	return $url ? $url : get_template_directory_uri() . '/assets/img/' . $file;
}

function skb_tel( $phone ) {
	return preg_replace( '/[^0-9+]/', '', (string) $phone );
}

/*
 * Mật khẩu ứng dụng (Javis gọi REST API): WordPress chốt người dùng ở WP::init(), trước khi biết đây là
 * yêu cầu REST, nên coi yêu cầu tới /wp-json/ hoặc ?rest_route= là yêu cầu API ngay từ đầu.
 */
add_filter( 'application_password_is_api_request', function ( $is ) {
	if ( $is ) {
		return $is;
	}
	$uri = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : ''; // phpcs:ignore
	return isset( $_GET['rest_route'] ) || false !== strpos( $uri, '/' . rest_get_url_prefix() . '/' ); // phpcs:ignore
} );

/**
 * Link nút "Chỉ đường" của đại lý: luôn mở Google Maps ở chế độ chỉ đường.
 * - Có toạ độ chính xác (nhập tay, hoặc đọc từ link Google Maps của đại lý) → chỉ đường tới đúng điểm ghim.
 * - Không có: link Google Maps đại lý đã nhập (maps.app.goo.gl, google.com/maps) → mở đúng địa điểm trên Maps.
 * - Còn lại (link share.google mở trang tìm kiếm, hoặc chưa có link) → chỉ đường theo tên + địa chỉ.
 */
function skb_map_url( $post_id ) {
	$ll    = (string) get_post_meta( $post_id, '_skb_toa_do_tu_dong', true );
	$nguon = (string) get_post_meta( $post_id, '_skb_toa_do_nguon', true );
	if ( $ll && in_array( $nguon, array( 'tay', 'link' ), true ) && preg_match( '/^-?\d+(\.\d+)?,-?\d+(\.\d+)?$/', $ll ) ) {
		return 'https://www.google.com/maps/dir/?api=1&destination=' . $ll . '&travelmode=driving';
	}
	$url = trim( (string) get_post_meta( $post_id, '_skb_ban_do', true ) );
	if ( $url && preg_match( '#^https?://(maps\.app\.goo\.gl|goo\.gl/maps|(www\.)?google\.[a-z.]+/maps|maps\.google\.)#i', $url ) && false === strpos( $url, '/maps/search/' ) ) {
		return $url;
	}
	$addr = trim( (string) get_post_meta( $post_id, '_skb_dia_chi', true ) );
	$tinh = trim( (string) get_post_meta( $post_id, '_skb_tinh', true ) );
	if ( $tinh && false === mb_stripos( $addr, $tinh ) ) {
		$addr .= ', ' . $tinh;
	}
	$q = trim( get_the_title( $post_id ) . ', ' . $addr, ', ' );
	return 'https://www.google.com/maps/dir/?api=1&destination=' . rawurlencode( $q ) . '&travelmode=driving';
}

/* ---------------------------------------------------------------
 * Form đặt lịch lắp đặt
 * ------------------------------------------------------------- */
add_action( 'admin_post_nopriv_skb_booking', 'skb_handle_booking' );
add_action( 'admin_post_skb_booking', 'skb_handle_booking' );

function skb_handle_booking() {
	$back = wp_get_referer() ? wp_get_referer() : home_url( '/dat-lich/' );
	$back = remove_query_arg( 'datlich', $back );

	if ( ! isset( $_POST['skb_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skb_nonce'] ) ), 'skb_booking' ) ) {
		wp_safe_redirect( add_query_arg( 'datlich', 'loi', $back ) . '#dat-lich-form' );
		exit;
	}
	if ( ! empty( $_POST['website'] ) ) { // bẫy chống spam
		wp_safe_redirect( add_query_arg( 'datlich', 'ok', $back ) . '#dat-lich-form' );
		exit;
	}

	$name  = sanitize_text_field( wp_unslash( $_POST['ho_ten'] ?? '' ) );
	$phone = sanitize_text_field( wp_unslash( $_POST['dien_thoai'] ?? '' ) );
	$digits = preg_replace( '/\D/', '', $phone );
	if ( '' === $name || strlen( $digits ) < 9 || strlen( $digits ) > 11 ) {
		wp_safe_redirect( add_query_arg( 'datlich', 'thieu', $back ) . '#dat-lich-form' );
		exit;
	}

	$ip_key = 'skb_bk_' . md5( (string) ( $_SERVER['REMOTE_ADDR'] ?? '' ) );
	$count  = (int) get_transient( $ip_key );
	if ( $count >= 5 ) {
		wp_safe_redirect( add_query_arg( 'datlich', 'nhieu', $back ) . '#dat-lich-form' );
		exit;
	}
	set_transient( $ip_key, $count + 1, HOUR_IN_SECONDS );

	$data = array(
		'ho_ten'     => $name,
		'dien_thoai' => $phone,
		'dong_xe'    => sanitize_text_field( wp_unslash( $_POST['dong_xe'] ?? '' ) ),
		'dai_ly'     => sanitize_text_field( wp_unslash( $_POST['dai_ly'] ?? '' ) ),
		'ngay'       => sanitize_text_field( wp_unslash( $_POST['ngay'] ?? '' ) ),
		'ghi_chu'    => sanitize_textarea_field( wp_unslash( $_POST['ghi_chu'] ?? '' ) ),
	);

	$id = wp_insert_post( array(
		'post_type'   => 'lich_hen',
		'post_status' => 'private',
		'post_title'  => $name . ' - ' . $phone,
	) );
	if ( $id && ! is_wp_error( $id ) ) {
		foreach ( $data as $k => $v ) {
			update_post_meta( $id, '_skb_' . $k, $v );
		}
		$lines = array();
		foreach ( $data as $k => $v ) {
			$lines[] = $k . ': ' . $v;
		}
		update_post_meta( $id, '_skb_trang', esc_url_raw( (string) wp_get_referer() ) );
		wp_mail( get_option( 'admin_email' ), 'Lịch hẹn lắp đặt mới: ' . $name, implode( "\n", $lines ) . "\n\nXem: " . admin_url( 'post.php?post=' . $id . '&action=edit' ) );
		if ( function_exists( 'skb_su_kien_cong' ) ) {
			skb_su_kien_cong( 'dat_lich', $data['dong_xe'] ? $data['dong_xe'] : '(không ghi dòng xe)' );
		}
		if ( function_exists( 'skb_lh_gui_sau' ) ) {
			skb_lh_gui_sau( $id );
		}
	}
	wp_safe_redirect( add_query_arg( 'datlich', 'ok', $back ) . '#dat-lich-form' );
	exit;
}

function skb_booking_form() {
	$state = isset( $_GET['datlich'] ) ? sanitize_key( $_GET['datlich'] ) : '';
	$msgs  = array(
		'ok'    => array( 'ok', 'Cảm ơn bạn! SK sẽ gọi lại xác nhận lịch trong giờ làm việc.' ),
		'thieu' => array( 'err', 'Vui lòng nhập họ tên và số điện thoại hợp lệ.' ),
		'nhieu' => array( 'err', 'Bạn đã gửi nhiều lần. Vui lòng gọi hotline để được hỗ trợ nhanh.' ),
		'loi'   => array( 'err', 'Phiên gửi đã hết hạn, vui lòng thử lại.' ),
	);
	$dealers = get_posts( array( 'post_type' => 'dai_ly', 'numberposts' => 100, 'orderby' => 'menu_order title', 'order' => 'ASC' ) );
	$brands  = get_terms( array( 'taxonomy' => 'hang_xe', 'hide_empty' => false ) );
	ob_start();
	?>
	<div id="dat-lich-form">
	<?php if ( isset( $msgs[ $state ] ) ) : ?>
		<div class="notice <?php echo esc_attr( $msgs[ $state ][0] ); ?>" role="status"><?php echo esc_html( $msgs[ $state ][1] ); ?></div>
	<?php endif; ?>
	<form class="form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
		<input type="hidden" name="action" value="skb_booking">
		<?php wp_nonce_field( 'skb_booking', 'skb_nonce' ); ?>
		<div class="hp" aria-hidden="true"><label for="skb-website">Website</label><input id="skb-website" type="text" name="website" tabindex="-1" autocomplete="off"></div>
		<div><label for="skb-name">Họ và tên *</label><input id="skb-name" name="ho_ten" type="text" required autocomplete="name"></div>
		<div><label for="skb-phone">Số điện thoại *</label><input id="skb-phone" name="dien_thoai" type="tel" inputmode="tel" required autocomplete="tel" pattern="[0-9 +.]{9,15}"></div>
		<div><label for="skb-car">Hãng / dòng xe</label>
			<input id="skb-car" name="dong_xe" type="text" list="skb-brands" placeholder="Ví dụ: Mazda 3 2022" value="<?php echo isset( $_GET['xe'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_GET['xe'] ) ) ) : ''; // phpcs:ignore ?>">
			<datalist id="skb-brands">
				<?php if ( ! is_wp_error( $brands ) ) : foreach ( $brands as $b ) : ?>
					<option value="<?php echo esc_attr( $b->name ); ?>"></option>
				<?php endforeach; endif; ?>
			</datalist>
		</div>
		<div><label for="skb-dealer">Đại lý muốn lắp</label>
			<select id="skb-dealer" name="dai_ly">
				<option value="">Để SK tư vấn đại lý gần nhất</option>
				<?php foreach ( $dealers as $d ) : ?>
					<option value="<?php echo esc_attr( $d->post_title ); ?>"><?php echo esc_html( $d->post_title ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div><label for="skb-date">Ngày mong muốn</label><input id="skb-date" name="ngay" type="date" min="<?php echo esc_attr( wp_date( 'Y-m-d' ) ); ?>"></div>
		<div class="full"><label for="skb-note">Ghi chú</label><textarea id="skb-note" name="ghi_chu" placeholder="Mẫu đèn quan tâm, thời gian tiện gọi lại…"></textarea></div>
		<div class="full"><button class="btn btn-light btn-lg" type="submit">Gửi đặt lịch</button></div>
	</form>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'sk_dat_lich', 'skb_booking_form' );

/* ---------------------------------------------------------------
 * Dữ liệu ban đầu khi bật theme (chỉ chạy một lần)
 * ------------------------------------------------------------- */
add_action( 'after_switch_theme', 'skb_seed' );
function skb_seed() {
	if ( get_option( 'skb_seeded' ) ) {
		return;
	}
	$brands = array( 'VinFast', 'Honda', 'Toyota', 'Mazda', 'Ford', 'Hyundai', 'Kia', 'Isuzu', 'BMW', 'Lexus', 'MG', 'Mitsubishi', 'Suzuki', 'Dòng xe khác' );
	foreach ( $brands as $i => $b ) {
		if ( ! term_exists( $b, 'hang_xe' ) ) {
			$t = wp_insert_term( $b, 'hang_xe' );
			if ( ! is_wp_error( $t ) ) {
				update_term_meta( $t['term_id'], 'skb_order', $i );
			}
		}
	}
	$dealers = array(
		array( 'SK Ambient Light Thanh Am', '197 Thanh Am, Việt Hưng, Long Biên, Hà Nội', '0868 055 555', '08:00 – 22:00', 'Hà Nội' ),
		array( 'SK Ambient Light Mỹ Đình', '28 Phạm Hùng, Nam Từ Liêm, Hà Nội', '0365 247 888', '08:00 – 21:00', 'Hà Nội' ),
		array( 'SK Ambient Light Hà Đông', '88 Tố Hữu, Hà Đông, Hà Nội', '0988 125 999', '08:00 – 21:00', 'Hà Nội' ),
		array( 'Tín Auto', '83 Lê Trọng Tấn, Phường Mỹ Phước, Thành phố Long Xuyên, An Giang', '0976 878 763', '08:00 – 18:00', 'An Giang' ),
		array( 'Auto 365 Bắc Giang Cơ sở 2', '614 Xương Giang, Bắc Giang', '0832 666 365', '08:00 – 18:00', 'Bắc Giang' ),
		array( 'Auto 365 Bắc Kạn', '587 Đ. Kon Tum, Phùng Chí Kiên, Bắc Kạn', '0398 265 365', '08:00 – 18:00', 'Bắc Kạn' ),
		array( 'Minh Tân Auto', 'LK 09 khu đô thị Mạnh Đức, vòng xuyến, Dương Lôi, Từ Sơn, Bắc Ninh', '0912 259 779', '08:00 – 18:00', 'Bắc Ninh' ),
		array( 'ChungAuto.vn Bắc Ninh', 'LK 09 khu đô thị Mạnh Đức, vòng xuyến, Dương Lôi, Từ Sơn, Bắc Ninh', '0913 259 779', '08:00 – 18:00', 'Bắc Ninh' ),
		array( 'Auto 365 Bến Tre', 'T604 Ca Văn Thỉnh, P. Phú Khương, Bến Tre', '0967 666 766', '08:00 – 18:00', 'Bến Tre' ),
		array( 'Decal Bình Dương', '161 Khu Phố Hoà Lân 2, Thuận Giao, Thuận An, Bình Dương', '', '08:00 – 18:00', 'Bình Dương' ),
	);
	foreach ( $dealers as $i => $d ) {
		$id = wp_insert_post( array( 'post_type' => 'dai_ly', 'post_status' => 'publish', 'post_title' => $d[0], 'menu_order' => $i ) );
		if ( $id && ! is_wp_error( $id ) ) {
			update_post_meta( $id, '_skb_dia_chi', $d[1] );
			update_post_meta( $id, '_skb_dien_thoai', $d[2] );
			update_post_meta( $id, '_skb_gio_mo', $d[3] );
			update_post_meta( $id, '_skb_tinh', $d[4] );
		}
	}
	$pages = array(
		'dat-lich'          => array( 'Đặt lịch lắp đặt', '<!-- wp:paragraph --><p>Để lại thông tin, SK sẽ gọi lại xác nhận lịch và tư vấn mẫu phù hợp với xe của bạn.</p><!-- /wp:paragraph --><!-- wp:shortcode -->[sk_dat_lich]<!-- /wp:shortcode -->' ),
		'gioi-thieu'        => array( 'Giới thiệu', '' ),
		'huong-dan-su-dung' => array( 'Hướng dẫn sử dụng', '' ),
		'lien-he'           => array( 'Liên hệ', '' ),
		'tin-tuc'           => array( 'Tin tức', '' ),
	);
	foreach ( $pages as $slug => $p ) {
		if ( ! get_page_by_path( $slug ) ) {
			$pid = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => $p[0], 'post_name' => $slug, 'post_content' => $p[1] ) );
			if ( 'tin-tuc' === $slug && $pid ) {
				update_option( 'page_for_posts', $pid );
			}
		}
	}
	update_option( 'permalink_structure', '/%postname%/' );
	update_option( 'blog_public', 0 );
	update_option( 'timezone_string', 'Asia/Ho_Chi_Minh' );
	update_option( 'date_format', 'd/m/Y' );
	update_option( 'skb_seeded', 1 );
	flush_rewrite_rules();
}

/* Sắp xếp hãng xe theo thứ tự đã đặt */
function skb_brands() {
	$terms = get_terms( array( 'taxonomy' => 'hang_xe', 'hide_empty' => false, 'parent' => 0 ) );
	if ( is_wp_error( $terms ) ) {
		return array();
	}
	/* Hãng chưa đặt thứ tự (thêm sau, vd. Nissan) đứng sau các hãng có sẵn, trước "Dòng xe khác" (13). */
	$thu_tu = function ( $t ) {
		$o = get_term_meta( $t->term_id, 'skb_order', true );
		return '' === $o ? 12.5 : (float) $o;
	};
	usort( $terms, function ( $a, $b ) use ( $thu_tu ) {
		return $thu_tu( $a ) <=> $thu_tu( $b );
	} );
	return $terms;
}

/* Menu dự phòng khi chưa tạo menu */
function skb_fallback_menu() {
	$items = array(
		home_url( '/mau-xe/' )            => 'Dòng xe',
		home_url( '/dai-ly/' )            => 'Đại lý',
		home_url( '/huong-dan-su-dung/' ) => 'Hướng dẫn',
		home_url( '/tin-tuc/' )           => 'Tin tức',
		home_url( '/lien-he/' )           => 'Liên hệ',
	);
	echo '<ul>';
	foreach ( $items as $url => $label ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	echo '</ul>';
}

/* v0.7.1: tóm tắt mẫu xe gọn gàng, tách dòng "Nhãn: giá trị" thành bảng thông số */
function skb_tom_tat( $post = null ) {
	$raw = (string) get_post_field( 'post_excerpt', $post ? $post : get_post() );
	$raw = preg_replace( '#<\s*(br|/p|/li|/div)[^>]*>#i', "\n", $raw );
	$txt = html_entity_decode( wp_strip_all_tags( $raw ), ENT_QUOTES, 'UTF-8' );
	$txt = str_replace( "\xC2\xA0", ' ', $txt );
	$txt = preg_replace( '#\(\s*Giá\s+đã\s+bao\s+gồm\s+VAT\s*\)#iu', '', $txt );
	$doan = array();
	$muc  = array();
	foreach ( preg_split( '#\R+#u', $txt ) as $d ) {
		$d = trim( preg_replace( '#\s+#u', ' ', $d ) );
		if ( '' === $d ) {
			continue;
		}
		if ( preg_match( '#^([^:.!?]{2,28}):\s*(.+)$#u', $d, $m ) ) {
			$muc[] = array( trim( $m[1] ), trim( $m[2] ) );
		} else {
			$doan[] = $d;
		}
	}
	return array( 'doan' => $doan, 'muc' => $muc );
}
add_filter( 'the_content', function ( $c ) {
	if ( is_singular() ) {
		$c = preg_replace( '#<(/?)h1(\s|>)#i', '<$1h2$2', $c );
	}
	return $c;
}, 20 );

/* v0.8.0: Tin tức không lẫn bài hướng dẫn (hướng dẫn có trang riêng) */
add_action( 'pre_get_posts', function ( $q ) {
	if ( ! is_admin() && $q->is_main_query() && $q->is_home() ) {
		$t = get_term_by( 'slug', 'huong-dan-su-dung', 'category' );
		if ( $t ) {
			$q->set( 'category__not_in', array( (int) $t->term_id ) );
		}
	}
} );
