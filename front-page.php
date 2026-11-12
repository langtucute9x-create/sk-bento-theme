<?php
/**
 * Trang chủ - bố cục thoáng: hero tràn màn hình, các phần tách bằng khoảng trống
 */
get_header();
$activate    = skb_activate_url();
$hotline     = skb_opt( 'hotline', '0868 055 555' );
$model_total = (int) wp_count_posts( 'mau_xe' )->publish;
$brand_total = count( skb_brands() );
$ngoai       = skb_ngu_ngoai();
$dealer_q    = $ngoai ? null : new WP_Query( array( 'post_type' => 'dai_ly', 'posts_per_page' => 10, 'orderby' => array( 'menu_order' => 'ASC', 'title' => 'ASC' ) ) );
?>
<section class="hero2 full-bleed<?php echo 'anh' === skb_hero_mode() ? ' has-photo' : ''; ?>" id="hero" data-led="rainbow" aria-labelledby="hero-title">
	<img class="hero2-img" src="<?php echo esc_url( skb_img( 'hero_image', 'Camera_XHS_17088886025041040g00830nd7sijp6k3048veh9175o11g6a6mv0-1.webp' ) ); ?>" alt="<?php echo esc_attr( skb__( 'Nội thất xe lắp đèn SK Ambient Light' ) ); ?>" fetchpriority="<?php echo 'anh' === skb_hero_mode() ? 'low' : 'high'; ?>">
	<div class="hero2-shade" aria-hidden="true"></div>
<?php if ( 'anh' === skb_hero_mode() ) :
	$sets  = skb_hero_sets();
	$first = $sets[0];
	$slots = array( 'ngang' => '.35', 'loa' => '.25', 'doc' => '.55', 'cua' => '1', 'taplo' => '.8' );
?>
	<svg class="hsvg" width="0" height="0" aria-hidden="true" focusable="false"><filter id="ledTint" color-interpolation-filters="sRGB"><feColorMatrix id="ledMx" type="matrix" values="1 0 0 0 0 0 1 0 0 0 0 0 1 0 0 0 0 0 1 0"/></filter></svg>
	<div class="hstage" id="hstage" aria-hidden="true" data-sets="<?php echo esc_attr( wp_json_encode( $sets ) ); ?>">
		<div class="hglow"></div>
		<?php foreach ( $slots as $slot => $depth ) : $it = $first[ $slot ]; ?>
		<div class="hp hp-<?php echo esc_attr( $slot ); ?>" data-d="<?php echo esc_attr( $depth ); ?>" data-slot="<?php echo esc_attr( $slot ); ?>"<?php echo $it ? '' : ' hidden'; ?>><div class="hp-in"><img<?php if ( $it ) { printf( ' src="%s" width="%d" height="%d"', esc_url( $it['u'] ), (int) $it['w'], (int) $it['h'] ); if ( ! empty( $it['m'] ) ) { printf( ' srcset="%s %dw, %s %dw"', esc_url( $it['m'] ), (int) $it['mw'], esc_url( $it['u'] ), (int) $it['w'] ); } } ?> sizes="<?php echo esc_attr( skb_hero_sizes( $slot ) ); ?>" alt="" decoding="async"<?php echo in_array( $slot, array( 'cua', 'ngang' ), true ) ? ' fetchpriority="high"' : ''; ?>></div></div>
		<?php endforeach; ?>
	</div>
<?php else : ?>
	<canvas id="hero3d" class="hero3d" aria-hidden="true"></canvas>
