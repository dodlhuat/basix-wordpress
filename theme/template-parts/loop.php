<?php $basix_event_day = basix_event_day(); ?>
<?php if ( $basix_event_day ) : ?>
    <?php get_template_part( 'template-parts/event-day-nav', null, array( 'day' => $basix_event_day ) ); ?>
<?php elseif ( is_post_type_archive( BASIX_EVENT ) || is_tax( BASIX_EVENT_CAT ) ) : ?>
    <?php get_template_part( 'template-parts/event-categories' ); ?>
    <?php $basix_feed_category = is_tax( BASIX_EVENT_CAT ) ? get_queried_object()->slug : ''; ?>
    <p class="event-subscribe text-caption">
        <a href="<?php echo esc_url( basix_events_feed_url( $basix_feed_category, true ) ); ?>"><?php esc_html_e( 'Subscribe in your calendar app', 'basix' ); ?></a>
        · <a href="<?php echo esc_url( basix_events_feed_url( $basix_feed_category ) ); ?>"><?php esc_html_e( 'Calendar file (.ics)', 'basix' ); ?></a>
    </p>
<?php else : ?>
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
<?php elseif ( $basix_event_day ) : ?>
    <div class="alert alert-info"><?php esc_html_e( 'No events on this day.', 'basix' ); ?></div>
<?php else : ?>
    <div class="alert alert-info"><?php esc_html_e( 'Nothing found.', 'basix' ); ?></div>
    <?php if ( ! is_search() && ! basix_active_filters() ) get_search_form(); ?>
<?php endif; ?>
