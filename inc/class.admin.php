<?php

class Bea_Sender_Admin {
	private $ListTable = null;
	private $ListTableSingle = null;

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

		// Post Actions
		add_action( 'admin_post_'.'bea_sender_purge', array( __CLASS__, 'p_purge' ) );
	}

	/**
	 * Add options settings menu and manage redirect tools
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function admin_menu() {
		$title = ! isset( $_GET['c_id'] ) ? __( 'BEA Send', 'bea_sender' ) : __( 'BEA Send - Campaign', 'bea_sender' ) ;
		$hook = add_management_page( $title, __( 'BEA Send', 'bea_sender' ), 'manage_options', 'bea_sender', array( &$this, 'pageManage' ) );

		add_action( 'load-' . $hook, array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( "admin_footer-" . $hook, array( __CLASS__, 'admin_footer' ) );
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
		global $wp_locale;
		$suffix = defined( 'SCRIPT_DEBUG' ) && true === SCRIPT_DEBUG ? '' : '.min';

		// Register script
		wp_enqueue_script( 'export', BEA_SENDER_URL . '/assets/js/app' . $suffix . '.js', array( 'jquery', 'backbone', 'jquery-ui-datepicker' ), BEA_SENDER_VER, true );

		// Localization
		wp_localize_script( 'export', 'bea_sender_vars',array(
			'month' => array_values( array_map( 'ucwords', $wp_locale->month ) ),
			'month_abbrev' => array_values( array_map( 'ucwords', $wp_locale->month_abbrev ) ),
			'weekday' => array_values( array_map( 'ucwords', $wp_locale->weekday ) ),
			'weekday_initial' => array_values( array_map( 'ucwords', $wp_locale->weekday_initial ) ),
			'weekday_abbrev' => array_values( array_map( 'ucwords', $wp_locale->weekday_abbrev ) ),
			'start_of_week' => get_option( 'start_of_week' ),
			'date_format' => self::dateformat_PHP_to_jQueryUI( get_option( 'date_format' ) ),
		) );

		wp_register_style( 'bea-sender-jquery-ui-style', '//code.jquery.com/ui/1.11.3/themes/ui-lightness/jquery-ui.css' );
		// enqueue the admin styles and scripts
		wp_enqueue_style( 'bea-send_table', BEA_SENDER_URL . '/assets/css/admin' . $suffix . '.css', array( 'bea-sender-jquery-ui-style' ), BEA_SENDER_VER );
	}

	/**
	 * Matches each symbol of PHP date format standard
	 * with jQuery equivalent codeword
	 *
	 * @param $php_format
	 *
	 * @return string
	 * @author Tristan Jahier
	 */
	private static function dateformat_PHP_to_jQueryUI( $php_format )  {
		$SYMBOLS_MATCHING = array(
			// Day
			'd' => 'dd',
			'D' => 'D',
			'j' => 'd',
			'l' => 'DD',
			'N' => '',
			'S' => '',
			'w' => '',
			'z' => 'o',
			// Week
			'W' => '',
			// Month
			'F' => 'MM',
			'm' => 'mm',
			'M' => 'M',
			'n' => 'm',
			't' => '',
			// Year
			'L' => '',
			'o' => '',
			'Y' => 'yy',
			'y' => 'y',
			// Time
			'a' => '',
			'A' => '',
			'B' => '',
			'g' => '',
			'G' => '',
			'h' => '',
			'H' => '',
			'i' => '',
			's' => '',
			'u' => ''
		);
		$jqueryui_format = "";
		$escaping = false;
		for($i = 0; $i < strlen($php_format); $i++)
		{
			$char = $php_format[$i];
			if($char === '\\') // PHP date format escaping character
			{
				$i++;
				if($escaping) $jqueryui_format .= $php_format[$i];
				else $jqueryui_format .= '\'' . $php_format[$i];
				$escaping = true;
			}
			else
			{
				if($escaping) { $jqueryui_format .= "'"; $escaping = false; }
				if(isset($SYMBOLS_MATCHING[$char]))
					$jqueryui_format .= $SYMBOLS_MATCHING[$char];
				else
					$jqueryui_format .= $char;
			}
		}
		return $jqueryui_format;
	}

	/**
	 *
	 *
	 * @author Nicolas Juen
	 */
	public static function admin_footer() {
		$file = BEA_SENDER_DIR.'/templates/admin-js.tpl';
		if( !is_file( $file ) ) {
			return;
		}

		include_once( $file );
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
			$message_code = (int) $_GET['message-code'];

			switch( $message_code ) {
				case 0 :
					add_settings_error( 'bea_sender', 'settings_updated', __( 'Internal error', 'bea_sender' ), 'error' );
				break;
				case 1 :
					add_settings_error( 'bea_sender', 'settings_updated', __( 'No results', 'bea_sender' ), 'updated' );
				break;
				case 2 :
					$result = isset( $_GET['message-value'] ) ? $_GET['message-value'] : 0;
					add_settings_error( 'bea_sender', 'settings_updated', sprintf( _n( '%d line deleted', '%d lines deleted', $result , 'bea_sender' ), $result ), 'updated' );
				break;
				case 3 :
					$result = isset( $_GET['message-value'] ) ? $_GET['message-value'] : 0;
					add_settings_error( 'bea_sender', 'settings_updated', sprintf( _n( '%d line purged', '%d lines purged', $result, 'bea_sender' ), $result ), 'updated' );
				break;
			}
		}

		$export_options = get_option( BEA_SENDER_EXPORT_OPTION_NAME, array() );

		// Include right file
		$file = ! isset( $_GET['c_id'] ) ? BEA_SENDER_DIR . '/templates/admin-table.php' : BEA_SENDER_DIR . '/templates/admin-campaign-single.php';

		// Check the file
		if ( ! is_file( $file ) ) {
			return;
		}

		include( $file );

		return;
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
	 * run cron from Ajax request
	 * @author Zainoudine Soulé
	 */
	public function a_launchCron() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : false;
		$type = isset( $_POST['type'] ) ? $_POST['type'] : false;

		switch( $type ) {
			case 'bounces':
				$nonce_name = 'bea-sender-export-bounces';
				$scheduled_event = 'generate_global_bounces_csv_event';
			break;
			case 'global':
			default:
				$nonce_name = 'bea-sender-export-global';
				$scheduled_event = 'generate_global_csv_event';
			break;
		}

		if ( ! wp_verify_nonce( $nonce, $nonce_name ) ) {
			wp_send_json( array( 'status' => 'error', 'message' => 'Cheater' ) );
		}

		wp_schedule_single_event( time(), $scheduled_event, array( $type ) );
		//wp_remote_get( home_url() );
		wp_send_json( array( 'status' => 'success', 'message' => __( 'File creation requested', 'bea_sender' ) ) );
	}

	/**
	 * Check file from Ajax request
	 * @author Zainoudine Soulé
	 */
	public function a_getCheckFile() {
		$nonce = isset( $_POST['nonce'] ) ? $_POST['nonce'] : false;
		$type = isset( $_POST['type'] ) ? $_POST['type'] : false;

		switch( $type ) {
			case 'bounces':
				$nonce_name = 'bea-sender-export-bounces';
				$scheduled_event = 'generate_global_bounces_csv_event';
				break;
			case 'global':
			default:
				$nonce_name = 'bea-sender-export-global';
				$scheduled_event = 'generate_global_csv_event';
				break;
		}
		if ( ! wp_verify_nonce( $nonce, $nonce_name ) ) {
			wp_send_json( array( 'status' => 'error', 'message' => __( 'Cheater', 'bea_sender' ) ) );
		}

		$scheduled = Bea_Sender_Cron::wp_get_schedule( $scheduled_event, array( $type ) );

		if ( false === Bea_Sender_Cron::is_locked( $type ) && false === $scheduled ) {
			wp_send_json( array( 'status' => 'error', 'message' => __( 'File not found !', 'bea_sender' ) ) );
		}

		$upload_dir = wp_upload_dir();
		$file_name  = 'bea-sender-'.$type.'.csv';
		$csv_file   = $upload_dir['basedir'] . '/' . $file_name;

		if ( is_file( $csv_file ) ) {
			// Get settings
			$options = get_option( BEA_SENDER_EXPORT_OPTION_NAME, array() );

			// Replace or append the data for the new file
			$options[$type] = array(
				'date' => date_i18n( 'Y-m-d H:i:s' ),
				'url'  => $upload_dir['baseurl'] . '/' . $file_name
			);

			// Save new option
			update_option( BEA_SENDER_EXPORT_OPTION_NAME, $options );
			wp_send_json( array( 'status' => 'success', 'finished' => true, 'message' => __( sprintf( 'You can <a href="%s">download</a> your file.', $options[$type]['url'] ), 'bea_sender' ) ) );
		} else {
			wp_send_json( array( 'status' => 'success', 'finished' => false, 'message' => __( sprintf( 'File currently being created. last verification : %s' , date_i18n( 'd/m/Y  H:i:s' ) ), 'bea_sender' ) ) );
		}

	}

	/**
	 * Allow to purge all the bounces
	 *
	 * @author Nicolas Juen
	 */
	public static function p_purge() {
		// Check nonce
		check_admin_referer( 'bea-sender-purge' );

		// Check permissions
		if( !current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Cheatin&#8217; uh?' ) );
		}

		$from = isset( $_POST['date_from'] ) && strtotime( $_POST['date_from'] ) !== false ? $_POST['date_from'] : false ;
		$to = isset( $_POST['date_to'] ) && strtotime( $_POST['date_to'] ) !== false ? $_POST['date_to'] : false ;

		$purged = Bea_Sender_Receivers::purge_bounced( array( 'from' => $from, 'to' => $to ) );

		wp_safe_redirect( add_query_arg(
			array(
				'page' => 'bea_sender',
				'message-code' => 3,
				'message-value' => $purged
			),
			admin_url( 'tools.php' )
		) );
		die();
	}

}