<?php endif; ?>
	<div class="hero2-line" aria-hidden="true"><span></span></div>
	<div class="hero2-inner">
		<p class="hero-eyebrow"><?php echo esc_html( $ngoai ? skb__( 'Đèn nội thất ô tô cao cấp' ) : skb_opt( 'hero_eyebrow', 'Đèn nội thất ô tô cao cấp' ) ); ?></p>
		<h1 id="hero-title"><?php echo esc_html( $ngoai ? skb__( 'Ánh sáng thiết kế riêng' ) : skb_opt( 'hero_title_1', 'Ánh sáng thiết kế riêng' ) ); ?><br><span class="grad-text"><?php echo esc_html( $ngoai ? skb__( 'cho xe bạn.' ) : skb_opt( 'hero_title_2', 'cho xe bạn.' ) ); ?></span></h1>
		<p class="hero-sub"><?php echo esc_html( $ngoai ? skb__( '@hero_sub' ) : skb_opt( 'hero_sub', 'Ốp thay zin theo từng dòng xe, không cắt chích dây, lắp đặt tại hệ thống đại lý SK toàn quốc.' ) ); ?></p>
		<?php skb_tim_xe_box( 'hero' ); ?>
		<div class="hero2-cta">
			<a class="btn btn-light btn-lg" href="<?php echo esc_url( add_query_arg( 'nguon', 'web', SKB_CATALOG_URL . '/' ) ); ?>" target="_blank" rel="noopener"><?php skb_e( 'Xem catalog sản phẩm' ); ?> <span aria-hidden="true">↗</span><span class="sr-only"> (<?php skb_e( 'mở tab mới' ); ?>)</span></a>
			<?php if ( $ngoai ) : ?>
				<a class="btn btn-ghost btn-lg btn-wa" href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php echo skb_wa_icon( 18 ); // phpcs:ignore ?> <?php skb_e( 'Nhắn WhatsApp' ); ?></a>
			<?php else : ?>
				<a class="btn btn-ghost btn-lg" href="<?php echo esc_url( $activate ); ?>">Kích hoạt sản phẩm</a>
			<?php endif; ?>
		</div>
		<div class="led-pick" role="group" aria-label="<?php echo esc_attr( skb__( 'Thử màu đèn LED' ) ); ?>">
			<span><?php skb_e( 'Thử màu đèn' ); ?></span>
			<button type="button" class="led-swatch" data-led="rainbow" aria-pressed="true" aria-label="<?php echo esc_attr( skb__( 'Đa sắc' ) ); ?>" style="background:conic-gradient(#F49B3F,#DF1C4E,#8B2BB8,#3FA9FF,#F49B3F)"></button>
			<button type="button" class="led-swatch" data-led="tim" aria-pressed="false" aria-label="<?php echo esc_attr( skb__( 'Tím' ) ); ?>" style="background:#9B3CFF"></button>
			<button type="button" class="led-swatch" data-led="xanh" aria-pressed="false" aria-label="<?php echo esc_attr( skb__( 'Xanh dương' ) ); ?>" style="background:#2F7BFF"></button>
			<button type="button" class="led-swatch" data-led="do" aria-pressed="false" aria-label="<?php echo esc_attr( skb__( 'Đỏ' ) ); ?>" style="background:#FF2D55"></button>
			<button type="button" class="led-swatch" data-led="trang" aria-pressed="false" aria-label="<?php echo esc_attr( skb__( 'Trắng ấm' ) ); ?>" style="background:#FFE9D0"></button>
		</div>
	</div>
</section>

<section class="features" aria-label="<?php echo esc_attr( skb__( 'Ưu điểm' ) ); ?>">
	<div class="feat">
		<span class="feat-i" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="5" width="18" height="14" rx="3"/><path d="M3 10h18"/></svg></span>
		<p class="feat-t"><?php skb_e( 'Ốp thay zin theo xe' ); ?></p>
		<p class="feat-d"><?php skb_e( 'Chuẩn form từng đời xe, giữ vẻ nguyên bản.' ); ?></p>
	</div>
	<div class="feat">
		<span class="feat-i" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l8 3v6c0 5-3.5 8-8 9-4.5-1-8-4-8-9V6z"/><path d="M9 12l2 2 4-4"/></svg></span>
		<p class="feat-t"><?php skb_e( 'Không cắt dây zin' ); ?></p>
		<p class="feat-d"><?php skb_e( 'Lắp giắc chờ, an toàn điện nguyên bản.' ); ?></p>
	</div>
	<div class="feat">
		<span class="feat-i" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="3" width="6" height="11" rx="3"/><path d="M5 11a7 7 0 0014 0M12 18v3"/></svg></span>
		<p class="feat-t"><?php echo esc_html( $ngoai ? skb__( 'Đổi màu theo nhạc' ) : 'Giọng nói tiếng Việt' ); ?></p>
		<p class="feat-d"><?php echo esc_html( $ngoai ? skb__( 'Hiệu ứng nháy theo nhịp nhạc trong xe.' ) : 'Ra lệnh, đèn đổi màu ngay.' ); ?></p>
	</div>
	<div class="feat">
		<span class="feat-i" aria-hidden="true"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><rect x="7" y="3" width="10" height="18" rx="2"/><path d="M11 18h2"/></svg></span>
		<p class="feat-t"><?php skb_e( 'Đồng bộ app' ); ?></p>
		<p class="feat-d"><?php skb_e( 'Màu, hiệu ứng theo nhạc trên điện thoại.' ); ?></p>
	</div>
