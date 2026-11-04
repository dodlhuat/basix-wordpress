<?php
/**
 * Checks how events handle time zones and repeats. Run with `wp eval-file /php/check-events.php` (make check does).
 * It creates a few events, changes the site's time zone for a moment, and puts everything back.
 */

$failed   = 0;
$check    = static function ( bool $ok, string $what ) use ( &$failed ) {
	echo ( $ok ? 'ok   ' : 'FAIL ' ), $what, "\n";
	if ( ! $ok ) {
		++$failed;
	}
};
$original = get_option( 'timezone_string' );
$ids      = array();
$make     = static function ( array $meta ) use ( &$ids ): int {
	$id = wp_insert_post(
		array(
			'post_type'   => 'basix-event',
			'post_status' => 'publish',
			'post_title'  => 'Zone check',
			'meta_input'  => $meta,
		)
	);
	$ids[] = $id;
	return (int) $id;
};
$titles   = static function ( string $from, string $to ): array {
	return array_map( 'get_the_ID', basix_events_between( $from, $to ) );
};

update_option( 'timezone_string', 'Europe/Vienna' );

// A timed event in New York, seen from Vienna.
$ny = $make(
	array(
		'basix_event_start'      => '2026-11-05',
		'basix_event_start_time' => '19:00',
		'basix_event_end'        => '2026-11-05',
		'basix_event_end_time'   => '20:30',
		'basix_event_timezone'   => 'America/New_York',
	)
);
$site = basix_event_range_site( $ny );
$check( '2026-11-06T01:00' === $site['start'] && '2026-11-06T02:30' === $site['end'], 'New York 19:00 is 01:00 the next day in Vienna' );
$check( '2026-11-06 00:00:00' === get_post_meta( $ny, '_basix_event_start_utc', true ), 'the start is stored as a UTC moment' );
$check( in_array( $ny, wp_list_pluck( basix_events_between( '2026-11-06', '2026-11-06' ), 'ID' ), true ), 'the event is on its Vienna day' );
$check( ! in_array( $ny, wp_list_pluck( basix_events_between( '2026-11-05', '2026-11-05' ), 'ID' ), true ), 'and not on its New York day' );
$check( false !== strpos( basix_event_when( $ny ), 'EST' ), 'its own time names the zone' );
$check( false === strpos( basix_event_when( $ny, true ), 'EST' ), 'the site time does not' );

// A timed event without a zone follows the site.
$plain = $make(
	array(
		'basix_event_start'      => '2026-11-05',
		'basix_event_start_time' => '19:00',
		'basix_event_end'        => '2026-11-05',
		'basix_event_end_time'   => '20:00',
	)
);
$check( basix_event_range( $plain ) === basix_event_range_site( $plain ), 'an event without a zone is not converted' );
$check( false === strpos( basix_event_when( $plain ), 'CET' ), 'and does not name a zone' );

// An all-day event is a whole day in the site's zone.
$day = $make(
	array(
		'basix_event_start' => '2026-11-10',
		'basix_event_end'   => '2026-11-10',
	)
);
$check( '2026-11-09 23:00:00' === get_post_meta( $day, '_basix_event_start_utc', true ) && '2026-11-10 22:59:59' === get_post_meta( $day, '_basix_event_end_utc', true ), 'an all-day event covers the site day' );
$check( in_array( $day, wp_list_pluck( basix_events_between( '2026-11-10', '2026-11-10' ), 'ID' ), true ), 'it is on its day' );
$check( ! in_array( $day, wp_list_pluck( basix_events_between( '2026-11-09', '2026-11-09' ), 'ID' ), true ) && ! in_array( $day, wp_list_pluck( basix_events_between( '2026-11-11', '2026-11-11' ), 'ID' ), true ), 'and not on the days around it' );

// A time that does not exist (clocks jump forward) must not break anything.
$gap = $make(
	array(
		'basix_event_start'      => '2026-03-08',
		'basix_event_start_time' => '02:30',
		'basix_event_end'        => '2026-03-08',
		'basix_event_end_time'   => '03:30',
		'basix_event_timezone'   => 'America/New_York',
	)
);
$check( '' !== (string) get_post_meta( $gap, '_basix_event_start_utc', true ), 'a time inside a daylight saving gap still gets a moment' );

