<?php get_header(); ?>

<header class="archive-header">
    <h1><?php printf(esc_html__('Search results for “%s”', 'basix'), esc_html(get_search_query())); ?></h1>
    <?php get_search_form(); ?>
</header>

<?php get_template_part('template-parts/loop'); ?>

<?php get_footer(); ?>
