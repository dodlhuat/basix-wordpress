<?php
/**
 * Recurring events: a repeat rule stored on the event, expanded into dates when it is read.
 *
 * The rule is a small subset of the iCalendar rule (RRULE), so it can be exported unchanged. Occurrences are not
 * saved as posts. They are computed in the event's own time zone, so 6:30 pm stays 6:30 pm across daylight saving.
 *
 * Rule (JSON in the `basix_event_repeat` field):
 *   freq      daily | weekly | monthly | monthly_weekday | yearly
 *   interval  every N days, weeks, months or years (1 to 99)
 *   weekdays  for weekly: 0 (Sunday) to 6 (Saturday); empty means the weekday of the start date
 *   end       never | until (with `until`, Y-m-d, inclusive) | count (with `count`, 1 to 500)
 *   skip      dates (Y-m-d) that do not happen
 *
 * "monthly" repeats on the same day of the month and skips months that do not have it (the 31st). "monthly_weekday"
 * repeats on the same weekday of the month ("second Tuesday"; a fifth weekday means the last one). A yearly
 * February 29 only happens in leap years. This is what iCalendar does too.
 *
 * @package basix-events
 */

/** Most occurrences returned for one event and range, so a bad rule cannot loop forever. */
const BASIX_EVENT_MAX_OCCURRENCES = 500;

/** Most dates looked at while expanding one rule. */
const BASIX_EVENT_MAX_STEPS = 100000;

/** The repeat frequencies, as value => label. */
function basix_event_repeat_freqs(): array {
	return array(
		'daily'           => __( 'Every day', 'basix-events' ),
		'weekly'          => __( 'Every week', 'basix-events' ),
		'monthly'         => __( 'Every month, on the same date', 'basix-events' ),
		'monthly_weekday' => __( 'Every month, on the same weekday', 'basix-events' ),
		'yearly'          => __( 'Every year', 'basix-events' ),
	);
}

/**
 * Turns a rule (an array, or JSON text) into a clean one. Anything that is not a usable rule gives an empty array,
 * which means the event does not repeat.
 *
 * @param mixed $raw Rule as an array or JSON.
 * @return array{freq?: string, interval?: int, weekdays?: int[], end?: array, skip?: string[]}
 */
function basix_event_rule_clean( $raw ): array {
	if ( is_string( $raw ) ) {
		$raw = json_decode( $raw, true );
	}
	if ( ! is_array( $raw ) || ! isset( $raw['freq'] ) || ! array_key_exists( $raw['freq'], basix_event_repeat_freqs() ) ) {
		return array();
	}
	$rule = array(
		'freq'     => $raw['freq'],
		'interval' => max( 1, min( 99, (int) ( $raw['interval'] ?? 1 ) ) ),
		'weekdays' => array(),
		'end'      => array( 'type' => 'never' ),
		'skip'     => array(),
	);
	if ( 'weekly' === $rule['freq'] && ! empty( $raw['weekdays'] ) && is_array( $raw['weekdays'] ) ) {
		$days = array();
		foreach ( $raw['weekdays'] as $day ) {
			if ( is_numeric( $day ) && (int) $day >= 0 && (int) $day <= 6 ) {
				$days[] = (int) $day;
			}
		}
		$rule['weekdays'] = array_values( array_unique( $days ) );
		sort( $rule['weekdays'] );
	}
	$end = isset( $raw['end'] ) && is_array( $raw['end'] ) ? $raw['end'] : array();
	if ( 'until' === ( $end['type'] ?? '' ) && basix_events_is_date( (string) ( $end['until'] ?? '' ) ) ) {
		$rule['end'] = array(
			'type'  => 'until',
			'until' => $end['until'],
		);
	} elseif ( 'count' === ( $end['type'] ?? '' ) ) {
		$rule['end'] = array(
			'type'  => 'count',
			'count' => max( 1, min( BASIX_EVENT_MAX_OCCURRENCES, (int) ( $end['count'] ?? 1 ) ) ),
		);
	}
	if ( ! empty( $raw['skip'] ) && is_array( $raw['skip'] ) ) {
		foreach ( $raw['skip'] as $date ) {
			if ( is_string( $date ) && basix_events_is_date( $date ) ) {
				$rule['skip'][] = $date;
			}
		}
		$rule['skip'] = array_values( array_unique( $rule['skip'] ) );
		sort( $rule['skip'] );
	}
	return $rule;
}

