<?php
$basix_static_front = get_option( 'show_on_front' ) === 'page';
get_header();
?>

<?php $basix_hero = get_theme_mod( 'basix_hero_style', 'card' ); ?>
<?php if ( ! is_paged() && 'none' !== $basix_hero ) : ?>
<section class="<?php echo 'minimal' === $basix_hero ? 'hero-minimal' : 'hero card card-flat'; ?><?php echo has_header_image() ? ' has-image' : ''; ?>"<?php echo apply_filters( 'basix_hero_attributes', '' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts. ?>>
	<p class="text-overline"><?php esc_html_e( 'Welcome', 'basix' ); ?></p>
	<h1 class="hero-title"><?php bloginfo( 'name' ); ?></h1>
	<?php if ( get_bloginfo( 'description' ) ) : ?>
		<p class="hero-lead"><?php bloginfo( 'description' ); ?></p>
	<?php endif; ?>
	<p class="hero-actions">
		<a class="button button-primary" href="#latest"><?php esc_html_e( 'Latest posts', 'basix' ); ?></a>
		<?php if ( $basix_static_front && get_option( 'page_for_posts' ) ) : ?>
			<a class="button" href="<?php echo esc_url( get_permalink( get_option( 'page_for_posts' ) ) ); ?>"><?php esc_html_e( 'All posts', 'basix' ); ?></a>
		<?php endif; ?>
	</p>
</section>
<?php endif; ?>

<?php if ( $basix_static_front ) : ?>
	<?php
	while ( have_posts() ) :
		the_post();
		?>
		<?php if ( get_the_content() ) : ?>
			<div class="entry-content front-content"><?php the_content(); ?></div>
		<?php endif; ?>
	<?php endwhile; ?>
<?php endif; ?>

<?php get_template_part( 'template-parts/featured' ); ?>

<section id="latest" class="front-latest">
	<h2><?php esc_html_e( 'Latest posts', 'basix' ); ?></h2>
	<?php if ( $basix_static_front ) : ?>
		<?php
		$basix_latest = new WP_Query(
			[
				'posts_per_page'      => 6,
				'ignore_sticky_posts' => true,
			]
		);
		if ( $basix_latest->have_posts() ) :
			?>
			<div class="grid post-grid">
				<?php
				while ( $basix_latest->have_posts() ) :
					$basix_latest->the_post();
					get_template_part( 'template-parts/content', 'card' );
endwhile;
				?>
			</div>
		<?php else : ?>
			<div class="alert alert-info"><?php esc_html_e( 'Nothing published yet.', 'basix' ); ?></div>
			<?php
		endif;
		wp_reset_postdata();
		?>
	<?php else : ?>
		<?php get_template_part( 'template-parts/loop' ); ?>
	<?php endif; ?>
</section>

<?php get_footer(); ?>