// Changing the site's zone moves all-day events.
update_option( 'timezone_string', 'UTC' );
basix_events_rebuild_derived();
$check( '2026-11-10 00:00:00' === get_post_meta( $day, '_basix_event_start_utc', true ), 'a new site time zone moves all-day events' );

// A made up zone is dropped.
update_post_meta( $plain, 'basix_event_timezone', 'Mars/Olympus' );
$check( '' === (string) get_post_meta( $plain, 'basix_event_timezone', true ), 'an unknown time zone is dropped' );

// Repeat rules. First the dates alone, then what an event does with them.
update_option( 'timezone_string', 'Europe/Vienna' );
$dates = static function ( string $start, array $rule, int $take = 50 ): array {
	$out = array();
	foreach ( basix_event_rule_dates( $start, basix_event_rule_clean( $rule ) ) as $date ) {
		$out[] = $date;
		if ( count( $out ) >= $take ) {
			break;
		}
	}
	return $out;
};
$same = static function ( array $a, array $b ): bool {
	return $a === $b;
};
$check( $same( $dates( '2026-11-03', array( 'freq' => 'daily', 'interval' => 2, 'end' => array( 'type' => 'count', 'count' => 3 ) ) ), array( '2026-11-03', '2026-11-05', '2026-11-07' ) ), 'every second day, three times' );
$check( $same( $dates( '2026-11-03', array( 'freq' => 'weekly', 'weekdays' => array( 2, 4 ), 'end' => array( 'type' => 'count', 'count' => 5 ) ) ), array( '2026-11-03', '2026-11-05', '2026-11-10', '2026-11-12', '2026-11-17' ) ), 'weekly on Tuesday and Thursday' );
$check( $same( $dates( '2026-11-05', array( 'freq' => 'weekly', 'weekdays' => array( 2, 4 ), 'end' => array( 'type' => 'count', 'count' => 3 ) ) ), array( '2026-11-05', '2026-11-10', '2026-11-12' ) ), 'a weekly rule starts at its start date, not earlier in the week' );
$check( $same( $dates( '2026-11-02', array( 'freq' => 'weekly', 'interval' => 2, 'end' => array( 'type' => 'count', 'count' => 3 ) ) ), array( '2026-11-02', '2026-11-16', '2026-11-30' ) ), 'every second week uses the weekday of the start' );
$check( $same( $dates( '2026-01-31', array( 'freq' => 'monthly', 'end' => array( 'type' => 'count', 'count' => 4 ) ) ), array( '2026-01-31', '2026-03-31', '2026-05-31', '2026-07-31' ) ), 'the 31st skips months that do not have one' );
$check( $same( $dates( '2024-02-29', array( 'freq' => 'yearly', 'end' => array( 'type' => 'count', 'count' => 3 ) ) ), array( '2024-02-29', '2028-02-29', '2032-02-29' ) ), 'a leap day only happens in leap years' );
$check( $same( $dates( '2026-11-10', array( 'freq' => 'monthly_weekday', 'end' => array( 'type' => 'count', 'count' => 3 ) ) ), array( '2026-11-10', '2026-12-08', '2027-01-12' ) ), 'the second Tuesday of each month' );
$check( $same( $dates( '2026-10-30', array( 'freq' => 'monthly_weekday', 'end' => array( 'type' => 'count', 'count' => 3 ) ) ), array( '2026-10-30', '2026-11-27', '2026-12-25' ) ), 'a fifth weekday means the last one' );
$check( $same( $dates( '2026-11-03', array( 'freq' => 'daily', 'end' => array( 'type' => 'until', 'until' => '2026-11-05' ) ) ), array( '2026-11-03', '2026-11-04', '2026-11-05' ) ), 'an end date counts as a day of the rule' );
$check( array() === basix_event_rule_clean( array( 'freq' => 'hourly' ) ) && array() === basix_event_rule_clean( 'not json' ), 'a rule that makes no sense means no repeat' );
$cleaned = basix_event_rule_clean( array( 'freq' => 'weekly', 'interval' => 500, 'weekdays' => array( 9, '3', 3 ), 'skip' => array( 'x', '2026-11-05' ) ) );
$check( 99 === $cleaned['interval'] && array( 3 ) === $cleaned['weekdays'] && array( '2026-11-05' ) === $cleaned['skip'], 'a rule is cleaned: interval, weekdays and skipped dates' );

