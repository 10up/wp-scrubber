<?php
/**
 * Plugin specific helpers.
 *
 * @package TenUpWPScrubber
 */

namespace TenUpWPScrubber\Helpers;

/**
 * Get the size of the current database.
 *
 * @return int
 */
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

	$result = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT table_schema AS 'Database',
			SUM(data_length + index_length) / 1024 / 1024 AS 'Size (MB)'
			FROM information_schema.TABLES
			WHERE table_schema = %s
			GROUP BY table_schema;",
			$database_name
		)
	);

	if ( ! empty( $result ) ) {
		// Round to an integer.
		return intval( $result[0]->{'Size (MB)'} );
	}

	return 0;
}

/**
 * Apply_filters wrapper in case it's not defined
 *
 * @param string $hook_name The name of the filter hook.
 * @param mixed  $value     The value to filter.
 * @param mixed  ...$args   Additional parameters to pass to the callback functions.
 * @return mixed The filtered value after all hooked functions are applied to it.
 */
function wp_scrubber_apply_filters( $hook_name, $value, ...$args ) {
	if ( function_exists( 'apply_filters' ) ) {
		return apply_filters( $hook_name, $value, ...$args );
	}

	return $value;
}

/**
 * Logging helper function
 *
 * @param mixed    $message Message to log
 * @param callable $logger Logging function
 */
function log( $message, $logger = null ) {
	if ( ! empty( $logger ) && is_callable( $logger ) ) {
		$logger( $message );
	}
}

/**
 * Scrub comments
 *
 * Remove any comment data from the database.
 *
 * @param callable $logger Logging function
 */
function scrub_comments( $logger = null ) {
	global $wpdb;

	// Drop tables if they exist.
	log( "Scrubbing comments on {$wpdb->comments}...", $logger );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->comments}_temp" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->commentmeta}_temp" );

	log( ' - Duplicating comments table into temp table...', $logger );
	$wpdb->query( "CREATE TABLE {$wpdb->comments}_temp LIKE $wpdb->comments" );
	$wpdb->query( "INSERT INTO {$wpdb->comments}_temp SELECT * FROM $wpdb->comments" );

	log( ' - Duplicating comment meta table into temp table...', $logger );
	$wpdb->query( "CREATE TABLE {$wpdb->commentmeta}_temp LIKE $wpdb->commentmeta" );
	$wpdb->query( "INSERT INTO {$wpdb->commentmeta}_temp SELECT * FROM $wpdb->commentmeta" );

	// TODO: We may want more sophisticated scrubbing of comments later, but right now we'll just truncate the tables.
	log( ' - Scrubbing comments table...', $logger );
	$wpdb->query( "TRUNCATE TABLE {$wpdb->comments}_temp" );
	$wpdb->query( "TRUNCATE TABLE {$wpdb->commentmeta}_temp" );

	log( ' - Replacing comment tables with the scrubbed versions...', $logger );
	$wpdb->query( "DROP TABLE {$wpdb->comments}" );
	$wpdb->query( "DROP TABLE {$wpdb->commentmeta}" );
	$wpdb->query( "RENAME TABLE {$wpdb->comments}_temp TO {$wpdb->comments}" );
	$wpdb->query( "RENAME TABLE {$wpdb->commentmeta}_temp TO {$wpdb->commentmeta}" );
}

/**
 * Scrub WordPress Users
 *
 * @param array    $allowed_domains Allowed email domains
 * @param array    $allowed_emails  Allowed email addresses
 * @param callable $logger Logging function
 * @return void
 */
