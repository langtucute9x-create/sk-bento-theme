<?php
/**
 * Thẻ đại lý (dùng trong vòng lặp). $args['img'] = true: hiện ảnh đại lý (trang Đại lý).
 */
$phone = get_post_meta( get_the_ID(), '_skb_dien_thoai', true );
$hours = get_post_meta( get_the_ID(), '_skb_gio_mo', true );
$addr  = get_post_meta( get_the_ID(), '_skb_dia_chi', true );
$tinh  = get_post_meta( get_the_ID(), '_skb_tinh', true );
$img   = ! empty( $args['img'] ) && has_post_thumbnail();
$ll    = function_exists( 'skb_dl_toa_do' ) ? skb_dl_toa_do( get_the_ID() ) : '';
$xap   = 'tinh' === get_post_meta( get_the_ID(), '_skb_toa_do_nguon', true );
?>
<div class="dealer<?php echo $img ? ' has-img' : ''; ?>"<?php if ( $ll ) : ?> data-ll="<?php echo esc_attr( $ll ); ?>"<?php echo $xap ? ' data-xap="1"' : ''; ?><?php endif; ?> data-tinh="<?php echo esc_attr( $tinh ); ?>" data-q="<?php echo esc_attr( mb_strtolower( get_the_title() . ' ' . $addr . ' ' . $tinh . ' ' . $phone ) ); ?>">
	<?php if ( $img ) : ?><div class="dealer-img"><?php the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy', 'alt' => get_the_title() ) ); ?></div><?php elseif ( ! empty( $args['img'] ) ) : ?><div class="dealer-img ph" aria-hidden="true"><img src="<?php echo esc_url( get_template_directory_uri() . '/assets/img/logo-full-png-1.webp' ); ?>" alt="" loading="lazy"></div><?php endif; ?>
	<?php if ( ! empty( $args['img'] ) && $tinh ) : ?><p class="tinh"><?php echo esc_html( $tinh ); ?></p><?php endif; ?>
	<p class="name"><?php the_title(); ?></p>
	<p class="dl-km" hidden></p>
	<p class="addr"><?php echo esc_html( $addr ); ?></p>
	<p class="meta">
		<?php if ( $phone ) : ?>
			<a href="tel:<?php echo esc_attr( skb_tel( $phone ) ); ?>"><?php echo esc_html( $phone ); ?></a>
		<?php else : ?>
			Chưa cập nhật số điện thoại
		<?php endif; ?>
		<?php if ( $hours ) : ?> · <?php echo esc_html( $hours ); ?><?php endif; ?>
	</p>
	<a class="dir" href="<?php echo esc_url( skb_map_url( get_the_ID() ) ); ?>" target="_blank" rel="noopener"><span>Chỉ đường</span></a>
</div>
