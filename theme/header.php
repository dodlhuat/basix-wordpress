<?php
/**
 * Site header: logo or title, the primary menu in the layout chosen in the Customizer, and the colour toggle.
 *
 * @package basix
 */

$basix_layout = basix_header_layout();
$basix_sprite = get_theme_file_uri( BASIX_VENDOR . '/svg-icons/icons.svg' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e( 'Skip to content', 'basix' ); ?></a>

<?php if ( 'push' === $basix_layout && has_nav_menu( 'primary' ) ) : ?>
    <nav class="push-menu" aria-label="<?php esc_attr_e( 'Primary menu', 'basix' ); ?>">
        <?php basix_primary_menu( 'push' ); ?>
    </nav>
    <div class="push-menu-backdrop"></div>
    <div class="push-content">
<?php endif; ?>

<header class="site-header">
    <div class="container site-header-inner">
        <?php if ( 'push' === $basix_layout && has_nav_menu( 'primary' ) ) : ?>
            <div class="navigation-controls">
                <input type="checkbox" id="menu-navigation" class="navigation">
                <label for="menu-navigation" class="button" aria-label="<?php esc_attr_e( 'Toggle menu', 'basix' ); ?>">
                    <svg class="icon icon-svg" aria-hidden="true"><use href="<?php echo esc_url( $basix_sprite . '#menu' ); ?>"/></svg>
                </label>
            </div>
        <?php endif; ?>

        <?php if ( has_custom_logo() ) : ?>
            <?php the_custom_logo(); ?>
        <?php else : ?>
            <a class="site-title" href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php bloginfo( 'name' ); ?></a>
        <?php endif; ?>

        <?php if ( 'inline' === $basix_layout ) : ?>
            <?php basix_primary_menu( 'inline' ); ?>
        <?php endif; ?>

        <div class="site-actions">
            <a class="site-search-trigger button" href="<?php echo esc_url( home_url( '/?s=' ) ); ?>" aria-label="<?php esc_attr_e( 'Search', 'basix' ); ?>" data-tooltip="<?php esc_attr_e( 'Search (press /)', 'basix' ); ?>" data-tooltip-position="bottom">
                <svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url( $basix_sprite . '#search' ); ?>"/></svg>
            </a>
            <button type="button" id="theme-toggle" class="theme-toggle button" data-tooltip="<?php esc_attr_e( 'Toggle dark mode', 'basix' ); ?>" data-tooltip-position="bottom" aria-label="<?php esc_attr_e( 'Toggle dark mode', 'basix' ); ?>">
                <svg id="theme-icon" class="icon-svg" aria-hidden="true" data-icon-light="clear_day" data-icon-dark="bedtime"><use href="<?php echo esc_url( $basix_sprite . '#clear_day' ); ?>"/></svg>
            </button>

            <?php if ( 'push' !== $basix_layout ) : ?>
                <button type="button" class="site-menu-trigger button" aria-label="<?php esc_attr_e( 'Open menu', 'basix' ); ?>">
                    <svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url( $basix_sprite . '#menu' ); ?>"/></svg>
                </button>
            <?php endif; ?>
        </div>
    </div>
</header>

<?php if ( 'push' !== $basix_layout ) : ?>
    <div class="flyout-overlay" id="site-flyout-overlay"></div>
    <nav class="flyout-menu" id="site-flyout" aria-label="<?php esc_attr_e( 'Menu', 'basix' ); ?>">
        <?php basix_primary_menu( 'drawer' ); ?>
    </nav>
<?php endif; ?>

<main id="content" class="container site-main">
<?php basix_breadcrumb(); ?>
