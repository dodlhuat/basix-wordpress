<?php get_header(); ?>
<?php if ( ! basix_builder_location( 'single' ) ) : // A theme builder can draw this part of the page instead. ?>

	<?php basix_layout_open(); ?>

	<?php
	while ( have_posts() ) :
		the_post();
		?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry-header<?php echo esc_attr( basix_title_class() ); ?>">
			<?php basix_post_terms(); ?>
			<p class="text-overline"><?php esc_html_e( 'Event', 'basix' ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php basix_post_meta(); ?>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="entry-media"><?php the_post_thumbnail( 'large' ); ?></figure>
		<?php endif; ?>

		<div class="entry-content"><?php the_content(); ?></div>

		<?php basix_event_repeat_info(); ?>
	</article>

		<?php if ( ! post_password_required() ) : ?>
		<p><a class="button" href="<?php echo esc_url( basix_event_ics_url( get_the_ID() ) ); ?>" rel="nofollow"><?php esc_html_e( 'Add to calendar', 'basix' ); ?></a></p>
	<?php endif; ?>

	<nav class="post-nav" aria-label="<?php esc_attr_e( 'Back to the events', 'basix' ); ?>">
		<div><a href="<?php echo esc_url( get_post_type_archive_link( BASIX_EVENT ) ); ?>">← <?php esc_html_e( 'All events', 'basix' ); ?></a></div>
	</nav>
<?php endwhile; ?>

	<?php basix_layout_close(); ?>

<?php endif; ?>

<?php get_footer(); ?>
