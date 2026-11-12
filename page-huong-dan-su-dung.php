<?php
/**
 * Trang Hướng dẫn sử dụng: nội dung trang + danh sách bài thuộc chuyên mục "Hướng dẫn sử dụng"
 */
get_header();
the_post();
$skb_hd = new WP_Query(
	array(
		'post_type'      => 'post',
		'posts_per_page' => 50,
		'category_name'  => 'huong-dan-su-dung',
		'no_found_rows'  => true,
	)
);
?>
<div class="page-wrap">
	<div class="page-card">
		<h1><?php the_title(); ?></h1>
		<?php if ( trim( get_the_content() ) ) : ?><div class="entry"><?php the_content(); ?></div><?php endif; ?>
		<?php if ( $skb_hd->have_posts() ) : ?>
			<div class="posts">
				<?php while ( $skb_hd->have_posts() ) : $skb_hd->the_post(); ?>
					<a class="post-card" href="<?php the_permalink(); ?>">
						<div class="thumb"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); } ?></div>
						<div class="body">
							<h2><?php the_title(); ?></h2>
							<p><?php echo esc_html( get_the_date() ); ?></p>
						</div>
					</a>
				<?php endwhile; wp_reset_postdata(); ?>
			</div>
		<?php else : ?>
			<p class="tile-text">Hướng dẫn đang được cập nhật. Gọi hotline để được hỗ trợ trực tiếp.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
