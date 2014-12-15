<?php
// Do the settings error
settings_errors( 'bea_sender' );

?>
<div class="wrap">
	<h2>
		<?php echo get_admin_page_title(); ?>
		<a href="" data-nonce="<?php echo wp_create_nonce( 'bea-sender-export' ); ?>" class="add-new-h2 export-global">
			<?php _e( 'Export all emails to CSV', 'bea_sender' ) ?>
		</a>
	</h2>

	<?php if( isset( $export_options['global'] ) && !empty( $export_options['global'] ) ): ?>
		<p class="description">
			<?php printf( __( 'The last file was generated on <strong> %s </strong>, you can download the CSV file on <strong><a href="%s" >this link</a></strong>','bea_sender' ), esc_attr( $export_options['global']['date'] ), esc_attr( $export_options['global']['url'] ) ); ?>
		</p>
	<?php endif; ?>

	<div class="log"></div>

	<?php $this->ListTable->prepare_items( ); ?>
	<form method="get" action="">
		<input type="hidden" name="page" value="bea_sender" />
		<?php
		$this->ListTable->views( );
		$this->ListTable->display( );
		?>
	</form>
</div>