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
			\WP_CLI::log( sprintf( 'No campaign to send. You can see more with log : %s', WP_CONTENT_DIR . '/bea-sender-email-cron' ) );
			return;
		}

		\WP_CLI::success( 'The mailing campaigns were sent' );
	}
}
