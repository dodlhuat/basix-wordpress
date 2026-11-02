<?php
/**
 * Template Name: Full width
 * Template Post Type: page, post
 *
 * The page title and content use the full container width: no narrow reading column and no sidebar.
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
		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="entry-media"><?php the_post_thumbnail( 'large' ); ?></figure>
		<?php endif; ?>
		<div class="entry-content"><?php the_content(); ?></div>
		<?php wp_link_pages(); ?>
	</article>
		<?php
endwhile;

endif;

get_footer();
