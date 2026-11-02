<?php
/**
 * Creates sample events around today: some on one day, some with times, one across several days.
 * Run by seed-content.sh with `wp eval-file`. Existing sample events are replaced.
 */

foreach ( get_posts( array( 'post_type' => 'basix-event', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => 'basix_sample', 'meta_value' => '1' ) ) as $old ) {
	wp_delete_post( $old->ID, true );
}

// title, days from today, start time, end days from today, end time, location, text, category, time zone (optional), repeat rule (optional)
$events = array(
	array( 'Design systems meetup', 3, '18:30', 3, '20:30', 'Studio 4, Vienna', 'An evening of short talks on tokens, components and keeping a system alive.', 'Meetups' ),
	array( 'Accessibility clinic', 6, '10:00', 6, '12:00', 'Online', 'Bring a page and we will audit it together, live.', 'Workshops' ),
	array( 'WordPress theme workshop', 9, '', 9, '', 'Library hall', 'A full day of building a block-friendly theme from components.', 'Workshops' ),
	array( 'Conference: Interfaces 2026', 14, '', 16, '', 'Congress Center', 'Three days of talks and workshops about interface craft.', 'Conferences' ),
	array( 'Open office hours', 1, '15:00', 1, '16:00', 'Online', 'Ask anything about the Basix components.', 'Meetups' ),
	array( 'Release party', 21, '19:00', 21, '', 'Rooftop bar, New York', 'Celebrating the next release.', 'Meetups', 'America/New_York' ),
	array( 'Year in review', -35, '', -35, '', 'Online', 'Looking back at what shipped.', 'Conferences' ),
	array( 'Coffee and code', 2, '09:00', 2, '10:00', 'Café Letter', 'A weekly morning of pairing and small talk. Bring a laptop or just yourself.', 'Meetups', '', array( 'freq' => 'weekly', 'end' => array( 'type' => 'never' ) ) ),
	array( 'Typography reading group', -5, '17:00', -5, '18:30', 'Café Letter', 'Last week: notes on variable fonts.', 'Meetups' ),
);

$ids = array();
foreach ( $events as $e ) {
	list( $title, $from, $from_time, $to, $to_time, $place, $text, $category, $zone, $repeat ) = array_pad( $e, 10, '' );
	$ids[] = wp_insert_post(
		array(
			'post_type'    => 'basix-event',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_title'   => $title,
			'post_content' => "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->",
			'meta_input'   => array(
				'basix_sample'           => '1',
				'basix_event_start'      => wp_date( 'Y-m-d', strtotime( "{$from} days" ) ),
				'basix_event_start_time' => $from_time,
				'basix_event_end'        => wp_date( 'Y-m-d', strtotime( "{$to} days" ) ),
				'basix_event_end_time'   => $to_time,
				'basix_event_location'   => $place,
				'basix_event_timezone'   => $zone,
				'basix_event_repeat'     => $repeat ? wp_json_encode( $repeat ) : '',
			),
		)
	);
	wp_set_object_terms( end( $ids ), $category, 'basix-event-category' );
}
echo count( $ids ), " events\n";
