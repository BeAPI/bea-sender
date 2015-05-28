<?php

class Bea_Sender_Cron {

	function __construct() {
		add_action( 'generate_global_csv_event', array( __CLASS__, 'cron_buildCSV' ) );
		add_action( 'generate_global_bounces_csv_event', array( __CLASS__, 'cron_buildCSV_Bounces' ) );
	}

	public static function activate() {}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'generate_global_csv_event' );
		wp_clear_scheduled_hook( 'generate_global_bounces_csv_event' );
	}

	/**
	 * Create the CSV
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Zainoudine soulé
	 */
	public static function cron_buildCSV( $name = '' ) {
		if ( self::is_locked( $name ) ) {
			return false;
		}

		// Lock the file
		if ( self::create_lock_file( $name ) === false ) {
			return false;
		}

		self::generate_csv_global_campaign( $name );
	}

	/**
	 * Create the CSV
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Zainoudine soulé
	 */
	public static function cron_buildCSV_Bounces( $name ) {
		if ( self::is_locked( $name ) ) {
			return false;
		}

		// Lock the file
		if ( self::create_lock_file( $name ) === false ) {
			return false;
		}

		self::generate_csv_bounces_campaign( $name );
	}

	/**
	 * Check if locked file exist
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Zainoudine Soulé
	 */
	public static function is_locked( $name = '' ) {
		clearstatcache();

		return file_exists( self::get_lock_file_path( $name ) );
	}

	/**
	 * Get lock file
	 *
	 * @param string $name
	 *
	 * @return string
	 * @author Zainoudine Soulé
	 */
	public static function get_lock_file_path( $name = '' ) {
		// Create the file on system file
		$uploads = wp_upload_dir();

		$name = !empty( $name ) ? '.lock-bea-sender-' . $name : '.lock-bea-sender';

		return $uploads['basedir'] . '/' . $name;
	}

	/**
	 * Create the .lock file
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Zainoudine Soulé
	 */
	public function create_lock_file( $name = '' ) {
		return touch( self::get_lock_file_path( $name ) );
	}

	/**
	 * Delete lock file
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Zainoudine Soulé
	 */
	public static function delete_lock_file( $name = '' ) {
		return self::is_locked( $name ) ? unlink( self::get_lock_file_path( $name ) ) : true;
	}

	/**
	 * Export bea receivers for all campaign in CSV
	 *
	 * @return bool
	 * @author Zainoudine soulé
	 */
	private static function generate_csv_global_campaign( $type ) {
		$upload_dir = wp_upload_dir();
		$file_name  = $upload_dir['basedir'] . '/bea-sender-'.$type.'.csv';

		@unlink( $file_name );

		$header_titles = Bea_Sender_Export::get_header_titles( $type );
		$list          = Bea_Sender_Export::export_campaign();

		return self::generate_file( $file_name, $header_titles, $list, $type );
	}

	/**
	 * Export bounces
	 *
	 * @return bool
	 * @author Zainoudine soulé
	 */
	private static function generate_csv_bounces_campaign( $type ) {
		$upload_dir = wp_upload_dir();
		$file_name  = $upload_dir['basedir'] . '/bea-sender-'.$type.'.csv';

		@unlink( $file_name );

		$header_titles = Bea_Sender_Export::get_header_titles( $type );

		$list          = Bea_Sender_Export::export_bounces();

		return self::generate_file( $file_name, $header_titles, $list, $type );
	}

	private static function generate_file( $file_name, $headers, $list, $type ) {
		$outstream = fopen( $file_name, 'w' );
		fputcsv( $outstream, array_map( 'utf8_decode', $headers ), ';' );
		// Put lines in csv file
		foreach ( $list as $fields ) {
			fputcsv( $outstream, array_map( 'utf8_decode', $fields ), ';' );
		}

		fclose( $outstream );

		return self::delete_lock_file( $type );
	}

	public static function wp_get_schedule( $hook, $args = array() ) {
		$crons = _get_cron_array();
		$key   = md5( serialize( $args ) );
		if ( empty( $crons ) ) {
			return null;
		}

		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron[$hook][$key] ) ) {
				return true;
			}
		}

		return null;
	}
}