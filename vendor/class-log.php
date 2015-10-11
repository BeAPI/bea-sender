<?php
if( !class_exists( 'Bea_Log' ) ) {
	class Bea_Log {

		private $file_path;
		private $file_extention;
		private $retention_size = '41943040';

		private $is_configured = false;

		// Levels of gravity for the logging
		const gravity_0 = 'Emerg';
		const gravity_1 = 'Alert';
		const gravity_2 = 'Crit';
		const gravity_3 = 'Err';
		const gravity_4 = 'Warning';
		const gravity_5 = 'Notice';
		const gravity_6 = 'Info';
		const gravity_7 = 'Debug';

		function __construct( $file_path, $file_extention = '.log' , $retention_size = '' ) {
			if( !isset( $file_path ) || empty( $file_path ) ) {
				return false;
			}

			// Put file path
			$this->file_path = $file_path;

			// File extention
			if( isset( $file_extention ) ) {
				$this->file_extention = $file_extention;
			}

			// Retention size
			if( isset( $retention_size ) && !empty( $retention_size ) && (int)$retention_size > 0 ) {
				$this->retention_size = $retention_size;
			}

			$this->is_configured = true;
		}

		/**
		 * Log data in multiple files when full
		 *
		 * @param $message : the message to log
		 * @return boolean : true on success
		 * @author Nicolas Juen
		 *
		 */
		public function log_this( $message, $type = '' ) {
			if( $this->is_configured === false ) {
				return false;
			}

			// Add the type
			if( empty( $type ) ) {
				$type = self::gravity_7;
			}

			// Make the file path
			$file_path = $this->file_path.$this->file_extention;

			// If the file exists
			if( is_file( $file_path ) ) {
				// Get file size
				$size = filesize( $file_path );

				// Check size
				if( $size > $this->retention_size ) {
					// Rename the file
					rename( $file_path, $this->file_path.'-'.date( 'Y-m-d-H-i-s' ).$this->file_extention );
				}
			}

			// Log the error
			error_log( date('[d-m-Y H:i:s]').'['.$type.'] '.$message."\n", 3, $file_path );
			return true;
		}
	}
}