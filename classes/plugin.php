<?php
namespace BEA\Sender;

/**
 * The purpose of the plugin class is to have the methods for
 *  - activation actions
 *  - deactivation actions
 *  - uninstall actions
 *
 * Class Plugin
 * @package BEA\Sender
 */
class Plugin {
	public static function activate() {
		/* @var $wpdb \wpdb */
		global $wpdb;

		// Charset
		if( !empty( $wpdb->charset ) ) {
			$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";
		}
		if( !empty( $wpdb->collate ) ) {
			$charset_collate .= " COLLATE $wpdb->collate";
		}

		// Add one library admin function for next function
		require_once (ABSPATH.'wp-admin/includes/upgrade.php');

		// Campaign Table
		maybe_create_table( $wpdb->bea_s_campaigns, "CREATE TABLE ".$wpdb->bea_s_campaigns." (
			`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`add_date` datetime NOT NULL,
			`scheduled_from` datetime NOT NULL,
			`current_status` varchar(10) NOT NULL,
			`from_name` varchar(255) NOT NULL,
			`from` varchar(255) NOT NULL,
			`subject` text NOT NULL
		) $charset_collate;" );

		add_clean_index( $wpdb->bea_s_campaigns, 'id' );
		add_clean_index( $wpdb->bea_s_campaigns, 'current_status' );

		// Receiver Table
		maybe_create_table( $wpdb->bea_s_receivers, "CREATE TABLE ".$wpdb->bea_s_receivers." (
			`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`email` varchar(255) NOT NULL,
			`current_status` varchar(10) NOT NULL,
			`bounce_cat` varchar(20) NOT NULL,
			`bounce_type` varchar(20) NOT NULL,
			`bounce_no` varchar(10) NOT NULL
		) $charset_collate;" );

		maybe_add_column( $wpdb->bea_s_receivers, 'bounce_cat', "ALTER TABLE $wpdb->bea_s_receivers ADD bounce_cat char(20)" );
		maybe_add_column( $wpdb->bea_s_receivers, 'bounce_type', "ALTER TABLE $wpdb->bea_s_receivers ADD bounce_type char(20)" );
		maybe_add_column( $wpdb->bea_s_receivers, 'bounce_no', "ALTER TABLE $wpdb->bea_s_receivers ADD bounce_no char(10)" );

		add_clean_index( $wpdb->bea_s_receivers, 'email' );
		add_clean_index( $wpdb->bea_s_receivers, 'current_status' );

		// Recesiver/campaign link table
		maybe_create_table( $wpdb->bea_s_re_ca, "CREATE TABLE ".$wpdb->bea_s_re_ca." (
			`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`id_campaign` int NOT NULL,
			`id_receiver` int NOT NULL,
			`id_content` int NOT NULL,
			`current_status` varchar(10) NOT NULL,
			`response` varchar(10) NOT NULL
		) $charset_collate;" );
		add_clean_index( $wpdb->bea_s_re_ca, 'current_status' );
		add_clean_index( $wpdb->bea_s_re_ca, 'id_campaign' );
		add_clean_index( $wpdb->bea_s_re_ca, 'id_receiver' );

		// Content Table
		maybe_create_table( $wpdb->bea_s_contents, "CREATE TABLE ".$wpdb->bea_s_contents." (
			`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`html` longtext NOT NULL,
			`text` longtext NOT NULL
		) $charset_collate;" );
		add_clean_index( $wpdb->bea_s_contents, 'id' );

		// Attachment Table
		maybe_create_table( $wpdb->bea_s_attachments, "CREATE TABLE ".$wpdb->bea_s_attachments." (
			`id` int NOT NULL AUTO_INCREMENT PRIMARY KEY,
			`campaign_id` int NOT NULL,
			`path` longtext NOT NULL
		) $charset_collate;" );
		add_clean_index( $wpdb->bea_s_attachments, 'id' );
		add_clean_index( $wpdb->bea_s_attachments, 'campaign_id' );
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( 'generate_global_csv_event' );
		wp_clear_scheduled_hook( 'generate_global_bounces_csv_event' );
	}
}