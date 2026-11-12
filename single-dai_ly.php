<?php
/**
 * Một đại lý
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="page-wrap">
		<section class="page-card">
			<h1><?php the_title(); ?></h1>
			<div class="dealers" style="display:block;max-width:420px"><?php get_template_part( 'template-parts/dealer-card' ); ?></div>
		</section>
	</div>
	<?php
endwhile;
get_footer();
