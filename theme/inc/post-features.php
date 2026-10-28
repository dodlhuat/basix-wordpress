<?php
/**
 * Features of single posts and the front page: reading time, table of contents, related posts,
 * share links and featured posts. Each one can be switched off in the Customizer.
 *
 * @package basix
 */

/**
 * The on/off switches in the Customizer's "Posts" section: setting => label.
 *
 * @return array<string, string>
 */
function basix_feature_switches(): array {
	return array(
		'basix_show_reading_time' => __( 'Show the reading time', 'basix' ),
		'basix_show_toc'          => __( 'Show a table of contents on long posts', 'basix' ),
		'basix_show_progress'     => __( 'Show a reading progress bar', 'basix' ),
		'basix_show_share'        => __( 'Show share links', 'basix' ),
		'basix_show_author_box'   => __( 'Show the author box (when the author has a biography)', 'basix' ),
		'basix_show_related'      => __( 'Show related posts', 'basix' ),
		'basix_show_featured'     => __( 'Show sticky posts as a featured slider on the front page', 'basix' ),
	);
}

/**
 * Whether a post feature is switched on. Everything is on by default.
 *
 * @param string $key Setting name, for example basix_show_toc.
 * @return bool
 */
function basix_feature( string $key ): bool {
	return (bool) get_theme_mod( $key, true );
}

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_section(
			'basix_posts',
			array(
				'title' => __( 'Posts', 'basix' ),
				'panel' => 'basix',
			)
		);
		foreach ( basix_feature_switches() as $setting => $label ) {
			$wp_customize->add_setting(
				$setting,
				array(
					'default'           => true,
					'sanitize_callback' => 'basix_sanitize_checkbox',
				)
			);
			$wp_customize->add_control(
				$setting,
				array(
					'section' => 'basix_posts',
					'label'   => $label,
					'type'    => 'checkbox',
				)
			);
		}
	}
);

/**
 * Minutes it takes to read a post, at about 220 words a minute (at least one).
 *
 * @param int|WP_Post|null $post Post, defaults to the current one.
 * @return int
 */
function basix_reading_minutes( $post = null ): int {
	$text  = wp_strip_all_tags( strip_shortcodes( (string) get_post_field( 'post_content', $post ) ) );
	$words = preg_match_all( "/[\p{L}\p{N}'’-]+/u", $text );
	return max( 1, (int) ceil( (int) $words / 220 ) );
}

/**
 * The reading time as text, for example "4 min read".
 *
 * @param int|WP_Post|null $post Post, defaults to the current one.
 * @return string
 */
function basix_reading_time( $post = null ): string {
	/* translators: %s: number of minutes. */
	return sprintf( __( '%s min read', 'basix' ), number_format_i18n( basix_reading_minutes( $post ) ) );
}

/**
 * Gives every h2 and h3 of a post an anchor, and puts a table of contents (a Basix accordion) before the content.
 *
 * Only the main post of a single post view is touched, and only once.
 *
 * @param string $content Post content.
 * @return string
 */
function basix_content_headings( string $content ): string {
	static $done = false;
	if ( $done || ! is_singular( 'post' ) || ! in_the_loop() || ! is_main_query() || is_feed() ) {
		return $content;
	}
	$done = true;

	$items = array();
	$used  = array();

	$content = preg_replace_callback(
		'/<h([23])([^>]*)>(.*?)<\/h\1>/is',
		function ( array $m ) use ( &$items, &$used ): string {
			$level = (int) $m[1];
			$attrs = $m[2];
			$text  = trim( wp_strip_all_tags( $m[3] ) );
			if ( '' === $text ) {
				return $m[0];
			}
			if ( preg_match( '/\sid=(["\'])(.*?)\1/i', $attrs, $existing ) ) {
				$id = $existing[2];
			} else {
				$base = sanitize_title( $text );
				$id   = $base ? $base : 'section';
				$n    = 2;
				while ( in_array( $id, $used, true ) ) {
					$id = $base . '-' . $n;
					++$n;
				}
				$attrs .= ' id="' . esc_attr( $id ) . '"';
			}
			$used[]  = $id;
			$items[] = array(
				'level' => $level,
				'id'    => $id,
				'text'  => $text,
			);
			return '<h' . $level . $attrs . '>' . $m[3] . '</h' . $level . '>';
		},
		$content
	);

	if ( basix_feature( 'basix_show_toc' ) && count( $items ) >= 3 ) {
		$content = basix_toc_html( $items ) . $content;
	}
	return $content;
}
add_filter( 'the_content', 'basix_content_headings', 20 );

