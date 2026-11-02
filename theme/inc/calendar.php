<?php
/**
 * Settings for the calendar: events (or posts by publish date), loaded for the visible range.
 *
 * @package basix
 */

/**
 * Everything the calendar's JavaScript needs: where to fetch posts, how to link to a day, and the
 * site's language for month and weekday names and the interface text.
 *
 * @return array
 */
function basix_calendar_config(): array {
	global $wp_locale;

	// Weekday names start on Sunday, which is what the calendar component expects.
	$days_full  = array();
	$days_short = array();
	for ( $i = 0; $i < 7; $i++ ) {
		$name         = $wp_locale->get_weekday( $i );
		$days_full[]  = $name;
		$days_short[] = $wp_locale->get_weekday_abbrev( $name );
	}
	$months = array();
	for ( $i = 1; $i <= 12; $i++ ) {
		$months[] = $wp_locale->get_month( $i );
	}

	// A day archive address with placeholders, in whatever permalink style the site uses.
	$day_url = get_day_link( 1970, 1, 1 );
	$day_url = false !== strpos( $day_url, '1970/01/01' )
		? str_replace( '1970/01/01', '{y}/{m}/{d}', $day_url )
		: str_replace( '19700101', '{y}{m}{d}', $day_url );

	$categories = array();
	foreach ( get_categories() as $category ) {
		$categories[ $category->term_id ] = $category->name;
	}

	return array(
		'restUrl'         => rest_url( 'wp/v2/posts' ),
		'eventsUrl'       => rest_url( 'basix/v1/events' ),
		'eventCategories' => basix_has_events() ? basix_event_category_list() : array(),
		'dayUrl'          => $day_url,
		'eventDayUrl'     => basix_has_events() ? str_replace( '2000-01-01', '{date}', basix_event_day_url( '2000-01-01' ) ) : '',
		'categories'      => (object) $categories,
		'firstDay'        => (int) get_option( 'start_of_week' ),
		'dateLocale'      => str_replace( '_', '-', get_locale() ),
		'hour12'          => (bool) preg_match( '/[aA]/', (string) get_option( 'time_format' ) ),
		'months'          => $months,
		'daysFull'        => $days_full,
		'daysShort'       => $days_short,
		'text'            => array(
			'today'      => __( 'Today', 'basix' ),
			'month'      => __( 'Month', 'basix' ),
			'week'       => __( 'Week', 'basix' ),
			'agenda'     => __( 'Agenda', 'basix' ),
			'allDay'     => __( 'All day', 'basix' ),
			'noEvents'   => __( 'No posts', 'basix' ),
			'previous'   => __( 'Previous', 'basix' ),
			'next'       => __( 'Next', 'basix' ),
			'viewSwitch' => __( 'Calendar view', 'basix' ),
			'calendar'   => __( 'Posts calendar', 'basix' ),
			'error'      => __( 'The posts could not be loaded. Please try again.', 'basix' ),
			'categories' => __( 'Event categories', 'basix' ),
		),
	);
}

/**
 * The calendar's markup, for the page templates and for the block and shortcode of the Basix Events plugin.
 *
 * @param string $view   Starting view: month, week or agenda.
 * @param string $source What it shows: events (the Event post type) or posts (blog posts by publish date).
 * @param string $category Event category slugs, comma separated (only these events; the category chips are then left out).
 * @return string
 */
function basix_calendar_html( string $view = 'month', string $source = 'events', string $category = '' ): string {
	if ( ! in_array( $view, array( 'month', 'week', 'agenda' ), true ) ) {
		$view = 'month';
	}
	ob_start();
	get_template_part(
		'template-parts/calendar',
		null,
		array(
			'view'     => $view,
			'source'   => 'posts' === $source || ! basix_has_events() ? 'posts' : 'events',
			'category' => basix_has_events() ? implode( ',', basix_event_category_slugs( $category ) ) : '',
		)
	);
	return (string) ob_get_clean();
}
