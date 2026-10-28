<?php
/**
 * Creates a long demo post that uses what the post features show off: several headings (for the table of
 * contents), code blocks with a language, a sortable table, and images with captions and a gallery.
 * Run by seed-content.sh with `wp eval-file`; prints the post id.
 */

$images = get_posts(
	array(
		'post_type'   => 'attachment',
		'numberposts' => 6,
		'orderby'     => 'ID',
		'order'       => 'ASC',
		'post_status' => 'inherit',
	)
);
$count  = max( 1, count( $images ) );
$full   = static fn( int $i ) => wp_get_attachment_url( $images[ $i % $count ]->ID );
$large  = static fn( int $i ) => wp_get_attachment_image_url( $images[ $i % $count ]->ID, 'large' );
$id     = static fn( int $i ) => $images[ $i % $count ]->ID;
$alt    = static fn( int $i ) => esc_attr( (string) get_post_meta( $id( $i ), '_wp_attachment_image_alt', true ) );

$p  = static fn( string $text ): string => "<!-- wp:paragraph -->\n<p>{$text}</p>\n<!-- /wp:paragraph -->\n\n";
$h2 = static fn( string $text ): string => "<!-- wp:heading -->\n<h2 class=\"wp-block-heading\">{$text}</h2>\n<!-- /wp:heading -->\n\n";
$h3 = static fn( string $text ): string => "<!-- wp:heading {\"level\":3} -->\n<h3 class=\"wp-block-heading\">{$text}</h3>\n<!-- /wp:heading -->\n\n";
$code = static fn( string $lang, string $source ): string => "<!-- wp:code {\"className\":\"language-{$lang}\"} -->\n<pre class=\"wp-block-code language-{$lang}\"><code>" . esc_html( $source ) . "</code></pre>\n<!-- /wp:code -->\n\n";

$gallery = '';
foreach ( array( 1, 2, 3 ) as $i ) {
	$gallery .= sprintf(
		"<!-- wp:image {\"id\":%1\$d,\"sizeSlug\":\"large\",\"linkDestination\":\"media\"} -->\n<figure class=\"wp-block-image size-large\"><a href=\"%2\$s\"><img src=\"%3\$s\" alt=\"%4\$s\" class=\"wp-image-%1\$d\"/></a></figure>\n<!-- /wp:image -->\n\n",
		$id( $i ),
		esc_url( $full( $i ) ),
		esc_url( $large( $i ) ),
		$alt( $i )
	);
}

$content =
	$p( 'This post is long on purpose. It has enough headings for a table of contents, code in three languages, a table you can sort, and pictures you can open full size. Scroll down and watch the progress bar at the top of the window.' )
	. $h2( 'Why tokens matter' )
	. $p( 'A design token is a named value: a colour, a radius, a spacing step. Components read the name, never the raw value, so changing the value changes every component at once.' )
	. $h3( 'Two layers are enough' )
	. $p( 'A semantic layer on top of component defaults covers almost every case, and keeps the system readable.' )
	. $code( 'css', ":root {\n  --accent-color: #3d63dd;\n  --radius-md: 0.6rem;\n}\n\n.button-primary {\n  background: var(--accent-color);\n  border-radius: var(--radius-md);\n}" )
	. $h3( 'Dark mode is a second set of values' )
	. $p( 'Nothing in a component changes. Only the values behind the names do, which is why a theme switch can be one attribute on the root element.' )
	. $code( 'js', "const root = document.documentElement;\n\n// Apply the saved choice before the first paint.\nconst saved = localStorage.getItem('theme');\nif (saved) {\n  root.setAttribute('data-theme', saved);\n}" )
	. $h2( 'Building a page from components' )
	. $p( 'Small components compose into pages. The markup stays plain, and the classes describe what each piece is.' )
	. $code( 'html', "<article class=\"card card-bordered\">\n  <h2 class=\"card-title\">A card</h2>\n  <p>Some text.</p>\n  <a class=\"button button-primary\" href=\"#\">Read more</a>\n</article>" )
	. $code( 'php', "<?php\nforeach ( \$posts as \$post ) {\n\tget_template_part( 'template-parts/content', 'card' );\n}" )
	. $h2( 'Comparing the options' )
	. $p( 'Click a column heading to sort this table. Click again to reverse the order, and a third time to restore it.' )
	. "<!-- wp:table {\"className\":\"is-style-sortable\"} -->\n<figure class=\"wp-block-table is-style-sortable\"><table class=\"has-fixed-layout\"><thead><tr><th>Approach</th><th>Setup time</th><th>Flexibility</th></tr></thead><tbody><tr><td>Hard-coded values</td><td>1 hour</td><td>Low</td></tr><tr><td>One token layer</td><td>1 day</td><td>Medium</td></tr><tr><td>Two token layers</td><td>3 days</td><td>High</td></tr><tr><td>Full theming engine</td><td>3 weeks</td><td>Very high</td></tr></tbody></table><figcaption class=\"wp-element-caption\">Setup time grows faster than flexibility</figcaption></figure>\n<!-- /wp:table -->\n\n"
	. $h2( 'A few pictures' )
	. $p( 'Click any image to open it full size. Use the arrow keys, or the buttons, to move between them.' )
	. sprintf(
		"<!-- wp:image {\"id\":%1\$d,\"sizeSlug\":\"large\",\"linkDestination\":\"none\"} -->\n<figure class=\"wp-block-image size-large\"><img src=\"%2\$s\" alt=\"%3\$s\" class=\"wp-image-%1\$d\"/><figcaption class=\"wp-element-caption\">An image with a caption</figcaption></figure>\n<!-- /wp:image -->\n\n",
		$id( 0 ),
		esc_url( $large( 0 ) ),
		$alt( 0 )
	)
	. $gallery
	. $h2( 'Wrapping up' )
	. $p( 'That is all the ingredients: names for values, components that read the names, and a page that is just components in a row. Start small, ship the tokens first, and let the components grow around them.' );

foreach ( get_posts( array( 'post_type' => 'post', 'name' => 'everything-in-one-post', 'post_status' => 'any', 'numberposts' => -1 ) ) as $old ) {
	wp_delete_post( $old->ID, true );
}

$engineering = get_term_by( 'slug', 'engineering', 'category' );
$post_id     = wp_insert_post(
	array(
		'post_type'     => 'post',
		'post_status'   => 'publish',
		'post_title'    => 'Everything in one post',
		'post_name'     => 'everything-in-one-post',
		'post_author'   => 1,
		'post_content'  => $content,
		'post_category' => $engineering ? array( $engineering->term_id ) : array(),
		'tags_input'    => array( 'tokens', 'css', 'dark-mode' ),
		'post_date'     => gmdate( 'Y-m-d H:i:s', time() - 12 * HOUR_IN_SECONDS ),
	)
);
if ( $images ) {
	set_post_thumbnail( $post_id, $id( 5 ) );
}
echo $post_id, "\n";
