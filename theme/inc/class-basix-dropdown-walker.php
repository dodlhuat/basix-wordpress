<?php
/**
 * Renders the primary menu as Basix dropdowns for the desktop header.
 *
 * Top-level items with children become a .dropdown-container (button + .dropdown-menu); deeper levels
 * become nested lists. An item that has children is no longer a link itself, so its own page is added as
 * the first entry of its submenu (unless it is a "#" placeholder).
 *
 * @package basix
 */

/**
 * Walker for the inline desktop navigation.
 */
class Basix_Dropdown_Walker extends Walker_Nav_Menu {

	/**
	 * Parents whose submenu is open, so start_lvl() can add the parent's own page as first entry.
	 *
	 * @var array
	 */
	private $parents = array();

	/**
	 * Opens a submenu: the dropdown panel at the top level, a nested list below that.
	 *
	 * @param string   $output Output so far.
	 * @param int      $depth  Depth of the list being opened.
	 * @param stdClass $args   Menu arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$parent = end( $this->parents );
		if ( 0 === $depth ) {
			$output .= '<ul class="dropdown-menu">';
		} else {
			$output .= '<ul>';
		}
		if ( $parent && $this->is_real_link( $parent ) ) {
			$output .= sprintf(
				'<li><a class="dropdown-item" href="%s"%s>%s</a></li>',
				esc_url( $parent->url ),
				$this->current_attr( $parent ),
				esc_html( $this->title( $parent ) )
			);
		}
	}

	/**
	 * Closes a submenu (and the dropdown container at the top level).
	 *
	 * @param string   $output Output so far.
	 * @param int      $depth  Depth of the list being closed.
	 * @param stdClass $args   Menu arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
		if ( 0 === $depth ) {
			$output .= '</div>';
		}
	}

	/**
	 * Opens one menu item.
	 *
	 * @param string   $output            Output so far.
	 * @param WP_Post  $data_object       The menu item.
	 * @param int      $depth             Depth of the item.
	 * @param stdClass $args              Menu arguments.
	 * @param int      $current_object_id Current object id.
	 */
	public function start_el( &$output, $data_object, $depth = 0, $args = null, $current_object_id = 0 ) {
		$item  = $data_object;
		$title = esc_html( $this->title( $item ) );
		$attr  = $this->current_attr( $item );

		if ( $this->has_children ) {
			$this->parents[] = $item;
			if ( 0 === $depth ) {
				$id      = esc_attr( 'basix-dd-' . $item->ID );
				$output .= sprintf(
					'<li class="menu-item has-dropdown"><div class="dropdown-container" id="%1$s"><button type="button" class="dropdown-trigger"%2$s>%3$s</button>',
					$id,
					$this->is_current_branch( $item ) ? ' data-current="true"' : '',
					$title
				);
			} else {
				$output .= sprintf( '<li><span class="dropdown-item" tabindex="0">%s</span>', $title );
			}
			return;
		}

		if ( 0 === $depth ) {
			$output .= sprintf( '<li class="menu-item"><a class="site-nav-link" href="%s"%s>%s</a>', esc_url( $item->url ), $attr, $title );
		} else {
			$output .= sprintf( '<li><a class="dropdown-item" href="%s"%s>%s</a>', esc_url( $item->url ), $attr, $title );
		}
	}

	/**
	 * Closes one menu item.
	 *
	 * @param string   $output      Output so far.
	 * @param WP_Post  $data_object The menu item.
	 * @param int      $depth       Depth of the item.
	 * @param stdClass $args        Menu arguments.
	 */
	public function end_el( &$output, $data_object, $depth = 0, $args = null ) {
		if ( $this->has_children ) {
			array_pop( $this->parents );
		}
		$output .= '</li>';
	}

	/**
	 * The item's title, filtered like core does.
	 *
	 * @param WP_Post $item Menu item.
	 * @return string
	 */
	private function title( $item ) {
		return apply_filters( 'the_title', $item->title, $item->ID ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core filter.
	}

	/**
	 * Whether the item points somewhere (placeholder "#" or empty links are not links).
	 *
	 * @param WP_Post $item Menu item.
	 * @return bool
	 */
	private function is_real_link( $item ) {
		return ! empty( $item->url ) && '#' !== $item->url;
	}

	/**
	 * Whether the item is the current page, or an ancestor of it.
	 *
	 * @param WP_Post $item Menu item.
	 * @return bool
	 */
	private function is_current_branch( $item ) {
		$classes = (array) $item->classes;
		return in_array( 'current-menu-item', $classes, true ) || in_array( 'current-menu-ancestor', $classes, true ) || in_array( 'current-menu-parent', $classes, true );
	}

	/**
	 * The aria-current attribute for the current page.
	 *
	 * @param WP_Post $item Menu item.
	 * @return string
	 */
	private function current_attr( $item ) {
		return in_array( 'current-menu-item', (array) $item->classes, true ) ? ' aria-current="page"' : '';
	}
}
