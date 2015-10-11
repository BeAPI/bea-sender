<?php
// Do the settings error
settings_errors( 'bea-sender' );

?>
<div class="wrap">

	<div class="bea_sender_table" >
		<h2>
			<?php echo get_admin_page_title(); ?>
		</h2>

		<?php $this->ListTable->prepare_items( ); ?>
		<form method="get" action="">
			<input type="hidden" name="page" value="bea-sender" />
			<?php
			$this->ListTable->views( );
			$this->ListTable->display( );
			?>
		</form>
	</div>

	<div class="bea_sender_exports" >
		<h2><?php _e( 'Exports', 'bea-sender' ) ?></h2>
		<div class="bea-sender-export" id="bea-sender-global" >
			<h3> <?php _e(  'Global emails','bea-sender' ); ?></h3>
			<?php if( isset( $export_options['global'] ) && !empty( $export_options['global'] ) ): ?>
				<p class="description">
					<?php printf( __( 'The last file was generated on <strong> %s </strong>, you can download the CSV file on <strong><a href="%s" >this link</a></strong>','bea-sender' ), esc_attr( $export_options['global']['date'] ), esc_attr( $export_options['global']['url'] ) ); ?>
				</p>
			<?php endif; ?>
			<button data-type="global" data-nonce="<?php echo wp_create_nonce( 'bea-sender-export-global' ); ?>" class=" generate export-global button button-primary">
				<?php _e( 'Export all emails to CSV', 'bea-sender' ) ?>
			</button>
			<div class="log"></div>
		</div>

		<div class="bea-sender-export" id="bea-sender-bounces" >
			<h3> <?php _e(  'Bounced emails','bea-sender' ); ?> </h3>

			<?php if( isset( $export_options['bounces'] ) && !empty( $export_options['bounces'] ) ): ?>
				<p class="description">
					<?php printf( __( 'The last file was generated on <strong> %s </strong>, you can download the CSV file on <strong><a href="%s" >this link</a></strong>','bea-sender' ), esc_attr( $export_options['bounces']['date'] ), esc_attr( $export_options['bounces']['url'] ) ); ?>
				</p>
			<?php endif; ?>
			<button data-type="bounces" data-nonce="<?php echo wp_create_nonce( 'bea-sender-export-bounces' ); ?>" class="generate export-bounces button button-primary">
				<?php _e( 'Export all bounces to CSV', 'bea-sender' ) ?>
			</button>
			<div class="log"></div>
		</div>
	</div>

	<div class="bea_sender_purge" >
		<h2><?php _e( 'Purge', 'bea-sender' ) ?></h2>
		<div class="bea-sender-purge" id="bea-sender-purge" >
			<p class="description" ><?php _e( 'Here you can purge all bounced emails from the database between two dates' , 'bea-sender' ) ;?></p>
			<form method="post" action="<?php echo admin_url( 'admin-post.php' ); ?>">
				<label for="bea-sender-date-from-display" ><?php _e(  'From :','bea-sender' ); ?> </label>
				<input type="text" class="bea_sender_datepicker" value="" id="bea-sender-date-from-display" />
				<input type="hidden" value="" id="bea-sender-date-from" name="date_from" />

				<label for="bea-sender-date-to-display" > <?php _e(  'To :','bea-sender' ); ?> </label>
				<input type="text" class="bea_sender_datepicker" value="" id="bea-sender-date-to-display" />
				<input type="hidden" value="" id="bea-sender-date-to" name="date_to" />

				<input type="hidden" name="action" value="bea_sender_purge" >
				<?php
				wp_nonce_field( 'bea-sender-purge' );
				submit_button( __( 'Purge this range', 'bea-sender' ) ); ?>
			</form>
		</div>
	</div>

</div>