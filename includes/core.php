<?php
/**
 * Core plugin functionality.
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber\Core;

/**
 * Default setup routine
 *
 * @return void
 */
function setup() {
	$n = function( $function ) {
		return __NAMESPACE__ . "\\$function";
	};

	if ( defined( 'WP_CLI' ) && WP_CLI ) {
		\WP_CLI::add_command( 'scrub', '\TenUpWPScrubber\Command' );

		if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
			add_action( 'vip_datasync_cleanup', $n( 'vip_scrub_data' ) );
		}
	}

	do_action( 'wp_scrubber_loaded' );
}

/**
 * Scrub data on WP VIP data sync
 */
function vip_scrub_data() {
	\WP_CLI::runCommand( apply_filters( 'wp_scrubber_vip_cleanup_command', 'scrub all' ) );
}
