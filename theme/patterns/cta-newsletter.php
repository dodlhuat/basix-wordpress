<?php
/**
 * Title: Newsletter sign-up
 * Slug: basix/cta-newsletter
 * Description: A headline and an email field. Replace the form with your newsletter plugin.
 * Categories: basix, basix-call-to-action, basix-newsletter
 * Keywords: newsletter, subscribe, email, form
 */
?>
<!-- wp:group {"className":"cta card"} -->
<div class="wp-block-group cta card"><!-- wp:heading {"textAlign":"center"} -->
<h2 class="wp-block-heading has-text-align-center"><?php echo esc_html_x( 'Stay in the loop', 'Call to action pattern', 'basix' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"text-secondary"} -->
<p class="has-text-align-center text-secondary"><?php echo esc_html_x( 'One short email when something new is published. No spam.', 'Call to action pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:html -->
<form class="newsletter-form" action="#" method="post"><div class="input-group"><input type="email" name="email" placeholder="<?php echo esc_attr_x( 'you@example.com', 'Call to action pattern', 'basix' ); ?>" aria-label="<?php echo esc_attr_x( 'Email address', 'Call to action pattern', 'basix' ); ?>" autocomplete="email" required><button class="button button-primary" type="submit"><?php echo esc_html_x( 'Subscribe', 'Call to action pattern', 'basix' ); ?></button></div></form>
<!-- /wp:html --></div>
<!-- /wp:group -->
