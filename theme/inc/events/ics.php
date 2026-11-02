<?php
/**
 * ICS export: an "Add to calendar" file for one event (?ics=1) and a subscribe feed of the coming events
 * (/events/feed.ics, optionally ?category=slug).
 *
 * The output follows RFC 5545: CRLF line ends, lines folded at 75 bytes, escaped text, a stable UID, SEQUENCE from the
 * modified time, all-day events with an exclusive end date, TZID with generated VTIMEZONE blocks (so Outlook accepts
 * it), and RRULE and EXDATE for repeating events. Password protected and private events are left out.
 *
 * @package basix
 */

/** Bump when the feed rewrite rule changes, so existing sites flush their rules once. */
const BASIX_ICS_REWRITE = 1;

/** The ICS weekday codes, Sunday first (like PHP's "w"). */
const BASIX_ICS_DAYS = array( 'SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA' );

/**
 * Escapes text for an ICS value.
 *
 * @param string $text Plain text.
 * @return string
 */
function basix_ics_escape( string $text ): string {
	return str_replace(
		array( '\\', ';', ',', "\r\n", "\r", "\n" ),
		array( '\\\\', '\\;', '\\,', '\\n', '\\n', '\\n' ),
		$text
	);
}

/**
 * Folds a line at 75 bytes, never inside a character. Each continuation starts with a space, which counts too.
 *
 * @param string $line One content line.
 * @return string
 */
function basix_ics_fold( string $line ): string {
	if ( strlen( $line ) <= 75 ) {
		return $line;
	}
	$out  = '';
	$size = 0;
	foreach ( preg_split( '//u', $line, -1, PREG_SPLIT_NO_EMPTY ) as $char ) {
		$bytes = strlen( $char );
		if ( $size + $bytes > 75 ) {
			$out .= "\r\n ";
			$size = 1;
		}
		$out  .= $char;
		$size += $bytes;
	}
	return $out;
}

/**
 * Joins content lines into a calendar text: folded, with CRLF after every line.
 *
 * @param string[] $lines Content lines.
 * @return string
 */
function basix_ics_text( array $lines ): string {
	return implode( "\r\n", array_map( 'basix_ics_fold', $lines ) ) . "\r\n";
}

/**
 * The offset of a time zone as ICS writes it, for example +0100.
 *
 * @param int $seconds Offset from UTC in seconds.
 * @return string
 */
function basix_ics_offset( int $seconds ): string {
	$sign    = $seconds < 0 ? '-' : '+';
	$seconds = abs( $seconds );
	return sprintf( '%s%02d%02d', $sign, intdiv( $seconds, 3600 ), intdiv( $seconds % 3600, 60 ) );
}

/**
 * A VTIMEZONE block for a time zone, with one STANDARD or DAYLIGHT part per change in a range of years.
 *
 * @param string $name Time zone name, for example Europe/Vienna.
 * @param int    $from First year.
 * @param int    $to   Last year.
 * @return string[] Content lines.
 */
function basix_ics_timezone( string $name, int $from, int $to ): array {
	$zone  = new DateTimeZone( $name );
	$start = gmmktime( 0, 0, 0, 1, 1, $from );
	$list  = $zone->getTransitions( $start, gmmktime( 23, 59, 59, 12, 31, $to ) );
	$lines = array( 'BEGIN:VTIMEZONE', 'TZID:' . $name );
	foreach ( $list as $index => $change ) {
		$before  = $index ? $list[ $index - 1 ]['offset'] : $change['offset'];
		$kind    = $change['isdst'] ? 'DAYLIGHT' : 'STANDARD';
		$lines[] = 'BEGIN:' . $kind;
		$lines[] = 'DTSTART:' . gmdate( 'Ymd\THis', $change['ts'] + $before );
		$lines[] = 'TZOFFSETFROM:' . basix_ics_offset( (int) $before );
		$lines[] = 'TZOFFSETTO:' . basix_ics_offset( (int) $change['offset'] );
		$lines[] = 'TZNAME:' . $change['abbr'];
		$lines[] = 'END:' . $kind;
	}
	$lines[] = 'END:VTIMEZONE';
	return $lines;
}

/**
 * The repeat rule as an RRULE value.
 *
 * @param array  $rule     A clean rule.
 * @param string $start    First date (Y-m-d).
 * @param string $until    The "until" date as an ICS value, ready to use, or an empty string.
 * @param int    $count    Number of times, or 0 for no count.
 * @return string
 */