/**
 * The dates (Y-m-d) a rule produces, in order, starting with the start date itself. The start date always counts as
 * the first occurrence, even if the rule would not hit it (a weekly rule on other weekdays starts on its start date).
 * Skipped dates are still counted towards "after N times", as in iCalendar.
 *
 * @param string $start Start date (Y-m-d).
 * @param array  $rule  A clean rule.
 * @return Generator<int, string>
 */
function basix_event_rule_dates( string $start, array $rule ): Generator {
	$utc   = new DateTimeZone( 'UTC' );
	$first = new DateTimeImmutable( $start . ' 00:00:00', $utc );
	$every = (int) $rule['interval'];
	$limit = 'count' === $rule['end']['type'] ? (int) $rule['end']['count'] : PHP_INT_MAX;
	$until = 'until' === $rule['end']['type'] ? $rule['end']['until'] : '9999-12-31';
	$given = 0;
	$steps = 0;

	yield $start;
	++$given;

	$start_day = (int) $first->format( 'j' );
	$start_wd  = (int) $first->format( 'w' );
	$nth       = (int) ceil( $start_day / 7 );
	$nth       = $nth > 4 ? -1 : $nth;

	$weekdays = $rule['weekdays'] ? $rule['weekdays'] : array( $start_wd );
	$monday   = $first->modify( '-' . ( ( $start_wd + 6 ) % 7 ) . ' days' );

	// Weekly rules begin with the start week itself (its days after the start date still count).
	for ( $i = 'weekly' === $rule['freq'] ? 0 : 1; $given < $limit && $steps < BASIX_EVENT_MAX_STEPS; ++$i ) {
		$found = array();
		++$steps;
		switch ( $rule['freq'] ) {
			case 'daily':
				$found[] = $first->modify( '+' . ( $i * $every ) . ' days' );
				break;
			case 'weekly':
				// Weeks start on Monday, like the calendar and iCalendar's default.
				$week = $monday->modify( '+' . ( $i * $every ) . ' weeks' );
				foreach ( $weekdays as $wd ) {
					$day = $week->modify( '+' . ( ( $wd + 6 ) % 7 ) . ' days' );
					if ( $day > $first ) {
						$found[] = $day;
					}
				}
				break;
			case 'monthly':
				$month = $first->modify( 'first day of this month' )->modify( '+' . ( $i * $every ) . ' months' );
				if ( $start_day <= (int) $month->format( 't' ) ) {
					$found[] = $month->setDate( (int) $month->format( 'Y' ), (int) $month->format( 'n' ), $start_day );
				}
				break;
			case 'monthly_weekday':
				$month   = $first->modify( 'first day of this month' )->modify( '+' . ( $i * $every ) . ' months' );
				$found[] = basix_event_nth_weekday( (int) $month->format( 'Y' ), (int) $month->format( 'n' ), $start_wd, $nth );
				break;
			case 'yearly':
				$year  = (int) $first->format( 'Y' ) + $i * $every;
				$month = (int) $first->format( 'n' );
				if ( checkdate( $month, $start_day, $year ) ) {
					$found[] = $first->setDate( $year, $month, $start_day );
				}
				break;
		}
		sort( $found );
		foreach ( $found as $date ) {
			$text = $date->format( 'Y-m-d' );
			if ( $text > $until ) {
				return;
			}
			yield $text;
			if ( ++$given >= $limit ) {
				return;
			}
		}
	}
}

/**
 * The nth weekday of a month.
 *
 * @param int $year    Year.
 * @param int $month   Month (1 to 12).
 * @param int $weekday Weekday, 0 (Sunday) to 6.
 * @param int $nth     1 to 4, or -1 for the last one.
 * @return DateTimeImmutable|null
 */
function basix_event_nth_weekday( int $year, int $month, int $weekday, int $nth ): ?DateTimeImmutable {
	$utc  = new DateTimeZone( 'UTC' );
	$days = (int) gmdate( 't', gmmktime( 0, 0, 0, $month, 1, $year ) );
	$hits = array();
	for ( $day = 1; $day <= $days; $day++ ) {
		if ( (int) gmdate( 'w', gmmktime( 0, 0, 0, $month, $day, $year ) ) === $weekday ) {
			$hits[] = $day;
		}
	}
	$day = -1 === $nth ? end( $hits ) : ( $hits[ $nth - 1 ] ?? null );
	return $day ? ( new DateTimeImmutable( 'now', $utc ) )->setDate( $year, $month, $day )->setTime( 0, 0 ) : null;
}

