<?php
/**
 * Basix theme setup.
 */

const BASIX_VENDOR = 'assets/vendor/basix';

// A post without a title still needs something to link with: a link with no text has no name for a screen reader.
add_filter(
	'the_title',
	function ( $title, $post_id = 0 ) {
		if ( '' === trim( (string) $title ) && ! is_admin() && 'nav_menu_item' !== get_post_type( (int) $post_id ) ) {
			return __( '(no title)', 'basix' );
		}
		return $title;
	},
	10,
	2
);

// Events come from the companion plugin Basix Events (the directory does not let a theme register a post type). The
// two names are defined here too, so the templates also work while the plugin is off.
if ( ! defined( 'BASIX_EVENT' ) ) {
	define( 'BASIX_EVENT', 'basix-event' );
}
if ( ! defined( 'BASIX_EVENT_CAT' ) ) {
	define( 'BASIX_EVENT_CAT', 'basix-event-category' );
}

/** Whether the Basix Events plugin is active. */
function basix_has_events(): bool {
	return defined( 'BASIX_EVENTS_VERSION' );
}

require_once __DIR__ . '/inc/class-basix-dropdown-walker.php';
require_once __DIR__ . '/inc/class-basix-drawer-walker.php';
require_once __DIR__ . '/inc/menus.php';
require_once __DIR__ . '/inc/looks.php';
require_once __DIR__ . '/inc/customizer.php';
require_once __DIR__ . '/inc/header.php';
require_once __DIR__ . '/inc/docs.php';
require_once __DIR__ . '/inc/post-features.php';
require_once __DIR__ . '/inc/filters.php';
require_once __DIR__ . '/inc/calendar.php';
require_once __DIR__ . '/inc/gallery.php';
require_once __DIR__ . '/inc/seo.php';
require_once __DIR__ . '/inc/schema.php';
require_once __DIR__ . '/inc/starter-content.php';
require_once __DIR__ . '/inc/setup/content.php';
require_once __DIR__ . '/inc/setup/importer.php';
require_once __DIR__ . '/inc/setup/class-basix-starter-command.php';
require_once __DIR__ . '/inc/setup/admin.php';

add_action(
	'after_setup_theme',
	function () {
		load_theme_textdomain( 'basix', get_template_directory() . '/languages' );
		add_theme_support( 'title-tag' );
		add_theme_support( 'automatic-feed-links' );
		add_theme_support( 'post-thumbnails' );
		add_theme_support( 'align-wide' );
		add_theme_support( 'responsive-embeds' );
		add_theme_support( 'editor-styles' );
		// Basix first (colours, type, components), then the content styles, so the editor matches the site.
		add_editor_style( [ 'assets/vendor/basix/css/style.min.css', 'assets/css/content.css' ] );
		add_theme_support( 'html5', [ 'search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script' ] );
		// Reading column width in px (46rem), used by WordPress to size embeds and images.
		$GLOBALS['content_width'] = 736;
	}
);

// The editor is always light: it sits inside the light admin, and a visitor's dark mode is not the author's choice.
add_action(
	'enqueue_block_assets',
	function () {
		if ( ! is_admin() ) {
			return;
		}
		wp_register_script( 'basix-editor-light', false, [], wp_get_theme()->get( 'Version' ), false );
		wp_enqueue_script( 'basix-editor-light' );
		wp_add_inline_script( 'basix-editor-light', "document.documentElement.setAttribute('data-theme','light');" );
	}
);

// Apply a saved dark/light choice before first paint so the page never flashes the wrong theme.
add_action(
	'wp_head',
	function () {
		wp_print_inline_script_tag( "document.documentElement.classList.add('js');try{var d=document.documentElement,t=localStorage.getItem('theme')||d.dataset.defaultTheme;if(t)d.setAttribute('data-theme',t)}catch(e){}" );
	},
	1
);

