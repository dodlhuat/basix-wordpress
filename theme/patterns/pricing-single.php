<?php
/**
 * Title: Pricing, one offer
 * Slug: basix/pricing-single
 * Description: One price with everything included.
 * Categories: basix, basix-pricing
 * Keywords: pricing, single, offer, price
 */
?>
<!-- wp:group {"className":"pricing-single"} -->
<div class="wp-block-group pricing-single"><!-- wp:group {"className":"card card-bordered is-featured"} -->
<div class="wp-block-group card card-bordered is-featured"><!-- wp:paragraph {"className":"text-overline"} -->
<p class="text-overline"><?php echo esc_html_x( 'Lifetime licence', 'Pricing pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"price"} -->
<p class="price"><?php echo esc_html_x( '$99', 'Pricing pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'One payment, every update, every site.', 'Pricing pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"check-list"} -->
<ul class="wp-block-list check-list"><!-- wp:list-item -->
<li><?php echo esc_html_x( 'All patterns and looks', 'Pricing pattern', 'basix' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html_x( 'Free updates', 'Pricing pattern', 'basix' ); ?></li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li><?php echo esc_html_x( 'Support by email', 'Pricing pattern', 'basix' ); ?></li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Buy now', 'Pricing pattern', 'basix' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
