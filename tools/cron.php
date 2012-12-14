<?php
if( php_sapi_name( ) !== 'cli' || isset( $_SERVER[ 'REMOTE_ADDR' ] ) ) {
	die('CLI Only');
}

@ini_set( 'memory_limit','1024M' );
@ini_set('display_errors',0);

define('WP_ALLOW_MULTISITE', false);
define('MULTISITE', false);

require( dirname(__FILE__) . '/../../../../wp-load.php' );

$sender = new Bea_Sender_Sender();
$sender->init();

die();