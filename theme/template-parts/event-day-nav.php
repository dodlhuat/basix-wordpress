<?php
/**
 * Above the events of one day: the day before, the day after, and back to the list of coming events.
 *
 * Args: day (Y-m-d).
 *
 * @package basix
 */

$basix_day   = $args['day'];
$basix_stamp = strtotime( $basix_day . ' 12:00:00 UTC' );
$basix_prev  = gmdate( 'Y-m-d', $basix_stamp - DAY_IN_SECONDS );
$basix_next  = gmdate( 'Y-m-d', $basix_stamp + DAY_IN_SECONDS );
$basix_show  = static function ( string $day ): string {
	return wp_date( get_option( 'date_format' ), strtotime( $day . ' 12:00:00 UTC' ), new DateTimeZone( 'UTC' ) );
};
?>
<nav class="filter-bar event-day-nav" aria-label="<?php esc_attr_e( 'Days', 'basix' ); ?>">
	<a class="chip clickable" href="<?php echo esc_url( basix_event_day_url( $basix_prev ) ); ?>" rel="nofollow">← <?php echo esc_html( $basix_show( $basix_prev ) ); ?></a>
	<a class="chip clickable" href="<?php echo esc_url( get_post_type_archive_link( BASIX_EVENT ) ); ?>"><?php esc_html_e( 'All coming events', 'basix' ); ?></a>
	<a class="chip clickable" href="<?php echo esc_url( basix_event_day_url( $basix_next ) ); ?>" rel="nofollow"><?php echo esc_html( $basix_show( $basix_next ) ); ?> →</a>
</nav>
