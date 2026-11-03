<?php get_header(); ?>
<?php if ( ! basix_builder_location( 'single' ) ) : // A theme builder can draw this part of the page instead. ?>

	<?php basix_layout_open(); ?>

	<?php
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
		if ( comments_open() ) {
			comments_template();}
		?>
<?php endwhile; ?>

	<?php basix_layout_close(); ?>

<?php endif; ?>

<?php get_footer(); ?>
