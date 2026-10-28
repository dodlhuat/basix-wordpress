<?php
/**
 * Creates a small documentation section (a top-level page with nested pages) that uses the
 * Documentation template. Run by seed-content.sh with `wp eval-file`; prints the root page id.
 */

$make = static function ( string $title, string $slug, int $parent, int $order, string $body ): int {
	$existing = get_page_by_path( $parent ? get_page_uri( $parent ) . '/' . $slug : $slug, OBJECT, 'page' );
	if ( $existing ) {
		wp_delete_post( $existing->ID, true );
	}
	return (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_parent'  => $parent,
			'menu_order'   => $order,
			'post_content' => $body,
			'meta_input'   => array( '_wp_page_template' => 'page-templates/docs.php' ),
		)
	);
};

$p = static fn( string $text ): string => "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->\n\n";
$h = static fn( string $text ): string => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$text}</h2>\n<!-- /wp:heading -->\n\n";
$li = static function ( array $items ): string {
	$out = "<!-- wp:list -->\n<ul class=\"wp-block-list\">";
	foreach ( $items as $item ) {
		$out .= "<!-- wp:list-item -->\n<li>{$item}</li>\n<!-- /wp:list-item -->";
	}
	return $out . "</ul>\n<!-- /wp:list -->\n\n";
};

$root = $make(
	'Documentation',
	'docs',
	0,
	0,
	$p( 'Everything you need to set up and customise the theme. Use the navigation on the left, or the previous and next links at the bottom of each page.' )
	. $h( 'Where to start' )
	. $li( array( 'Install the theme and create a menu under <strong>Getting started</strong>.', 'Make it yours with the options under <strong>Customizing</strong>.', 'Build pages with the patterns described in <strong>Writing content</strong>.' ) )
);

$start = $make( 'Getting started', 'getting-started', $root, 1, $p( 'The theme works as soon as it is active. Two short steps make it feel like your own site.' ) );
$make( 'Installation', 'installation', $start, 1, $p( 'Upload the zip under Appearance, Themes, Add New, then activate it.' ) . $li( array( 'Requires WordPress 6.5 or later and PHP 8.1 or later.', 'No plugins are needed.' ) ) );
$make( 'Your first menu', 'first-menu', $start, 2, $p( 'Create a menu under Appearance, Menus and assign it to the <strong>Primary menu</strong> location. Items can be nested up to three levels deep.' ) );

$custom = $make( 'Customizing', 'customizing', $root, 2, $p( 'Open Appearance, Customize and look for the <strong>Basix</strong> panel.' ) );
$make( 'Colours and dark mode', 'colours', $custom, 1, $p( 'Pick an accent colour for light mode and one for dark mode. The default colour mode decides what visitors see first; they can always switch with the button in the header.' ) );
$make( 'Header layouts', 'header-layouts', $custom, 2, $p( 'Choose how the primary menu is shown:' ) . $li( array( '<strong>Inline</strong>: links in the header, with dropdowns for submenus.', '<strong>Flyout</strong>: a menu button that opens a side panel.', '<strong>Push</strong>: a menu button that slides the page aside, with drill-down panels.' ) ) );
$make( 'Footer and widgets', 'footer', $custom, 3, $p( 'The footer has three widget columns, a footer menu, a social links menu and a line of text you can edit in the Customizer.' ) );

$write = $make( 'Writing content', 'writing', $root, 3, $p( 'Pages and posts are built from blocks.' ) );
$make( 'Blocks and patterns', 'patterns', $write, 1, $p( 'Open the block inserter and choose <strong>Patterns</strong>, then <strong>Basix</strong>, for a hero, feature cards, steps, a timeline, an FAQ, a call to action and stats.' ) );

echo $root, "\n";
