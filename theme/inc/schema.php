<?php
/**
 * Structured data and social tags.
 *
 * Writes JSON-LD (organisation, website, breadcrumbs, articles and events) and Open Graph and Twitter tags. Both
 * are left to a well-known SEO plugin when one is active, except for events: plugins do not know about them, so
 * those are always written. The basix_schema_graph filter changes the JSON-LD, and basix_open_graph the tags.
 *
 * @package basix
 */

/**
 * The site's logo as a schema.org ImageObject, or null.
 *
 * @return array|null
 */
function basix_schema_logo(): ?array {
	$logo = (int) get_theme_mod( 'custom_logo' );
	$src  = $logo ? wp_get_attachment_image_src( $logo, 'full' ) : false;
	if ( ! $src ) {
		return null;
	}
	return array(
		'@type'  => 'ImageObject',
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
	);
}

/**
 * The picture of a post as an ImageObject, or null.
 *
 * @param WP_Post $post Post.
 * @return array|null
 */
function basix_schema_image( WP_Post $post ): ?array {
	$id  = (int) get_post_thumbnail_id( $post );
	$src = $id ? wp_get_attachment_image_src( $id, 'large' ) : false;
	if ( ! $src ) {
		return null;
	}
	return array(
		'@type'  => 'ImageObject',
		'url'    => $src[0],
		'width'  => (int) $src[1],
		'height' => (int) $src[2],
	);
}

/**
 * The organisation (the site), with its social links as sameAs.
 *
 * @return array
 */
function basix_schema_organization(): array {
	$node = array(
		'@type' => 'Organization',
		'@id'   => home_url( '/#organization' ),
		'name'  => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'url'   => home_url( '/' ),
	);
	$logo = basix_schema_logo();
	if ( $logo ) {
		$node['logo'] = $logo;
	}
	$locations = get_nav_menu_locations();
	if ( ! empty( $locations['social'] ) ) {
		$links = array();
		foreach ( (array) wp_get_nav_menu_items( $locations['social'] ) as $item ) {
			if ( 0 === strpos( (string) $item->url, 'http' ) ) {
				$links[] = $item->url;
			}
		}
		if ( $links ) {
			$node['sameAs'] = array_values( array_unique( $links ) );
		}
	}
	return $node;
}

/**
 * The path to the current page as name and address pairs. The last one has no address, which is allowed.
 *
 * @return array<int, array{name: string, url?: string}>
 */
function basix_breadcrumb_trail(): array {
	if ( is_front_page() || is_404() ) {
		return array();
	}
	$trail = array(
		array(
			'name' => __( 'Home', 'basix' ),
			'url'  => home_url( '/' ),
		),
	);
	$terms = static function ( int $term_id, string $taxonomy ): array {
		$out = array();
		foreach ( array_reverse( get_ancestors( $term_id, $taxonomy, 'taxonomy' ) ) as $ancestor ) {
			$term = get_term( $ancestor, $taxonomy );
			if ( $term instanceof WP_Term ) {
				$out[] = array(
					'name' => $term->name,
					'url'  => get_term_link( $term ),
				);
			}
		}
		return $out;
	};
	$last = null;

	if ( is_singular( BASIX_EVENT ) ) {
		$trail[] = array(
			'name' => __( 'Events', 'basix' ),
			'url'  => get_post_type_archive_link( BASIX_EVENT ),
		);
		$last    = get_the_title();
	} elseif ( is_singular( 'post' ) ) {
		$cats = get_the_category();
		if ( $cats ) {
			$trail = array_merge( $trail, $terms( $cats[0]->term_id, 'category' ) );
			$trail[] = array(
				'name' => $cats[0]->name,
				'url'  => get_category_link( $cats[0] ),
			);
		}
		$last = get_the_title();
	} elseif ( is_singular() ) {
		foreach ( array_reverse( get_post_ancestors( get_queried_object() ) ) as $ancestor ) {
			$trail[] = array(
				'name' => get_the_title( $ancestor ),
				'url'  => get_permalink( $ancestor ),
			);
		}
		$last = get_the_title();
	} elseif ( is_post_type_archive( BASIX_EVENT ) ) {
		$last = __( 'Events', 'basix' );
	} elseif ( is_tax( BASIX_EVENT_CAT ) ) {
		$trail[] = array(
			'name' => __( 'Events', 'basix' ),
			'url'  => get_post_type_archive_link( BASIX_EVENT ),
		);
		$trail   = array_merge( $trail, $terms( get_queried_object_id(), BASIX_EVENT_CAT ) );
		$last    = single_term_title( '', false );
	} elseif ( is_category() ) {
		$trail = array_merge( $trail, $terms( get_queried_object_id(), 'category' ) );
		$last  = single_cat_title( '', false );
	} elseif ( is_home() ) {
		$last = get_the_title( (int) get_option( 'page_for_posts' ) );
	} elseif ( is_search() ) {
		$last = __( 'Search results', 'basix' );
	} else {
		$last = wp_strip_all_tags( get_the_archive_title() );
	}
	if ( $last ) {
		$trail[] = array( 'name' => html_entity_decode( (string) $last, ENT_QUOTES, 'UTF-8' ) );
	}
	return count( $trail ) > 1 ? $trail : array();
}

