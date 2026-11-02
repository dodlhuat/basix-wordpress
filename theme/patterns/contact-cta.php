<?php
/**
 * Title: Contact with a button
 * Slug: basix/contact-cta
 * Description: A friendly invitation to write, with a button.
 * Categories: basix, basix-contact
 * Keywords: contact, email, button, write
 */
?>
<!-- wp:group {"className":"cta card"} -->
<div class="wp-block-group cta card"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html_x( 'Questions? Write to us.', 'Contact pattern', 'basix' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"text-secondary"} -->
<p class="has-text-align-center text-secondary"><?php echo esc_html_x( 'We answer within one working day.', 'Contact pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Send an email', 'Contact pattern', 'basix' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group -->
