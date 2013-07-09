<?php
class Bea_Sender_Client {

	function __construct( ) {
		add_action( 'init', array( __CLASS__, 'init' ) );
	}

	public static function registerCampaign( $data_campaign, $data, $content_html, $content_text = '' ) {
		$campaign = new Bea_Sender_Campaign( );
		$insert = $campaign->add( $data_campaign, $data, $content_html, $content_text );
		return $insert;
	}
	
	public static function init() {
		load_plugin_textdomain( 'bea_sender', false, basename( BEA_SENDER_DIR ) . '/languages' );
	}

	/**
	 * Create the tables if needed
	 *
	 * @param void
	 * @return void
	 * @author Nicolas Juen
	 */
	public static function activation( ) {
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
			`bounce_no` varchar(10) NOT NULL,
			UNIQUE ( email )
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
	}

	public static function getStatus( $slug ) {
		$statuses = array(
			'progress' => __( 'In progress', 'bea_sender' ),
			'registered' => __( 'Registered', 'bea_sender' ),
			'done' => __( 'Done', 'bea_sender' ),
			'send' => __( 'Sent', 'bea_sender' ),
			'pending' => __( 'Pending', 'bea_sender' ),
			'valid' => __( 'Valid', 'bea_sender' ),
			'bounced' => __( 'Bounced', 'bea_sender' ),
			'failed' => __( 'Failed', 'bea_sender' ),
			'invalid' => __( 'Invalid', 'bea_sender' ),
		);
		return isset( $statuses[$slug] ) ? $statuses[$slug] : $slug;
	}

}
