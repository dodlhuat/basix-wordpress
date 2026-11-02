<?php
/**
 * Title: Hero
 * Slug: basix/hero
 * Description: A centred introduction with a headline, a short lead and two buttons.
 * Categories: basix, basix-hero
 * Keywords: hero, banner, intro, header
 */
?>
<!-- wp:group {"className":"hero card card-flat"} -->
<div class="wp-block-group hero card card-flat"><!-- wp:paragraph {"className":"text-overline"} -->
<p class="text-overline"><?php echo esc_html_x( 'Welcome', 'Hero pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"hero-title"} -->
<h2 class="wp-block-heading hero-title"><?php echo esc_html_x( 'A clear headline for your site', 'Hero pattern', 'basix' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"hero-lead"} -->
<p class="hero-lead"><?php echo esc_html_x( 'Say what you do in one or two sentences, and point people to the next step.', 'Hero pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"className":"hero-actions","layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons hero-actions"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Get started', 'Hero pattern', 'basix' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Learn more', 'Hero pattern', 'basix' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
