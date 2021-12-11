/**
 * Toolbox plugin script
 *
 * @licstart  The following is the entire license notice for the
 * JavaScript code in this file.
 *
 * Copyright (C) Gianluca Giacometti
 *
 * The JavaScript code in this page is free software: you can redistribute it
 * and/or modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation, either version 3 of
 * the License, or (at your option) any later version.
 *
 * @licend  The above is the entire license notice
 * for the JavaScript code in this file.
 */

let calendar_format = window.calendar_format || 'dd/mm/yy';

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
