jQuery(document).ready(function($) {
	var callme_widget = $('#callme_widget');
	var connected = false;
	Twilio.Device.setup(token);
	Twilio.Device.ready(function (device) {
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
		connected = false;
		callme_widget.html("An error occurred. Please try again.");			
		console.log('An error occurred loading the CallMe Widget - '+error);
	});
	

	function call() {
		if (connected) {
			connected = false;
			Twilio.Device.disconnect()
			callme_widget.html(callme_widget.data('standard_text'));			
		}else{
			Twilio.Device.connect();
			connected = true;
			callme_widget.data('standard_text',callme_widget.html()),
			callme_widget.html("Click to Disconnect");
		}
	}
	$('#callme_widget').click(function(event) {
		call();
	});
});