</section>

<section class="sec" aria-labelledby="gal-title">
	<div class="sec-head">
		<h2 id="gal-title"><?php skb_e( 'Không gian ánh sáng đẳng cấp' ); ?></h2>
		<p><?php skb_e( 'Xe thật, lắp tại hệ thống SK' ); ?></p>
	</div>
	<div class="gallery2">
		<figure class="g-big"><img src="<?php echo esc_url( skb_img( 'gal_1', 'Camera_XHS_17088885987411040g00830nd7sijp6k3g48veh9175o11ipa2j98.webp' ) ); ?>" alt="<?php echo esc_attr( skb__( 'Toàn cảnh khoang xe sáng đèn' ) ); ?>" loading="lazy"></figure>
		<figure><img src="<?php echo esc_url( skb_img( 'img_door', 'IMG_1424.webp' ) ); ?>" alt="<?php echo esc_attr( skb__( 'Ốp cửa phát sáng thay zin' ) ); ?>" loading="lazy"><figcaption><?php skb_e( 'Ốp cửa thay zin' ); ?></figcaption></figure>
		<figure><img src="<?php echo esc_url( skb_img( 'gal_2', 'IMG_2917.webp' ) ); ?>" alt="<?php echo esc_attr( skb__( 'Đèn bệ cần số' ) ); ?>" loading="lazy"><figcaption><?php skb_e( 'Bệ cần số' ); ?></figcaption></figure>
		<figure><img src="<?php echo esc_url( skb_img( 'img_curve', 'IMG_2915.webp' ) ); ?>" alt="<?php echo esc_attr( skb__( 'Dải đèn viền nội thất' ) ); ?>" loading="lazy"><figcaption><?php skb_e( 'Dải viền taplo' ); ?></figcaption></figure>
		<figure><img src="<?php echo esc_url( skb_img( 'gal_3', 'Camera_XHS_17088885963761040g00830nd7sijp6k4048veh9175o11dd9rn90-1.webp' ) ); ?>" alt="<?php echo esc_attr( skb__( 'Đèn taplo và cửa' ) ); ?>" loading="lazy"><figcaption><?php skb_e( 'Taplo và cửa' ); ?></figcaption></figure>
	</div>
</section>

<?php $skb_vids = skb_video_trang_chu(); if ( $skb_vids ) : ?>
<section class="sec" aria-labelledby="vid-title">
	<div class="sec-head">
		<h2 id="vid-title"><?php skb_e( 'Xe thật, khách thật' ); ?></h2>
		<p><?php skb_e( 'Video lắp đặt tại hệ thống SK · bấm để xem' ); ?></p>
	</div>
	<div class="khach-rail khach-rail-home">
		<?php foreach ( $skb_vids as $v ) : if ( $ngoai ) { $v['t'] = ! empty( $v['id'] ) ? skb_ten_xe( $v['id'] ) : $v['t']; $v['u'] = skb_ngu_url( $v['u'] ); } ?>
			<figure class="vid-card">
				<?php echo skb_yt_facade( $v['yt'], $v['t'] ); // phpcs:ignore ?>
				<figcaption><a href="<?php echo esc_url( $v['u'] ); ?>"><?php echo esc_html( $v['t'] ); ?> <span aria-hidden="true">→</span></a></figcaption>
			</figure>
		<?php endforeach; ?>
	</div>
</section>
<?php endif; ?>

