/**
 * Toolbox Vacation editor JavaScript.
 *
 * Handles the HTML/plain-text editor toggle and optional datepickers.
 * Copyright (C) Gianluca Giacometti
 * GPL-3.0-or-later (same license as the original Toolbox script).
 */

"use strict";

$(document).ready(function() {
    if (!window.rcmail) {
        return;
    }

    rcmail.addEventListener('init', function() {
        if (rcmail.env.cur_section !== 'vacation' ||
            (rcmail.env.action !== 'plugin.toolbox.edit' &&
             rcmail.env.action !== 'plugin.toolbox.add')) {
            return;
        }

        // The module exposes a format only when the calendar option is enabled.
        var calendarFormat = rcmail.env.toolbox_vacation_calendar_format;
        if (typeof calendarFormat === 'string' && typeof $.fn.datepicker === 'function') {
            $('#rcmfd_vacationactivefrom, #rcmfd_vacationactiveuntil').datepicker({
                dateFormat: calendarFormat,
                changeMonth: true,
                changeYear: true,
                showOtherMonths: true,
                selectOtherMonths: true
            });
        }

        $('#rcmfd_vacationhtmleditor').on("click", function() {
            // make sure the editor is initalised
            if (this.checked && !$('#rcmfd_vacationbody').hasClass('mce_editor')) {
                rcmail.editor_init(rcmail.env.editor_config, 'rcmfd_vacationbody');
            }
            rcmail.command('toggle-editor', {id: 'rcmfd_vacationbody', html: this.checked});
        });

        rcmail.enable_command('toggle-editor', true);
        if ($('.mce_editor').length == 1) {
            rcmail.editor_init(rcmail.env.editor_config, $('.mce_editor').prop('id'));
        }

    });
});

// END OF FILE

