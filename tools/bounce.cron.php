<?php
if( php_sapi_name( ) !== 'cli' || isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
	die('CLI Only');
}

// Get first arg
if ( !isset($argv) || count($argv) < 2 ) {
	die('Missing args for CLI usage');
}

// Domain
$domain = ( isset($argv[1]) ) ? $argv[1] : '';

// Fake WordPress, build server array
$_SERVER = array(
	'HTTP_HOST'      => $domain,
	'SERVER_NAME'    => $domain,
	'REQUEST_URI'    => basename(__FILE__),
	'REQUEST_METHOD' => 'GET',
	'SCRIPT_NAME' 	 => basename(__FILE__),
	'SCRIPT_FILENAME' 	 => basename(__FILE__),
	'PHP_SELF' 		 => basename(__FILE__)
);

@ini_set( 'memory_limit','1024M' );
@ini_set('display_errors',0);

define('WP_ALLOW_MULTISITE', false);
define('MULTISITE', false);

// Load WordPress
$bootstrap = 'wp-load.php';
while( !is_file( $bootstrap ) ) {
	if( is_dir( '..' ) ) 
		chdir( '..' );
	else
		die( 'EN: Could not find WordPress! FR : Impossible de trouver WordPress !' );
}
require_once( $bootstrap );

$bounce = new Bea_Sender_BounceEmail();
$bounce->bounce_init();

die();