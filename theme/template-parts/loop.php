<?php if (have_posts()) : ?>
    <div class="grid post-grid">
        <?php while (have_posts()) : the_post(); get_template_part('template-parts/content', 'card'); endwhile; ?>
    </div>
    <?php basix_pagination(); ?>
<?php else : ?>
    <div class="alert alert-info"><?php esc_html_e('Nothing found.', 'basix'); ?></div>
    <?php if (!is_search()) get_search_form(); ?>
<?php endif; ?>
