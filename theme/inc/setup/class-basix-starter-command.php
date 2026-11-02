<?php
/**
 * WP-CLI: wp basix starter list | import <slug> [--look=<look>] | undo | status
 *
 * @package basix
 */

if ( ! defined( 'WP_CLI' ) || ! WP_CLI ) {
	return;
}

/**
 * Starter sites of the Basix theme.
 */
class Basix_Starter_Command {

	/**
	 * Lists the starter sites.
	 *
	 * @subcommand list
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function list_( $args, $assoc_args ) {
		$rows = array();
		foreach ( basix_starters() as $slug => $def ) {
			$rows[] = array_merge(
				array(
					'slug' => $slug,
					'look' => $def['look'],
				),
				basix_starter_counts( $def )
			);
		}
		WP_CLI\Utils\format_items( $assoc_args['format'] ?? 'table', $rows, array( 'slug', 'look', 'pages', 'posts', 'events' ) );
	}

	/**
	 * Imports a starter site (an earlier import is undone first).
	 *
	 * ## OPTIONS
	 *
	 * <slug>
	 * : Starter slug.
	 *
	 * [--look=<look>]
	 * : Use this look instead of the starter's own.
	 *
	 * @param array $args       Positional arguments.
	 * @param array $assoc_args Named arguments.
	 */
	public function import( $args, $assoc_args ) {
		$record = basix_starter_import( $args[0], (string) ( $assoc_args['look'] ?? '' ) );
		if ( is_wp_error( $record ) ) {
			WP_CLI::error( $record->get_error_message() );
		}
		WP_CLI::success( sprintf( 'Imported %s: %d items, look %s.', $record['slug'], count( $record['created']['posts'] ), $record['look'] ) );
	}

	/**
	 * Undoes the last import.
	 */
	public function undo() {
		$record = basix_starter_undo();
		if ( ! $record ) {
			WP_CLI::success( 'Nothing to undo.' );
			return;
		}
		WP_CLI::success( sprintf( 'Undid %s.', $record['slug'] ) );
	}

	/**
	 * Shows the last import as JSON (slug, look, addresses).
	 */
	public function status() {
		WP_CLI::print_value( basix_starter_record(), array( 'format' => 'json' ) );
	}
}

WP_CLI::add_command( 'basix starter', 'Basix_Starter_Command' );
