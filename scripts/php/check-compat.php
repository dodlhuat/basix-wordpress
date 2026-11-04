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

// The translation configuration: one file in the plugin (events) and one in the theme (page choices, Customizer texts).
$load  = static function ( string $file ) use ( &$check ): ?DOMXPath {
	$dom = new DOMDocument();
	$ok  = file_exists( $file ) && @$dom->load( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged -- a broken file is reported below.
	$check( $ok && 'wpml-config' === $dom->documentElement->nodeName, basename( dirname( $file ) ) . '/wpml-config.xml is well-formed XML' );
	return $ok ? new DOMXPath( $dom ) : null;
};
$names = static function ( DOMXPath $xpath, string $query ): array {
	$out = array();
	foreach ( $xpath->query( $query ) as $node ) {
		$out[] = trim( $node->textContent );
	}
	return $out;
};
$plugin_xml = $load( BASIX_EVENTS_DIR . 'wpml-config.xml' );
$theme_xml  = $load( get_theme_file_path( 'wpml-config.xml' ) );

$event_fields = $names( $plugin_xml, '//custom-fields/custom-field' );
$derived      = array( '_basix_event_start_utc', '_basix_event_end_utc', '_basix_event_last_utc', '_basix_event_next_utc' );
$missing      = array_diff( array_keys( basix_event_fields() ), $event_fields );
$check( ! $missing, 'every event field is in the plugin configuration' . ( $missing ? ' (missing: ' . implode( ', ', $missing ) . ')' : '' ) );
$unknown = array_diff( $event_fields, array_keys( basix_event_fields() ), $derived );
$check( ! $unknown, 'and it names no field that is gone' . ( $unknown ? ' (' . implode( ', ', $unknown ) . ')' : '' ) );
$check( ! array_diff( $derived, $event_fields ), 'the derived event fields are listed (to be ignored, they are worked out again)' );
$check( in_array( BASIX_EVENT, $names( $plugin_xml, '//custom-types/custom-type' ), true ) && in_array( BASIX_EVENT_CAT, $names( $plugin_xml, '//taxonomies/taxonomy' ), true ), 'the event type and its categories are translatable' );
$check( 'translate' === $plugin_xml->query( '//custom-field[. = "basix_event_location"]' )->item( 0 )->getAttribute( 'action' ) && 'copy' === $plugin_xml->query( '//custom-field[. = "basix_event_start"]' )->item( 0 )->getAttribute( 'action' ), 'the place of an event is translated, its dates are copied' );

$page_fields = $names( $theme_xml, '//custom-fields/custom-field' );
$check( ! array_diff( array_keys( BASIX_PAGE_OPTIONS ), $page_fields ) && ! array_diff( $page_fields, array_keys( BASIX_PAGE_OPTIONS ) ), 'the theme configuration lists exactly the per-page choices' );

require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
$manager = new WP_Customize_Manager();
do_action( 'customize_register', $manager );
$texts   = array();
foreach ( $theme_xml->query( '//admin-texts/key[@name="theme_mods_basix-paperlight"]/key' ) as $node ) {
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

// A theme builder can also draw the content: single pages and posts, the 404 page and archives, in every template.
$first_page = (int) get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) )[0];
$first_post = (int) get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) )[0];
$first_evt  = basix_has_events() ? (int) ( get_posts( array( 'post_type' => BASIX_EVENT, 'post_status' => 'publish', 'numberposts' => 1, 'fields' => 'ids' ) )[0] ?? 0 ) : 0;
$cases      = array(
	'single.php'                       => array( array( 'p' => $first_post ), 'single', 'entry-content' ),
	'page.php'                         => array( array( 'page_id' => $first_page ), 'single', 'entry-content' ),
	'404.php'                          => array( array( 'p' => 999999999 ), 'single', 'not-found' ),
	'archive.php'                      => array( array( 'cat' => (int) get_option( 'default_category' ) ), 'archive', 'archive-header' ),
	'search.php'                       => array( array( 's' => 'design' ), 'archive', 'archive-header' ),
	'index.php'                        => array( array(), 'archive', 'class="layout' ),
	'page-templates/calendar.php'      => array( array( 'page_id' => $first_page ), 'single', 'entry-content' ),
	'page-templates/gallery.php'       => array( array( 'page_id' => $first_page ), 'single', 'entry-content' ),
	'page-templates/full-width.php'    => array( array( 'page_id' => $first_page ), 'single', 'entry-content' ),
	'page-templates/landing.php'       => array( array( 'page_id' => $first_page ), 'single', 'entry-content' ),
	'page-templates/docs.php'          => array( array( 'page_id' => $first_page ), 'single', 'docs-sidebar' ),
);
if ( $first_evt ) {
	$cases['single-basix-event.php'] = array( array( 'p' => $first_evt, 'post_type' => BASIX_EVENT ), 'single', 'entry-content' );
}
foreach ( $cases as $template => $case ) {
	list( $args, $location, $marker ) = $case;
	$run = static function ( bool $builder ) use ( $template, $args, $location ): string {
		$GLOBALS['basix_builder'] = array();
		$GLOBALS['wp_query']      = new WP_Query( $args );
		if ( 'p' === array_key_first( $args ) && 999999999 === $args['p'] ) {
			$GLOBALS['wp_query']->set_404();
		}
		$GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
		$GLOBALS['post']         = $GLOBALS['wp_query']->posts[0] ?? null;
		$filter = static function ( $handled, $asked ) use ( $builder, $location ) {
			if ( $builder && $asked === $location ) {
				echo '<!-- builder ', esc_html( $asked ), ' -->';
				return true;
			}
			return false;
		};
		add_filter( 'basix_builder_location', $filter, 10, 2 );
		ob_start();
		load_template( get_theme_file_path( $template ), false );
		$out = (string) ob_get_clean();
		remove_filter( 'basix_builder_location', $filter, 10 );
		return $out;
	};
	$with    = $run( true );
	$without = $run( false );
	$check( false !== strpos( $without, $marker ), "$template: draws its own content without a builder" );
	$check( false !== strpos( $with, "<!-- builder $location -->" ) && false === strpos( $with, $marker ), "$template: leaves it to a builder that draws the $location location" );
}
$GLOBALS['basix_builder'] = array();

