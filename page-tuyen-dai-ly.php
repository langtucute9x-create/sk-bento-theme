<?php
/**
 * Trang Tuyển đại lý (/tuyen-dai-ly/): chỉ nút gọi và Zalo, không form.
 * Ảnh thật trong assets/img/tuyen-dai-ly. Chữ theo brief chủ gửi 12/11/2026:
 * không ghi giá, không chia hạng; lắp đặt 20-50%, cấp 1 5-20%.
 */
get_header();
the_post();
$skb_hotline = skb_opt( 'hotline', '0868 055 555' );
$skb_zalo    = skb_opt( 'zalo', '0868055555' );
$skb_anh     = get_template_directory_uri() . '/assets/img/tuyen-dai-ly/';
$skb_dem     = wp_count_posts( 'mau_xe' );
$skb_so_mau  = ( $skb_dem && ! empty( $skb_dem->publish ) ) ? (int) $skb_dem->publish : 69;
?>
<div class="tdl">

	<section class="tdl-sec">
		<div>
			<p class="tdl-eyebrow">Bộ LED ốp hoạ tiết theo từng dòng xe</p>
			<h1>Tuyển đại lý toàn quốc</h1>
			<p class="tdl-lead">Lợi nhuận <span class="tdl-grad">20-50%</span> mỗi bộ</p>
			<ul class="tdl-loi">
				<li>Giá được giữ</li>
				<li>Bảo hành 24 tháng</li>
				<li>Mẫu mới liên tục</li>
				<li>Hỗ trợ kỹ thuật</li>
			</ul>
			<div class="tdl-cta">
				<a class="btn btn-light btn-lg" href="tel:<?php echo esc_attr( skb_tel( $skb_hotline ) ); ?>">Gọi <?php echo esc_html( $skb_hotline ); ?></a>
				<a class="btn btn-dark btn-lg" href="https://zalo.me/<?php echo esc_attr( skb_tel( $skb_zalo ) ); ?>" target="_blank" rel="noopener">Nhắn Zalo</a>
			</div>
		</div>
		<figure class="tdl-photo"><img src="<?php echo esc_url( $skb_anh . 'vf6-07-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'vf6-07-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'vf6-07-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="1600" alt="LED nội thất VinFast VF6 mẫu mới, ốp hoạ tiết phát sáng trên táp lô và cánh cửa" style="object-position:50% 58%" fetchpriority="high"></figure>
	</section>

	<section class="tdl-sec dao">
		<div>
			<p class="tdl-eyebrow">Lợi nhuận của đại lý lắp đặt</p>
			<span class="tdl-num tdl-grad">20-50%</span>
			<h2>Lãi cao. Giá được giữ.</h2>
			<ul class="tdl-gach">
				<li>Một mặt bằng giá, không lo phá giá</li>
				<li>Thương hiệu uy tín, bảo hành 24 tháng</li>
			</ul>
		</div>
		<figure class="tdl-photo ngang"><img src="<?php echo esc_url( $skb_anh . 'vf6-11-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'vf6-11-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'vf6-11-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="900" alt="Tấm ốp LED hoạ tiết trên táp lô VinFast VF6, chụp gần" style="object-position:50% 50%" loading="lazy" decoding="async"></figure>
	</section>

	<section class="tdl-mau">
		<div class="tdl-mau-dau">
			<div>
				<p class="tdl-eyebrow">Luôn có hàng mới để bán</p>
				<div class="tdl-mau-so">
					<span class="tdl-num tdl-grad"><?php echo esc_html( $skb_so_mau ); ?></span>
					<h2>mẫu xe có bộ LED riêng</h2>
				</div>
			</div>
			<p class="tdl-text">Ốp hoạ tiết phát sáng làm riêng theo từng dòng xe</p>
		</div>
		<div class="tdl-luoi">
			<figure class="tdl-o"><div><img src="<?php echo esc_url( $skb_anh . 'vf6-23-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'vf6-23-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'vf6-23-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="1600" alt="LED nội thất VinFast VF6, ốp táp lô phía ghế phụ" style="object-position:50% 38%" loading="lazy" decoding="async"></div><figcaption>VinFast VF6</figcaption></figure>
			<figure class="tdl-o"><div><img src="<?php echo esc_url( $skb_anh . 'vf6-09-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'vf6-09-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'vf6-09-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="900" alt="LED nội thất VinFast VF6, ốp tay nắm cửa" style="object-position:40% 50%" loading="lazy" decoding="async"></div><figcaption>VinFast VF6</figcaption></figure>
			<figure class="tdl-o"><div><img src="<?php echo esc_url( $skb_anh . 'vf7-06-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'vf7-06-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'vf7-06-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="1600" alt="LED nội thất VinFast VF7 mẫu Ma trận, ốp tay nắm cửa" style="object-position:50% 62%" loading="lazy" decoding="async"></div><figcaption>VinFast VF7</figcaption></figure>
			<figure class="tdl-o"><div><img src="<?php echo esc_url( $skb_anh . 'vf7-08-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'vf7-08-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'vf7-08-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="1600" alt="LED nội thất VinFast VF7 mẫu Ma trận, màu hồng" style="object-position:50% 62%" loading="lazy" decoding="async"></div><figcaption>VinFast VF7</figcaption></figure>
			<figure class="tdl-o"><div><img src="<?php echo esc_url( $skb_anh . 'civic-sao-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'civic-sao-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'civic-sao-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="1289" alt="LED nội thất Honda Civic mẫu full sao trên cánh cửa" style="object-position:50% 45%" loading="lazy" decoding="async"></div><figcaption>Honda Civic</figcaption></figure>
			<figure class="tdl-o"><div><img src="<?php echo esc_url( $skb_anh . 'everest-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'everest-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'everest-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="1600" alt="LED nội thất Ford Everest, cửa và sàn xe" style="object-position:30% 45%" loading="lazy" decoding="async"></div><figcaption>Ford Everest</figcaption></figure>
		</div>
	</section>

	<section class="tdl-sec">
		<div>
			<p class="tdl-eyebrow">Đại lý phân phối</p>
			<h2>Đặc quyền <span class="tdl-grad">cấp 1</span></h2>
			<ol class="tdl-quyen">
				<li><b aria-hidden="true">01</b><strong>Sản phẩm mới sớm nhất</strong><span>Xem, đặt và nhận mẫu mới trước các đại lý khác</span></li>
				<li><b aria-hidden="true">02</b><strong>Độc quyền mẫu mã riêng</strong><span>Khi đạt số lượng theo thoả thuận</span></li>
				<li><b aria-hidden="true">03</b><strong>Lợi nhuận 5-20%</strong><span>Mức lợi nhuận phân phối của đại lý cấp 1</span></li>
			</ol>
		</div>
		<figure class="tdl-photo"><img src="<?php echo esc_url( $skb_anh . 'civic-lua-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'civic-lua-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'civic-lua-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="1600" alt="LED nội thất Honda Civic mẫu dải lụa trên cánh cửa" style="object-position:55% 35%" loading="lazy" decoding="async"></figure>
	</section>

	<section class="tdl-sec dao">
		<div>
			<p class="tdl-eyebrow">Tuyển đại lý toàn quốc</p>
			<h2>Làm đại lý SK, lợi nhuận <span class="tdl-grad">20-50%</span></h2>
			<ul class="tdl-gach">
				<li>Giá được giữ, không lo phá giá</li>
				<li>Mẫu mới và xe mới liên tục</li>
				<li>Thương hiệu uy tín, bảo hành 24 tháng</li>
				<li>Đại lý cấp 1 có mẫu mã độc quyền</li>
			</ul>
			<a class="tdl-so" href="tel:<?php echo esc_attr( skb_tel( $skb_hotline ) ); ?>"><?php echo esc_html( $skb_hotline ); ?></a>
			<div class="tdl-cta">
				<a class="btn btn-light btn-lg" href="https://zalo.me/<?php echo esc_attr( skb_tel( $skb_zalo ) ); ?>" target="_blank" rel="noopener">Zalo</a>
				<a class="btn btn-dark btn-lg" href="tel:<?php echo esc_attr( skb_tel( $skb_hotline ) ); ?>">Điện thoại</a>
			</div>
			<p class="tdl-chan">sklight.vn/tuyen-dai-ly</p>
		</div>
		<figure class="tdl-photo"><img src="<?php echo esc_url( $skb_anh . 'vf6-21-1200.webp' ); ?>" srcset="<?php echo esc_url( $skb_anh . 'vf6-21-640.webp' ); ?> 640w, <?php echo esc_url( $skb_anh . 'vf6-21-1200.webp' ); ?> 1200w" sizes="(max-width:900px) 100vw, 640px" width="1200" height="1600" alt="LED nội thất VinFast VF6, dải ốp hoạ tiết chạy ngang táp lô" style="object-position:50% 45%" loading="lazy" decoding="async"></figure>
	</section>

</div>
<?php
get_footer();
