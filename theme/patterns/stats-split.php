<?php
/**
 * Title: Stats beside a text
 * Slug: basix/stats-split
 * Description: A short story on one side and three numbers on the other.
 * Categories: basix, basix-stats
 * Keywords: stats, numbers, story, about
 */
?>
<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"className":"text-overline"} -->
<p class="text-overline"><?php echo esc_html_x( 'In numbers', 'Stats pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html_x( 'A track record you can check', 'Stats pattern', 'basix' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Say in two sentences what these figures mean for the people who use what you make.', 'Stats pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:group {"className":"grid cols-2"} -->
<div class="wp-block-group grid cols-2"><!-- wp:group {"className":"card card-flat"} -->
<div class="wp-block-group card card-flat"><!-- wp:paragraph {"className":"stat-value"} -->
<p class="stat-value"><?php echo esc_html_x( '120+', 'Stats pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"stat-label text-secondary"} -->
<p class="stat-label text-secondary"><?php echo esc_html_x( 'Articles', 'Stats pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"card card-flat"} -->
<div class="wp-block-group card card-flat"><!-- wp:paragraph {"className":"stat-value"} -->
<p class="stat-value"><?php echo esc_html_x( '48k', 'Stats pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"className":"stat-label text-secondary"} -->
<p class="stat-label text-secondary"><?php echo esc_html_x( 'Readers', 'Stats pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
