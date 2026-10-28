<?php
/**
 * Template Name: Calendar
 * Template Post Type: page
 *
 * A month, week and agenda calendar of the site's posts by publish date. The posts for the visible range
 * are loaded from the REST API; without JavaScript the posts of the current month are listed instead.
 *
 * @package basix
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry-header"><h1><?php the_title(); ?></h1></header>
		<div class="entry-content"><?php the_content(); ?></div>

		<?php get_template_part( 'template-parts/calendar' ); ?>
	</article>
	<?php
endwhile;

get_footer();