// A post without a title still has something to link with.
$untitled = wp_insert_post( array( 'post_type' => 'post', 'post_status' => 'publish', 'post_title' => '', 'post_content' => 'No title here.' ) );
$check( '' !== trim( get_the_title( $untitled ) ), 'a post without a title gets a placeholder, so its link has a name' );
$check( 'Real title' === apply_filters( 'the_title', 'Real title', $untitled ), 'and a real title is left alone' );
wp_delete_post( $untitled, true );

// Markup that old content and a few core blocks produce and that assistive technology cannot use.
libxml_use_internal_errors( true );
$parse = static function ( string $html ): DOMXPath {
	$dom = new DOMDocument();
	$dom->loadHTML( '<?xml encoding="utf-8"?><body>' . $html . '</body>' );
	return new DOMXPath( $dom );
};
$nav = $parse( do_blocks( '<!-- wp:navigation --><!-- wp:page-list /--><!-- /wp:navigation -->' ) );
$check( 0 === $nav->query( '//ul[contains(@class, "wp-block-navigation__container")]/ul' )->length && $nav->query( '//ul[contains(@class, "wp-block-navigation__container")]/li' )->length > 0, 'a Navigation block with a page list holds its page items directly, not a list in a list' );
$lists_ok = true;
foreach ( $nav->query( '//ul' ) as $list ) {
	foreach ( $list->childNodes as $child ) {
		if ( XML_ELEMENT_NODE === $child->nodeType && 'li' !== $child->nodeName ) {
			$lists_ok = false;
		}
	}
}
$check( $lists_ok, 'and every list in it holds only list items' );
$nested = basix_unwrap_nested_list( '<ul class="outer"><ul class="inner"><li>a<ul><li>b</li></ul></li><li>c</li></ul></ul><ul class="inner"><li>kept</li></ul>', 'outer', 'inner' );
$check( '<ul class="outer"><li>a<ul><li>b</li></ul></li><li>c</li></ul><ul class="inner"><li>kept</li></ul>' === $nested, 'unwrapping keeps deeper lists, and leaves a list of the same class elsewhere alone' );

$cover_open = '<!-- wp:cover {"url":"http://example.com/a.jpg","hasParallax":true} --><div class="wp-block-cover has-parallax"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span>';
$cover_end  = '<div class="wp-block-cover__inner-container"><!-- wp:paragraph --><p>Text</p><!-- /wp:paragraph --></div></div><!-- /wp:cover -->';
$bare       = do_blocks( $cover_open . '<div role="img" class="wp-block-cover__image-background has-parallax" style="background-image:url(http://example.com/a.jpg)"></div>' . $cover_end );
$bare_xpath = $parse( $bare );
$bare_image = $bare_xpath->query( '//div[contains(@class, "wp-block-cover__image-background")]' )->item( 0 );
$check( $bare_image && ! $bare_image->hasAttribute( 'role' ) && 'true' === $bare_image->getAttribute( 'aria-hidden' ), 'a parallax cover without a description hides its picture from assistive technology instead of calling it an unnamed image' );
$named = do_blocks( $cover_open . '<div role="img" class="wp-block-cover__image-background has-parallax" aria-label="A harbour at dusk" style="background-image:url(http://example.com/a.jpg)"></div>' . $cover_end );
$named_image = $parse( $named )->query( '//div[contains(@class, "wp-block-cover__image-background")]' )->item( 0 );
$check( $named_image && 'img' === $named_image->getAttribute( 'role' ) && 'A harbour at dusk' === $named_image->getAttribute( 'aria-label' ) && ! $named_image->hasAttribute( 'aria-hidden' ), 'and one with a description keeps its role and name' );

$empty_dl = apply_filters( 'the_content', '<dl id="attachment_9" class="wp-caption aligncenter" style="width:650px;"><dt class="wp-caption-dt"></dt></dl>&nbsp;<p>Text after.</p>' );
$check( false === strpos( $empty_dl, '<dl' ) && false !== strpos( $empty_dl, 'Text after.' ), 'an empty definition list left by an old picture caption is removed' );
$real_dl = apply_filters( 'the_content', '<dl class="wp-caption alignleft"><dt class="wp-caption-dt"><img src="x.jpg" alt="y"></dt><dd class="wp-caption-dd">A caption</dd></dl>' );
$check( false !== strpos( $real_dl, '<dl' ) && false !== strpos( $real_dl, 'A caption' ), 'but a caption with a picture and a text is kept' );

// Forms.
$css = (string) file_get_contents( get_theme_file_path( 'assets/css/forms.css' ) );
foreach ( array( 'wpcf7', 'wpforms', 'fluentform', 'gform' ) as $plugin ) {
	$check( false !== strpos( $css, $plugin ), "forms.css styles $plugin" );
}

echo $failed ? "\nCompatibility check FAILED ($failed)\n" : "\nCompatibility check passed.\n";
exit( $failed ? 1 : 0 );
