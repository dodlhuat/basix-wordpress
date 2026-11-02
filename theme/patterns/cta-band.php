<?php
/**
 * Title: Call to action band
 * Slug: basix/cta-band
 * Description: A short line with one button on a tinted band.
 * Categories: basix, basix-call-to-action
 * Keywords: cta, band, banner, button
 */
?>
<!-- wp:group {"align":"wide","className":"cta-band"} -->
<div class="wp-block-group alignwide cta-band"><!-- wp:paragraph {"className":"cta-band-text"} -->
<p class="cta-band-text"><?php echo esc_html_x( 'Start your project today.', 'Call to action pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Get in touch', 'Call to action pattern', 'basix' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
