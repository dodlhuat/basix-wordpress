<?php
/**
 * Template Name: Canvas
 * Template Post Type: page, post
 *
 * A blank page for page builders (Elementor, Beaver Builder, Bricks, Divi and the like): no header, no footer,
 * no breadcrumb, no title and no margins. Only the content, so the builder has the whole window.
 *
 * @package basix
 */

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class( 'basix-canvas' ); ?>>
<?php wp_body_open(); ?>
<main id="content" class="canvas">
	<h1 class="screen-reader-text"><?php the_title(); ?></h1>
	<?php
	while ( have_posts() ) {
		the_post();
		the_content();
	}
	?>
</main>
<?php wp_footer(); ?>
</body>
</html>
