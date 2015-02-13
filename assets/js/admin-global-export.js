(function() {
	jQuery('#wpbody').on( 'click', '.export-global', function (event) {
		event.preventDefault();

		var exportCampaign = {
			logDiv: '',
			init  : function (e, el) {
				this.nonce = el.attr( 'data-nonce' );
				this.initMessageSection();
				this.launchEvent();
			},

			initMessageSection: function () {
				this.logDiv = jQuery('<div/>').addClass('metabox-holder has-right-sidebar').append(jQuery('<div/>').addClass('stuffbox').append(jQuery('<h3/>').append(jQuery('<label/>').html(export_csv.log_file))).append(jQuery('<div/>').addClass('inside logBlock')));
				jQuery('.log').html(this.logDiv);
			},

			launchEvent : function () {
				var _self = this;
				jQuery.ajax({
					url       : ajaxurl,
					dataType  : 'json',
					type      : 'POST',
					data      : {
						action: 'bea_sender_launch_cron',
						nonce : _self.nonce
					},
					beforeSend: function () {
						_self.setMessage('message', export_csv.request_file);
					},
					success   : function (checkFile) {

						_self.setMessage('success', checkFile.message);

						if (checkFile.status == false) {
							return false;
						}
						_self.setMessage('message', export_csv.check_file);

						_self.getCheckFile();
					}
				});
			},
			setMessage  : function (status, message) {
				if (status == true) {
					var div = jQuery('<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"><p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span><strong> ' + status + ' :</strong> ' + message + '</p></div>');
				} else {
					var div = jQuery('<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;"><p><span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span><strong> ' + status + ' :</strong> ' + message + '</p></div>');
				}

				jQuery(this.logDiv).find('.inside').append(div);
			},
			getCheckFile: function () {
				var _self = this;
				jQuery.ajax({
					url     : ajaxurl,
					dataType: 'json',
					type    : 'POST',
					data    : {
						action: 'bea_sender_get_check_file',
						nonce : _self.nonce
					},
					success : function (checkFile) {

						if (checkFile.status == false) {
							_self.setMessage('error', checkFile.message);
							return false;
						}

						if (checkFile.finished == false) {
							window.setTimeout(function () {
								_self.getCheckFile();
							}, 2000);
							_self.setMessage('success', checkFile.message);

							return false;
						}

						_self.setMessage('success', checkFile.message);


					}
				});
			}

		};

		exportCampaign.init(event, jQuery(this));
	});
})();
