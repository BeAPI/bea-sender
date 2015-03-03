/**
 * Exporter view
 */
'use strict';
fr.bea_sender.views.Exporter = Backbone.View.extend({
	log : '',
	nonce : '',
	type : '',
	action : '',
	launched : false,
	button : '',
	events : {
		'click .generate' : 'generate'
	},
	initialize : function() {
		// Launch the log
		this.log = new fr.bea_sender.views.Log();
	},
	render : function() {
		jQuery( this.$el ).find( '.log' ).html( this.log.render().$el );

		return this;
	},
	generate : function( e ) {
		e.preventDefault();

		if( true === this.launched ) {
			return;
		}

		this.launched = true;

		var self = this;

		this.nonce = e.currentTarget.getAttribute( 'data-nonce' );
		this.type =  e.currentTarget.getAttribute( 'data-type' );
		this.action =  e.currentTarget.getAttribute( 'data-action' );
		this.button =jQuery( e.currentTarget);
		this.button.attr( 'disabled', 'disabled' );

		this.log.add_log( {
			status : 'success',
			message : 'Send request to generate the CSV file.'
		} );

		this.render();

		jQuery.ajax({
			url       : ajaxurl,
			dataType  : 'json',
			type      : 'POST',
			data      : {
				action: 'bea_sender_launch_cron',
				type : self.type,
				nonce : self.nonce
			},
			success   : function ( checkFile ) {
				self.log.add_log( {
					status : 'success',
					message :  checkFile.message
				} );

				if ( checkFile.status == false ) {
					this.launched = false;
					self.button.removeAttr( 'disabled' );
					return false;
				}

				self.log.add_log( {
					status : 'success',
					message :  'Checking if the lock file exists'
				} );

				// Wait 2sec
				window.setTimeout(function () {
					self.check_file();
				}, 2000);
			}
		});
	},
	check_file : function() {
		var self = this;
		jQuery.ajax({
			url     : ajaxurl,
			dataType: 'json',
			type    : 'POST',
			data    : {
				action: 'bea_sender_get_check_file',
				nonce : self.nonce,
				type : self.type
			},
			success : function ( checkFile ) {
				if( checkFile.status == 'error' ) {
					self.log.add_log( {
						status : 'error',
						message : checkFile.message
					} );
					this.launched = false;
					self.button.removeAttr( 'disabled' );
					return false;
				}

				if ( checkFile.finished == false) {
					window.setTimeout(function () {
						self.check_file();
					}, 2000);
					self.log.add_log({
						status   : 'success',
						message: checkFile.message
					});

					return false;
				}

				self.log.add_log( {
					status : 'success',
					message : checkFile.message
				} );
				this.launched = false;
				self.button.removeAttr( 'disabled' );
			}
		});
	}
});