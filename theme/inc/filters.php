<?php
/**
 * Filtering post lists (blog, archives, search) by category and date range.
 *
 * The filter is a plain GET form, so it works without JavaScript. Three query variables are used:
 * categories (ids, comma separated or as an array), date_from and date_to (YYYY-MM-DD).
 *
 * @package basix
 */

/** The query variables the filter adds to the address. */
const BASIX_FILTER_VARS = array( 'categories', 'date_from', 'date_to' );

add_filter(
	'query_vars',
	function ( array $vars ): array {
		return array_merge( $vars, BASIX_FILTER_VARS );
	}
);

/**
 * Whether a string is a real date in YYYY-MM-DD form.
 *
 * @param mixed $value Value to check.
 * @return bool
 */
function basix_is_date( $value ): bool {
	if ( ! is_string( $value ) || ! preg_match( '/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m ) ) {
		return false;
	}
	return checkdate( (int) $m[2], (int) $m[3], (int) $m[1] );
}

/**
 * The filter values of the current request, cleaned: only existing category ids and valid dates.
 *
 * @return array{categories: int[], date_from: string, date_to: string}
 */
function basix_filter_values(): array {
	$raw   = get_query_var( 'categories' );
	$parts = is_array( $raw ) ? $raw : explode( ',', (string) $raw );
	$ids   = array();
	foreach ( $parts as $part ) {
		$id = absint( $part );
		if ( $id && term_exists( $id, 'category' ) ) {
			$ids[] = $id;
		}
	}

	$from = get_query_var( 'date_from' );
	$to   = get_query_var( 'date_to' );
	return array(
		'categories' => array_values( array_unique( $ids ) ),
		'date_from'  => basix_is_date( $from ) ? $from : '',
		'date_to'    => basix_is_date( $to ) ? $to : '',
	);
}

// Apply the filter to the main list on the blog, archives and search.
add_action(
	'pre_get_posts',
	function ( WP_Query $query ) {
		if ( is_admin() || ! $query->is_main_query() || ! ( $query->is_home() || $query->is_archive() || $query->is_search() ) ) {
			return;
		}
		$values = basix_filter_values();

		if ( $values['categories'] ) {
			$tax_query   = (array) $query->get( 'tax_query' );
			$tax_query[] = array(
				'taxonomy' => 'category',
				'field'    => 'term_id',
				'terms'    => $values['categories'],
			);
			$query->set( 'tax_query', $tax_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- the visitor's own category filter.
		}

		if ( $values['date_from'] || $values['date_to'] ) {
			$range = array( 'inclusive' => true );
			if ( $values['date_from'] ) {
				$range['after'] = $values['date_from'] . ' 00:00:00';
			}
			if ( $values['date_to'] ) {
				$range['before'] = $values['date_to'] . ' 23:59:59';
			}
			$query->set( 'date_query', array( $range ) );
		}
	}
);

/**
 * The address of the current list without any filter (and without the page number).
 *
 * @return string
 */
function basix_filter_base_url(): string {
	return remove_query_arg( array_merge( BASIX_FILTER_VARS, array( 'paged' ) ), get_pagenum_link( 1, false ) );
}

/**
 * Address of the current list with some filter values replaced.
 *
 * @param array $changes Query variables to set (an empty value removes it).
 * @return string
 */
function basix_filter_url( array $changes ): string {
	$values = basix_filter_values();
	$args   = array(
		'categories' => $values['categories'] ? implode( ',', $values['categories'] ) : '',
		'date_from'  => $values['date_from'],
		'date_to'    => $values['date_to'],
	);
	return add_query_arg( array_filter( array_merge( $args, $changes ), 'strlen' ), basix_filter_base_url() );
}

/**
 * The filters that are on now, each with a link that removes it.
 *
 * @return array<int, array{label: string, url: string}>
 */
function basix_active_filters(): array {
	$values = basix_filter_values();
	$active = array();

	foreach ( $values['categories'] as $id ) {
		$term = get_term( $id, 'category' );
		if ( $term instanceof WP_Term ) {
			$rest     = array_values( array_diff( $values['categories'], array( $id ) ) );
			$active[] = array(
				'label' => $term->name,
				'url'   => basix_filter_url( array( 'categories' => implode( ',', $rest ) ) ),
			);
		}
	}

	if ( $values['date_from'] || $values['date_to'] ) {
		$format = get_option( 'date_format' );
		$from   = $values['date_from'] ? wp_date( $format, strtotime( $values['date_from'] . ' 12:00:00' ) ) : '';
		$to     = $values['date_to'] ? wp_date( $format, strtotime( $values['date_to'] . ' 12:00:00' ) ) : '';
		if ( $from && $to ) {
			$label = $from . ' – ' . $to;
		} elseif ( $from ) {
			/* translators: %s: date. */
			$label = sprintf( __( 'From %s', 'basix-paperlight' ), $from );
		} else {
			/* translators: %s: date. */
			$label = sprintf( __( 'Until %s', 'basix-paperlight' ), $to );
		}
		$active[] = array(
			'label' => $label,
			'url'   => basix_filter_url(
				array(
					'date_from' => '',
					'date_to'   => '',
				)
			),
		);
	}
	return $active;
}

/**
 * The categories as groups for the category picker: each top-level category with all of its descendants
 * flattened below it. The picker has two levels.
 *
 * @return array<int, array{id: string, label: string, subgroups: array}>
 */
function basix_category_groups(): array {
	$groups = array();
	foreach ( get_categories( array( 'parent' => 0 ) ) as $top ) {
		$subs = array();
		foreach ( get_categories( array( 'child_of' => $top->term_id ) ) as $child ) {
			$subs[] = array(
				'id'    => (string) $child->term_id,
				'label' => $child->name,
			);
		}
		$groups[] = array(
			'id'        => (string) $top->term_id,
			'label'     => $top->name,
			'subgroups' => $subs,
		);
	}
	return $groups;
}

/**
 * The top-level categories shown as chips above a list (the default category only when it is the only one).
 *
 * @return WP_Term[]
 */
function basix_chip_categories(): array {
	$default = (int) get_option( 'default_category' );
	$terms   = get_categories(
		array(
			'parent'  => 0,
			'orderby' => 'count',
			'order'   => 'DESC',
			'number'  => 8,
		)
	);
	$others  = array_filter(
		$terms,
		function ( $term ) use ( $default ) {
			return $term->term_id !== $default;
		}
	);
	return $others ? array_values( $others ) : array();
}

/**
 * Address of the main blog list (the posts page when the front page is a static page).
 *
 * @return string
 */
function basix_blog_url(): string {
	$posts_page = (int) get_option( 'page_for_posts' );
	return 'page' === get_option( 'show_on_front' ) && $posts_page ? get_permalink( $posts_page ) : home_url( '/' );
}

/**
 * Settings for the filter's JavaScript: the category groups, what is selected, and the date picker's language.
 *
 * @return array
 */
function basix_filter_config(): array {
	global $wp_locale;
	$values = basix_filter_values();
	return array(
		'groups'     => basix_category_groups(),
		'selected'   => array_map( 'strval', $values['categories'] ),
		'dateFrom'   => $values['date_from'],
		'dateTo'     => $values['date_to'],
		'startDay'   => (int) get_option( 'start_of_week' ),
		'dateLocale' => str_replace( '_', '-', get_locale() ),
		'days'       => array_values( $wp_locale->weekday_abbrev ),
		'months'     => array_values( $wp_locale->month ),
		'text'       => array(
			'title'      => __( 'Filter posts', 'basix-paperlight' ),
			'search'     => __( 'Search categories…', 'basix-paperlight' ),
			'selectAll'  => __( 'Select all', 'basix-paperlight' ),
			'clear'      => __( 'Clear', 'basix-paperlight' ),
			'empty'      => __( 'No categories found', 'basix-paperlight' ),
			'none'       => __( 'Any category', 'basix-paperlight' ),
			'dateRange'  => __( 'Choose a date range', 'basix-paperlight' ),
			'categories' => __( 'Categories', 'basix-paperlight' ),
			'dates'      => __( 'Published between', 'basix-paperlight' ),
		),
	);
}
