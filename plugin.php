<?php
/**
 * WP Scrubber
 *
 * @package TenUpWPScrubber
 *
 * @wordpress-plugin
 * Plugin Name:       WP Scrubber
 * Plugin URI:        https://github.com/10up/wp-scrubber
 * Description:       A tool for scrubbing sensitive data from production databases. Available through CLI. Includes actions and filters for extensibility.
 * Version:           1.0.2
 * Requires PHP:      8.0+
 * Author:            10up
 * Author URI:        https://10up.com
 * License:           GPL v2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Update URI:        https://github.com/10up/wp-scrubber
 * Text Domain:       wp-scrubber
 * Domain Path:       /languages
 */

// Useful global constants.
define( 'TENUP_WP_SCRUBBER_VERSION', '1.0.2' );
define( 'TENUP_WP_SCRUBBER_URL', plugin_dir_url( __FILE__ ) );
define( 'TENUP_WP_SCRUBBER_PATH', plugin_dir_path( __FILE__ ) );
define( 'TENUP_WP_SCRUBBER_INC', TENUP_WP_SCRUBBER_PATH . 'includes/' );

// Require Composer autoloader if it exists.
if ( file_exists( TENUP_WP_SCRUBBER_PATH . 'vendor/autoload.php' ) ) {
	require_once TENUP_WP_SCRUBBER_PATH . 'vendor/autoload.php';
} else {
	spl_autoload_register(
		function( $class ) {
			// project-specific namespace prefix.
			$prefix = 'TenUpWPScrubber\\';

			// base directory for the namespace prefix.
			$base_dir = __DIR__ . '/includes/classes/';

			// does the class use the namespace prefix?
			$len = strlen( $prefix );

			if ( strncmp( $prefix, $class, $len ) !== 0 ) {
				return;
			}

			$relative_class = substr( $class, $len );

			$file = $base_dir . str_replace( '\\', '/', $relative_class ) . '.php';

			// if the file exists, require it.
			if ( file_exists( $file ) ) {
				require $file;
			}
		}
	);

	// Include files.
	require_once TENUP_WP_SCRUBBER_INC . '/core.php';
	require_once TENUP_WP_SCRUBBER_INC . '/helpers.php';
}

// Bootstrap.
TenUpWPScrubber\Core\setup();
