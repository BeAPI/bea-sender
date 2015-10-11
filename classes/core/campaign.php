<?php
namespace BEA\Sender\Core;

class Campaign {

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

	/**
	 * Is data for this campaign
	 *
	 * @var bool
	 */
	private $is_data = false;

	/**
	 * Authorized statuses
	 *
	 * @var array
	 */
	private static $auth_statuses = array(
		'registered',
		'progress',
		'done'
	);

	/**
	 * The receiversfor this campaign
	 *
	 * @var array
	 */
	private $receivers;

	/**
	 * Id of the campaign
	 *
	 * @param int $id
	 */
	function __construct( $id ) {
		// Init the object by getting informations from the database
		return $this->set_ID( $id );
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
	private function set_ID( $id = 0 ) {
		if( !isset( $id ) || $id <= 0 ) {
			return false;
		}

		$this->id = (int)$id;
		$this->setup( );

		return $this->is_data === true ? $this->is_data : false;
	}

	/**
	 * @return int
	 * @author Nicolas Juen
	 */
	public function get_ID( ) {
		return (int)$this->id;
	}

	/**
	 * @return bool
	 * @author Nicolas Juen
	 */
	public function is_data( ) {
		return (bool)$this->is_data;
	}

	/**
	 * @return array
	 * @author Nicolas Juen
	 */
	public static function get_auth_statuses( ) {
		return self::$auth_statuses;
	}

	/**
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function setup( ) {
		/**
		 * @var $wpdb \wpdb
		 */
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
	public function make_send( ) {
		global $bea_send_counter;

		if( $bea_send_counter <= 0 ) {
			return array(
				0,
				0,
			);
		}

		$ready = $this->setup_sending_data( );

		if( empty( $ready ) ) {
			// Make the status done when the campaign is done
			$this->change_status( 'done' );
			return false;
		}

		return $this->send( );
	}

	/**
	 * @return bool|int
	 * @author Nicolas Juen
	 */
	public function delete( ) {
		/* @var $wpdb \wpdb */
		global $wpdb;
		if( $this->id <= 0 || !$this->is_data( ) ) {
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
	private function setup_sending_data( ) {
		if( !isset( $this->id ) || empty( $this->id ) || !$this->is_data ) {
			return false;
		}
		$this->attachments = $this->get_attachments( );
		return $this->emailContents = $this->get_emails_contents( );
	}

	/**
	 * Send the campaign data
	 *
	 * @return array
	 */
	private function send( ) {
		global $bea_send_counter;
		$counter = 0;
		$failed = array( );

		// Put the filters for the from name and the from
		$this->add_send_filters( );

		foreach( $this->emailContents as $send ) {
			if( $bea_send_counter <= 0 ) {
				return array(
					0,
					0
				);
				break;
			}

			/**
			 * Loop counter
			 */
			$counter++;

			// Mail trough the email class accepting the raw format
			$mailed = \Bea_Sender_Email::wpMail( $send->email, $this->subject, array(
				'html' => self::content_replace( $send, 'html' ),
				'raw' => self::content_replace( $send, 'text' ),
			), array( 'campaign-id: '.$this->id."\n" ), $this->attachments );

			if( !$mailed ) {
				$failed[] = $send->email;
				$this->change_reca_status( $send->reca_id, 'failed' );
			} else {
				$this->change_reca_status( $send->reca_id, 'send' );
			}

			/**
			 * Total counter for global session send
			 */
			$bea_send_counter--;
		}

		if( self::todo( ) <= 0 ) {
			$this->change_status( 'done' );
		} else {
			$this->change_status( 'progress' );
		}

		// Remove the filters
		$this->remove_send_filters( );

		return array(
			$failed,
			$counter,
		);
	}

	/**
	 * @param $send_data
	 * @param $type
	 *
	 * @return mixed|void
	 * @author Alexandre Sadowski
	 */
	private static function content_replace( $send_data, $type ) {
		$replaced = str_replace( '{email}', $send_data->email, $send_data->{$type} );
		return apply_filters( "bea_sender_campaign_replace_content_{$type}", $replaced, $send_data );
	}

	/**
	 * Get the email contents
	 *
	 * @return array|null|object
	 */
	private function get_emails_contents( ) {
		/**
		 * @var $wpdb \wpdb
		 */
		global $wpdb, $bea_send_counter;

		$emails = $wpdb->get_results( $wpdb->prepare( "SELECT 
				reca.id as reca_id,
				r.id as r_id,
				r.email,
				c.html,
				c.text,
				c.id as c_id,
				reca.id_campaign as id_campaign
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
		/**
		 * @var $wpdb \wpdb
		 */
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
	 * @return Campaign|bool
	 * @author Nicolas Juen
	 */
	public static function create( $data_campaign = array(), $data = array(), $content_html = '', $content_text = '', $attachments = array() ) {

		// Add a campaign
		$campaign = self::create_campaign( $data_campaign );

		// Check added
		if ( ! $campaign ) {
			return false;
		}

		// Init content Id
		$content_id = 0;
		if( isset( $content_html ) && !empty( $content_html ) ) {
			// New content and create it
			$content = Content::create( $content_html, $content_text );
			if( $content === false ) {
				return false;
			}

			$content_id = $content->get_id();
		}

		// Handle the attachments
		if( isset( $attachments ) && is_array( $attachments ) && !empty( $attachments ) ) {
			foreach( $attachments as $attachment ) {

				// Make an attachment
				$att = new Attachment( $attachment );

				// Check the attachment
				if( $att->create( ) !== false ) {
					$campaign->add_attachment( $att );
				}
			}
		}

		/**
		 * Add the receivers
		 */
		$campaign->add_receivers( $data, $content_id );

		// Return the campaign
		return $campaign;
	}

	/**
	 * @param array $data
	 *
	 * @return bool|Campaign
	 * @author Nicolas Juen
	 */
	private static function create_campaign( $data = array() ) {
		/* @var $wpdb \wpdb */
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
			'subject' => $data['subject'],
		), array(
			'%s',
			'%s',
			'%s',
			'%s',
		) );

		//Return inserted element
		return $inserted !== false ? new Campaign( $wpdb->insert_id ) : false;
	}

	/**
	 * @param array $receivers
	 * @param int   $content_id
	 *
	 * @return array
	 * @author Nicolas Juen
	 */
	private function add_receivers( $receivers = array(), $content_id = 0 ) {
		$result = array( );
		foreach( $receivers as $receiver ) {

			// Setup vars if multiple content or not
			$email = is_array( $receiver ) ? $receiver['email'] : $receiver;
			$html = is_array( $receiver ) && isset( $receiver['html'] ) ? $receiver['html'] : '';
			$text = is_array( $receiver ) && isset( $receiver['text'] ) ? $receiver['text'] : '';

			// Create the receiver
			$receiver_added = $this->add_receiver( $email, $content_id, $html, $text );

			// if not added,linked and stuff then add email to the list
			if( !$receiver_added ) {
				$result[] = $email;
			}
		}

		return $result;
	}

	/**
	 * @param string $email
	 * @param int    $content_id
	 * @param string $content_html
	 * @param string $content_text
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private function add_receiver( $email = '', $content_id = 0, $content_html = '', $content_text = '' ) {
		// Create the user and get the ID on database
		$receiver = new Receiver( $email );
		$receiver_id = $receiver->create( );

		if( $receiver_id === false ) {
			return false;
		}

		// Link the content to the user simply if given otherwise create it
		if( !isset( $content_id ) || (int)$content_id == 0 ) {
			// Create the content
			$content = Content::create( $content_html, $content_text );
			if( false === $content ){
				return false;
			}
			$content_id = $content->get_id();
		}

		// Link the receiver, the content and the campaign
		return $this->link_receiver( $content_id, $receiver_id );
	}

	/**
	 * Link a receivers to the campaign
	 *
	 * @param $content_id
	 * @param $receiver_id
	 *
	 * @return bool
	 */
	private function link_receiver( $content_id, $receiver_id ) {
		/**
		 * @var $wpdb \wpdb
		 */
		global $wpdb;
		if( !isset( $content_id ) || (int)$content_id <= 0 || !isset( $receiver_id ) || (int)$receiver_id <= 0 ) {
			return false;
		}

		$inserted = $wpdb->insert( $wpdb->bea_s_re_ca, array(
			'id_campaign' => $this->id,
			'id_receiver' => $receiver_id,
			'id_content' => $content_id,
			'current_status' => 'pending',
			'response' => '',
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
	private function change_reca_status( $id, $status ) {
		/* @var $wpdb \wpdb */
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
	private function change_status( $status ) {
		/* @var $wpdb \wpdb */
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
		/* @var $wpdb \wpdb */
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
	private function add_send_filters( ) {
		// Add filters for correct email send
		add_filter( 'wp_mail_content_type', array(
			__CLASS__,
			'return_html'
		) );
		add_filter( 'wp_mail_from', array(
			$this,
			'mail_from'
		) );
		add_filter( 'wp_mail_from_name', array(
			$this,
			'mail_from_name'
		) );
	}

	/**
	 * @author Nicolas Juen
	 */
	private function remove_send_filters( ) {
		// Remove filters
		remove_filter( 'wp_mail_content_type', array(
			__CLASS__,
			'return_html'
		) );
		remove_filter( 'wp_mail_from', array(
			$this,
			'mail_from'
		) );
		remove_filter( 'wp_mail_from_name', array(
			$this,
			'mail_from_name'
		) );
	}

	/**
	 * @return string
	 * @author Nicolas Juen
	 */
	public static function return_html( ) {
		return 'text/html';
	}

	/**
	 * Change email by the current campaign
	 *
	 * @return string : email
	 */
	public function mail_from( ) {
		return $this->from;
	}

	/**
	 * Change name from by the current campaign
	 *
	 * @return string
	 * @author Edouard Labre
	 */
	public function mail_from_name( ) {
		return $this->from_name;
	}

	/**
	 * Getthe campaign receivers
	 *
	 * @param string $where : the where query to add
	 * @param string $orderby : the where query to add
	 * @param string $limit : the limit query to add
	 * @return Receiver objects
	 *
	 */
	public function get_receivers( $where = '', $orderby = '', $limit = '' ) {
		/* @var $wpdb \wpdb */
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
			$re = new Receiver( $receiver->email );
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
	 * @param string $where : the where query to add
	 * @param string $orderby : the where query to add
	 * @param string $limit : the limit query to add
	 * @return Receiver objects
	 *
	 */
	public function get_total_receivers( $where = '', $orderby = '', $limit = '' ) {
		/* @var $wpdb \wpdb */
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
	 * @param Attachment : attachment object
	 * @return false|true
	 * @author Nicolas Juen
	 * 
	 */
	private function add_attachment( Attachment $attachment ) {
		return $attachment->link_campaign( $this );
	}
	
	/**
	 * Get a status emails counter
	 * 
	 * @param string $status the status to count on
	 * @return int
	 * @author Nicolas Juen
	 * 
	 */
	public function get_status_count( $status ) {
		/* @var $wpdb \wpdb */
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
