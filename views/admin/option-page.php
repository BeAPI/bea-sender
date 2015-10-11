<!-- Create a header in the default WordPress 'wrap' container -->
<div class="wrap">
	<h2><?php echo get_admin_page_title() ?></h2>

	<?php
	self::$settings_api->show_navigation( );
	self::$settings_api->show_forms( );
	?>
</div><!-- /.wrap -->