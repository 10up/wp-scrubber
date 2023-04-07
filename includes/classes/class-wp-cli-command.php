<?php
/**
 * Main WP CLI command integration
 */

namespace TenUpWPScrubber;

/**
 * Register migration commands.
 * Class WP_CLI_Command
 *
 * @package TenUpWPScrubber
 */
class WP_CLI_Command extends \WP_CLI_Command {

	/**
	 * Scrub users
	 *
	 * Remove any user data from the database.
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @return bool
	 *
	 */
	public function all( $args, $assoc_args ) {

		define( 'WP_IMPORTING', true );
		define( 'WP_ADMIN', true );

		global $wpdb;

		// Drop tables if they exist.
		\WP_CLI::log( 'Scrubbing users...' );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->usermeta}_temp" );
		$wpdb->query( "DROP TABLE IF EXISTS {$wpdb->users}_temp" );

		\WP_CLI::log( ' - Duplicating users table...' );
		$wpdb->query( "CREATE TABLE {$wpdb->users}_temp LIKE $wpdb->users" );
		$wpdb->query( "INSERT INTO {$wpdb->users}_temp SELECT * FROM $wpdb->users" );
		
		\WP_CLI::log( ' - Scrub each user record...' );
		$dummy_users = $this->get_dummy_users();

		$offset = 0;
		$password = wp_hash_password( 'password' );

		$user_ids = [];

		while ( true ) {
			$users = $wpdb->get_results( $wpdb->prepare( "SELECT ID, user_login FROM {$wpdb->users}_temp LIMIT 1000 OFFSET %d", $offset ), 'ARRAY_A' );

			if ( empty( $users ) ) {
				break;
			}

			if ( 1000 <= $offset ) {
				usleep( 100 );
			}

			foreach ( $users as $user ) {
				$user_id = (int) $user['ID'];

				$user_ids[] = $user_id;

				$dummy_user = $dummy_users[ $user_id % 1000 ];

				$wpdb->query(
					$wpdb->prepare(
						"UPDATE {$wpdb->users}_temp SET user_pass=%s, user_email=%s, user_url='', user_activation_key='', display_name=%s WHERE ID=%d",
						$password,
						$dummy_user['email'],
						$user['user_login'],
						$user['ID']
					)
				);
			}

			$offset += 1000;
		}

		\WP_CLI::log( ' - Duplicating user meta table...' );

		$wpdb->query( "CREATE TABLE {$wpdb->usermeta}_temp LIKE $wpdb->usermeta" );
		$wpdb->query( "INSERT INTO {$wpdb->usermeta}_temp SELECT * FROM $wpdb->usermeta" );

		// Just truncate these fields
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

		\WP_CLI::log( ' - Replacing User tables with the scrubbed versions...' );

		$wpdb->query( "DROP TABLE {$wpdb->usermeta}" );
		$wpdb->query( "DROP TABLE {$wpdb->users}" );
		$wpdb->query( "RENAME TABLE {$wpdb->usermeta}_temp TO {$wpdb->usermeta}" );
		$wpdb->query( "RENAME TABLE {$wpdb->users}_temp TO {$wpdb->users}" );
	}

	private function get_dummy_users() {
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
}