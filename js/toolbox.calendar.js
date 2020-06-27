

$(function() {
	$('#rcmfd_vacationactivefrom').datepicker( {
		dateFormat : calendar_format,
		changeMonth: true,
		changeYear: true,
		showOtherMonths: true,
		selectOtherMonths: true

	});
	$('#rcmfd_vacationactiveuntil').datepicker( {
		dateFormat : calendar_format,
		changeMonth: true,
		changeYear: true,
		showOtherMonths: true,
		selectOtherMonths: true
	});
});