/**
 * The rule of an event, or an empty array when it does not repeat.
 *
 * @param int $post_id Event id.
 * @return array
 */
function basix_event_rule( int $post_id ): array {
	return basix_event_rule_clean( (string) get_post_meta( $post_id, 'basix_event_repeat', true ) );
}

/**
 * The occurrences of an event that touch a range of days. An event that does not repeat gives itself, if it touches
 * the range. All times are in the site's time zone, like basix_event_range_site(); `own_start` and `own_end` are the
 * event's own local times.
 *
 * @param int|WP_Post $event Event.
 * @param string      $from  First day (Y-m-d, site days).
 * @param string      $to    Last day (Y-m-d, site days).
 * @param int         $max   Most occurrences to return.
 * @return array<int, array{id: string, date: string, start: string, end: string, all_day: bool, own_start: string, own_end: string}>
 */
function basix_event_occurrences( $event, string $from, string $to, int $max = BASIX_EVENT_MAX_OCCURRENCES ): array {
	$post_id = $event instanceof WP_Post ? $event->ID : (int) $event;
	$range   = basix_event_range( $post_id );
	if ( ! $range ) {
		return array();
	}
	$max    = max( 1, min( BASIX_EVENT_MAX_OCCURRENCES, $max ) );
	$site   = wp_timezone();
	$zone   = $range['all_day'] ? $site : basix_event_zone( $post_id );
	$rule   = basix_event_rule( $post_id );
	$win_a  = ( new DateTimeImmutable( $from . ' 00:00:00', $site ) )->getTimestamp();
	$win_b  = ( new DateTimeImmutable( $to . ' 23:59:59', $site ) )->getTimestamp();
	$first  = substr( $range['start'], 0, 10 );
	$span   = (int) ( ( strtotime( substr( $range['end'], 0, 10 ) . ' UTC' ) - strtotime( $first . ' UTC' ) ) / DAY_IN_SECONDS );
	$s_time = substr( $range['start'], 11 );
	$e_time = substr( $range['end'], 11 );
	$out    = array();
	$dates  = $rule ? basix_event_rule_dates( $first, $rule ) : array( $first );

	foreach ( $dates as $date ) {
		// Dates arrive in order. Stop once one starts after the range, with a day of slack for time zones.
		if ( $date > gmdate( 'Y-m-d', strtotime( $to . ' UTC' ) + 2 * DAY_IN_SECONDS ) ) {
			break;
		}
		if ( $rule && in_array( $date, $rule['skip'], true ) ) {
			continue;
		}
		$end_date = gmdate( 'Y-m-d', strtotime( $date . ' UTC' ) + $span * DAY_IN_SECONDS );
		$start    = new DateTimeImmutable( $date . 'T' . $s_time, $zone );
		$end      = new DateTimeImmutable( $end_date . 'T' . $e_time, $zone );
		if ( $range['all_day'] ) {
			$end = $end->setTime( 23, 59, 59 );
		}
		if ( $start->getTimestamp() > $win_b || $end->getTimestamp() < $win_a ) {
			continue;
		}
		$in_site = $range['all_day'] ? array( $start, $end ) : array( $start->setTimezone( $site ), $end->setTimezone( $site ) );
		$out[]   = array(
			'id'        => $post_id . '-' . $date,
			'date'      => $date,
			'start'     => $range['all_day'] ? $date . 'T00:00' : $in_site[0]->format( 'Y-m-d\TH:i' ),
			'end'       => $range['all_day'] ? $end_date . 'T23:59' : $in_site[1]->format( 'Y-m-d\TH:i' ),
			'all_day'   => $range['all_day'],
			'own_start' => $date . 'T' . $s_time,
			'own_end'   => $end_date . 'T' . $e_time,
		);
		if ( count( $out ) >= $max ) {
			break;
		}
	}
	return $out;
}

/**
 * The end of the last occurrence as a UTC moment, or a far future moment for a rule that never ends. Used to leave
 * out events that are over without expanding them.
 *
 * @param int $post_id Event id.
 * @return string Y-m-d H:i:s, or '' when the event has no start date.
 */