function basix_ics_rrule( array $rule, string $start, string $until, int $count ): string {
	$stamp = strtotime( $start . ' UTC' );
	$day   = (int) gmdate( 'j', $stamp );
	$wd    = (int) gmdate( 'w', $stamp );
	$parts = array();
	switch ( $rule['freq'] ) {
		case 'daily':
			$parts[] = 'FREQ=DAILY';
			break;
		case 'weekly':
			$parts[] = 'FREQ=WEEKLY';
			break;
		case 'monthly':
		case 'monthly_weekday':
			$parts[] = 'FREQ=MONTHLY';
			break;
		default:
			$parts[] = 'FREQ=YEARLY';
	}
	if ( $rule['interval'] > 1 ) {
		$parts[] = 'INTERVAL=' . $rule['interval'];
	}
	if ( 'weekly' === $rule['freq'] ) {
		$codes = array();
		foreach ( $rule['weekdays'] ? $rule['weekdays'] : array( $wd ) as $number ) {
			$codes[] = BASIX_ICS_DAYS[ $number ];
		}
		$parts[] = 'BYDAY=' . implode( ',', $codes );
		$parts[] = 'WKST=MO';
	} elseif ( 'monthly' === $rule['freq'] ) {
		$parts[] = 'BYMONTHDAY=' . $day;
	} elseif ( 'monthly_weekday' === $rule['freq'] ) {
		$nth     = (int) ceil( $day / 7 );
		$parts[] = 'BYDAY=' . ( $nth > 4 ? -1 : $nth ) . BASIX_ICS_DAYS[ $wd ];
	} elseif ( 'yearly' === $rule['freq'] ) {
		$parts[] = 'BYMONTH=' . (int) gmdate( 'n', $stamp );
		$parts[] = 'BYMONTHDAY=' . $day;
	}
	if ( $until ) {
		$parts[] = 'UNTIL=' . $until;
	} elseif ( $count ) {
		$parts[] = 'COUNT=' . $count;
	}
	return implode( ';', $parts );
}

/**
 * The calendar lines of one event, as one or more VEVENTs.
 *
 * Almost always one. A weekly rule whose weekdays leave out the start date's own weekday needs two, because ICS only
 * counts dates the rule hits: the start date becomes its own event and the rule starts on its first date.
 *
 * @param WP_Post $event Event.
 * @param array   $zones Time zones used so far, name => [first year, last year]. Added to.
 * @return string[] Content lines.
 */
