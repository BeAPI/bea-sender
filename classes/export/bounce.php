<?php
namespace BEA\Sender\Export;

class Bounce {
	private static $header_titles;

	function __construct() {}
	/**
	 * Get all the data for the bounces
	 *
	 * @return mixed|void
	 * @author Nicolas Juen
	 */
	private static function generate_csv_bounces() {
		/* @var $wpdb \wpdb */
		global $wpdb;

		$list = array();

		$contacts = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT
				id,
				email,
				current_status,
				bounce_cat,
				bounce_type,
				bounce_no
			FROM $wpdb->bea_s_receivers as r
			WHERE current_status = %s", 'invalid' )
		);

		foreach ( $contacts as $contact ) {
			$list[] = apply_filters(
				'bea_sender_csv_bounce_item', array(
				$contact->id,
				$contact->email,
				$contact->current_status,
				$contact->bounce_cat,
				$contact->bounce_type,
				$contact->bounce_no
			), $contact
			);
		}

		return apply_filters( 'bea_sender_csv_bounce_list', $list, $contacts );
	}

	/**
	 * Export all the bounces available
	 *
	 * @return mixed|void
	 */
	public static function export_bounces() {
		return self::generate_csv_bounces();
	}

	/**
	 * Add the campaign id
	 *
	 * @param $campaign_id
	 *
	 * @return mixed|void
	 * @author Nicolas Juen
	 */
	public static function get_header_titles( $campaign_id ) {
		self::$header_titles = apply_filters(
			'bea_sender_csv_headers', array(
				'Id',
				'Email',
				'Current status',
				'Bounce cat',
				'Bounce type',
				'Bounce no'
			),
			$campaign_id
		);

		return self::$header_titles;
	}
}