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
 * @package basix
 */

/** Most occurrences returned for one event and range, so a bad rule cannot loop forever. */
const BASIX_EVENT_MAX_OCCURRENCES = 500;

/** Most dates looked at while expanding one rule. */
const BASIX_EVENT_MAX_STEPS = 100000;

/** The repeat frequencies, as value => label. */
function basix_event_repeat_freqs(): array {
	return array(
		'daily'           => __( 'Every day', 'basix' ),
		'weekly'          => __( 'Every week', 'basix' ),
		'monthly'         => __( 'Every month, on the same date', 'basix' ),
		'monthly_weekday' => __( 'Every month, on the same weekday', 'basix' ),
		'yearly'          => __( 'Every year', 'basix' ),
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
	if ( 'until' === ( $end['type'] ?? '' ) && basix_is_date( (string) ( $end['until'] ?? '' ) ) ) {
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
			if ( is_string( $date ) && basix_is_date( $date ) ) {
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
