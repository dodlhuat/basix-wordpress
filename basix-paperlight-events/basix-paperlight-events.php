<?php
/**
 * Plugin Name: Basix Paperlight Events
 * Description: Events with dates, times, time zones, repeats and categories, an events calendar block and shortcode, a calendar feed (ICS), and the data for event search results. Made for the Basix theme; the events work with any theme.
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
