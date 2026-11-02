<?php
/**
 * Title: Features beside a picture
 * Slug: basix/features-split
 * Description: A list of benefits next to a picture.
 * Categories: basix, basix-features
 * Keywords: features, list, picture, split
 */
?>
<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-shadow"} -->
<figure class="wp-block-image size-large is-style-shadow"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/wide-1.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"className":"text-overline"} -->
<p class="text-overline"><?php echo esc_html_x( 'Why choose us', 'Features pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html_x( 'Everything you need, nothing you do not', 'Features pattern', 'basix' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:list {"className":"check-list"} -->
<ul class="wp-block-list check-list"><!-- wp:list-item -->
<li><?php echo esc_html_x( 'A clear first benefit', 'Features pattern', 'basix' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html_x( 'A second benefit that matters', 'Features pattern', 'basix' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html_x( 'A third reason to stay', 'Features pattern', 'basix' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Learn more', 'Features pattern', 'basix' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
