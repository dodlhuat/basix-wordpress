<?php
/**
 * Renders a menu in the markup the Basix FlyoutMenu turns into a drawer with expandable sections.
 *
 * FlyoutMenu reads a parent's label from a text node, so items with children are not links; their own
 * page becomes the first entry of the section (unless it is a "#" placeholder).
 *
 * @package basix
 */

/**
 * Walker for the mobile / flyout drawer.
 */
class Basix_Drawer_Walker extends Walker_Nav_Menu {

	/**
	 * Parents whose submenu is open, so start_lvl() can add the parent's own page first.
	 *
	 * @var array
	 */
	private $parents = array();

	/**
	 * Opens a submenu.
	 *
	 * @param string   $output Output so far.
	 * @param int      $depth  Depth of the list being opened.
	 * @param stdClass $args   Menu arguments.
	 */
	public function start_lvl( &$output, $depth = 0, $args = null ) {
		$parent  = end( $this->parents );
		$output .= '<ul>';
		if ( $parent && ! empty( $parent->url ) && '#' !== $parent->url ) {
			$output .= sprintf( '<li><a href="%s">%s</a></li>', esc_url( $parent->url ), esc_html( $this->title( $parent ) ) );
		}
	}

	/**
	 * Closes a submenu.
	 *
	 * @param string   $output Output so far.
	 * @param int      $depth  Depth of the list being closed.
	 * @param stdClass $args   Menu arguments.
	 */
	public function end_lvl( &$output, $depth = 0, $args = null ) {
		$output .= '</ul>';
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

		if ( $this->has_children ) {
			$this->parents[] = $item;
			$output         .= '<li>' . $title;
			return;
		}

		$current = in_array( 'current-menu-item', (array) $item->classes, true ) ? ' aria-current="page"' : '';
		$output .= sprintf( '<li><a href="%s"%s>%s</a>', esc_url( $item->url ), $current, $title );
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
}
