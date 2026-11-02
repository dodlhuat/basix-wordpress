<?php
/**
 * Title: Three pictures
 * Slug: basix/gallery-three
 * Description: Three equal pictures in a row.
 * Categories: basix, basix-portfolio
 * Keywords: gallery, pictures, images, row
 */
?>
<!-- wp:group {"align":"wide","className":"grid cols-3"} -->
<div class="wp-block-group alignwide grid cols-3"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-2.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-5.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-8.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:group -->
