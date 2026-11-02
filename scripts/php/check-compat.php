<?php
/**
 * Checks the compatibility pieces: the translation plugin configuration (wpml-config.xml) names things that exist,
 * the page builder canvas template is offered, and a theme builder can draw the header and the footer. Run with
 * `wp eval-file /php/check-compat.php` (make check does).
 */

$failed = 0;
$check  = static function ( bool $ok, string $what ) use ( &$failed ) {
	echo ( $ok ? 'ok   ' : 'FAIL ' ), $what, "\n";
	if ( ! $ok ) {
		++$failed;
	}
};

// The translation configuration.
$file = get_theme_file_path( 'wpml-config.xml' );
$dom  = new DOMDocument();
$ok   = file_exists( $file ) && @$dom->load( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a broken file is reported below.
$check( $ok && 'wpml-config' === $dom->documentElement->nodeName, 'wpml-config.xml is well-formed XML' );
$xpath    = new DOMXPath( $dom );
$names    = static function ( string $query ) use ( $xpath ): array {
	$out = array();
	foreach ( $xpath->query( $query ) as $node ) {
		$out[] = trim( $node->textContent );
	}
	return $out;
};
$fields   = $names( '//custom-fields/custom-field' );
$expected = array_merge( array_keys( basix_event_fields() ), array_keys( BASIX_PAGE_OPTIONS ) );
$missing  = array_diff( $expected, $fields );
$check( ! $missing, 'every event field and per-page choice is in the configuration' . ( $missing ? ' (missing: ' . implode( ', ', $missing ) . ')' : '' ) );
$unknown = array_diff( array_filter( $fields, static function ( $name ) {
	return 0 !== strpos( $name, '_basix_event_' );
} ), $expected );
$check( ! $unknown, 'and it names no field that is gone' . ( $unknown ? ' (' . implode( ', ', $unknown ) . ')' : '' ) );
$derived = array( '_basix_event_start_utc', '_basix_event_end_utc', '_basix_event_last_utc', '_basix_event_next_utc' );
$check( ! array_diff( $derived, $fields ), 'the derived event fields are listed (to be ignored, they are worked out again)' );
$check( in_array( BASIX_EVENT, $names( '//custom-types/custom-type' ), true ) && in_array( BASIX_EVENT_CAT, $names( '//taxonomies/taxonomy' ), true ), 'the event type and its categories are translatable' );
$check( 'translate' === $xpath->query( '//custom-field[. = "basix_event_location"]' )->item( 0 )->getAttribute( 'action' ) && 'copy' === $xpath->query( '//custom-field[. = "basix_event_start"]' )->item( 0 )->getAttribute( 'action' ), 'the place of an event is translated, its dates are copied' );

require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
$manager = new WP_Customize_Manager();
do_action( 'customize_register', $manager );
$texts   = $names( '//admin-texts/key[@name="theme_mods_basix"]/key/@name' );
$texts   = array();
foreach ( $xpath->query( '//admin-texts/key[@name="theme_mods_basix"]/key' ) as $node ) {
	$texts[] = $node->getAttribute( 'name' );
}
$gone = array_filter( $texts, static function ( $name ) use ( $manager ) {
	return ! $manager->get_setting( $name );
} );
$check( $texts && ! $gone, 'every Customizer text in the configuration is a real setting' . ( $gone ? ' (' . implode( ', ', $gone ) . ')' : '' ) );
$typed = array();
foreach ( $manager->settings() as $id => $setting ) {
	if ( 0 === strpos( $id, 'basix_' ) && in_array( $setting->sanitize_callback, array( 'sanitize_text_field', 'esc_url_raw', 'wp_kses_post' ), true ) ) {
		$typed[] = $id;
	}
}
$unlisted = array_diff( $typed, $texts );
$check( ! $unlisted, 'and every Customizer setting that holds text is in it' . ( $unlisted ? ' (missing: ' . implode( ', ', $unlisted ) . ')' : '' ) );

// The canvas template.
$templates = wp_get_theme()->get_page_templates( null, 'page' );
$check( isset( $templates['page-templates/canvas.php'] ), 'the Canvas template is offered for pages' );
$check( isset( wp_get_theme()->get_page_templates( null, 'post' )['page-templates/canvas.php'] ), 'and for posts' );
$canvas = (string) file_get_contents( get_theme_file_path( 'page-templates/canvas.php' ) );
$check( false === strpos( $canvas, 'get_header' ) && false === strpos( $canvas, 'get_footer' ) && false !== strpos( $canvas, 'wp_head()' ) && false !== strpos( $canvas, 'wp_footer()' ), 'it has no theme header or footer, only what plugins and builders need in the head and the footer' );

// A theme builder draws the header and the footer.
$rendered = static function ( string $part ): string {
	ob_start();
	// get_header() would load the file only once in this process, so the template is loaded directly.
	load_template( get_theme_file_path( $part . '.php' ), false );
	return (string) ob_get_clean();
};
$_SERVER['SERVER_NAME'] = 'localhost';
$GLOBALS['wp_query']    = new WP_Query( array( 'page_id' => (int) get_option( 'page_on_front' ) ? (int) get_option( 'page_on_front' ) : 0 ) );
$GLOBALS['basix_builder'] = array();
$normal = $rendered( 'header' );
$check( false !== strpos( $normal, 'class="site-header"' ) && false !== strpos( $normal, 'class="skip-link"' ), 'without a builder the theme draws its header' );
$normal_footer = $rendered( 'footer' );
$check( false !== strpos( $normal_footer, 'class="site-footer"' ), 'and its footer' );

$asked = array();
add_filter( 'basix_builder_location', static function ( $handled, $location ) use ( &$asked ) {
	$asked[] = $location;
	echo '<!-- builder ', esc_html( $location ), ' -->';
	return true;
}, 10, 2 );
$GLOBALS['basix_builder'] = array();
$built = $rendered( 'header' );
$check( false === strpos( $built, 'class="site-header"' ) && false === strpos( $built, 'class="top-bar"' ) && false === strpos( $built, 'flyout-menu' ), 'when a builder draws the header, the theme leaves its own out' );
$check( false !== strpos( $built, '<!-- builder header -->' ) && false !== strpos( $built, 'class="skip-link"' ) && false !== strpos( $built, '<main id="content"' ), 'the builder\'s header is in the page, with the skip link and the content area' );
$built_footer = $rendered( 'footer' );
$check( false === strpos( $built_footer, 'class="site-footer"' ) && false !== strpos( $built_footer, '<!-- builder footer -->' ), 'the same for the footer' );
$check( array( 'header', 'footer' ) === $asked, 'each part is asked about once' );
remove_all_filters( 'basix_builder_location' );
$GLOBALS['basix_builder'] = array();

// Forms.
$css = (string) file_get_contents( get_theme_file_path( 'assets/css/forms.css' ) );
foreach ( array( 'wpcf7', 'wpforms', 'fluentform', 'gform' ) as $plugin ) {
	$check( false !== strpos( $css, $plugin ), "forms.css styles $plugin" );
}

echo $failed ? "\nCompatibility check FAILED ($failed)\n" : "\nCompatibility check passed.\n";
exit( $failed ? 1 : 0 );
