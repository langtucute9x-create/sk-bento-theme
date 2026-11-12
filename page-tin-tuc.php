<?php
/**
 * Trang Tin tức: bài viết mới nhất, không gồm bài hướng dẫn sử dụng
 */
get_header();
the_post();
$skb_hd_cat = get_term_by( 'slug', 'huong-dan-su-dung', 'category' );
$skb_tt     = new WP_Query(
	array(
		'post_type'        => 'post',
		'posts_per_page'   => 12,
		'paged'            => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ),
		'category__not_in' => $skb_hd_cat ? array( (int) $skb_hd_cat->term_id ) : array(),
	)
);
?>
<div class="page-wrap">
	<div class="page-card">
		<h1><?php the_title(); ?></h1>
		<?php if ( trim( get_the_content() ) ) : ?><div class="entry"><?php the_content(); ?></div><?php endif; ?>
		<?php if ( $skb_tt->have_posts() ) : ?>
			<div class="posts">
				<?php while ( $skb_tt->have_posts() ) : $skb_tt->the_post(); ?>
					<a class="post-card" href="<?php the_permalink(); ?>">
						<div class="thumb"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); } ?></div>
						<div class="body">
							<h2><?php the_title(); ?></h2>
							<p><?php echo esc_html( get_the_date() ); ?></p>
						</div>
					</a>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
			<div class="pagination"><?php echo paginate_links( array( 'total' => $skb_tt->max_num_pages, 'current' => max( 1, (int) get_query_var( 'paged' ), (int) get_query_var( 'page' ) ) ) ); // phpcs:ignore ?></div>
		<?php else : ?>
			<p class="tile-text">Chưa có tin tức.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
