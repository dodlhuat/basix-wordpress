<?php
/**
 * Checks the header and footer options: the per-page choices, how they combine with the Customizer, the box that
 * saves them, the mega menu markup and the top bar. Run with `wp eval-file /php/check-header.php` (make check does).
 * It changes settings for a moment and puts them back.
 */

$failed = 0;
$check  = static function ( bool $ok, string $what ) use ( &$failed ) {
	echo ( $ok ? 'ok   ' : 'FAIL ' ), $what, "\n";
	if ( ! $ok ) {
		++$failed;
	}
};

$names = array( 'basix_header_style', 'basix_header_sticky', 'basix_header_cta_label', 'basix_header_cta_url', 'basix_topbar_enable', 'basix_footer_layout', 'basix_sidebar_position' );
$saved = array();
foreach ( $names as $name ) {
	$saved[ $name ] = get_theme_mod( $name, null );
	remove_theme_mod( $name );
}

/** Makes WordPress think a post or page is being viewed. */
$view = static function ( int $id ) {
	$post                = get_post( $id );
	$GLOBALS['wp_query'] = new WP_Query( 'page' === $post->post_type ? array( 'page_id' => $id ) : array( 'p' => $id ) );
	$GLOBALS['post']     = $post;
};

$page = wp_insert_post( array( 'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Header check page' ) );
$post = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'Header check post' ) );

// The page's own choice beats the Customizer.
$view( $page );
$check( 'default' === basix_header_mode(), 'the header is solid by default' );
set_theme_mod( 'basix_header_style', 'transparent' );
$check( 'transparent' === basix_header_mode(), 'the Customizer can make it transparent' );
update_post_meta( $page, '_basix_header', 'none' );
$check( 'none' === basix_header_mode(), 'and a page can have none' );
update_post_meta( $page, '_basix_header', 'bogus' );
$check( 'transparent' === basix_header_mode(), 'an invalid page choice is ignored' );
delete_post_meta( $page, '_basix_header' );
remove_theme_mod( 'basix_header_style' );

// Sidebar: pages have none unless they ask; posts follow the Customizer.
$check( 'none' === basix_sidebar_position(), 'a page has no sidebar unless it asks for one' );
update_post_meta( $page, '_basix_sidebar', 'left' );
$check( 'left' === basix_sidebar_position(), 'and gets it on the side it asks for' );
delete_post_meta( $page, '_basix_sidebar' );
$view( $post );
$check( 'right' === basix_sidebar_position(), 'a post follows the Customizer (right)' );
set_theme_mod( 'basix_sidebar_position', 'left' );
$check( 'left' === basix_sidebar_position(), 'when it changes, so does the post' );
update_post_meta( $post, '_basix_sidebar', 'none' );
$check( 'none' === basix_sidebar_position(), 'and a post can opt out' );
delete_post_meta( $post, '_basix_sidebar' );
remove_theme_mod( 'basix_sidebar_position' );

// Title and footer.
$check( '' === basix_title_class(), 'the title shows by default' );
update_post_meta( $post, '_basix_hide_title', '1' );
$check( ' screen-reader-text' === basix_title_class(), 'a page can hide its title for sight but not for screen readers' );
delete_post_meta( $post, '_basix_hide_title' );
$check( 'columns' === basix_footer_layout(), 'the footer is in columns by default' );
set_theme_mod( 'basix_footer_layout', 'minimal' );
$check( 'minimal' === basix_footer_layout(), 'and can be minimal' );
set_theme_mod( 'basix_footer_layout', 'sideways' );
$check( 'columns' === basix_footer_layout(), 'an unknown layout falls back to columns' );
remove_theme_mod( 'basix_footer_layout' );

// The button needs both a text and an address.
$check( '' === basix_header_cta()['label'] && '' === basix_header_cta()['url'], 'there is no header button by default' );
set_theme_mod( 'basix_header_cta_label', ' Get a quote ' );
set_theme_mod( 'basix_header_cta_url', 'javascript:alert(1)' );
$check( 'Get a quote' === basix_header_cta()['label'] && '' === basix_header_cta()['url'], 'an unsafe address is not used, so the button stays away' );
set_theme_mod( 'basix_header_cta_url', 'https://example.com/quote' );
$check( 'https://example.com/quote' === basix_header_cta()['url'], 'a good address is' );
remove_theme_mod( 'basix_header_cta_label' );
remove_theme_mod( 'basix_header_cta_url' );

