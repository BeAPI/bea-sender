/**
 * Main.js
 */
// Object basic
var fr;
if (!fr) {
	fr = {};
} else {
	if (typeof fr !== "object") {
		throw new Error('fr already exists and not an object');
	}
}

if (!fr.bea_sender) {
	fr.bea_sender = {};
} else {
	if (typeof fr.bea_sender !== "object") {
		throw new Error('fr.bea_sender already exists and not an object');
	}
}

fr.bea_sender = {
	views : {},
	models : {}
};


fr.bea_sender.tools = {
	uniqid : function( prefix, more_entropy ) {
		// +   original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
		// +    revised by: Kankrelune (http://www.webfaktory.info/)
		// %        note 1: Uses an internal counter (in php_js global) to avoid collision
		// *     example 1: uniqid();
		// *     returns 1: 'a30285b160c14'
		// *     example 2: uniqid('foo');
		// *     returns 2: 'fooa30285b1cd361'
		// *     example 3: uniqid('bar', true);
		// *     returns 3: 'bara20285b23dfd1.31879087'
		if( typeof prefix == 'undefined' ) {
			prefix = "";
		}

		var retId;
		var formatSeed = function( seed, reqWidth ) {
			seed = parseInt( seed, 10 ).toString( 16 );
			// to hex str
			if( reqWidth < seed.length ) {// so long we split
				return seed.slice( seed.length - reqWidth );
			}
			if( reqWidth > seed.length ) {// so short we pad
				return Array( 1 + ( reqWidth - seed.length ) ).join( '0' ) + seed;
			}
			return seed;
		};

		// BEGIN REDUNDANT
		if( !this.php_js ) {
			this.php_js = {};
		}
		// END REDUNDANT
		if( !this.php_js.uniqidSeed ) {// init seed with big random int
			this.php_js.uniqidSeed = Math.floor( Math.random( ) * 0x75bcd15 );
		}
		this.php_js.uniqidSeed++;

		retId = prefix;
		// start with prefix, add current milliseconds hex string
		retId += formatSeed( parseInt( new Date( ).getTime( ) / 1000, 10 ), 8 );
		retId += formatSeed( this.php_js.uniqidSeed, 5 );
		// add seed hex string
		if( more_entropy ) {
			// for more entropy we add a float lower to 10
			retId += ( Math.random( ) * 10 ).toFixed( 8 ).toString( );
		}

		return retId;
	},
	template : _.memoize( function ( id ) {
		var compiled,
			options = {
				evaluate:    /<#([\s\S]+?)#>/g,
				interpolate: /\{\{\{([\s\S]+?)\}\}\}/g,
				escape:      /\{\{([^\}]+?)\}\}(?!\})/g,
				variable:    'data'
			};

		return function ( data ) {
			compiled = compiled || _.template( jQuery( '#bea-sender-' + id ).html(), null, options );
			return compiled( data );
		};
	})
};

/**
 * Log model
 */
'use strict';
fr.bea_sender.models.Log = Backbone.Model.extend({
	defaults: {
		logs :''
	}
});

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

/**
 * Exporter view
 */
'use strict';
fr.bea_sender.views.Log = Backbone.View.extend({
	tagName: "div",
	className : 'metabox-holder has-right-sidebar',
	template: fr.bea_sender.tools.template( 'log' ),
	template_log: fr.bea_sender.tools.template( 'log-line' ),
	initialize : function() {
		// Init the model
		this.model = new fr.bea_sender.models.Log( { id : fr.bea_sender.tools.uniqid() } );
	},
	render : function() {
		this.$el.html( this.template( { logs : this.model.get( 'logs' ) } ) );
		return this;
	},
	add_log : function( data ) {
		var logs = this.model.get( 'logs' );
		if( !_.isArray( logs ) ) {
			logs = [];
		}

		logs.push( data );

		this.model.set( 'logs', logs );

		// Render the new line
		this.$el.find( '.inside' ).append( this.template_log( data ) );
	}
});

/**
 * Init
 */
// Fill the view

jQuery(function() {
	jQuery('.bea-sender-export').each( function( i, el ) {
		// Make the view object
		var export_view = new fr.bea_sender.views.Exporter( { el : el } );
	} );
});

//# sourceMappingURL=app.js.map