<?php
namespace BEA\Sender\Admin;

class Bounce {
	static $settings_api;
	static $id = 'bea_s-main';

	/**
	 * __construct
	 *
	 * @access public
	 *
	 * @return mixed Value.
	 */
	public function __construct( ) {
		self::$settings_api = new \WeDevs_Settings_API( );

		add_action( 'admin_menu', array( __CLASS__, 'admin_menu' ) );
		add_action( 'admin_init', array( __CLASS__, 'admin_init' ) );
	}

	/**
	 * admin_menu
	 *
	 * @param mixed $hook Description.
	 *
	 * @access public
	 * @static
	 *
	 * @return mixed Value.
	 */
	public static function admin_menu( ) {
		add_options_page( __( 'BEA Send', 'bea-sender' ), __( 'BEA Send', 'bea-sender' ), 'manage_options', 'bea_s-settings', array( __CLASS__, 'render_page_settings' ) );
	}

	/**
	 * render_page_settings
	 *
	 * @access public
	 * @static
	 *
	 * @return mixed Value.
	 */
	public static function render_page_settings( ) {
		if( is_file( BEA_SENDER_DIR.'/views/admin/option-page.php' ) ) {
			include ( BEA_SENDER_DIR.'/views/admin/option-page.php' );
		} else {
			_e( 'Missing template', 'bea-sender' );
		}
	}

	/**
	 * admin_init
	 *
	 * @access public
	 * @static
	 *
	 * @return mixed Value.
	 */
	public static function admin_init( ) {
		//set the settings
		self::$settings_api->set_sections( self::get_settings_sections( ) );
		self::$settings_api->set_fields( self::get_settings_fields( ) );

		//initialize settings
		self::$settings_api->admin_init( );
	}

	public static function get_settings_sections( ) {
		$sections = array( array(
				'id' => BEA_SENDER_OPTION_NAME,
				'tab_label' => __( 'General', 'bea-sender' ),
				'title' => __( 'Server settings', 'bea-sender' ),
				'desc' => false,
			) );
		return $sections;
	}

	/**
	 * Returns all the settings fields
	 *
	 * @return array settings fields
	 */
	public static function get_settings_fields( ) {
		$settings_fields = array( BEA_SENDER_OPTION_NAME => array(
				array(
					'name' => 'mailhost',
					'label' => __( 'Mailhost:', 'bea-sender' ),
					'desc' => __( 'Your mail server.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'mailbox_username',
					'label' => __( 'Mailbox Username:', 'bea-sender' ),
					'desc' => __( 'Your mailbox username.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'mailbox_password',
					'label' => __( 'Mailbox Password:', 'bea-sender' ),
					'desc' => __( 'Your mailbox password', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'port',
					'label' => __( 'Port:', 'bea-sender' ),
					'desc' => __( 'The port to access your mailbox, default is 143.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'service',
					'label' => __( 'Service:', 'bea-sender' ),
					'desc' => __( 'The service to use (imap or pop3), default is imap.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'service_option',
					'label' => __( 'Service Option:', 'bea-sender' ),
					'desc' => __( 'The service options (none, tls, notls, ssl, etc.), default is notls.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'boxname',
					'label' => __( 'Boxname:', 'bea-sender' ),
					'desc' => __( 'The mailbox to access, default is INBOX.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'movehard',
					'label' => __( 'Move Hard:', 'bea-sender' ),
					'desc' => __( 'Default is false.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'hardmailbox',
					'label' => __( 'Hard Mail Box:', 'bea-sender' ),
					'desc' => __( 'Default is INBOX.hard - NOTE: must start with INBOX.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'movesoft',
					'label' => __( 'Move Soft:', 'bea-sender' ),
					'desc' => __( 'Default is false.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
				array(
					'name' => 'softmailbox',
					'label' => __( 'Soft Mail Box:', 'bea-sender' ),
					'desc' => __( 'Default is INBOX.soft - NOTE: must start with INBOX.', 'bea-sender' ),
					'type' => 'text',
					'default' => __( '', 'bea-sender' )
				),
			), );

		return $settings_fields;
	}

	/**
	 * TODO: Keep logic
	 *
	 * @param mixed $input Description.
	 *
	 * @access public
	 * @static
	 *
	 * @return mixed Value.
	 */
	public static function validate_input( $input ) {
		// Cleanup input
		$input = stripslashes_deep( $input );

		// Create our array for storing the validated options
		$output = array( );

		// Loop through each of the incoming options
		foreach( self::get_default_options() as $key => $value ) {
			if( isset( $input[$key] ) ) {
				$output[$key] = strip_tags( $input[$key] );
				// TODO : Remove striptags depending fields
			} else {
				$output[$key] = 0;
			}
		}

		// Return the array processing any additional functions filtered by this action
		return apply_filters( 'bea_s_settings_validate_input', $output, $input, self::$id );
	}

}
