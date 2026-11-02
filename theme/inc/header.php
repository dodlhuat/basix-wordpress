<?php
/**
 * Header and footer options: sticky or static header, a header that sits transparent over the first section, a
 * call-to-action button, an optional top bar, footer layouts, a header image, and a small box on pages and posts
 * to change the header, sidebar, title and footer for that page alone.
 *
 * @package basix
 */

/** The per-page options: meta key => allowed values (an empty string means "as set in the Customizer"). */
const BASIX_PAGE_OPTIONS = array(
	'_basix_header'     => array( '', 'transparent', 'none' ),
	'_basix_sidebar'    => array( '', 'left', 'right', 'none' ),
	'_basix_hide_title' => array( '', '1' ),
	'_basix_footer'     => array( '', 'none' ),
);

add_action(
	'after_setup_theme',
	function () {
		add_theme_support(
			'custom-header',
			array(
				'width'       => 1600,
				'height'      => 700,
				'flex-width'  => true,
				'flex-height' => true,
				'header-text' => false,
			)
		);
	}
);

/**
 * The page the per-page options belong to: the singular post, or the page chosen for the blog.
 *
 * @return WP_Post|null
 */
function basix_options_post(): ?WP_Post {
	if ( is_singular() ) {
		$post = get_queried_object();
		return $post instanceof WP_Post ? $post : null;
	}
	if ( is_home() && ! is_front_page() ) {
		$post = get_post( (int) get_option( 'page_for_posts' ) );
		return $post instanceof WP_Post ? $post : null;
	}
	return null;
}

/**
 * One per-page option of the current page ('' when not set or not allowed).
 *
 * @param string $key Meta key, such as _basix_header.
 * @return string
 */
function basix_page_option( string $key ): string {
	$post = basix_options_post();
	if ( ! $post || ! isset( BASIX_PAGE_OPTIONS[ $key ] ) ) {
		return '';
	}
	$value = (string) get_post_meta( $post->ID, $key, true );
	return in_array( $value, BASIX_PAGE_OPTIONS[ $key ], true ) ? $value : '';
}

/**
 * How the header looks on this page: none, transparent or default. The page's own choice beats the Customizer's.
 *
 * @return string
 */
function basix_header_mode(): string {
	if ( basix_builder_location( 'header', false ) ) {
		return 'none';
	}
	$page = basix_page_option( '_basix_header' );
	if ( '' !== $page ) {
		return $page;
	}
	return 'transparent' === get_theme_mod( 'basix_header_style', 'default' ) ? 'transparent' : 'default';
}

/**
 * The sidebar position on this page: the page's own choice, else none for a page, else the Customizer's.
 *
 * @return string left, right or none.
 */
function basix_sidebar_position(): string {
	$own = basix_page_option( '_basix_sidebar' );
	if ( '' !== $own ) {
		return $own;
	}
	if ( is_page() ) {
		return 'none';
	}
	$position = (string) get_theme_mod( 'basix_sidebar_position', 'right' );
	return in_array( $position, array( 'left', 'right', 'none' ), true ) ? $position : 'right';
}

/**
 * The class that hides a title visually but keeps it for screen readers, when the page asks for that.
 *
 * @return string
 */
function basix_title_class(): string {
	return '1' === basix_page_option( '_basix_hide_title' ) ? ' screen-reader-text' : '';
}

/**
 * The footer layout: columns, centered or minimal.
 *
 * @return string
 */
function basix_footer_layout(): string {
	$layout = (string) get_theme_mod( 'basix_footer_layout', 'columns' );
	return in_array( $layout, basix_choices()['basix_footer_layout'], true ) ? $layout : 'columns';
}

/**
 * Whether the top bar has something to show (or the Customizer preview needs its places to exist).
 *
 * @return bool
 */
function basix_top_bar_enabled(): bool {
	return (bool) get_theme_mod( 'basix_topbar_enable', false ) && 'none' !== basix_header_mode();
}

/**
 * The language switcher of Polylang or WPML as a list, or an empty string. Filter basix_language_switcher to
 * supply another one.
 *
 * @return string HTML.
 */
function basix_language_switcher(): string {
	$html = '';
	if ( function_exists( 'pll_the_languages' ) ) {
		$items = pll_the_languages(
			array(
				'echo'          => 0,
				'show_flags'    => 0,
				'show_names'    => 1,
				'hide_if_empty' => 0,
			)
		);
		$html  = $items ? '<ul class="top-bar-languages">' . $items . '</ul>' : '';
	} elseif ( function_exists( 'icl_get_languages' ) ) {
		$items = array();
		foreach ( (array) icl_get_languages( 'skip_missing=0' ) as $language ) {
			$items[] = sprintf(
				'<li%s><a href="%s" lang="%s">%s</a></li>',
				! empty( $language['active'] ) ? ' class="current-lang"' : '',
				esc_url( $language['url'] ),
				esc_attr( $language['language_code'] ),
				esc_html( $language['native_name'] )
			);
		}
		$html = $items ? '<ul class="top-bar-languages">' . implode( '', $items ) . '</ul>' : '';
	}
	return (string) apply_filters( 'basix_language_switcher', $html );
}

