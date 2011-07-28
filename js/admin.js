jQuery(document).ready(function($) {
	$('#callme_widget_type').change(function(event) {
			that = $(this);
			$('.selected_widget_inputs').each(function(index) {
				if ($(this).is(":visible")) {
					$(this).fadeOut(400, function() {
						that.removeClass('.selected_widget_inputs');
						$('#'+that.val()).fadeIn().addClass('.selected_widget_inputs');
					});
					
				}
			});
	});
});

