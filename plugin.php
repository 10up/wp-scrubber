<?php
/**
 * 10up WP Scrubber
 * 
 * @package TenUpWPScrubber
 * 
 * @wordpress-plugin
 * Plugin Name:       10up WP Scrubber
 * Plugin URI:        https://github.com/10up/wp-scrubber
 * Description:       A tool for scrubbing sensitive data from production databases. Available through CLI and includes actions and filters for extensibility.
 * Version:           0.1.0
 * Requires at least: 
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
define( 'TENUP_WP_SCRUBBER_VERSION', '0.1.0' );
define( 'TENUP_WP_SCRUBBER_URL', plugin_dir_url( __FILE__ ) );
define( 'TENUP_WP_SCRUBBER_PATH', plugin_dir_path( __FILE__ ) );
define( 'TENUP_WP_SCRUBBER_INC', TENUP_WP_SCRUBBER_PATH . 'includes/' );

$is_local_env = in_array( wp_get_environment_type(), [ 'local', 'development' ], true );
$is_local_url = strpos( home_url(), '.test' ) || strpos( home_url(), '.local' );
$is_local     = $is_local_env || $is_local_url;

if ( $is_local && file_exists( __DIR__ . '/dist/fast-refresh.php' ) ) {
	require_once __DIR__ . '/dist/fast-refresh.php';
	TenUpToolkit\set_dist_url_path( basename( __DIR__ ), TENUP_THEME_DIST_URL, TENUP_THEME_DIST_PATH );
}

// Require Composer autoloader if it exists.
if ( file_exists( TENUP_WP_SCRUBBER_PATH . 'vendor/autoload.php' ) ) {
	require_once TENUP_WP_SCRUBBER_PATH . 'vendor/autoload.php';
}

// Include files.
require_once TENUP_WP_SCRUBBER_INC . '/utility.php';
require_once TENUP_WP_SCRUBBER_INC . '/core.php';

// Activation/Deactivation.
register_activation_hook( __FILE__, '\TenUpWPScrubber\Core\activate' );
register_deactivation_hook( __FILE__, '\TenUpWPScrubber\Core\deactivate' );

// Bootstrap.
TenUpWPScrubber\Core\setup();
