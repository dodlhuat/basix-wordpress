<?php
/**
 * Starter sites: import and undo.
 *
 * A starter site is a definition in /starters (a look, Customizer values, pages built from patterns, menus, sample
 * posts and events). Importing it creates content, marks everything it creates, and records what it changed, so that
 * undoing it removes exactly what was added and puts the changed settings back. Nothing the owner made is touched.
 *
 * @package basix
 */

/** Marks a post, page, attachment or event created by a starter site; the value is the starter's slug. */
const BASIX_STARTER_META = '_basix_starter';

/** The option that remembers the last import. */
const BASIX_STARTER_OPTION = 'basix_starter';

/**
 * Every starter site: slug => definition. A starter whose file is missing or broken is left out.
 *
 * @return array<string, array>
 */
function basix_starters(): array {
	static $starters = null;
	if ( null !== $starters ) {
		return $starters;
	}
	$starters = array();
	foreach ( array( 'blog', 'magazine', 'business', 'events' ) as $slug ) {
		// The events starter needs the Basix Events plugin.
		if ( 'events' === $slug && ! basix_has_events() ) {
			continue;
		}
		$file = get_theme_file_path( 'starters/' . $slug . '.php' );
		if ( file_exists( $file ) ) {
			$definition = include $file;
			if ( is_array( $definition ) ) {
				$starters[ $slug ] = $definition;
			}
		}
	}
	return $starters;
}

/**
 * What a starter would create, as numbers for the setup screen.
 *
 * @param array $definition A starter definition.
 * @return array{pages: int, posts: int, events: int}
 */
function basix_starter_counts( array $definition ): array {
	return array(
		'pages'  => count( $definition['pages'] ?? array() ),
		'posts'  => count( $definition['posts'] ?? array() ),
		'events' => ! empty( $definition['events'] ) ? count( basix_starter_events() ) : 0,
	);
}

/**
 * The record of the last import, or an empty array.
 *
 * @return array
 */
function basix_starter_record(): array {
	$record = get_option( BASIX_STARTER_OPTION, array() );
	return is_array( $record ) ? $record : array();
}

/**
 * Remembers the current value of a Customizer setting or an option before the import changes it.
 *
 * @param array  $record Import record, changed.
 * @param string $kind   mods or options.
 * @param string $name   Name.
 */
function basix_starter_remember( array &$record, string $kind, string $name ): void {
	if ( array_key_exists( $name, $record['before'][ $kind ] ) ) {
		return;
	}
	if ( 'mods' === $kind ) {
		$mods                               = (array) get_theme_mods();
		$record['before'][ $kind ][ $name ] = array_key_exists( $name, $mods ) ? array( 'value' => $mods[ $name ] ) : array();
		return;
	}
	$missing                            = '__basix_missing__';
	$value                              = get_option( $name, $missing );
	$record['before'][ $kind ][ $name ] = $missing === $value ? array() : array( 'value' => $value );
}

/**
 * Puts a Customizer setting or option back (an empty entry means it did not exist).
 *
 * @param string $kind  mods or options.
 * @param string $name  Name.
 * @param array  $saved Remembered entry.
 */
function basix_starter_restore( string $kind, string $name, array $saved ): void {
	if ( 'mods' === $kind ) {
		if ( array_key_exists( 'value', $saved ) ) {
			set_theme_mod( $name, $saved['value'] );
		} else {
			remove_theme_mod( $name );
		}
		return;
	}
	if ( array_key_exists( 'value', $saved ) ) {
		update_option( $name, $saved['value'] );
	} else {
		delete_option( $name );
	}
}

/**
 * Finds or creates a term, remembering it when it is new.
 *
 * @param array  $record   Import record, changed.
 * @param string $name     Term name.
 * @param string $taxonomy Taxonomy.
 * @return int Term id, or 0.
 */
function basix_starter_term( array &$record, string $name, string $taxonomy ): int {
	$existing = term_exists( $name, $taxonomy );
	if ( $existing ) {
		return (int) ( is_array( $existing ) ? $existing['term_id'] : $existing );
	}
	$made = wp_insert_term( $name, $taxonomy );
	if ( is_wp_error( $made ) ) {
		return 0;
	}
	$record['created']['terms'][] = array( $taxonomy, (int) $made['term_id'] );
	return (int) $made['term_id'];
}

/**
 * Adds a picture of the theme to the media library once per import.
 *
 * @param array  $record Import record, changed.
 * @param string $file   File name in assets/images.
 * @param string $title  Title.
 * @return int Attachment id, or 0.
 */
