<?php
/**
 * Chân trang
 */
$hotline   = skb_opt( 'hotline', '0868 055 555' );
$zalo      = skb_opt( 'zalo', '0868055555' );
$skb_ngoai = function_exists( 'skb_ngu_ngoai' ) && skb_ngu_ngoai();
?>
</main>
<section class="hst" aria-labelledby="hst-h">
	<h2 id="hst-h" class="hst-h"><?php skb_e( 'Hệ sinh thái SK Ambient Light' ); ?></h2>
	<ul class="hst-list">
		<li><div class="hst-card on"><span class="hst-tag"><?php skb_e( 'Bạn đang ở đây' ); ?></span><b><?php skb_e( 'Website chính' ); ?></b><span class="hst-d"><?php echo esc_html( $skb_ngoai ? skb__( 'Mẫu xe, gói sản phẩm và giá' ) : 'Mẫu xe, giá, đại lý và kích hoạt bảo hành' ); ?></span><span class="hst-u">sklight.vn</span></div></li>
		<li><a class="hst-card cat-link" href="https://catalog.sklight.vn/?nguon=web" target="_blank" rel="noopener"><b><?php skb_e( 'Catalog phụ kiện 3D' ); ?></b><span class="hst-d"><?php skb_e( 'Xem từng chi tiết sản phẩm trên mô hình 3D' ); ?></span><span class="hst-u">catalog.sklight.vn <span aria-hidden="true">↗</span></span><span class="sr-only"> (<?php skb_e( 'mở tab mới' ); ?>)</span></a></li>
		<li><a class="hst-card" href="https://www.youtube.com/@SKAmbientLightLuxury" target="_blank" rel="noopener"><b>YouTube</b><span class="hst-d"><?php skb_e( 'Kênh video chính thức' ); ?></span><span class="hst-u">@SKAmbientLightLuxury <span aria-hidden="true">↗</span></span><span class="sr-only"> (<?php skb_e( 'mở tab mới' ); ?>)</span></a></li>
		<li><a class="hst-card" href="https://www.tiktok.com/@sk.ambient.light.luxury" target="_blank" rel="noopener"><b>TikTok</b><span class="hst-d"><?php skb_e( 'Video ngắn' ); ?></span><span class="hst-u">@sk.ambient.light.luxury <span aria-hidden="true">↗</span></span><span class="sr-only"> (<?php skb_e( 'mở tab mới' ); ?>)</span></a></li>
		<li><a class="hst-card" href="https://www.facebook.com/SK.Ambient.Light.Luxury" target="_blank" rel="noopener"><b>Facebook</b><span class="hst-d"><?php skb_e( 'Trang chính thức' ); ?></span><span class="hst-u">SK.Ambient.Light.Luxury <span aria-hidden="true">↗</span></span><span class="sr-only"> (<?php skb_e( 'mở tab mới' ); ?>)</span></a></li>
	</ul>
</section>
<footer class="site-footer">
	<p><?php bloginfo( 'name' ); ?> · <?php echo esc_html( skb_opt( 'address', '197 Thanh Am, Việt Hưng, Long Biên, Hà Nội' ) . ( $skb_ngoai ? ', ' . skb__( 'Việt Nam' ) : '' ) ); ?></p>
	<?php if ( $skb_ngoai ) : ?>
		<p>WhatsApp / <?php skb_e( 'Điện thoại' ); ?> <a href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( skb_tel_quoc_te() ); ?></a> · <a href="<?php echo esc_url( home_url( '/' ) ); ?>">sklight.vn</a> · <a class="cat-link" href="https://catalog.sklight.vn/?nguon=web" target="_blank" rel="noopener"><?php skb_e( 'Catalog phụ kiện' ); ?></a></p>
	<?php else : ?>
		<p>Hotline <a href="tel:<?php echo esc_attr( skb_tel( $hotline ) ); ?>"><?php echo esc_html( $hotline ); ?></a> · <a href="<?php echo esc_url( home_url( '/' ) ); ?>">sklight.vn</a> · <a class="cat-link" href="https://catalog.sklight.vn/?nguon=web" target="_blank" rel="noopener">Catalog phụ kiện</a></p>
		<?php
		$skb_legal = array();
		foreach ( array( 'chinh-sach-bao-mat', 'dieu-khoan-su-dung' ) as $skb_slug ) {
			$skb_pg = get_page_by_path( $skb_slug );
			if ( $skb_pg && 'publish' === $skb_pg->post_status ) {
				$skb_legal[] = '<a href="' . esc_url( get_permalink( $skb_pg ) ) . '">' . esc_html( get_the_title( $skb_pg ) ) . '</a>';
			}
		}
		if ( $skb_legal ) {
			echo '<p>' . implode( ' · ', $skb_legal ) . '</p>'; // phpcs:ignore
		}
		?>
	<?php endif; ?>
</footer>
</div>

