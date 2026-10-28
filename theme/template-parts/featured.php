<?php
/**
 * Featured posts: the sticky posts as a Basix carousel on the front page.
 *
 * @package basix
 */

$basix_featured_ids = basix_featured_ids();
if ( ! $basix_featured_ids || is_paged() ) {
	return;
}
$basix_featured = new WP_Query(
	array(
		'post__in'            => $basix_featured_ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => count( $basix_featured_ids ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
if ( ! $basix_featured->have_posts() ) {
	return;
}
?>
<section class="featured" aria-label="<?php esc_attr_e( 'Featured posts', 'basix' ); ?>">
	<div class="carousel" <?php echo basix_component_attrs( 'featured', array( 'loop' => true ) ); ?>>
		<?php
		while ( $basix_featured->have_posts() ) :
			$basix_featured->the_post();
			?>
			<article class="featured-slide">
				<?php if ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large', array( 'class' => 'featured-image' ) ); ?>
				<?php endif; ?>
				<div class="featured-body">
					<?php basix_post_terms(); ?>
					<h2 class="featured-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
					<?php basix_post_meta(); ?>
				</div>
			</article>
		<?php endwhile; ?>
		<?php wp_reset_postdata(); ?>
	</div>
</section>
