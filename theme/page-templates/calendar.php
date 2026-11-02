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

if ( ! basix_builder_location( 'single' ) ) : // A theme builder can draw this part of the page instead.

	while ( have_posts() ) :
		the_post();
		?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry-header<?php echo esc_attr( basix_title_class() ); ?>"><h1><?php the_title(); ?></h1></header>
		<div class="entry-content"><?php the_content(); ?></div>

		<?php get_template_part( 'template-parts/calendar' ); ?>
	</article>
		<?php
endwhile;

endif;

get_footer();