// The Customizer's default colour mode, read by the init script above and by assets/js/main.js.
add_filter(
	'language_attributes',
	function ( string $output ): string {
		$mode = get_theme_mod( 'basix_color_mode', 'system' );
		return in_array( $mode, [ 'light', 'dark' ], true ) ? $output . ' data-default-theme="' . esc_attr( $mode ) . '"' : $output;
	}
);

add_action(
	'init',
	function () {
		register_block_pattern_category( 'basix', [ 'label' => __( 'Basix', 'basix' ) ] );
		foreach ( [
			'hero'           => __( 'Basix: Hero', 'basix' ),
			'features'       => __( 'Basix: Features', 'basix' ),
			'pricing'        => __( 'Basix: Pricing', 'basix' ),
			'testimonials'   => __( 'Basix: Testimonials', 'basix' ),
			'team'           => __( 'Basix: Team', 'basix' ),
			'logos'          => __( 'Basix: Logos', 'basix' ),
			'stats'          => __( 'Basix: Numbers', 'basix' ),
			'call-to-action' => __( 'Basix: Call to action', 'basix' ),
			'newsletter'     => __( 'Basix: Newsletter', 'basix' ),
			'contact'        => __( 'Basix: Contact', 'basix' ),
			'portfolio'      => __( 'Basix: Portfolio and pictures', 'basix' ),
			'blog'           => __( 'Basix: Blog', 'basix' ),
			'faq'            => __( 'Basix: Questions', 'basix' ),
			'about'          => __( 'Basix: About', 'basix' ),
		] as $basix_slug => $basix_label ) {
			register_block_pattern_category( 'basix-' . $basix_slug, [ 'label' => $basix_label ] );
		}

		register_block_style(
			'core/group',
			[
				'name'  => 'card',
				'label' => __( 'Card', 'basix' ),
			]
		);
		register_block_style(
			'core/group',
			[
				'name'  => 'card-bordered',
				'label' => __( 'Bordered card', 'basix' ),
			]
		);
		register_block_style(
			'core/paragraph',
			[
				'name'  => 'lead',
				'label' => __( 'Lead text', 'basix' ),
			]
		);
		register_block_style(
			'core/table',
			[
				'name'  => 'sortable',
				'label' => __( 'Sortable', 'basix' ),
			]
		);
		register_block_style(
			'core/gallery',
			[
				'name'  => 'masonry',
				'label' => __( 'Masonry', 'basix' ),
			]
		);
		register_block_style(
			'core/image',
			[
				'name'  => 'framed',
				'label' => __( 'Framed', 'basix' ),
			]
		);
		register_block_style(
			'core/image',
			[
				'name'  => 'shadow',
				'label' => __( 'Soft shadow', 'basix' ),
			]
		);
		register_block_style(
			'core/image',
			[
				'name'  => 'circle',
				'label' => __( 'Circle', 'basix' ),
			]
		);
	}
);

/** Core buttons use the Basix button classes, so hover, focus and active states come from Basix. */
add_filter(
	'render_block_core/button',
	function ( string $content ): string {
		$processor = new WP_HTML_Tag_Processor( $content );
		if ( ! $processor->next_tag() ) {
			return $content;
		}
		$outline = $processor->has_class( 'is-style-outline' );
		if ( $processor->next_tag( [ 'class_name' => 'wp-block-button__link' ] ) ) {
			$processor->add_class( 'button' );
			$processor->add_class( $outline ? 'button-outline' : 'button-primary' );
		}
		return $processor->get_updated_html();
	}
);

