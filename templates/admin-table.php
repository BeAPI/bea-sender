<?php
// Do the settings error
settings_errors('bea_sender');

// Add screen icon
screen_icon();
?>
<div class="wrap"> 
	<h2><?php _e( 'Bea Send', 'bea_sender' ); ?></h2>
	<?php $this->ListTable->prepare_items(); ?>
	<form method="get" action="">
		<input type="hidden" name="page" value="bea_sender" />
		<?php 
		$this->ListTable->views();
		$this->ListTable->display(); ?>
	</form>
</div>