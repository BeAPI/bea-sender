<script type="text/html" id="bea-sender-log">
	<div class="stuffbox" >
		<h3><label>Log file creation</label></h3>
		<div class="inside logBlock" >
			<# _.each( data.logs, function( log ) { #>
				<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;">
					<p>
						<span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
						<strong> {{log.status}} :</strong> {{{log.message}}}
					</p>
				</div>
			<# }); #>
		</div>
	</div>
</script>

<script type="text/html" id="bea-sender-log-line">
	<div class="ui-state-highlight ui-corner-all" style="margin-top: 20px; padding: 0 .7em;">
		<p>
			<span class="ui-icon ui-icon-info" style="float: left; margin-right: .3em;"></span>
			<strong> {{data.status}} :</strong> {{{data.message}}}
		</p>
	</div>
</script>