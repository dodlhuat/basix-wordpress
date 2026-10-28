<article <?php post_class(!empty($args['first']) ? 'card card-bordered card-hover cell is-first' : 'card card-bordered card-hover cell'); ?>>
    <?php if (has_post_thumbnail()) : ?>
        <a class="card-media" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
            <?php the_post_thumbnail('medium_large'); ?>
        </a>
    <?php endif; ?>
    <div class="card-header"><?php basix_post_terms(); ?></div>
    <h2 class="card-title"><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h2>
    <?php basix_post_meta(); ?>
    <div class="card-excerpt"><?php the_excerpt(); ?></div>
    <div class="card-footer">
        <a class="button button-primary" href="<?php the_permalink(); ?>"><?php esc_html_e('Read more', 'basix'); ?>
            <span class="screen-reader-text">
            <?php
            /* translators: %s: post title. */
            echo esc_html(sprintf(__('about “%s”', 'basix'), get_the_title()));
            ?>
            </span></a>
    </div>
</article>
