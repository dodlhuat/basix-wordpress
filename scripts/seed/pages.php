<?php
/**
 * Creates the Calendar and Photo gallery pages. Run by seed-content.sh with `wp eval-file`;
 * prints the two page ids, calendar first.
 */

$make = static function ( string $title, string $slug, string $template, string $intro ): int {
	foreach ( get_posts( array( 'post_type' => 'page', 'name' => $slug, 'post_status' => 'any', 'numberposts' => -1 ) ) as $old ) {
		wp_delete_post( $old->ID, true );
	}
	return (int) wp_insert_post(
		array(
			'post_type'    => 'page',
			'post_status'  => 'publish',
			'post_author'  => 1,
			'post_title'   => $title,
			'post_name'    => $slug,
			'post_content' => "<!-- wp:paragraph -->\n<p>{$intro}</p>\n<!-- /wp:paragraph -->",
			'meta_input'   => array( '_wp_page_template' => $template ),
		)
	);
};

echo $make( 'Calendar', 'calendar', 'page-templates/calendar.php', 'What is coming up, on a calendar. Switch between month, week and agenda, and click an event for the details.' ), "\n";
echo $make( 'Photo gallery', 'photo-gallery', 'page-templates/gallery.php', 'All the images on this site, in a gallery that keeps loading as you scroll. Click an image to see it full size.' ), "\n";
