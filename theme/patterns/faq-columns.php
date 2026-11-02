<?php
/**
 * Title: FAQ with a heading beside
 * Slug: basix/faq-columns
 * Description: A heading and intro on the left, questions on the right.
 * Categories: basix, basix-faq
 * Keywords: faq, questions, columns, details
 */
?>
<!-- wp:columns {"align":"wide"} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"34%"} -->
<div class="wp-block-column" style="flex-basis:34%"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html_x( 'Questions?', 'FAQ pattern', 'basix' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"text-secondary"} -->
<p class="text-secondary"><?php echo esc_html_x( 'The short answers to what people ask most.', 'FAQ pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:details -->
<details class="wp-block-details"><summary><?php echo esc_html_x( 'What is this about?', 'FAQ pattern', 'basix' ); ?></summary><!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Give a short, direct answer.', 'FAQ pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary><?php echo esc_html_x( 'How do I get started?', 'FAQ pattern', 'basix' ); ?></summary><!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Give a short, direct answer.', 'FAQ pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<!-- wp:details -->
<details class="wp-block-details"><summary><?php echo esc_html_x( 'Can I change my mind later?', 'FAQ pattern', 'basix' ); ?></summary><!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Give a short, direct answer.', 'FAQ pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
