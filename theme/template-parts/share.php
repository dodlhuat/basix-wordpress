<?php
/**
 * Share links. They are plain links without JavaScript; the share component turns them into a Share
 * button with a popover (a bottom sheet on small screens) and a copy link entry.
 *
 * @package basix
 */

if ( ! basix_feature( 'basix_show_share' ) ) {
	return;
}
?>
<section class="share" 
<?php
echo basix_component_attrs(
	'share',
	array(
		'url'    => get_permalink(),
		'share'  => __( 'Share', 'basix' ),
		'copy'   => __( 'Copy link', 'basix' ),
		'copied' => __( 'Link copied', 'basix' ),
	)
);
?>
>
	<h2 class="share-title"><?php esc_html_e( 'Share this post', 'basix' ); ?></h2>
	<ul class="share-links chips">
		<?php foreach ( basix_share_links() as $basix_label => $basix_url ) : ?>
			<li><a class="chip clickable" href="<?php echo esc_url( $basix_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $basix_label ); ?></a></li>
		<?php endforeach; ?>
	</ul>
</section>
