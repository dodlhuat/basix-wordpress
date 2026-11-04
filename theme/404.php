<?php get_header(); ?>
<?php if ( ! basix_builder_location( 'single' ) ) : // A theme builder can draw this part of the page instead. ?>

<section class="not-found text-center">
	<h1>404</h1>
	<p class="text-secondary"><?php esc_html_e( 'This page could not be found.', 'basix-paperlight' ); ?></p>
	<?php get_search_form( [ 'aria_label' => __( 'Search the site', 'basix-paperlight' ) ] ); ?>
	<p><a class="button button-primary" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Back to home', 'basix-paperlight' ); ?></a></p>
</section>

<?php endif; ?>

<?php get_footer(); ?>
