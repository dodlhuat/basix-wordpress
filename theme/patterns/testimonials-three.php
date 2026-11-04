<?php
/**
 * Title: Testimonials, three cards
 * Slug: basix/testimonials-three
 * Description: Three short quotes in cards.
 * Categories: basix, basix-testimonials
 * Keywords: testimonials, quotes, reviews, cards
 */
?>
<!-- wp:group {"align":"wide","className":"grid cols-3"} -->
<div class="wp-block-group alignwide grid cols-3"><!-- wp:group {"className":"card card-bordered"} -->
<div class="wp-block-group card card-bordered"><!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Clear, fast and easy to read.', 'Testimonial pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html_x( 'Sam Lee, Designer', 'Testimonial pattern', 'basix-paperlight' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"card card-bordered"} -->
<div class="wp-block-group card card-bordered"><!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html_x( 'The best starting point we have used.', 'Testimonial pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html_x( 'Jo Baker, Developer', 'Testimonial pattern', 'basix-paperlight' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"card card-bordered"} -->
<div class="wp-block-group card card-bordered"><!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Our readers stay longer now.', 'Testimonial pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html_x( 'Kim Novak, Publisher', 'Testimonial pattern', 'basix-paperlight' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
