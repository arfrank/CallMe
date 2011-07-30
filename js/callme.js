jQuery(document).ready(function($) {
	Twilio.Device.setup(token);

      Twilio.Device.ready(function (device) {
        $("#log").text("Ready");
      });

      Twilio.Device.error(function (error) {
        $("#log").text("Error: " + error.message);
      });

      Twilio.Device.connect(function (conn) {
        $("#log").text("Successfully established call");
      });

      function call() {
        Twilio.Device.connect();
      }
	$('#callme_widget').click(function(event) {
			call();
	});
});
