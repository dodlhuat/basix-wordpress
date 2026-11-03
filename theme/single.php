<?php get_header(); ?>
<?php if ( ! basix_builder_location( 'single' ) ) : // A theme builder can draw this part of the page instead. ?>

	<?php basix_layout_open(); ?>

	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<?php if ( basix_feature( 'basix_show_progress' ) ) : ?>
		<div class="reading-progress" <?php echo basix_component_attrs( 'reading-progress' ); ?> aria-hidden="true">
			<div class="progress"><div class="progress-bar accent" style="width:0"></div></div>
		</div>
	<?php endif; ?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry-header<?php echo esc_attr( basix_title_class() ); ?>">
			<?php basix_post_terms(); ?>
			<h1><?php the_title(); ?></h1>
			<?php basix_post_meta(); ?>
		</header>

		<?php if ( has_post_thumbnail() ) : ?>
			<figure class="entry-media"><?php the_post_thumbnail( 'large' ); ?></figure>
		<?php endif; ?>

		<div class="entry-content"><?php the_content(); ?></div>

		<?php wp_link_pages(); ?>

		<?php if ( has_tag() ) : ?>
			<footer class="entry-footer chips">
				<?php foreach ( get_the_tags() as $basix_tag ) : ?>
					<a class="chip clickable" href="<?php echo esc_url( get_tag_link( $basix_tag ) ); ?>"><?php echo esc_html( $basix_tag->name ); ?></a>
				<?php endforeach; ?>
			</footer>
		<?php endif; ?>

		<?php get_template_part( 'template-parts/share' ); ?>
		<?php get_template_part( 'template-parts/author-box' ); ?>
	</article>

	<nav class="post-nav" aria-label="<?php esc_attr_e( 'Post navigation', 'basix' ); ?>">
		<div><?php previous_post_link( '%link', '← %title' ); ?></div>
		<div class="text-right"><?php next_post_link( '%link', '%title →' ); ?></div>
	</nav>

		<?php get_template_part( 'template-parts/related' ); ?>

		<?php
		if ( comments_open() || get_comments_number() ) {
			comments_template();}
		?>
<?php endwhile; ?>

	<?php basix_layout_close(); ?>

<?php endif; ?>

<?php get_footer(); ?>
