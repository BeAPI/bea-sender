<?php
require( dirname(__FILE__) . '/config.php' );

$data_campaign = array(
	'from' => 'njuen+sender@beapi.fr',
	'from_name' => 'Nicolas Juen',
	'subject' => 'Tests normal',

);
$data = array(
	'njuen+receiver1@beapi.fr',
	'njuen+receiver2@beapi.fr',
	'njuen+receiver3@beapi.fr',
	'njuen+receiver4@beapi.fr',
	'njuen+receiver5@beapi.fr',
	'njuen+receiver6@beapi.fr',
);

$content_html = '<div>{email} HTML Not multiple Okokokokokok</div>';
$content_text = '<div>{email} TEXT Not multiple Okokokokokok</div>';

$campaign = new Bea_Sender_Campaign();
$insert = $campaign->add( $data_campaign, $data ,$content_html, $content_text );
if( !empty( $insert ) ) {
	print_r( $insert );
} else {
	echo $campaign->getID().' : ok Single content'."\n";
}

$data_campaign = array(
	'from' => 'njuen+sender@beapi.fr',
	'from_name' => 'Nicolas Juen',
	'subject' => 'Tests mutltiple',
);

$content_html = '<div>{email} HTML Multiple</div>';
$content_text = '<div>{email} TEXT multiple</div>';
$data = array(
	array( 
		'email' => 'njuen+receiver1@beapi.fr',
		'html' => '1'.$content_html,
		'text' => '1'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver2@beapi.fr',
		'html' => '2'.$content_html,
		'text' => '2'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver3@beapi.fr',
		'html' => '3'.$content_html,
		'text' => '3'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver4@beapi.fr',
		'html' => '4'.$content_html,
		'text' => '4'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver5@beapi.fr',
		'html' => '5'.$content_html,
		'text' => '5'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver6@beapi.fr',
		'html' => '6'.$content_html,
		'text' => '6'.$content_text,
	),
);

$campaign = new Bea_Sender_Campaign();
$insert = $campaign->add( $data_campaign, $data );
if( !empty( $insert ) ) {
	print_r( $insert );
} else {
	echo $campaign->getID().' : ok Multiple content'."\n";
}

$data_campaign = array(
	'from' => 'njuen+sender@beapi.fr',
	'from_name' => 'Nicolas Juen',
	'subject' => 'Tests mutltiple',
	'scheduled_from' => date( 'Y-m-d H:m:i' ,strtotime( '+15 minutes' ) )
);

$content_html = '<div>{email} HTML Multiple</div>';
$content_text = '<div>{email} TEXT multiple</div>';
$data = array(
	array( 
		'email' => 'njuen+receiver1@beapi.fr',
		'html' => '1'.$content_html,
		'text' => '1'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver2@beapi.fr',
		'html' => '2'.$content_html,
		'text' => '2'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver3@beapi.fr',
		'html' => '3'.$content_html,
		'text' => '3'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver4@beapi.fr',
		'html' => '4'.$content_html,
		'text' => '4'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver5@beapi.fr',
		'html' => '5'.$content_html,
		'text' => '5'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver6@beapi.fr',
		'html' => '6'.$content_html,
		'text' => '6'.$content_text,
	),
);

$campaign = new Bea_Sender_Campaign();
$insert = $campaign->add( $data_campaign, $data );
if( !empty( $insert ) ) {
	print_r( $insert );
} else {
	echo $campaign->getID().' : ok Multiple content'."\n";
}