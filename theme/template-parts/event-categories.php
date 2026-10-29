<?php
/**
 * Category chips above the events list: All, then each event category.
 *
 * @package basix
 */

$basix_terms = get_terms( array( 'taxonomy' => BASIX_EVENT_CAT ) );
if ( is_wp_error( $basix_terms ) || count( $basix_terms ) < 2 ) {
	return;
}
$basix_current = is_tax( BASIX_EVENT_CAT ) ? get_queried_object_id() : 0;
?>
<div class="filter-bar">
	<nav class="chips filter-chips" aria-label="<?php esc_attr_e( 'Event categories', 'basix' ); ?>">
		<a class="chip clickable<?php echo $basix_current ? '' : ' selected'; ?>" href="<?php echo esc_url( get_post_type_archive_link( BASIX_EVENT ) ); ?>"><?php esc_html_e( 'All', 'basix' ); ?></a>
		<?php foreach ( $basix_terms as $basix_term ) : ?>
			<a class="chip clickable<?php echo $basix_current === $basix_term->term_id ? ' selected' : ''; ?>" href="<?php echo esc_url( get_term_link( $basix_term ) ); ?>"><?php echo esc_html( $basix_term->name ); ?></a>
		<?php endforeach; ?>
	</nav>
</div>
