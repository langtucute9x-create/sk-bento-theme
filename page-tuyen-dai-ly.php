<?php
/**
 * Trang Tuyển đại lý: chỉ nút Gọi và Zalo, không form.
 * Chữ lấy đúng theo brief chủ duyệt (12/11/2026): đại lý lắp đặt 20-50%, đại lý cấp 1 5-20%, không ghi giá, không chia hạng.
 * Ảnh: assets/img/tuyen-dai-ly (ảnh thật đã lắp, chữ ở nền đã che mờ). Chính sách chi tiết: chủ gõ vào nội dung trang.
 */
get_header();
the_post();
$skb_hotline = skb_opt( 'hotline', '0868 055 555' );
$skb_zalo    = skb_opt( 'zalo', '0868055555' );
$skb_tel     = 'tel:' . skb_tel( $skb_hotline );
$skb_zl      = $skb_zalo ? 'https://zalo.me/' . skb_tel( $skb_zalo ) : '';
$skb_so_xe   = (int) wp_count_posts( 'mau_xe' )->publish;

/** Thẻ ảnh có srcset */
$skb_anh = function ( $ten, $rong, $cao, $alt, $sizes, $lazy = true ) {
	$w = array_map( 'intval', (array) $rong );
	$set = array();
	foreach ( $w as $x ) {
		$set[] = esc_url( skb_tdl_anh( $ten . '-' . $x . '.webp' ) ) . ' ' . $x . 'w';
	}
	$max = max( $w );
	printf(
		'<img src="%s" srcset="%s" sizes="%s" width="%d" height="%d" alt="%s"%s>',
		esc_url( skb_tdl_anh( $ten . '-' . $max . '.webp' ) ),
		esc_attr( implode( ', ', $set ) ),
		esc_attr( $sizes ),
		(int) $max,
		(int) round( $max * $cao ),
		esc_attr( $alt ),
		$lazy ? ' loading="lazy" decoding="async"' : ' fetchpriority="high"'
	);
};
$skb_nut = function ( $nho = false ) use ( $skb_tel, $skb_zl, $skb_hotline ) {
	?>
	<div class="td-nut<?php echo $nho ? ' td-nut-giua' : ''; ?>">
		<a class="btn btn-light btn-lg" href="<?php echo esc_attr( $skb_tel ); ?>"><svg aria-hidden="true" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2"/></svg>Gọi <?php echo esc_html( $skb_hotline ); ?></a>
		<?php if ( $skb_zl ) : ?><a class="btn btn-lg td-zalo" href="<?php echo esc_url( $skb_zl ); ?>" target="_blank" rel="noopener">Nhắn Zalo</a><?php endif; ?>
	</div>
	<?php
};
?>
<div class="page-wrap td">

	<section class="td-mo" aria-labelledby="td-h1">
		<div class="td-mo-chu">
			<p class="td-eb">Bộ LED ốp hoạ tiết theo từng dòng xe</p>
			<h1 id="td-h1">Tuyển đại lý toàn quốc</h1>
			<p class="td-mo-loi">Lợi nhuận <b class="td-grad">20-50%</b> mỗi bộ</p>
			<?php $skb_nut(); ?>
			<p class="td-phu">Zalo / Điện thoại <?php echo esc_html( $skb_hotline ); ?></p>
		</div>
		<figure class="td-mo-anh">
			<?php $skb_anh( 'mo-dau', array( 720, 1200 ), 1040 / 1200, 'Ốp LED hoạ tiết phát sáng trên táp lô và cửa xe VinFast VF6', '(max-width:900px) 100vw, 640px', false ); ?>
		</figure>
	</section>

	<ul class="td-o4" aria-label="Lợi ích khi làm đại lý SK">
		<li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L3 13V3h10l7.6 7.6a2 2 0 0 1 0 2.8z"/><circle cx="7.5" cy="7.5" r="1.5"/></svg>Giá được giữ</li>
		<li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="m8.5 12 2.5 2.5 4.5-5"/></svg>Bảo hành 24 tháng</li>
		<li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3l1.9 5.1L19 10l-5.1 1.9L12 17l-1.9-5.1L5 10l5.1-1.9z"/><path d="M19 16l.8 2.2L22 19l-2.2.8L19 22l-.8-2.2L16 19l2.2-.8z"/></svg>Mẫu mới liên tục</li>
		<li><svg aria-hidden="true" viewBox="0 0 24 24"><path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.6 2.6-2.4-.6-.6-2.4z"/></svg>Hỗ trợ kỹ thuật</li>
	</ul>

	<section class="td-khoi td-ln" aria-labelledby="td-ln-h">
		<div class="td-ln-chu">
			<h2 id="td-ln-h" class="td-eb">Lợi nhuận của đại lý lắp đặt</h2>
			<p class="td-so td-grad">20-50%</p>
			<p class="td-tag">Lãi cao. Giá được giữ.</p>
			<ul class="td-ds">
				<li>Một mặt bằng giá, không lo phá giá</li>
				<li>Thương hiệu uy tín, bảo hành 24 tháng</li>
			</ul>
		</div>
		<figure class="td-ln-anh">
			<?php $skb_anh( 'loi-nhuan', array( 800, 1400 ), 714 / 1400, 'Dải LED ốp hoạ tiết trên táp lô VinFast VF6', '(max-width:900px) 100vw, 760px' ); ?>
		</figure>
	</section>

	<section class="td-khoi td-mm" aria-labelledby="td-mm-h">
		<div class="td-mm-chu">
			<h2 id="td-mm-h" class="td-eb">Luôn có hàng mới để bán</h2>
			<?php if ( $skb_so_xe > 0 ) : ?>
				<p class="td-so td-grad"><?php echo esc_html( $skb_so_xe ); ?></p>
				<p class="td-tag">mẫu xe có bộ LED riêng</p>
			<?php endif; ?>
			<p class="td-mo-ta">Ốp hoạ tiết phát sáng làm riêng theo từng dòng xe</p>
			<a class="td-link" href="<?php echo esc_url( get_post_type_archive_link( 'mau_xe' ) ); ?>">Xem các mẫu xe <span aria-hidden="true">→</span></a>
		</div>
		<ul class="td-luoi">
			<?php
			foreach ( array( 'VinFast VF6', 'VinFast VF6', 'VinFast VF7', 'VinFast VF7', 'Honda Civic', 'Ford Everest' ) as $skb_i => $skb_xe ) :
				?>
				<li><figure><?php $skb_anh( 'mau-' . ( $skb_i + 1 ), array( 480 ), 4 / 3, 'Ốp LED nội thất ' . $skb_xe, '(max-width:900px) 46vw, 220px' ); ?><figcaption><?php echo esc_html( $skb_xe ); ?></figcaption></figure></li>
			<?php endforeach; ?>
		</ul>
	</section>

	<section class="td-khoi td-c1" aria-labelledby="td-c1-h">
		<figure class="td-c1-anh">
			<?php $skb_anh( 'cap-1', array( 720, 1200 ), 1024 / 1200, 'Ốp cửa hoạ tiết dải lụa phát sáng trên Honda Civic', '(max-width:900px) 100vw, 600px' ); ?>
		</figure>
		<div class="td-c1-chu">
			<p class="td-eb">Đại lý phân phối</p>
			<h2 id="td-c1-h" class="td-h2">Đặc quyền <span class="td-grad">cấp 1</span></h2>
			<ol class="td-dq">
				<li><span class="td-n td-grad" aria-hidden="true">01</span><div><h3>Sản phẩm mới sớm nhất</h3><p>Xem, đặt và nhận mẫu mới trước các đại lý khác</p></div></li>
				<li><span class="td-n td-grad" aria-hidden="true">02</span><div><h3>Độc quyền mẫu mã riêng</h3><p>Khi đạt số lượng theo thoả thuận</p></div></li>
				<li><span class="td-n td-grad" aria-hidden="true">03</span><div><h3>Lợi nhuận 5-20%</h3><p>Mức lợi nhuận phân phối của đại lý cấp 1</p></div></li>
			</ol>
		</div>
	</section>

	<?php if ( trim( get_the_content() ) ) : ?>
	<section class="td-khoi td-cs" aria-labelledby="td-cs-h">
		<h2 id="td-cs-h" class="td-h2">Chính sách đại lý</h2>
		<div class="entry"><?php the_content(); ?></div>
	</section>
	<?php endif; ?>

	<section class="td-khoi td-cuoi" aria-labelledby="td-cuoi-h">
		<p class="td-eb">Tuyển đại lý toàn quốc</p>
		<h2 id="td-cuoi-h" class="td-h2">Làm đại lý SK, lợi nhuận <span class="td-grad">20-50%</span></h2>
		<ul class="td-ds">
			<li>Giá được giữ, không lo phá giá</li>
			<li>Mẫu mới và xe mới liên tục</li>
			<li>Thương hiệu uy tín, bảo hành 24 tháng</li>
			<li>Đại lý cấp 1 có mẫu mã độc quyền</li>
		</ul>
		<?php $skb_nut( true ); ?>
		<p class="td-phu">Zalo / Điện thoại <?php echo esc_html( $skb_hotline ); ?> · <a href="<?php echo esc_url( get_post_type_archive_link( 'dai_ly' ) ); ?>">Xem hệ thống đại lý hiện có</a></p>
	</section>

</div>
<?php
get_footer();
