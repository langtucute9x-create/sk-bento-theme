<?php
/**
 * Danh sách bài viết / lưu trữ chung
 */
get_header();
?>
<div class="page-wrap">
	<div class="page-card">
		<h1><?php
		if ( is_home() ) {
			echo esc_html( get_the_title( get_option( 'page_for_posts' ) ) ?: 'Tin tức' );
		} elseif ( is_search() ) {
			printf( 'Kết quả cho “%s”', esc_html( get_search_query() ) );
		} else {
			the_archive_title();
		}
		?></h1>
		<?php if ( have_posts() ) : ?>
			<div class="posts">
				<?php while ( have_posts() ) : the_post(); ?>
					<a class="post-card" href="<?php the_permalink(); ?>">
						<div class="thumb"><?php if ( has_post_thumbnail() ) { the_post_thumbnail( 'medium_large', array( 'loading' => 'lazy' ) ); } ?></div>
						<div class="body">
							<h2><?php the_title(); ?></h2>
							<p><?php echo esc_html( get_the_date() ); ?></p>
						</div>
					</a>
				<?php endwhile; ?>
			</div>
			<div class="pagination"><?php echo paginate_links(); // phpcs:ignore ?></div>
		<?php else : ?>
			<p class="tile-text">Chưa có nội dung.</p>
		<?php endif; ?>
	</div>
</div>
<?php
get_footer();
