fr.bea_sender.datepicker = (function(){
	var from = jQuery( '#bea-sender-date-from-display'),
	to = jQuery( '#bea-sender-date-to-display' );


	from.datepicker({
		altField : '#bea-sender-date-from',
		altFormat : 'yymmdd',
		dateFormat : bea_sender_vars.date_format,
		changeMonth: true,
		numberOfMonths: 3,
		onClose: function( selectedDate ) {
			to.datepicker( "option", "minDate", selectedDate );
		},
		monthNames: bea_sender_vars.month,
		monthNamesShort: bea_sender_vars.month_abbrev,
		dayNames: bea_sender_vars.weekday,
		dayNamesShort: bea_sender_vars.weekday_abbrev,
		dayNamesMin: bea_sender_vars.weekday_initial,
		firstDay: bea_sender_vars.start_of_week
	});

	to.datepicker({
		altField : '#bea-sender-date-to',
		altFormat : 'yymmdd',
		dateFormat : bea_sender_vars.date_format,
		changeMonth: true,
		numberOfMonths: 3,
		onClose: function( selectedDate ) {
			from.datepicker( "option", "maxDate", selectedDate );
		},
		monthNames: bea_sender_vars.month,
		monthNamesShort: bea_sender_vars.month_abbrev,
		dayNames: bea_sender_vars.weekday,
		dayNamesShort: bea_sender_vars.weekday_abbrev,
		dayNamesMin: bea_sender_vars.weekday_initial,
		firstDay: bea_sender_vars.start_of_week
	});
})();