<?php
/**
 * Title: Call to action
 * Slug: basix/cta
 * Description: A highlighted box with a headline, a sentence and a button.
 * Categories: basix, basix-call-to-action
 * Keywords: cta, call to action, banner, newsletter
 */
?>
<!-- wp:group {"className":"cta card"} -->
<div class="wp-block-group cta card"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html_x( 'Ready when you are', 'CTA pattern', 'basix' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><?php echo esc_html_x( 'One sentence that makes the next step feel easy.', 'CTA pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Get in touch', 'CTA pattern', 'basix' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
