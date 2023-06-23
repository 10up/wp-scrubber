<?php
/**
 * Main WP CLI command integration
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber;

/**
 * Register migration commands.
 */
class Command extends \WP_CLI_Command {

	/**
	 * Run all scrubbing functions.
	 *
	 * @param array $args       Positional arguments passed to the command.
	 * @param array $assoc_args Associative arguments passed to the command.
	 * @return void
	 */
	public function all( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		$defaults = apply_filters(
			'wp_scrubber_scrub_all_defaults',
			array(
				'allowed-domains'   => '',
				'allowed-emails'    => '',
				'ignore-size-limit' => '',
			)
		);

		$assoc_args = wp_parse_args( $assoc_args, $defaults );

		$allowed_domains = [
			'get10up.com',
			'10up.com',
		];

		$allowed_emails = [];

		// Add additional email domains which should not be scrubbed.
		if ( ! empty( $assoc_args['allowed-domains'] ) ) {
			$allowed_domains = array_merge( $allowed_domains, explode( ',', $assoc_args['allowed-domains'] ) );
		}

		// Add user emails which should not be scrubbed.
		if ( ! empty( $assoc_args['allowed-emails'] ) ) {
			$allowed_emails = array_merge( $allowed_emails, explode( ',', $assoc_args['allowed-emails'] ) );
		}

		do_action( 'wp_scrubber_before_scrub', $args, $assoc_args );

		// Check the environment. Do not allow
		if ( 'production' === wp_get_environment_type() && ! $this->allow_on_production() ) {
			\WP_CLI::error( 'This command cannot be run on a production environment.' );
		}

		// Limit the plugin on sites with large database sizes.
		$size_limit = apply_filters( 'wp_scrubber_db_size_limit', 2000 );
		if ( $size_limit < Helpers\get_database_size() && 'yes' !== $assoc_args['ignore-size-limit'] ) {
			\WP_CLI::error( "This database is larger than {$size_limit}MB. Ignore this warning with `--ignore-size-limit=yes`" );
		}

		// Run through the scrubbing process.
		Helpers\scrub_users( $allowed_domains, $allowed_emails, '\WP_CLI::log' );
		Helpers\scrub_comments( '\WP_CLI::log' );

		// Flush the cache.
		wp_cache_flush();

		do_action( 'wp_scrubber_after_scrub', $args, $assoc_args );
	}

	/**
	 * Check if we should allow scrubbing on production.
	 *
	 * @return boolean
	 */
	protected function allow_on_production() {
		return apply_filters( 'wp_scrubber_allow_on_production', false );
	}
}
