<?php
require( dirname(__FILE__) . '/config.php' );

$sender = new Bea_Sender_Sender();
var_dump( $sender->init() );