/**
 * The header's call-to-action: label and address, both needed.
 *
 * @return array{label: string, url: string}
 */
function basix_header_cta(): array {
	return array(
		'label' => trim( (string) get_theme_mod( 'basix_header_cta_label', '' ) ),
		'url'   => esc_url_raw( (string) get_theme_mod( 'basix_header_cta_url', '' ) ),
	);
}

add_filter(
	'body_class',
	function ( array $classes ): array {
		$mode      = basix_header_mode();
		$classes[] = 'header-' . $mode;
		$classes[] = get_theme_mod( 'basix_header_sticky', true ) ? 'header-sticky' : 'header-static';
		$classes[] = 'footer-' . ( 'none' === basix_page_option( '_basix_footer' ) ? 'none' : basix_footer_layout() );
		if ( basix_top_bar_enabled() ) {
			$classes[] = 'has-top-bar';
		}
		return $classes;
	}
);

// The header image is the backdrop of the front page introduction.
add_filter(
	'basix_hero_attributes',
	function ( string $attributes ): string {
		if ( ! has_header_image() ) {
			return $attributes;
		}
		return $attributes . sprintf( ' style="--hero-image:url(\'%s\')"', esc_url( get_header_image() ) );
	}
);

// Settings in the Customizer.
add_action(
	'customize_register',
	function ( WP_Customize_Manager $wp_customize ) {
		$checkbox = static function ( string $id, string $section, string $label, bool $initial, string $description = '', string $transport = 'refresh' ) use ( $wp_customize ) {
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => $initial,
					'sanitize_callback' => 'basix_sanitize_checkbox',
					'transport'         => $transport,
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'section'     => $section,
					'label'       => $label,
					'description' => $description,
					'type'        => 'checkbox',
				)
			);
		};
		$text     = static function ( string $id, string $section, string $label, string $description, string $type, string $sanitize ) use ( $wp_customize ) {
			$wp_customize->add_setting(
				$id,
				array(
					'default'           => '',
					'sanitize_callback' => $sanitize,
					'transport'         => 'postMessage',
				)
			);
			$wp_customize->add_control(
				$id,
				array(
					'section'     => $section,
					'label'       => $label,
					'description' => $description,
					'type'        => $type,
				)
			);
		};

		// Header.
		$checkbox( 'basix_header_sticky', 'basix_header', __( 'Keep the header at the top while scrolling', 'basix' ), true, '', 'postMessage' );
		$wp_customize->add_setting(
			'basix_header_style',
			array(
				'default'           => 'default',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_header_style',
			array(
				'section'     => 'basix_header',
				'label'       => __( 'Header style', 'basix' ),
				'description' => __( 'A transparent header sits over the first section of the page, with light text until you scroll. Use it with a dark picture at the top, such as the Landing page template with a cover. A single page can choose its own.', 'basix' ),
				'type'        => 'radio',
				'choices'     => array(
					'default'     => __( 'Solid', 'basix' ),
					'transparent' => __( 'Transparent over the first section', 'basix' ),
				),
			)
		);
		$text( 'basix_header_cta_label', 'basix_header', __( 'Button text', 'basix' ), __( 'A call-to-action button in the header. It needs a text and an address.', 'basix' ), 'text', 'sanitize_text_field' );
		$text( 'basix_header_cta_url', 'basix_header', __( 'Button address', 'basix' ), '', 'url', 'esc_url_raw' );

		// Top bar.
		$wp_customize->add_section(
			'basix_topbar',
			array(
				'title'       => __( 'Top bar', 'basix' ),
				'description' => __( 'A thin line above the header for a short message, your social links and a language switcher.', 'basix' ),
				'panel'       => 'basix',
			)
		);
		$checkbox( 'basix_topbar_enable', 'basix_topbar', __( 'Show the top bar', 'basix' ), false );
		$text( 'basix_topbar_text', 'basix_topbar', __( 'Message', 'basix' ), '', 'text', 'sanitize_text_field' );
		$checkbox( 'basix_topbar_social', 'basix_topbar', __( 'Show the social links menu', 'basix' ), true, __( 'The menu in the location "Social links".', 'basix' ) );
		$checkbox( 'basix_topbar_language', 'basix_topbar', __( 'Show the language switcher', 'basix' ), true, __( 'Shown when Polylang or WPML is active.', 'basix' ) );

		// Footer.
		$wp_customize->add_setting(
			'basix_footer_layout',
			array(
				'default'           => 'columns',
				'sanitize_callback' => 'basix_sanitize_choice',
			)
		);
		$wp_customize->add_control(
			'basix_footer_layout',
			array(
				'section' => 'basix_footer',
				'label'   => __( 'Footer layout', 'basix' ),
				'type'    => 'radio',
				'choices' => array(
					'columns'  => __( 'Widget columns, then the footer line', 'basix' ),
					'centered' => __( 'Centred', 'basix' ),
					'minimal'  => __( 'Minimal: only the footer line and menus', 'basix' ),
				),
			)
		);
		$wp_customize->get_setting( 'basix_footer_text' )->transport = 'postMessage';
	},
	11
);

