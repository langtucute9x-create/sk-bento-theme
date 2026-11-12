<?php
/**
 * Tất cả mẫu xe / theo hãng
 */
get_header();
$term = is_tax( 'hang_xe' ) ? get_queried_object() : null;
?>
<div class="page-wrap">
	<section class="page-card">
		<h1><?php echo $term ? esc_html( sprintf( skb__( 'Đèn nội thất cho %s' ), skb__( $term->name ) ) ) : esc_html( skb__( 'Chọn dòng xe' ) ); ?></h1>
		<?php skb_tim_xe_box( 'page' ); ?>
		<?php if ( ! $term ) : ?>
			<?php get_template_part( 'template-parts/brand-grid' ); ?>
		<?php else : ?>
			<?php get_template_part( 'template-parts/brand-chips' ); ?>
			<?php if ( have_posts() ) : ?>
				<div class="posts">
					<?php while ( have_posts() ) : the_post(); ?>
						<a class="post-card" href="<?php the_permalink(); ?>">
							<div class="thumb"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); } ?></div>
							<div class="body"><h2><?php echo esc_html( skb_ngu_ngoai() ? skb_ngu_tieu_de_xe() : get_the_title() ); ?></h2><?php $g = skb_ngu_ngoai() ? skb_ngu_tien( (float) get_post_meta( get_the_ID(), '_skb_gia', true ) ) : skb_gia(); if ( $g ) : ?><p class="mx-card-price"><?php skb_e( 'Từ' ); ?> <strong><?php echo esc_html( $g ); ?></strong></p><?php endif; ?></div>
						</a>
					<?php endwhile; ?>
				</div>
				<div class="pagination"><?php echo paginate_links(); // phpcs:ignore ?></div>
			<?php else : ?>
				<?php if ( skb_ngu_ngoai() ) : ?><p class="tile-text"><?php skb_e( 'Mẫu cho hãng này đang được cập nhật.' ); ?> <a href="<?php echo esc_url( skb_wa_url() ); ?>" target="_blank" rel="noopener"><?php skb_e( 'Nhắn WhatsApp' ); ?></a></p><?php else : ?><p class="tile-text">Mẫu cho hãng này đang được cập nhật. Gọi <a href="tel:<?php echo esc_attr( skb_tel( skb_opt( 'hotline', '0868 055 555' ) ) ); ?>"><?php echo esc_html( skb_opt( 'hotline', '0868 055 555' ) ); ?></a> để được tư vấn.</p><?php endif; ?>
			<?php endif; ?>
		<?php endif; ?>
	</section>
</div>
<?php
get_footer();
