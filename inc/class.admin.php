<?php
class Bea_Sender_Admin {
	private $ListTable = null;
	private $ListTableSingle = null;
	private $campaign_table = null;
	
	/** 
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		// Add the menu page
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		
		// Init the WP_List_Table
		add_action( 'load-tools_page_'.'bea_sender', array( &$this, 'init_table' ), 2 );
		
		// Screen options
		add_filter( 'set-screen-option', array( __CLASS__, 'set_options' ), 1, 3 );
		add_action( 'load-tools_page_'.'bea_sender', array( __CLASS__, 'add_option_screen' ), 1 );
		
		// CSV generation
		add_action( 'admin_init', array( __CLASS__, 'generate_csv' ) );
	}
	
	/**
	 * Add options settings menu and manage redirect tools
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function admin_menu() {
		$hook = add_management_page( __( 'BEA Send','bea_sender' ), __( 'BEA Send','bea_sender' ), 'manage_options', 'bea_sender', array( &$this, 'pageManage' ) );
		
		add_action( 'load-'.$hook, array( __CLASS__, 'admin_enqueue_scripts' ) );
	}
	
	/**
	 * Instanciate custom WP List table after current_screen defined
	 */
	function init_table() {
		if( !isset( $_GET['c_id'] ) ) {
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
		// enqueue tghe admin styles
		wp_enqueue_style( 'bea-send_table', BEA_SENDER_URL.'/assets/css/admin.css' );
	}
	
	/**
	 * Add Options Screen in Manage page
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function add_option_screen() {
		$option = 'per_page';
			$args = array(
				'label' => __( 'Campaigns', 'bea_sender' ),
				'default' => BEA_SENDER_PPP,
				'option' => 'bea_s_per_page'
			);
		add_screen_option( $option, $args );
	}

	/**
	 * This method return the value of options
	 *
	 * @return integer $value or string $value
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function set_options( $status, $option, $value ) {
		// Get Post Per Page value in Option Screen
		if ( 'bea_s_per_page' == $option ) {
			return (int)$value;
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
				add_settings_error( 'bea_sender', 'settings_updated', __( 'Internal error', 'bea_sender'), 'error' );
			} elseif ( $_GET['message-code'] == 1 ) {
				add_settings_error( 'bea_sender', 'settings_updated', __( 'No results', 'bea_sender'), 'updated' );
			} elseif ( $_GET['message-code'] == 2 ) {
				$result = isset($_GET['message-value']) ? $_GET['message-value'] : 0;
				add_settings_error( 'bea_sender', 'settings_updated', sprintf( __( '%d lines deleted', 'bea_sender' ), $result ), 'updated' );
			}
		}
		
		// Include right file
		$file = !isset( $_GET['c_id'] ) ? BEA_SENDER_DIR.'/templates/admin-table.php' : BEA_SENDER_DIR.'/templates/admin-campaign-single.php';

		// Check the file
		if( !is_file( $file ) ) {
			return false;
		} else {
			include( $file );
		}
		return true;
	}

	/**
	 * Export bea_s_receivers table in CSV
	 *
	 *
	 * @author Salah Khouildi
	 */
	public static function generate_csv( ) {

		if( !isset( $_GET['bea_s-export'] ) ) {
			return false;
		}
		
		check_admin_referer( 'bea-sender-export' );
		
		global $wpdb;

		$header_titles = apply_filters( 'bea_sender_csv_headers', array(
			'Id',
			'Email',
			'Current status',
			'Bounce cat',
			'Bounce type',
			'Bounce no'
		) );

		$contacts = $wpdb->get_results( "SELECT * FROM $wpdb->bea_s_receivers as r LEFT JOIN $wpdb->bea_s_re_ca AS re ON r.id = re.id_receiver" );
		foreach( $contacts as $contact ) {
			$list[] = apply_filters( 'bea_sender_csv_item', array(
				$contact->id,
				$contact->email,
				$contact->current_status,
				$contact->bounce_cat,
				$contact->bounce_type,
				$contact->bounce_no
			), $contact );
		}
		
		$list = apply_filters( 'bea_sender_csv_list', $list, $contacts );
		
		header( "Pragma: public" );
		header( "Expires: 0" );
		header( "Cache-Control: private" );
		header( "Content-type: text/csv" );
		header( "Content-Disposition: attachment; filename=bea-send-".date( 'd-m-y' ).".csv" );
		header( "Accept-Ranges: bytes" );

		$outstream = fopen( "php://output", 'w' );
		//Put header titles
		fputcsv( $outstream, array_map( 'utf8_decode', $header_titles ), ';' );
		// Put lines in csv file
		foreach( $list as $fields ) {
			fputcsv( $outstream, array_map( 'utf8_decode', $fields ), ';' );
		}

		fclose( $outstream );
		die( );
	}
}