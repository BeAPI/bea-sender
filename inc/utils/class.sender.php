<?php
class Bea_Sender_Sender {

	private $campaigns = array( );
	private static $locked = false;
	private static $lock_file = '/lock-send.lock';

	/**
	 * Log for the bounces
	 *
	 * @var Bea_Log
	 */
	private $log;

	function __construct( ) {
		$this->log = new Bea_Log( WP_CONTENT_DIR.'/bea-sender-email-cron' );
	}

	/**
	 * @return array|bool
	 * @author Nicolas Juen
	 */
	public function init( ) {
		if( !self::lock() ) {
			return false;
		}

		$this->log->log_this( 'Start sending' );

		if( !$this->getCampaigns( ) ) {
			$this->log->log_this( 'No campaign to send, exit.' );
			// Unlock the file
			self::unlock();
		}

		return $this->sendCampaigns( );
	}

	/**
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function getCampaigns( ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		$cols = $wpdb->get_col( "SELECT id FROM $wpdb->bea_s_campaigns WHERE current_status IN( '".implode( "','", Bea_Sender_Campaign::getAuthStatuses( ) )."' ) AND scheduled_from <= '".current_time( 'mysql' )."' ORDER BY add_date ASC" );

		$this->log->log_this( sprintf( '%d campaigns to send', count( $cols ) ) );
		if( !isset( $cols ) || empty( $cols ) ) {
			$this->campaigns = array( );
			return false;
		}
		$this->campaigns = array_map( 'absint', $cols );
		return true;
	}

	/**
	 * @return array
	 * @author Nicolas Juen
	 */
	private function sendCampaigns( ) {
		$results = array( );

		do_action( 'bea_sender_before_send' );
		foreach( $this->campaigns as $campaign_id ) {
			$campaign = new Bea_Sender_Campaign( $campaign_id );
			if( $campaign->isData( ) !== true ) {
				continue;
			}
			$this->log->log_this( sprintf( 'Send %s campaign', $campaign_id ) );
			do_action( 'bea_sender_before_send_campaign', $campaign_id, $campaign );
			// Make the sending
			$results[] = $campaign->makeSend( );
			do_action( 'bea_sender_after_send_campaign', $campaign_id, $campaign );

		}
		do_action( 'bea_sender_after_send' );

		// Unlock the file
		self::unlock();

		$this->log->log_this( 'End campaigns '.var_export( $results, true ) );
		
		return $results;
	}

	/**
	 * @return bool
	 * @author Nicolas Juen
	 */
	public static function is_locked() {
		return self::$locked;
	}

	/**
	 * @return bool
	 * @author Nicolas Juen
	 */
	private static function lock() {

		clearstatcache();

		if( is_file( WP_CONTENT_DIR.self::$lock_file ) ) {
			self::$locked = true;
			return false;
		}
		
		// If we are already locked, stop now
		if( fopen( WP_CONTENT_DIR.self::$lock_file, "x" ) ) {
			self::$locked = true;
			return true;
		}
		
		return false;
	}

	/**
	 * @author Nicolas Juen
	 */
	private static function unlock() {
		// Remove the file if needed
		if( is_file( WP_CONTENT_DIR.self::$lock_file ) ) {
			unlink( WP_CONTENT_DIR.self::$lock_file );
		}
		
		// Unlock the file
		self::$locked = false;
	} 
}
