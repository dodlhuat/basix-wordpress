<?php get_header(); ?>

<?php basix_layout_open(); ?>

<header class="archive-header">
    <h1><?php printf(esc_html__('Search results for “%s”', 'basix'), esc_html(get_search_query())); ?></h1>
    <?php get_search_form(); ?>
</header>

<?php get_template_part('template-parts/loop'); ?>

<?php basix_layout_close(); ?>

<?php get_footer(); ?>
