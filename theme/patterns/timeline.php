<?php
/**
 * Title: Timeline
 * Slug: basix/timeline
 * Description: A vertical timeline of dated events.
 * Categories: basix
 * Keywords: timeline, history, milestones, changelog
 */
?>
<!-- wp:group {"className":"timeline"} -->
<div class="wp-block-group timeline"><!-- wp:group {"className":"timeline-item"} -->
<div class="wp-block-group timeline-item"><!-- wp:paragraph {"className":"timeline-marker"} -->
<p class="timeline-marker"></p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"timeline-content"} -->
<div class="wp-block-group timeline-content"><!-- wp:paragraph {"className":"timeline-time"} -->
<p class="timeline-time"><?php echo esc_html_x( 'January 2026', 'Timeline pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4,"className":"timeline-title"} -->
<h4 class="wp-block-heading timeline-title"><?php echo esc_html_x( 'The beginning', 'Timeline pattern', 'basix' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'What happened, in a sentence.', 'Timeline pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"timeline-item"} -->
<div class="wp-block-group timeline-item"><!-- wp:paragraph {"className":"timeline-marker success"} -->
<p class="timeline-marker success"></p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"timeline-content"} -->
<div class="wp-block-group timeline-content"><!-- wp:paragraph {"className":"timeline-time"} -->
<p class="timeline-time"><?php echo esc_html_x( 'April 2026', 'Timeline pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4,"className":"timeline-title"} -->
<h4 class="wp-block-heading timeline-title"><?php echo esc_html_x( 'A big step forward', 'Timeline pattern', 'basix' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'What happened, in a sentence.', 'Timeline pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"timeline-item"} -->
<div class="wp-block-group timeline-item"><!-- wp:paragraph {"className":"timeline-marker accent"} -->
<p class="timeline-marker accent"></p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"timeline-content"} -->
<div class="wp-block-group timeline-content"><!-- wp:paragraph {"className":"timeline-time"} -->
<p class="timeline-time"><?php echo esc_html_x( 'Today', 'Timeline pattern', 'basix' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":4,"className":"timeline-title"} -->
<h4 class="wp-block-heading timeline-title"><?php echo esc_html_x( 'Where we are now', 'Timeline pattern', 'basix' ); ?></h4>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'What is happening now, in a sentence.', 'Timeline pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