function scrub_users( $allowed_domains = [], $allowed_emails = [], $logger = null ) {
	global $wpdb;

	// Drop tables if they exist.
	log( 'Scrubbing users...', $logger );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->usermeta}_temp" );
	$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->users}_temp" );

	log( ' - Duplicating users table into temp tables...', $logger );
	$wpdb->query( "CREATE TABLE {$wpdb->users}_temp LIKE $wpdb->users" );
	$wpdb->query( "INSERT INTO {$wpdb->users}_temp SELECT * FROM $wpdb->users" );

	log( ' - Scrubbing each user record...', $logger );
	$dummy_users = get_dummy_users();

	$offset   = 0;
	$user_ids = [];

	while ( true ) {
		$users = $wpdb->get_results( $wpdb->prepare( "SELECT ID, user_login, user_email FROM {$wpdb->users}_temp LIMIT 1000 OFFSET %d", $offset ), 'ARRAY_A' );

		if ( empty( $users ) ) {
			break;
		}

		if ( 1000 <= $offset ) {
			usleep( 100 );
		}

		foreach ( $users as $user ) {
			$user_id    = (int) $user['ID'];
			$user_ids[] = $user_id;
			$dummy_user = $dummy_users[ $user_id % 1000 ];

			scrub_user( $user, $dummy_user, $allowed_domains, $allowed_emails );
		}

		$offset += 1000;
	}

	log( ' - Duplicating user meta table into temp table...', $logger );

	$wpdb->query( "CREATE TABLE {$wpdb->usermeta}_temp LIKE $wpdb->usermeta" );
	$wpdb->query( "INSERT INTO {$wpdb->usermeta}_temp SELECT * FROM $wpdb->usermeta" );

	// Just truncate user description and session tokens.
	$wpdb->query( "UPDATE {$wpdb->usermeta}_temp SET meta_value='' WHERE meta_key='description' OR meta_key='session_tokens'" );

	$user_ids_count = count( $user_ids );
	for ( $i = 0; $i < $user_ids_count; $i++ ) {
		if ( 1 < $i && 0 === $i % 1000 ) {
			usleep( 100 );
		}

		$user_id = $user_ids[ $i ];

		$dummy_user = $dummy_users[ $user_id % 1000 ];

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->usermeta}_temp SET meta_value=%s WHERE meta_key='first_name' AND user_id=%d",
				$dummy_user['first_name'],
				(int) $user_id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->usermeta}_temp SET meta_value=%s WHERE meta_key='last_name' AND user_id=%d",
				$dummy_user['last_name'],
				$user_id
			)
		);

		$wpdb->query(
			$wpdb->prepare(
				"UPDATE {$wpdb->usermeta}_temp SET meta_value=%s WHERE meta_key='nickname' AND user_id=%d",
				$dummy_user['first_name'],
				$user_id
			)
		);
	}

	log( ' - Replacing user tables with the scrubbed versions...', $logger );

	$wpdb->query( "DROP TABLE {$wpdb->usermeta}" );
	$wpdb->query( "DROP TABLE {$wpdb->users}" );
	$wpdb->query( "RENAME TABLE {$wpdb->usermeta}_temp TO {$wpdb->usermeta}" );
	$wpdb->query( "RENAME TABLE {$wpdb->users}_temp TO {$wpdb->users}" );
}

/**
 * Scrub the user data
 *
 * @param array $user User array from wpdb query.
 * @param array $dummy_user User array from dummy user csv.
 * @param array $allowed_domains Allowed email domains
 * @param array $allowed_emails  Allowed email addresses
 */
function scrub_user( $user, $dummy_user, $allowed_domains = [], $allowed_emails = [] ) {

	global $wpdb;

	$scrub_user = true;

	if ( ! should_scrub_user( $user, $allowed_domains, $allowed_emails ) ) {
		return false;
	}

	/**
	 * Allow site owners to define their own user password ruleset.
	 * Otherwise, use the WordPress generated password.
	 * wp_generate_password() could potentially have performance
	 * issues on sites with a large user base.
	 */
	$password = wp_scrubber_apply_filters( 'wp_scrubber_scrubbed_password', false );
	if ( false === $password ) {
		$password = wp_hash_password( wp_generate_password() );
	}

	return $wpdb->query(
		$wpdb->prepare(
			"UPDATE {$wpdb->users}_temp SET user_pass=%s, user_email=%s, user_url='', user_activation_key='', user_login=%s, user_nicename=%s, display_name=%s WHERE ID=%d",
			$password,
			$dummy_user['email'],
			$dummy_user['username'],
			$dummy_user['username'],
			$dummy_user['first_name'] . ' ' . $dummy_user['last_name'],
			$user['ID']
		)
	);
}

/**
 * Add conditions to check whether a user should be scrubbed or not.
 *
 * @param array $user User array from wpdb query.
 * @param array $allowed_domains Allowed email domains
 * @param array $allowed_emails  Allowed email addresses
 * @return boolean
 */
function should_scrub_user( $user, $allowed_domains = [], $allowed_emails = [] ) {

	$scrub = true;

	// Check if the user is part of list of allowed email domains.
	$allowed_email_domains = wp_scrubber_apply_filters( 'wp_scrubber_allowed_email_domains', $allowed_domains );

	foreach ( $allowed_email_domains as $domain ) {
		if ( str_contains( $user['user_email'], '@' . $domain ) ) {
			$scrub = false;
		}
	}

	// Check if the user has been specifically allowed.
	$allowed_emails = wp_scrubber_apply_filters( 'wp_scrubber_allowed_emails', $allowed_emails );
	foreach ( $allowed_emails as $email ) {
		if ( $user['user_email'] === $email ) {
			$scrub = false;
		}
	}

	return wp_scrubber_apply_filters( 'wp_scrubber_should_scrub_user', $scrub, $user );
}

/**
 * Get dummy users from csv file.
 *
 * @return array
 */
function get_dummy_users() {
	static $users = [];

	if ( empty( $users ) ) {
		$file = fopen( trailingslashit( TENUP_WP_SCRUBBER_INC ) . 'data/users.csv', 'r' ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fopen

		$line = fgetcsv( $file );
		while ( false !== $line ) {

			$user = [
				'username'   => $line[0],
				'first_name' => $line[1],
				'last_name'  => $line[2],
				'email'      => $line[3],
			];

			$users[] = $user;

			$line = fgetcsv( $file );
		}

		fclose( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_fclose
	}

	return $users;
}