$repeat = static function ( array $rule ): string {
	return wp_json_encode( $rule );
};
$ids_in = static function ( array $occurrences ): array {
	return array_column( $occurrences, 'date' );
};

// A weekly event, skipped on one date, with an end after four times.
$weekly = $make(
	array(
		'basix_event_start'      => '2026-11-03',
		'basix_event_start_time' => '18:30',
		'basix_event_end'        => '2026-11-03',
		'basix_event_end_time'   => '20:00',
		'basix_event_repeat'     => $repeat( array( 'freq' => 'weekly', 'end' => array( 'type' => 'count', 'count' => 4 ), 'skip' => array( '2026-11-10' ) ) ),
	)
);
$check( $same( $ids_in( basix_event_occurrences( $weekly, '2026-11-01', '2026-12-31' ) ), array( '2026-11-03', '2026-11-17', '2026-11-24' ) ), 'a skipped date does not happen, and still counts towards the end' );
$check( $same( $ids_in( basix_event_occurrences( $weekly, '2026-11-15', '2026-11-18' ) ), array( '2026-11-17' ) ), 'only the dates inside the range are returned' );
$check( in_array( $weekly, wp_list_pluck( basix_events_between( '2026-11-24', '2026-11-24' ), 'ID' ), true ), 'a repeat shows on its last day' );
$check( ! in_array( $weekly, wp_list_pluck( basix_events_between( '2026-12-01', '2026-12-31' ), 'ID' ), true ), 'and not after its rule has run out' );
$check( ! in_array( $weekly, wp_list_pluck( basix_events_between( '2026-11-10', '2026-11-10' ), 'ID' ), true ), 'nor on a skipped date' );
$check( '2026-11-24 19:00:00' === get_post_meta( $weekly, '_basix_event_last_utc', true ), 'the last end is stored for the list query' );

// A repeat that never ends, with a cap.
$endless = $make(
	array(
		'basix_event_start'  => '2026-01-01',
		'basix_event_repeat' => $repeat( array( 'freq' => 'daily' ) ),
	)
);
$check( BASIX_EVENT_MAX_OCCURRENCES === count( basix_event_occurrences( $endless, '2026-01-01', '2036-12-31' ) ), 'an endless daily event is capped' );
$check( '9999-12-31 23:59:59' === get_post_meta( $endless, '_basix_event_last_utc', true ), 'and never runs out' );
$check( 7 === count( basix_event_occurrences( $endless, '2030-06-01', '2030-06-07' ) ), 'a range far from the start still works' );

// A repeating multi-day event: each date keeps the length.
$multi = $make(
	array(
		'basix_event_start'  => '2026-11-03',
		'basix_event_end'    => '2026-11-04',
		'basix_event_repeat' => $repeat( array( 'freq' => 'weekly', 'end' => array( 'type' => 'count', 'count' => 2 ) ) ),
	)
);
$second = basix_event_occurrences( $multi, '2026-11-09', '2026-11-12' );
$check( 1 === count( $second ) && '2026-11-10T00:00' === $second[0]['start'] && '2026-11-11T23:59' === $second[0]['end'], 'a repeating two day event stays two days' );

// The clock time stays the same across daylight saving, in the event's own zone.
$ny_weekly = $make(
	array(
		'basix_event_start'      => '2026-10-27',
		'basix_event_start_time' => '18:30',
		'basix_event_end'        => '2026-10-27',
		'basix_event_end_time'   => '19:30',
		'basix_event_timezone'   => 'America/New_York',
		'basix_event_repeat'     => $repeat( array( 'freq' => 'weekly', 'end' => array( 'type' => 'count', 'count' => 2 ) ) ),
	)
);
$pair = basix_event_occurrences( $ny_weekly, '2026-10-01', '2026-11-30' );
$check( 2 === count( $pair ) && '2026-10-27T18:30' === $pair[0]['own_start'] && '2026-11-03T18:30' === $pair[1]['own_start'], '6:30 pm stays 6:30 pm in New York across the change' );
$check( '2026-10-27T23:30' === $pair[0]['start'] && '2026-11-04T00:30' === $pair[1]['start'], 'and moves by an hour for Vienna, where the change came a week earlier' );

