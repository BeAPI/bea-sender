<?php
Class Bea_Sender_Content {

	public $id = 0;
	private $content_html = '';
	private $content_text = '';

	function __construct( $content_html, $content_text = '' ) {
		$this->setContent( $content_html, $content_text );
		return $this;
	}

	/**
	 * @param        $content_html
	 * @param string $content_text
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function setContent( $content_html, $content_text = '' ) {
		if( !isset( $content_html ) || empty( $content_html ) ) {
			return false;
		}

		$this->content_html = $content_html;
		$this->content_text = strip_tags( $content_text );
		return true;
	}

	/**
	 * @return int
	 * @author Nicolas Juen
	 */
	public function create( ) {
		if( !isset( $this->content_html ) || empty( $this->content_html ) ) {
			return $this->id;
		}
		return $this->createContent( );
	}

	/**
	 * @return int
	 * @author Nicolas Juen
	 */
	private function createContent( ) {
		global $wpdb;

		$content_text = isset( $this->content_text ) ? $this->content_text : '';

		$inserted = $wpdb->insert( $wpdb->bea_s_contents, array(
			'html' => $this->content_html,
			'text' => $content_text
		) );

		//Return inserted element
		$this->id = $inserted !== false ? $wpdb->insert_id : 0;
		return $this->id;
	}

	/**
	 * @return string
	 * @author Nicolas Juen
	 */
	public function get_html( ) {
		return $this->content_html;
	}

	/**
	 * @return string
	 * @author Nicolas Juen
	 */
	public function get_text( ) {
		return $this->content_text;
	}

}
