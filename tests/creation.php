<?php
require( dirname(__FILE__) . '/config.php' );

$data_campaign = array(
	'from' => 'nicolasjuenbeapi@yahoo.fr',
	'from_name' => 'Nicolas Juen',
	'subject' => 'Tests normal',

);
$data = array(
	'njuen+receiver1@behrthi.fr',
	'njuen+receiver2@betrhtrhi.fr',
	'njuen+receiver3@betrhtrhi.fr',
	'njuen+receiver4@betrhtrhi.fr',
	'njuen+receiver5@bthtrhei.fr',
	'njuen+receiver6@btrhtrhtrei.fr',
);

$content_html = '<div>HTML Not multiple Okokokokokok</div>';
$content_text = '<div>TEXT Not multiple Okokokokokok</div>';

$campaign = new Bea_Sender_Campaign();
$insert = $campaign->add( $data_campaign, $data ,$content_html, $content_text );
if( !empty( $insert ) ) {
	print_r( $insert );
} else {
	echo $campaign->getID().' : ok Single content'."\n";
}

$data_campaign = array(
	'from' => 'nicolasjuenbeapi@yahoo.fr',
	'from_name' => 'Nicolas Juen',
	'subject' => 'Tests mutltiple',
);

$content_html = '<div>HTML Multiple</div>';
$content_text = '<div>TEXT multiple</div>';
$data = array(
	array( 
		'email' => 'nicolasjuenbeapi@yahoo.fr',
		'html' => '1'.$content_html,
		'text' => '1'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver2@betyjtyji.fr',
		'html' => '2'.$content_html,
		'text' => '2'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver3@betyjtyi.fr',
		'html' => '3'.$content_html,
		'text' => '3'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver4@betyjtyji.fr',
		'html' => '4'.$content_html,
		'text' => '4'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver5@betyjtyji.fr',
		'html' => '5'.$content_html,
		'text' => '5'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver6@bjtyjtyei.fr',
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
	'from' => 'nicolasjuenbeapi@yahoo.fr',
	'from_name' => 'Nicolas Juen',
	'subject' => 'Tests mutltiple',
	'scheduled_from' => date( 'Y-m-d H:m:i' ,strtotime( '+15 minutes' ) )
);

$content_html = '<div>HTML Multiple</div>';
$content_text = '<div>TEXT multiple</div>';
$data = array(
	array( 
		'email' => 'njuen+receiver1@beityjtyj.fr',
		'html' => '1'.$content_html,
		'text' => '1'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver2@beityjtyj.fr',
		'html' => '2'.$content_html,
		'text' => '2'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver3@beityjtyj.fr',
		'html' => '3'.$content_html,
		'text' => '3'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver4@beityjty.fr',
		'html' => '4'.$content_html,
		'text' => '4'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver5@betyjtyji.fr',
		'html' => '5'.$content_html,
		'text' => '5'.$content_text,
	),
	array( 
		'email' => 'njuen+receiver6@bejytjtyji.fr',
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