function basix_starter_image( array &$record, string $file, string $title ): int {
	static $done = array();
	$key         = $record['slug'] . '|' . $file . '|' . $record['time'];
	if ( isset( $done[ $key ] ) ) {
		return $done[ $key ];
	}
	$source = get_theme_file_path( 'assets/images/' . $file );
	if ( ! file_exists( $source ) ) {
		return 0;
	}
	require_once ABSPATH . 'wp-admin/includes/file.php';
	require_once ABSPATH . 'wp-admin/includes/media.php';
	require_once ABSPATH . 'wp-admin/includes/image.php';
	$tmp = wp_tempnam( $file );
	if ( ! $tmp || ! copy( $source, $tmp ) ) {
		return 0;
	}
	$id = media_handle_sideload(
		array(
			'name'     => $file,
			'tmp_name' => $tmp,
		),
		0,
		$title
	);
	if ( is_wp_error( $id ) ) {
		wp_delete_file( $tmp );
		return 0;
	}
	update_post_meta( $id, BASIX_STARTER_META, $record['slug'] );
	update_post_meta( $id, '_wp_attachment_image_alt', '' );
	$record['created']['posts'][] = (int) $id;
	$done[ $key ]                 = (int) $id;
	return (int) $id;
}

/**
 * Creates a post of any type and remembers it.
 *
 * @param array $record Import record, changed.
 * @param array $args   wp_insert_post arguments.
 * @return int Post id, or 0.
 */
function basix_starter_post( array &$record, array $args ): int {
	$args               = array_merge(
		array(
			'post_status' => 'publish',
			'post_author' => get_current_user_id() ? get_current_user_id() : 1,
		),
		$args
	);
	$args['meta_input'] = array_merge( (array) ( $args['meta_input'] ?? array() ), array( BASIX_STARTER_META => $record['slug'] ) );
	$id                 = wp_insert_post( $args, true );
	if ( is_wp_error( $id ) || ! $id ) {
		return 0;
	}
	$record['created']['posts'][] = (int) $id;
	return (int) $id;
}

/**
 * Imports a starter site. An earlier import is undone first.
 *
 * @param string $slug Starter slug.
 * @param string $look Look slug to use instead of the starter's own (empty for the starter's).
 * @return array|WP_Error The import record, or an error.
 */
