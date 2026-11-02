<?php
/**
 * Title: Team, three people
 * Slug: basix/team-three
 * Description: Three people with a round picture, name and role.
 * Categories: basix, basix-team
 * Keywords: team, people, staff, about
 */
?>
<!-- wp:group {"align":"wide","className":"grid cols-3"} -->
<div class="wp-block-group alignwide grid cols-3"><!-- wp:group {"className":"team-card"} -->
<div class="wp-block-group team-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-circle"} -->
<figure class="wp-block-image size-large is-style-circle"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-1.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center"><?php echo esc_html_x( 'Alex Morgan', 'Team pattern', 'basix' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"text-secondary"} -->
<p class="has-text-align-center text-secondary"><?php echo esc_html_x( 'Editor', 'Team pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"team-card"} -->
<div class="wp-block-group team-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-circle"} -->
<figure class="wp-block-image size-large is-style-circle"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-2.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center"><?php echo esc_html_x( 'Sam Lee', 'Team pattern', 'basix' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"text-secondary"} -->
<p class="has-text-align-center text-secondary"><?php echo esc_html_x( 'Designer', 'Team pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:group {"className":"team-card"} -->
<div class="wp-block-group team-card"><!-- wp:image {"sizeSlug":"large","linkDestination":"none","className":"is-style-circle"} -->
<figure class="wp-block-image size-large is-style-circle"><img src="<?php echo esc_url( get_theme_file_uri( 'assets/images/square-3.jpg' ) ); ?>" alt=""/></figure>
<!-- /wp:image -->

<!-- wp:heading {"level":3,"textAlign":"center"} -->
<h3 class="wp-block-heading has-text-align-center"><?php echo esc_html_x( 'Jo Baker', 'Team pattern', 'basix' ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","className":"text-secondary"} -->
<p class="has-text-align-center text-secondary"><?php echo esc_html_x( 'Developer', 'Team pattern', 'basix' ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
