<?php get_header(); ?>

<section class="hero">
    <h1 class="hero-title"><?php bloginfo('name'); ?></h1>
    <?php if (get_bloginfo('description')) : ?>
        <p class="hero-lead"><?php bloginfo('description'); ?></p>
    <?php endif; ?>
    <p class="hero-actions">
        <a class="button button-primary" href="#latest"><?php esc_html_e('Latest posts', 'basix'); ?></a>
        <?php if (get_option('show_on_front') === 'page' && get_option('page_for_posts')) : ?>
            <a class="button" href="<?php echo esc_url(get_permalink(get_option('page_for_posts'))); ?>"><?php esc_html_e('All posts', 'basix'); ?></a>
        <?php endif; ?>
    </p>
</section>

<?php if (get_option('show_on_front') === 'page') : ?>
    <?php while (have_posts()) : the_post(); ?>
        <?php if (get_the_content()) : ?>
            <div class="entry-content front-content"><?php the_content(); ?></div>
        <?php endif; ?>
    <?php endwhile; ?>
<?php endif; ?>

<section id="latest" class="front-latest">
    <h2><?php esc_html_e('Latest posts', 'basix'); ?></h2>
    <?php
    $latest = new WP_Query(['posts_per_page' => 6, 'ignore_sticky_posts' => true]);
    if ($latest->have_posts()) : ?>
        <div class="grid post-grid">
            <?php while ($latest->have_posts()) : $latest->the_post(); get_template_part('template-parts/content', 'card'); endwhile; ?>
        </div>
    <?php else : ?>
        <div class="alert alert-info"><?php esc_html_e('Nothing published yet.', 'basix'); ?></div>
    <?php endif; wp_reset_postdata(); ?>
</section>

<?php get_footer(); ?>
