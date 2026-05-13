<?php
if ( ! class_exists( 'Bea_Log' ) ) {
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

		/**
		 * @param string $file_path      Base path without extension.
		 * @param string $file_extention Extension including dot.
		 * @param string $retention_size Max size in bytes before rotation.
		 * @param bool   $write_enabled  When false, log_this is a no-op.
		 */
		function __construct( $file_path, $file_extention = '.log', $retention_size = '', $write_enabled = true ) {
			if ( ! isset( $file_path ) || empty( $file_path ) ) {
				return false;
			}

			// Put file path
			$this->file_path = $file_path;

			// File extention
			if ( isset( $file_extention ) ) {
				$this->file_extention = $file_extention;
			}

			// Retention size
			if ( isset( $retention_size ) && ! empty( $retention_size ) && (int) $retention_size > 0 ) {
				$this->retention_size = $retention_size;
			}

			$this->is_configured = (bool) $write_enabled;
		}

		/**
		 * Log data in multiple files when full
		 *
		 * @param $message : the message to log
		 * @return boolean : true on success
		 * @author Nicolas Juen
		 */
		public function log_this( $message, $type = '' ) {
			if ( ! $this->is_configured ) {
				return false;
			}

			// Add the type
			if ( empty( $type ) ) {
				$type = self::gravity_7;
			}

			// Make the file path
			$file_path = $this->file_path . $this->file_extention;

			// If the file exists
			if ( is_file( $file_path ) ) {
				// Get file size
				$size = filesize( $file_path );

				// Check size
				if ( $size > $this->retention_size ) {
					$rotated_path = $this->file_path . '-' . date( 'Y-m-d-H-i-s' ) . $this->file_extention;
					if ( rename( $file_path, $rotated_path ) ) {
						$this->maybe_zip_rotated_file( $rotated_path );
					}
				}
			}

			// Log the error
			error_log( date( '[d-m-Y H:i:s]' ) . '[' . $type . '] ' . $message . "\n", 3, $file_path );
			return true;
		}

		/**
		 * Compress a rotated log into a sibling .zip and remove the plain file on success.
		 *
		 * @param string $rotated_path Absolute path to the rotated log file.
		 */
		private function maybe_zip_rotated_file( $rotated_path ) {
			if ( ! class_exists( 'ZipArchive' ) ) {
				return;
			}

			if ( ! is_string( $rotated_path ) || '' === $rotated_path || ! is_file( $rotated_path ) ) {
				return;
			}

			$zip_path = $rotated_path . '.zip';
			$zip      = new ZipArchive();

			if ( true !== $zip->open( $zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE ) ) {
				return;
			}

			$zip->addFile( $rotated_path, basename( $rotated_path ) );

			if ( ! $zip->close() ) {
				if ( is_file( $zip_path ) ) {
					unlink( $zip_path );
				}
				return;
			}

			if ( is_file( $zip_path ) && filesize( $zip_path ) > 0 ) {
				unlink( $rotated_path );
			}
		}
	}
}
