<?php
/**
 * The filter bar above a list of posts: category chips (links), a Filter button and the active filters.
 *
 * Without JavaScript the Filter button opens a plain form below it. With JavaScript the form opens in a
 * modal (a bottom sheet on phones) with the Basix group picker and date picker (see assets/js/filters.js).
 *
 * @package basix
 */

$basix_chips    = basix_chip_categories();
$basix_values   = basix_filter_values();
$basix_active   = basix_active_filters();
$basix_groups   = basix_category_groups();
$basix_base_url = basix_filter_base_url();
$basix_current  = is_category() ? get_queried_object_id() : 0;
$basix_sprite   = get_theme_file_uri( BASIX_VENDOR . '/svg-icons/icons.svg' );

// Other query arguments of the list (the search term) must survive an Apply.
$basix_keep = array();
wp_parse_str( (string) wp_parse_url( get_pagenum_link( 1, false ), PHP_URL_QUERY ), $basix_keep );
$basix_keep = array_diff_key( $basix_keep, array_flip( BASIX_FILTER_VARS ) );
?>
<div class="filter-bar">
	<?php if ( count( $basix_chips ) >= 2 ) : ?>
		<nav class="chips filter-chips" aria-label="<?php esc_attr_e( 'Categories', 'basix-paperlight' ); ?>">
			<a class="chip clickable<?php echo is_home() && ! is_paged() && ! $basix_active ? ' selected' : ''; ?>" href="<?php echo esc_url( basix_blog_url() ); ?>"><?php esc_html_e( 'All', 'basix-paperlight' ); ?></a>
			<?php foreach ( $basix_chips as $basix_chip ) : ?>
				<a class="chip clickable<?php echo $basix_current === $basix_chip->term_id ? ' selected' : ''; ?>" href="<?php echo esc_url( get_category_link( $basix_chip ) ); ?>"><?php echo esc_html( $basix_chip->name ); ?></a>
			<?php endforeach; ?>
		</nav>
	<?php endif; ?>

	<details class="filter" <?php echo basix_component_attrs( 'filters', basix_filter_config() ); ?>>
		<summary class="button filter-toggle">
			<svg class="icon-svg" aria-hidden="true"><use href="<?php echo esc_url( $basix_sprite . '#filter_list' ); ?>"/></svg>
			<span><?php esc_html_e( 'Filter', 'basix-paperlight' ); ?></span>
			<?php if ( $basix_active ) : ?>
				<span class="badge badge-solid badge-info"><?php echo esc_html( number_format_i18n( count( $basix_active ) ) ); ?></span>
			<?php endif; ?>
		</summary>

		<form class="filter-form" method="get" action="<?php echo esc_url( $basix_base_url ); ?>">
			<?php foreach ( $basix_keep as $basix_name => $basix_value ) : ?>
				<?php if ( is_scalar( $basix_value ) ) : ?>
					<input type="hidden" name="<?php echo esc_attr( $basix_name ); ?>" value="<?php echo esc_attr( $basix_value ); ?>">
				<?php endif; ?>
			<?php endforeach; ?>

			<fieldset class="filter-cats">
				<legend><?php esc_html_e( 'Categories', 'basix-paperlight' ); ?></legend>
				<?php foreach ( $basix_groups as $basix_group ) : ?>
					<div class="filter-cat-group">
						<div>
							<input class="styled-checkbox" type="checkbox" name="categories[]" id="filter-cat-<?php echo esc_attr( $basix_group['id'] ); ?>" value="<?php echo esc_attr( $basix_group['id'] ); ?>" <?php checked( in_array( (int) $basix_group['id'], $basix_values['categories'], true ) ); ?>>
							<label for="filter-cat-<?php echo esc_attr( $basix_group['id'] ); ?>"><?php echo esc_html( $basix_group['label'] ); ?></label>
						</div>
						<?php foreach ( $basix_group['subgroups'] as $basix_sub ) : ?>
							<div class="filter-cat-sub">
								<input class="styled-checkbox" type="checkbox" name="categories[]" id="filter-cat-<?php echo esc_attr( $basix_sub['id'] ); ?>" value="<?php echo esc_attr( $basix_sub['id'] ); ?>" <?php checked( in_array( (int) $basix_sub['id'], $basix_values['categories'], true ) ); ?>>
								<label for="filter-cat-<?php echo esc_attr( $basix_sub['id'] ); ?>"><?php echo esc_html( $basix_sub['label'] ); ?></label>
							</div>
						<?php endforeach; ?>
					</div>
				<?php endforeach; ?>
			</fieldset>

			<fieldset class="filter-dates">
				<legend><?php esc_html_e( 'Published between', 'basix-paperlight' ); ?></legend>
				<label><span class="text-caption"><?php esc_html_e( 'From', 'basix-paperlight' ); ?></span>
					<input type="date" name="date_from" value="<?php echo esc_attr( $basix_values['date_from'] ); ?>"></label>
				<label><span class="text-caption"><?php esc_html_e( 'To', 'basix-paperlight' ); ?></span>
					<input type="date" name="date_to" value="<?php echo esc_attr( $basix_values['date_to'] ); ?>"></label>
			</fieldset>

			<div class="filter-actions">
				<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply', 'basix-paperlight' ); ?></button>
				<a class="button" href="<?php echo esc_url( $basix_base_url ); ?>"><?php esc_html_e( 'Clear', 'basix-paperlight' ); ?></a>
			</div>
		</form>
	</details>
</div>

<?php if ( $basix_active ) : ?>
	<div class="chips filter-active" aria-label="<?php esc_attr_e( 'Active filters', 'basix-paperlight' ); ?>">
		<?php foreach ( $basix_active as $basix_filter ) : ?>
			<a class="chip clickable selected" href="<?php echo esc_url( $basix_filter['url'] ); ?>" aria-label="
				<?php
				/* translators: %s: filter, for example a category name. */
				echo esc_attr( sprintf( __( 'Remove filter: %s', 'basix-paperlight' ), $basix_filter['label'] ) );
				?>
				"><?php echo esc_html( $basix_filter['label'] ); ?> <span aria-hidden="true">&times;</span></a>
		<?php endforeach; ?>
		<a class="chip clickable" href="<?php echo esc_url( $basix_base_url ); ?>"><?php esc_html_e( 'Clear all', 'basix-paperlight' ); ?></a>
	</div>
<?php endif; ?>
