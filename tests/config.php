<?php
if( php_sapi_name( ) !== 'cli' || isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
	die('CLI Only');
}

// Load WordPress
$bootstrap = 'wp-load.php';
while( !is_file( $bootstrap ) ) {
	if( is_dir( '..' ) )
		chdir( '..' );
	else
		die( 'EN: Could not find WordPress! FR : Impossible de trouver WordPress !' );
}
require_once( $bootstrap );