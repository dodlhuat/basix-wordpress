<?php
/**
 * Customizer settings and the helpers that read them.
 *
 * @package basix
 */

/**
 * Settings that are limited to a fixed set of values.
 *
 * @return array<string, string[]>
 */
function basix_choices(): array {
	return array(
		'basix_header_layout'    => array( 'inline', 'flyout', 'push' ),
		'basix_color_mode'       => array( 'system', 'light', 'dark' ),
		'basix_sidebar_position' => array( 'right', 'left', 'none' ),
		'basix_card_layout'      => array( 'grid', 'list', 'magazine' ),
		'basix_hero_style'       => array( 'card', 'minimal', 'none' ),
		'basix_look'             => array_keys( basix_looks() ),
		'basix_font_heading'     => array_merge( array( 'look' ), array_keys( basix_fonts() ) ),
		'basix_font_body'        => array_merge( array( 'look' ), array_keys( basix_fonts() ) ),
		'basix_text_size'        => array( 'small', 'medium', 'large' ),
	);
}

/**
 * Sanitizes a choice setting: anything outside the allowed values falls back to the default.
 *
 * @param string               $value   Submitted value.
 * @param WP_Customize_Setting $setting The setting.
 * @return string
 */
function basix_sanitize_choice( $value, $setting ) {
	$allowed = basix_choices()[ $setting->id ] ?? array();
	return in_array( $value, $allowed, true ) ? $value : $setting->default;
}

/**
 * Sanitizes a checkbox.
 *
 * @param mixed $value Submitted value.
 * @return bool
 */
function basix_sanitize_checkbox( $value ): bool {
	return (bool) $value;
}

add_action(
	'after_setup_theme',
	function () {
		add_theme_support(
			'custom-logo',
			array(
				'height'      => 80,
				'width'       => 320,
				'flex-height' => true,
				'flex-width'  => true,
			)
		);
	}
);

