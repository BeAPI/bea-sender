<?php
class Bea_Sender_Campaign {

	// Campaign data
	private $id = 0;
	private $add_date = '';
	private $scheduled_from = '';
	private $current_status = '';
	private $from_name = '';
	private $from = '';
	private $subject = '';
	private $attachments = array( );

	// Data for sending
	private $emailContents = array( );

	private $is_data = false;

	private static $auth_statuses = array(
		'registered',
		'progress',
		'done'
	);

	private $receivers;

	function __construct( $id = 0 ) {
		// Init the object by getting informations from the database
		return $this->setID( $id );
	}

	/**
	 * Getter
	 *
	 * @param $key_name
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	public function __get( $key_name ) {
		return $this->$key_name;
	}

	/**
	 * @param int $id
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function setID( $id = 0 ) {
		if( !isset( $id ) || $id <= 0 ) {
			return false;
		}

		$this->id = (int)$id;
		$this->setupBasics( );

		return $this->is_data === true ? $this->is_data : false;
	}

	/**
	 * @return int
	 * @author Nicolas Juen
	 */
	public function getID( ) {
		return (int)$this->id;
	}

	/**
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function isData( ) {
		return (bool)$this->is_data;
	}

	/**
	 * @return array
	 * @author Nicolas Juen
	 */
	public static function getAuthStatuses( ) {
		return self::$auth_statuses;
	}

	/**
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function setupBasics( ) {
		global $wpdb;

		$row = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `add_date`,`scheduled_from`, `current_status`, `from`, `from_name`, `subject` FROM $wpdb->bea_s_campaigns WHERE 1=1 AND id = %d", $this->id ) );

		if( empty( $row ) ) {
			return false;
		}

		// Setup vars
		$this->add_date = $row->add_date;
		$this->scheduled_from = $row->scheduled_from;
		$this->current_status = $row->current_status;
		$this->from_name = $row->from_name;
		$this->from = $row->from;
		$this->subject = $row->subject;
		$this->is_data = true;

		return true;
	}

	/**
	 * @return array|bool
	 * @author Nicolas Juen
	 */
	public function makeSend( ) {
		global $bea_send_counter;

		if( $bea_send_counter <= 0 ) {
			return array(
				0,
				0
			);
		}

		$ready = $this->setupSendingData( );

		if( empty( $ready ) ) {
			// Make the status done when the campaign is done
			$this->changeStatus( 'done' );
			return false;
		}

		return $this->send( );
	}

	/**
	 * @return bool|int
	 * @author Nicolas Juen
	 */
	public function deleteCampaign( ) {
		/* @var $wpdb wpdb */
		global $wpdb;
		if( $this->id <= 0 || !$this->isData( ) ) {
			return false;
		}

		// Get the reca cols
		$reca = $wpdb->get_results( $wpdb->prepare( "SELECT id, id_content FROM $wpdb->bea_s_re_ca WHERE id_campaign = %d", $this->id ), OBJECT_K );

		// Action for deleted campaign
		$allow = apply_filters( 'bea_sender_before_campaign_deleted', true );

		if( !isset( $reca ) || empty( $reca ) || $allow === false ) {
			return 0;
		}

		$contents = $wpdb->query( "DELETE FROM $wpdb->bea_s_contents WHERE id IN ( ".implode( ', ', array_unique( wp_list_pluck( $reca, 'id_content' ) ) ).")" );
		$reca = $wpdb->query( "DELETE FROM $wpdb->bea_s_re_ca WHERE id IN ( ".implode( ', ', array_unique( wp_list_pluck( $reca, 'id' ) ) ).")" );
		$attachments = $wpdb->delete( $wpdb->bea_s_attachments, array( 'campaign_id' => $this->id ), array( '%d' ) );
		$campaign = $wpdb->delete( $wpdb->bea_s_campaigns, array( 'id' => $this->id ), array( '%d' ) );

		// Action for deleted campaign
		do_action( 'bea_sender_campaign_deleted', $this );

		return $contents + $reca + $campaign + $attachments;
	}

	/**
	 * @return array|bool
	 * @author Nicolas Juen
	 */
	private function setupSendingData( ) {
		if( !isset( $this->id ) || empty( $this->id ) || !$this->is_data ) {
			return false;
		}
		$this->attachments = $this->get_attachments( );
		return $this->emailContents = $this->getEmailsContents( );
	}


