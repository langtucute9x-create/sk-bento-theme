<?php
/**
 * Trang Liên hệ: thông tin, nút gọi/Zalo/chỉ đường, form tư vấn, bản đồ
 */
get_header();
the_post();
$skb_hotline = skb_opt( 'hotline', '0868 055 555' );
$skb_zalo    = skb_opt( 'zalo', '0868055555' );
$skb_addr    = skb_opt( 'address', '197 Thanh Am, Việt Hưng, Long Biên, Hà Nội' );
$skb_email   = skb_opt( 'email', 'sklight@gmail.com' );
$skb_q       = rawurlencode( $skb_addr );
?>
<div class="page-wrap">
	<article class="page-card">
		<h1><?php the_title(); ?></h1>
		<?php if ( trim( get_the_content() ) ) : ?><div class="entry lh-intro"><?php the_content(); ?></div><?php else : ?><p class="tile-text lh-intro">Liên hệ SK để được tư vấn mẫu đèn phù hợp với xe của bạn và đặt lịch lắp đặt tại đại lý gần nhất.</p><?php endif; ?>
		<div class="lh-grid">
			<div class="lh-info">
				<dl class="lh-list">
					<dt>Địa chỉ</dt><dd><?php echo esc_html( $skb_addr ); ?></dd>
					<dt>Hotline</dt><dd><a href="tel:<?php echo esc_attr( skb_tel( $skb_hotline ) ); ?>"><?php echo esc_html( $skb_hotline ); ?></a></dd>
					<?php if ( $skb_zalo ) : ?><dt>Zalo</dt><dd><a href="https://zalo.me/<?php echo esc_attr( skb_tel( $skb_zalo ) ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $skb_zalo ); ?></a></dd><?php endif; ?>
					<?php if ( $skb_email ) : ?><dt>Email</dt><dd><a href="mailto:<?php echo esc_attr( $skb_email ); ?>"><?php echo esc_html( $skb_email ); ?></a></dd><?php endif; ?>
				</dl>
				<div class="mx-cta">
					<a class="btn btn-light btn-lg" href="tel:<?php echo esc_attr( skb_tel( $skb_hotline ) ); ?>">Gọi <?php echo esc_html( $skb_hotline ); ?></a>
					<?php if ( $skb_zalo ) : ?><a class="btn btn-dark btn-lg" href="https://zalo.me/<?php echo esc_attr( skb_tel( $skb_zalo ) ); ?>" target="_blank" rel="noopener">Nhắn Zalo</a><?php endif; ?>
					<a class="btn btn-dark btn-lg" href="https://www.google.com/maps/dir/?api=1&amp;destination=<?php echo esc_attr( $skb_q ); ?>" target="_blank" rel="noopener">Chỉ đường</a>
				</div>
				<p class="tile-text">Cần lắp ở tỉnh khác? Xem <a href="<?php echo esc_url( get_post_type_archive_link( 'dai_ly' ) ); ?>">hệ thống đại lý</a>.</p>
			</div>
			<div class="lh-form">
				<h2>Gửi yêu cầu tư vấn</h2>
				<?php echo do_shortcode( '[sk_dat_lich]' ); // phpcs:ignore ?>
			</div>
		</div>
		<div class="lh-map">
			<iframe title="Bản đồ đường đến SK Ambient Light" src="https://maps.google.com/maps?q=<?php echo esc_attr( $skb_q ); ?>&amp;z=16&amp;output=embed" loading="lazy" referrerpolicy="no-referrer-when-downgrade" allowfullscreen></iframe>
		</div>
	</article>
</div>
<?php
get_footer();
