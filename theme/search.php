<?php get_header(); ?>
<?php if ( ! basix_builder_location( 'archive' ) ) : // A theme builder can draw this part of the page instead. ?>

	<?php basix_layout_open(); ?>

<header class="archive-header">
	<h1>
		<?php
		printf(
			/* translators: %s: search query. */
			esc_html__( 'Search results for “%s”', 'basix' ),
			esc_html( get_search_query() )
		);
		?>
	</h1>
	<?php get_search_form( [ 'aria_label' => __( 'Refine your search', 'basix' ) ] ); ?>
</header>

	<?php get_template_part( 'template-parts/loop' ); ?>

	<?php basix_layout_close(); ?>

<?php endif; ?>

<?php get_footer(); ?>
