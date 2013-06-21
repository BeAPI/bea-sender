<?php
// Do the settings error
settings_errors('bea_sender');

// Add screen icon
screen_icon();

// Setup the campaign
$campaign = new Bea_Sender_Campaign( (int)$_GET['c_id'] );

// Get all the receivers
$receivers = $campaign->get_receivers();


?>
<div class="wrap"> 
	<h2><?php _e( 'Bea Send - Campaign', 'bea_sender' ); ?></h2>
	<a href="<?php echo add_query_arg( array( 'page' => 'bea_sender' ), admin_url( '/tools.php' ) ); ?>"> Retourner aux campagnes </a>
	<?php if( !$campaign->isData() ): ?>
		<p><?php _e( 'Sorry this campaign does not exsists' ); ?></p>
	<?php else : ?>
		<table class='wp-list-table widefat'>
			<thead>
				<tr>
					<th>ID</th>
					<th>Email</th>
					<th>Current status</th>
					<th>Campaign status</th>
					<th>Bounce cat</th>
					<th>Bounce type</th>
					<th>Bounce number</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<th>ID</th>
					<th>Email</th>
					<th>Current status</th>
					<th>Campaign status</th>
					<th>Bounce cat</th>
					<th>Bounce type</th>
					<th>Bounce number</th>
				</tr>
			</tfoot>
			<?php 
			foreach ( $receivers as $receiver ) :
				if( $receiver->get_contents_campaign( $campaign->getID() ) === false ) {
					continue;
				}
			?>
			<tr>
				<td>
					<?php echo $receiver->id; ?>
				</td>
				<td>
					<?php echo $receiver->email; ?>
				</td>
				<td>
					<?php echo Bea_Sender_Client::getStatus( $receiver->current_status ); ?>
				</td>
				<td>
					<?php echo Bea_Sender_Client::getStatus( $receiver->campaign_current_status ); ?>
				</td>
				<td>
					<?php echo $receiver->bounce_cat; ?>
				</td>
				<td>
					<?php echo $receiver->bounce_type; ?>
				</td>
				<td>
					<?php echo $receiver->bounce_no; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	<?php endif; ?>
</div>