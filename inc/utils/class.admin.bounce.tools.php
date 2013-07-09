<?php
class BEA_Admin_Settings_Main {
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
            self::$settings_api = new WeDevs_Settings_API();

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
            add_options_page( __('Bea Send', 'bea_s'), __('Bea Send', 'bea_s'), 'manage_options', 'bea_s-settings', array( __CLASS__, 'render_page_settings' ) );
	}

    /**
     * render_page_settings
     * 
     * @access public
     * @static
     *
     * @return mixed Value.
     */
	public static function render_page_settings() {
            include (BEA_SENDER_DIR . '/templates/admin-option-page.php');
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
            self::$settings_api->set_sections( self::get_settings_sections() );
            self::$settings_api->set_fields( self::get_settings_fields() );

            //initialize settings
            self::$settings_api->admin_init();
	}

    public static function get_settings_sections() {
        $sections = array(
            array(
                'id' => 'bea_s-main',
				'tab_label' => __( 'General', 'bea_s' ),
                'title' => __( 'Server settings', 'bea_s' ),
                'desc' => false,
            )
        );
        return $sections;
    }

    /**
     * Returns all the settings fields
     *
     * @return array settings fields
     */
    public static function get_settings_fields() {
        $settings_fields = array(
            'bea_s-main' => array(
                                    array(
                                        'name' => 'mailhost',
                                        'label' => __( 'Mailhost:', 'bea_s' ),
                                        'desc' => __( 'Your mail server.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),
                                     array(
                                        'name' => 'mailbox_username',
                                        'label' => __( 'Mailbox Username:', 'bea_s' ),
                                        'desc' => __( 'Your mailbox username.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                                      array(
                                        'name' => 'mailbox_password',
                                        'label' => __( 'Mailbox Password:', 'bea_s' ),
                                        'desc' => __( 'Your mailbox password', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                                      array(
                                        'name' => 'port',
                                        'label' => __( 'Port:', 'bea_s' ),
                                        'desc' => __( 'The port to access your mailbox, default is 143.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                                      array(
                                        'name' => 'service',
                                        'label' => __( 'Service:', 'bea_s' ),
                                        'desc' => __( 'The service to use (imap or pop3), default is imap.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                                      array(
                                        'name' => 'service_option',
                                        'label' => __( 'Service Option:', 'bea_s' ),
                                        'desc' => __( 'The service options (none, tls, notls, ssl, etc.), default is notls.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                                      array(
                                        'name' => 'boxname',
                                        'label' => __( 'Boxname:', 'bea_s' ),
                                        'desc' => __( 'The mailbox to access, default is INBOX.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                                       array(
                                        'name' => 'movehard',
                                        'label' => __( 'Move Hard:', 'bea_s' ),
                                        'desc' => __( 'Default is false.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  array(
                                        'name' => 'hardmailbox',
                                        'label' => __( 'Hard Mail Box:', 'bea_s' ),
                                        'desc' => __( 'Default is INBOX.hard - NOTE: must start with INBOX.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                                        array(
                                        'name' => 'movesoft',
                                        'label' => __( 'Move Soft:', 'bea_s' ),
                                        'desc' => __( 'Default is false.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                                        array(
                                        'name' => 'softmailbox',
                                        'label' => __( 'Soft Mail Box:', 'bea_s' ),
                                        'desc' => __( 'Default is INBOX.soft - NOTE: must start with INBOX.', 'bea_s' ),
                                        'type' => 'text',
                                        'default' => __('', 'bea_s')
                                    ),  
                
            ),

        );

        return $settings_fields;
    }

    /**
     * Get all the pages
     *
     * @return array page names with key value pairs
     */
    private static function _get_pages() {
        $pages = get_pages();
        $pages_options = array( 0 => __('Select a page', 'bea_s') );
        if ( $pages ) {
            foreach ($pages as $page) {
                $pages_options[$page->ID] = $page->post_title;
            }
        }

        return $pages_options;
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
		$input = stripslashes_deep($input);
		
		// Create our array for storing the validated options
		$output = array();
		
		// Loop through each of the incoming options
		foreach( self::get_default_options() as $key => $value ) {
			if( isset( $input[$key] ) ) {
				$output[$key] = strip_tags( $input[ $key ] ); // TODO : Remove striptags depending fields
			} else {
				$output[$key] = 0;
			}
		}
		
		// Constraint & Signon
		if ( (int) $output['allow-signon-email'] == 1 ) {
			$output['unique-email'] = 1;
		}
		
		// Return the array processing any additional functions filtered by this action
		return apply_filters( 'bea_s_settings_validate_input', $output, $input, self::$id );
	}       
	
}