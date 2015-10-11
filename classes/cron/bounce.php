<?php

namespace BEA\Sender\Cron;

use BEA\Sender\Cron;

/**
 * This class needs Bea_Log to work
 * This class purpose is to handle cron process by :
 * - creating lock files
 * - Having a start and an end process methods
 *
 * Class Cron
 * @package BEA\Sender
 */
class Bounce extends Cron {
	/**
	 * Type for the log filename
	 *
	 * @var string
	 */
	protected $type = 'bounce-';

	private $name;

	public function __construct( $name ) {
		$this->name = $name;
		$this->type .= $name;
	}

	public function process() {
		if( $this->is_locked() ) {
			return;
		}

		// Create the lock file
		$this->create_lock_file();

		$upload_dir = wp_upload_dir();

		$file_name  = $upload_dir['basedir'] . '/bea-sender-'.$this->name.'.csv';

		@unlink( $file_name );

		$header_titles = \BEA\Sender\Export\Bounce::get_header_titles( $this->name );
		$list          = \BEA\Sender\Export\Bounce::export_bounces();

		return self::generate_file( $file_name, $header_titles, $list );
	}

	/**
	 * @param $file_name
	 * @param $headers
	 * @param $list
	 *
	 * @return bool
	 */
	private function generate_file( $file_name, $headers, $list ) {
		$outstream = fopen( $file_name, 'w' );
		fputcsv( $outstream, array_map( 'utf8_decode', $headers ), ';' );
		// Put lines in csv file
		foreach ( $list as $fields ) {
			fputcsv( $outstream, array_map( 'utf8_decode', $fields ), ';' );
		}

		fclose( $outstream );

		return $this->delete_lock_file();
	}

}