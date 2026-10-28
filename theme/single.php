<?php get_header(); ?>

<?php basix_layout_open(); ?>

<?php while (have_posts()) : the_post(); ?>
    <article <?php post_class('entry'); ?>>
        <header class="entry-header">
            <?php basix_post_terms(); ?>
            <h1><?php the_title(); ?></h1>
            <?php basix_post_meta(); ?>
        </header>

        <?php if (has_post_thumbnail()) : ?>
            <figure class="entry-media"><?php the_post_thumbnail('large'); ?></figure>
        <?php endif; ?>

        <div class="entry-content"><?php the_content(); ?></div>

        <?php wp_link_pages(); ?>

        <?php if (has_tag()) : ?>
            <footer class="entry-footer chips">
                <?php foreach (get_the_tags() as $basix_tag) : ?>
                    <a class="chip clickable" href="<?php echo esc_url(get_tag_link($basix_tag)); ?>"><?php echo esc_html($basix_tag->name); ?></a>
                <?php endforeach; ?>
            </footer>
        <?php endif; ?>
    </article>

    <nav class="post-nav" aria-label="<?php esc_attr_e('Post navigation', 'basix'); ?>">
        <div><?php previous_post_link('%link', '← %title'); ?></div>
        <div class="text-right"><?php next_post_link('%link', '%title →'); ?></div>
    </nav>

    <?php if (comments_open() || get_comments_number()) comments_template(); ?>
<?php endwhile; ?>

<?php basix_layout_close(); ?>

<?php get_footer(); ?>