function basix_event_ics_lines( WP_Post $event, array &$zones ): array {
	$id    = $event->ID;
	$range = basix_event_range( $id );
	if ( ! $range ) {
		return array();
	}
	$rule     = basix_event_rule( $id );
	$all_day  = $range['all_day'];
	$zone     = $all_day ? wp_timezone() : basix_event_zone( $id );
	$name     = $zone->getName();
	$tzid     = ! $all_day && 'UTC' !== $name && ! preg_match( '/^[+-]/', $name ) ? $name : '';
	$utc      = new DateTimeZone( 'UTC' );
	$first    = substr( $range['start'], 0, 10 );
	$s_time   = substr( $range['start'], 11 );
	$e_time   = substr( $range['end'], 11 );
	$span     = (int) ( ( strtotime( substr( $range['end'], 0, 10 ) . ' UTC' ) - strtotime( $first . ' UTC' ) ) / DAY_IN_SECONDS );
	$host     = (string) wp_parse_url( home_url(), PHP_URL_HOST );
	$modified = (int) strtotime( $event->post_modified_gmt . ' UTC' );
	$stamp    = gmdate( 'Ymd\THis\Z', $modified );
	$terms    = get_the_terms( $event, BASIX_EVENT_CAT );
	$cats     = $terms && ! is_wp_error( $terms ) ? wp_list_pluck( $terms, 'name' ) : array();
	$summary  = html_entity_decode( get_the_title( $event ), ENT_QUOTES, 'UTF-8' );
	$text     = trim( wp_strip_all_tags( $event->post_excerpt ? $event->post_excerpt : strip_shortcodes( $event->post_content ) ) );
	$text     = wp_trim_words( $text, 60, '…' );
	$place    = (string) get_post_meta( $id, 'basix_event_location', true );

	// A wall clock time on a date, as a value with its parameter: DTSTART;TZID=...:20261105T183000.
	$moment = static function ( string $prop, string $date, string $time ) use ( $all_day, $zone, $tzid, $utc ): string {
		if ( $all_day ) {
			return $prop . ';VALUE=DATE:' . str_replace( '-', '', $date );
		}
		$local = new DateTimeImmutable( $date . 'T' . $time, $zone );
		if ( $tzid ) {
			return $prop . ';TZID=' . $tzid . ':' . $local->format( 'Ymd\THis' );
		}
		return $prop . ':' . $local->setTimezone( $utc )->format( 'Ymd\THis\Z' );
	};
	$add_days = static function ( string $date, int $days ): string {
		return gmdate( 'Y-m-d', strtotime( $date . ' UTC' ) + $days * DAY_IN_SECONDS );
	};

	// The runs of dates: normally one, starting on the start date.
	$runs   = array( array( $first, $rule, '' ) );
	$weekly = $rule && 'weekly' === $rule['freq'] && $rule['weekdays'] && ! in_array( (int) gmdate( 'w', strtotime( $first . ' UTC' ) ), $rule['weekdays'], true );
	if ( $weekly ) {
		$dates = array();
		foreach ( basix_event_rule_dates( $first, $rule ) as $date ) {
			$dates[] = $date;
			if ( count( $dates ) >= 2 ) {
				break;
			}
		}
		$runs = array( array( $first, array(), '-start' ) );
		if ( 2 === count( $dates ) ) {
			$runs[] = array( $dates[1], $rule, '' );
		}
	}

	$lines = array();
	foreach ( $runs as $run ) {
		list( $date, $run_rule, $suffix ) = $run;
		$lines[]                          = 'BEGIN:VEVENT';
		$lines[]                          = 'UID:basix-event-' . $id . $suffix . '@' . $host;
		$lines[]                          = 'DTSTAMP:' . $stamp;
		$lines[]                          = 'LAST-MODIFIED:' . $stamp;
		$lines[]                          = 'SEQUENCE:' . $modified;
		$lines[]                          = $moment( 'DTSTART', $date, $s_time );
		// An all-day event ends the day after its last day (the end date is exclusive).
		$lines[] = $all_day ? $moment( 'DTEND', $add_days( $date, $span + 1 ), '' ) : $moment( 'DTEND', $add_days( $date, $span ), $e_time );
		if ( $run_rule ) {
			$until = '';
			$count = 0;
			if ( 'until' === $run_rule['end']['type'] ) {
				$until = $all_day ? str_replace( '-', '', $run_rule['end']['until'] ) : ( new DateTimeImmutable( $run_rule['end']['until'] . 'T23:59:59', $zone ) )->setTimezone( $utc )->format( 'Ymd\THis\Z' );
			} elseif ( 'count' === $run_rule['end']['type'] ) {
				// The start date's own event is one of the times when it stands apart.
				$count = (int) $run_rule['end']['count'] - ( $weekly ? 1 : 0 );
			}
			$lines[] = 'RRULE:' . basix_ics_rrule( $run_rule, $date, $until, $count );
			foreach ( $run_rule['skip'] as $skipped ) {
				if ( $skipped >= $date ) {
					$lines[] = $moment( 'EXDATE', $skipped, $s_time );
				}
			}
		}
		$lines[] = 'SUMMARY:' . basix_ics_escape( $summary );
		if ( '' !== $text ) {
			$lines[] = 'DESCRIPTION:' . basix_ics_escape( $text );
		}
		if ( '' !== $place ) {
			$lines[] = 'LOCATION:' . basix_ics_escape( $place );
		}
		if ( $cats ) {
			$lines[] = 'CATEGORIES:' . implode( ',', array_map( 'basix_ics_escape', $cats ) );
		}
		$lines[] = 'URL:' . get_permalink( $event );
		$lines[] = 'END:VEVENT';
	}

	// Time zones: the years the event covers, up to where PHP knows the rules for a repeat with no end.
	if ( $tzid ) {
		$from = (int) substr( $first, 0, 4 ) - 1;
		$to   = (int) substr( $first, 0, 4 ) + 1;
		if ( $rule ) {
			$to = 'never' === $rule['end']['type'] ? 2037 : (int) substr( basix_event_last_utc( $id ), 0, 4 ) + 1;
		}
		$zones[ $tzid ] = isset( $zones[ $tzid ] ) ? array( min( $zones[ $tzid ][0], $from ), max( $zones[ $tzid ][1], $to, $from ) ) : array( $from, max( $to, $from ) );
	}
	return $lines;
}

