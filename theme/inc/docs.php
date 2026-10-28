<?php
/**
 * Helpers for the Documentation page template: a navigation tree of the current page's section.
 *
 * A "section" is a top-level page and all of its descendants. Every page in it that uses the
 * Documentation template shows the same tree, with the current branch expanded.
 *
 * @package basix
 */

/**
 * The id of the top-level page of the current page's section.
 *
 * @return int
 */
function basix_docs_root_id(): int {
	$ancestors = get_post_ancestors( get_the_ID() );
	return $ancestors ? (int) end( $ancestors ) : (int) get_the_ID();
}

/**
 * All pages of the section, as a nested array: each node has 'page' (WP_Post) and 'children'.
 *
 * @return array The root node.
 */
function basix_docs_tree(): array {
	$root_id = basix_docs_root_id();
	$pages   = get_pages(
		array(
			'child_of'    => $root_id,
			'sort_column' => 'menu_order,post_title',
		)
	);

	$by_parent = array();
	foreach ( $pages as $page ) {
		$by_parent[ $page->post_parent ][] = $page;
	}

	$build = function ( WP_Post $page ) use ( &$build, $by_parent ): array {
		$children = array();
		foreach ( $by_parent[ $page->ID ] ?? array() as $child ) {
			$children[] = $build( $child );
		}
		return array(
			'page'     => $page,
			'children' => $children,
		);
	};

	return $build( get_post( $root_id ) );
}

/**
 * Whether the node is the current page or one of its ancestors, so its children should be shown.
 *
 * @param array $node A tree node.
 * @return bool
 */
function basix_docs_node_is_open( array $node ): bool {
	$current = get_the_ID();
	return $node['page']->ID === $current || in_array( $node['page']->ID, get_post_ancestors( $current ), true );
}

/**
 * Prints one level of the navigation tree.
 *
 * @param array[] $nodes Nodes to print.
 */
function basix_docs_print_nodes( array $nodes ): void {
	echo '<ul>';
	foreach ( $nodes as $node ) {
		$page     = $node['page'];
		$current  = get_the_ID() === $page->ID;
		$has_kids = ! empty( $node['children'] );
		$classes  = array( 'docs-nav-item' );
		if ( $has_kids ) {
			$classes[] = 'has-children';
			if ( basix_docs_node_is_open( $node ) ) {
				$classes[] = 'is-open';
			}
		}
		printf(
			'<li class="%1$s"><a href="%2$s"%3$s>%4$s</a>',
			esc_attr( implode( ' ', $classes ) ),
			esc_url( get_permalink( $page ) ),
			$current ? ' class="is-active" aria-current="page"' : '',
			esc_html( get_the_title( $page ) )
		);
		if ( $has_kids ) {
			basix_docs_print_nodes( $node['children'] );
		}
		echo '</li>';
	}
	echo '</ul>';
}

/**
 * The section's pages in reading order (the order of the tree, top to bottom).
 *
 * @return WP_Post[]
 */
function basix_docs_flat(): array {
	$flat = array();
	$walk = function ( array $node ) use ( &$walk, &$flat ) {
		$flat[] = $node['page'];
		foreach ( $node['children'] as $child ) {
			$walk( $child );
		}
	};
	$walk( basix_docs_tree() );
	return $flat;
}

/**
 * The pages before and after the current one in reading order.
 *
 * @return array{prev: ?WP_Post, next: ?WP_Post}
 */
function basix_docs_neighbours(): array {
	$flat  = basix_docs_flat();
	$index = array_search( get_the_ID(), wp_list_pluck( $flat, 'ID' ), true );
	if ( false === $index ) {
		return array(
			'prev' => null,
			'next' => null,
		);
	}
	return array(
		'prev' => $flat[ $index - 1 ] ?? null,
		'next' => $flat[ $index + 1 ] ?? null,
	);
}
