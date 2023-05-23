<?php
/**
 * Plugin specific helpers.
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber\Helpers;

function get_database_size() {
	global $wpdb;

	$database_name = $wpdb->dbname;

	$query = "
		SELECT table_schema AS 'Database',
		SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)'
		FROM information_schema.TABLES
		WHERE table_schema = '$database_name'
		GROUP BY table_schema;
	";

	$result = $wpdb->get_results($query);

	if (!empty($result)) {
		// Round to an integer.
		return intval( $result[0]->{'Size (MB)'} );
	}

	return 0;
}
