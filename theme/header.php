<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php wp_head(); ?>
</head>
<body <?php body_class('primary-light-bg primary'); ?>>
<?php wp_body_open(); ?>
<a class="skip-link" href="#content"><?php esc_html_e('Skip to content', 'basix'); ?></a>

<header class="site-header">
    <div class="container site-header-inner">
        <a class="site-title" href="<?php echo esc_url(home_url('/')); ?>"><?php bloginfo('name'); ?></a>

        <button class="site-nav-toggle button" aria-expanded="false" aria-controls="site-nav" aria-label="<?php esc_attr_e('Menu', 'basix'); ?>">
            <svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url(get_theme_file_uri(BASIX_VENDOR . '/svg-icons/icons.svg#menu')); ?>"/></svg>
        </button>

        <?php
        wp_nav_menu([
            'theme_location' => 'primary',
            'container'      => 'nav',
            'container_id'   => 'site-nav',
            'container_class' => 'site-nav',
            'menu_class'     => 'site-nav-list',
            'fallback_cb'    => false,
        ]);
        ?>

        <div id="theme-toggle" class="theme-toggle flex" role="button" tabindex="0" aria-label="<?php esc_attr_e('Toggle dark mode', 'basix'); ?>" style="cursor:pointer;">
            <svg id="theme-icon" data-icon-light="clear_day" data-icon-dark="bedtime" style="fill:currentColor;width:20px;height:20px;display:block;" width="20" height="20"><use href="<?php echo esc_url(get_theme_file_uri(BASIX_VENDOR . '/svg-icons/icons.svg#clear_day')); ?>"/></svg>
        </div>
    </div>
</header>

<main id="content" class="container site-main">
<?php basix_breadcrumb(); ?>