function basix_event_last_utc( int $post_id ): string {
	$range = basix_event_range( $post_id );
	if ( ! $range ) {
		return '';
	}
	$rule = basix_event_rule( $post_id );
	$zone = $range['all_day'] ? wp_timezone() : basix_event_zone( $post_id );
	$utc  = new DateTimeZone( 'UTC' );
	$end  = static function ( string $date ) use ( $range, $zone, $utc ): string {
		$days = (int) ( ( strtotime( substr( $range['end'], 0, 10 ) . ' UTC' ) - strtotime( substr( $range['start'], 0, 10 ) . ' UTC' ) ) / DAY_IN_SECONDS );
		$last = gmdate( 'Y-m-d', strtotime( $date . ' UTC' ) + $days * DAY_IN_SECONDS );
		$when = new DateTimeImmutable( $last . 'T' . substr( $range['end'], 11 ), $zone );
		return ( $range['all_day'] ? $when->setTime( 23, 59, 59 ) : $when )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
	};
	if ( ! $rule || 'never' === $rule['end']['type'] ) {
		return $rule ? '9999-12-31 23:59:59' : $end( substr( $range['start'], 0, 10 ) );
	}
	$last = substr( $range['start'], 0, 10 );
	foreach ( basix_event_rule_dates( $last, $rule ) as $date ) {
		$last = $date;
	}
	return $end( $last );
}

/**
 * The start of the next occurrence that has not ended yet, as a UTC moment. When every occurrence is over it is the
 * start of the last one. Archives sort by it.
 *
 * @param int $post_id Event id.
 * @return string Y-m-d H:i:s, or '' without a start date.
 */
function basix_event_next_utc( int $post_id ): string {
	$next = basix_event_next( $post_id );
	return $next ? $next['start_utc'] : '';
}

/**
 * The next occurrence that has not ended yet (running now counts), or the last one when all are over.
 *
 * @param int $post_id Event id.
 * @return array{date: string, start: string, end: string, all_day: bool, own_start: string, own_end: string, start_utc: string}|null
 */
function basix_event_next( int $post_id ): ?array {
	$range = basix_event_range( $post_id );
	if ( ! $range ) {
		return null;
	}
	$utc  = new DateTimeZone( 'UTC' );
	$zone = $range['all_day'] ? wp_timezone() : basix_event_zone( $post_id );
	$now  = gmdate( 'Y-m-d H:i:s' );
	$list = array();
	// A few occurrences from yesterday on, so one that ended earlier today is passed over.
	foreach ( basix_event_occurrences( $post_id, (string) wp_date( 'Y-m-d', time() - DAY_IN_SECONDS ), (string) wp_date( 'Y-m-d', time() + 120 * YEAR_IN_SECONDS ), 4 ) as $occurrence ) {
		$ends = ( new DateTimeImmutable( $occurrence['own_end'], $zone ) );
		$ends = $range['all_day'] ? $ends->setTime( 23, 59, 59 ) : $ends;
		if ( $ends->setTimezone( $utc )->format( 'Y-m-d H:i:s' ) >= $now ) {
			$list = array( $occurrence );
			break;
		}
	}
	if ( ! $list ) {
		// Over: the last occurrence, found by walking the rule to its end (it is bounded or it would not be over).
		$rule = basix_event_rule( $post_id );
		$date = substr( $range['start'], 0, 10 );
		foreach ( $rule ? basix_event_rule_dates( $date, $rule ) : array( $date ) as $candidate ) {
			if ( $rule && in_array( $candidate, $rule['skip'], true ) && $candidate !== $date ) {
				continue;
			}
			$date = $candidate;
		}
		$list = basix_event_occurrences( $post_id, $date, $date, 1 );
	}
	if ( ! $list ) {
		return null;
	}
	$next              = $list[0];
	$next['start_utc'] = ( new DateTimeImmutable( $next['own_start'], $zone ) )->setTimezone( $utc )->format( 'Y-m-d H:i:s' );
	return $next;
}

/**
 * The rule as a sentence, for example "Every week on Tuesday, until Dec 20, 2026".
 *
 * @param array  $rule  A clean rule.
 * @param string $start Start date of the event (Y-m-d); monthly and yearly rules repeat on its day.
 * @return string Empty without a rule.
 */
