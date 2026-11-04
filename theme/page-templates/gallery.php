<?php
/**
 * Template Name: Photo gallery
 * Template Post Type: page
 *
 * A masonry gallery of images that keeps loading as you scroll. It shows the images uploaded to this page,
 * or the whole media library when there are none. Without JavaScript the first images are listed as links.
 *
 * @package basix
 */

get_header();

if ( ! basix_builder_location( 'single' ) ) : // A theme builder can draw this part of the page instead.

	while ( have_posts() ) :
		the_post();
		$basix_parent = basix_gallery_parent();
		?>
	<article <?php post_class( 'entry' ); ?>>
		<header class="entry-header<?php echo esc_attr( basix_title_class() ); ?>"><h1><?php the_title(); ?></h1></header>
		<div class="entry-content"><?php the_content(); ?></div>

		<div class="gallery-wrap">
			<div id="media-gallery" class="gallery-host" <?php echo basix_component_attrs( 'media-gallery', basix_gallery_config( $basix_parent ) ); ?>>
				<ul class="gallery-fallback">
					<?php foreach ( basix_gallery_first_images( $basix_parent ) as $basix_image_id ) : ?>
						<li>
							<a href="<?php echo esc_url( wp_get_attachment_url( $basix_image_id ) ); ?>">
								<?php echo wp_get_attachment_image( $basix_image_id, 'medium_large', false, array( 'loading' => 'lazy' ) ); ?>
							</a>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
			<div class="loader hidden" role="status"><?php esc_html_e( 'Loading more…', 'basix-paperlight' ); ?></div>
		</div>
	</article>
		<?php
endwhile;

endif;

get_footer();
