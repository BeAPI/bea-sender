<?php
namespace BEA\Sender\Core;

class Attachment {

	public $id = 0;
	private $attachment_path;

	/**
	 * @param string $attachment_path
	 */
	function __construct( $attachment_path ) {
		if( !isset( $attachment_path ) || empty( $attachment_path ) ) {
			return false;
		}
		
		$this->set_attachment( $attachment_path );
		return $this;
	}

	/**
	 * @return bool|int
	 * @author Nicolas Juen
	 */
	public function create( ) {
		if( !isset( $this->attachment_path ) || empty( $this->attachment_path ) ) {
			return false;
		}
		return $this->create_attachment( );
	}

	/**
	 * @return mixed
	 * @author Nicolas Juen
	 */
	public function get_attachment( ) {
		return $this->attachment_path;
	}

	/**
	 * @param Campaign $campaign
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function link_campaign( Campaign $campaign ) {
		/* @var $wpdb \wpdb */
		global $wpdb;
		if( !isset( $this->id ) || empty( $this->id ) || !isset( $this->attachment_path ) || empty( $this->attachment_path ) ) {
			return false;
		}
		
		return $wpdb->update( $wpdb->bea_s_attachments, array( 'campaign_id' => $campaign->getID() ), array( 'id' => $this->id ), array( '%d' ), array( '%d' ) );
	}

	/**
	 * @param $attachment_path
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function set_attachment( $attachment_path ) {
		if( !is_file( $attachment_path ) ) {
			return false;
		}
		$this->attachment_path = $attachment_path;
		return true;
	}

	/**
	 * @return int
	 * @author Nicolas Juen
	 */
	private function create_attachment( ) {
		/* @var $wpdb \wpdb */
		global $wpdb;

		$inserted = $wpdb->insert( $wpdb->bea_s_attachments, array(
			'path' => $this->attachment_path,
		) );

		//Return inserted element
		$this->id = $inserted !== false ? $wpdb->insert_id : 0;
		return $this->id;
	}
}
