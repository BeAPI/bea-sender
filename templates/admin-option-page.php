<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">
	<?php screen_icon( ); ?>
	<h2><?php _e( 'Bea Send - Settings', 'bea_sender' ); ?></h2>

	<?php
	self::$settings_api->show_navigation( );
	self::$settings_api->show_forms( );
	?>
</div><!-- /.wrap -->