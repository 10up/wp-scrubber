<?php

namespace TenUpWPScrubber;

class Command extends \TenUpWPScrubber\Module {

	public function can_register() {
		return defined( 'WP_CLI' ) && WP_CLI;
	}

	/**
	 * Register our hooks.
	 *
	 * @return void
	 */
	public function register() {
		add_action( 'init', [ $this, 'add_commands' ] );
	}

	/**
	 * Register the command library.
	 *
	 * @return void
	 */
	public function add_commands() {
		require_once __DIR__ . '/class-wp-cli-command.php';

		\WP_CLI::add_command( 'scrub', 'TenUpWPScrubber\WP_CLI_Command' );
	}
}