/**
 * The breadcrumb list as a schema.org node.
 *
 * @return array|null
 */
function basix_schema_breadcrumbs(): ?array {
	$trail = basix_breadcrumb_trail();
	if ( ! $trail ) {
		return null;
	}
	$items = array();
	foreach ( $trail as $index => $step ) {
		$item = array(
			'@type'    => 'ListItem',
			'position' => $index + 1,
			'name'     => $step['name'],
		);
		if ( isset( $step['url'] ) ) {
			$item['item'] = $step['url'];
		}
		$items[] = $item;
	}
	return array(
		'@type'           => 'BreadcrumbList',
		'itemListElement' => $items,
	);
}

/**
 * A blog post as an Article.
 *
 * @param WP_Post $post Post.
 * @return array
 */
function basix_schema_article( WP_Post $post ): array {
	$url  = get_permalink( $post );
	$node = array(
		'@type'            => 'Article',
		'@id'              => $url . '#article',
		'mainEntityOfPage' => $url,
		'headline'         => mb_strimwidth( html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ), 0, 110, '…' ),
		'datePublished'    => get_post_time( 'c', false, $post ),
		'dateModified'     => get_post_modified_time( 'c', false, $post ),
		'author'           => array(
			'@type' => 'Person',
			'name'  => get_the_author_meta( 'display_name', (int) $post->post_author ),
			'url'   => get_author_posts_url( (int) $post->post_author ),
		),
		'publisher'        => array( '@id' => home_url( '/#organization' ) ),
	);
	$description = basix_meta_description();
	if ( '' !== $description ) {
		$node['description'] = $description;
	}
	$image = basix_schema_image( $post );
	if ( $image ) {
		$node['image'] = $image;
	}
	$cats = get_the_category( $post->ID );
	if ( $cats ) {
		$node['articleSection'] = $cats[0]->name;
	}
	$tags = get_the_tags( $post->ID );
	if ( $tags ) {
		$node['keywords'] = implode( ', ', wp_list_pluck( $tags, 'name' ) );
	}
	return $node;
}

/**
 * The Event nodes of an event: one for an event on its own, and one for each coming date of a repeating event, as
 * search engines want repeats listed separately. Timed events carry their own zone's offset; all-day events only a date.
 *
 * @param WP_Post $post Event.
 * @return array[]
 */
