<?php
/**
 * Title: Pricing, two plans
 * Slug: basix/pricing-two
 * Description: Two plans, one free and one paid.
 * Categories: basix, basix-pricing
 * Keywords: pricing, plans, free, price
 */
?>
<!-- wp:group {"align":"wide","className":"grid cols-2"} -->
<div class="wp-block-group alignwide grid cols-2"><!-- wp:group {"className":"card card-bordered"} -->
<div class="wp-block-group card card-bordered"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Free', 'Pricing pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"price"} -->
<p class="price"><?php echo esc_html_x( '$0', 'Pricing pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'for personal projects', 'Pricing pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"check-list"} -->
<ul class="wp-block-list check-list"><!-- wp:list-item -->
<li><?php echo esc_html_x( 'One site', 'Pricing pattern', 'basix-paperlight' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html_x( 'Community support', 'Pricing pattern', 'basix-paperlight' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Start for free', 'Pricing pattern', 'basix-paperlight' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"card card-bordered is-featured"} -->
<div class="wp-block-group card card-bordered is-featured"><!-- wp:paragraph {"className":"badge badge-info"} -->
<p class="badge badge-info"><?php echo esc_html_x( 'Recommended', 'Pricing pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Pro', 'Pricing pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"price"} -->
<p class="price"><?php echo esc_html_x( '$29', 'Pricing pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'per month, billed yearly', 'Pricing pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"check-list"} -->
<ul class="wp-block-list check-list"><!-- wp:list-item -->
<li><?php echo esc_html_x( 'Unlimited sites', 'Pricing pattern', 'basix-paperlight' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html_x( 'Priority support', 'Pricing pattern', 'basix-paperlight' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html_x( 'Early access', 'Pricing pattern', 'basix-paperlight' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Go Pro', 'Pricing pattern', 'basix-paperlight' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
