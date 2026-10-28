<?php get_header(); ?>

<section class="not-found text-center">
    <h1>404</h1>
    <p class="text-secondary"><?php esc_html_e('This page could not be found.', 'basix'); ?></p>
    <?php get_search_form(); ?>
    <p><a class="button button-primary" href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to home', 'basix'); ?></a></p>
</section>

<?php get_footer(); ?>