// The same rule in Vienna itself: the site time does not move.
$vienna = $make(
	array(
		'basix_event_start'      => '2026-10-20',
		'basix_event_start_time' => '18:30',
		'basix_event_end'        => '2026-10-20',
		'basix_event_end_time'   => '19:30',
		'basix_event_repeat'     => $repeat( array( 'freq' => 'weekly', 'end' => array( 'type' => 'count', 'count' => 3 ) ) ),
	)
);
$three = basix_event_occurrences( $vienna, '2026-10-01', '2026-11-30' );
$check( array( '2026-10-20T18:30', '2026-10-27T18:30', '2026-11-03T18:30' ) === array_column( $three, 'start' ), 'a Vienna event stays at 6:30 pm across the change' );

// The next date, for the archive.
$next = basix_event_next( $endless );
$check( $next && $next['date'] >= wp_date( 'Y-m-d' ), 'the next date of an endless event is today or later' );
$over = $make(
	array(
		'basix_event_start'  => '2020-01-07',
		'basix_event_repeat' => $repeat( array( 'freq' => 'weekly', 'end' => array( 'type' => 'count', 'count' => 3 ) ) ),
	)
);
$check( '2020-01-21' === basix_event_next( $over )['date'], 'the next date of a finished event is its last' );
$check( $next['date'] >= wp_date( 'Y-m-d' ) && basix_event_next( $weekly )['date'] >= wp_date( 'Y-m-d' ), 'and a running one gives its coming date' );
$check( '' !== (string) get_post_meta( $weekly, '_basix_event_next_utc', true ), 'the next date is stored for sorting' );

// The editor's form turns into the same rule.
$form = basix_event_rule_clean( basix_event_rule_from_form( array( 'freq' => 'weekly', 'interval' => '2', 'weekdays' => array( '2', '4' ), 'end' => 'count', 'until' => '', 'count' => '6', 'skip' => "2026-12-24\n2026-12-31, nonsense" ) ) );
$check( 'weekly' === $form['freq'] && 2 === $form['interval'] && array( 2, 4 ) === $form['weekdays'] && 6 === $form['end']['count'] && array( '2026-12-24', '2026-12-31' ) === $form['skip'], 'the Repeat form fields make a rule' );
$check( array() === basix_event_rule_clean( basix_event_rule_from_form( array( 'freq' => '' ) ) ), 'and "does not repeat" makes none' );
$saved = $make( array( 'basix_event_start' => '2026-11-03', 'basix_event_repeat' => array( 'freq' => 'daily', 'interval' => 3 ) ) );
$check( array( 'freq' => 'daily', 'interval' => 3 ) === array_intersect_key( basix_event_rule( $saved ), array( 'freq' => 1, 'interval' => 1 ) ), 'a rule saved through the field comes back clean' );

// The rule in words (in English, whatever language the site uses).
switch_to_locale( 'en_US' );
$words = static function ( array $rule, string $start = '2026-11-03' ): string {
	return basix_event_rule_words( basix_event_rule_clean( $rule ), $start );
};
$check( 'Every week on Tuesday, until December 20, 2026' === $words( array( 'freq' => 'weekly', 'end' => array( 'type' => 'until', 'until' => '2026-12-20' ) ) ), 'the rule in words: every week on Tuesday, until a date' );
$check( 'Every 2 weeks on Tuesday and Thursday, 6 times' === $words( array( 'freq' => 'weekly', 'interval' => 2, 'weekdays' => array( 2, 4 ), 'end' => array( 'type' => 'count', 'count' => 6 ) ) ), 'and with an interval, two weekdays and a count' );
$check( 'Every month on the second Tuesday' === $words( array( 'freq' => 'monthly_weekday' ), '2026-11-10' ) && 'Every month on the last Friday' === $words( array( 'freq' => 'monthly_weekday' ), '2026-10-30' ), 'and a weekday of the month' );
$check( 'Every day' === $words( array( 'freq' => 'daily' ) ) && 'Every 3 months on day 5' === $words( array( 'freq' => 'monthly', 'interval' => 3 ), '2026-11-05' ) && 'Every year on Nov 5' === $words( array( 'freq' => 'yearly' ), '2026-11-05' ), 'and the simple ones' );

restore_previous_locale();

// An event without a repeat is unchanged.
$check( $same( $ids_in( basix_event_occurrences( $plain, '2026-11-01', '2026-11-30' ) ), array( '2026-11-05' ) ), 'an event without a rule is one occurrence' );

