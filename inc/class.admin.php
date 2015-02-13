<?php

class Bea_Sender_Admin {
	private $ListTable = null;
	private $ListTableSingle = null;
	private $campaign_table = null;

	/**
	 * Constructor
	 *
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Add the menu page
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );

		// Init the WP_List_Table
		add_action( 'load-tools_page_' . 'bea_sender', array( &$this, 'init_table' ), 2 );

		// Screen options
		add_filter( 'set-screen-option', array( __CLASS__, 'set_options' ), 1, 3 );
		add_action( 'load-tools_page_' . 'bea_sender', array( __CLASS__, 'add_option_screen' ), 1 );

		// CSV generation
		add_action( 'admin_init', array( __CLASS__, 'generate_global_csv' ), 1 );
		add_action( 'admin_init', array( __CLASS__, 'generate_campaign_csv' ), 2 );

		// AJAX Action
		add_action( 'wp_ajax_' . 'bea_sender_launch_cron', array( __CLASS__, 'a_launchCron' ) );
		add_action( 'wp_ajax_' . 'bea_sender_get_check_file', array( __CLASS__, 'a_getCheckFile' ) );
	}

	/**
	 * Add options settings menu and manage redirect tools
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function admin_menu() {
		$hook = add_management_page( __( 'BEA Send', 'bea_sender' ), __( 'BEA Send', 'bea_sender' ), 'manage_options', 'bea_sender', array( &$this, 'pageManage' ) );

		add_action( 'load-' . $hook, array( __CLASS__, 'admin_enqueue_scripts' ) );
	}

	/**
	 * Instanciate custom WP List table after current_screen defined
	 */
	function init_table() {
		if ( ! isset( $_GET['c_id'] ) ) {
			$this->ListTable = new Bea_Sender_Admin_Table();
		} else {
			$this->ListTableSingle = new Bea_Sender_Admin_Table_Single();
		}
	}

	/**
	 * Load JavaScript in admin
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function admin_enqueue_scripts() {
		$suffix = defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ? '' : '.min';

		// Register script
		wp_enqueue_script( 'export-global-csv', BEA_SENDER_URL . '/assets/js/admin-global-export' . $suffix . '.js', array( 'jquery' ), BEA_SENDER_VER, true );
		wp_localize_script('export-global-csv', 'export_csv',array(
				'log_file'     => __( 'Log file creation', 'bea_sender' ),
				'request_file' => __( 'Request to create the file.', 'bea_sender' ),
				'check_file'   => __( 'Checking if the lock file exists', 'bea_sender' ),
			));

		// enqueue the admin styles and scripts
		wp_enqueue_style( 'bea-send_table', BEA_SENDER_URL . '/assets/css/admin' . $suffix . '.css', array(), BEA_SENDER_VER );
	}

	/**
	 * Add Options Screen in Manage page
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function add_option_screen() {
		$option = 'per_page';
		$args   = array(
			'label'   => __( 'Campaigns', 'bea_sender' ),
			'default' => BEA_SENDER_PPP,
			'option'  => 'bea_s_per_page'
		);
		add_screen_option( $option, $args );
	}

	/**
	 * This method return the value of options
	 *
	 * @param $status
	 * @param $option
	 * @param $value
	 *
	 * @return integer $value or string $value
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function set_options( $status, $option, $value ) {
		// Get Post Per Page value in Option Screen
		if ( 'bea_s_per_page' == $option ) {
			return (int) $value;
		}

		return $value;
	}

	/**
	 * Display table with redirect in manage page
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public function pageManage() {
		if ( isset( $_GET['message-code'] ) ) {
			$_GET['message-code'] = (int) $_GET['message-code'];
			if ( $_GET['message-code'] == 0 ) {
				add_settings_error( 'bea_sender', 'settings_updated', __( 'Internal error', 'bea_sender' ), 'error' );
			} elseif ( $_GET['message-code'] == 1 ) {
				add_settings_error( 'bea_sender', 'settings_updated', __( 'No results', 'bea_sender' ), 'updated' );
			} elseif ( $_GET['message-code'] == 2 ) {
				$result = isset( $_GET['message-value'] ) ? $_GET['message-value'] : 0;
				add_settings_error( 'bea_sender', 'settings_updated', sprintf( __( '%d lines deleted', 'bea_sender' ), $result ), 'updated' );
			}
		}

		$export_options = get_option( BEA_SENDER_EXPORT_OPTION_NAME, array() );

		// Include right file
		$file = ! isset( $_GET['c_id'] ) ? BEA_SENDER_DIR . '/templates/admin-table.php' : BEA_SENDER_DIR . '/templates/admin-campaign-single.php';

		// Check the file
		if ( ! is_file( $file ) ) {
			return false;
		}

		include( $file );

		return true;
	}

	/**
	 * schedule event to run cron for generate the CSV
	 *
	 * @return bool
	 * @author Zainoudine Soulé
	 */
	public static function generate_global_csv() {
		if ( ! isset( $_GET['bea_s-export'] ) ) {
			return false;
		}

		check_admin_referer( 'bea-sender-export' );

		wp_schedule_single_event( time(), 'generate_global_csv_event' );

		return true;
	}

