<?php
/**
 * Share links under posts (email and the common networks), with a Customizer switch.
 *
 * They are plain links. The Basix Paperlight theme prints them at its basix_entry_after_content hook and turns them
 * into a Share button with a popover (a bottom sheet on phones) and a "Copy link" entry. The WordPress.org theme
 * directory counts share buttons as plugin territory, so they live here and not in the theme.
 *
 * @package basix-paperlight-events
 */

/**
 * Where a post can be shared: label => URL. Email is first; the rest are common networks.
 *
 * @return array<string, string>
 */
function basix_share_links(): array {
	$url   = rawurlencode( get_permalink() );
	$title = rawurlencode( wp_strip_all_tags( get_the_title() ) );
	return (array) apply_filters(
		'basix_share_links',
		array(
			__( 'Email', 'basix-paperlight-events' ) => 'mailto:?subject=' . $title . '&body=' . $url,
			'X'                                      => 'https://twitter.com/intent/tweet?url=' . $url . '&text=' . $title,
			'LinkedIn'                               => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $url,
			'Facebook'                               => 'https://www.facebook.com/sharer/sharer.php?u=' . $url,
			'Bluesky'                                => 'https://bsky.app/intent/compose?text=' . $title . '%20' . $url,
		)
	);
}

/**
 * Whether share links are switched on (Customizer > Basix > Posts). On by default.
 *
 * @return bool
 */
function basix_share_enabled(): bool {
	return (bool) get_theme_mod( 'basix_show_share', true );
}

/**
 * Prints the share links of the current post.
 */
function basix_share_section(): void {
	if ( ! is_singular( 'post' ) || ! basix_share_enabled() ) {
		return;
	}
	$config = array(
		'url'    => get_permalink(),
		'share'  => __( 'Share', 'basix-paperlight-events' ),
		'copy'   => __( 'Copy link', 'basix-paperlight-events' ),
		'copied' => __( 'Link copied', 'basix-paperlight-events' ),
	);
	?>
	<section class="share" data-basix="share" data-config="<?php echo esc_attr( wp_json_encode( $config ) ); ?>">
		<h2 class="share-title"><?php esc_html_e( 'Share this post', 'basix-paperlight-events' ); ?></h2>
		<ul class="share-links chips">
			<?php foreach ( basix_share_links() as $basix_label => $basix_url ) : ?>
				<li><a class="chip clickable" href="<?php echo esc_url( $basix_url ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $basix_label ); ?></a></li>
			<?php endforeach; ?>
		</ul>
	</section>
	<?php
}
add_action( 'basix_entry_after_content', 'basix_share_section' );

// The switch sits with the theme's other post features when the theme has that section.
add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		if ( ! $wp_customize->get_section( 'basix_posts' ) ) {
			return;
		}
		$wp_customize->add_setting(
			'basix_show_share',
			array(
				'default'           => true,
				'sanitize_callback' => 'rest_sanitize_boolean',
			)
		);
		$wp_customize->add_control(
			'basix_show_share',
			array(
				'section'  => 'basix_posts',
				'label'    => __( 'Show share links', 'basix-paperlight-events' ),
				'type'     => 'checkbox',
				'priority' => 40,
			)
		);
	},
	20
);
