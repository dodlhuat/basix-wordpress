<?php get_header(); ?>

<?php basix_layout_open(); ?>

<?php if (is_home() && !is_front_page()) : ?>
    <h1><?php single_post_title(); ?></h1>
<?php endif; ?>

<?php get_template_part('template-parts/loop'); ?>

<?php basix_layout_close(); ?>

<?php get_footer(); ?>
