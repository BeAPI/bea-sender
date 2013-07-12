<?php
Class Bea_Sender_Attachment {

	public $id = 0;
	private $attachment_path;

	function __construct( $attachment_path = '' ) {
		if( !isset( $attachment_path ) || empty( $attachment_path ) ) {
			return false;
		}
		
		$this->set_attachment( $attachment_path );
		return $this;
	}
	
	public function create( ) {
		if( !isset( $this->attachment_path ) || empty( $this->attachment_path ) ) {
			return false;
		}
		return $this->create_attachment( );
	}
	
	public function get_attachment( ) {
		return $this->attachment_path;
	}
	
	public function link_campaign( Bea_Sender_Campaign $campaign ) {
		global $wpdb;
		if( !isset( $this->id ) || empty( $this->id ) || !isset( $this->attachment_path ) || empty( $this->attachment_path ) ) {
			return false;
		}
		
		return $wpdb->update( $wpdb->bea_s_attachments, array( 'campaign_id' => $campaign->getID() ), array( 'id' => $this->id ), array( '%d' ), array( '%d' ) );
	}

	private function set_attachment( $attachment_path ) {
		if( !is_file( $attachment_path ) ) {
			return false;
		}
		$this->attachment_path = $attachment_path;
		return true;
	}

	private function create_attachment( ) {
		global $wpdb;

		$inserted = $wpdb->insert( $wpdb->bea_s_attachments, array(
			'path' => $this->attachment_path,
		) );

		//Return inserted element
		$this->id = $inserted !== false ? $wpdb->insert_id : 0;
		return $this->id;
	}
}
