<?php
/**
 * Title: FAQ
 * Slug: basix/faq
 * Description: Frequently asked questions as collapsible items.
 * Categories: basix, basix-faq
 * Keywords: faq, questions, accordion, details
 */
?>
<!-- wp:group {"className":"faq"} -->
<div class="wp-block-group faq"><!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html_x( 'Frequently asked questions', 'FAQ pattern', 'basix' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:details -->
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
<!-- /wp:group -->