add_action(
	'widgets_init',
	function () {
		register_sidebar(
			[
				'name'          => __( 'Sidebar', 'basix' ),
				'id'            => 'sidebar-1',
				'description'   => __( 'Shown next to blog posts and archives.', 'basix' ),
				'before_widget' => '<div id="%1$s" class="widget card card-bordered %2$s">',
				'after_widget'  => '</div>',
				'before_title'  => '<h2 class="widget-title">',
				'after_title'   => '</h2>',
			]
		);

		// Up to three columns above the footer line; only columns with widgets are shown.
		foreach ( [ 1, 2, 3 ] as $column ) {
			register_sidebar(
				[
					'name'          => sprintf(
						/* translators: %d: column number. */
						__( 'Footer column %d', 'basix' ),
						$column
					),
					'id'            => 'footer-' . $column,
					'description'   => __( 'Shown in the footer.', 'basix' ),
					'before_widget' => '<div id="%1$s" class="widget footer-widget %2$s">',
					'after_widget'  => '</div>',
					'before_title'  => '<h2 class="widget-title">',
					'after_title'   => '</h2>',
				]
			);
		}
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		$dir = get_theme_file_path( BASIX_VENDOR );
		$uri = get_theme_file_uri( BASIX_VENDOR );

		if ( ! is_dir( $dir ) ) {
			return;
		}

		wp_enqueue_style( 'basix', "$uri/css/style.min.css", [], filemtime( "$dir/css/style.min.css" ) );
		wp_enqueue_style( 'basix-content', get_theme_file_uri( 'assets/css/content.css' ), [ 'basix' ], filemtime( get_theme_file_path( 'assets/css/content.css' ) ) );
		wp_enqueue_style( 'basix-theme', get_stylesheet_uri(), [ 'basix', 'basix-content' ], wp_get_theme()->get( 'Version' ) );
		wp_enqueue_style( 'basix-polish', get_theme_file_uri( 'assets/css/polish.css' ), [ 'basix-theme' ], filemtime( get_theme_file_path( 'assets/css/polish.css' ) ) );
		// Forms from Contact Form 7, WPForms, Fluent Forms and Gravity Forms, in the look of the theme.
		wp_enqueue_style( 'basix-forms', get_theme_file_uri( 'assets/css/forms.css' ), [ 'basix-theme' ], filemtime( get_theme_file_path( 'assets/css/forms.css' ) ) );
		// On a right-to-left site WordPress loads the mirrored "-rtl" file of each stylesheet instead (scripts/build-rtl.mjs).
		foreach ( [ 'basix', 'basix-content', 'basix-theme', 'basix-polish', 'basix-forms' ] as $basix_rtl_handle ) {
			wp_style_add_data( $basix_rtl_handle, 'rtl', 'replace' );
		}
	}
);

add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) {
			wp_enqueue_script( 'comment-reply' );
		}

		if ( ! is_dir( get_theme_file_path( BASIX_VENDOR ) ) ) {
			return;
		}
		wp_enqueue_script_module( 'basix-theme-main', get_theme_file_uri( 'assets/js/main.js' ), [], wp_get_theme()->get( 'Version' ) );
	}
);

// The archive type is shown as an eyebrow label instead of a "Category:" title prefix.
add_filter( 'get_the_archive_title_prefix', '__return_empty_string' );

function basix_archive_label(): string {
	return match ( true ) {
		is_category() => __( 'Category', 'basix' ),
		is_tag()      => __( 'Tag', 'basix' ),
		is_author()   => __( 'Author', 'basix' ),
		is_date()     => __( 'Archive', 'basix' ),
		is_post_type_archive( BASIX_EVENT ), is_tax( BASIX_EVENT_CAT ) => __( 'Events', 'basix' ),
		is_tax()      => get_taxonomy( get_queried_object()->taxonomy )->labels->singular_name ?? __( 'Archive', 'basix' ),
		default       => __( 'Archive', 'basix' ),
	};
}

// Six cards fill the front page grid evenly; archives use the Reading setting.
add_action(
	'pre_get_posts',
	function ( WP_Query $q ) {
		if ( ! is_admin() && $q->is_main_query() && $q->is_home() ) {
			$q->set( 'posts_per_page', 6 );
		}
	}
);