/**
 * A whole calendar for some events.
 *
 * @param WP_Post[] $events Events.
 * @param string    $name   Calendar name.
 * @return string
 */
function basix_events_ics( array $events, string $name ): string {
	$zones  = array();
	$bodies = array();
	foreach ( $events as $event ) {
		$bodies[] = basix_event_ics_lines( $event, $zones );
	}
	$lines = array(
		'BEGIN:VCALENDAR',
		'VERSION:2.0',
		'PRODID:-//Basix//Events//EN',
		'CALSCALE:GREGORIAN',
		'METHOD:PUBLISH',
		'X-WR-CALNAME:' . basix_ics_escape( $name ),
	);
	ksort( $zones );
	foreach ( $zones as $zone_name => $years ) {
		$lines = array_merge( $lines, basix_ics_timezone( $zone_name, $years[0], $years[1] ) );
	}
	foreach ( $bodies as $body ) {
		$lines = array_merge( $lines, $body );
	}
	$lines[] = 'END:VCALENDAR';
	return basix_ics_text( $lines );
}

/**
 * The events that have not ended yet (or repeat further), soonest first. Password protected events are left out, and
 * only published events are listed, so private ones never are.
 *
 * @param string[] $categories Category slugs (all when empty).
 * @param int      $max        Most events.
 * @return WP_Post[]
 */
function basix_events_upcoming_posts( array $categories = array(), int $max = 500 ): array {
	$args = array(
		'post_type'           => BASIX_EVENT,
		'post_status'         => 'publish',
		'has_password'        => false,
		'posts_per_page'      => $max,
		'orderby'             => 'meta_value',
		'meta_key'            => '_basix_event_next_utc', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- a bounded list.
		'order'               => 'ASC',
		'ignore_sticky_posts' => true,
		'no_found_rows'       => true,
		'meta_query'          => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query -- same.
			array(
				'key'     => '_basix_event_last_utc',
				'value'   => gmdate( 'Y-m-d H:i:s' ),
				'compare' => '>=',
				'type'    => 'CHAR',
			),
		),
	);
	if ( $categories ) {
		$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the feed's category.
			array(
				'taxonomy' => BASIX_EVENT_CAT,
				'field'    => 'slug',
				'terms'    => $categories,
			),
		);
	}
	return get_posts( $args );
}

/** Makes the cached feeds out of date. */
function basix_events_feed_flush(): void {
	update_option( 'basix_events_feed_version', (int) get_option( 'basix_events_feed_version' ) + 1, false );
}

/**
 * The feed text for some categories, cached until an event changes (or an hour passes, since "coming" moves on).
 *
 * @param string[] $categories Category slugs.
 * @return string
 */
function basix_events_feed_text( array $categories ): string {
	$key    = 'basix_ics_' . md5( (int) get_option( 'basix_events_feed_version' ) . '|' . implode( ',', $categories ) . '|' . get_locale() );
	$cached = get_transient( $key );
	if ( is_string( $cached ) ) {
		return $cached;
	}
	/* translators: %s: the site name. */
	$text = basix_events_ics( basix_events_upcoming_posts( $categories ), sprintf( __( '%s – events', 'basix' ), wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ) ) );
	set_transient( $key, $text, HOUR_IN_SECONDS );
	return $text;
}

// Anything that changes what the feed says empties its cache.
foreach ( array( 'save_post_' . BASIX_EVENT, 'delete_post', 'wp_trash_post', 'untrashed_post' ) as $basix_feed_hook ) {
	add_action( $basix_feed_hook, 'basix_events_feed_flush' );
}
unset( $basix_feed_hook );
foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $basix_feed_hook ) {
	add_action(
		$basix_feed_hook,
		function ( $meta_id, $post_id, $key ) {
			if ( array_key_exists( $key, basix_event_fields() ) && BASIX_EVENT === get_post_type( $post_id ) ) {
				basix_events_feed_flush();
			}
		},
		10,
		3
	);
}
unset( $basix_feed_hook );
add_action(
	'set_object_terms',
	function ( $object_id, $terms, $tt_ids, $taxonomy ) {
		if ( BASIX_EVENT_CAT === $taxonomy ) {
			basix_events_feed_flush();
		}
	},
	10,
	4
);
add_action( 'edited_' . BASIX_EVENT_CAT, 'basix_events_feed_flush' );
add_action( 'delete_' . BASIX_EVENT_CAT, 'basix_events_feed_flush' );
add_action( 'basix_events_refresh', 'basix_events_feed_flush' );
add_action( 'update_option_timezone_string', 'basix_events_feed_flush' );
add_action( 'update_option_gmt_offset', 'basix_events_feed_flush' );

