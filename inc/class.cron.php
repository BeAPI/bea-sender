<?php

class Bea_Sender_Cron {

	function __construct() {
		add_action( 'generate_global_csv_event', array( __CLASS__, 'cron_buildCSV' ) );
	}

	public static function activate() {
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'generate_global_csv_event' );
	}

	/**
	 * Create the CSV
	 *
	 * @param array $args
	 *
	 * @return bool
	 * @author Zainoudine soulé
	 */
	public static function cron_buildCSV( $args = array() ) {
		if ( self::is_locked( $args[0] ) ) {
			return false;
		}

		// Lock the file
		if ( self::create_lock_file( $args[0] ) === false ) {
			return false;
		}

		$export = self::generate_csv_global_compaign();
	}

	/**
	 * Check if locked file exist
	 *
	 * @param int $campaign_id
	 *
	 * @return bool
	 * @author Zainoudine Soulé
	 */
	public static function is_locked( $campaign_id = 0 ) {
		clearstatcache();

		return file_exists( self::get_lock_file_path( $campaign_id ) );
	}

	/**
	 * Get lock file
	 *
	 * @param int $campaign_id
	 *
	 * @return string
	 * @author Zainoudine Soulé
	 */
	public static function get_lock_file_path( $campaign_id = 0 ) {
		// Create the file on system file
		$uploads = wp_upload_dir();

		$name = $campaign_id > 0 ? '.lock-campaign-' . $campaign_id : '.lock-campaign';

		return $uploads['basedir'] . '/' . $name;
	}

	/**
	 * Create the .lock file
	 *
	 * @param int $campaign_id
	 *
	 * @return bool
	 * @author Zainoudine Soulé
	 */
	public function create_lock_file( $campaign_id = 0 ) {
		return touch( self::get_lock_file_path( $campaign_id ) );
	}

	/**
	 * Delete lock file
	 *
	 * @param $campaign_id
	 *
	 * @return bool
	 * @author Zainoudine Soulé
	 */
	public static function delete_lock_file( $campaign_id = 0 ) {
		return self::is_locked( $campaign_id ) ? unlink( self::get_lock_file_path( $campaign_id ) ) : true;
	}

	/**
	 * Export bea receivers for all campaign in CSV
	 *
	 * @return bool
	 * @author Zainoudine soulé
	 */
	private static function generate_csv_global_compaign() {
		$upload_dir = wp_upload_dir();
		$file_name  = $upload_dir['basedir'] . '/bea-send.csv';

		@unlink( $file_name );

		$header_titles = apply_filters(
			'bea_sender_csv_headers', array(
				'Id',
				'Email',
				'Current status',
				'Bounce cat',
				'Bounce type',
				'Bounce no'
			)
		);
		$list          = Bea_Sender_Export::export_campaign();


		$outstream = fopen( $file_name, 'w' );
		fputcsv( $outstream, array_map( 'utf8_decode', $header_titles ), ';' );
		// Put lines in csv file
		foreach ( $list as $fields ) {
			fputcsv( $outstream, array_map( 'utf8_decode', $fields ), ';' );
		}

		fclose( $outstream );

		self::delete_lock_file();

		return true;
	}

	public static function wp_get_schedule( $hook, $args = array() ) {
		$crons = _get_cron_array();
		$key   = md5( serialize( $args ) );
		if ( empty( $crons ) ) {
			return null;
		}

		foreach ( $crons as $timestamp => $cron ) {
			if ( isset( $cron[$hook][$key] ) ) {
				return $cron[$hook][$key]['schedule'];
			}
		}

		return null;
	}
}