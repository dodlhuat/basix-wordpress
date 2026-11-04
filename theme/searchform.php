<form role="search" method="get" class="search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" aria-label="<?php echo esc_attr( ! empty( $args['aria_label'] ) ? $args['aria_label'] : __( 'Search', 'basix-paperlight' ) ); ?>">
	<div class="input-group">
		<input type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Search…', 'basix-paperlight' ); ?>" aria-label="<?php esc_attr_e( 'Search', 'basix-paperlight' ); ?>">
		<button type="submit" class="button button-primary"><?php esc_html_e( 'Search', 'basix-paperlight' ); ?></button>
	</div>
</form>