	/**
	 * Export bea_s_receivers table in CSV for single campaign
	 *
	 *
	 * @author Salah Khouildi
	 */
	private static function generate_csv( $campaign_id = 0 ) {
		$list = Bea_Sender_Export::export_campaign( $campaign_id );
		header( "Pragma: public" );
		header( "Expires: 0" );
		header( "Cache-Control: private" );
		header( "Content-type: text/csv" );

		$file_name = "bea-send-" . date( 'd-m-y' ) . ".csv";
		if ( isset( $campaign_id ) && (int) $campaign_id > 0 ) {
			$file_name = "bea-send-" . date( 'd-m-y' ) . "-campaign-" . $_GET['c_id'] . ".csv";
		}
		header( "Content-Disposition: attachment; filename=" . $file_name );
		header( "Accept-Ranges: bytes" );

		$outstream = fopen( "php://output", 'w' );
		//Put header titles
		fputcsv( $outstream, array_map( 'utf8_decode', Bea_Sender_Export::get_Header_titles() ), ';' );
		// Put lines in csv file
		foreach ( $list as $fields ) {
			fputcsv( $outstream, array_map( 'utf8_decode', $fields ), ';' );
		}

		fclose( $outstream );
		die();
	}

	/**
	 * Export bea_s_receivers of the campaign table in CSV
	 *
	 *
	 * @author Salah Khouildi
	 */
	public static function generate_campaign_csv() {

		if ( ! isset( $_GET['action'] ) || $_GET['bea_export'] || ! isset( $_GET['nonce'] ) || ! isset( $_GET['c_id'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_GET['nonce'], 'bea-sender-export-' . $_GET['c_id'] ) ) {
			wp_die( __( 'Are you sure you want to do this ?', 'bea_sender' ) );
		}

		// Generate the csv file
		self::generate_csv( $_GET['c_id'] );
	}

	/**
	 * Run cron from Ajax request
	 *
	 *
	 * @author Zainoudine Soulé
	 */
	public function a_launchCron() {
		$nonce  = isset( $_POST['nonce'] ) ? $_POST['nonce'] : false;

		if ( ! wp_verify_nonce( $nonce, 'bea-sender-export' ) ) {
			wp_send_json( array( 'status' => 'error', 'message' => __( 'Cheater', 'bea_sender' ) ) );
		}

		wp_schedule_single_event( time(), 'generate_global_csv_event', array( 0 ) );
		wp_send_json( array( 'status' => 'success', 'message' => __( 'File creation requested', 'bea_sender' ) ) );
	}

	/**
	 * Check file from Ajax request
	 *
	 *
	 * @author Zainoudine Soulé
	 */
	public function a_getCheckFile() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : false;

		if ( ! wp_verify_nonce( $nonce, 'bea-sender-export' ) ) {
			wp_send_json( array( 'status' => 'error', 'message' => __( 'Cheater', 'bea_sender' ) ) );
		}

		$scheduled = Bea_Sender_Cron::wp_get_schedule( 'generate_global_csv_event', array( 0 ) );

		if ( false === Bea_Sender_Cron::is_locked() && false === $scheduled ) {
			wp_send_json( array( 'status' => 'error', 'message' => __( 'File not found !', 'bea_sender' ) ) );
		}

		$upload_dir = wp_upload_dir();
		$file_name  = 'bea-send.csv';
		$csv_file   = $upload_dir['basedir'] . '/' . $file_name;

		if ( is_file( $csv_file ) ) {
			// Get settings
			$options = get_option( BEA_SENDER_EXPORT_OPTION_NAME, array() );

			// Replace or append the data for the new file
			$options['global'] = array(
				'date' => date_i18n( 'Y-m-d H:i:s' ),
				'url'  => $upload_dir['baseurl'] . '/' . $file_name
			);

			// Save new option
			update_option( BEA_SENDER_EXPORT_OPTION_NAME, $options );
			wp_send_json( array( 'status' => 'success', 'finished' => true,  'message' => sprintf( __( 'You can <a href="%s">download</a> your file.', 'bea_sender' ), $options['global']['url'] ) ) );
		} else {
			wp_send_json( array( 'status' => 'success', 'finished' => false, 'message' => sprintf( __( 'File currently being created. last verification : %s', 'bea_sender' ), date_i18n( 'd/m/Y  H:i:s' ) ) ) );
		}

	}

}