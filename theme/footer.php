<?php
/**
 * Site footer: text, footer and social menus, back to top button.
 *
 * @package basix
 */

?>
</main>

<footer class="site-footer">
    <div class="container site-footer-inner">
        <div class="site-footer-main">
            <p class="text-caption"><?php echo basix_footer_text(); ?></p>
            <?php basix_footer_menus(); ?>
        </div>
        <?php if ( get_theme_mod( 'basix_back_to_top', true ) ) : ?>
            <button type="button" class="back-to-top button" aria-label="<?php esc_attr_e( 'Back to top', 'basix' ); ?>">
                <svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url( get_theme_file_uri( BASIX_VENDOR . '/svg-icons/icons.svg#arrow_upward' ) ); ?>"/></svg>
            </button>
        <?php endif; ?>
    </div>
</footer>
<?php if ( 'push' === basix_header_layout() && has_nav_menu( 'primary' ) ) : ?>
    </div><!-- .push-content -->
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
