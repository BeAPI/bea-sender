<?php
namespace BEA\Sender;
use BEA\Sender\Core\Campaign;


/**
 * The purpose of the main class is to init all the plugin base code like :
 *  - Taxonomies
 *  - Post types
 *  - Posts to posts relations etc.
 *  - Loading the text domain
 *
 * Class Main
 * @package BEA\Sender
 */
class Main {

	public function __construct() {
		add_action( 'init', array( __CLASS__, 'init' ) );

		add_action( 'generate_global_csv_event', array( __CLASS__, 'cron_buildCSV' ) );
		add_action( 'generate_global_bounces_csv_event', array( __CLASS__, 'cron_buildCSV_Bounces' ) );
	}

	/**
	 * Load the plugin translation
	 */
	public static function init() {
		// Load translations
		load_plugin_textdomain( 'bea-sender', false, BEA_SENDER_DIR . '/languages' );
	}

	/**
	 * Create the CSV
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Zainoudine soulé
	 */
	public static function cron_buildCSV( $name = '' ) {
		$cron = new Cron\Campaign( $name );
		$cron->process();
	}

	/**
	 * Create the CSV
	 *
	 * @param string $name
	 *
	 * @return bool
	 * @author Zainoudine soulé
	 */
	public static function cron_buildCSV_Bounces( $name ) {
		$cron = new Cron\Bounce( $name );
		$cron->process();
	}

	/**
	 * Add given data to the campaign
	 *
	 * @param (array)$data_campaign : the campaign datas
	 * @param (array)$data : the emails to do
	 * @param (string)$content_html : the html content to use
	 * @param (string)$content_text : (optional) the raw content to use on this emailing
	 *
	 * @return boolean
	 *
	 * @author Nicolas Juen
	 *
	 */
	public static function registerCampaign( $data_campaign, $data, $content_html, $content_text = '', $attachments= array() ) {
		$campaign = new Campaign( );
		$insert = $campaign->add( $data_campaign, $data, $content_html, $content_text, $attachments );
		return $insert;
	}

	/**
	 * Get a status given all available
	 *
	 * @param (string)$slug : the status slug
	 * @return (sting): translated string
	 * @author Nicolas Juen
	 *
	 */
	public static function getStatus( $slug ) {
		$statuses = array(
			'progress' => __( 'In progress', 'bea-sender' ),
			'registered' => __( 'Registered', 'bea-sender' ),
			'done' => __( 'Done', 'bea-sender' ),
			'send' => __( 'Sent', 'bea-sender' ),
			'pending' => __( 'Pending', 'bea-sender' ),
			'valid' => __( 'Valid', 'bea-sender' ),
			'bounced' => __( 'Bounced', 'bea-sender' ),
			'failed' => __( 'Failed', 'bea-sender' ),
			'invalid' => __( 'Invalid', 'bea-sender' ),
		);
		return isset( $statuses[$slug] ) ? $statuses[$slug] : $slug;
	}
}