// Top bar.
$view( $page );
$check( ! basix_top_bar_enabled(), 'the top bar is off by default' );
set_theme_mod( 'basix_topbar_enable', true );
$check( basix_top_bar_enabled(), 'and can be switched on' );
update_post_meta( $page, '_basix_header', 'none' );
$check( ! basix_top_bar_enabled(), 'a page without a header has no top bar either' );
delete_post_meta( $page, '_basix_header' );
$check( '' === basix_language_switcher(), 'without Polylang or WPML there is no language switcher' );
add_filter( 'basix_language_switcher', static function () {
	return '<ul class="top-bar-languages"><li>de</li></ul>';
} );
$check( false !== strpos( basix_language_switcher(), 'top-bar-languages' ), 'but a filter can supply one' );
remove_all_filters( 'basix_language_switcher' );
$classes = get_body_class();
$check( in_array( 'has-top-bar', $classes, true ) && in_array( 'header-sticky', $classes, true ) && in_array( 'footer-columns', $classes, true ), 'the page says what it has in its body classes' );

// The box saves only what is allowed.
wp_set_current_user( 1 );
$_POST = array(
	'basix_page_options_nonce' => wp_create_nonce( 'basix_page_options' ),
	'_basix_header'            => 'transparent',
	'_basix_sidebar'           => 'sideways',
	'_basix_hide_title'        => '1',
	'_basix_footer'            => '',
);
do_action( 'save_post', $page );
$check( 'transparent' === get_post_meta( $page, '_basix_header', true ) && '1' === get_post_meta( $page, '_basix_hide_title', true ), 'the page box saves a header choice and a hidden title' );
$check( '' === get_post_meta( $page, '_basix_sidebar', true ) && '' === get_post_meta( $page, '_basix_footer', true ), 'and drops a value that is not allowed and an empty one' );
$_POST = array( '_basix_header' => 'none' );
do_action( 'save_post', $page );
$check( 'transparent' === get_post_meta( $page, '_basix_header', true ), 'without its nonce nothing is saved' );
$_POST = array();

// The mega menu.
$menu = wp_create_nav_menu( 'Header check menu ' . time() );
$add  = static function ( string $title, int $parent = 0, array $classes = array() ) use ( $menu ): int {
	return (int) wp_update_nav_menu_item(
		$menu,
		0,
		array(
			'menu-item-title'     => $title,
			'menu-item-url'       => 'https://example.com/' . sanitize_title( $title ),
			'menu-item-type'      => 'custom',
			'menu-item-status'    => 'publish',
			'menu-item-parent-id' => $parent,
			'menu-item-classes'   => implode( ' ', $classes ),
		)
	);
};
$top    = $add( 'Topics', 0, array( 'mega-menu' ) );
$design = $add( 'Design', $top );
$add( 'Tokens', $design );
$add( 'Colour', $design );
$add( 'Engineering', $top );
$plain = $add( 'Explore' );
$add( 'Gallery', $plain );
$render = static function () use ( $menu ): string {
	return (string) wp_nav_menu( array( 'menu' => $menu, 'echo' => false, 'depth' => 3, 'container' => false, 'walker' => new Basix_Dropdown_Walker() ) );
};
$html = $render();
$check( 1 === substr_count( $html, 'dropdown-container is-mega' ), 'only the item with the class opens a wide panel' );
$check( 2 === substr_count( $html, 'class="mega-column"' ), 'its two children are columns' );
$check( false !== strpos( $html, '<a class="mega-heading" href="https://example.com/design">Design</a>' ) && false !== strpos( $html, 'Tokens</a>' ), 'a column has its heading and its own items' );
$check( 1 === substr_count( $html, 'Explore</button>' ) && false !== strpos( $html, '<ul class="dropdown-menu"><li><a class="dropdown-item" href="https://example.com/explore">Explore</a></li>' ), 'the other menu is the usual dropdown, with its own page first' );
$check( false === strpos( $html, 'href="https://example.com/topics"' ), 'the mega item has no first entry for itself' );
wp_delete_nav_menu( $menu );

// Clean up.
wp_delete_post( $page, true );
wp_delete_post( $post, true );
foreach ( $saved as $name => $value ) {
	if ( null === $value ) {
		remove_theme_mod( $name );
	} else {
		set_theme_mod( $name, $value );
	}
}

echo $failed ? "\nHeader check FAILED ($failed)\n" : "\nHeader check passed.\n";
exit( $failed ? 1 : 0 );