function basix_starter_import( string $slug, string $look = '' ) {
	$starters = basix_starters();
	if ( ! isset( $starters[ $slug ] ) ) {
		return new WP_Error( 'basix_starter', __( 'That starter site does not exist.', 'basix' ) );
	}
	$def = $starters[ $slug ];
	if ( '' === $look || ! isset( basix_looks()[ $look ] ) ) {
		$look = (string) $def['look'];
	}
	if ( basix_starter_record() ) {
		basix_starter_undo();
	}

	$record = array(
		'slug'    => $slug,
		'look'    => $look,
		'time'    => time(),
		'created' => array(
			'posts' => array(),
			'terms' => array(),
			'menus' => array(),
		),
		'before'  => array(
			'mods'    => array(),
			'options' => array(),
		),
		'urls'    => array(),
	);
	// Saved first, so an import that stops half way can still be undone.
	update_option( BASIX_STARTER_OPTION, $record, false );

	// Settings: the look and the Customizer values; the owner's accent, font and size choices step aside.
	$mods = array_merge( (array) ( $def['mods'] ?? array() ), array( 'basix_look' => $look ) );
	foreach ( array( 'basix_accent_light', 'basix_accent_dark', 'basix_font_heading', 'basix_font_body', 'basix_text_size' ) as $name ) {
		basix_starter_remember( $record, 'mods', $name );
		remove_theme_mod( $name );
	}
	foreach ( $mods as $name => $value ) {
		basix_starter_remember( $record, 'mods', $name );
		set_theme_mod( $name, $value );
	}

	// Pages.
	$pages = array();
	foreach ( (array) $def['pages'] as $key => $page ) {
		$content = is_callable( $page['content'] ) ? (string) call_user_func( $page['content'] ) : (string) $page['content'];
		$meta    = array();
		if ( ! empty( $page['template'] ) ) {
			$meta['_wp_page_template'] = $page['template'];
		}
		$pages[ $key ] = basix_starter_post(
			$record,
			array(
				'post_type'    => 'page',
				'post_title'   => $page['title'],
				'post_name'    => sanitize_title( $key ),
				'post_content' => $content,
				'meta_input'   => $meta,
			)
		);
	}

	// Front page and posts page.
	foreach ( array( 'show_on_front', 'page_on_front', 'page_for_posts' ) as $name ) {
		basix_starter_remember( $record, 'options', $name );
	}
	if ( 'posts' === $def['front'] ) {
		update_option( 'show_on_front', 'posts' );
		update_option( 'page_on_front', 0 );
		update_option( 'page_for_posts', 0 );
	} else {
		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', $pages[ $def['front'] ] ?? 0 );
		update_option( 'page_for_posts', ! empty( $def['blog'] ) ? ( $pages[ $def['blog'] ] ?? 0 ) : 0 );
	}

	// Sample posts, with pictures.
	$library = basix_starter_posts();
	$posts   = array();
	foreach ( (array) $def['posts'] as $key ) {
		if ( ! isset( $library[ $key ] ) ) {
			continue;
		}
		$item = $library[ $key ];
		$cat  = basix_starter_term( $record, $item['category'], 'category' );
		foreach ( $item['tags'] as $tag ) {
			basix_starter_term( $record, $tag, 'post_tag' );
		}
		$stamp = time() - $item['days'] * DAY_IN_SECONDS - HOUR_IN_SECONDS * 3;
		$id    = basix_starter_post(
			$record,
			array(
				'post_type'     => 'post',
				'post_title'    => $item['title'],
				'post_content'  => $item['content'],
				'post_date'     => wp_date( 'Y-m-d H:i:s', $stamp ),
				'post_date_gmt' => gmdate( 'Y-m-d H:i:s', $stamp ),
				'post_category' => $cat ? array( $cat ) : array(),
				'tags_input'    => $item['tags'],
			)
		);
		if ( $id ) {
			$image = basix_starter_image( $record, $item['image'], $item['title'] );
			if ( $image ) {
				set_post_thumbnail( $id, $image );
			}
			$posts[] = $id;
		}
	}
	basix_starter_remember( $record, 'options', 'sticky_posts' );
	$sticky = (int) ( $def['sticky'] ?? 0 );
	update_option( 'sticky_posts', $sticky ? array_slice( $posts, 0, $sticky ) : array() );

	// Events.
	if ( ! empty( $def['events'] ) && basix_has_events() ) {
		foreach ( basix_starter_events() as $event ) {
			list( $title, $days, $from, $to, $place, $text, $category, $repeat ) = $event;
			$date = wp_date( 'Y-m-d', strtotime( $days . ' days' ) );
			$id   = basix_starter_post(
				$record,
				array(
					'post_type'    => BASIX_EVENT,
					'post_title'   => $title,
					'post_content' => basix_sc_p( $text ),
					'meta_input'   => array(
						'basix_event_start'      => $date,
						'basix_event_start_time' => $from,
						'basix_event_end'        => $date,
						'basix_event_end_time'   => $to,
						'basix_event_location'   => $place,
						'basix_event_repeat'     => $repeat ? wp_json_encode( $repeat ) : '',
					),
				)
			);
			$term = basix_starter_term( $record, $category, BASIX_EVENT_CAT );
			if ( $id && $term ) {
				wp_set_object_terms( $id, array( $term ), BASIX_EVENT_CAT );
			}
		}
	}

	// Menus.
	$locations = (array) get_theme_mod( 'nav_menu_locations', array() );
	basix_starter_remember( $record, 'mods', 'nav_menu_locations' );
	foreach ( (array) $def['menus'] as $location => $items ) {
		$menu = wp_create_nav_menu( sprintf( '%s (%s)', $def['title'], 'primary' === $location ? __( 'Primary', 'basix' ) : __( 'Footer', 'basix' ) ) );
		if ( is_wp_error( $menu ) ) {
			// A menu with that name exists already (an earlier import); take a unique one.
			$menu = wp_create_nav_menu( sprintf( '%s (%s) %d', $def['title'], $location, time() ) );
		}
		if ( is_wp_error( $menu ) ) {
			continue;
		}
		$record['created']['menus'][] = (int) $menu;
		foreach ( $items as $item ) {
			$args = array( 'menu-item-status' => 'publish' );
			if ( isset( $pages[ $item ] ) && $pages[ $item ] ) {
				$args += array(
					'menu-item-type'      => 'post_type',
					'menu-item-object'    => 'page',
					'menu-item-object-id' => $pages[ $item ],
				);
			} elseif ( 'events-archive' === $item ) {
				$args += array(
					'menu-item-type'  => 'custom',
					'menu-item-title' => __( 'Events', 'basix' ),
					'menu-item-url'   => get_post_type_archive_link( BASIX_EVENT ),
				);
			} elseif ( 'home' === $item ) {
				$args += array(
					'menu-item-type'  => 'custom',
					'menu-item-title' => __( 'Home', 'basix' ),
					'menu-item-url'   => home_url( '/' ),
				);
			} else {
				continue;
			}
			wp_update_nav_menu_item( $menu, 0, $args );
		}
		$locations[ $location ] = (int) $menu;
	}
	set_theme_mod( 'nav_menu_locations', $locations );

	// Footer widgets, only into areas that are empty.
	if ( ! empty( $def['widgets'] ) ) {
		basix_starter_widgets( $record );
	}

	$record['urls'] = basix_starter_urls( $pages, $posts, $def );
	update_option( BASIX_STARTER_OPTION, $record, false );
	if ( function_exists( 'basix_events_feed_flush' ) ) {
		basix_events_feed_flush();
	}
	do_action( 'basix_starter_imported', $slug, $record );
	return $record;
}

