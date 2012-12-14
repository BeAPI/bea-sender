<?php
Class Bea_Sender_Content{
	
	public $id = 0;
	private $content_html = '';
	private $content_text = '';
	
	function __construct( $content_html, $content_text = '' ) {
		$this->setContent( $content_html, $content_text );
		return $this;
	}
	
	private function setContent( $content_html, $content_text = '' ) {
		if( !isset( $content_html ) || empty( $content_html ) ) {
			return false;
		}
		
		$this->content_html = $content_html;
		$this->content_text = strip_tags( $content_text );
		return true;
	}
	
	public function create() {
		if( !isset( $this->content_html ) || empty( $this->content_html ) ) {
			return $this->id;
		}
		return $this->createContent();
	}
	
	private function createContent( ) {
		global $wpdb;

		$content_text = isset( $this->content_text ) ? $this->content_text : '' ;
		
		$inserted = $wpdb->insert( $wpdb->bea_s_contents, array( 'html' => $this->content_html, 'text' => $this->content_text ) );
		
		//Return inserted element
		$this->id =  $inserted!== false ? $wpdb->insert_id : 0;
		return $this->id;
	}
}