<?php

class Bea_Sender_BounceEmail {

	private $bmh;
	private static $locked = false;
	private static $lock_file = '/lock-bounce.lock';

	public function __construct() {
		$this->bmh = new BounceMailHandler();
	}

	public function bounce_init() {

		// If we are already locked, stop the process
		if ( !self::lock() ) {
			return false;
		}

		// Ge teh user options
		$options = get_option( 'bea_s-main' );

		// Make the default data
		$default = array(
			'mailhost'         => '',
			'mailbox_username' => '',
			'mailbox_password' => '',
			'port'             => 143,
			'service'          => 'imap',
			'service_option'   => 'notls',
			'boxname'          => 'INBOX',
			'movehard'         => false,
			'hardmailbox'      => 'INBOX.hard',
			'movesoft'         => false,
			'softmailbox'      => 'INBOX.soft'
		);

		// Parse the args
		$host = wp_parse_args( $options, $default );

		// Check the basic options
		if ( empty( $host['mailhost'] ) || empty( $host['mailbox_username'] ) || empty( $host['mailbox_password'] ) ) {
			// Unlock the file
			self::unlock();

			return false;
		}


		// BHM setup
		$this->bmh->mailhost = $host['mailhost'];
		// your mail server
		$this->bmh->mailbox_username = $host['mailbox_username'];
		// your mailbox username
		$this->bmh->mailbox_password = $host['mailbox_password'];
		// your mailbox password
		$this->bmh->port = $host['port'];
		// the port to access your mailbox, default is 143
		$this->bmh->service = $host['service'];
		// the service to use (imap or pop3), default is 'imap'
		$this->bmh->service_option = $host['service_option'];
		// the service options (none, tls, notls, ssl, etc.), default is 'notls'
		$this->bmh->boxname = $host['boxname'];
		// the mailbox to access, default is 'INBOX'
		$this->bmh->moveHard = $host['movehard'];
		// default is false
		$this->bmh->hardMailbox = $host['hardmailbox'];
		// default is 'INBOX.hard' - NOTE: must start with 'INBOX.'
		$this->bmh->moveSoft = $host['movesoft'];
		// default is false
		$this->bmh->softMailbox = $host['softmailbox'];
		// default is 'INBOX.soft' - NOTE: must start with 'INBOX.'
		//$this->bmh->deleteMsgDate      = '2009-01-05'; // format must be as
		// 'yyyy-mm-dd'

		$this->bmh->openMailbox();
		$this->bmh->action_function = array(
			$this,
			'callback_action'
		);
		$this->bmh->processMailbox();

		// Delete flag and do global deletes if true
		$this->bmh->globalDelete();

		// Unlock the file
		self::unlock();
	}

	/* This is a sample callback function for PHPMailer-BMH (Bounce Mail Handler).
	 * This callback function will echo the results of the BMH processing.
	 */

	/* Callback (action) function
	 * @param int     $msgnum        the message number returned by Bounce Mail
	 * Handler
	 * @param string  $bounce_type   the bounce type:
	 * 'antispam','autoreply','concurrent','content_reject','command_reject','internal_error','defer','delayed'
	 * =>
	 * array('remove'=>0,'bounce_type'=>'temporary'),'dns_loop','dns_unknown','full','inactive','latin_only','other','oversize','outofoffice','unknown','unrecognized','user_reject','warning'
	 * @param string  $email         the target email address
	 * @param string  $subject       the subject, ignore now
	 * @param string  $xheader       the campaign ID
	 * @param boolean $remove        remove status, 1 means removed, 0 means not
	 * removed
	 * @param string  $rule_no       Bounce Mail Handler detect rule no.
	 * @param string  $rule_cat      Bounce Mail Handler detect rule category.
	 * @param int     $totalFetched  total number of messages in the mailbox
	 * @return boolean
	 */

	public function callback_action( $msgnum, $bounce_type, $email, $subject, $xheader, $remove, $rule_no = false, $rule_cat = false, $totalFetched = 0 ) {
		/* @var $wpdb wpdb */
		global $wpdb;

		// The query for update bea_s_receivers table
		$receiver_result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bea_s_receivers WHERE email = %s", $email ) );

		// Update the receiver if possible
		if ( $receiver_result ) {
			$wpdb->update(
				$wpdb->bea_s_receivers, array(
					'current_status' => 'invalid',
					'bounce_cat'     => $rule_cat,
					'bounce_type'    => $bounce_type,
					'bounce_no'      => $rule_no
				), array( 'email' => $email ), array(
					'%s',
					'%s',
					'%s',
					'%s'
				), array( '%s' )
			);
		}

		// The query for update bea_s_re_ca table
		$receiver_id   = Bea_Sender_Receiver::getReceiver( $email );
		$re_ca__result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $wpdb->bea_s_re_ca WHERE id_campaign = %s AND id_receiver = %s", $xheader, $receiver_id ) );

		if ( $re_ca__result ) {
			$wpdb->update(
				$wpdb->bea_s_re_ca, array( 'current_status' => 'bounced' ), array(
					'id_campaign' => $xheader,
					'id_receiver' => $receiver_id
				), array( '%s' ), array(
					'%d',
					'%d'
				)
			);
		}

		return true;
	}

	/**
	 * Check if file is locked
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	public static function is_locked() {
		return self::$locked;
	}

	/**
	 * Lock or not the cron
	 *
	 * @return bool
	 * @author Nicolas Juen
	 */
	private static function lock() {
		clearstatcache();

		if ( is_file( WP_CONTENT_DIR . self::$lock_file ) ) {
			self::$locked = true;
			return false;
		}

		// If we are already locked, stop now
		if ( fopen( WP_CONTENT_DIR . self::$lock_file, "x" ) ) {
			self::$locked = true;
			return true;
		}

		return false;
	}

	/**
	 * Unlock the cron
	 *
	 * @author Nicolas Juen
	 */
	private static function unlock() {
		// Remove the file if needed
		if ( is_file( WP_CONTENT_DIR . self::$lock_file ) ) {
			unlink( WP_CONTENT_DIR . self::$lock_file );
		}

		// Unlock the file
		self::$locked = false;
	}

}
