<?php
/*
Plugin Name: BeApi - Sender
Description: Register email campaigns and send them trough a CRON
Author: BeApi
Version: 1.0
*/

// Database declarations
global $wpdb;
$wpdb->bea_s_campaigns 	= $wpdb->prefix . 'bea_s_campaigns';
$wpdb->bea_s_receivers 	= $wpdb->prefix . 'bea_s_receivers';
$wpdb->bea_s_re_ca 		= $wpdb->prefix . 'bea_s_re_ca';
$wpdb->bea_s_contents 	= $wpdb->prefix . 'bea_s_contents';

// Add tables to the index of tables for WordPress
$wpdb->tables[] = 'bea_s_campaigns';
$wpdb->tables[] = 'bea_s_receivers';
$wpdb->tables[] = 'bea_s_re_ca';
$wpdb->tables[] = 'bea_s_contents';

define( 'BEA_SENDER_URL', plugins_url( '', __FILE__ ) );
define( 'BEA_SENDER_DIR', dirname( __FILE__ ) );
define( 'BEA_SENDER_VER', '1.0' );
define( 'BEA_SENDER_PPP', '10' );
define( 'BEA_SENDER_DEFAULT_COUNTER', 6 );

// Utils
require( BEA_SENDER_DIR.'/inc/utils/'.'class.email.php' );
require( BEA_SENDER_DIR.'/inc/utils/'.'class.campaign.php' );
require( BEA_SENDER_DIR.'/inc/utils/'.'class.content.php' );
require( BEA_SENDER_DIR.'/inc/utils/'.'class.receiver.php' );
require( BEA_SENDER_DIR.'/inc/utils/'.'class.sender.php' );
if ( is_admin() && !class_exists( 'WP_List_Table' ) ) {
	require ( ABSPATH . '/wp-admin/includes/class-wp-list-table.php' );
	require( BEA_SENDER_DIR.'/inc/utils/'.'class.admin.table.php' );
}

// Inc
require( BEA_SENDER_DIR.'/inc/'.'class.client.php' );
require( BEA_SENDER_DIR.'/inc/'.'class.admin.php' );

// Create tables on activation
register_activation_hook( __FILE__, array( 'Bea_Sender_Client' ,'activation' ) );

add_action( 'plugins_loaded', 'Bea_sender_init' );
function Bea_sender_init() {
	global $bea_sender,$bea_send_counter;
	
	$bea_send_counter = apply_filters( 'bea_send_counter', BEA_SENDER_DEFAULT_COUNTER );
	$bea_sender['client'] = new  Bea_Sender_Client();
	
	if( is_admin() ) {
		$bea_sender['admin'] = new  Bea_Sender_Admin();
	}

	add_action( 'bea_sender_register_send', array( $bea_sender['client'], 'registerCampaign' ), 99, 4 );
}