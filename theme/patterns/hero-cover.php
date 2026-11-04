<?php
/**
 * Title: Hero on a picture
 * Slug: basix/hero-cover
 * Description: A headline and a button on top of a full picture.
 * Categories: basix, basix-hero
 * Keywords: hero, banner, cover, picture, intro
 */

$basix_hero_image = esc_url( get_theme_file_uri( 'assets/images/hero.jpg' ) );
?>
<!-- wp:cover {"url":"<?php echo $basix_hero_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>","dimRatio":60,"minHeight":440,"align":"wide","className":"hero-cover"} -->
<div class="wp-block-cover alignwide hero-cover" style="min-height:440px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-60 has-background-dim"></span><img class="wp-block-cover__image-background" alt="" src="<?php echo $basix_hero_image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","className":"hero-title"} -->
<h2 class="wp-block-heading has-text-align-center hero-title"><?php echo esc_html_x( 'A clear headline for your site', 'Hero pattern', 'basix-paperlight' ); ?></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"hero-lead"} -->
<p class="has-text-align-center hero-lead"><?php echo esc_html_x( 'Say what you do in one or two sentences, and point people to the next step.', 'Hero pattern', 'basix-paperlight' ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:buttons {"layout":{"type":"flex","justifyContent":"center"}} -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#"><?php echo esc_html_x( 'Get started', 'Hero pattern', 'basix-paperlight' ); ?></a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div></div>
<!-- /wp:cover -->
