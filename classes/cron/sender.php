<?php
namespace BEA\Sender\Cron;

use BEA\Sender\Cron;
use BEA\Sender\Core\Campaign as Campaign;

class Sender extends Cron {

	private $campaigns = array( );

	/**
	 * Type for the cron
	 *
	 * @var string
	 */
	protected $type = 'send';

	/**
	 * @return array|bool
	 * @author Nicolas Juen
	 */
	public function process( ) {
		if( $this->is_locked() ) {
			return false;
		}

		$this->create_lock_file();

		$this->add_log( 'Start sending' );

		if( !$this->getCampaigns( ) ) {
			$this->add_log( 'No campaign to send, exit.' );
			// Unlock the file
			$this->delete_lock_file();
		}

		$result = $this->sendCampaigns( );

		$this->delete_lock_file();
		return $result;
	}

	/**
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function getCampaigns( ) {
		/* @var $wpdb \wpdb */
		global $wpdb;

		$cols = $wpdb->get_col( "SELECT id FROM $wpdb->bea_s_campaigns WHERE current_status IN( '".implode( "','", Bea_Sender_Campaign::getAuthStatuses( ) )."' ) AND scheduled_from <= '".current_time( 'mysql' )."' ORDER BY add_date ASC" );

		$this->add_log( sprintf( '%d campaigns to send', count( $cols ) ) );
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
			$campaign = new Campaign( $campaign_id );
			if( $campaign->isData( ) !== true ) {
				continue;
			}
			$this->add_log( sprintf( 'Send %s campaign', $campaign_id ) );
			do_action( 'bea_sender_before_send_campaign', $campaign_id, $campaign );
			// Make the sending
			$results[] = $campaign->makeSend( );
			do_action( 'bea_sender_after_send_campaign', $campaign_id, $campaign );

		}
		do_action( 'bea_sender_after_send' );

		$this->add_log( 'End campaigns '.var_export( $results, true ) );
		
		return $results;
	}
}
