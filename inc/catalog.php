<?php
/**
 * Nối web với catalog phụ kiện (catalog.sklight.vn).
 *
 * - Mỗi mẫu xe có một link catalog: ô "Link catalog" trong trang sửa mẫu xe (meta _skb_catalog, nhập đường dẫn
 *   đầy đủ hoặc phần sau catalog.sklight.vn) → bảng ghép sẵn theo đường dẫn mẫu xe (lấy từ catalog 10/11/2026)
 *   → trang hãng trên catalog theo Hãng xe → mục "Dùng chung mọi xe".
 * - Trang mẫu xe có khối "Phụ kiện khác cho …"; trang hãng xe và chân trang có link catalog.
 * - Khách từ catalog tới (?nguon=catalog) và lượt bấm sang catalog được đếm trong Thống kê web (inc/su-kien.php, main.js).
 */

defined( 'ABSPATH' ) || exit;

define( 'SKB_CATALOG_URL', 'https://catalog.sklight.vn' );

/** Đường dẫn mẫu xe trên web → trang dòng xe trên catalog. */
function skb_catalog_bang() {
	return array(
		'toyota-corolla-cross-2019-2025'                  => '/c/toyota/corolla-cross',
		'toyota-camry-2018-2024'                          => '/c/toyota/camry-19-24',
		'toyota-camry-2025'                               => '/c/toyota/camry-25',
		'toyota-fortuner-2017-2024'                       => '/c/toyota/fortuner',
		'toyota-corolla-altis-2022-2025'                  => '/c/toyota/corolla-altis',
		'led-vien-noi-that-lexus-es-gen-6-2018-2022'      => '/c/lexus/lexus-es',
		'led-vien-noi-that-lexus-rx-2016-2022'            => '/c/lexus/lexus-rx',
		'mazda-cx-5-cx-8-2017-2025'                       => '/c/mazda/cx5-cx8-18-24',
		'mazda3-2014-2018'                                => '/c/mazda/mazda3-15-19',
		'mazda3-2019-2025'                                => '/c/mazda/mazda3-20-24',
		'led-vien-noi-that-honda-civic-gen-10-2016-2021'  => '/c/honda/civic-gen-10',
		'honda-civic-gen-11-2021-2025'                    => '/c/honda/civic-gen-11',
		'led-vien-noi-that-honda-crv-2017-2022'           => '/c/honda/crv-18-23',
		'led-vien-noi-that-honda-crv-2023-2027'           => '/c/honda/crv-24-27',
		'honda-accord-2018-2024'                          => '/c/honda/accord-19-24',
		'honda-city-2021-2024'                            => '/c/honda/city-21-26',
		'led-vien-noi-that-ford-ranger'                   => '/c/ford/next-gen-22-25',
		'led-vien-noi-that-ford-everest'                  => '/c/ford/everest',
		'led-noi-that-xpander-2022-2025'                  => '/c/mitsubishi/xpander-2022-2025',
		'led-noi-that-xpander-2018-2022'                  => '/c/mitsubishi/xpander-2017-2021',
		'led-noi-that-vinfast-vf3'                        => '/c/vinfast/vf3',
		'led-noi-that-vinfast-vf8'                        => '/c/vinfast/vf8',
		'led-noi-that-bmw'                                => '/c/bmw/bmw',
		'led-vien-noi-that-mg5'                           => '/c/mg/mg5-2022-2025',
		'led-vien-noi-that-hyundai-elantra-2021-2025'     => '/c/hyundai/elantra',
	);
}

/** Hãng có trang riêng trên catalog (khoá đã bỏ dấu, viết thường). */
function skb_catalog_hang() {
	return array(
		'toyota'     => '/c/toyota',
		'lexus'      => '/c/lexus',
		'mazda'      => '/c/mazda',
		'honda'      => '/c/honda',
		'ford'       => '/c/ford',
		'mg'         => '/c/mg',
		'mercedes'   => '/c/mercedes',
		'mitsubishi' => '/c/mitsubishi',
		'vinfast'    => '/c/vinfast',
		'hyundai'    => '/c/hyundai',
		'huyndai'    => '/c/hyundai',
		'bmw'        => '/c/bmw',
		'porsche'    => '/c/porsche',
	);
}

function skb_catalog_full( $path ) {
	$path = trim( (string) $path );
	if ( '' === $path ) {
		return '';
	}
	if ( preg_match( '#^https?://#i', $path ) ) {
		return $path;
	}
	return SKB_CATALOG_URL . '/' . ltrim( $path, '/' );
}

/** Trang hãng trên catalog cho một Hãng xe (dùng hãng gốc nếu là dòng xe con). */
function skb_catalog_cho_hang( $term ) {
	if ( ! $term || is_wp_error( $term ) ) {
		return array( skb_catalog_full( '/c/dung-chung-moi-xe' ), 'Dùng chung mọi xe', false );
	}
	$root = $term;
	while ( $root->parent ) {
		$p = get_term( $root->parent, 'hang_xe' );
		if ( ! $p || is_wp_error( $p ) ) {
			break;
		}
		$root = $p;
	}
	$k    = sanitize_title( remove_accents( $root->name ) );
	$hang = skb_catalog_hang();
	if ( isset( $hang[ $k ] ) ) {
		return array( skb_catalog_full( $hang[ $k ] ), $root->name, true );
	}
	return array( skb_catalog_full( '/c/dung-chung-moi-xe' ), 'Dùng chung mọi xe', false );
}