/**
 * The table of contents, as a Basix accordion that is open by default.
 *
 * @param array[] $items Headings with level, id and text.
 * @return string Escaped HTML.
 */
function basix_toc_html( array $items ): string {
	$list = '';
	foreach ( $items as $item ) {
		$list .= sprintf(
			'<li class="toc-item toc-level-%1$d"><a href="#%2$s">%3$s</a></li>',
			$item['level'],
			esc_attr( $item['id'] ),
			esc_html( $item['text'] )
		);
	}
	return sprintf(
		'<div class="accordion toc"><div class="accordion-item"><input type="checkbox" id="toc-toggle" class="accordion-toggle" checked><label class="accordion-header" for="toc-toggle">%1$s</label><div class="accordion-content"><div class="accordion-body"><nav aria-label="%1$s"><ol class="toc-list">%2$s</ol></nav></div></div></div></div>',
		esc_html__( 'On this page', 'basix' ),
		$list
	);
}

/**
 * Ids of posts related to the current one: those sharing tags first, then those in the same categories.
 *
 * @param int $count How many to return at most.
 * @return int[]
 */
function basix_related_posts( int $count = 3 ): array {
	$post_id = get_the_ID();
	$found   = array();
	$exclude = array( $post_id );

	$sources = array(
		'post_tag' => wp_get_post_terms( $post_id, 'post_tag', array( 'fields' => 'ids' ) ),
		'category' => wp_get_post_terms( $post_id, 'category', array( 'fields' => 'ids' ) ),
	);

	foreach ( $sources as $taxonomy => $term_ids ) {
		if ( is_wp_error( $term_ids ) || ! $term_ids || count( $found ) >= $count ) {
			continue;
		}
		$query = new WP_Query(
			array(
				'post_type'           => 'post',
				'fields'              => 'ids',
				'posts_per_page'      => $count - count( $found ),
				'post__not_in'        => $exclude,
				'ignore_sticky_posts' => true,
				'no_found_rows'       => true,
				'tax_query'           => array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- small, bounded query.
					array(
						'taxonomy' => $taxonomy,
						'terms'    => $term_ids,
					),
				),
			)
		);
		foreach ( $query->posts as $related_id ) {
			$found[]   = $related_id;
			$exclude[] = $related_id;
		}
	}
	return $found;
}

/**
 * Where a post can be shared: label => URL. Email is first; the rest are common networks.
 *
 * @return array<string, string>
 */
function basix_share_links(): array {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( wp_strip_all_tags( get_the_title() ) );
	return array(
		__( 'Email', 'basix' ) => 'mailto:?subject=' . $title . '&body=' . $url,
		'X'                    => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
		'LinkedIn'             => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
		'Facebook'             => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
		'Bluesky'              => 'https://bsky.app/intent/compose?text=' . $title . '%20' . $url,
	);
}

/**
 * Ids of the sticky posts shown in the featured slider on the front page (a slider needs two or more).
 *
 * @return int[]
 */
function basix_featured_ids(): array {
	$sticky = get_option( 'sticky_posts' );
	if ( ! basix_feature( 'basix_show_featured' ) || ! is_array( $sticky ) || count( $sticky ) < 2 ) {
		return array();
	}
	return array_slice( array_map( 'intval', $sticky ), 0, 5 );
}

// The featured posts get their own slider, so keep them out of the latest-posts list.
add_action(
	'pre_get_posts',
	function ( WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! $query->is_home() ) {
			return;
		}
		$featured = basix_featured_ids();
		if ( $featured ) {
			$query->set( 'ignore_sticky_posts', true );
			$query->set( 'post__not_in', $featured );
		}
	}
);
