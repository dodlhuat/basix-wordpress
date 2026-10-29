<?php
/**
 * Checks how events handle time zones. Run with `wp eval-file /php/check-events.php` (make check does).
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

foreach ( $ids as $id ) {
	wp_delete_post( $id, true );
}
update_option( 'timezone_string', $original );
basix_events_rebuild_derived();

echo $failed ? "\nEvent check FAILED ($failed)\n" : "\nEvent check passed.\n";
exit( $failed ? 1 : 0 );