/**
 * Attributes that make assets/js/loader.js start a Basix component on an element.
 *
 * Usage: <div <?php echo basix_component_attrs('calendar', ['view' => 'month']); ?>>
 * The component must have an entry in assets/js/components.js.
 *
 * @param string $name   Component key in components.js.
 * @param array  $config Optional settings, passed to the component as JSON.
 * @return string Escaped attributes.
 */
function basix_component_attrs( string $name, array $config = [] ): string {
	$attrs = sprintf( 'data-basix="%s"', esc_attr( $name ) );
	if ( $config ) {
		$attrs .= sprintf( ' data-config="%s"', esc_attr( wp_json_encode( $config ) ) );
	}
	return $attrs;
}

/** Opening wrapper for templates with a sidebar; pair with basix_layout_close(). */
function basix_layout_open(): void {
	$classes = '';
	if ( basix_has_sidebar() ) {
		$classes = ' has-sidebar sidebar-' . ( 'left' === basix_sidebar_position() ? 'left' : 'right' );
	}
	printf( '<div class="layout%s"><div class="layout-main">', esc_attr( $classes ) );
}

function basix_layout_close(): void {
	echo '</div>';
	get_sidebar();
	echo '</div>';
}

add_filter( 'excerpt_length', fn() => 28 );
add_filter( 'excerpt_more', fn() => '…' );

/** Renders post meta (date, author, categories) using basix badges. */
function basix_post_meta(): void {
	echo '<p class="post-meta text-caption">';
	if ( BASIX_EVENT === get_post_type() ) {
		$range = basix_event_range( get_the_ID() );
		// A repeating event shows one date: the one asked for on its page, otherwise the next.
		$occurrence = basix_event_shown_occurrence( get_the_ID() );
		if ( $range ) {
			printf( '<time datetime="%s">%s</time>', esc_attr( $occurrence ? $occurrence['own_start'] : $range['start'] ), esc_html( basix_event_when( 0, false, $occurrence ) ) );
		}
		$place = (string) get_post_meta( get_the_ID(), 'basix_event_location', true );
		echo $place ? ' · ' . esc_html( $place ) : '';
		// An event in another time zone also says what time that is for the site.
		$site_time = is_singular( BASIX_EVENT ) && basix_event_zone( get_the_ID() )->getName() !== wp_timezone()->getName() ? basix_event_when( get_the_ID(), true, $occurrence ) : '';
		if ( $site_time ) {
			/* translators: %s: the event's time in the site's time zone. */
			echo ' · ' . esc_html( sprintf( __( '%s in site time', 'basix' ), $site_time ) );
		}
		echo '</p>';
		return;
	}
	printf( '<time datetime="%s">%s</time>', esc_attr( get_the_date( DATE_W3C ) ), esc_html( get_the_date() ) );
	if ( get_the_author() ) {
		echo ' · ' . esc_html( get_the_author() );
	}
	if ( 'post' === get_post_type() && basix_feature( 'basix_show_reading_time' ) ) {
		echo ' · ' . esc_html( basix_reading_time() );
	}
	echo '</p>';
}

function basix_post_terms(): void {
	if ( BASIX_EVENT === get_post_type() ) {
		basix_event_terms();
		return;
	}
	$cats = get_the_category();
	if ( ! $cats ) {
		return;
	}
	echo '<p class="post-terms">';
	foreach ( $cats as $cat ) {
		printf( '<a class="badge badge-info" href="%s">%s</a> ', esc_url( get_category_link( $cat ) ), esc_html( $cat->name ) );
	}
	echo '</p>';
}