// ICS export.
$check( 'a\;b\\,c\\\\d\\ne' === basix_ics_escape( "a;b,c\\d\ne" ), 'ICS text escapes semicolons, commas, backslashes and line breaks' );
$long   = basix_ics_fold( 'SUMMARY:' . str_repeat( 'ä', 60 ) );
$pieces = explode( "\r\n", $long );
$check( count( $pieces ) > 1 && max( array_map( 'strlen', $pieces ) ) <= 75 && str_repeat( 'ä', 60 ) === str_replace( array( 'SUMMARY:', "\r\n " ), '', $long ), 'a long line is folded at 75 bytes without cutting a character' );
$lines_of = static function ( int $id ): array {
	$zones = array();
	return basix_event_ics_lines( get_post( $id ), $zones );
};
$has = static function ( array $lines, string $prefix ): bool {
	foreach ( $lines as $line ) {
		if ( 0 === strpos( $line, $prefix ) ) {
			return true;
		}
	}
	return false;
};
$all = $lines_of( $day );
$check( $has( $all, 'DTSTART;VALUE=DATE:20261110' ) && $has( $all, 'DTEND;VALUE=DATE:20261111' ), 'an all-day event ends on the day after (exclusive end)' );
$one = $lines_of( $ny );
$check( $has( $one, 'DTSTART;TZID=America/New_York:20261105T190000' ) && $has( $one, 'DTEND;TZID=America/New_York:20261105T203000' ), 'a timed event keeps its own zone and clock time' );
$check( $has( $lines_of( $weekly ), 'RRULE:FREQ=WEEKLY;BYDAY=TU;WKST=MO;COUNT=4' ) && $has( $lines_of( $weekly ), 'EXDATE;TZID=Europe/Vienna:20261110T183000' ), 'a repeat becomes RRULE and EXDATE' );
$dst = $lines_of( $vienna );
$check( $has( $dst, 'DTSTART;TZID=Europe/Vienna:20261020T183000' ), 'a repeat starts in its own zone' );
$feed = basix_events_ics( array( get_post( $ny ), get_post( $weekly ) ), 'Test' );
$check( 0 === strpos( $feed, "BEGIN:VCALENDAR\r\n" ) && "END:VCALENDAR\r\n" === substr( $feed, -15 ), 'a calendar starts and ends right' );
$check( substr_count( $feed, 'BEGIN:' ) === substr_count( $feed, 'END:' ), 'every BEGIN has an END' );
$check( false !== strpos( $feed, "BEGIN:VTIMEZONE\r\nTZID:America/New_York" ) && false !== strpos( $feed, "BEGIN:VTIMEZONE\r\nTZID:Europe/Vienna" ), 'each used zone has a VTIMEZONE' );
$check( false !== strpos( $feed, 'TZOFFSETFROM:-0400' ) && false !== strpos( $feed, 'TZOFFSETTO:-0500' ), 'the zone blocks carry the daylight saving changes' );
$check( 1 === substr_count( $feed, 'UID:basix-event-' . $ny . '@' ), 'an event has one stable UID' );

// A weekly rule that skips the start date's own weekday: the start date stands alone.
$odd      = $make(
	array(
		'basix_event_start'  => '2026-11-04',
		'basix_event_repeat' => $repeat( array( 'freq' => 'weekly', 'weekdays' => array( 2, 4 ), 'end' => array( 'type' => 'count', 'count' => 4 ) ) ),
	)
);
$odd_text = implode( "\n", $lines_of( $odd ) );
$check( 2 === substr_count( $odd_text, 'BEGIN:VEVENT' ) && false !== strpos( $odd_text, 'DTSTART;VALUE=DATE:20261104' ) && false !== strpos( $odd_text, 'DTSTART;VALUE=DATE:20261105' ) && false !== strpos( $odd_text, 'COUNT=3' ), 'a weekly rule without the start weekday becomes two events' );

