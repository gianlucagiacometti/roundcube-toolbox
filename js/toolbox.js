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

"use strict";

rcube_webmail.prototype.toolbox_table_sort = function(id, idx, asc) {
    if (idx == null) {
        idx = this.env.table_sort[id][0];
        asc = this.env.table_sort[id][1] == "true";
    }

    var table = $(id);
    var rows = table.find('tbody tr:visible').toArray().sort(
        function(a, b) {
            var result;

            a = $(a).children('td').eq(idx).html();
            b = $(b).children('td').eq(idx).html();

            result = asc ? a.localeCompare(b) : b.localeCompare(a);

            return result;
        }
    );

    table.children('tbody').children('tr:visible').remove();
    for (var i = 0; i < rows.length; i++) {
        table.children('tbody').append(rows[i]);
    }

    // move hidden rows to the bottom of the table
    table.children('tbody').children('tr:hidden').appendTo(table.children('tbody'));
}

/**
 * Set up sorting for the table identified by the calling tool module.
 *
 * @param {string} id CSS selector for the settings table.
 */
rcube_webmail.prototype.toolbox_init_sort_table = function(id) {
    if ($(id).length == 1) {
        // Match the existing Toolbox table initialization behavior.
        var sorting_defaults = this.env.table_sort[id];
        $(id).find('thead th').eq(sorting_defaults[0]).addClass(sorting_defaults[1] == "true" ? this.env.toolbox_table_sort_asc : this.env.toolbox_table_sort_desc);

        var temp_table = new rcube_list_widget($(id)[0], {});
        temp_table.init();

        this.toolbox_table_sort(id);
    }
}

$(document).ready(function() {
    if (window.rcmail) {
        // set table sorting classes
        rcmail.env.toolbox_table_sort_asc = 'sorted-asc';
        rcmail.env.toolbox_table_sort_desc =  'sorted-desc';

        rcmail.addEventListener('init', function() {
            if (rcmail.env.action == 'plugin.toolbox.add' || rcmail.env.action == 'plugin.toolbox.edit') {

                rcmail.register_command('plugin.toolbox.save', function() { rcmail.gui_objects.editform.submit(); }, true);
                rcmail.enable_command('plugin.toolbox.save', true);

            }
        });

        if (rcmail.env.action == 'plugin.toolbox') {
            rcmail.section_select = function(list) {
                var win, id = list.get_single_selection();

                if (id && (win = this.get_frame_window(this.env.contentframe))) {
                    this.location_href({_action: 'plugin.toolbox.edit', _section: id, _framed: 1}, win, true);
                }

            }
        }


    }
});

// END OF FILE