/** Breadcrumb in basix markup. */
function basix_breadcrumb(): void {
	if ( is_front_page() || is_page_template( 'page-templates/landing.php' ) ) {
		return;
	}
	$crumbs = [ [ __( 'Home', 'basix' ), home_url( '/' ) ] ];
	if ( is_singular( 'post' ) ) {
		$cat = get_the_category()[0] ?? null;
		if ( $cat ) {
			$crumbs[] = [ $cat->name, get_category_link( $cat ) ];
		}
	} elseif ( is_page() ) {
		foreach ( array_reverse( get_post_ancestors( get_the_ID() ) ) as $id ) {
			$crumbs[] = [ get_the_title( $id ), get_permalink( $id ) ];
		}
	}
	if ( is_singular() ) {
		$current = get_the_title();
	} elseif ( is_404() ) {
		$current = __( 'Not found', 'basix' );
	} elseif ( is_search() ) {
		$current = __( 'Search results', 'basix' );
	} elseif ( is_category() || is_tag() || is_tax() ) {
		$current = single_term_title( '', false );
	} elseif ( is_home() && ! is_front_page() ) {
		$current = single_post_title( '', false );
	} else {
		$current = wp_strip_all_tags( get_the_archive_title() );
	}
	echo '<nav class="breadcrumb" aria-label="' . esc_attr__( 'Breadcrumb', 'basix' ) . '"><ol>';
	foreach ( $crumbs as [$label, $url] ) {
		printf( '<li><a href="%s">%s</a></li>', esc_url( $url ), esc_html( $label ) );
	}
	printf( '<li aria-current="page"><span>%s</span></li></ol></nav>', esc_html( $current ) );
}

/** Pagination as basix buttons. */
function basix_pagination(): void {
	$links = paginate_links(
		[
			'type'      => 'array',
			'prev_text' => '←',
			'next_text' => '→',
		]
	);
	if ( ! $links ) {
		return;
	}
	echo '<nav class="pagination" aria-label="' . esc_attr__( 'Pagination', 'basix' ) . '"><div class="button-group">';
	foreach ( $links as $link ) {
		$current = str_contains( $link, 'current' );
		$link    = preg_replace( '/class=[\'"]([^\'"]*)[\'"]/', 'class="button' . ( $current ? ' button-primary' : '' ) . '"', $link, 1 );
		echo $link; // phpcs:ignore WordPress.Security.EscapeOutput -- generated by WP core
	}
	echo '</div></nav>';
}

/** Basix validation states live on .form-group, so every comment field gets one. */
function basix_form_group( string $field ): string {
	return preg_replace( '/<p class="(comment-form-(?!cookies)[a-z-]+)"/', '<p class="$1 form-group"', $field );
}
add_filter( 'comment_form_field_comment', 'basix_form_group' );
add_filter( 'comment_form_default_fields', fn( array $fields ): array => array_map( 'basix_form_group', $fields ) );

/** Basix checkboxes are pure CSS and need the .styled-checkbox class on the input, right before its label. */
add_filter(
	'comment_form_default_fields',
	function ( array $fields ): array {
		if ( isset( $fields['cookies'] ) ) {
			$fields['cookies'] = str_replace( 'type="checkbox"', 'class="styled-checkbox" type="checkbox"', $fields['cookies'] );
		}
		return $fields;
	}
);

/** Renders a single comment as a basix card. */
function basix_comment( $comment, $args, $depth ): void {
	?>
	<li <?php comment_class( 'comment' ); ?> id="comment-<?php comment_ID(); ?>">
		<div class="card card-bordered">
			<div class="card-header comment-head">
				<?php echo get_avatar( $comment, 36, '', '', [ 'class' => 'comment-avatar' ] ); ?>
				<strong><?php comment_author(); ?></strong>
				<time class="text-caption" datetime="<?php comment_time( DATE_W3C ); ?>"><?php comment_date(); ?></time>
			</div>
			<?php if ( '0' === $comment->comment_approved ) : ?>
				<div class="alert alert-warning"><?php esc_html_e( 'Your comment is awaiting moderation.', 'basix' ); ?></div>
			<?php endif; ?>
			<?php comment_text(); ?>
			<div class="card-footer">
			<?php
			comment_reply_link(
				array_merge(
					$args,
					[
						'depth'     => $depth,
						'add_below' => 'comment',
					]
				)
			);
			?>
										</div>
		</div>
	<?php
}

