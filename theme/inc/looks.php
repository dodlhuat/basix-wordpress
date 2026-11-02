<?php
/**
 * Looks and typography.
 *
 * A look is a style variation file in /styles (a valid theme.json variation, with the Basix design data under
 * settings.custom.basix): colours for light and dark mode, the roundness of corners and a pair of fonts. The
 * Customizer's "Look" setting picks one; accent colours, fonts and text size can still be changed on top of it.
 *
 * @package basix
 */

/** Page and surface colours Basix itself ships with, per mode (the accent text is checked against them). */
const BASIX_SHIPPED_PAGE    = array(
	'light' => '#f9f9fb',
	'dark'  => '#111113',
);
const BASIX_SHIPPED_SURFACE = array(
	'light' => '#e7e8ec',
	'dark'  => '#19191b',
);

/**
 * The fonts a look or the Customizer can use: slug => name, stack, kind, and the self-hosted file (none for the
 * system font; Outfit is loaded by the Basix stylesheet).
 *
 * @return array<string, array{name: string, stack: string, file: string, weights: string}>
 */
function basix_fonts(): array {
	return array(
		'outfit'         => array(
			'name'    => 'Outfit',
			'stack'   => '"Outfit", helvetica, arial, sans-serif',
			'file'    => 'assets/vendor/basix/fonts/Outfit-VariableFont_wght.woff2',
			'weights' => '100 900',
		),
		'inter'          => array(
			'name'    => 'Inter',
			'stack'   => '"Inter", system-ui, -apple-system, "Segoe UI", helvetica, arial, sans-serif',
			'file'    => 'assets/fonts/inter-latin-wght-normal.woff2',
			'weights' => '100 900',
		),
		'space-grotesk'  => array(
			'name'    => 'Space Grotesk',
			'stack'   => '"Space Grotesk", system-ui, -apple-system, "Segoe UI", helvetica, arial, sans-serif',
			'file'    => 'assets/fonts/space-grotesk-latin-wght-normal.woff2',
			'weights' => '300 700',
		),
		'source-serif-4' => array(
			'name'    => 'Source Serif 4',
			'stack'   => '"Source Serif 4", georgia, "Times New Roman", serif',
			'file'    => 'assets/fonts/source-serif-4-latin-wght-normal.woff2',
			'weights' => '200 900',
		),
		'fraunces'       => array(
			'name'    => 'Fraunces',
			'stack'   => '"Fraunces", georgia, "Times New Roman", serif',
			'file'    => 'assets/fonts/fraunces-latin-wght-normal.woff2',
			'weights' => '100 900',
		),
		'system'         => array(
			'name'    => 'System',
			'stack'   => 'system-ui, -apple-system, "Segoe UI", helvetica, arial, sans-serif',
			'file'    => '',
			'weights' => '',
		),
	);
}

/** The corner roundness scales: name => value of --border-radius and of the --radius-* tokens. */
function basix_radius_scales(): array {
	return array(
		'none'    => array( 0, 0, 0, 0, 0, 0, 0 ),
		'sharp'   => array( 0.1, 0.05, 0.08, 0.15, 0.2, 0.25, 0.4 ),
		'default' => array( 0.4, 0.2, 0.3, 0.6, 0.8, 1, 1.6 ),
		'round'   => array( 0.8, 0.4, 0.6, 1, 1.4, 1.6, 2.4 ),
	);
}

/**
 * The titles and descriptions of the looks that ship with the theme, translatable.
 *
 * @return array<string, array{0: string, 1: string}>
 */
function basix_look_texts(): array {
	return array(
		'basix'     => array( __( 'Basix', 'basix' ), __( 'The shipped look: calm blue, Outfit type.', 'basix' ) ),
		'editorial' => array( __( 'Editorial', 'basix' ), __( 'Warm paper, terracotta accent and serif type, for long reading.', 'basix' ) ),
		'bold'      => array( __( 'Bold', 'basix' ), __( 'High contrast, violet accent and a geometric heading font.', 'basix' ) ),
		'calm'      => array( __( 'Calm', 'basix' ), __( 'Soft greens, rounded corners and friendly Inter type.', 'basix' ) ),
		'paper'     => array( __( 'Paper', 'basix' ), __( 'Black on white, square corners and a serif heading font.', 'basix' ) ),
	);
}