// The feed leaves out what is not public, and follows changes.
$secret = $make(
	array(
		'basix_event_start' => wp_date( 'Y-m-d', time() + 5 * DAY_IN_SECONDS ),
		'basix_event_end'   => wp_date( 'Y-m-d', time() + 5 * DAY_IN_SECONDS ),
	)
);
wp_update_post(
	array(
		'ID'            => $secret,
		'post_password' => 'secret',
	)
);
$listed = static function ( int $id ): bool {
	return in_array( $id, wp_list_pluck( basix_events_upcoming_posts(), 'ID' ), true );
};
$check( ! $listed( $secret ), 'a password protected event is not in the feed' );
wp_update_post(
	array(
		'ID'            => $secret,
		'post_password' => '',
		'post_status'   => 'private',
	)
);
$check( ! $listed( $secret ), 'a private event is not in the feed' );
wp_update_post(
	array(
		'ID'          => $secret,
		'post_status' => 'publish',
	)
);
$check( $listed( $secret ), 'a public one is' );
$before = basix_events_feed_text( array() );
$check( basix_events_feed_text( array() ) === $before, 'the feed is cached' );
wp_update_post(
	array(
		'ID'         => $secret,
		'post_title' => 'Renamed for the feed',
	)
);
$check( false !== strpos( basix_events_feed_text( array() ), 'SUMMARY:Renamed for the feed' ), 'saving an event updates the cached feed' );
wp_set_object_terms( $secret, 'Meetups', BASIX_EVENT_CAT );
$check( false !== strpos( basix_events_feed_text( array() ), 'CATEGORIES:Meetups' ), 'and so does changing its categories' );
$check( ! in_array( $secret, wp_list_pluck( basix_events_upcoming_posts( array( 'workshops' ) ), 'ID' ), true ) && in_array( $secret, wp_list_pluck( basix_events_upcoming_posts( array( 'meetups' ) ), 'ID' ), true ), 'the feed can be limited to a category' );

// Structured data: the Event nodes.
$nodes_for = static function ( int $id ): array {
	return basix_schema_events( get_post( $id ) );
};
$one_node = $nodes_for( $ny );
$check( 1 === count( $one_node ) && '2026-11-05T19:00:00-05:00' === $one_node[0]['startDate'] && '2026-11-05T20:30:00-05:00' === $one_node[0]['endDate'], 'an event node carries the offset of the event\'s own zone' );
$all_node = $nodes_for( $day );
$check( '2026-11-10' === $all_node[0]['startDate'] && '2026-11-10' === $all_node[0]['endDate'], 'an all-day event node has dates only' );
// Starting tomorrow, so all four dates are still to come whenever this runs.
$soon       = $make(
	array(
		'basix_event_start'  => wp_date( 'Y-m-d', time() + DAY_IN_SECONDS ),
		'basix_event_repeat' => $repeat( array( 'freq' => 'weekly', 'end' => array( 'type' => 'count', 'count' => 4 ) ) ),
	)
);
$soon_nodes = $nodes_for( $soon );
$check( 4 === count( $soon_nodes ) && count( array_unique( array_column( $soon_nodes, 'url' ) ) ) === 4, 'a repeating event gives one node for each coming date, each with its own address' );
update_post_meta( $plain, 'basix_event_location', 'Online' );
$online = $nodes_for( $plain );
$check( 'VirtualLocation' === $online[0]['location']['@type'] && false !== strpos( $online[0]['eventAttendanceMode'], 'Online' ), 'an online event has a virtual location' );
update_post_meta( $plain, 'basix_event_location', 'Studio 4, Vienna' );
$check( 'Place' === $nodes_for( $plain )[0]['location']['@type'], 'and a place has an address' );

// What the page head writes, with and without an SEO plugin (which writes everything but events itself).
$_SERVER['SERVER_NAME'] = 'localhost';
$GLOBALS['wp_query'] = new WP_Query( array( 'p' => $ny, 'post_type' => 'basix-event' ) );
$GLOBALS['post']     = get_post( $ny );
$head = static function (): string {
	ob_start();
	do_action( 'wp_head' );
	return (string) ob_get_clean();
};
$page = $head();
$check( false !== strpos( $page, '"@type":"Organization"' ) && false !== strpos( $page, '"@type":"Event"' ) && false !== strpos( $page, 'property="og:title"' ), 'the head has the organisation, the event and the social tags' );
define( 'WPSEO_VERSION', '1' );
$page = $head();
$check( false !== strpos( $page, '"@type":"Event"' ) && false === strpos( $page, '"@type":"Organization"' ) && false === strpos( $page, 'property="og:title"' ), 'with an SEO plugin only the event is written' );

foreach ( $ids as $id ) {
	wp_delete_post( $id, true );
}
update_option( 'timezone_string', $original );
basix_events_rebuild_derived();

echo $failed ? "\nEvent check FAILED ($failed)\n" : "\nEvent check passed.\n";
exit( $failed ? 1 : 0 );