/**
 * Load a basix JS component as an ES module, e.g. basix_module('modal').
 * Modules import each other relatively, so the vendor layout must stay intact.
 */
function basix_module( string $name ): void {
	wp_enqueue_script_module(
		"basix-$name",
		get_theme_file_uri( BASIX_VENDOR . "/js/$name.js" ),
		[],
		null
	);
}

/**
 * Render comment submission errors (empty fields, duplicates, flooding) inside the theme
 * with a basix alert, instead of WordPress's bare error screen.
 */
add_filter(
	'wp_die_handler',
	function ( string|callable $handler ) {
		if ( basename( $_SERVER['SCRIPT_NAME'] ?? '' ) !== 'wp-comments-post.php' ) {
			return $handler;
		}
		return function ( $message, $title = '', $args = [] ) {
			$message = is_wp_error( $message ) ? $message->get_error_message() : (string) $message;
			status_header( (int) ( $args['response'] ?? 200 ) );
			nocache_headers();
			get_header();
			?>
		<section class="entry comment-error">
			<h1><?php esc_html_e( 'Your comment could not be submitted', 'basix' ); ?></h1>
			<div class="alert alert-error"><?php echo wp_kses_post( $message ); ?></div>
			<p><button type="button" class="button button-primary" onclick="history.back()"><?php esc_html_e( 'Go back', 'basix' ); ?></button></p>
		</section>
			<?php
			get_footer();
			exit;
		};
	}
);

/**
 * Text the scripts need (form errors, names for things they build). It comes from here so it follows the
 * site language, not the browser's.
 */
add_action(
	'wp_footer',
	function () {
		printf(
			'<script type="application/json" id="basix-i18n">%s</script>',
			wp_json_encode(
				[
					'required'  => __( 'Please fill out this field.', 'basix' ),
					'email'     => __( 'Please enter a valid email address.', 'basix' ),
					'url'       => __( 'Please enter a valid URL.', 'basix' ),
					'enlarge'   => __( 'View larger image', 'basix' ),
					'openImage' => __( 'Open image', 'basix' ),
					'code'      => __( 'Code', 'basix' ),
					'table'     => __( 'Table', 'basix' ),
					'carousel'  => __( 'Featured posts', 'basix' ),
					/* translators: 1: number of this slide, 2: number of slides. */
					'slide'     => __( 'Slide %1$d of %2$d', 'basix' ),
				]
			)
		);
	}
);

/** Password-protected posts: basix form group, input group and error state. */
add_filter(
	'the_password_form',
	function ( string $output, WP_Post $post, string $invalid_password = '' ): string {
		$id = 'pwbox-' . $post->ID;
		ob_start();
		?>
	<form action="<?php echo esc_url( site_url( 'wp-login.php?action=postpass', 'login_post' ) ); ?>" class="post-password-form" method="post">
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( get_permalink( $post ) ); ?>">
		<div class="alert alert-info"><?php esc_html_e( 'This content is password-protected. To view it, please enter the password below.', 'basix' ); ?></div>
		<div class="form-group<?php echo $invalid_password ? ' error' : ''; ?>">
			<label for="<?php echo esc_attr( $id ); ?>"><?php esc_html_e( 'Password', 'basix' ); ?></label>
			<div class="input-group">
				<input name="post_password" id="<?php echo esc_attr( $id ); ?>" type="password" spellcheck="false" required<?php echo $invalid_password ? ' aria-invalid="true" aria-describedby="error-' . esc_attr( $id ) . '"' : ''; ?>>
				<button type="submit" class="button button-primary"><?php echo esc_html_x( 'Enter', 'post password form', 'basix' ); ?></button>
			</div>
			<?php if ( $invalid_password ) : ?>
				<div class="form-hint" id="error-<?php echo esc_attr( $id ); ?>" role="alert"><?php echo esc_html( $invalid_password ); ?></div>
			<?php endif; ?>
		</div>
	</form>
		<?php
		return ob_get_clean();
	},
	10,
	3
);
