<?php

/*
 Plugin Name: BeApi - Sender
 Description: Register email campaigns and send them trough a CRON
 Author: BeApi
 Domain Path: /languages/
 Text Domain: bea_sender
 Version: 1.2.1
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
define( 'BEA_SENDER_VER', '1.2.1' );
define( 'BEA_SENDER_PPP', '10' );
define( 'BEA_SENDER_DEFAULT_COUNTER', 100 );
define( 'BEA_SENDER_OPTION_NAME', 'bea_s-main' );

// Utils
require (BEA_SENDER_DIR.'/inc/utils/'.'class.email.php');
require (BEA_SENDER_DIR.'/inc/utils/'.'class.campaign.php');
require (BEA_SENDER_DIR.'/inc/utils/'.'class.content.php');
require (BEA_SENDER_DIR.'/inc/utils/'.'class.attachment.php');
require (BEA_SENDER_DIR.'/inc/utils/'.'class.receiver.php');
require (BEA_SENDER_DIR.'/inc/utils/'.'class.sender.php');
require (BEA_SENDER_DIR.'/inc/utils/'.'class.bounce.email.php');

// Admin
if( is_admin( ) ) {
	// Admin basic
	require (BEA_SENDER_DIR.'/inc/class.admin.php');
	
	if( !class_exists( 'WP_List_Table' ) ) {
		require (ABSPATH.'/wp-admin/includes/class-wp-list-table.php');
	}
	require (BEA_SENDER_DIR.'/inc/utils/'.'class.admin.table.php');
	require (BEA_SENDER_DIR.'/inc/utils/'.'class.admin.table.single.php');
	require (BEA_SENDER_DIR.'/inc/utils/'.'class.admin.bounce.tools.php');
}

// Inc
require (BEA_SENDER_DIR.'/inc/class.client.php');

// Libs
require (BEA_SENDER_DIR.'/inc/libs/wordpress-settings-api/class.settings-api.php');
require (BEA_SENDER_DIR.'/inc/libs/php-bounce/class.phpmailer-bmh.php');

// Create tables on activation
register_activation_hook( __FILE__, array( 'Bea_Sender_Client', 'activation' ) );
register_uninstall_hook( __FILE__, array( 'Bea_Sender_Client', 'uninstall' ) );

add_action( 'plugins_loaded', 'Bea_sender_init' );

function Bea_sender_init( ) {
	global $bea_sender, $bea_send_counter;

	$bea_send_counter = apply_filters( 'bea_send_counter', BEA_SENDER_DEFAULT_COUNTER );
	$bea_sender['client'] = new Bea_Sender_Client( );

	if( is_admin( ) ) {
		$bea_sender['admin'] = new Bea_Sender_Admin( );
		$bea_sender['admin_bounce_tools'] = new BEA_Admin_Settings_Main( );
	}

	add_action( 'bea_sender_register_send', array( $bea_sender['client'], 'registerCampaign' ), 99, 4 );
}
