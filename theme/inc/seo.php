<?php
/**
 * A meta description for pages, as a fallback for sites without an SEO plugin.
 *
 * It is skipped when a well-known SEO plugin is active, and can be changed or switched off with the
 * basix_meta_description filter (return an empty string for none).
 *
 * @package basix
 */

/**
 * Whether a well-known SEO plugin is active (it writes its own description).
 *
 * @return bool
 */
function basix_seo_plugin_active(): bool {
	foreach ( array( 'WPSEO_VERSION', 'RANK_MATH_VERSION', 'AIOSEO_VERSION', 'SEOPRESS_VERSION', 'SLIM_SEO_VER' ) as $constant ) {
		if ( defined( $constant ) ) {
			return true;
		}
	}
	return class_exists( 'The_SEO_Framework\Load' ) || function_exists( 'tsf' );
}

/**
 * The description of the current page: the excerpt of a post, the description of a category or author, or
 * the site tagline.
 *
 * @return string Plain text, at most about 160 characters.
 */
function basix_meta_description(): string {
	$text = '';

	if ( is_singular() ) {
		$post = get_queried_object();
		if ( $post instanceof WP_Post && ! post_password_required( $post ) ) {
			$text = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 30, '' );
		}
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$text = term_description();
	} elseif ( is_author() ) {
		$text = get_the_author_meta( 'description', get_queried_object_id() );
	} elseif ( is_front_page() || is_home() ) {
		$text = get_bloginfo( 'description' );
	}

	$text = trim( preg_replace( '/\s+/', ' ', wp_strip_all_tags( (string) $text ) ) );
	$text = mb_strimwidth( $text, 0, 160, '…' );
	return (string) apply_filters( 'basix_meta_description', $text );
}

add_action(
	'wp_head',
	function () {
		if ( basix_seo_plugin_active() ) {
			return;
		}
		$description = basix_meta_description();
		if ( '' !== $description ) {
			printf( '<meta name="description" content="%s">' . "\n", esc_attr( $description ) );
		}
	},
	2
);
