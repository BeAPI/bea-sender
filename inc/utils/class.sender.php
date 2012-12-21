<?php
class Bea_Sender_Sender {
	
	private $campaigns = array();
	
	function __construct() {}
	
	public function init() {
		if( !$this->getCampaigns() ) {
			return false;
		}
		
		return $this->sendCampaigns();
	}
	
	private function getCampaigns() {
		global $wpdb;
		
		$cols = $wpdb->get_col( "SELECT id FROM $wpdb->bea_s_campaigns WHERE current_status IN( '".implode( "','", Bea_Sender_Campaign::getAuthStatuses() )."' ) AND scheduled_from <= NOW() ORDER BY add_date ASC" );
		
		if( !isset( $cols ) || empty( $cols ) ) {
			$this->campaigns = array();
			return false;
		}
		$this->campaigns = array_map( 'absint' ,$cols );
		return true;
	}
	
	private function sendCampaigns() {
		$results = array();
		foreach( $this->campaigns as $campaign_id ) {
			$campaign = new Bea_Sender_Campaign( $campaign_id );
			if( $campaign->isData() !== true ) {
				continue;
			}
			
			// Make the sending
			$results[] = $campaign->makeSend();
		}
		return $results;
	}
}