<?php
/**
 * Plugin specific helpers.
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber;

/**
 * Get an initialized class by its full class name, including namespace.
 *
 * @param string $class_name The class name including the namespace.
 *
 * @return false|Module
 */
function get_module( $class_name ) {
	return \TenUpWPScrubber\ModuleInitialization::instance()->get_class( $class_name );
}

/**
 * Filter the args provided by the CLI script and convert them to WP_Query args.
 *
 * @param array $assoc_args WP CLI args.
 * @return void
 */
function filter_cli_args( $assoc_args ) {

	// Organize the params to be better consumed by WP_Query.
	if ( ! empty( $assoc_args['per-page'] ) ) {
		$assoc_args['posts_per_page'] = absint( $assoc_args['per-page'] );
		unset( $assoc_args['per-page'] );
	}

	if ( ! empty( $assoc_args['offset'] ) ) {
		$assoc_args['offset'] = absint( $assoc_args['offset'] );
	}

	if ( ! empty( $assoc_args['include'] ) ) {
		$include                = explode( ',', str_replace( ' ', '', $assoc_args['include'] ) );
		$assoc_args['include']  = array_map( 'absint', $include );
		$assoc_args['per-page'] = count( $assoc_args['include'] );
	}

	return $assoc_args;
}
