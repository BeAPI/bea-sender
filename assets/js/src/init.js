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