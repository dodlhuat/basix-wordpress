<?php
/**
 * The top bar above the header: a short message, the social links and a language switcher.
 *
 * In the Customizer preview the places always exist, so the settings can change live.
 *
 * @package basix
 */

$basix_text     = trim( (string) get_theme_mod( 'basix_topbar_text', '' ) );
$basix_social   = get_theme_mod( 'basix_topbar_social', true ) && has_nav_menu( 'social' );
$basix_language = get_theme_mod( 'basix_topbar_language', true ) ? basix_language_switcher() : '';
$basix_preview  = is_customize_preview();
if ( '' === $basix_text && ! $basix_social && '' === $basix_language && ! $basix_preview ) {
	return;
}
?>
<div class="top-bar" role="region" aria-label="<?php esc_attr_e( 'Notice and links', 'basix' ); ?>">
	<div class="container top-bar-inner">
		<p class="top-bar-text"<?php echo '' === $basix_text ? ' hidden' : ''; ?>><?php echo esc_html( $basix_text ); ?></p>
		<?php if ( $basix_social ) : ?>
			<?php
			wp_nav_menu(
				array(
					'theme_location'       => 'social',
					'depth'                => 1,
					'container'            => 'nav',
					'container_class'      => 'top-bar-social',
					'container_aria_label' => __( 'Follow us', 'basix' ),
					'menu_class'           => 'top-bar-list',
					'link_before'          => '<span class="screen-reader-text">' . esc_html__( 'Follow on', 'basix' ) . ' </span>',
					'fallback_cb'          => false,
				)
			);
			?>
		<?php endif; ?>
		<?php echo $basix_language; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built from escaped parts in basix_language_switcher(). ?>
	</div>
</div>