// The preview script: settings that can change without reloading the preview.
add_action(
	'customize_preview_init',
	function () {
		wp_enqueue_script( 'basix-customizer-preview', get_theme_file_uri( 'assets/js/customizer-preview.js' ), array( 'customize-preview' ), (string) filemtime( get_theme_file_path( 'assets/js/customizer-preview.js' ) ), true );
		wp_localize_script(
			'basix-customizer-preview',
			'basixPreview',
			array(
				'year' => wp_date( 'Y' ),
				'site' => wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			)
		);
	}
);

// The box on pages, posts and events.
add_action(
	'add_meta_boxes',
	function () {
		foreach ( array( 'page', 'post', BASIX_EVENT ) as $type ) {
			add_meta_box( 'basix_page_options', __( 'Page layout', 'basix' ), 'basix_page_options_box', $type, 'side', 'default' );
		}
	}
);

/**
 * Prints the box with the per-page options.
 *
 * @param WP_Post $post The page or post.
 */
function basix_page_options_box( WP_Post $post ): void {
	wp_nonce_field( 'basix_page_options', 'basix_page_options_nonce' );
	$value  = static function ( string $key ) use ( $post ): string {
		return (string) get_post_meta( $post->ID, $key, true );
	};
	$select = static function ( string $name, string $label, array $choices ) use ( $value ): void {
		printf( '<p><label for="%1$s"><strong>%2$s</strong></label><br><select id="%1$s" name="%1$s" class="widefat">', esc_attr( $name ), esc_html( $label ) );
		foreach ( $choices as $choice => $text ) {
			printf( '<option value="%s"%s>%s</option>', esc_attr( $choice ), selected( $value( $name ), $choice, false ), esc_html( $text ) );
		}
		echo '</select></p>';
	};
	$select(
		'_basix_header',
		__( 'Header', 'basix' ),
		array(
			''            => __( 'As in the Customizer', 'basix' ),
			'transparent' => __( 'Transparent over the first section', 'basix' ),
			'none'        => __( 'No header', 'basix' ),
		)
	);
	$select(
		'_basix_sidebar',
		__( 'Sidebar', 'basix' ),
		array(
			''      => __( 'Default (pages none, posts as in the Customizer)', 'basix' ),
			'right' => __( 'On the right', 'basix' ),
			'left'  => __( 'On the left', 'basix' ),
			'none'  => __( 'None', 'basix' ),
		)
	);
	$select(
		'_basix_footer',
		__( 'Footer', 'basix' ),
		array(
			''     => __( 'As in the Customizer', 'basix' ),
			'none' => __( 'No footer widgets and menus', 'basix' ),
		)
	);
	printf( '<p><label><input type="checkbox" name="_basix_hide_title" value="1"%s> %s</label><br><span class="description">%s</span></p>', checked( $value( '_basix_hide_title' ), '1', false ), esc_html__( 'Hide the title', 'basix' ), esc_html__( 'It stays in the page for screen readers.', 'basix' ) );
}

add_action(
	'save_post',
	function ( int $post_id ) {
		if ( ! isset( $_POST['basix_page_options_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['basix_page_options_nonce'] ) ), 'basix_page_options' ) ) {
			return;
		}
		if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}
		foreach ( BASIX_PAGE_OPTIONS as $key => $allowed ) {
			$value = isset( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
			$value = in_array( $value, $allowed, true ) ? $value : '';
			if ( '' === $value ) {
				delete_post_meta( $post_id, $key );
			} else {
				update_post_meta( $post_id, $key, $value );
			}
		}
	}
);

/**
 * Lets a page builder's theme builder (Elementor Pro's header and footer locations, or anything that hooks the
 * basix_builder_location filter) draw a part of the page instead of the theme.
 *
 * The first call for a location asks the builder, which prints the part when it has one and answers true; the answer
 * is remembered, so the header and footer templates and the options that depend on them agree. A later call with
 * $ask = false only reads that answer.
 *
 * @param string $location header or footer.
 * @param bool   $ask      Ask the builder when it has not been asked yet.
 * @return bool Whether a builder has drawn the part.
 */
function basix_builder_location( string $location, bool $ask = true ): bool {
	if ( ! isset( $GLOBALS['basix_builder'][ $location ] ) ) {
		if ( ! $ask ) {
			return false;
		}
		$handled = function_exists( 'elementor_theme_do_location' ) && (bool) elementor_theme_do_location( $location );
		/**
		 * Filters whether a page builder has drawn this part of the page.
		 *
		 * @param bool   $handled  Whether it has.
		 * @param string $location header or footer.
		 */
		$GLOBALS['basix_builder'][ $location ] = (bool) apply_filters( 'basix_builder_location', $handled, $location );
	}
	return $GLOBALS['basix_builder'][ $location ];
}
