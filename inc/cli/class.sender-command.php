<?php

/**
 * Send bea sender campaign
 *
 * Class Bea_Sender_Command
 *
 * @author Léonard Phoumpakka
 *
 */
class Bea_Sender_Command extends \WP_CLI_Command {

	/**
	 * Bea sender to send email process
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @throws \WP_CLI\ExitException
	 *
	 * @author Léonard Phoumpakka
	 *
	 * ## EXAMPLES
	 *
	 * wp bea-sender-mail send
	 *
	 * @subcommand send
	 *
	 */
	public function send( $args, $assoc_args ) {
		if ( ! class_exists( 'Bea_Sender_Campaign' ) ) {
			\WP_CLI::error( 'The Bea_Sender_Campaign plugin is not activated' );
		}

		$sender = new \Bea_Sender_Sender();
		$sender = $sender->init();

		if ( empty( $sender ) ) {
			\WP_CLI::error( 'The mailing campaigns could not be sent' );
		}

		\WP_CLI::success( 'The mailing campaigns were sent' );
	}

	/**
	 * Bea sender to prepare test process
	 *
	 * @param $args
	 * @param $assoc_args
	 *
	 * @throws \WP_CLI\ExitException
	 *
	 * @author Léonard Phoumpakka
	 *
	 * ## EXAMPLES
	 *
	 * wp bea-sender-mail prepare-test-mail
	 *
	 * @subcommand prepare-test-mail
	 *
	 */
	public function prepare_send_test( $args, $assoc_args ) {

		$emails = isset( $assoc_args['emails'] ) ? explode( ',', $assoc_args['emails'] ) : [];

		$datas = [];

		if ( ! empty( $emails ) ) {
			foreach ( $emails as $email ) {
				if ( ! is_email( $email ) ) {
					continue;
				}
				$datas[] = [
					'email' => sanitize_email( $email ),
					'html'  => '<p>Test email for ' . $email . '</p>',
				];
			}
		}

		if ( empty( $datas ) ) {
			\WP_CLI::error( 'No recipients found' );
		}

		$data_campaign = [
			'from'      => 'test@bea-sender.fr',
			'from_name' => 'BEA Sender',
			'subject'   => 'BEA Sender - ' . date_i18n( 'd/m/Y', time() ),
		];

		$campaign = new \Bea_Sender_Campaign();
		$insert   = $campaign->add( $data_campaign, $datas );

		if ( ! $insert ) {
			\WP_CLI::error( 'Problem to prepared the mailing campaigns' );
		}

		\WP_CLI::success( 'The mailing campaigns were prepared' );
	}
}
