<?php
/**
 * Title: Testimonial with a picture
 * Slug: basix/testimonial-picture
 * Description: A quote beside a round portrait.
 * Categories: basix, basix-testimonials
 * Keywords: testimonial, portrait, quote, person
 */
?>
<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"20%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:20%"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-circle"} -->
<figure class="wp-block-image size-large is-style-circle"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-4.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:quote {"className":"testimonial-quote"} -->
<blockquote class="wp-block-quote testimonial-quote"><!-- wp:paragraph -->
<p><?php echo esc_html_x( 'A few sentences from a happy customer, in their own words.', 'Testimonial pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --><cite><?php echo esc_html_x( 'Alex Morgan, Editor', 'Testimonial pattern', 'basix' ); ?></cite></blockquote>
<!-- /wp:quote --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
