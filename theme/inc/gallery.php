<?php
/**
 * Settings and fallback for the Photo gallery page template.
 *
 * @package basix
 */

/** How many images the gallery loads at a time. */
const BASIX_GALLERY_PAGE_SIZE = 12;

/**
 * The page's own images: those uploaded to this page. When there are none, the whole media library is shown.
 *
 * @return int Page id, or 0 for the whole library.
 */
function basix_gallery_parent(): int {
	$attached = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_parent'    => get_the_ID(),
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
		)
	);
	return $attached ? (int) get_the_ID() : 0;
}

/**
 * Settings for the gallery's JavaScript.
 *
 * @param int $page_id Page whose images to show, or 0 for the whole library.
 * @return array
 */
function basix_gallery_config( int $page_id ): array {
	return array(
		'restUrl' => rest_url( 'wp/v2/media' ),
		'perPage' => BASIX_GALLERY_PAGE_SIZE,
		'parent'  => $page_id,
		'error'   => __( 'The images could not be loaded. Please try again.', 'basix' ),
	);
}

/**
 * The first page of images as a plain list of links, shown when JavaScript is off.
 *
 * @param int $page_id Page whose images to show, or 0 for the whole library.
 * @return int[] Attachment ids.
 */
function basix_gallery_first_images( int $page_id ): array {
	return get_posts(
		array(
			'post_type'      => 'attachment',
			'post_mime_type' => 'image',
			'post_status'    => 'inherit',
			'post_parent'    => $page_id ? $page_id : null,
			'posts_per_page' => BASIX_GALLERY_PAGE_SIZE,
			'orderby'        => 'date',
			'order'          => 'DESC',
			'fields'         => 'ids',
		)
	);
}
