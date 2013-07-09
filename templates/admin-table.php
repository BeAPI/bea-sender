<?php
// Do the settings error
settings_errors('bea_sender');

// Add screen icon
screen_icon();
// Export CSV url
$csv_url = wp_nonce_url(add_query_arg(array('page' => 'bea_sender', 'bea_s-export' => 'true'), admin_url('/tools.php')));
?>
<div class="wrap"> 
	<h2>
            <?php _e( 'Bea Send', 'bea_sender' ); ?>
            <a href="<?php echo $csv_url; ?>" class="add-new-h2">Exporter en CSV</a>
        </h2>
	<?php $this->ListTable->prepare_items(); ?>
	<form method="get" action="">
		<input type="hidden" name="page" value="bea_sender" />
		<?php 
		$this->ListTable->views();
		$this->ListTable->display(); ?>
	</form>
</div>