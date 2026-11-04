<?php
/**
 * Title: Hero with picture
 * Slug: basix/hero-split
 * Description: A headline and two buttons beside a picture.
 * Categories: basix, basix-hero
 * Keywords: hero, banner, picture, split, intro
 */

$basix_hero_image = esc_url( get_theme_file_uri( 'assets/images/hero.jpg' ) );
?>
<!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"hero-split"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center hero-split"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"className":"text-overline"} -->
<p class="text-overline"><?php echo esc_html_x( 'Welcome', 'Hero pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"hero-title"} -->
<h2 class="wp-block-heading hero-title"><?php echo esc_html_x( 'A clear headline for your site', 'Hero pattern', 'basix-paperlight' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"hero-lead"} -->
<p class="hero-lead"><?php echo esc_html_x( 'Say what you do in one or two sentences, and point people to the next step.', 'Hero pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Get started', 'Hero pattern', 'basix-paperlight' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Learn more', 'Hero pattern', 'basix-paperlight' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:image {"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="<?php echo $basix_hero_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>" alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
