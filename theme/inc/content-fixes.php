<?php
/**
 * Fixes for markup that old content and a few core blocks produce and that assistive technology cannot use: a menu
 * list nested directly in a list, a decorative picture marked as an image without a description, and an empty
 * definition list left behind by an old picture caption.
 *
 * @package basix
 */

/**
 * Takes the start and end tag of a nested list away and keeps what is inside, for the first list of a class that sits
 * directly in another list (a list may only hold list items).
 *
 * @param string $html  HTML.
 * @param string $outer Class of the outer list.
 * @param string $inner Class of the list that is nested in it.
 * @return string
 */
function basix_unwrap_nested_list( string $html, string $outer, string $inner ): string {
	$pattern = '#(<ul[^>]*class="[^"]*\b' . preg_quote( $outer, '#' ) . '\b[^"]*"[^>]*>\s*)(<ul[^>]*class="[^"]*\b' . preg_quote( $inner, '#' ) . '\b[^"]*"[^>]*>)#';
	if ( ! preg_match( $pattern, $html, $match, PREG_OFFSET_CAPTURE ) ) {
		return $html;
	}
	$start = $match[2][1];
	$open  = $match[2][0];
	// Find the end tag that belongs to the inner list by counting lists inside it.
	$depth  = 1;
	$offset = $start + strlen( $open );
	while ( $depth > 0 && preg_match( '#<(/?)ul\b[^>]*>#', $html, $tag, PREG_OFFSET_CAPTURE, $offset ) ) {
		$depth += '' === $tag[1][0] ? 1 : -1;
		$offset = $tag[0][1] + strlen( $tag[0][0] );
		if ( 0 === $depth ) {
			return substr( $html, 0, $start ) . substr( $html, $start + strlen( $open ), $tag[0][1] - $start - strlen( $open ) ) . substr( $html, $offset );
		}
	}
	return $html;
}

// The Navigation block with a page list: core prints the list of pages as a list inside the menu's list.
add_filter(
	'render_block_core/navigation',
	function ( $html ) {
		return is_string( $html ) ? basix_unwrap_nested_list( $html, 'wp-block-navigation__container', 'wp-block-page-list' ) : $html;
	}
);

// A Cover block with a parallax picture and no description draws the picture as a div that says it is an image but has no
// name. With no description the picture is decoration: hide it from assistive technology instead.
add_filter(
	'render_block_core/cover',
	function ( $html ) {
		if ( ! is_string( $html ) || false === strpos( $html, 'role="img"' ) ) {
			return $html;
		}
		$tags = new WP_HTML_Tag_Processor( $html );
		while ( $tags->next_tag( array( 'class_name' => 'wp-block-cover__image-background' ) ) ) {
			if ( 'img' === $tags->get_attribute( 'role' ) && '' === trim( (string) $tags->get_attribute( 'aria-label' ) ) ) {
				$tags->remove_attribute( 'role' );
				$tags->remove_attribute( 'aria-label' );
				$tags->set_attribute( 'aria-hidden', 'true' );
			}
		}
		return $tags->get_updated_html();
	}
);

// Old posts hold the leftovers of picture captions: a definition list whose only entry is an empty term.
add_filter(
	'the_content',
	function ( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, 'wp-caption' ) ) {
			return $content;
		}
		$cleaned = preg_replace( '#<dl\b[^>]*class="[^"]*\bwp-caption\b[^"]*"[^>]*>\s*<dt\b[^>]*>\s*</dt>\s*</dl>#i', '', $content );
		return null === $cleaned ? $content : $cleaned;
	},
	20
);
