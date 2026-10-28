<?php
/**
 * Menu locations and the three ways the primary menu is rendered.
 *
 * @package basix
 */

add_action(
	'after_setup_theme',
	function () {
		register_nav_menus(
			array(
				'primary' => __( 'Primary menu', 'basix' ),
				'footer'  => __( 'Footer menu', 'basix' ),
				'social'  => __( 'Social links', 'basix' ),
			)
		);
	}
);

/**
 * The header layout chosen in the Customizer: inline, flyout or push.
 *
 * @return string
 */
function basix_header_layout(): string {
	$layout = get_theme_mod( 'basix_header_layout', 'inline' );
	return in_array( $layout, array( 'inline', 'flyout', 'push' ), true ) ? $layout : 'inline';
}

add_filter(
	'body_class',
	function ( array $classes ): array {
		$classes[] = 'header-layout-' . basix_header_layout();
		return $classes;
	}
);

/**
 * Prints the primary menu for one header variant.
 *
 * - inline: Basix dropdowns for the desktop header
 * - drawer: markup for the Basix flyout (mobile, or every width in the flyout layout)
 * - push:   plain nested lists, which the Basix push menu turns into drill-down panels
 *
 * @param string $variant inline, drawer or push.
 */
function basix_primary_menu( string $variant ): void {
	if ( ! has_nav_menu( 'primary' ) ) {
		return;
	}

	switch ( $variant ) {
		case 'inline':
			wp_nav_menu(
				array(
					'theme_location'       => 'primary',
					'depth'                => 3,
					'fallback_cb'          => false,
					'container'            => 'nav',
					'container_class'      => 'site-nav',
					'container_id'         => 'site-nav',
					'container_aria_label' => __( 'Primary menu', 'basix' ),
					'menu_class'           => 'site-nav-list',
					'walker'               => new Basix_Dropdown_Walker(),
				)
			);
			break;
		case 'drawer':
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'depth'          => 3,
					'fallback_cb'    => false,
					'container'      => false,
					'items_wrap'     => '<ul>%3$s</ul>',
					'walker'         => new Basix_Drawer_Walker(),
				)
			);
			break;
		default:
			wp_nav_menu(
				array(
					'theme_location' => 'primary',
					'depth'          => 3,
					'fallback_cb'    => false,
					'container'      => false,
					'items_wrap'     => '<ul>%3$s</ul>',
				)
			);
	}
}

/**
 * Prints the footer and social menus.
 */
function basix_footer_menus(): void {
	if ( has_nav_menu( 'footer' ) ) {
		wp_nav_menu(
			array(
				'theme_location'       => 'footer',
				'depth'                => 1,
				'container'            => 'nav',
				'container_aria_label' => __( 'Footer menu', 'basix' ),
				'container_class'      => 'footer-nav',
				'menu_class'           => 'footer-nav-list',
				'fallback_cb'          => false,
			)
		);
	}
	if ( has_nav_menu( 'social' ) ) {
		wp_nav_menu(
			array(
				'theme_location'       => 'social',
				'depth'                => 1,
				'container'            => 'nav',
				'container_class'      => 'footer-nav footer-social',
				'container_aria_label' => __( 'Social links', 'basix' ),
				'menu_class'           => 'footer-nav-list',
				'link_before'          => '<span class="screen-reader-text">' . esc_html__( 'Follow on', 'basix' ) . ' </span>',
				'fallback_cb'          => false,
			)
		);
	}
}
