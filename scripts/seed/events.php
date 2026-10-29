<?php
/**
 * Creates sample events around today: some on one day, some with times, one across several days.
 * Run by seed-content.sh with `wp eval-file`. Existing sample events are replaced.
 */

foreach ( get_posts( array( 'post_type' => 'basix-event', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => 'basix_sample', 'meta_value' => '1' ) ) as $old ) {
	wp_delete_post( $old->ID, true );
}

// title, days from today, start time, end days from today, end time, location, text
$events = array(
	array( 'Design systems meetup', 3, '18:30', 3, '20:30', 'Studio 4, Vienna', 'An evening of short talks on tokens, components and keeping a system alive.' ),
	array( 'Accessibility clinic', 6, '10:00', 6, '12:00', 'Online', 'Bring a page and we will audit it together, live.' ),
	array( 'WordPress theme workshop', 9, '', 9, '', 'Library hall', 'A full day of building a block-friendly theme from components.' ),
	array( 'Conference: Interfaces 2026', 14, '', 16, '', 'Congress Center', 'Three days of talks and workshops about interface craft.' ),
	array( 'Open office hours', 1, '15:00', 1, '16:00', 'Online', 'Ask anything about the Basix components.' ),
	array( 'Release party', 21, '19:00', 21, '', 'Rooftop bar', 'Celebrating the next release.' ),
	array( 'Year in review', -35, '', -35, '', 'Online', 'Looking back at what shipped.' ),
	array( 'Typography reading group', -5, '17:00', -5, '18:30', 'Café Letter', 'Last week: notes on variable fonts.' ),
);

$ids = array();
foreach ( $events as $e ) {
	list( $title, $from, $from_time, $to, $to_time, $place, $text ) = $e;
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
			),
		)
	);
}
echo count( $ids ), " events\n";
