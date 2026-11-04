<?php
/**
 * The author of a post, when they have written a biography.
 *
 * @package basix
 */

$basix_author_id = (int) get_the_author_meta( 'ID' );
$basix_bio       = get_the_author_meta( 'description', $basix_author_id );

if ( ! basix_feature( 'basix_show_author_box' ) || '' === trim( (string) $basix_bio ) ) {
	return;
}
?>
<aside class="author-box card card-bordered" aria-label="<?php esc_attr_e( 'About the author', 'basix-paperlight' ); ?>">
	<?php echo get_avatar( $basix_author_id, 72, '', '', array( 'class' => 'author-avatar' ) ); ?>
	<div class="author-details">
		<p class="author-name"><a href="<?php echo esc_url( get_author_posts_url( $basix_author_id ) ); ?>"><?php echo esc_html( get_the_author() ); ?></a></p>
		<p class="author-bio"><?php echo esc_html( $basix_bio ); ?></p>
	</div>
</aside>
