<?php
/**
 * The setup screen (Appearance > Basix setup): pick a look, pick a starter site, make it yours.
 *
 * It works without JavaScript: one form, two posts (set up, undo), and plain redirects with a message.
 *
 * @package basix
 */

// A welcome notice after the theme is switched on, until the owner opens the setup screen or dismisses it.
add_action(
	'after_switch_theme',
	function () {
		update_option( 'basix_welcome', 1, false );
	}
);

add_action(
	'admin_menu',
	function () {
		add_theme_page( __( 'Basix setup', 'basix' ), __( 'Basix setup', 'basix' ), 'edit_theme_options', 'basix-setup', 'basix_setup_page' );
	}
);

add_action(
	'admin_notices',
	function () {
		if ( ! get_option( 'basix_welcome' ) || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && 'appearance_page_basix-setup' === $screen->id ) {
			return;
		}
		$dismiss = wp_nonce_url( admin_url( 'admin-post.php?action=basix_dismiss_welcome' ), 'basix_dismiss_welcome' );
		?>
		<div class="notice notice-info">
			<p><strong><?php esc_html_e( 'Thank you for choosing Basix.', 'basix' ); ?></strong> <?php esc_html_e( 'Pick a look and a starter site in a minute, or start from scratch.', 'basix' ); ?></p>
			<p>
				<a class="button button-primary" href="<?php echo esc_url( admin_url( 'themes.php?page=basix-setup' ) ); ?>"><?php esc_html_e( 'Set up your site', 'basix' ); ?></a>
				<a class="button" href="<?php echo esc_url( $dismiss ); ?>"><?php esc_html_e( 'Not now', 'basix' ); ?></a>
			</p>
		</div>
		<?php
	}
);

add_action(
	'admin_post_basix_dismiss_welcome',
	function () {
		check_admin_referer( 'basix_dismiss_welcome' );
		if ( current_user_can( 'edit_theme_options' ) ) {
			delete_option( 'basix_welcome' );
		}
		wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url() );
		exit;
	}
);

/**
 * Goes back to the setup screen with a message.
 *
 * @param string $done    What happened: import, look, undo or error.
 * @param string $starter Starter slug, for the message.
 */
function basix_setup_redirect( string $done, string $starter = '' ): void {
	wp_safe_redirect(
		add_query_arg(
			array(
				'page'        => 'basix-setup',
				'basix_done'  => $done,
				'basix_start' => $starter,
			),
			admin_url( 'themes.php' )
		)
	);
	exit;
}

add_action(
	'admin_post_basix_setup',
	function () {
		check_admin_referer( 'basix_setup' );
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'basix' ), '', array( 'response' => 403 ) );
		}
		$starter = isset( $_POST['starter'] ) ? sanitize_key( wp_unslash( $_POST['starter'] ) ) : '';
		$look    = isset( $_POST['look'] ) ? sanitize_key( wp_unslash( $_POST['look'] ) ) : '';
		if ( '' !== $look && ! isset( basix_looks()[ $look ] ) ) {
			$look = '';
		}
		if ( '' === $starter ) {
			if ( '' !== $look ) {
				set_theme_mod( 'basix_look', $look );
			}
			delete_option( 'basix_welcome' );
			basix_setup_redirect( 'look' );
		}
		$result = basix_starter_import( $starter, $look );
		delete_option( 'basix_welcome' );
		basix_setup_redirect( is_wp_error( $result ) ? 'error' : 'import', $starter );
	}
);

add_action(
	'admin_post_basix_setup_undo',
	function () {
		check_admin_referer( 'basix_setup_undo' );
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_die( esc_html__( 'You are not allowed to do this.', 'basix' ), '', array( 'response' => 403 ) );
		}
		$record = basix_starter_undo();
		basix_setup_redirect( 'undo', (string) ( $record['slug'] ?? '' ) );
	}
);

/**
 * A small swatch of a look: page, text and accent colour, light on the left and dark on the right.
 *
 * @param array $look A look.
 */
function basix_setup_swatch( array $look ): void {
	$shipped = array(
		'light' => array(
			'background' => '#f9f9fb',
			'text'       => '#1e1f24',
			'accent'     => '#3d63dd',
		),
		'dark'  => array(
			'background' => '#111113',
			'text'       => '#eeeef0',
			'accent'     => '#1971a6',
		),
	);
	echo '<span class="swatch" aria-hidden="true">';
	foreach ( array( 'light', 'dark' ) as $mode ) {
		$c = array_merge( $shipped[ $mode ], $look[ $mode ] );
		printf( '<span class="half" style="background:%1$s;color:%2$s"><b>Aa</b><i style="background:%3$s"></i></span>', esc_attr( $c['background'] ), esc_attr( $c['text'] ), esc_attr( $c['accent'] ) );
	}
	echo '</span>';
}

