<?php
/**
 * The events calendar block and the [basix_calendar] shortcode.
 *
 * The calendar itself (its markup, script and styles) belongs to the Basix theme. With another theme the block and
 * the shortcode show a plain list of the coming events instead.
 *
 * @package basix-events
 */

/**
 * The markup for the calendar block and the shortcode.
 *
 * @param string $view     Starting view: month, week or agenda.
 * @param string $source   What it shows: events or posts (blog posts by publish date; the Basix theme only).
 * @param string $category Event category slugs, comma separated.
 * @return string
 */
function basix_events_calendar_html( string $view, string $source, string $category ): string {
	if ( function_exists( 'basix_calendar_html' ) ) {
		return basix_calendar_html( $view, $source, $category );
	}
	// Another theme: a plain list of what is coming, which needs no script.
	$posts = basix_events_upcoming_posts( basix_event_category_slugs( $category ), 20 );
	if ( ! $posts ) {
		return '<p>' . esc_html__( 'No events are planned.', 'basix-events' ) . '</p>';
	}
	$out = '<ul class="basix-events-list">';
	foreach ( $posts as $post ) {
		$next = basix_event_next( $post->ID );
		$out .= sprintf(
			'<li><a href="%s">%s</a> <span>%s</span></li>',
			esc_url( get_permalink( $post ) ),
			esc_html( get_the_title( $post ) ),
			esc_html( basix_event_when( $post->ID, false, $next ) )
		);
	}
	return $out . '</ul>';
}

// The "Events calendar" block. It renders on the server, so the editor shows its options in the sidebar.
add_action(
	'init',
	function () {
		register_block_type(
			BASIX_EVENTS_DIR . 'blocks/calendar',
			array(
				'render_callback' => function ( array $attributes ): string {
					$wrapper = get_block_wrapper_attributes();
					return '<div ' . $wrapper . '>' . basix_events_calendar_html( (string) ( $attributes['view'] ?? 'month' ), (string) ( $attributes['source'] ?? 'events' ), (string) ( $attributes['category'] ?? '' ) ) . '</div>';
				},
			)
		);
	}
);

// Shortcode: basix_calendar, with optional view (month, week, agenda), source (events, posts) and category (event category slugs) attributes.
add_shortcode(
	'basix_calendar',
	function ( $atts ): string {
		$atts = shortcode_atts(
			array(
				'view'     => 'month',
				'source'   => 'events',
				'category' => '',
			),
			$atts,
			'basix_calendar'
		);
		return basix_events_calendar_html( (string) $atts['view'], (string) $atts['source'], (string) $atts['category'] );
	}
);
