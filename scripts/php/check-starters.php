<?php
/**
 * Checks the starter sites: each one imports what it promises, marks it, and undoing it puts the site back exactly as
 * it was, without touching what the owner made. Run with `wp eval-file /php/check-starters.php` (make check does).
 * It leaves the site as it found it.
 */

$failed = 0;
$check  = static function ( bool $ok, string $what ) use ( &$failed ) {
	echo ( $ok ? 'ok   ' : 'FAIL ' ), $what, "\n";
	if ( ! $ok ) {
		++$failed;
	}
};

/** Everything an import could change, as one comparable value. */
$snapshot = static function (): string {
	$counts = array();
	foreach ( get_post_types() as $type ) {
		$counts[ 'type:' . $type ] = (int) ( new WP_Query( array( 'post_type' => $type, 'post_status' => 'any', 'posts_per_page' => 1, 'fields' => 'ids' ) ) )->found_posts;
	}
	foreach ( array( 'category', 'post_tag', BASIX_EVENT_CAT, 'nav_menu' ) as $taxonomy ) {
		$counts[ 'terms:' . $taxonomy ] = (int) wp_count_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
	}
	$options = array();
	foreach ( array( 'show_on_front', 'page_on_front', 'page_for_posts', 'sticky_posts', 'sidebars_widgets', 'widget_text', 'widget_recent-posts', 'widget_tag_cloud' ) as $name ) {
		$value            = get_option( $name, '__none__' );
		$options[ $name ] = is_scalar( $value ) ? (string) $value : $value; // WordPress turns 0 into "0" and back; that is not a change.
	}
	$mods = (array) get_theme_mods();
	ksort( $mods );
	ksort( $counts );
	return (string) wp_json_encode( array( $counts, $options, $mods ) );
};

$original = $snapshot();
$mine     = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'My own page',
		'post_content' => 'Written by the owner.',
	)
);
$check( array() === basix_starter_record(), 'there is no earlier import to start with' );

foreach ( basix_starters() as $slug => $def ) {
	$title  = $def['title'];
	$counts = basix_starter_counts( $def );
	$before = $snapshot();
	$record = basix_starter_import( $slug );
	$check( is_array( $record ) && $slug === $record['slug'], "$title: imports" );
	if ( ! is_array( $record ) ) {
		continue;
	}

	$pages = get_posts( array( 'post_type' => 'page', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => BASIX_STARTER_META, 'meta_value' => $slug ) );
	$posts = get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => BASIX_STARTER_META, 'meta_value' => $slug ) );
	$evnts = get_posts( array( 'post_type' => BASIX_EVENT, 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => BASIX_STARTER_META, 'meta_value' => $slug ) );
	$check( count( $pages ) === $counts['pages'] && count( $posts ) === $counts['posts'] && count( $evnts ) === $counts['events'], "$title: creates {$counts['pages']} pages, {$counts['posts']} posts and {$counts['events']} events" );
	$empty = array();
	foreach ( $def['pages'] as $page_def ) {
		if ( is_callable( $page_def['content'] ) ) {
			$made = array_filter( $pages, static function ( $page ) use ( $page_def ) {
				return $page->post_title === $page_def['title'];
			} );
			if ( ! $made || '' === trim( reset( $made )->post_content ) ) {
				$empty[] = $page_def['title'];
			}
		}
	}
	$check( ! $empty, "$title: every page with content has content" . ( $empty ? ' (empty: ' . implode( ', ', $empty ) . ')' : '' ) );
	$check( get_theme_mod( 'basix_look' ) === $def['look'], "$title: sets its look ({$def['look']})" );
	$front = get_option( 'show_on_front' );
	$check( 'posts' === $def['front'] ? 'posts' === $front : ( 'page' === $front && get_post_meta( (int) get_option( 'page_on_front' ), BASIX_STARTER_META, true ) === $slug ), "$title: sets the front page" );
	$locations = (array) get_theme_mod( 'nav_menu_locations' );
	$items     = ! empty( $locations['primary'] ) ? wp_get_nav_menu_items( (int) $locations['primary'] ) : array();
	$check( count( $items ) === count( $def['menus']['primary'] ), "$title: the primary menu has its " . count( $def['menus']['primary'] ) . ' items' );
	$check( ! empty( $locations['footer'] ), "$title: the footer menu is set" );
	foreach ( $posts as $post ) {
		if ( ! has_post_thumbnail( $post ) ) {
			$check( false, "$title: {$post->post_title} has a picture" );
		}
	}
	$check( ! $posts || has_post_thumbnail( $posts[0] ), "$title: sample posts have pictures" );
	if ( $def['sticky'] ) {
		$check( count( (array) get_option( 'sticky_posts' ) ) === $def['sticky'], "$title: makes {$def['sticky']} posts sticky" );
	}
	if ( $counts['events'] ) {
		$check( count( basix_events_upcoming_posts() ) >= 1 && false !== strpos( basix_events_feed_text( array() ), 'RRULE' ), "$title: the events and a repeat reach the calendar feed" );
	}
	$check( ! empty( $record['urls'] ) && count( $record['urls'] ) >= 2, "$title: records its addresses" );

	basix_starter_undo();
	$check( $snapshot() === $before, "$title: undo puts everything back as it was" );
	$check( array() === basix_starter_record(), "$title: and forgets the import" );
	$check( 'My own page' === get_the_title( $mine ), "$title: leaves the owner's own page alone" );
}

