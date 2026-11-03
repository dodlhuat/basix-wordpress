<?php get_header(); ?>
<?php if ( ! basix_builder_location( 'archive' ) ) : // A theme builder can draw this part of the page instead. ?>

	<?php basix_layout_open(); ?>

	<?php if ( is_home() && ! is_front_page() ) : ?>
	<h1><?php single_post_title(); ?></h1>
<?php endif; ?>

	<?php get_template_part( 'template-parts/loop' ); ?>

	<?php basix_layout_close(); ?>

<?php endif; ?>

<?php get_footer(); ?>
