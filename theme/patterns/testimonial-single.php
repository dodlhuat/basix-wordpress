<?php
/**
 * Title: Testimonial
 * Slug: basix/testimonial-single
 * Description: One large quote with a name.
 * Categories: basix, basix-testimonials
 * Keywords: testimonial, quote, review
 */
?>
<!-- wp:group {"className":"testimonial-single"} -->
<div class="wp-block-group testimonial-single"><!-- wp:quote {"className":"testimonial-quote"} -->
<blockquote class="wp-block-quote testimonial-quote"><!-- wp:paragraph -->
<p><?php echo esc_html_x( 'This changed how we work. It was ready in an afternoon and our readers noticed at once.', 'Testimonial pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html_x( 'Alex Morgan, Editor', 'Testimonial pattern', 'basix' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:group -->
