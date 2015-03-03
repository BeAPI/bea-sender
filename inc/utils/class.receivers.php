<?php
class Bea_Sender_Receivers {


	public static function get_bounced( $args = array() ) {
		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$defaults = array(
			'campaign_ids' => array()
		);

		$arguments = wp_parse_args( $args, $defaults );

		$where = "WHERE 1=1 r.current_status = 'bounced'";
		if( !empty( $arguments['campaign_ids'] ) ) {
			$where .= 'AND c.id IN ( '.implode( ',', array_map( 'absint', $arguments['campaign_ids'] ) ).' ) ';
		}

		// Make the where hookable
		$where = apply_filters( 'bea_sender_get_bounces_where', $where, $arguments );

		return $wpdb->get_results(
			"SELECT
				r.id,
				r.email,
				r.current_status,
				r.bounce_cat,
				r.bounce_type,
				r.bounce_no
				FROM
					$wpdb->bea_s_receivers as r
				JOIN
					$wpdb->bea_s_re_ca as re_ca
					ON
					r.id = re_ca.id_receiver
				JOIN
					$wpdb->bea_s_campaigns as c
					ON
					re_ca.id_campaign = c.id
				$where"
		);

	}

	/**
	 * Purge a certain bounced elements
	 *
	 * @param array $args
	 *
	 * @return false|int
	 * @author Nicolas Juen
	 */
	public static function purge_bounced( $args = array() ) {
		/**
		 * @var $wpdb wpdb
		 */
		global $wpdb;

		$defaults = array(
			'from' => '',
			'to' => '',
			'campaign_ids' => array()
		);

		$arguments = wp_parse_args( $args, $defaults );

		// On no arguments
		if( empty( $arguments['from'] ) && empty( $arguments['to'] ) && empty( $arguments['campaign_ids'] ) ) {
			// Everybody gets updated \o/
			return $wpdb->update( $wpdb->bea_s_receivers, array( 'current_status' => 'valid' ), array( 'current_status' => 'bounced' ), array( '%s' ), array( '%s' ) );

		}

		// Get the bounced users
		$where = "WHERE 1=1 AND r.current_status = 'bounced'";

		// If the campaign is given
		if( !empty( $arguments['campaign_ids'] ) && is_array( $arguments['campaign_ids'] ) ) {
			$where .= 'AND c.id IN ( '.implode( ',', array_map( 'absint', $arguments['campaign_ids'] ) ).' ) ';
		}

		// Handle from and to
		if( !empty( $arguments['from'] ) && !empty( $arguments['to'] ) ) {
			$arguments['from'] = date( 'Y-m-d H:i:s', strtotime( $arguments['from'] ) );
			$arguments['to'] = date( 'Y-m-d H:i:s', strtotime( $arguments['to'] ) );
			$where .= $wpdb->prepare( 'AND c.scheduled_from BETWEEN %s AND %s ', array( $arguments['from'], $arguments['to'] ) );
		}

		// Make the where hookable
		$where = apply_filters( 'bea_sender_purge_where', $where, $arguments );

		// Get all the needed ids
		$ids = $wpdb->get_col( "SELECT
					r.id
 					FROM
						$wpdb->bea_s_receivers as r
					JOIN
						$wpdb->bea_s_re_ca as re_ca
						ON
						r.id = re_ca.id_receiver
					JOIN
						$wpdb->bea_s_campaigns as c
						ON
						re_ca.id_campaign = c.id
					$where" );

		// If no users, do not update
		if( empty( $ids ) ) {
			return 0;
		}

		// Make the ids
		$ids = implode( ',', $ids );

		// Update the given ids
		return $wpdb->query( "
			UPDATE
				$wpdb->bea_s_receivers as r,
				$wpdb->bea_s_re_ca as re_ca
			SET
			 re_ca.current_status = 'valid',
			 r.current_status = 'valid',
			 r.bounce_cat = '',
			 r.bounce_type = '',
			 r.bounce_no = ''
			 WHERE
			 	r.id IN ( ".$ids." )
			 	OR
			 	re_ca.id_receiver IN ( ".$ids." )
		" );

	}
}
