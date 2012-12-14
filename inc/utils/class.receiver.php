<?php
class Bea_Sender_Receiver {
	private $email = '';
	private $id = 0;
	
	function __construct( $email ) {
		$this->setEmail( $email );
		return $this;
	}
	
	public static function getReceiver( $email = '' ) {
		global $wpdb;
		if( empty( $email ) || !is_email( $email ) ) {
			return false;
		}
		
		// Get the user
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->bea_s_receivers WHERE 1=1 AND email=%s", $email ) );
	}
	
	public function setEmail( $email ) {
		if( !isset( $email) || empty( $email ) || !is_email( $email ) ) {
			return false;
		}
		
		$this->email = $email;
	}
	
	public function create() {
		if( !isset( $this->email ) || empty( $this->email ) ) {
			return false;
		}
		
		// Create the receiver
		$this->createReceiver();
		
		return $this->id;
	}
	
	private function createReceiver() {
		global $wpdb;
		
		// Try to get the receiver before
		$receiver = self::getReceiver( $this->email );
		
		if( !$receiver ) {
			// Insert the user
			$inserted = $wpdb->insert( $wpdb->bea_s_receivers, array( 'email' => $this->email, 'current_status' => 'valid'  ), array( '%s' ) );
		} else {
			$this->id = $receiver;
			return true;
		}
		
		//Return inserted element
		$this->id = ($inserted !== false) ? $wpdb->insert_id : 0;
		return $inserted;
	}
}