<?php
/**
 * Title: Portfolio grid
 * Slug: basix/portfolio-grid
 * Description: Six projects with a picture, title and kind of work.
 * Categories: basix, basix-portfolio
 * Keywords: portfolio, work, projects, gallery
 */
?>
<!-- wp:group {"align":"wide","className":"grid cols-3"} -->
<div class="wp-block-group alignwide grid cols-3"><!-- wp:group {"className":"work-card"} -->
<div class="wp-block-group work-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-framed"} -->
<figure class="wp-block-image size-large is-style-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-1.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Project one', 'Portfolio pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'Branding', 'Portfolio pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"work-card"} -->
<div class="wp-block-group work-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-framed"} -->
<figure class="wp-block-image size-large is-style-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-2.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Project two', 'Portfolio pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'Website', 'Portfolio pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"work-card"} -->
<div class="wp-block-group work-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-framed"} -->
<figure class="wp-block-image size-large is-style-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-3.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Project three', 'Portfolio pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'Editorial', 'Portfolio pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"work-card"} -->
<div class="wp-block-group work-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-framed"} -->
<figure class="wp-block-image size-large is-style-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-5.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Project four', 'Portfolio pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'Illustration', 'Portfolio pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"work-card"} -->
<div class="wp-block-group work-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-framed"} -->
<figure class="wp-block-image size-large is-style-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-6.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Project five', 'Portfolio pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'Packaging', 'Portfolio pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"work-card"} -->
<div class="wp-block-group work-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-framed"} -->
<figure class="wp-block-image size-large is-style-framed"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-7.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Project six', 'Portfolio pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'Photography', 'Portfolio pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