<?php if ( $skb_ngoai ) : ?>
<a class="zalo-float wa-float" href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener" aria-label="<?php echo esc_attr( skb__( 'Nhắn WhatsApp' ) ); ?>"><?php echo skb_wa_icon( 26 ); // phpcs:ignore ?></a>
<?php elseif ( $zalo ) : ?>
<a class="zalo-float zalo-pill" href="https://zalo.me/<?php echo esc_attr( skb_tel( $zalo ) ); ?>" target="_blank" rel="noopener" aria-label="Nhắn Zalo cho SK"><svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="currentColor"><path d="M12 3C6.5 3 2 6.8 2 11.5c0 2.6 1.4 5 3.6 6.5L5 21.5l3.900-2c1 .3 2 .4 3.100.4 5.500 0 10-3.800 10-8.400S17.500 3 12 3Z"/></svg><span><span class="zl-l">Tư vấn </span>Zalo</span></a>
<?php endif; ?>

<?php
if ( is_singular( 'mau_xe' ) ) :
	$skb_gia_bar = skb_gia( get_the_ID() );
	$skb_goi     = skb_goi( get_the_ID() );
	$skb_chon    = isset( $_GET['goi'] ) ? absint( $_GET['goi'] ) - 1 : -1; // phpcs:ignore
	$skb_chon    = $skb_chon < count( $skb_goi ) ? $skb_chon : -1;
	if ( $skb_ngoai ) :
		list( $skb_n, $skb_tu ) = skb_ngu_bo_gia( get_the_ID() );
		$skb_xe      = skb_ten_xe() . ( $skb_chon >= 0 ? ' — ' . skb_ngu_ten_goi( $skb_goi[ $skb_chon ]['ten'] ) : '' );
		$skb_bar_txt = $skb_chon >= 0 ? skb_ngu_ten_goi( $skb_goi[ $skb_chon ]['ten'] ) . ' · ' . ( $skb_goi[ $skb_chon ]['gia'] ? skb_ngu_tien( $skb_goi[ $skb_chon ]['gia'] ) : skb__( 'Liên hệ' ) ) : ( $skb_tu ? sprintf( skb__( 'Từ %s' ), skb_ngu_tien( $skb_tu ) ) : '' );
		?>
<nav class="mbar mbar-xe" aria-label="<?php echo esc_attr( skb__( 'Thao tác nhanh' ) ); ?>">
	<a class="mbar-call" href="tel:+<?php echo esc_attr( skb_wa_so() ); ?>" aria-label="<?php echo esc_attr( skb__( 'Gọi' ) . ' ' . skb_tel_quoc_te() ); ?>"><svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></a>
	<a class="main" data-wa href="<?php echo esc_url( skb_wa_url( $skb_xe ) ); ?>" target="_blank" rel="noopener"><span><?php skb_e( 'Nhắn WhatsApp về mẫu này' ); ?></span><?php if ( $skb_bar_txt ) : ?><small data-gia-bar><?php echo esc_html( $skb_bar_txt ); ?></small><?php endif; ?></a>
</nav>
	<?php else :
		$skb_xe      = get_the_title() . ( $skb_chon >= 0 ? ' — ' . $skb_goi[ $skb_chon ]['ten'] : '' );
		$skb_bar_txt = $skb_chon >= 0 ? $skb_goi[ $skb_chon ]['ten'] . ' · ' . ( $skb_goi[ $skb_chon ]['gia'] ? skb_tien( $skb_goi[ $skb_chon ]['gia'] ) : 'Liên hệ' ) : ( $skb_gia_bar ? 'Từ ' . $skb_gia_bar . ( $skb_goi ? ' · ' . count( $skb_goi ) . ' gói' : '' ) : '' );
		?>
<nav class="mbar mbar-xe" aria-label="Thao tác nhanh">
	<a class="mbar-call" href="tel:<?php echo esc_attr( skb_tel( $hotline ) ); ?>" aria-label="Gọi <?php echo esc_attr( $hotline ); ?>"><svg aria-hidden="true" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg></a>
	<a class="main" data-book href="<?php echo esc_url( add_query_arg( 'xe', rawurlencode( $skb_xe ), home_url( '/dat-lich/' ) ) ); ?>"><span><?php echo $skb_chon >= 0 ? 'Đặt lịch lắp gói này' : 'Đặt lịch lắp mẫu này'; ?></span><?php if ( $skb_bar_txt ) : ?><small data-gia-bar><?php echo esc_html( $skb_bar_txt ); ?></small><?php endif; ?></a>
</nav>
	<?php endif; ?>
<?php elseif ( $skb_ngoai ) : ?>
<nav class="mbar" aria-label="<?php echo esc_attr( skb__( 'Thao tác nhanh' ) ); ?>">
	<a href="tel:+<?php echo esc_attr( skb_wa_so() ); ?>"><?php skb_e( 'Gọi' ); ?></a>
	<a class="main" href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php skb_e( 'Nhắn WhatsApp' ); ?></a>
	<a href="<?php echo esc_url( get_post_type_archive_link( 'mau_xe' ) ); ?>"><?php skb_e( 'Dòng xe' ); ?></a>
</nav>
<?php else : ?>
<nav class="mbar" aria-label="Thao tác nhanh">
	<a href="tel:<?php echo esc_attr( skb_tel( $hotline ) ); ?>">Gọi</a>
	<a class="main" href="<?php echo esc_url( home_url( '/dat-lich/' ) ); ?>">Đặt lịch</a>
	<a href="<?php echo esc_url( skb_activate_url() ); ?>">Kích hoạt</a>
</nav>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
