jQuery(document).ready(function($) {
	$( "#date-picker" ).datepicker({
		changeMonth: true,
		changeYear: true,
		dateFormat: "mm/dd/yy",
		minDate: +1
	});

});