/** Prints the setup screen. */
function basix_setup_page(): void {
	if ( ! current_user_can( 'edit_theme_options' ) ) {
		return;
	}
	delete_option( 'basix_welcome' );
	$record  = basix_starter_record();
	$starter = $record ? ( basix_starters()[ $record['slug'] ] ?? null ) : null;
	// A message after setting up, read only to choose which text to show.
	$done      = isset( $_GET['basix_done'] ) ? sanitize_key( wp_unslash( $_GET['basix_done'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$done_slug = isset( $_GET['basix_start'] ) ? sanitize_key( wp_unslash( $_GET['basix_start'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$done_name = basix_starters()[ $done_slug ]['title'] ?? '';
	$pattern   = get_page_by_path( 'pattern-library' );
	?>
	<div class="wrap basix-setup">
		<style>
			.basix-setup .cards{display:grid;grid-template-columns:repeat(auto-fill,minmax(14rem,1fr));gap:1rem;margin:1rem 0 1.5rem;padding:0;list-style:none}
			.basix-setup .card-choice{display:block;position:relative;padding:1rem;background:#fff;border:2px solid #c3c4c7;border-radius:6px;cursor:pointer;height:100%;box-sizing:border-box}
			.basix-setup .card-choice:has(input:checked){border-color:#2271b1;box-shadow:0 0 0 1px #2271b1}
			.basix-setup .card-choice:focus-within{outline:2px solid #2271b1;outline-offset:2px}
			.basix-setup .card-choice input{position:absolute;inset-block-start:1rem;inset-inline-end:1rem}
			.basix-setup .card-choice strong{display:block;font-size:1rem;margin-bottom:.25rem}
			.basix-setup .card-choice p{margin:.25rem 0 0;color:#50575e}
			.basix-setup .swatch{display:flex;margin-bottom:.75rem;border:1px solid #c3c4c7;border-radius:4px;overflow:hidden}
			.basix-setup .swatch .half{flex:1;display:flex;align-items:center;justify-content:space-between;padding:.6rem .75rem}
			.basix-setup .swatch i{width:1.5rem;height:.75rem;border-radius:2px}
			.basix-setup .facts{margin:.5rem 0 0;padding:0;list-style:none;color:#50575e;font-size:12px}
			.basix-setup .undo{background:#fff;border:1px solid #c3c4c7;border-left:4px solid #dba617;padding:.5rem 1rem 1rem;margin:1rem 0}
			.basix-setup ul.links{list-style:disc;margin-inline-start:1.25rem}
		</style>
		<h1><?php esc_html_e( 'Set up Basix', 'basix' ); ?></h1>
		<p><?php esc_html_e( 'Choose how your site looks, and start from a ready-made site if you like. Nothing you have made is deleted: an import only adds, and Undo removes exactly what it added.', 'basix' ); ?></p>

		<?php if ( 'import' === $done && $done_name ) : ?>
			<div class="notice notice-success"><p>
				<?php
				/* translators: %s: name of the starter site. */
				echo esc_html( sprintf( __( 'The %s starter site is ready.', 'basix' ), $done_name ) );
				?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View your site', 'basix' ); ?></a>
			</p></div>
		<?php elseif ( 'look' === $done ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'The look is set.', 'basix' ); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'View your site', 'basix' ); ?></a></p></div>
		<?php elseif ( 'undo' === $done ) : ?>
			<div class="notice notice-success"><p><?php esc_html_e( 'The import was undone. Your settings are back as they were.', 'basix' ); ?></p></div>
		<?php elseif ( 'error' === $done ) : ?>
			<div class="notice notice-error"><p><?php esc_html_e( 'That did not work. Nothing was changed that you cannot undo here.', 'basix' ); ?></p></div>
		<?php endif; ?>

		<?php if ( $record && $starter ) : ?>
			<?php
			$made = (array) $record['created']['posts'];
			?>
			<div class="undo">
				<h2><?php esc_html_e( 'Your current starter site', 'basix' ); ?></h2>
				<p>
					<?php
					/* translators: 1: starter site name, 2: number of pages, posts, events and pictures added. */
					echo esc_html( sprintf( __( '%1$s was added on %2$s with %3$d items (pages, posts, events and pictures).', 'basix' ), $starter['title'], wp_date( get_option( 'date_format' ), (int) $record['time'] ), count( $made ) ) );
					?>
				</p>
				<p><?php esc_html_e( 'Undo removes what it added and puts your settings back. Changes you made to the added pages and posts are lost with them.', 'basix' ); ?></p>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="basix_setup_undo">
					<?php wp_nonce_field( 'basix_setup_undo' ); ?>
					<button class="button" type="submit"><?php esc_html_e( 'Undo this import', 'basix' ); ?></button>
				</form>
			</div>
		<?php endif; ?>

		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<input type="hidden" name="action" value="basix_setup">
			<?php wp_nonce_field( 'basix_setup' ); ?>

			<h2><?php esc_html_e( '1. Pick a look', 'basix' ); ?></h2>
			<ul class="cards">
				<li><label class="card-choice">
					<input type="radio" name="look" value="" checked>
					<strong><?php esc_html_e( "The starter site's own look", 'basix' ); ?></strong>
					<p><?php esc_html_e( 'Each starter site suggests one. Without a starter, your current look stays.', 'basix' ); ?></p>
				</label></li>
				<?php foreach ( basix_looks() as $slug => $look ) : ?>
					<li><label class="card-choice">
						<input type="radio" name="look" value="<?php echo esc_attr( $slug ); ?>">
						<?php basix_setup_swatch( $look ); ?>
						<strong><?php echo esc_html( $look['title'] ); ?></strong>
						<p><?php echo esc_html( $look['description'] ); ?></p>
					</label></li>
				<?php endforeach; ?>
			</ul>

			<h2><?php esc_html_e( '2. Pick a starter site', 'basix' ); ?></h2>
			<ul class="cards">
				<li><label class="card-choice">
					<input type="radio" name="starter" value="" checked>
					<strong><?php esc_html_e( 'No content, only the look', 'basix' ); ?></strong>
					<p><?php esc_html_e( 'Keep your site as it is and only change how it looks.', 'basix' ); ?></p>
				</label></li>
				<?php foreach ( basix_starters() as $slug => $def ) : ?>
					<?php $counts = basix_starter_counts( $def ); ?>
					<li><label class="card-choice">
						<input type="radio" name="starter" value="<?php echo esc_attr( $slug ); ?>">
						<strong><?php echo esc_html( $def['title'] ); ?></strong>
						<p><?php echo esc_html( $def['description'] ); ?></p>
						<ul class="facts">
							<li>
								<?php
								/* translators: 1: number of pages, 2: number of sample posts, 3: number of sample events. */
								echo esc_html( sprintf( __( '%1$d pages, %2$d posts, %3$d events', 'basix' ), $counts['pages'], $counts['posts'], $counts['events'] ) );
								?>
							</li>
							<li><?php echo esc_html( sprintf( /* translators: %s: look name. */ __( 'Look: %s', 'basix' ), basix_looks()[ $def['look'] ]['title'] ?? $def['look'] ) ); ?></li>
						</ul>
					</label></li>
				<?php endforeach; ?>
			</ul>
			<p><button class="button button-primary button-hero" type="submit"><?php esc_html_e( 'Set up my site', 'basix' ); ?></button></p>
		</form>

		<h2><?php esc_html_e( '3. Make it yours', 'basix' ); ?></h2>
		<ul class="links">
			<li><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[section]=title_tagline' ) ); ?>"><?php esc_html_e( 'Site name, tagline and logo', 'basix' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'nav-menus.php' ) ); ?>"><?php esc_html_e( 'Menus', 'basix' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'customize.php?autofocus[panel]=basix' ) ); ?>"><?php esc_html_e( 'Colours, fonts, header, layout and more (Basix options)', 'basix' ); ?></a></li>
			<li><a href="<?php echo esc_url( admin_url( 'widgets.php' ) ); ?>"><?php esc_html_e( 'Sidebar and footer widgets', 'basix' ); ?></a></li>
		</ul>

		<h2><?php esc_html_e( '4. Learn more', 'basix' ); ?></h2>
		<ul class="links">
			<li><?php esc_html_e( 'In the editor, open the block inserter, choose Patterns and look for the groups that start with Basix.', 'basix' ); ?></li>
			<?php if ( $pattern ) : ?>
				<li><a href="<?php echo esc_url( get_permalink( $pattern ) ); ?>"><?php esc_html_e( 'See every pattern on one page', 'basix' ); ?></a></li>
			<?php endif; ?>
			<?php if ( wp_get_theme()->get( 'ThemeURI' ) ) : ?>
				<li><a href="<?php echo esc_url( wp_get_theme()->get( 'ThemeURI' ) ); ?>"><?php esc_html_e( 'Documentation and support', 'basix' ); ?></a></li>
			<?php endif; ?>
		</ul>
	</div>
	<?php
}