/**
 * Fills the three footer areas that are still empty with an About text, the latest posts and the tags.
 *
 * @param array $record Import record, changed.
 */
function basix_starter_widgets( array &$record ): void {
	foreach ( array( 'sidebars_widgets', 'widget_text', 'widget_recent-posts', 'widget_tag_cloud' ) as $name ) {
		basix_starter_remember( $record, 'options', $name );
	}
	$sidebars = (array) get_option( 'sidebars_widgets', array() );
	$plan     = array(
		'footer-1' => array(
			'text',
			array(
				'title'  => __( 'About', 'basix' ),
				'text'   => __( 'A small site built with the Basix theme. Say here what it is about.', 'basix' ),
				'filter' => false,
				'visual' => false,
			),
		),
		'footer-2' => array(
			'recent-posts',
			array(
				'title'  => __( 'Latest posts', 'basix' ),
				'number' => 3,
			),
		),
		'footer-3' => array(
			'tag_cloud',
			array(
				'title'    => __( 'Tags', 'basix' ),
				'taxonomy' => 'post_tag',
			),
		),
	);
	foreach ( $plan as $area => $widget ) {
		if ( ! empty( $sidebars[ $area ] ) ) {
			continue;
		}
		list( $type, $instance ) = $widget;
		$option                  = (array) get_option( 'widget_' . $type, array() );
		$numbers                 = array_filter( array_keys( $option ), 'is_int' );
		$number                  = $numbers ? max( $numbers ) + 1 : 2;
		$option[ $number ]       = $instance;
		$option['_multiwidget']  = 1;
		update_option( 'widget_' . $type, $option );
		$sidebars[ $area ] = array( $type . '-' . $number );
	}
	update_option( 'sidebars_widgets', $sidebars );
}

/**
 * The addresses of the main pages of an import, for the setup screen and the checks.
 *
 * @param array $pages Page ids by key.
 * @param array $posts Post ids.
 * @param array $def   Starter definition.
 * @return string[]
 */
function basix_starter_urls( array $pages, array $posts, array $def ): array {
	$urls = array( home_url( '/' ) );
	foreach ( $pages as $id ) {
		if ( $id ) {
			$urls[] = (string) get_permalink( $id );
		}
	}
	foreach ( array_slice( $posts, 0, 2 ) as $id ) {
		$urls[] = (string) get_permalink( $id );
	}
	if ( ! empty( $def['events'] ) && basix_has_events() ) {
		$urls[] = (string) get_post_type_archive_link( BASIX_EVENT );
		$urls[] = basix_events_feed_url();
	}
	return array_values( array_unique( $urls ) );
}

/**
 * Undoes the last import: deletes what it created and puts the changed settings back.
 *
 * @return array The record that was undone (empty when there was none).
 */
function basix_starter_undo(): array {
	$record = basix_starter_record();
	if ( ! $record ) {
		return array();
	}
	$ids = array_map( 'intval', (array) ( $record['created']['posts'] ?? array() ) );
	// Also anything still carrying the mark, in case the record is incomplete (an import that stopped half way).
	foreach ( get_posts(
		array(
			'post_type'      => 'any',
			'post_status'    => 'any',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'no_found_rows'  => true,
			'meta_key'       => BASIX_STARTER_META, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- one undo, a handful of posts.
			'meta_value'     => $record['slug'], // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value -- same.
		)
	) as $id ) {
		$ids[] = (int) $id;
	}
	foreach ( array_unique( $ids ) as $id ) {
		wp_delete_post( $id, true );
	}
	foreach ( (array) ( $record['created']['menus'] ?? array() ) as $menu ) {
		wp_delete_nav_menu( (int) $menu );
	}
	foreach ( (array) ( $record['created']['terms'] ?? array() ) as $term ) {
		wp_delete_term( (int) $term[1], (string) $term[0] );
	}
	foreach ( (array) ( $record['before']['options'] ?? array() ) as $name => $saved ) {
		basix_starter_restore( 'options', (string) $name, (array) $saved );
	}
	foreach ( (array) ( $record['before']['mods'] ?? array() ) as $name => $saved ) {
		basix_starter_restore( 'mods', (string) $name, (array) $saved );
	}
	delete_option( BASIX_STARTER_OPTION );
	if ( function_exists( 'basix_events_feed_flush' ) ) {
		basix_events_feed_flush();
	}
	do_action( 'basix_starter_undone', $record );
	return $record;
}
