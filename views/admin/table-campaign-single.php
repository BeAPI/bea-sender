<?php
// Do the settings error
settings_errors('bea-sender');

// Setup the campaign
$campaign = new Bea_Sender_Campaign( (int)$_GET['c_id'] );

// Get all the receivers
$receivers = $campaign->get_receivers();
?>
<div class="wrap"> 
	<h2><?php echo get_admin_page_title(); ?><a class="add-new-h2" href="<?php echo add_query_arg( array( 'page' => 'bea-sender' ), admin_url( '/tools.php' ) ); ?>"> <?php _e( 'Return to campaigns', 'bea-sender') ?></a></h2>
	<?php $this->ListTableSingle->prepare_items( ); ?>
	<form method="get" action="">
		<input type="hidden" name="page" value="bea_sender" />
		<?php
		$this->ListTableSingle->views( );
		$this->ListTableSingle->display( );
		?>
	</form>
</div>