/**
 * Cleans the colours of one mode: only known keys, only valid hex colours.
 *
 * @param mixed $colors Raw colours.
 * @return array<string, string>
 */
function basix_look_colors( $colors ): array {
	$out = array();
	foreach ( array( 'background', 'surface', 'text', 'textSecondary', 'divider', 'accent' ) as $key ) {
		$hex = is_array( $colors ) && isset( $colors[ $key ] ) ? sanitize_hex_color( (string) $colors[ $key ] ) : '';
		if ( $hex ) {
			$out[ $key ] = strtolower( $hex );
		}
	}
	return $out;
}

/**
 * Every look, in order: slug => title, description, light and dark colours, radius and fonts.
 *
 * @return array<string, array{title: string, description: string, light: array, dark: array, radius: string, fonts: array{heading: string, body: string}}>
 */
function basix_looks(): array {
	static $looks = null;
	if ( null !== $looks ) {
		return $looks;
	}
	$looks = array();
	$texts = basix_look_texts();
	$fonts = basix_fonts();
	$radii = basix_radius_scales();
	$order = array();
	foreach ( (array) glob( get_theme_file_path( 'styles/*.json' ) ) as $file ) {
		$data = json_decode( (string) file_get_contents( $file ), true ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local file of the theme.
		$slug = sanitize_key( basename( $file, '.json' ) );
		$look = is_array( $data ) ? ( $data['settings']['custom']['basix'] ?? null ) : null;
		if ( ! $slug || ! is_array( $look ) ) {
			continue;
		}
		$title        = isset( $texts[ $slug ] ) ? $texts[ $slug ][0] : (string) ( $data['title'] ?? $slug );
		$description  = isset( $texts[ $slug ] ) ? $texts[ $slug ][1] : (string) ( $look['description'] ?? '' );
		$heading      = (string) ( $look['fonts']['heading'] ?? 'outfit' );
		$body         = (string) ( $look['fonts']['body'] ?? 'outfit' );
		$looks[ $slug ] = array(
			'title'       => $title,
			'description' => $description,
			'light'       => basix_look_colors( $look['light'] ?? array() ),
			'dark'        => basix_look_colors( $look['dark'] ?? array() ),
			'radius'      => isset( $radii[ $look['radius'] ?? '' ] ) ? $look['radius'] : 'default',
			'fonts'       => array(
				'heading' => isset( $fonts[ $heading ] ) ? $heading : 'outfit',
				'body'    => isset( $fonts[ $body ] ) ? $body : 'outfit',
			),
		);
		$order[ $slug ] = (int) ( $look['order'] ?? 100 );
	}
	uksort(
		$looks,
		static function ( $a, $b ) use ( $order ) {
			return array( $order[ $a ], $a ) <=> array( $order[ $b ], $b );
		}
	);
	return $looks;
}

/**
 * The slug of the chosen look (Basix when it is missing or gone).
 *
 * @return string
 */
function basix_look_slug(): string {
	$slug = (string) get_theme_mod( 'basix_look', 'basix' );
	return isset( basix_looks()[ $slug ] ) ? $slug : 'basix';
}

/**
 * The chosen look.
 *
 * @return array
 */
function basix_look(): array {
	$looks = basix_looks();
	return $looks[ basix_look_slug() ] ?? reset( $looks );
}

/**
 * The accent colour in use for a mode: the one chosen in the Customizer, or the look's own.
 *
 * @param string $mode light or dark.
 * @return string
 */
function basix_accent( string $mode ): string {
	$chosen = sanitize_hex_color( (string) get_theme_mod( 'basix_accent_' . $mode, '' ) );
	if ( $chosen ) {
		return strtolower( $chosen );
	}
	$look = basix_look();
	return $look[ $mode ]['accent'] ?? ( 'dark' === $mode ? '#1971a6' : '#3d63dd' );
}

/**
 * The font slug in use for a role: the one chosen in the Customizer, or the look's own.
 *
 * @param string $role heading or body.
 * @return string
 */
function basix_font( string $role ): string {
	$chosen = (string) get_theme_mod( 'basix_font_' . $role, 'look' );
	if ( 'look' !== $chosen && isset( basix_fonts()[ $chosen ] ) ) {
		return $chosen;
	}
	return basix_look()['fonts'][ $role ];
}

/**
 * Colour variables of one mode, as a CSS declaration list, and the accent text computed for them.
 *
 * @param string $mode light or dark.
 * @return string Empty when the mode has nothing to change.
 */
function basix_mode_vars( string $mode ): string {
	$look   = basix_look();
	$colors = $look[ $mode ];
	$accent = basix_accent( $mode );
	$shipped_accent = 'dark' === $mode ? '#1b7bb4' : '#3d63dd';
	$vars   = '';
	$map    = array(
		'background'    => array( '--background', '--primary-bg' ),
		'surface'       => array( '--secondary-background' ),
		'text'          => array( '--primary-text' ),
		'textSecondary' => array( '--secondary-text' ),
		'divider'       => array( '--divider' ),
	);
	foreach ( $map as $key => $names ) {
		if ( isset( $colors[ $key ] ) ) {
			foreach ( $names as $name ) {
				$vars .= $name . ':' . $colors[ $key ] . ';';
			}
		}
	}
	if ( strtolower( $accent ) !== $shipped_accent ) {
		$page    = $colors['background'] ?? BASIX_SHIPPED_PAGE[ $mode ];
		$surface = $colors['surface'] ?? BASIX_SHIPPED_SURFACE[ $mode ];
		$vars   .= sprintf( '--accent-color:%1$s;--on-accent:%2$s;--accent-text:%3$s;', $accent, basix_readable_on( $accent ), basix_accent_text( $accent, 'dark' === $mode, $page, $surface ) );
	}
	return $vars;
}

/**
 * The CSS font-face rule of a self-hosted font.
 *
 * @param string $slug Font slug.
 * @return string Empty for the system font.
 */
function basix_font_face( string $slug ): string {
	$font = basix_fonts()[ $slug ] ?? null;
	if ( ! $font || '' === $font['file'] || 'outfit' === $slug ) {
		return '';
	}
	return sprintf( '@font-face{font-family:"%1$s";src:url("%2$s") format("woff2");font-weight:%3$s;font-style:normal;font-display:swap}', $font['name'], esc_url( get_theme_file_uri( $font['file'] ) ), $font['weights'] );
}

/**
 * All the CSS that makes the chosen look, accent, fonts and text size: colours per mode (each rule scoped so it
 * cannot leak into the other mode, whether the mode comes from the data-theme attribute or, before JavaScript runs,
 * from the visitor's device), the corner scale, the font variables and rules, and the text size. Anything that
 * is what Basix already ships is left out, so the default look adds nothing but its dark accent.
 *
 * @return string
 */
function basix_design_css(): string {
	$css = '';
	$light = basix_mode_vars( 'light' );
	if ( $light ) {
		$css .= ':root[data-theme="light"]{' . $light . '}';
		$css .= '@media (prefers-color-scheme: light){:root:not([data-theme="dark"]){' . $light . '}}';
	}
	$dark = basix_mode_vars( 'dark' );
	if ( $dark ) {
		$css .= ':root[data-theme="dark"]{' . $dark . '}';
		$css .= '@media (prefers-color-scheme: dark){:root:not([data-theme="light"]){' . $dark . '}}';
	}

	$scale = basix_radius_scales()[ basix_look()['radius'] ];
	if ( 'default' !== basix_look()['radius'] ) {
		$names = array( '--border-radius', '--radius-xs', '--radius-sm', '--radius-md', '--radius-lg', '--radius-panel', '--radius-xl' );
		$vars  = '';
		foreach ( $names as $index => $name ) {
			$vars .= $name . ':' . $scale[ $index ] . 'rem;';
		}
		$css .= ':root{' . $vars . '}';
	}

	$fonts   = basix_fonts();
	$heading = basix_font( 'heading' );
	$body    = basix_font( 'body' );
	$css    .= basix_font_face( $body );
	if ( $heading !== $body ) {
		$css .= basix_font_face( $heading );
	}
	if ( 'outfit' !== $body ) {
		$css .= 'body{font-family:' . $fonts[ $body ]['stack'] . '}';
	}
	if ( $heading !== $body ) {
		$css .= 'h1,h2,h3,h4,h5,h6,.h1,.h2,.h3,.h4,.h5,.h6,.card-title,.site-title,.stat-value{font-family:' . $fonts[ $heading ]['stack'] . '}';
	}

	$size = (string) get_theme_mod( 'basix_text_size', 'medium' );
	if ( 'small' === $size ) {
		$css .= 'html{font-size:93.75%}';
	} elseif ( 'large' === $size ) {
		$css .= 'html{font-size:112.5%}';
	}
	return $css;
}

add_action(
	'wp_enqueue_scripts',
	function () {
		$css = basix_design_css();
		if ( $css ) {
			wp_add_inline_style( 'basix-theme', $css );
		}
	},
	20
);

// The block editor shows the same look.
add_filter(
	'block_editor_settings_all',
	function ( array $settings ): array {
		$css = basix_design_css();
		if ( $css ) {
			$settings['styles'][] = array( 'css' => $css );
		}
		return $settings;
	}
);

// The fonts of the page are fetched as early as possible, so the text does not jump when they arrive.
add_action(
	'wp_head',
	function () {
		$fonts = basix_fonts();
		foreach ( array_unique( array( basix_font( 'body' ), basix_font( 'heading' ) ) ) as $slug ) {
			if ( '' !== $fonts[ $slug ]['file'] ) {
				printf( '<link rel="preload" href="%s" as="font" type="font/woff2" crossorigin>' . "\n", esc_url( get_theme_file_uri( $fonts[ $slug ]['file'] ) ) );
			}
		}
	},
	1
);

// Customizer: the look, and the typography.
add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$looks = array();
		foreach ( basix_looks() as $slug => $look ) {
			$looks[ $slug ] = $look['title'] . ' – ' . $look['description'];
		}
		$wp_customize->add_section(
			'basix_look',
			array(
				'title'       => __( 'Look', 'basix' ),
				'description' => __( 'A look sets the colours (light and dark), corners and fonts together. Everything below can still be changed on top of it.', 'basix' ),
				'panel'       => 'basix',
				'priority'    => 1,
			)
		);
		$wp_customize->add_setting(
			'basix_look',
			array(
				'default'           => 'basix',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_look',
			array(
				'section' => 'basix_look',
				'label'   => __( 'Look', 'basix' ),
				'type'    => 'radio',
				'choices' => $looks,
			)
		);

		$font_choices = array( 'look' => __( "Follow the look's font", 'basix' ) );
		foreach ( basix_fonts() as $slug => $font ) {
			$font_choices[ $slug ] = 'system' === $slug ? __( 'System font (nothing to download)', 'basix' ) : $font['name'];
		}
		$wp_customize->add_section(
			'basix_type',
			array(
				'title' => __( 'Typography', 'basix' ),
				'panel' => 'basix',
			)
		);
		foreach ( array(
			'heading' => __( 'Heading font', 'basix' ),
			'body'    => __( 'Text font', 'basix' ),
		) as $role => $label ) {
			$wp_customize->add_setting(
				'basix_font_' . $role,
				array(
					'default'           => 'look',
					'sanitize_callback' => 'basix_sanitize_choice',
				)
			);
			$wp_customize->add_control(
				'basix_font_' . $role,
				array(
					'section' => 'basix_type',
					'label'   => $label,
					'type'    => 'select',
					'choices' => $font_choices,
				)
			);
		}
		$wp_customize->add_setting(
			'basix_text_size',
			array(
				'default'           => 'medium',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_text_size',
			array(
				'section'     => 'basix_type',
				'label'       => __( 'Text size', 'basix' ),
				'description' => __( 'Scales the whole page: text, spacing and buttons.', 'basix' ),
				'type'        => 'radio',
				'choices'     => array(
					'small'  => __( 'Small', 'basix' ),
					'medium' => __( 'Medium', 'basix' ),
					'large'  => __( 'Large', 'basix' ),
				),
			)
		);
	},
	11
);
