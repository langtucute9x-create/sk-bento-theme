<?php
/**
 * Trang Giới thiệu / Liên hệ ở bản nước ngoài (nội dung lấy từ từ điển inc/da-ngu-tu-dien.php).
 * Bản nước ngoài không có Đặt lịch, Đại lý: khách liên hệ qua WhatsApp.
 */
get_header();
$skb_lh   = is_page( 'lien-he' );
$skb_addr = skb_opt( 'address', '197 Thanh Am, Việt Hưng, Long Biên, Hà Nội' ) . ', ' . skb__( 'Việt Nam' );
$skb_mail = skb_opt( 'email', 'sklight@gmail.com' );
?>
<div class="page-wrap">
	<article class="page-card ngoai-trang">
		<?php if ( $skb_lh ) : ?>
			<h1><?php skb_e( 'Liên hệ SK Ambient Light' ); ?></h1>
			<p class="tile-text lh-intro"><?php skb_e( '@lh_intro' ); ?></p>
			<div class="lh-grid">
				<div class="lh-info">
					<dl class="lh-list">
						<dt>WhatsApp</dt><dd><a href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php echo esc_html( skb_tel_quoc_te() ); ?></a></dd>
						<dt><?php skb_e( 'Điện thoại' ); ?></dt><dd><a href="tel:+<?php echo esc_attr( skb_wa_so() ); ?>"><?php echo esc_html( skb_tel_quoc_te() ); ?></a></dd>
						<?php if ( $skb_mail ) : ?><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr( $skb_mail ); ?>"><?php echo esc_html( $skb_mail ); ?></a></dd><?php endif; ?>
						<dt><?php skb_e( 'Địa chỉ' ); ?></dt><dd><?php echo esc_html( $skb_addr ); ?></dd>
						<dt><?php skb_e( 'Giờ làm việc' ); ?></dt><dd><?php skb_e( '08:00–22:00 hằng ngày (giờ Việt Nam, GMT+7)' ); ?></dd>
					</dl>
					<div class="mx-cta">
						<a class="btn btn-light btn-lg btn-wa" href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php echo skb_wa_icon( 18 ); // phpcs:ignore ?> <?php skb_e( 'Nhắn WhatsApp' ); ?></a>
						<a class="btn btn-dark btn-lg" href="<?php echo esc_url( get_post_type_archive_link( 'mau_xe' ) ); ?>"><?php skb_e( 'Chọn dòng xe' ); ?></a>
					</div>
				</div>
				<div class="lh-form">
					<h2><?php skb_e( 'Khi nhắn tin, bạn gửi giúp SK' ); ?></h2>
					<ul class="mx-points">
						<li><?php skb_e( 'Hãng, dòng xe và năm sản xuất' ); ?></li>
						<li><?php skb_e( 'Gói đèn bạn quan tâm (nếu đã chọn)' ); ?></li>
						<li><?php skb_e( 'Quốc gia và thành phố nhận hàng' ); ?></li>
					</ul>
					<p class="tile-text"><?php skb_e( 'SK sẽ trả lời giá, thời gian và cách vận chuyển tới nước bạn.' ); ?></p>
				</div>
			</div>
		<?php else : ?>
			<h1><?php skb_e( 'Giới thiệu SK Ambient Light' ); ?></h1>
			<div class="entry">
				<p><?php skb_e( '@gt_1' ); ?></p>
				<p><?php skb_e( '@gt_2' ); ?></p>
				<p><?php skb_e( '@gt_3' ); ?></p>
				<h2><?php skb_e( 'Vì sao chọn SK' ); ?></h2>
				<ul>
					<li><?php skb_e( 'Ốp thay zin theo đúng đời xe, không cắt dây zin' ); ?></li>
					<li><?php skb_e( 'Điều khiển màu, hiệu ứng qua app và nút bấm' ); ?></li>
					<li><?php skb_e( 'Bảo hành 24 tháng kể từ ngày kích hoạt' ); ?></li>
					<li><?php echo esc_html( sprintf( skb__( '%d mẫu xe được thiết kế riêng cho các hãng phổ biến' ), (int) wp_count_posts( 'mau_xe' )->publish ) ); ?></li>
				</ul>
			</div>
			<div class="mx-cta">
				<a class="btn btn-light btn-lg" href="<?php echo esc_url( get_post_type_archive_link( 'mau_xe' ) ); ?>"><?php skb_e( 'Chọn dòng xe' ); ?></a>
				<a class="btn btn-dark btn-lg btn-wa" href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php echo skb_wa_icon( 18 ); // phpcs:ignore ?> <?php skb_e( 'Nhắn WhatsApp' ); ?></a>
			</div>
		<?php endif; ?>
	</article>
</div>
<?php
get_footer();
