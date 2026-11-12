<?php
/**
 * Trang tĩnh
 */
get_header();
while ( have_posts() ) :
	the_post();
	?>
	<div class="page-wrap">
		<article class="page-card">
			<h1><?php the_title(); ?></h1>
			<div class="entry"><?php the_content(); ?></div>
		</article>
	</div>
	<?php
endwhile;
get_footer();
