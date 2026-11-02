<?php
/**
 * Creates the "Pattern library" page: every Basix pattern except the heroes, one after the other with its name above.
 * It shows what the patterns look like on a real page, and the checks run axe on it. Run by seed-content.sh with
 * `wp eval-file`. An existing page of that name is replaced.
 */

foreach ( get_posts( array( 'post_type' => 'page', 'name' => 'pattern-library', 'post_status' => 'any', 'numberposts' => -1 ) ) as $old ) {
	wp_delete_post( $old->ID, true );
}

$content = '';
foreach ( WP_Block_Patterns_Registry::get_instance()->get_all_registered() as $pattern ) {
	if ( 0 !== strpos( $pattern['name'], 'basix/' ) || 0 === strpos( $pattern['name'], 'basix/hero' ) ) {
		continue;
	}
	$content .= "<!-- wp:paragraph {\"className\":\"text-overline\"} -->\n<p class=\"text-overline\">" . esc_html( $pattern['title'] ) . "</p>\n<!-- /wp:paragraph -->\n" . $pattern['content'] . "\n\n";
}

$id = wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Pattern library',
		'post_name'    => 'pattern-library',
		'post_author'  => 1,
		'post_content' => $content,
	)
);
echo $id, "\n";
