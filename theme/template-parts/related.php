<?php
/**
 * Related posts: those with shared tags first, then the same categories.
 *
 * @package basix
 */

$basix_related_ids = basix_feature( 'basix_show_related' ) ? basix_related_posts( 3 ) : array();
if ( ! $basix_related_ids ) {
	return;
}
$basix_related = new WP_Query(
	array(
		'post__in'            => $basix_related_ids,
		'orderby'             => 'post__in',
		'posts_per_page'      => count( $basix_related_ids ),
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
	)
);
?>
<section class="related" aria-labelledby="related-title">
	<h2 id="related-title"><?php esc_html_e( 'Related posts', 'basix' ); ?></h2>
	<div class="grid post-grid">
		<?php
		while ( $basix_related->have_posts() ) {
			$basix_related->the_post();
			get_template_part( 'template-parts/content', 'card' );
		}
		wp_reset_postdata();
		?>
	</div>
</section>
