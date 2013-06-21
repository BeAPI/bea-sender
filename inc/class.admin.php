<?php
class Bea_Sender_Admin {
	private $ListTable = null;
	private $campaign_table = null;
	
	/** 
	 * Constructor
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function __construct() {
		add_action( 'admin_menu', array( &$this, 'admin_menu' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_enqueue_scripts' ) );
		add_action( 'load-tools_page_'.'bea_sender', array( __CLASS__, 'addOptionScreen' ) );
		add_filter( 'set-screen-option', array( __CLASS__, 'setOptions' ), 1, 3 );
		add_action( 'admin_head', array( __CLASS__, 'admin_head' ) );
		add_action( 'load-tools_page_bea_sender', array( &$this, 'init_table' ) );
	}
	
	/**
	 * Add options settings menu and manage redirect tools
	 *
	 * @return void
	 * @author Amaury Balmer
	 */
	public function admin_menu() {
		add_management_page( __( 'BEA Send','bea_sender' ), __( 'BEA Send','bea_sender' ), 'manage_options', 'bea_sender', array( &$this, 'pageManage' ) );
	}
	
	/**
	 * Instanciate custom WP List table after current_screen defined
	 */
	function init_table() {
		$this->ListTable = new Bea_Sender_Admin_Table();
	}
	
	/**
	 * Instanciate custom WP List table after current_screen defined
	 */
	function init_campaign_table() {
		//$this->campaign_table = new Bea_Sender_Admin_Campaign_Table();
	}
	
	/**
	 * Load JavaScript in admin
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function admin_enqueue_scripts( $hook ) {
		if( $hook == 'settings_page_bea_sender' ) {
			
			// Enqueue main script
			wp_enqueue_script ( 'admin-populate', BEA_SENDER_URL.'/ressources/js/admin-populate.js', array( 'jquery' ), BEA_SENDER_VER, true );
			wp_localize_script( 'admin-populate', 'umL10n ', array(
				'confirm' => __( 'Are you sure you want to flush the table?', 'bea_sender' ),
				'ppp' => (int) BEA_SENDER_PPP,
				'end_processus_message' => __( 'End of process', 'bea_sender' ),
				'start_processus_message' => __( 'Start of process', 'bea_sender' ),
				'ppp_valid_message' => __( 'You need PPP valid to make working this script', 'bea_sender' ),
				'processing_message' => __( 'Processing of ', 'bea_sender' )
			) );
			
		}
	}
	
	/**
	 * Add Options Screen in Manage page
	 *
	 * @return void
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function addOptionScreen() {
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
	public static function setOptions( $status, $option, $value ) {
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
		
		if( !isset( $_GET['c_id'] ) ) {
			$file = BEA_SENDER_DIR.'/templates/admin-table.php';
		} else {
			$file = BEA_SENDER_DIR.'/templates/admin-campaign-single.php';
		}
		if( !is_file( $file ) ) {
			return false;
		} else {
			include( $file );
		}
		return true;
	}
	/**
	 *  This method manage css for table
	 *
	 * @return string <style css>
	 * @author Amaury Balmer, Alexandre Sadowski
	 */
	public static function admin_head() {
		$page = ( isset($_GET['page'] ) ) ? esc_attr( $_GET['page'] ) : false;
		if( 'bea_sender' != $page )
			return false;
		 
		echo '<style type="text/css">';
			echo '.wp-list-table .column-id { width: 10%; }';
			echo '.wp-list-table .column-status { width: 5%; }';
			echo '.wp-list-table .column-post_id { width: 30%; }';
			echo '.wp-list-table .column-path { width: 55%; }';
		echo '</style>';
		return true;
	}
}