/** Link catalog của một mẫu xe: array( url, nhãn, mức ) — mức: rieng | dong | hang | chung. */
function skb_catalog_cho_mau( $post_id ) {
	$tay = get_post_meta( $post_id, '_skb_catalog', true );
	if ( $tay ) {
		return array( skb_catalog_full( $tay ), get_the_title( $post_id ), 'rieng' );
	}
	$bang = skb_catalog_bang();
	$slug = get_post_field( 'post_name', $post_id );
	if ( isset( $bang[ $slug ] ) ) {
		return array( skb_catalog_full( $bang[ $slug ] ), get_the_title( $post_id ), 'dong' );
	}
	$terms = get_the_terms( $post_id, 'hang_xe' );
	$h     = skb_catalog_cho_hang( $terms && ! is_wp_error( $terms ) ? $terms[0] : null );
	return array( $h[0], $h[1], $h[2] ? 'hang' : 'chung' );
}

/** Khối "Phụ kiện khác cho …" trên trang mẫu xe. */
function skb_catalog_khoi( $post_id ) {
	list( $url, $ten, $muc ) = skb_catalog_cho_mau( $post_id );
	if ( ! $url ) {
		return;
	}
	$tieu_de = in_array( $muc, array( 'rieng', 'dong' ), true ) ? 'Phụ kiện khác cho xe này' : ( 'hang' === $muc ? 'Phụ kiện khác cho xe ' . $ten : 'Phụ kiện dùng chung mọi xe' );
	?>
	<aside class="cat-khoi" aria-labelledby="cat-khoi-h">
		<div>
			<h2 id="cat-khoi-h"><?php echo esc_html( $tieu_de ); ?></h2>
			<p>Đèn sao trần, loa treble, đèn chân kính, điều khiển bằng giọng nói AI… kèm giá niêm yết, trên catalog phụ kiện SK.</p>
		</div>
		<a class="btn btn-ghost cat-link" href="<?php echo esc_url( add_query_arg( 'nguon', 'web', $url ) ); ?>" target="_blank" rel="noopener">Xem catalog phụ kiện <span aria-hidden="true">↗</span><span class="sr-only"> (mở tab mới)</span></a>
	</aside>
	<?php
}

/** Link "Trải nghiệm toàn diện chi tiết sản phẩm" ở đầu trang mẫu xe → trang xe trên catalog (xem 3D trên xe). */
function skb_catalog_dau( $post_id ) {
	list( $url ) = skb_catalog_cho_mau( $post_id );
	if ( ! $url ) {
		return;
	}
	?>
	<a class="mx-3d" href="<?php echo esc_url( add_query_arg( 'nguon', 'web', $url ) ); ?>" target="_blank" rel="noopener">
		<span class="mx-3d-i" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.8l8 4.6v9.2l-8 4.6-8-4.6V7.4z"/><path d="M4 7.4l8 4.6 8-4.6M12 12v9.2"/></svg></span>
		<span class="mx-3d-t"><strong><?php skb_e( 'Trải nghiệm toàn diện chi tiết sản phẩm' ); ?> <em>3D</em></strong><small><?php skb_e( 'Xem mô hình 3D trên xe tại catalog SK' ); ?></small></span>
		<span class="mx-3d-go" aria-hidden="true">↗</span><span class="sr-only"> (<?php skb_e( 'mở catalog ở tab mới' ); ?>)</span>
	</a>
	<?php
}

/* Ô "Link catalog" trong trang sửa mẫu xe */
add_action( 'add_meta_boxes_mau_xe', function () {
	add_meta_box( 'skb_catalog_box', 'Link catalog phụ kiện', 'skb_catalog_box', 'mau_xe', 'side', 'default' );
} );

function skb_catalog_box( $post ) {
	wp_nonce_field( 'skb_catalog_save', 'skb_catalog_nonce' );
	$tay   = get_post_meta( $post->ID, '_skb_catalog', true );
	$bang  = skb_catalog_bang();
	$terms = get_the_terms( $post->ID, 'hang_xe' );
	$mac   = isset( $bang[ $post->post_name ] ) ? skb_catalog_full( $bang[ $post->post_name ] ) : skb_catalog_cho_hang( $terms && ! is_wp_error( $terms ) ? $terms[0] : null )[0];
	?>
	<p><label for="skb-catalog" class="screen-reader-text">Link catalog</label>
	<input type="text" id="skb-catalog" name="skb_catalog" class="widefat" value="<?php echo esc_attr( $tay ); ?>" placeholder="<?php echo esc_attr( str_replace( SKB_CATALOG_URL, '', $mac ) ); ?>"></p>
	<p class="description">Để trống thì dùng: <a href="<?php echo esc_url( $mac ); ?>" target="_blank" rel="noopener"><?php echo esc_html( str_replace( SKB_CATALOG_URL, 'catalog', $mac ) ); ?></a>. Có thể dán cả link catalog, ví dụ <code>/c/toyota/fortuner</code>.</p>
	<?php
}

add_action( 'save_post_mau_xe', function ( $post_id ) {
	if ( ! isset( $_POST['skb_catalog_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['skb_catalog_nonce'] ) ), 'skb_catalog_save' ) ) {
		return;
	}
	if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	$v = isset( $_POST['skb_catalog'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['skb_catalog'] ) ) ) : '';
	if ( '' !== $v ) {
		$v = preg_replace( '#^https?://catalog\.sklight\.vn#i', '', $v );
		if ( preg_match( '#^https?://#i', $v ) ) {
			$v = esc_url_raw( $v );
		} else {
			$v = '/' . ltrim( preg_replace( '#[^a-z0-9/_\-]#i', '', $v ), '/' );
		}
	}
	if ( '' === $v || '/' === $v ) {
		delete_post_meta( $post_id, '_skb_catalog' );
	} else {
		update_post_meta( $post_id, '_skb_catalog', $v );
	}
} );