function basix_schema_events( WP_Post $post ): array {
	$range = basix_event_range( $post->ID );
	if ( ! $range ) {
		return array();
	}
	$zone  = $range['all_day'] ? wp_timezone() : basix_event_zone( $post->ID );
	$rule  = basix_event_rule( $post->ID );
	$dates = array( null );
	if ( $rule ) {
		$dates = array();
		$shown = basix_event_shown_occurrence( $post->ID );
		if ( $shown ) {
			$dates[ $shown['date'] ] = $shown;
		}
		foreach ( basix_event_upcoming( $post->ID, 5 ) as $occurrence ) {
			$dates[ $occurrence['date'] ] = $occurrence;
		}
		ksort( $dates );
	}
	$place     = (string) get_post_meta( $post->ID, 'basix_event_location', true );
	$image     = basix_schema_image( $post );
	$text      = trim( wp_strip_all_tags( $post->post_excerpt ? $post->post_excerpt : strip_shortcodes( $post->post_content ) ) );
	$text      = wp_trim_words( $text, 60, '…' );
	$permalink = get_permalink( $post );
	$out       = array();
	foreach ( $dates as $occurrence ) {
		$start = $occurrence ? $occurrence['own_start'] : $range['start'];
		$end   = $occurrence ? $occurrence['own_end'] : $range['end'];
		$url   = $occurrence ? add_query_arg( 'date', $occurrence['date'], $permalink ) : $permalink;
		$node  = array(
			'@type'               => 'Event',
			'@id'                 => $url . '#event',
			'name'                => html_entity_decode( get_the_title( $post ), ENT_QUOTES, 'UTF-8' ),
			'url'                 => $url,
			'startDate'           => $range['all_day'] ? substr( $start, 0, 10 ) : ( new DateTimeImmutable( $start, $zone ) )->format( 'c' ),
			'endDate'             => $range['all_day'] ? substr( $end, 0, 10 ) : ( new DateTimeImmutable( $end, $zone ) )->format( 'c' ),
			'eventStatus'         => 'https://schema.org/EventScheduled',
			'organizer'           => array( '@id' => home_url( '/#organization' ) ),
			'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
		);
		if ( '' !== $place ) {
			if ( preg_match( '/^(online|virtual|webinar)$/i', $place ) ) {
				$node['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
				$node['location']            = array(
					'@type' => 'VirtualLocation',
					'url'   => $permalink,
				);
			} else {
				$node['location'] = array(
					'@type'   => 'Place',
					'name'    => $place,
					'address' => $place,
				);
			}
		}
		if ( '' !== $text ) {
			$node['description'] = $text;
		}
		if ( $image ) {
			$node['image'] = $image;
		}
		$out[] = $node;
	}
	return $out;
}

/**
 * The nodes for the current page.
 *
 * @param bool $events_only Only the Event nodes (used when an SEO plugin writes the rest).
 * @return array[]
 */
function basix_schema_nodes( bool $events_only = false ): array {
	$nodes = array();
	$post  = is_singular() ? get_queried_object() : null;
	if ( $post instanceof WP_Post && post_password_required( $post ) ) {
		return array();
	}
	if ( ! $events_only ) {
		$nodes[] = basix_schema_organization();
		if ( is_front_page() ) {
			$nodes[] = array(
				'@type'           => 'WebSite',
				'@id'             => home_url( '/#website' ),
				'url'             => home_url( '/' ),
				'name'            => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
				'publisher'       => array( '@id' => home_url( '/#organization' ) ),
				'potentialAction' => array(
					'@type'       => 'SearchAction',
					'target'      => array(
						'@type'       => 'EntryPoint',
						'urlTemplate' => home_url( '/?s={search_term_string}' ),
					),
					'query-input' => 'required name=search_term_string',
				),
			);
		}
		$crumbs = basix_schema_breadcrumbs();
		if ( $crumbs ) {
			$nodes[] = $crumbs;
		}
		if ( $post instanceof WP_Post && 'post' === $post->post_type ) {
			$nodes[] = basix_schema_article( $post );
		}
	}
	if ( $post instanceof WP_Post && BASIX_EVENT === $post->post_type ) {
		$nodes = array_merge( $nodes, basix_schema_events( $post ) );
	}
	return $nodes;
}

add_action(
	'wp_head',
	function () {
		$nodes = basix_schema_nodes( basix_seo_plugin_active() );
		/**
		 * Filters the structured data of the page, as a list of schema.org nodes.
		 *
		 * @param array[] $nodes Nodes; return an empty array for none.
		 */
		$nodes = (array) apply_filters( 'basix_schema_graph', $nodes );
		if ( ! $nodes ) {
			return;
		}
		$json = wp_json_encode(
			array(
				'@context' => 'https://schema.org',
				'@graph'   => array_values( $nodes ),
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
		);
		echo '<script type="application/ld+json">' . $json . "</script>\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON with < > & escaped.
	},
	4
);

/**
 * The Open Graph and Twitter tags of the current page, as property => content. Empty when an SEO plugin is active.
 *
 * @return array<string, string|string[]>
 */
function basix_open_graph(): array {
	$post = is_singular() ? get_queried_object() : null;
	if ( $post instanceof WP_Post && post_password_required( $post ) ) {
		return array();
	}
	$tags = array(
		'og:locale'    => get_locale(),
		'og:type'      => $post instanceof WP_Post && 'post' === $post->post_type ? 'article' : 'website',
		'og:site_name' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
		'og:title'     => wp_get_document_title(),
		'og:url'       => $post instanceof WP_Post ? get_permalink( $post ) : home_url( '/' ),
	);
	$description = basix_meta_description();
	if ( '' !== $description ) {
		$tags['og:description'] = $description;
	}
	$image = $post instanceof WP_Post ? basix_schema_image( $post ) : null;
	$image = $image ? $image : basix_schema_logo();
	if ( $image ) {
		$tags['og:image']        = $image['url'];
		$tags['og:image:width']  = (string) $image['width'];
		$tags['og:image:height'] = (string) $image['height'];
	}
	if ( $post instanceof WP_Post && 'post' === $post->post_type ) {
		$tags['article:published_time'] = get_post_time( 'c', false, $post );
		$tags['article:modified_time']  = get_post_modified_time( 'c', false, $post );
		$cats                           = get_the_category( $post->ID );
		if ( $cats ) {
			$tags['article:section'] = $cats[0]->name;
		}
		$tag_list = get_the_tags( $post->ID );
		if ( $tag_list ) {
			$tags['article:tag'] = wp_list_pluck( $tag_list, 'name' );
		}
	}
	$tags['twitter:card'] = $image ? 'summary_large_image' : 'summary';
	if ( ! is_singular() ) {
		unset( $tags['og:url'] );
	}
	return (array) apply_filters( 'basix_open_graph', $tags );
}

add_action(
	'wp_head',
	function () {
		if ( basix_seo_plugin_active() ) {
			return;
		}
		foreach ( basix_open_graph() as $property => $content ) {
			foreach ( (array) $content as $value ) {
				$attr = 0 === strpos( $property, 'twitter:' ) ? 'name' : 'property';
				printf( '<meta %s="%s" content="%s">' . "\n", esc_attr( $attr ), esc_attr( $property ), esc_attr( (string) $value ) );
			}
		}
	},
	3
);
