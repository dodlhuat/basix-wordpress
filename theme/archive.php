<?php get_header(); ?>
<?php if ( ! basix_builder_location( 'archive' ) ) : // A theme builder can draw this part of the page instead. ?>

	<?php basix_layout_open(); ?>

<header class="archive-header">
	<p class="text-overline"><?php echo esc_html( basix_archive_label() ); ?></p>
	<?php the_archive_title( '<h1>', '</h1>' ); ?>
	<?php the_archive_description( '<div class="text-secondary">', '</div>' ); ?>
</header>

	<?php get_template_part( 'template-parts/loop' ); ?>

	<?php basix_layout_close(); ?>

<?php endif; ?>

<?php get_footer(); ?>