add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$wp_customize->add_panel(
			'basix',
			array(
				'title'    => __( 'Basix', 'basix' ),
				'priority' => 30,
			)
		);

		// Header.
		$wp_customize->add_section(
			'basix_header',
			array(
				'title' => __( 'Header', 'basix' ),
				'panel' => 'basix',
			)
		);
		$wp_customize->add_setting(
			'basix_header_layout',
			array(
				'default'           => 'inline',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_header_layout',
			array(
				'section'     => 'basix_header',
				'label'       => __( 'Menu layout', 'basix' ),
				'description' => __( 'How the primary menu is shown.', 'basix' ),
				'type'        => 'radio',
				'choices'     => array(
					'inline' => __( 'Inline links with dropdowns (menu button on small screens)', 'basix' ),
					'flyout' => __( 'Menu button opening a side panel', 'basix' ),
					'push'   => __( 'Menu button pushing the page aside, with drill-down panels', 'basix' ),
				),
			)
		);

		// Colours.
		$wp_customize->add_section(
			'basix_colors',
			array(
				'title' => __( 'Colours', 'basix' ),
				'panel' => 'basix',
			)
		);
		$wp_customize->add_setting(
			'basix_color_mode',
			array(
				'default'           => 'system',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_color_mode',
			array(
				'section'     => 'basix_colors',
				'label'       => __( 'Default colour mode', 'basix' ),
				'description' => __( 'What visitors see first. They can always switch with the button in the header.', 'basix' ),
				'type'        => 'radio',
				'choices'     => array(
					'system' => __( "Follow the visitor's device", 'basix' ),
					'light'  => __( 'Light', 'basix' ),
					'dark'   => __( 'Dark', 'basix' ),
				),
			)
		);
		$wp_customize->add_setting(
			'basix_accent_light',
			array(
				'default'           => basix_look()['light']['accent'] ?? '#3d63dd',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'basix_accent_light',
				array(
					'section'     => 'basix_colors',
					'label'       => __( 'Accent colour (light mode)', 'basix' ),
					'description' => __( 'Leave it as it is to use the look\'s own accent colour.', 'basix' ),
				)
			)
		);
		$wp_customize->add_setting(
			'basix_accent_dark',
			array(
				'default'           => basix_look()['dark']['accent'] ?? '#1971a6',
				'sanitize_callback' => 'sanitize_hex_color',
			)
		);
		$wp_customize->add_control(
			new WP_Customize_Color_Control(
				$wp_customize,
				'basix_accent_dark',
				array(
					'section' => 'basix_colors',
					'label'   => __( 'Accent colour (dark mode)', 'basix' ),
				)
			)
		);

		// Layout.
		$wp_customize->add_section(
			'basix_layout',
			array(
				'title' => __( 'Layout', 'basix' ),
				'panel' => 'basix',
			)
		);
		$wp_customize->add_setting(
			'basix_sidebar_position',
			array(
				'default'           => 'right',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_sidebar_position',
			array(
				'section' => 'basix_layout',
				'label'   => __( 'Sidebar', 'basix' ),
				'type'    => 'radio',
				'choices' => array(
					'right' => __( 'Right', 'basix' ),
					'left'  => __( 'Left', 'basix' ),
					'none'  => __( 'No sidebar', 'basix' ),
				),
			)
		);

		$wp_customize->add_setting(
			'basix_card_layout',
			array(
				'default'           => 'grid',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_card_layout',
			array(
				'section'     => 'basix_layout',
				'label'       => __( 'Post list layout', 'basix' ),
				'description' => __( 'How posts are shown on the blog, in archives and in search.', 'basix' ),
				'type'        => 'radio',
				'choices'     => array(
					'grid'     => __( 'Grid of cards', 'basix' ),
					'list'     => __( 'List, with the picture beside the text', 'basix' ),
					'magazine' => __( 'Magazine: a large first post, then a grid', 'basix' ),
				),
			)
		);

		// Front page.
		$wp_customize->add_section(
			'basix_front',
			array(
				'title' => __( 'Front page', 'basix' ),
				'panel' => 'basix',
			)
		);
		$wp_customize->add_setting(
			'basix_hero_style',
			array(
				'default'           => 'card',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_hero_style',
			array(
				'section'     => 'basix_front',
				'label'       => __( 'Introduction at the top', 'basix' ),
				'description' => __( 'It shows the site title and tagline (Settings, General).', 'basix' ),
				'type'        => 'radio',
				'choices'     => array(
					'card'    => __( 'Centred, in a card', 'basix' ),
					'minimal' => __( 'Left-aligned, without a card', 'basix' ),
					'none'    => __( 'None', 'basix' ),
				),
			)
		);

		// Footer.
		$wp_customize->add_section(
			'basix_footer',
			array(
				'title' => __( 'Footer', 'basix' ),
				'panel' => 'basix',
			)
		);
		$wp_customize->add_setting(
			'basix_footer_text',
			array(
				'default'           => '',
				'sanitize_callback' => 'wp_kses_post',
			)
		);
		$wp_customize->add_control(
			'basix_footer_text',
			array(
				'section'     => 'basix_footer',
				'label'       => __( 'Footer text', 'basix' ),
				'description' => __( 'Leave empty for the default copyright line. {year} and {site} are replaced.', 'basix' ),
				'type'        => 'textarea',
			)
		);
		$wp_customize->add_setting(
			'basix_back_to_top',
			array(
				'default'           => true,
				'sanitize_callback' => 'basix_sanitize_checkbox',
			)
		);
		$wp_customize->add_control(
			'basix_back_to_top',
			array(
				'section' => 'basix_footer',
				'label'   => __( 'Show the back to top button', 'basix' ),
				'type'    => 'checkbox',
			)
		);
	}
);

/**
 * Whether a sidebar is shown: it needs widgets, and the Customizer must not have switched it off.
 *
 * @return bool
 */
function basix_has_sidebar(): bool {
	return 'none' !== get_theme_mod( 'basix_sidebar_position', 'right' ) && is_active_sidebar( 'sidebar-1' );
}

/**
 * The footer line: the custom text with {year} and {site} replaced, or the default copyright.
 *
 * @return string HTML, already safe to print.
 */
function basix_footer_text(): string {
	$text = trim( (string) get_theme_mod( 'basix_footer_text', '' ) );
	if ( '' === $text ) {
		$text = '&copy; {year} {site}';
	}
	return wp_kses_post( str_replace( array( '{year}', '{site}' ), array( wp_date( 'Y' ), esc_html( get_bloginfo( 'name' ) ) ), $text ) );
}

/**
 * The red, green and blue parts (0 to 255) of a colour such as #3d63dd.
 *
 * @param string $hex Colour.
 * @return int[]
 */
function basix_rgb( string $hex ): array {
	$hex = ltrim( $hex, '#' );
	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}
	return array_map( 'hexdec', str_split( $hex, 2 ) );
}

/**
 * Relative luminance of a colour, as defined by WCAG.
 *
 * @param string $hex Colour.
 * @return float
 */
function basix_luminance( string $hex ): float {
	list( $r, $g, $b ) = array_map(
		function ( $c ) {
			$c /= 255;
			return $c <= 0.03928 ? $c / 12.92 : pow( ( $c + 0.055 ) / 1.055, 2.4 );
		},
		basix_rgb( $hex )
	);
	return 0.2126 * $r + 0.7152 * $g + 0.0722 * $b;
}

/**
 * Contrast ratio between two colours (1 to 21).
 *
 * @param string $a Colour.
 * @param string $b Colour.
 * @return float
 */
function basix_contrast( string $a, string $b ): float {
	$la = basix_luminance( $a );
	$lb = basix_luminance( $b );
	return ( max( $la, $lb ) + 0.05 ) / ( min( $la, $lb ) + 0.05 );
}

/**
 * Mixes two colours.
 *
 * @param string $a        First colour.
 * @param string $b        Second colour.
 * @param float  $weight_a Share of the first colour, 0 to 1.
 * @return string Hex colour.
 */
function basix_mix( string $a, string $b, float $weight_a ): string {
	$ca  = basix_rgb( $a );
	$cb  = basix_rgb( $b );
	$hex = '#';
	for ( $i = 0; $i < 3; $i++ ) {
		$hex .= sprintf( '%02x', (int) round( $ca[ $i ] * $weight_a + $cb[ $i ] * ( 1 - $weight_a ) ) );
	}
	return $hex;
}

/**
 * Light or dark text for a button in the given colour: whichever contrasts more.
 *
 * @param string $hex Colour such as #3d63dd.
 * @return string
 */
function basix_readable_on( string $hex ): string {
	return basix_contrast( $hex, '#ffffff' ) >= basix_contrast( $hex, '#1a1b1f' ) ? '#ffffff' : '#1a1b1f';
}

/**
 * A version of the accent colour that is readable as text on the page and on tinted surfaces (cards, badges,
 * selected items): the accent, mixed towards black (light mode) or white (dark mode) until the contrast is
 * at least 4.6 to 1 everywhere.
 *
 * @param string $accent Accent colour.
 * @param bool   $dark   Whether this is for dark mode.
 * @param string $page    Page colour (Basix's own when empty).
 * @param string $surface Surface colour (Basix's own when empty).
 * @return string
 */
function basix_accent_text( string $accent, bool $dark, string $page = '', string $surface = '' ): string {
	$page      = $page ? $page : ( $dark ? '#111113' : '#f9f9fb' );
	$surface   = $surface ? $surface : ( $dark ? '#19191b' : '#e7e8ec' );
	$surfaces  = array( $page, $surface, basix_mix( $accent, $surface, 0.2 ), basix_mix( $accent, $page, 0.12 ) );
	$towards   = $dark ? '#ffffff' : '#000000';
	for ( $step = 0; $step <= 20; $step++ ) {
		$candidate = basix_mix( $accent, $towards, 1 - $step * 0.05 );
		if ( min( array_map( fn( $s ) => basix_contrast( $candidate, $s ), $surfaces ) ) >= 4.6 ) {
			return $candidate;
		}
	}
	return $towards;
}

// The post list layout is a class on the page, so the stylesheet can switch it.
add_filter(
	'body_class',
	function ( array $classes ): array {
		$layout    = get_theme_mod( 'basix_card_layout', 'grid' );
		$classes[] = 'cards-' . ( in_array( $layout, basix_choices()['basix_card_layout'], true ) ? $layout : 'grid' );
		return $classes;
	}
);
