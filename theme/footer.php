</main>

<footer class="site-footer">
    <div class="container site-footer-inner">
        <p class="text-caption">&copy; <?php echo esc_html(wp_date('Y')); ?> <?php bloginfo('name'); ?></p>
        <button type="button" class="back-to-top button" aria-label="<?php esc_attr_e('Back to top', 'basix'); ?>">
            <svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url(get_theme_file_uri(BASIX_VENDOR . '/svg-icons/icons.svg#arrow_upward')); ?>"/></svg>
        </button>
    </div>
</footer>
<?php wp_footer(); ?>
</body>
</html>
