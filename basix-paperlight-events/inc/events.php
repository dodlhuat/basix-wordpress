<?php
/**
 * Events: a post type with a start and end (date, optional time) and a location, shown on the calendar.
 *
 * Dates are stored as plain local text (Y-m-d and H:i) in the site's own time zone, so what an editor types is
 * what the calendar shows. A missing time means an all-day event.
 *
 * @package basix-paperlight-events
 */

// The theme defines the same two names for its templates, so that they also work while this plugin is off.
if ( ! defined( 'BASIX_EVENT' ) ) {
	define( 'BASIX_EVENT', 'basix-event' );
}
if ( ! defined( 'BASIX_EVENT_CAT' ) ) {
	define( 'BASIX_EVENT_CAT', 'basix-event-category' );
}

/**
 * Whether a value is a real date written as Y-m-d.
 *
 * @param mixed $value Value to check.
 * @return bool
 */
function basix_events_is_date( $value ): bool {
	if ( ! is_string( $value ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
		return false;
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

/** The event fields saved with each event: meta key => label. */
function basix_event_fields(): array {
	return array(
		'basix_event_start'      => __( 'Starts on', 'basix-paperlight-events' ),
		'basix_event_start_time' => __( 'Start time', 'basix-paperlight-events' ),
		'basix_event_end'        => __( 'Ends on', 'basix-paperlight-events' ),
		'basix_event_end_time'   => __( 'End time', 'basix-paperlight-events' ),
		'basix_event_timezone'   => __( 'Time zone', 'basix-paperlight-events' ),
		'basix_event_location'   => __( 'Location', 'basix-paperlight-events' ),
		'basix_event_repeat'     => __( 'Repeat', 'basix-paperlight-events' ),
	);
}

/**
 * Cleans an event field: dates and times must be valid, a location is plain text.
 *
 * @param string $key   Meta key.
 * @param mixed  $value Raw value.
 * @return string
 */
function basix_event_sanitize( string $key, $value ): string {
	if ( 'basix_event_repeat' === $key ) {
		$rule = basix_event_rule_clean( $value );
		return $rule ? (string) wp_json_encode( $rule ) : '';
	}
	$value = is_string( $value ) ? trim( $value ) : '';
	if ( 'basix_event_start' === $key || 'basix_event_end' === $key ) {
		return basix_events_is_date( $value ) ? $value : '';
	}
	if ( 'basix_event_start_time' === $key || 'basix_event_end_time' === $key ) {
		return preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $value ) ? $value : '';
	}
	if ( 'basix_event_timezone' === $key ) {
		return in_array( $value, timezone_identifiers_list(), true ) ? $value : '';
	}
	return sanitize_text_field( $value );
}

add_action(
	'init',
	function () {
		register_post_type(
			BASIX_EVENT,
			array(
				'labels'              => array(
					'name'               => __( 'Events', 'basix-paperlight-events' ),
					'singular_name'      => __( 'Event', 'basix-paperlight-events' ),
					'add_new'            => __( 'Add new', 'basix-paperlight-events' ),
					'add_new_item'       => __( 'Add new event', 'basix-paperlight-events' ),
					'edit_item'          => __( 'Edit event', 'basix-paperlight-events' ),
					'new_item'           => __( 'New event', 'basix-paperlight-events' ),
					'view_item'          => __( 'View event', 'basix-paperlight-events' ),
					'search_items'       => __( 'Search events', 'basix-paperlight-events' ),
					'not_found'          => __( 'No events found', 'basix-paperlight-events' ),
					'not_found_in_trash' => __( 'No events found in the bin', 'basix-paperlight-events' ),
					'all_items'          => __( 'All events', 'basix-paperlight-events' ),
					'menu_name'          => __( 'Events', 'basix-paperlight-events' ),
				),
				'public'              => true,
				'has_archive'         => 'events',
				'rewrite'             => array( 'slug' => 'event' ),
				'menu_icon'           => 'dashicons-calendar-alt',
				'menu_position'       => 5,
				'show_in_rest'        => true,
				'exclude_from_search' => false,
				'supports'            => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
			)
		);
		register_taxonomy(
			BASIX_EVENT_CAT,
			BASIX_EVENT,
			array(
				'labels'            => array(
					'name'          => __( 'Event categories', 'basix-paperlight-events' ),
					'singular_name' => __( 'Event category', 'basix-paperlight-events' ),
					'search_items'  => __( 'Search event categories', 'basix-paperlight-events' ),
					'all_items'     => __( 'All event categories', 'basix-paperlight-events' ),
					'edit_item'     => __( 'Edit event category', 'basix-paperlight-events' ),
					'update_item'   => __( 'Update event category', 'basix-paperlight-events' ),
					'add_new_item'  => __( 'Add new event category', 'basix-paperlight-events' ),
					'new_item_name' => __( 'New event category name', 'basix-paperlight-events' ),
					'menu_name'     => __( 'Categories', 'basix-paperlight-events' ),
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_in_rest'      => true,
				'show_admin_column' => true,
				'rewrite'           => array( 'slug' => 'events/category' ),
			)
		);
		foreach ( array_keys( basix_event_fields() ) as $key ) {
			register_post_meta(
				BASIX_EVENT,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => static function ( $value ) use ( $key ) {
						return basix_event_sanitize( $key, $value );
					},
					'auth_callback'     => static function () {
						return current_user_can( 'edit_posts' );
					},
				)
			);
		}
	}
);

// Event addresses are flushed once after the plugin is switched on (see basix-paperlight-events.php), when the post type exists.
add_action(
	'init',
	function () {
		if ( get_option( 'basix_flush_rewrite' ) ) {
			delete_option( 'basix_flush_rewrite' );
			flush_rewrite_rules();
		}
	},
	99
);

// The "Event details" box under the editor.
add_action(
	'add_meta_boxes',
	function () {
		add_meta_box( 'basix_event_details', __( 'Event details', 'basix-paperlight-events' ), 'basix_event_meta_box', BASIX_EVENT, 'normal', 'high' );
	}
);

/**
 * Prints the event fields.
 *
 * @param WP_Post $post The event.
 */
function basix_event_meta_box( WP_Post $post ): void {
	wp_nonce_field( 'basix_event_save', 'basix_event_nonce' );
	$value = static function ( string $key ) use ( $post ): string {
		return (string) get_post_meta( $post->ID, $key, true );
	};
	?>
	<style>.basix-event-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(11rem,1fr));gap:1rem}.basix-event-grid label{display:block;font-weight:600;margin-bottom:.25rem}.basix-event-grid input{width:100%}.basix-event-wide{grid-column:1/-1}</style>
	<div class="basix-event-grid">
		<div>
			<label for="basix_event_start"><?php esc_html_e( 'Starts on', 'basix-paperlight-events' ); ?></label>
			<input type="date" id="basix_event_start" name="basix_event_start" value="<?php echo esc_attr( $value( 'basix_event_start' ) ); ?>" required>
		</div>
		<div>
			<label for="basix_event_start_time"><?php esc_html_e( 'Start time', 'basix-paperlight-events' ); ?></label>
			<input type="time" id="basix_event_start_time" name="basix_event_start_time" value="<?php echo esc_attr( $value( 'basix_event_start_time' ) ); ?>">
		</div>
		<div>
			<label for="basix_event_end"><?php esc_html_e( 'Ends on', 'basix-paperlight-events' ); ?></label>
			<input type="date" id="basix_event_end" name="basix_event_end" value="<?php echo esc_attr( $value( 'basix_event_end' ) ); ?>">
		</div>
		<div>
			<label for="basix_event_end_time"><?php esc_html_e( 'End time', 'basix-paperlight-events' ); ?></label>
			<input type="time" id="basix_event_end_time" name="basix_event_end_time" value="<?php echo esc_attr( $value( 'basix_event_end_time' ) ); ?>">
		</div>
		<div class="basix-event-wide">
			<label for="basix_event_timezone"><?php esc_html_e( 'Time zone', 'basix-paperlight-events' ); ?></label>
			<select id="basix_event_timezone" name="basix_event_timezone">
				<?php /* translators: %s: the site's time zone, for example Europe/Vienna. */ ?>
				<option value=""><?php echo esc_html( sprintf( __( 'Site time zone (%s)', 'basix-paperlight-events' ), wp_timezone_string() ) ); ?></option>
				<?php foreach ( timezone_identifiers_list() as $basix_zone ) : ?>
					<option value="<?php echo esc_attr( $basix_zone ); ?>"<?php selected( $value( 'basix_event_timezone' ), $basix_zone ); ?>><?php echo esc_html( str_replace( '_', ' ', $basix_zone ) ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="basix-event-wide">
			<label for="basix_event_location"><?php esc_html_e( 'Location', 'basix-paperlight-events' ); ?></label>
			<input type="text" id="basix_event_location" name="basix_event_location" value="<?php echo esc_attr( $value( 'basix_event_location' ) ); ?>">
		</div>
	</div>
	<?php basix_event_repeat_fields( $post ); ?>
	<p class="description"><?php esc_html_e( 'Leave the times empty for an all-day event. Leave the end date empty for an event on one day. The time zone applies to timed events; all-day events follow the site.', 'basix-paperlight-events' ); ?></p>
	<?php
}

add_action(
	'save_post_' . BASIX_EVENT,
	function ( int $post_id ) {
		if ( ! isset( $_POST['basix_event_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['basix_event_nonce'] ) ), 'basix_event_save' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		$clean = array();
		foreach ( array_keys( basix_event_fields() ) as $key ) {
			if ( 'basix_event_repeat' === $key ) {
				$clean[ $key ] = basix_event_sanitize( $key, basix_event_rule_from_form( isset( $_POST['basix_repeat'] ) ? wp_unslash( $_POST['basix_repeat'] ) : array() ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cleaned by basix_event_rule_clean().
				continue;
			}
			$clean[ $key ] = basix_event_sanitize( $key, isset( $_POST[ $key ] ) ? wp_unslash( $_POST[ $key ] ) : '' ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitised by basix_event_sanitize().
		}
		// An event ends on the day it starts unless it says otherwise, and never before it starts.
		if ( '' === $clean['basix_event_end'] || $clean['basix_event_end'] < $clean['basix_event_start'] ) {
			$clean['basix_event_end'] = $clean['basix_event_start'];
		}
		if ( $clean['basix_event_end'] === $clean['basix_event_start'] && $clean['basix_event_end_time'] && $clean['basix_event_end_time'] < $clean['basix_event_start_time'] ) {
			$clean['basix_event_end_time'] = '';
		}
		foreach ( $clean as $key => $value ) {
			update_post_meta( $post_id, $key, $value );
		}
	}
);

/**
 * The time zone an event's times are in: its own, or the site's.
 *
 * @param int $post_id Event id.
 * @return DateTimeZone
 */
function basix_event_zone( int $post_id ): DateTimeZone {
	$name = (string) get_post_meta( $post_id, 'basix_event_timezone', true );
	try {
		return $name ? new DateTimeZone( $name ) : wp_timezone();
	} catch ( Exception $e ) {
		return wp_timezone();
	}
}

/**
 * When an event happens, in the site's time zone (what the calendar grid shows).
 * All-day events are not converted; timed events move into the site's zone.
 *
 * @param int $post_id Event id.
 * @return array{start: string, end: string, all_day: bool}|null
 */
function basix_event_range_site( int $post_id ): ?array {
	$range = basix_event_range( $post_id );
	if ( ! $range || $range['all_day'] ) {
		return $range;
	}
	$zone = basix_event_zone( $post_id );
	$site = wp_timezone();
	return array(
		'start'   => ( new DateTimeImmutable( $range['start'], $zone ) )->setTimezone( $site )->format( 'Y-m-d\TH:i' ),
		'end'     => ( new DateTimeImmutable( $range['end'], $zone ) )->setTimezone( $site )->format( 'Y-m-d\TH:i' ),
		'all_day' => false,
	);
}

/**
 * Writes the start and end as UTC moments (hidden fields), which queries and sorting use. All-day events
 * count as the whole day in the site's time zone.
 *
 * @param int $post_id Event id.
 */
function basix_event_update_derived( int $post_id ): void {
	$range = basix_event_range( $post_id );
	if ( ! $range ) {
		foreach ( array( 'start', 'end', 'last', 'next' ) as $part ) {
			delete_post_meta( $post_id, '_basix_event_' . $part . '_utc' );
		}
		return;
	}
	$zone  = $range['all_day'] ? wp_timezone() : basix_event_zone( $post_id );
	$utc   = new DateTimeZone( 'UTC' );
	$start = new DateTimeImmutable( $range['start'], $zone );
	$end   = new DateTimeImmutable( $range['end'], $zone );
	if ( $range['all_day'] ) {
		$end = $end->setTime( 23, 59, 59 );
	}
	update_post_meta( $post_id, '_basix_event_start_utc', $start->setTimezone( $utc )->format( 'Y-m-d H:i:s' ) );
	update_post_meta( $post_id, '_basix_event_end_utc', $end->setTimezone( $utc )->format( 'Y-m-d H:i:s' ) );
	// The end of the last occurrence (far away for a rule that never ends) and the start of the next one.
	update_post_meta( $post_id, '_basix_event_last_utc', basix_event_last_utc( $post_id ) );
	update_post_meta( $post_id, '_basix_event_next_utc', basix_event_next_utc( $post_id ) );
}

// Keep the hidden fields right whenever an event field changes (the editor, the REST API, WP-CLI).
add_action(
	'added_post_meta',
	'basix_event_meta_changed',
	10,
	3
);
add_action(
	'updated_post_meta',
	'basix_event_meta_changed',
	10,
	3
);
add_action(
	'deleted_post_meta',
	'basix_event_meta_changed',
	10,
	3
);

/**
 * Recomputes the hidden fields when one of the event fields changes.
 *
 * @param mixed  $meta_id Meta id (or ids).
 * @param int    $post_id Post id.
 * @param string $key     Meta key.
 */
function basix_event_meta_changed( $meta_id, $post_id, $key ): void {
	if ( array_key_exists( $key, basix_event_fields() ) && BASIX_EVENT === get_post_type( $post_id ) ) {
		basix_event_update_derived( (int) $post_id );
	}
}

/** Bump this when the hidden fields change, so existing events get them again. */
const BASIX_EVENTS_DERIVED = 3;

/** Recomputes the hidden fields of every event. */
function basix_events_rebuild_derived(): void {
	foreach ( get_posts(
		array(
			'post_type'      => BASIX_EVENT,
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
		)
	) as $id ) {
		basix_event_update_derived( (int) $id );
	}
	update_option( 'basix_events_derived', BASIX_EVENTS_DERIVED, false );
}

// Existing events get the hidden fields once, and again when the site's time zone changes.
add_action(
	'init',
	function () {
		if ( (int) get_option( 'basix_events_derived' ) !== BASIX_EVENTS_DERIVED ) {
			basix_events_rebuild_derived();
		}
	},
	98
);
foreach ( array( 'timezone_string', 'gmt_offset' ) as $basix_tz_option ) {
	add_action(
		'update_option_' . $basix_tz_option,
		function () {
			delete_option( 'basix_events_derived' );
		}
	);
}
unset( $basix_tz_option );

/**
 * When an event happens, as the calendar needs it.
 *
 * @param int $post_id Event id.
 * @return array{start: string, end: string, all_day: bool}|null Local date-times (Y-m-dTH:i), or null without a start date.
 */
function basix_event_range( int $post_id ): ?array {
	$start_date = (string) get_post_meta( $post_id, 'basix_event_start', true );
	if ( '' === $start_date ) {
		return null;
	}
	$end_date   = (string) get_post_meta( $post_id, 'basix_event_end', true );
	$end_date   = '' === $end_date ? $start_date : $end_date;
	$start_time = (string) get_post_meta( $post_id, 'basix_event_start_time', true );
	$end_time   = (string) get_post_meta( $post_id, 'basix_event_end_time', true );

	if ( '' === $start_time ) {
		return array(
			'start'   => $start_date . 'T00:00',
			'end'     => $end_date . 'T23:59',
			'all_day' => true,
		);
	}
	if ( '' === $end_time ) {
		// No end time: show a one hour slot, but stay inside the day.
		$end_time = $end_date === $start_date ? min( '23:59', gmdate( 'H:i', strtotime( '1970-01-01 ' . $start_time . ' UTC' ) + HOUR_IN_SECONDS ) ) : $start_time;
		if ( $end_time < $start_time && $end_date === $start_date ) {
			$end_time = '23:59';
		}
	}
	return array(
		'start'   => $start_date . 'T' . $start_time,
		'end'     => $end_date . 'T' . $end_time,
		'all_day' => false,
	);
}

/**
 * The time of an event as readable text, for example "Nov 5, 2026, 6:30 pm – 8:00 pm" or "Nov 5 – Nov 7, 2026".
 *
 * @param int  $post_id      Event id, defaults to the current post.
 * @param bool $in_site_zone Show the time in the site's time zone instead of the event's own.
 * @param array|null $occurrence One date of a repeating event (see basix_event_occurrences()); the event's first date when empty.
 * @return string
 */
function basix_event_when( int $post_id = 0, bool $in_site_zone = false, ?array $occurrence = null ): string {
	$post_id = $post_id ? $post_id : (int) get_the_ID();
	$range   = $in_site_zone ? basix_event_range_site( $post_id ) : basix_event_range( $post_id );
	if ( $range && $occurrence ) {
		$range['start'] = $in_site_zone ? $occurrence['start'] : $occurrence['own_start'];
		$range['end']   = $in_site_zone ? $occurrence['end'] : $occurrence['own_end'];
	}
	if ( ! $range ) {
		return '';
	}
	$zone   = $in_site_zone ? wp_timezone() : basix_event_zone( $post_id );
	$start  = new DateTimeImmutable( $range['start'], $zone );
	$end    = new DateTimeImmutable( $range['end'], $zone );
	$date   = get_option( 'date_format' );
	$time   = get_option( 'time_format' );
	$same   = $start->format( 'Y-m-d' ) === $end->format( 'Y-m-d' );
	$format = static function ( DateTimeImmutable $moment, string $pattern ) use ( $zone ): string {
		return wp_date( $pattern, $moment->getTimestamp(), $zone );
	};

	if ( $range['all_day'] ) {
		return $same ? $format( $start, $date ) : $format( $start, $date ) . ' – ' . $format( $end, $date );
	}
	// An event in another time zone than the site's says which one.
	$label = $zone->getName() !== wp_timezone()->getName() ? ' ' . $format( $end, 'T' ) : '';
	if ( $same ) {
		return $format( $start, $date ) . ', ' . $format( $start, $time ) . ' – ' . $format( $end, $time ) . $label;
	}
	return $format( $start, $date . ' ' . $time ) . ' – ' . $format( $end, $date . ' ' . $time ) . $label;
}

/**
 * Category slugs from a comma separated list, keeping only those that exist.
 *
 * @param string $csv  For example "meetups,workshops".
 * @return string[]
 */
function basix_event_category_slugs( string $csv ): array {
	$slugs = array();
	foreach ( explode( ',', $csv ) as $slug ) {
		$slug = sanitize_title( $slug );
		if ( $slug && term_exists( $slug, BASIX_EVENT_CAT ) ) {
			$slugs[] = $slug;
		}
	}
	return array_values( array_unique( $slugs ) );
}

/**
 * The event categories that have at least one event, for the calendar's chips.
 *
 * @return array<int, array{slug: string, name: string}>
 */
function basix_event_category_list(): array {
	$out = array();
	foreach ( get_terms( array( 'taxonomy' => BASIX_EVENT_CAT ) ) as $term ) {
		if ( $term instanceof WP_Term ) {
			$out[] = array(
				'slug' => $term->slug,
				'name' => $term->name,
			);
		}
	}
	return $out;
}

/**
 * The occurrences that touch a date range, soonest first. An event that does not repeat is one occurrence; a
 * recurring one gives each of its dates in the range (see basix_event_occurrences(), which adds the times).
 *
 * @param string   $from       First day (Y-m-d).
 * @param string   $to         Last day (Y-m-d).
 * @param int      $max        Most events to look at.
 * @param string[] $categories Category slugs; only events in one of them (all when empty).
 * @return array<int, array{event: WP_Post, id: string, date: string, start: string, end: string, all_day: bool, own_start: string, own_end: string}>
 */
function basix_event_occurrences_between( string $from, string $to, int $max = 300, array $categories = array() ): array {
	// The days are the site's; events are compared as moments in UTC.
	$utc         = new DateTimeZone( 'UTC' );
	$window_from = ( new DateTimeImmutable( $from . ' 00:00:00', wp_timezone() ) )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
	$window_to   = ( new DateTimeImmutable( $to . ' 23:59:59', wp_timezone() ) )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
	$args        = array(
		'post_type'           => BASIX_EVENT,
		'post_status'         => 'publish',
		'has_password'        => false,
		'posts_per_page'      => $max,
		'orderby'             => 'meta_value',
		'meta_key'            => '_basix_event_start_utc', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- the events list is small and bounded.
		'order'               => 'ASC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- small, bounded query.
			'relation' => 'AND',
			array(
				'key'     => '_basix_event_start_utc',
				'value'   => $window_to,
				'compare' => '<=',
				'type'    => 'CHAR',
			),
			// The end of the last occurrence: a recurring event is only left out once its rule has run out.
			array(
				'key'     => '_basix_event_last_utc',
				'value'   => $window_from,
				'compare' => '>=',
				'type'    => 'CHAR',
			),
		),
	);
	if ( $categories ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the visitor's own category filter.
			array(
				'taxonomy' => BASIX_EVENT_CAT,
				'field'    => 'slug',
				'terms'    => $categories,
			),
		);
	}
	$out = array();
	foreach ( get_posts( $args ) as $event ) {
		foreach ( basix_event_occurrences( $event, $from, $to ) as $occurrence ) {
			$out[] = array_merge( array( 'event' => $event ), $occurrence );
		}
	}
	usort(
		$out,
		static function ( $a, $b ) {
			return array( $a['start'], $a['id'] ) <=> array( $b['start'], $b['id'] );
		}
	);
	return $out;
}

/**
 * The events that have an occurrence in a date range (each event once, even when it repeats).
 *
 * @param string   $from       First day (Y-m-d).
 * @param string   $to         Last day (Y-m-d).
 * @param int      $max        Most events to look at.
 * @param string[] $categories Category slugs; only events in one of them (all when empty).
 * @return WP_Post[]
 */
function basix_events_between( string $from, string $to, int $max = 300, array $categories = array() ): array {
	$out = array();
	foreach ( basix_event_occurrences_between( $from, $to, $max, $categories ) as $occurrence ) {
		$out[ $occurrence['event']->ID ] = $occurrence['event'];
	}
	return array_values( $out );
}

// GET /wp-json/basix/v1/events?from=2026-11-01&to=2026-11-30&category=meetups, the public list the calendar reads.
add_action(
	'rest_api_init',
	function () {
		$date_arg = array(
			'required'          => true,
			'validate_callback' => static function ( $value ) {
				return basix_events_is_date( $value );
			},
		);
		register_rest_route(
			'basix/v1',
			'/events',
			array(
				'methods'             => 'GET',
				'permission_callback' => '__return_true',
				'args'                => array(
					'from'     => $date_arg,
					'to'       => $date_arg,
					'category' => array(
						'required'          => false,
						'default'           => '',
						'sanitize_callback' => 'sanitize_text_field',
					),
				),
				'callback'            => static function ( WP_REST_Request $request ) {
					$out = array();
					foreach ( basix_event_occurrences_between( $request['from'], $request['to'], 300, basix_event_category_slugs( (string) $request['category'] ) ) as $item ) {
						$event = $item['event'];
						$rule  = basix_event_rule( $event->ID );
						$out[] = array(
							'id'         => $rule ? $item['id'] : $event->ID,
							'title'      => html_entity_decode( get_the_title( $event ), ENT_QUOTES, 'UTF-8' ),
							'link'       => $rule ? add_query_arg( 'date', $item['date'], get_permalink( $event ) ) : get_permalink( $event ),
							'start'      => $item['start'],
							'end'        => $item['end'],
							'allDay'     => $item['all_day'],
							'timezone'   => basix_event_zone( $event->ID )->getName(),
							'location'   => (string) get_post_meta( $event->ID, 'basix_event_location', true ),
							'categories' => wp_list_pluck( (array) get_the_terms( $event, BASIX_EVENT_CAT ), 'slug' ),
							'repeats'    => (bool) $rule,
							'startUtc'   => $item['all_day'] ? null : ( new DateTimeImmutable( $item['own_start'], basix_event_zone( $event->ID ) ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ),
							'endUtc'     => $item['all_day'] ? null : ( new DateTimeImmutable( $item['own_end'], basix_event_zone( $event->ID ) ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d\TH:i:s\Z' ),
						);
					}
					return rest_ensure_response( $out );
				},
			)
		);
	}
);

// /events/?event_day=2026-11-05 lists what happens on that day, recurring events included.
add_filter(
	'query_vars',
	function ( array $vars ): array {
		$vars[] = 'event_day';
		return $vars;
	}
);

/**
 * The day asked for on the events archive (Y-m-d), or an empty string.
 *
 * @return string
 */
function basix_event_day(): string {
	if ( ! is_post_type_archive( BASIX_EVENT ) ) {
		return '';
	}
	$day = (string) get_query_var( 'event_day' );
	return basix_events_is_date( $day ) ? $day : '';
}

/**
 * The address of the events list for one day.
 *
 * @param string $day Day (Y-m-d).
 * @return string
 */
function basix_event_day_url( string $day ): string {
	return add_query_arg( 'event_day', $day, get_post_type_archive_link( BASIX_EVENT ) );
}

// The day list is not something for search engines to crawl day by day.
add_filter(
	'wp_robots',
	function ( array $robots ): array {
		if ( basix_event_day() ) {
			$robots['noindex'] = true;
			$robots['follow']  = true;
		}
		return $robots;
	}
);

add_filter(
	'get_the_archive_title',
	function ( string $title ): string {
		$day = basix_event_day();
		if ( ! $day ) {
			return $title;
		}
		/* translators: %s: a date, for example November 5, 2026. */
		return sprintf( __( 'Events on %s', 'basix-paperlight-events' ), wp_date( get_option( 'date_format' ), strtotime( $day . ' 12:00:00 UTC' ), new DateTimeZone( 'UTC' ) ) );
	}
);

// The events archive and its categories list what is still to come (or running now), soonest first. A repeating
// event appears once, by its next date.
add_action(
	'pre_get_posts',
	function ( WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_post_type_archive( BASIX_EVENT ) || $query->is_tax( BASIX_EVENT_CAT ) ) ) {
			return;
		}
		$day = $query->is_post_type_archive( BASIX_EVENT ) ? (string) $query->get( 'event_day' ) : '';
		if ( basix_events_is_date( $day ) ) {
			// One day: the events with a date on it (a repeating event once), in the order they start.
			$ids = array();
			foreach ( basix_event_occurrences_between( $day, $day ) as $occurrence ) {
				$ids[ $occurrence['event']->ID ] = $occurrence['event']->ID;
			}
			$query->set( 'post__in', $ids ? array_values( $ids ) : array( 0 ) );
			$query->set( 'orderby', 'post__in' );
			$query->set( 'nopaging', true );
			return;
		}
		$query->set(
			'meta_query', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- the events list is small.
			array(
				'start_clause' => array(
					'key'     => '_basix_event_next_utc',
					'type'    => 'CHAR',
					'compare' => 'EXISTS',
				),
				'end_clause'   => array(
					'key'     => '_basix_event_last_utc',
					'value'   => gmdate( 'Y-m-d H:i:s' ),
					'compare' => '>=',
					'type'    => 'CHAR',
				),
			)
		);
		$query->set(
			'orderby',
			array(
				'start_clause' => 'ASC',
				'title'        => 'ASC',
			)
		);
	}
);

/**
 * Prints the event's categories as badges (links to the category's list).
 */
function basix_event_terms(): void {
	$terms = get_the_terms( get_the_ID(), BASIX_EVENT_CAT );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return;
	}
	echo '<p class="post-terms">';
	foreach ( $terms as $term ) {
		printf( '<a class="badge badge-info" href="%s">%s</a> ', esc_url( get_term_link( $term ) ), esc_html( $term->name ) );
	}
	echo '</p>';
}
