<?php
/**
 * Plugin Name: Basix Paperlight Events
 * Description: Events with dates, times, time zones, repeats and categories, an events calendar block and shortcode, a calendar feed (ICS), the data for event search results, structured data and social tags, a meta description, share links, and starter sites for the Basix Paperlight theme. The events work with any theme.
 * Version: 1.0.0
 * Requires at least: 6.5
 * Requires PHP: 8.1
 * Author: Andi Bauer
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: basix-paperlight-events
 * Domain Path: /languages
 *
 * @package basix-paperlight-events
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BASIX_EVENTS_VERSION = '1.0.0';
define( 'BASIX_EVENTS_DIR', plugin_dir_path( __FILE__ ) );

require_once BASIX_EVENTS_DIR . 'inc/events.php';
require_once BASIX_EVENTS_DIR . 'inc/recurrence.php';
require_once BASIX_EVENTS_DIR . 'inc/ics.php';
require_once BASIX_EVENTS_DIR . 'inc/calendar.php';
require_once BASIX_EVENTS_DIR . 'inc/seo.php';
require_once BASIX_EVENTS_DIR . 'inc/schema.php';
require_once BASIX_EVENTS_DIR . 'inc/share.php';

// The setup screen and the starter sites are built from the Basix Paperlight theme's looks, patterns and pictures,
// so they are only there while that theme (or a child of it) is active.
add_action(
	'after_setup_theme',
	function () {
		if ( ! function_exists( 'basix_looks' ) ) {
			return;
		}
		require_once BASIX_EVENTS_DIR . 'inc/setup/content.php';
		require_once BASIX_EVENTS_DIR . 'inc/setup/importer.php';
		require_once BASIX_EVENTS_DIR . 'inc/setup/class-basix-starter-command.php';
		require_once BASIX_EVENTS_DIR . 'inc/setup/admin.php';
	},
	20
);

add_action(
	'init',
	function () {
		load_plugin_textdomain( 'basix-paperlight-events', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	},
	1
);

// The addresses of events and of the calendar feed need flushing once, after the post type exists.
register_activation_hook(
	__FILE__,
	function () {
		update_option( 'basix_flush_rewrite', 1, false );
	}
);

register_deactivation_hook(
	__FILE__,
	function () {
		wp_clear_scheduled_hook( 'basix_events_refresh' );
		delete_option( 'basix_ics_rewrite' );
		flush_rewrite_rules();
	}
);