// /events/feed.ics, and ?ics=1 on an event.
add_action(
	'init',
	function () {
		add_rewrite_rule( '^events/feed\.ics/?$', 'index.php?basix_events_feed=1', 'top' );
		if ( (int) get_option( 'basix_ics_rewrite' ) !== BASIX_ICS_REWRITE ) {
			update_option( 'basix_ics_rewrite', BASIX_ICS_REWRITE, false );
			flush_rewrite_rules();
		}
	}
);
add_filter(
	'query_vars',
	function ( array $vars ): array {
		$vars[] = 'basix_events_feed';
		$vars[] = 'ics';
		return $vars;
	}
);

/**
 * The address of the subscribe feed.
 *
 * @param string $category Event category slug, or empty for every event.
 * @param bool   $webcal   Use the webcal:// scheme, which opens the calendar app.
 * @return string
 */
function basix_events_feed_url( string $category = '', bool $webcal = false ): string {
	$url = get_option( 'permalink_structure' ) ? home_url( '/events/feed.ics' ) : add_query_arg( 'basix_events_feed', '1', home_url( '/' ) );
	if ( $category ) {
		$url = add_query_arg( 'category', $category, $url );
	}
	return $webcal ? (string) preg_replace( '#^https?:#', 'webcal:', $url ) : $url;
}

/**
 * The address of one event's calendar file.
 *
 * @param int $post_id Event id.
 * @return string
 */
function basix_event_ics_url( int $post_id ): string {
	return add_query_arg( 'ics', '1', get_permalink( $post_id ) );
}

/**
 * Sends a calendar file and stops. Answers 304 when the visitor already has this version.
 *
 * @param string $text       Calendar text.
 * @param string $filename   File name.
 * @param bool   $attachment Offer it as a download instead of showing it.
 */
function basix_ics_send( string $text, string $filename, bool $attachment ): void {
	$etag = '"' . md5( $text ) . '"';
	header( 'ETag: ' . $etag );
	header( 'Cache-Control: public, max-age=900' );
	header( 'X-Robots-Tag: noindex' );
	if ( isset( $_SERVER['HTTP_IF_NONE_MATCH'] ) && trim( sanitize_text_field( wp_unslash( $_SERVER['HTTP_IF_NONE_MATCH'] ) ) ) === $etag ) {
		status_header( 304 );
		exit;
	}
	header( 'Content-Type: text/calendar; charset=utf-8' );
	header( 'Content-Disposition: ' . ( $attachment ? 'attachment' : 'inline' ) . '; filename="' . sanitize_file_name( $filename ) . '"' );
	echo $text; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- a calendar file, escaped by the ics functions.
	exit;
}

add_action(
	'template_redirect',
	function () {
		if ( get_query_var( 'basix_events_feed' ) ) {
			// A plain read-only list filter; only slugs of existing categories are used.
			$category = isset( $_GET['category'] ) ? sanitize_text_field( wp_unslash( $_GET['category'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			basix_ics_send( basix_events_feed_text( basix_event_category_slugs( $category ) ), 'events.ics', false );
		}
		if ( is_singular( BASIX_EVENT ) && get_query_var( 'ics' ) ) {
			$event = get_queried_object();
			// A protected or unpublished event has no file, as it has no public page.
			if ( ! $event instanceof WP_Post || 'publish' !== $event->post_status || post_password_required( $event ) ) {
				global $wp_query;
				$wp_query->set_404();
				status_header( 404 );
				nocache_headers();
				return;
			}
			basix_ics_send( basix_events_ics( array( $event ), html_entity_decode( get_the_title( $event ), ENT_QUOTES, 'UTF-8' ) ), $event->post_name . '.ics', true );
		}
	},
	1
);
