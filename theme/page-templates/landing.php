<?php
/**
 * Template Name: Landing page
 * Template Post Type: page
 *
 * No title, breadcrumb or page margins: the blocks run edge to edge, so Full width blocks and
 * patterns such as the hero can use the whole window. The title stays in the page for screen readers.
 *
 * @package basix
 */

get_header();

while ( have_posts() ) :
	the_post();
	?>
	<article <?php post_class( 'entry' ); ?>>
		<h1 class="screen-reader-text"><?php the_title(); ?></h1>
		<div class="entry-content"><?php the_content(); ?></div>
	</article>
	<?php
endwhile;

get_footer();
