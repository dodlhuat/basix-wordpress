<?php
/**
 * Title: Portfolio feature
 * Slug: basix/portfolio-feature
 * Description: One large project with its story.
 * Categories: basix, basix-portfolio
 * Keywords: portfolio, project, case study, feature
 */
?>
<!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-shadow"} -->
<figure class="wp-block-image size-large is-style-shadow"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/wide-2.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center"} -->
<div class="wp-block-column is-vertically-aligned-center"><!-- wp:paragraph {"className":"text-overline"} -->
<p class="text-overline"><?php echo esc_html_x( 'Case study', 'Portfolio pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:heading -->
<h2 class="wp-block-heading"><?php echo esc_html_x( 'Project name', 'Portfolio pattern', 'basix-paperlight' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html_x( 'Two or three sentences about the problem, the approach and the result.', 'Portfolio pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Read the story', 'Portfolio pattern', 'basix-paperlight' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->
