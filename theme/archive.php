<?php get_header(); ?>

<header class="archive-header">
    <?php the_archive_title('<h1>', '</h1>'); ?>
    <?php the_archive_description('<div class="text-secondary">', '</div>'); ?>
</header>

<?php get_template_part('template-parts/loop'); ?>

<?php get_footer(); ?>
