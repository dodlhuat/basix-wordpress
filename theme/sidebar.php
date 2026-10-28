<?php if (!basix_has_sidebar()) return; ?>

<aside class="sidebar" aria-label="<?php esc_attr_e('Sidebar', 'basix'); ?>">
    <?php dynamic_sidebar('sidebar-1'); ?>
</aside>