<?php if ( ! $ngoai ) : ?>
<section class="activate-band" aria-labelledby="act-title">
	<div>
		<p class="lead">Bảo hành điện tử toàn quốc</p>
		<h2 id="act-title">Kích hoạt sản phẩm, nhận mã dùng app.</h2>
	</div>
	<a class="btn btn-dark-solid btn-lg" href="<?php echo esc_url( $activate ); ?>">Kích hoạt ngay <span aria-hidden="true">→</span></a>
</section>
<?php endif; ?>

<section id="dong-xe" class="sec" aria-labelledby="brand-title">
	<div class="sec-head">
		<h2 id="brand-title"><?php skb_e( 'Chọn dòng xe' ); ?></h2>
		<p><?php echo $model_total ? esc_html( sprintf( skb__( '%1$d mẫu cho %2$d hãng' ), $model_total, $brand_total ) ) : esc_html( sprintf( skb__( '%d hãng xe' ), $brand_total ) ); ?></p>
	</div>
	<?php get_template_part( 'template-parts/brand-grid' ); ?>
</section>

<?php if ( ! $ngoai ) : ?>
<section id="dai-ly" class="sec" aria-labelledby="dealer-title">
	<div class="sec-head">
		<h2 id="dealer-title">Đại lý lắp đặt toàn quốc</h2>
		<p><a href="<?php echo esc_url( get_post_type_archive_link( 'dai_ly' ) ); ?>"><?php echo esc_html( (int) wp_count_posts( 'dai_ly' )->publish ); ?> điểm · Xem tất cả →</a></p>
	</div>
	<div class="dl-hero">
		<?php get_template_part( 'template-parts/dealer-map' ); ?>
		<div class="dl-side">
			<h3>Lắp tại đại lý gần bạn, bảo hành chính hãng</h3>
			<p>Chạm vào cột sáng trên bản đồ để xem đại lý ở tỉnh đó, hoặc bấm Đại lý gần tôi để xếp theo khoảng cách. Nơi chưa có đại lý, SK tư vấn điểm lắp gần nhất.</p>
			<div class="row">
				<a class="btn btn-light btn-lg" href="<?php echo esc_url( add_query_arg( 'gan', '1', get_post_type_archive_link( 'dai_ly' ) ) ); ?>">Đại lý gần tôi</a>
				<a class="btn btn-dark btn-lg" href="tel:<?php echo esc_attr( skb_tel( $hotline ) ); ?>">Gọi <?php echo esc_html( $hotline ); ?></a>
			</div>
		</div>
	</div>
	<div class="dealers">
		<?php
		while ( $dealer_q->have_posts() ) {
			$dealer_q->the_post();
			get_template_part( 'template-parts/dealer-card' );
		}
		wp_reset_postdata();
		?>
	</div>
</section>

<?php endif; ?>

<?php if ( $ngoai ) : ?>
<section class="sec cta2">
	<h2><?php skb_e( 'Sẵn sàng thay đổi cảm xúc lái xe?' ); ?></h2>
	<p><?php skb_e( '@cta2_sub' ); ?></p>
	<div class="row">
		<a class="btn btn-light btn-lg btn-wa" href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php echo skb_wa_icon( 18 ); // phpcs:ignore ?> <?php skb_e( 'Nhắn WhatsApp' ); ?></a>
		<a class="btn btn-dark btn-lg" href="<?php echo esc_url( get_post_type_archive_link( 'mau_xe' ) ); ?>"><?php skb_e( 'Chọn dòng xe' ); ?></a>
	</div>
</section>
<?php else : ?>
<section class="sec cta2">
	<h2>Sẵn sàng thay đổi cảm xúc lái xe?</h2>
	<p>Đặt lịch tư vấn và lắp đặt tại đại lý gần nhất.</p>
	<div class="row">
		<a class="btn btn-light btn-lg" href="<?php echo esc_url( home_url( '/dat-lich/' ) ); ?>">Đặt lịch lắp đặt</a>
		<a class="btn btn-dark btn-lg" href="tel:<?php echo esc_attr( skb_tel( $hotline ) ); ?>">Gọi <?php echo esc_html( $hotline ); ?></a>
	</div>
</section>
<?php endif; ?>
<?php
get_footer();
