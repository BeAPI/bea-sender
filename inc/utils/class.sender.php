<?php
class Bea_Sender_Sender {

	private $campaigns = array( );
	private static $locked = false;
	private static $lock_file = '/lock-send.lock';

	function __construct( ) {}

	public function init( ) {
		if( !self::lock() ) {
			return false;
		}
		
		if( !$this->getCampaigns( ) ) {
			// Unlock the file
			self::unlock();
		}
		
		return $this->sendCampaigns( );
	}

	private function getCampaigns( ) {
		global $wpdb;

		$cols = $wpdb->get_col( "SELECT id FROM $wpdb->bea_s_campaigns WHERE current_status IN( '".implode( "','", Bea_Sender_Campaign::getAuthStatuses( ) )."' ) AND scheduled_from <= '".current_time( 'mysql' )."' ORDER BY add_date ASC" );

		if( !isset( $cols ) || empty( $cols ) ) {
			$this->campaigns = array( );
			return false;
		}
		$this->campaigns = array_map( 'absint', $cols );
		return true;
	}

	private function sendCampaigns( ) {
		$results = array( );
		foreach( $this->campaigns as $campaign_id ) {
			$campaign = new Bea_Sender_Campaign( $campaign_id );
			if( $campaign->isData( ) !== true ) {
				continue;
			}

			// Make the sending
			$results[] = $campaign->makeSend( );
		}
		
		// Unlock the file
		self::unlock();
		
		return $results;
	}
	
	public static function is_locked() {
		return self::$locked;
	}
	
	private static function lock() {
		if( is_file( BEA_SENDER_DIR.'tools'.self::$lock_file ) ) {
			self::$locked = true;
			return false;
		}
		
		// If we are already locked, stop now
		if( fopen( BEA_SENDER_DIR.'tools'.self::$lock_file, "x" ) ) {
			self::$locked = true;
			return true;
		}
		
		return false;
	}
	
	private static function unlock() {
		// Remove the file if needed
		if( is_file( BEA_SENDER_DIR.'tools'.self::$lock_file ) ) {
			unlink( BEA_SENDER_DIR.'tools'.self::$lock_file );
		}
		
		// Unlock the file
		self::$locked = false;
	} 
}
