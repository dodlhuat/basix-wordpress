<?php
/**
 * Title: Contact details
 * Slug: basix/contact-details
 * Description: Address, email and opening hours in three columns.
 * Categories: basix, basix-contact
 * Keywords: contact, address, email, phone, hours
 */
?>
<!-- wp:group {"align":"wide","className":"grid cols-3"} -->
<div class="wp-block-group alignwide grid cols-3"><!-- wp:group {"className":"card card-bordered"} -->
<div class="wp-block-group card card-bordered"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Visit', 'Contact pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Example Street 1', 'Contact pattern', 'basix-paperlight' ); ?><br><?php echo esc_html_x( '1010 Vienna', 'Contact pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"card card-bordered"} -->
<div class="wp-block-group card card-bordered"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Write', 'Contact pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'hello@example.com', 'Contact pattern', 'basix-paperlight' ); ?><br><?php echo esc_html_x( '+43 1 234 5678', 'Contact pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"card card-bordered"} -->
<div class="wp-block-group card card-bordered"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html_x( 'Hours', 'Contact pattern', 'basix-paperlight' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Monday to Friday', 'Contact pattern', 'basix-paperlight' ); ?><br><?php echo esc_html_x( '9:00 to 17:00', 'Contact pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
