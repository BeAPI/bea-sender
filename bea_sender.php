<?php
/*
 Plugin Name: BeApi - Sender
 Description: Register email campaigns and send them trough a CRON
 Author: BeApi
 Domain Path: /languages/
 Text Domain: bea_sender
 Version: 1.3.0
 */

// Database declarations
global $wpdb;
$wpdb->bea_s_campaigns = $wpdb->prefix.'bea_s_campaigns';
$wpdb->bea_s_receivers = $wpdb->prefix.'bea_s_receivers';
$wpdb->bea_s_re_ca = $wpdb->prefix.'bea_s_re_ca';
$wpdb->bea_s_contents = $wpdb->prefix.'bea_s_contents';
$wpdb->bea_s_attachments = $wpdb->prefix.'bea_s_attachments';

// Add tables to the index of tables for WordPress
$wpdb->tables[] = 'bea_s_campaigns';
$wpdb->tables[] = 'bea_s_receivers';
$wpdb->tables[] = 'bea_s_re_ca';
$wpdb->tables[] = 'bea_s_contents';

define('BEA_SENDER_URL', plugin_dir_url ( __FILE__ ));
define('BEA_SENDER_DIR', plugin_dir_path( __FILE__ ));
define( 'BEA_SENDER_VER', '1.3.0' );
define( 'BEA_SENDER_PPP', '10' );
define( 'BEA_SENDER_DEFAULT_COUNTER', 100 );
define( 'BEA_SENDER_OPTION_NAME', 'bea_s-main' );
define( 'BEA_SENDER_EXPORT_OPTION_NAME', 'bea_s-export' );

// Function for easy load files
function _bea_sender_load_files($dir, $files, $prefix = '') {
	foreach ($files as $file) {
		if ( is_file($dir . $prefix . $file . ".php") ) {
			require_once($dir . $prefix . $file . ".php");
		}
	}
}

// Utils
_bea_sender_load_files( BEA_SENDER_DIR . 'inc/utils/', array(
	'email',
	'campaign',
	'content',
	'attachment',
	'receiver',
	'sender',
	'bounce.email',
	'export',
	'receivers'
), 'class.' );

// Admin
if( is_admin( ) ) {

	if( !class_exists( 'WP_List_Table' ) ) {
		require (ABSPATH.'/wp-admin/includes/class-wp-list-table.php');
	}

	_bea_sender_load_files( BEA_SENDER_DIR . 'inc/', array( 'admin' ), 'class.' );
	_bea_sender_load_files( BEA_SENDER_DIR . 'inc/utils/', array(
		'admin.table',
		'admin.table.single',
		'admin.bounce.tools'
	), 'class.' );

}

// Inc
_bea_sender_load_files( BEA_SENDER_DIR . 'inc/', array(
	'client',
	'cron',
), 'class.' );

// Libs
_bea_sender_load_files( BEA_SENDER_DIR . 'inc/libs/wordpress-settings-api/', array(
	'settings-api',
), 'class.' );

_bea_sender_load_files( BEA_SENDER_DIR . 'inc/libs/php-bounce/', array(
	'phpmailer-bmh',
), 'class.' );

_bea_sender_load_files( BEA_SENDER_DIR . 'inc/libs/', array(
	'log',
), 'class-' );



// Create tables on activation
register_activation_hook( __FILE__, array( 'Bea_Sender_Client', 'activation' ) );
register_uninstall_hook( __FILE__, array( 'Bea_Sender_Client', 'uninstall' ) );

add_action( 'plugins_loaded', 'Bea_sender_init' );

function Bea_sender_init( ) {
	global $bea_sender, $bea_send_counter;

	$bea_send_counter = apply_filters( 'bea_send_counter', BEA_SENDER_DEFAULT_COUNTER );
	$bea_sender['client'] = new Bea_Sender_Client( );
	new Bea_Sender_Cron();

	if( is_admin( ) ) {
		$bea_sender['admin'] = new Bea_Sender_Admin( );
		$bea_sender['admin_bounce_tools'] = new BEA_Admin_Settings_Main( );
	}

	add_action( 'bea_sender_register_send', array( $bea_sender['client'], 'registerCampaign' ), 99, 4 );
}
