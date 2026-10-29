<?php if ( ! is_post_type_archive( BASIX_EVENT ) ) : ?>
    <?php get_template_part( 'template-parts/filters' ); ?>
<?php endif; ?>

<?php if ( have_posts() ) : ?>
    <div class="post-list"><div class="grid post-grid">
        <?php
        $basix_position = 0;
        while ( have_posts() ) :
            the_post();
            // The first card on the first page can be shown larger (the "magazine" list layout).
            get_template_part( 'template-parts/content', 'card', array( 'first' => 0 === $basix_position++ && ! is_paged() ) );
        endwhile;
        ?>
    </div></div>
    <?php basix_pagination(); ?>
<?php else : ?>
    <div class="alert alert-info"><?php esc_html_e( 'Nothing found.', 'basix' ); ?></div>
    <?php if ( ! is_search() && ! basix_active_filters() ) get_search_form(); ?>
<?php endif; ?>
