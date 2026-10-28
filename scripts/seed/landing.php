<?php
/**
 * Creates a "Landing page demo" page from the theme's patterns, using the Landing page template.
 * Run by seed-content.sh with `wp eval-file`; prints the page id.
 */

$pattern = static function ( string $slug ): string {
	$p = WP_Block_Patterns_Registry::get_instance()->get_registered( $slug );
	return $p ? $p['content'] : '';
};

$content = implode(
	"\n\n",
	array_map( $pattern, array( 'basix/hero-cover', 'basix/features', 'basix/stats', 'basix/steps', 'basix/faq', 'basix/cta' ) )
);

foreach ( get_posts( array( 'post_type' => 'page', 'name' => 'landing-demo', 'post_status' => 'any', 'numberposts' => -1 ) ) as $old ) {
	wp_delete_post( $old->ID, true );
}

echo wp_insert_post(
	array(
		'post_type'    => 'page',
		'post_status'  => 'publish',
		'post_title'   => 'Landing page demo',
		'post_name'    => 'landing-demo',
		'post_author'  => 1,
		'post_content' => $content,
		'meta_input'   => array( '_wp_page_template' => 'page-templates/landing.php' ),
	)
), "\n";
