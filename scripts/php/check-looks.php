<?php
/**
 * Checks the looks: every colour pair is readable in light and dark mode, the fonts exist, and the CSS that is
 * written follows the settings. Run with `wp eval-file /php/check-looks.php` (make check does). It changes theme
 * settings for a moment and puts them back.
 */

$failed = 0;
$check  = static function ( bool $ok, string $what ) use ( &$failed ) {
	echo ( $ok ? 'ok   ' : 'FAIL ' ), $what, "\n";
	if ( ! $ok ) {
		++$failed;
	}
};

$saved = array();
foreach ( array( 'basix_look', 'basix_accent_light', 'basix_accent_dark', 'basix_font_heading', 'basix_font_body', 'basix_text_size' ) as $name ) {
	$saved[ $name ] = get_theme_mod( $name, null );
}

$shipped = array(
	'light' => array(
		'background'    => '#f9f9fb',
		'surface'       => '#e7e8ec',
		'text'          => '#1e1f24',
		'textSecondary' => '#62636c',
	),
	'dark'  => array(
		'background'    => '#111113',
		'surface'       => '#19191b',
		'text'          => '#eeeef0',
		'textSecondary' => '#b2b3bd',
	),
);
$looks   = basix_looks();
$check( count( $looks ) >= 5 && isset( $looks['basix'] ) && 'basix' === array_key_first( $looks ), 'there are at least five looks and Basix comes first' );

foreach ( $looks as $slug => $look ) {
	remove_theme_mod( 'basix_accent_light' );
	remove_theme_mod( 'basix_accent_dark' );
	set_theme_mod( 'basix_look', $slug );
	foreach ( array( 'light', 'dark' ) as $mode ) {
		$c      = array_merge( $shipped[ $mode ], $look[ $mode ] );
		$accent = basix_accent( $mode );
		$text   = basix_accent_text( $accent, 'dark' === $mode, $c['background'], $c['surface'] );
		$pairs  = array(
			'text on the page'             => basix_contrast( $c['text'], $c['background'] ),
			'text on a surface'            => basix_contrast( $c['text'], $c['surface'] ),
			'secondary text on the page'   => basix_contrast( $c['textSecondary'], $c['background'] ),
			'secondary text on a surface'  => basix_contrast( $c['textSecondary'], $c['surface'] ),
			'button text on the accent'    => basix_contrast( basix_readable_on( $accent ), $accent ),
			'accent text on the page'      => basix_contrast( $text, $c['background'] ),
			'accent text on a surface'     => basix_contrast( $text, $c['surface'] ),
			'accent text on a tinted card' => basix_contrast( $text, basix_mix( $accent, $c['surface'], 0.2 ) ),
		);
		$worst  = min( $pairs );
		$what   = array_search( $worst, $pairs, true );
		$check( $worst >= 4.5, sprintf( '%s, %s: every pair is readable (lowest %.1f, %s)', $look['title'], $mode, $worst, $what ) );
	}
}

// The fonts: every look uses fonts that exist, and the files are there.
$fonts = basix_fonts();
foreach ( $looks as $look ) {
	foreach ( $look['fonts'] as $role => $slug ) {
		$check( isset( $fonts[ $slug ] ), sprintf( '%s uses a known %s font', $look['title'], $role ) );
	}
}
$missing = array();
foreach ( $fonts as $slug => $font ) {
	if ( '' !== $font['file'] && ! file_exists( get_theme_file_path( $font['file'] ) ) ) {
		$missing[] = $slug;
	}
}
$check( ! $missing, 'every font file is in the theme' . ( $missing ? ' (missing: ' . implode( ', ', $missing ) . ')' : '' ) );

// What is written follows the settings.
remove_theme_mod( 'basix_font_heading' );
remove_theme_mod( 'basix_font_body' );
remove_theme_mod( 'basix_text_size' );
set_theme_mod( 'basix_look', 'basix' );
$default = basix_design_css();
$check( false === strpos( $default, '--background' ) && false === strpos( $default, 'font-family' ) && false === strpos( $default, '--radius' ), 'the Basix look changes nothing but its dark accent' );
set_theme_mod( 'basix_look', 'editorial' );
$css = basix_design_css();
$check( false !== strpos( $css, '--background:#fbf8f3' ) && false !== strpos( $css, '--background:#17140f' ), 'a look writes its colours for both modes' );
$check( false !== strpos( $css, 'font-family:"Fraunces"' ) && false !== strpos( $css, 'body{font-family:"Source Serif 4"' ) && false !== strpos( $css, '@font-face{font-family:"Fraunces"' ), 'and its fonts, with the files' );
$check( false !== strpos( $css, '--border-radius:0.1rem' ), 'and its corner scale' );
$check( false === strpos( $css, 'Outfit' ), 'and nothing of the old font' );
set_theme_mod( 'basix_font_body', 'system' );
set_theme_mod( 'basix_font_heading', 'look' );
$css = basix_design_css();
$check( false !== strpos( $css, 'body{font-family:system-ui' ) && false === strpos( $css, '@font-face{font-family:"Source Serif 4"' ), 'a chosen font beats the look, and one that is not used is not loaded' );
set_theme_mod( 'basix_accent_light', '#aa2200' );
$check( false !== strpos( basix_design_css(), '--accent-color:#aa2200' ), 'a chosen accent colour beats the look\'s' );
set_theme_mod( 'basix_text_size', 'large' );
$check( false !== strpos( basix_design_css(), 'html{font-size:112.5%}' ), 'the text size is written' );
set_theme_mod( 'basix_look', 'nonsense' );
$check( 'basix' === basix_look_slug(), 'an unknown look falls back to Basix' );

foreach ( $saved as $name => $value ) {
	if ( null === $value ) {
		remove_theme_mod( $name );
	} else {
		set_theme_mod( $name, $value );
	}
}

echo $failed ? "\nLooks check FAILED ($failed)\n" : "\nLooks check passed.\n";
exit( $failed ? 1 : 0 );
