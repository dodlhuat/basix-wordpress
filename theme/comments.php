<?php if (post_password_required()) return; ?>

<section id="comments" class="comments">
    <?php if (have_comments()) : ?>
        <h2><?php printf(esc_html(_n('%s comment', '%s comments', get_comments_number(), 'basix')), number_format_i18n(get_comments_number())); ?></h2>
        <ol class="comment-list">
            <?php wp_list_comments(['style' => 'ol', 'callback' => 'basix_comment']); ?>
        </ol>
        <?php the_comments_pagination(); ?>
    <?php endif; ?>

    <?php if (!comments_open() && get_comments_number()) : ?>
        <div class="alert alert-default"><?php esc_html_e('Comments are closed.', 'basix'); ?></div>
    <?php endif; ?>

    <?php comment_form(['class_submit' => 'button button-primary', 'class_form' => 'comment-form form-group']); ?>
</section>
