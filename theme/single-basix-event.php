<?php get_header(); ?>

<?php basix_layout_open(); ?>

<?php while ( have_posts() ) : the_post(); ?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry-header">
			<p class="text-overline"><?php esc_html_e( 'Event', 'basix' ); ?></p>
			<h1><?php the_title(); ?></h1>
			<?php basix_post_meta(); ?>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="entry-media"><?php the_post_thumbnail( 'large' ); ?></figure>
		<?php endif; ?>

		<div class="entry-content"><?php the_content(); ?></div>
	</article>

	<nav class="post-nav" aria-label="<?php esc_attr_e( 'Back to the events', 'basix' ); ?>">
		<div><a href="<?php echo esc_url( get_post_type_archive_link( BASIX_EVENT ) ); ?>">← <?php esc_html_e( 'All events', 'basix' ); ?></a></div>
	</nav>
<?php endwhile; ?>

<?php basix_layout_close(); ?>

<?php get_footer(); ?>
