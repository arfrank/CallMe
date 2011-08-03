jQuery(document).ready(function($) {
	Twilio.Device.setup(token);
	Twilio.Device.ready(function (device) {
		var callme_widget = $('#callme_widget');
		setTimeout(function(){
			if (callme_widget.hasClass('callme_bottomleft') || callme_widget.hasClass('callme_bottomright') ) {
				callme_widget.animate({'bottom':'0px'});
			}
			if (callme_widget.hasClass('callme_topleft') || callme_widget.hasClass('callme_topright') ) {
				callme_widget.animate({'top':'0px'});	
			}
		}, 1000);
		if (autoconnect_conference != undefined && autoconnect_conference) {
			call();
		};
	});

	Twilio.Device.error(function (error) {
		console.log('An error occurred loading the CallMe Widget - '+error);
	});

	function call() {
		Twilio.Device.connect();
	}
	$('#callme_widget').click(function(event) {
		call();
	});
});