function basix_event_rule_words( array $rule, string $start ): string {
	if ( ! $rule || ! basix_events_is_date( $start ) ) {
		return '';
	}
	global $wp_locale;
	$every = (int) $rule['interval'];
	$stamp = strtotime( $start . ' UTC' );
	$wd    = (int) gmdate( 'w', $stamp );
	$day   = (int) gmdate( 'j', $stamp );
	switch ( $rule['freq'] ) {
		case 'daily':
			/* translators: %d: number of days, more than one. */
			$text = 1 === $every ? __( 'Every day', 'basix-events' ) : sprintf( __( 'Every %d days', 'basix-events' ), $every );
			break;
		case 'weekly':
			$names = array();
			foreach ( $rule['weekdays'] ? $rule['weekdays'] : array( $wd ) as $number ) {
				$names[] = $wp_locale->get_weekday( $number );
			}
			$list  = wp_sprintf( '%l', $names );
			/* translators: 1: number of weeks, more than one, 2: weekday names, for example "Tuesday and Thursday". */
			$text = 1 === $every ? sprintf( /* translators: %s: weekday names, for example "Tuesday and Thursday". */ __( 'Every week on %s', 'basix-events' ), $list ) : sprintf( __( 'Every %1$d weeks on %2$s', 'basix-events' ), $every, $list );
			break;
		case 'monthly':
			/* translators: 1: number of months, more than one, 2: day of the month, for example 5. */
			$text = 1 === $every ? sprintf( /* translators: %d: day of the month, for example 5. */ __( 'Every month on day %d', 'basix-events' ), $day ) : sprintf( __( 'Every %1$d months on day %2$d', 'basix-events' ), $every, $day );
			break;
		case 'monthly_weekday':
			$ordinals = array(
				1  => __( 'first', 'basix-events' ),
				2  => __( 'second', 'basix-events' ),
				3  => __( 'third', 'basix-events' ),
				4  => __( 'fourth', 'basix-events' ),
				-1 => __( 'last', 'basix-events' ),
			);
			$nth      = (int) ceil( $day / 7 );
			$nth      = $nth > 4 ? -1 : $nth;
			$name     = $wp_locale->get_weekday( $wd );
			/* translators: 1: number of months, more than one, 2: ordinal such as first or last, 3: weekday name. */
			$text = 1 === $every ? sprintf( /* translators: 1: ordinal such as first or last, 2: weekday name. */ __( 'Every month on the %1$s %2$s', 'basix-events' ), $ordinals[ $nth ], $name ) : sprintf( __( 'Every %1$d months on the %2$s %3$s', 'basix-events' ), $every, $ordinals[ $nth ], $name );
			break;
		default:
			$when = wp_date( _x( 'M j', 'yearly repeat date', 'basix-events' ), $stamp, new DateTimeZone( 'UTC' ) );
			/* translators: 1: number of years, more than one, 2: the date without a year, for example "Nov 5". */
			$text = 1 === $every ? sprintf( /* translators: %s: the date without a year, for example "Nov 5". */ __( 'Every year on %s', 'basix-events' ), $when ) : sprintf( __( 'Every %1$d years on %2$s', 'basix-events' ), $every, $when );
	}
	if ( 'until' === $rule['end']['type'] ) {
		/* translators: 1: the rule, for example "Every week on Tuesday", 2: the last date. */
		$text = sprintf( __( '%1$s, until %2$s', 'basix-events' ), $text, wp_date( get_option( 'date_format' ), strtotime( $rule['end']['until'] . ' UTC' ), new DateTimeZone( 'UTC' ) ) );
	} elseif ( 'count' === $rule['end']['type'] ) {
		$times = (int) $rule['end']['count'];
		/* translators: 1: the rule, for example "Every week on Tuesday", 2: how many times it happens, more than one. */
		$text = 1 === $times ? sprintf( /* translators: %s: the rule, for example "Every week on Tuesday". */ __( '%s, once', 'basix-events' ), $text ) : sprintf( __( '%1$s, %2$d times', 'basix-events' ), $text, $times );
	}
	return $text;
}

/**
 * The occurrence of a repeating event to show on its page or in a list: the one named by `?date=` on the event's own
 * page, otherwise the next one.
 *
 * @param int $post_id Event id.
 * @return array|null An occurrence, or null when the event does not repeat.
 */