	private function send( ) {
		global $bea_send_counter;
		$counter = 0;
		$failed = array( );

		// Put the filters for the from name and the from
		$this->addSendFilters( );

		foreach( $this->emailContents as $send ) {
			if( $bea_send_counter <= 0 ) {
				return array(
					0,
					0
				);
				break;
			}

			$counter++;

			// Mail trough the email class accepting the raw format
			$mailed = Bea_Sender_Email::wpMail( $send->email, $this->subject, array(
				'html' => self::contentReplace( $send->html, $send->email ),
				'raw' => self::contentReplace( $send->text, $send->email )
			), array( 'campaign-id: '.$this->id."\n" ), $this->attachments );

			if( !$mailed ) {
				$failed[] = $send->email;
				$this->changeRecaStatus( $send->reca_id, 'failed' );
			} else {
				$this->changeRecaStatus( $send->reca_id, 'send' );
			}
			$bea_send_counter--;
		}

		if( self::todo( ) <= 0 ) {
			$this->changeStatus( 'done' );
		} else {
			$this->changeStatus( 'progress' );
		}

		// Remove the filters
		$this->removeSendFilters( );

		return array(
			$failed,
			$counter
		);
	}

	private static function contentReplace( $content, $email ) {
		return str_replace( '{email}', $email, $content );
	}

