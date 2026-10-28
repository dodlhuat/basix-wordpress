<?php get_header(); ?>

<?php while (have_posts()) : the_post(); ?>
    <article <?php post_class('entry'); ?>>
        <header class="entry-header"><h1><?php the_title(); ?></h1></header>
        <?php if (has_post_thumbnail()) : ?>
            <figure class="entry-media"><?php the_post_thumbnail('large'); ?></figure>
        <?php endif; ?>
        <div class="entry-content"><?php the_content(); ?></div>
        <?php wp_link_pages(); ?>
    </article>
    <?php if (comments_open()) comments_template(); ?>
<?php endwhile; ?>

<?php get_footer(); ?>
