<?php
/**
 * Bài viết
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="page-wrap">
		<article class="page-card">
			<p class="tile-text"><?php echo esc_html( get_the_date() ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php if ( has_post_thumbnail() ) : ?><div class="entry" style="margin-bottom:20px"><?php the_post_thumbnail( 'large' ); ?></div><?php endif; ?>
			<div class="entry"><?php the_content(); ?></div>
		</article>
	</div>
	<?php
endwhile;
get_footer();