// A second import replaces the first one.
basix_starter_import( 'blog' );
$blog_posts = count( get_posts( array( 'post_type' => 'post', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => BASIX_STARTER_META, 'meta_value' => 'blog' ) ) );
basix_starter_import( 'business' );
$left = count( get_posts( array( 'post_type' => 'any', 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => BASIX_STARTER_META, 'meta_value' => 'blog' ) ) );
$check( $blog_posts > 0 && 0 === $left, 'importing another starter undoes the earlier one first' );
basix_starter_undo();

// A look chosen at import time wins over the starter's own.
basix_starter_import( 'blog', 'paper' );
$check( 'paper' === get_theme_mod( 'basix_look' ), 'a look chosen at import time wins' );
basix_starter_undo();

// What the owner had chosen comes back after an undo.
$kept = array(
	'basix_accent_light' => get_theme_mod( 'basix_accent_light', null ),
	'basix_text_size'    => get_theme_mod( 'basix_text_size', null ),
);
set_theme_mod( 'basix_accent_light', '#aa2200' );
set_theme_mod( 'basix_text_size', 'large' );
$before = $snapshot();
basix_starter_import( 'magazine' );
$check( false === get_theme_mod( 'basix_accent_light', false ) && false === get_theme_mod( 'basix_text_size', false ), 'an import clears the owner\'s accent and text size choices' );
basix_starter_undo();
$check( '#aa2200' === get_theme_mod( 'basix_accent_light' ) && 'large' === get_theme_mod( 'basix_text_size' ) && $snapshot() === $before, 'and an undo brings them back' );
foreach ( $kept as $name => $value ) {
	if ( null === $value ) {
		remove_theme_mod( $name );
	} else {
		set_theme_mod( $name, $value );
	}
}

// Undo with nothing to undo is fine.
$check( array() === basix_starter_undo(), 'undo without an import does nothing' );

wp_delete_post( $mine, true );
$same_as_start = $snapshot() === $original;
$check( $same_as_start, 'the site is as it was at the start' );
if ( ! $same_as_start ) {
	// Say what differs, so a failure can be understood.
	$then = json_decode( $original, true );
	$now  = json_decode( $snapshot(), true );
	foreach ( array( 'counts', 'options', 'mods' ) as $index => $part ) {
		foreach ( array_unique( array_merge( array_keys( $then[ $index ] ), array_keys( $now[ $index ] ) ) ) as $key ) {
			if ( ( $then[ $index ][ $key ] ?? null ) !== ( $now[ $index ][ $key ] ?? null ) ) {
				echo "     $part $key: ", wp_json_encode( $then[ $index ][ $key ] ?? null ), ' => ', wp_json_encode( $now[ $index ][ $key ] ?? null ), "\n";
			}
		}
	}
}

echo $failed ? "\nStarter check FAILED ($failed)\n" : "\nStarter check passed.\n";
exit( $failed ? 1 : 0 );