	private function getEmailsContents( ) {
		global $wpdb, $bea_send_counter;

		$emails = $wpdb->get_results( $wpdb->prepare( "SELECT 
				reca.id as reca_id,
				r.id as r_id,
				r.email,
				c.html,
				c.text
			FROM $wpdb->bea_s_re_ca AS reca
				JOIN $wpdb->bea_s_receivers AS r ON r.id = reca.id_receiver
				JOIN $wpdb->bea_s_contents AS c ON reca.id_content = c.id
			WHERE
				1=1
				AND r.current_status = 'valid'
				AND reca.current_status = 'pending'
				AND reca.id_campaign = %d
				LIMIT 0, %d
			", $this->id, $bea_send_counter ) );

		return !isset( $emails ) || empty( $emails ) ? array( ) : $emails;
	}

	/**
	 * @return array
	 * @author Nicolas Juen
	 */
	private function get_attachments( ) {
		global $wpdb;

		$attachments = $wpdb->get_col( $wpdb->prepare( "SELECT
				path
			FROM $wpdb->bea_s_attachments 
			WHERE
				1=1
				AND campaign_id = %d
			", $this->id ) );

		return !isset( $attachments ) || empty( $attachments ) ? array( ) : $attachments;
	}

	/**
	 * @param array  $data_campaign
	 * @param array  $data
	 * @param string $content_html
	 * @param string $content_text
	 * @param array  $attachments
	 *
	 * @return array|bool
	 * @author Nicolas Juen
	 */
	public function add( $data_campaign = array(), $data = array(), $content_html = '', $content_text = '', $attachments = array() ) {

		// Add a campaign
		$ca_id = $this->createCampaign( $data_campaign );

		// Check added
		if( !$ca_id ) {
			return false;
		}

		$this->id = $ca_id;

		// Init content Id
		$c_id = 0;
		if( isset( $content_html ) && !empty( $content_html ) ) {
			// New content and create it
			$content = new Bea_Sender_Content( $content_html, $content_text );
			$c_id = $content->create( );
			if( $c_id === false ) {
				return false;
			}
		}

		// Handle the attachments
		if( isset( $attachments ) && is_array( $attachments ) && !empty( $attachments ) ) {
			foreach( $attachments as $attachment ) {

				// Make an attachment
				$att = new Bea_Sender_Attachment( $attachment );

				// Check the attachment
				if( $att->create( ) !== false ) {
					$this->add_attachment( $att );
				}
			}
		}

		// Return the addReceveivers data
		return $this->addReceivers( $data, $c_id );
	}

	/**
	 * @param array $data
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function createCampaign( $data = array() ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		if( !isset( $data['from_name'] ) || empty( $data['from_name'] ) ) {
			return false;
		}

		if( !isset( $data['from'] ) || !is_email( $data['from'] ) ) {
			// Get the options
			$options = get_option( 'bea_s-main' );

			// If there is an adresse given, then use it
			if( !isset( $options['mailbox_username'] ) || !is_email( $options['mailbox_username'] ) ) {
				return false;
			}

			$data['from'] = $options['mailbox_username'];
		}

		if( !isset( $data['subject'] ) || empty( $data['subject'] ) ) {
			return false;
		}

		$add_date = current_time( 'mysql' );
		if( !isset( $data['scheduled_from'] ) || empty( $data['scheduled_from'] ) ) {
			$data['scheduled_from'] = $add_date;
		}

		// Insert the row
		$inserted = $wpdb->insert( $wpdb->bea_s_campaigns, array(
			'add_date' => $add_date,
			'scheduled_from' => $data['scheduled_from'],
			'current_status' => 'registered',
			'from_name' => $data['from_name'],
			'from' => $data['from'],
			'subject' => $data['subject']
		), array(
			'%s',
			'%s',
			'%s',
			'%s'
		) );

		//Return inserted element
		return $inserted !== false ? $wpdb->insert_id : false;
	}

	/**
	 * @param array $receivers
	 * @param int   $c_id
	 *
	 * @return array
	 * @author Nicolas Juen
	 */
	private function addReceivers( $receivers = array(), $c_id = 0 ) {
		$result = array( );
		foreach( $receivers as $receiver ) {

			// Setup vars if multiple content or not
			$email = is_array( $receiver ) ? $receiver['email'] : $receiver;
			$html = is_array( $receiver ) && isset( $receiver['html'] ) ? $receiver['html'] : '';
			$text = is_array( $receiver ) && isset( $receiver['text'] ) ? $receiver['text'] : '';

			// Create the receiver
			$receiver_added = $this->addReceiver( $email, $c_id, $html, $text );

			// if not added,linked and stuff then add email to the list
			if( !$receiver_added ) {
				$result[] = $email;
			}
		}

		return $result;
	}

	/**
	 * @param string $email
	 * @param int    $c_id
	 * @param string $content_html
	 * @param string $content_text
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function addReceiver( $email = '', $c_id = 0, $content_html = '', $content_text = '' ) {
		// Create the user and get the ID on database
		$receiver = new Bea_Sender_Receiver( $email );
		$r_id = $receiver->create( );

		if( $r_id === false ) {
			return false;
		}

		// Link the content to the user simply if given otherwise create it
		if( !isset( $c_id ) || (int)$c_id == 0 ) {
			// Create the content
			$content = new Bea_Sender_Content( $content_html, $content_text );
			$c_id = $content->create( );
		}

		// Link the receiver, the content and the campaign
		return $this->linkReceiver( $c_id, $r_id );
	}

	private function linkReceiver( $c_id, $r_id ) {
		global $wpdb;
		if( !isset( $c_id ) || (int)$c_id <= 0 || !isset( $r_id ) || (int)$r_id <= 0 ) {
			return false;
		}

		$inserted = $wpdb->insert( $wpdb->bea_s_re_ca, array(
			'id_campaign' => $this->id,
			'id_receiver' => $r_id,
			'id_content' => $c_id,
			'current_status' => 'pending',
			'response' => ''
		) );

		return $inserted !== false ? true : false;
	}

	/**
	 * @param $id
	 * @param $status
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	private function changeRecaStatus( $id, $status ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		// Do action when changing the status
		do_action( 'bea_sender_transition_reca_status', $status, $id );

		return $wpdb->update( $wpdb->bea_s_re_ca, array( 'current_status' => $status ), array( 'id' => $id ) );
	}

	/**
	 * @param $status
	 *
	 * @return mixed
	 * @author Nicolas Juen
	 */
	private function changeStatus( $status ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		// Do action when changing the status
		do_action( 'bea_sender_transition_campaign_status', $this->current_status, $status, $this->id );
		return $wpdb->update( $wpdb->bea_s_campaigns, array( 'current_status' => $status ), array( 'id' => $this->id ) );
	}

	/**
	 * @return int
	 * @author Nicolas Juen
	 */
	public function todo( ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		$counter = $wpdb->get_var( $wpdb->prepare( "SELECT 
				COUNT(r.email) as counter
			FROM $wpdb->bea_s_re_ca AS reca
				JOIN $wpdb->bea_s_receivers AS r ON r.id = reca.id_receiver
			WHERE
				1=1
				AND r.current_status = 'valid'
				AND reca.current_status = 'pending'
				AND reca.id_campaign = %d
			", $this->id ) );

		return !isset( $counter ) ? 0 : (int)$counter;
	}

	/**
	 * @author Nicolas Juen
	 */
	private function addSendFilters( ) {
		// Add filters for correct email send
		add_filter( 'wp_mail_content_type', array(
			&$this,
			'returnHTML'
		) );
		add_filter( 'wp_mail_from', array(
			&$this,
			'mailFrom'
		) );
		add_filter( 'wp_mail_from_name', array(
			&$this,
			'mailFromName'
		) );
	}

	/**
	 * @author Nicolas Juen
	 */
	private function removeSendFilters( ) {
		// Remove filters
		remove_filter( 'wp_mail_content_type', array(
			&$this,
			'returnHTML'
		) );
		remove_filter( 'wp_mail_from', array(
			&$this,
			'mailFrom'
		) );
		remove_filter( 'wp_mail_from_name', array(
			&$this,
			'mailFromName'
		) );
	}

	/**
	 * @return string
	 * @author Nicolas Juen
	 */
	public function returnHtml( ) {
		return 'text/html';
	}

	/**
	 * Change email by the current campaign
	 *
	 * @return string : email
	 */
	function mailFrom( ) {
		return $this->from;
	}

	/**
	 * Change name from by the current campaign
	 *
	 * @return string
	 * @author Edouard Labre
	 */
	function mailFromName( ) {
		return $this->from_name;
	}

	/**
	 * Getthe campaign receivers
	 *
	 * @param (array)$where : the where query to add
	 * @param (array)$orderby : the where query to add
	 * @return Bea_Sender_Receiver objects
	 *
	 */
	public function get_receivers( $where = '', $orderby = '', $limit = '' ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		$receivers = $wpdb->get_results( $wpdb->prepare( "SELECT 
				r.id,
				r.current_status,
				r.email,
				r.bounce_cat,
				r.bounce_type,
				r.bounce_no,
				reca.current_status
			FROM $wpdb->bea_s_re_ca AS reca
				JOIN $wpdb->bea_s_receivers AS r 
				ON r.id = reca.id_receiver
			WHERE
				1=1
				AND reca.id_campaign = %d
				$where
				$orderby
				$limit
			", $this->id ) );

		foreach( $receivers as $receiver ) {
			$re = new Bea_Sender_Receiver( $receiver->email );
			if( $re->set_receiver( $this->id ) === false ) {
				continue;
			}
			$this->receivers[] = $re;
		}

		return $this->receivers;
	}

	/**
	 * Get the campaign total receivers
	 *
	 * @param (array)$where : the where query to add
	 * @param (array)$orderby : the where query to add
	 * @return Bea_Sender_Receiver objects
	 *
	 */
	public function get_total_receivers( $where = '', $orderby = '', $limit = '' ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		$receivers = $wpdb->get_var( $wpdb->prepare( "SELECT 
				COUNT( r.id )
			FROM $wpdb->bea_s_re_ca AS reca
				JOIN $wpdb->bea_s_receivers AS r 
				ON r.id = reca.id_receiver
			WHERE
				1=1
				AND reca.id_campaign = %d
				$where
				$orderby
				$limit
			", $this->id ) );

		return (int)$receivers;
	}
	
	/**
	 * Link an attachment to the current campaign
	 * 
	 * @param Bea_Sender_Attachment : attahcment object
	 * @return false|true
	 * @author Nicolas Juen
	 * 
	 */
	private function add_attachment( Bea_Sender_Attachment $attachment ) {
		return $attachment->link_campaign( $this );
	}
	
	/**
	 * Get a status emails counter
	 * 
	 * @param (string) : the status to count on
	 * @return int
	 * @author Nicolas Juen
	 * 
	 */
	public function get_status_count( $status ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "
		SELECT 
			COUNT( reca.id ) as status
		FROM $wpdb->bea_s_re_ca as reca
		WHERE 1 = 1 
		AND reca.current_status = %s
		AND reca.id_campaign = %d", $status, $this->id ) );
	}

}
