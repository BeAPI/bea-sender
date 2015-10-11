<?php
namespace BEA\Sender\Core;

class Receiver {
	private $email = '';
	private $id = 0;
	private $current_status = '';
	private $campaign_current_status = '';
	private $bounce_cat = '';
	private $bounce_type = '';
	private $bounce_no = '';

	public $content;

	/**
	 * @param $email
	 */
	function __construct( $email ) {
		$this->setEmail( $email );

		return $this;
	}

	/**
	 * @param string $email
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public static function getReceiver( $email = '' ) {
		global $wpdb;
		if ( empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		// Get the user
		return $wpdb->get_var( $wpdb->prepare( "SELECT id FROM $wpdb->bea_s_receivers WHERE 1=1 AND email=%s", $email ) );
	}

	/**
	 * @param $email
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function setEmail( $email ) {
		if ( ! isset( $email ) || empty( $email ) || ! is_email( $email ) ) {
			return false;
		}

		$this->email = $email;
	}

	/**
	 * @param int $campaign_id
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function set_receiver( $campaign_id = 0 ) {
		$receiver = self::getReceiver( $this->email );

		if ( ! isset( $receiver ) || empty( $receiver ) ) {
			return false;
		}

		// Setup id
		$this->id = (int) $receiver;

		// Make the user data to the object
		return $this->setup_receiver( $campaign_id );
	}

	/**
	 * @param int $campaign_id
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function setup_receiver( $campaign_id = 0 ) {
		/* @var $wpdb \wpdb */
		global $wpdb;

		if ( (int) $campaign_id <= 0 ) {
			$data = $wpdb->get_row( $wpdb->prepare( "
					SELECT * FROM $wpdb->bea_s_receivers as r
					WHERE 1=1 
						AND r.id=%d", $this->id ) );
		} else { // Campaign data related
			$data = $wpdb->get_row( $wpdb->prepare( "
					SELECT r.id, email, reca.current_status as campaign_current_status, r.current_status, bounce_cat, bounce_type, bounce_no FROM $wpdb->bea_s_receivers as r
						JOIN $wpdb->bea_s_re_ca AS reca ON r.id = reca.id_receiver 
					WHERE 1=1 
						AND r.id=%d
						AND id_campaign = %d", $this->id, $campaign_id ) );
		}

		foreach ( $data as $key => $value ) {
			$this->$key = $value;
		}

		return true;
	}

	/**
	 * @param int $campaign_id
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function get_contents_campaign( $campaign_id = 0 ) {
		/* @var $wpdb \wpdb */
		global $wpdb;

		$data = $wpdb->get_row( $wpdb->prepare( "SELECT 
				id
			FROM $wpdb->bea_s_re_ca AS reca
				JOIN $wpdb->bea_s_receivers AS r ON r.id = reca.id_receiver
				JOIN $wpdb->bea_s_contents AS c ON reca.id_content = c.id
			WHERE
				1=1
				AND reca.id_campaign = %d
				AND r.id = %d
			", $campaign_id, $this->id ) );

		if ( ! isset( $data ) || empty( $data ) ) {
			return false;
		}

		// Setup object
		$this->content = new Content( $data->id );

		return true;
	}

	/**
	 * @return bool|int
	 * @author Nicolas Juen
	 */
	public function create() {
		if ( ! isset( $this->email ) || empty( $this->email ) ) {
			return false;
		}

		// Create the receiver
		$this->createReceiver();

		return $this->id;
	}

	/**
	 * @return bool|false|int
	 * @author Nicolas Juen
	 */
	private function createReceiver() {
		/* @var $wpdb \wpdb */
		global $wpdb;

		// Try to get the receiver before
		$receiver = self::getReceiver( $this->email );

		if ( ! $receiver ) {
			// Insert the user
			$inserted = $wpdb->insert( $wpdb->bea_s_receivers, array(
				'email'          => $this->email,
				'current_status' => 'valid'
			), array( '%s' ) );
		} else {
			$this->id = $receiver;

			return true;
		}

		//Return inserted element
		$this->id = ( $inserted !== false ) ? $wpdb->insert_id : 0;

		return $inserted;
	}

	/**
	 * @param $name
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	public function __get( $name ) {
		return $this->$name;
	}

}
