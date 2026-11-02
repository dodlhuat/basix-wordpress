<?php
/**
 * Site footer: text, footer and social menus, back to top button.
 *
 * @package basix
 */

?>
</main>

<template id="basix-search-template" data-title="<?php esc_attr_e( 'Search', 'basix' ); ?>">
    <div class="search-overlay">
        <?php get_search_form(); ?>
        <p class="search-overlay-hint text-caption"><?php esc_html_e( 'Press Enter to search, Esc to close.', 'basix' ); ?></p>
    </div>
</template>

<?php $basix_footer_none = 'none' === basix_page_option( '_basix_footer' ); ?>
<?php if ( ! $basix_footer_none ) : ?>
<footer class="site-footer">
    <?php
    $basix_footer_columns = 'minimal' === basix_footer_layout() ? array() : array_values( array_filter( array( 'footer-1', 'footer-2', 'footer-3' ), 'is_active_sidebar' ) );
    if ( $basix_footer_columns ) :
        ?>
        <div class="container footer-widgets grid cols-<?php echo esc_attr( count( $basix_footer_columns ) ); ?>">
            <?php foreach ( $basix_footer_columns as $basix_column ) : ?>
                <div class="cell"><?php dynamic_sidebar( $basix_column ); ?></div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <div class="container site-footer-inner">
        <div class="site-footer-main">
            <p class="text-caption site-footer-text"><?php echo basix_footer_text(); ?></p>
            <?php basix_footer_menus(); ?>
        </div>
        <?php if ( get_theme_mod( 'basix_back_to_top', true ) ) : ?>
            <button type="button" class="back-to-top button" aria-label="<?php esc_attr_e( 'Back to top', 'basix' ); ?>">
                <svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url( get_theme_file_uri( BASIX_VENDOR . '/svg-icons/icons.svg#arrow_upward' ) ); ?>"/></svg>
            </button>
        <?php endif; ?>
    </div>
</footer>
<?php endif; ?>
<?php if ( 'none' !== basix_header_mode() && 'push' === basix_header_layout() && has_nav_menu( 'primary' ) ) : ?>
    </div><!-- .push-content -->
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
