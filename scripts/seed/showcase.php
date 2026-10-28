<?php
/**
 * Creates (or replaces) a "Block showcase" page that uses the theme's patterns and the core blocks
 * it styles. Run by seed-content.sh with `wp eval-file`.
 */

$images = get_posts([
	'post_type'   => 'attachment',
	'numberposts' => 4,
	'orderby'     => 'ID',
	'order'       => 'ASC',
	'post_status' => 'inherit',
]);
$img = static fn( int $i ) => $images[ $i % max( 1, count( $images ) ) ] ?? null;
$src = static fn( int $i ) => $img( $i ) ? wp_get_attachment_image_url( $img( $i )->ID, 'large' ) : '';
$id  = static fn( int $i ) => $img( $i ) ? $img( $i )->ID : 0;

$pattern = static function ( string $slug ): string {
	$p = WP_Block_Patterns_Registry::get_instance()->get_registered( $slug );
	return $p ? $p['content'] : '';
};

$gallery = '';
foreach ( [ 0, 1, 2 ] as $i ) {
	$gallery .= sprintf(
		'<!-- wp:image {"id":%1$d,"sizeSlug":"large","linkDestination":"none"} -->' . "\n" .
		'<figure class="wp-block-image size-large"><img src="%2$s" alt="" class="wp-image-%1$d"/></figure>' . "\n" .
		'<!-- /wp:image -->' . "\n\n",
		$id( $i ),
		esc_url( $src( $i ) )
	);
}

$content = $pattern( 'basix/hero' ) . "\n\n" . <<<BLOCKS
<!-- wp:heading -->
<h2 class="wp-block-heading">Text and lists</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Body text sits at a comfortable reading size, with <a href="#">links</a>, <strong>bold</strong>, <em>italic</em> and <code>inline code</code>. Everything here is plain core blocks styled by the theme.</p>
<!-- /wp:paragraph -->

<!-- wp:list -->
<ul class="wp-block-list"><!-- wp:list-item -->
<li>An unordered list item</li>
<!-- /wp:list-item -->

<!-- wp:list-item -->
<li>Another item with a little more text</li>
<!-- /wp:list-item --></ul>
<!-- /wp:list -->

<!-- wp:quote -->
<blockquote class="wp-block-quote"><!-- wp:paragraph -->
<p>Polish is the sum of boring decisions made carefully.</p>
<!-- /wp:paragraph --><cite>A designer</cite></blockquote>
<!-- /wp:quote -->

<!-- wp:pullquote -->
<figure class="wp-block-pullquote"><blockquote><p>If you can't explain a token in one sentence, it probably shouldn't exist.</p><cite>Design systems</cite></blockquote></figure>
<!-- /wp:pullquote -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="#">Primary button</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-outline"} -->
<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" href="#">Outline button</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Code and tables</h2>
<!-- /wp:heading -->

<!-- wp:code -->
<pre class="wp-block-code"><code>:root {
  --accent-color: #3d63dd;
}</code></pre>
<!-- /wp:code -->

<!-- wp:table -->
<figure class="wp-block-table"><table class="has-fixed-layout"><thead><tr><th>Colour</th><th>Purpose</th><th>Default</th></tr></thead><tbody><tr><td>Accent</td><td>Links and buttons</td><td>#3d63dd</td></tr><tr><td>Divider</td><td>Borders</td><td>#d8d9e0</td></tr><tr><td>Secondary text</td><td>Captions</td><td>#62636c</td></tr></tbody></table><figcaption class="wp-element-caption">Three tokens from the Basix palette</figcaption></figure>
<!-- /wp:table -->

<!-- wp:heading -->
<h2 class="wp-block-heading">Images</h2>
<!-- /wp:heading -->

<!-- wp:image {"id":{$id(0)},"sizeSlug":"large","linkDestination":"none"} -->
<figure class="wp-block-image size-large"><img src="{$src(0)}" alt="" class="wp-image-{$id(0)}"/><figcaption class="wp-element-caption">A single image with a caption</figcaption></figure>
<!-- /wp:image -->

<!-- wp:gallery {"linkTo":"none"} -->
<figure class="wp-block-gallery has-nested-images columns-default is-cropped">
{$gallery}</figure>
<!-- /wp:gallery -->

<!-- wp:separator -->
<hr class="wp-block-separator has-alpha-channel-opacity"/>
<!-- /wp:separator -->

<!-- wp:columns -->
<div class="wp-block-columns"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Left column</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Columns stack on small screens.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading">Right column</h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Each column is an ordinary group of blocks.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns -->

<!-- wp:cover {"url":"{$src(1)}","id":{$id(1)},"dimRatio":50,"minHeight":260,"align":"wide"} -->
<div class="wp-block-cover alignwide" style="min-height:260px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim"></span><img class="wp-block-cover__image-background wp-image-{$id(1)}" alt="" src="{$src(1)}" data-object-fit="cover"/><div class="wp-block-cover__inner-container"><!-- wp:heading {"textAlign":"center","level":2} -->
<h2 class="wp-block-heading has-text-align-center">A wide cover block</h2>
<!-- /wp:heading --></div></div>
<!-- /wp:cover -->

BLOCKS;

$content .= "\n\n" . $pattern( 'basix/features' ) . "\n\n" . $pattern( 'basix/steps' ) . "\n\n" . $pattern( 'basix/stats' )
	. "\n\n" . $pattern( 'basix/timeline' ) . "\n\n" . $pattern( 'basix/faq' ) . "\n\n" . $pattern( 'basix/cta' );

foreach ( get_posts( [ 'post_type' => 'page', 'name' => 'block-showcase', 'post_status' => 'any', 'numberposts' => -1 ] ) as $old ) {
	wp_delete_post( $old->ID, true );
}
$page = wp_insert_post( [
	'post_type'    => 'page',
	'post_status'  => 'publish',
	'post_title'   => 'Block showcase',
	'post_name'    => 'block-showcase',
	'post_author'  => 1,
	'post_content' => $content,
] );
echo $page, "\n";
