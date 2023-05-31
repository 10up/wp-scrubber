<?php
/**
 * Register the Wpvip class.
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber;

/**
 * Register the Wpvip class.
 */
class Wpvip extends \TenUpWPScrubber\Module {

	/**
	 * Check if the class can be registered.
	 *
	 * @return boolean
	 */
	public function can_register() {

		$cli_defined = false;
		$vip_env     = false;

		// Check if CLI exists.
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			$cli_defined = true;
		}

		// Check if on VIP.
		if ( defined( 'VIP_GO_APP_ENVIRONMENT' ) ) {
			$vip_env = true;
		}

		return $vip_env && $cli_defined;
	}

	/**
	 * Register our hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'vip_datasync_cleanup', [ $this, 'scrub_data' ] );
	}

	/**
	 * Register the command library.
	 *
	 * @return void
	 */
	public function scrub_data() {
		\WP_CLI::runCommand( 'wp scrub all' );
	}
}
