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