function basix_event_shown_occurrence( int $post_id ): ?array {
	if ( ! basix_event_rule( $post_id ) ) {
		return null;
	}
	// A plain address parameter that only picks which date to show; it is checked against the rule.
	if ( is_singular( BASIX_EVENT ) && isset( $_GET['date'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$date = sanitize_text_field( wp_unslash( $_GET['date'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( basix_events_is_date( $date ) ) {
			$day = strtotime( $date . ' UTC' );
			foreach ( basix_event_occurrences( $post_id, gmdate( 'Y-m-d', $day - DAY_IN_SECONDS ), gmdate( 'Y-m-d', $day + DAY_IN_SECONDS ) ) as $occurrence ) {
				if ( $occurrence['date'] === $date ) {
					return $occurrence;
				}
			}
		}
	}
	// On a day's list: the date of that day.
	$day = basix_event_day();
	if ( $day ) {
		foreach ( basix_event_occurrences( $post_id, $day, $day ) as $occurrence ) {
			return $occurrence;
		}
	}
	return basix_event_next( $post_id );
}

/**
 * The coming occurrences of a repeating event (one running now counts).
 *
 * @param int $post_id Event id.
 * @param int $count   How many.
 * @return array
 */
function basix_event_upcoming( int $post_id, int $count = 5 ): array {
	$range = basix_event_range( $post_id );
	if ( ! $range ) {
		return array();
	}
	$zone = $range['all_day'] ? wp_timezone() : basix_event_zone( $post_id );
	$now  = time();
	$out  = array();
	foreach ( basix_event_occurrences( $post_id, (string) wp_date( 'Y-m-d', $now - DAY_IN_SECONDS ), (string) wp_date( 'Y-m-d', $now + 5 * YEAR_IN_SECONDS ), $count + 3 ) as $occurrence ) {
		$ends = new DateTimeImmutable( $occurrence['own_end'], $zone );
		if ( $range['all_day'] ) {
			$ends = $ends->setTime( 23, 59, 59 );
		}
		if ( $ends->getTimestamp() >= $now ) {
			$out[] = $occurrence;
		}
		if ( count( $out ) >= $count ) {
			break;
		}
	}
	return $out;
}

// The "next date" of each repeating event moves on as time passes, so a daily job refreshes it.
add_action(
	'init',
	function () {
		if ( ! wp_next_scheduled( 'basix_events_refresh' ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', 'basix_events_refresh' );
		}
	}
);
add_action(
	'basix_events_refresh',
	function () {
		foreach ( get_posts(
			array(
				'post_type'      => BASIX_EVENT,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'fields'         => 'ids',
				'no_found_rows'  => true,
				'meta_key'       => 'basix_event_repeat', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- a daily job over the repeating events.
				'meta_compare'   => '!=',
				'meta_value'     => '', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- same.
			)
		) as $id ) {
			update_post_meta( $id, '_basix_event_next_utc', basix_event_next_utc( (int) $id ) );
		}
	}
);

/**
 * Builds a rule from the "Repeat" form fields (basix_repeat[...]).
 *
 * @param mixed $form Posted fields.
 * @return array Rule, not yet cleaned (basix_event_rule_clean() does that).
 */
function basix_event_rule_from_form( $form ): array {
	if ( ! is_array( $form ) || empty( $form['freq'] ) ) {
		return array();
	}
	$skip = array();
	foreach ( preg_split( '/[\s,;]+/', (string) ( $form['skip'] ?? '' ), -1, PREG_SPLIT_NO_EMPTY ) as $date ) {
		$skip[] = $date;
	}
	return array(
		'freq'     => (string) $form['freq'],
		'interval' => (int) ( $form['interval'] ?? 1 ),
		'weekdays' => isset( $form['weekdays'] ) && is_array( $form['weekdays'] ) ? $form['weekdays'] : array(),
		'end'      => array(
			'type'  => (string) ( $form['end'] ?? 'never' ),
			'until' => (string) ( $form['until'] ?? '' ),
			'count' => (int) ( $form['count'] ?? 1 ),
		),
		'skip'     => $skip,
	);
}

/**
 * Prints the "Repeat" section of the event box, with a sentence that follows what is chosen.
 *
 * @param WP_Post $post The event.
 */
function basix_event_repeat_fields( WP_Post $post ): void {
	global $wp_locale;
	$rule     = basix_event_rule( $post->ID );
	$freq     = $rule['freq'] ?? '';
	$weekdays = $rule['weekdays'] ?? array();
	$end      = $rule['end']['type'] ?? 'never';
	$start    = (string) get_post_meta( $post->ID, 'basix_event_start', true );
	?>
	<fieldset class="basix-repeat" data-summary-url="<?php echo esc_url( rest_url( 'basix/v1/event-repeat' ) ); ?>" data-nonce="<?php echo esc_attr( wp_create_nonce( 'wp_rest' ) ); ?>">
		<legend><?php esc_html_e( 'Repeat', 'basix-events' ); ?></legend>
		<style>.basix-repeat{margin-top:1.25rem;padding:0}.basix-repeat legend{font-weight:600;padding:0 0 .5rem}.basix-repeat .row{display:flex;flex-wrap:wrap;gap:.75rem 1.5rem;align-items:center;margin-bottom:.75rem}.basix-repeat [hidden]{display:none}.basix-repeat textarea{width:100%;max-width:24rem}.basix-repeat .summary{font-weight:600}</style>
		<div class="row">
			<label for="basix_repeat_freq" class="screen-reader-text"><?php esc_html_e( 'Repeat', 'basix-events' ); ?></label>
			<select id="basix_repeat_freq" name="basix_repeat[freq]">
				<option value=""><?php esc_html_e( 'Does not repeat', 'basix-events' ); ?></option>
				<?php foreach ( basix_event_repeat_freqs() as $value => $label ) : ?>
					<option value="<?php echo esc_attr( $value ); ?>"<?php selected( $freq, $value ); ?>><?php echo esc_html( $label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<div class="basix-repeat-more"<?php echo $freq ? '' : ' hidden'; ?>>
			<div class="row">
				<label for="basix_repeat_interval"><?php esc_html_e( 'Every', 'basix-events' ); ?></label>
				<input type="number" id="basix_repeat_interval" name="basix_repeat[interval]" min="1" max="99" value="<?php echo esc_attr( (string) ( $rule['interval'] ?? 1 ) ); ?>" style="width:5rem">
				<span class="description"><?php esc_html_e( 'times the unit above (2 means every second day, week, month or year)', 'basix-events' ); ?></span>
			</div>
			<div class="row basix-repeat-weekdays"<?php echo 'weekly' === $freq ? '' : ' hidden'; ?>>
				<?php foreach ( array( 1, 2, 3, 4, 5, 6, 0 ) as $number ) : ?>
					<label><input type="checkbox" name="basix_repeat[weekdays][]" value="<?php echo esc_attr( (string) $number ); ?>"<?php checked( in_array( $number, $weekdays, true ) ); ?>> <?php echo esc_html( $wp_locale->get_weekday_abbrev( $wp_locale->get_weekday( $number ) ) ); ?></label>
				<?php endforeach; ?>
				<span class="description"><?php esc_html_e( 'None ticked: the weekday of the start date.', 'basix-events' ); ?></span>
			</div>
			<div class="row">
				<label><input type="radio" name="basix_repeat[end]" value="never"<?php checked( 'never', $end ); ?>> <?php esc_html_e( 'Never ends', 'basix-events' ); ?></label>
				<label><input type="radio" name="basix_repeat[end]" value="until"<?php checked( 'until', $end ); ?>> <?php esc_html_e( 'Ends on', 'basix-events' ); ?></label>
				<input type="date" name="basix_repeat[until]" value="<?php echo esc_attr( (string) ( $rule['end']['until'] ?? '' ) ); ?>" aria-label="<?php esc_attr_e( 'Last date', 'basix-events' ); ?>">
				<label><input type="radio" name="basix_repeat[end]" value="count"<?php checked( 'count', $end ); ?>> <?php esc_html_e( 'Ends after', 'basix-events' ); ?></label>
				<input type="number" name="basix_repeat[count]" min="1" max="<?php echo esc_attr( (string) BASIX_EVENT_MAX_OCCURRENCES ); ?>" value="<?php echo esc_attr( (string) ( $rule['end']['count'] ?? 10 ) ); ?>" style="width:5rem" aria-label="<?php esc_attr_e( 'Number of times', 'basix-events' ); ?>">
				<span class="description"><?php esc_html_e( 'times', 'basix-events' ); ?></span>
			</div>
			<div class="row">
				<label for="basix_repeat_skip"><?php esc_html_e( 'Skipped dates', 'basix-events' ); ?></label>
				<textarea id="basix_repeat_skip" name="basix_repeat[skip]" rows="2" placeholder="2026-12-24"><?php echo esc_textarea( implode( "\n", $rule['skip'] ?? array() ) ); ?></textarea>
				<span class="description"><?php esc_html_e( 'One date (YYYY-MM-DD) per line. These dates do not happen.', 'basix-events' ); ?></span>
			</div>
		</div>
		<p class="summary" aria-live="polite"><?php echo esc_html( basix_event_rule_words( $rule, $start ) ); ?></p>
		<script>
		( function () {
			var box = document.currentScript.parentNode, more = box.querySelector( '.basix-repeat-more' ), days = box.querySelector( '.basix-repeat-weekdays' ),
				summary = box.querySelector( '.summary' ), timer = 0, serial = 0;
			function form() {
				var data = new FormData( box.closest( 'form' ) ), body = new URLSearchParams();
				data.forEach( function ( value, key ) { if ( key.indexOf( 'basix_repeat[' ) === 0 || key === 'basix_event_start' ) { body.append( key, value ); } } );
				return body;
			}
			function update() {
				var freq = box.querySelector( '#basix_repeat_freq' ).value, mine = ++serial;
				more.hidden = ! freq;
				days.hidden = freq !== 'weekly';
				if ( ! freq ) { summary.textContent = ''; return; }
				clearTimeout( timer );
				timer = setTimeout( function () {
					fetch( box.dataset.summaryUrl, { method: 'POST', headers: { 'X-WP-Nonce': box.dataset.nonce }, body: form() } )
						.then( function ( r ) { return r.json(); } )
						.then( function ( r ) { if ( mine === serial ) { summary.textContent = r.summary || ''; } } )
						.catch( function () {} );
				}, 250 );
			}
			box.addEventListener( 'input', update );
			box.closest( 'form' ).addEventListener( 'input', function ( e ) { if ( e.target.id === 'basix_event_start' ) { update(); } } );
		}() );
		</script>
	</fieldset>
	<?php
}

// POST /wp-json/basix/v1/event-repeat: the sentence for what the editor has chosen, so the box needs no copy of the wording.
add_action(
	'rest_api_init',
	function () {
		register_rest_route(
			'basix/v1',
			'/event-repeat',
			array(
				'methods'             => 'POST',
				'permission_callback' => static function () {
					return current_user_can( 'edit_posts' );
				},
				'callback'            => static function ( WP_REST_Request $request ) {
					$rule  = basix_event_rule_clean( basix_event_rule_from_form( $request->get_param( 'basix_repeat' ) ) );
					$start = (string) $request->get_param( 'basix_event_start' );
					return rest_ensure_response( array( 'summary' => basix_event_rule_words( $rule, basix_events_is_date( $start ) ? $start : wp_date( 'Y-m-d' ) ) ) );
				},
			)
		);
	}
);

/**
 * On the page of a repeating event: how it repeats, in words, and the next dates (each a link to that date).
 */
function basix_event_repeat_info(): void {
	$post_id = (int) get_the_ID();
	$rule    = basix_event_rule( $post_id );
	if ( ! $rule ) {
		return;
	}
	$shown = basix_event_shown_occurrence( $post_id );
	echo '<section class="event-repeat" aria-labelledby="event-repeat-title">';
	echo '<h2 id="event-repeat-title" class="text-h5">' . esc_html__( 'Repeats', 'basix-events' ) . '</h2>';
	echo '<p>' . esc_html( basix_event_rule_words( $rule, (string) get_post_meta( $post_id, 'basix_event_start', true ) ) ) . '</p>';
	$upcoming = basix_event_upcoming( $post_id, 5 );
	if ( $upcoming ) {
		echo '<p class="text-caption">' . esc_html__( 'Next dates', 'basix-events' ) . '</p><ul class="event-dates">';
		foreach ( $upcoming as $occurrence ) {
			$text = esc_html( basix_event_when( $post_id, false, $occurrence ) );
			if ( $shown && $shown['date'] === $occurrence['date'] ) {
				echo '<li aria-current="date"><strong>' . $text . '</strong></li>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			} else {
				printf( '<li><a href="%s">%s</a></li>', esc_url( add_query_arg( 'date', $occurrence['date'], get_permalink( $post_id ) ) ), $text ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
			}
		}
		echo '</ul>';
	} else {
		echo '<p class="text-caption">' . esc_html__( 'All dates of this event are over.', 'basix-events' ) . '</p>';
	}
	echo '</section>';
}
