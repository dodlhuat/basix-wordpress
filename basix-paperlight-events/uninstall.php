<?php
/**
 * Removes the plugin's settings when it is deleted. The events themselves stay: they are the site's content.
 *
 * @package basix-paperlight-events
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

foreach ( array( 'basix_events_derived', 'basix_ics_rewrite', 'basix_events_feed_version', 'basix_flush_rewrite' ) as $basix_events_option ) {
	delete_option( $basix_events_option );
}
wp_clear_scheduled_hook( 'basix_events_refresh' );
