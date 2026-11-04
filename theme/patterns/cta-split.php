<?php
/**
 * Title: Call to action, text and button
 * Slug: basix/cta-split
 * Description: A headline on the left and a button on the right.
 * Categories: basix, basix-call-to-action
 * Keywords: cta, call to action, banner, button
 */
?>
<!-- wp:group {"align":"wide","className":"cta card"} -->
<div class="wp-block-group alignwide cta card"><!-- wp:columns {"verticalAlignment":"center"} -->
<div class="wp-block-columns are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html_x( 'Ready when you are', 'Call to action pattern', 'basix-paperlight' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'One sentence that makes the next step easy.', 'Call to action pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"34%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:34%"><!-- wp:buttons {"layout":{"type":"flex","justifyContent":"right"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Get started', 'Call to action pattern', 'basix-paperlight' ); ?></a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Talk to us', 'Call to action pattern', 'basix-paperlight' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
