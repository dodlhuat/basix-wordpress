<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e('Skip to content', 'basix'); ?></a>

<header class="site-header">
    <div class="container site-header-inner">
        <a class="site-title" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>

        <?php
        wp_nav_menu([
            'theme_location'  => 'primary',
            'container'       => 'nav',
            'container_id'    => 'site-nav',
            'container_class' => 'site-nav',
            'menu_class'      => 'site-nav-list',
            'depth'           => 1,
            'fallback_cb'     => false,
        ]);
        ?>

        <div class="site-actions">
            <button type="button" id="theme-toggle" class="theme-toggle button" data-tooltip="<?php esc_attr_e('Toggle dark mode', 'basix'); ?>" data-tooltip-position="bottom" aria-label="<?php esc_attr_e('Toggle dark mode', 'basix'); ?>">
                <svg id="theme-icon" class="icon-svg" aria-hidden="true" data-icon-light="clear_day" data-icon-dark="bedtime"><use href="<?php echo esc_url(get_theme_file_uri(BASIX_VENDOR . '/svg-icons/icons.svg#clear_day')); ?>"/></svg>
            </button>
            <button type="button" class="site-menu-trigger button" aria-label="<?php esc_attr_e('Open menu', 'basix'); ?>">
                <svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url(get_theme_file_uri(BASIX_VENDOR . '/svg-icons/icons.svg#menu')); ?>"/></svg>
            </button>
        </div>
    </div>
</header>

<div class="flyout-overlay" id="site-flyout-overlay"></div>
<div class="flyout-menu" id="site-flyout">
    <?php
    wp_nav_menu([
        'theme_location' => 'primary',
        'container'      => false,
        'items_wrap'     => '<ul>%3$s</ul>',
        'depth'          => 1,
        'fallback_cb'    => false,
    ]);
    ?>
</div>

<main id="content" class="container site-main">
<?php basix_breadcrumb(); ?>
