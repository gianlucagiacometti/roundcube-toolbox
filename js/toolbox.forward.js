/**
 * Toolbox Forward editor JavaScript.
 *
 * Handles forward-address controls, keep-copies validation, and sorting.
 * Copyright (C) Gianluca Giacometti
 * GPL-3.0-or-later (same license as the original Toolbox script).
 */

"use strict";

rcube_webmail.prototype.toolbox_insert_forward_address_row = function(p) {
    var error = false;
    $.each($('input[name="_forwardaddresses[]"]'), function() {
        if ($(this).val() == p.address) {
            error = true;
            return false;
        }
    });
    if (error)
        return false;

    var adrTable = $('#forward-addresses-table tbody');
    var new_row = $(adrTable).children('tr.newaddress').clone();
    new_row.removeClass('newaddress').addClass('forward');
    new_row.children('td').eq(0).text(p.address);
    new_row.find('input[name="_forwardaddresses[]"]').val(p.address);
    $(new_row).show().appendTo('#forward-addresses-table tbody');

    $(adrTable).children('tr.noaddress').hide();

    this.env.forward_address_count++;
    this.toolbox_table_sort('#forward-addresses-table');

    return true;
}

rcube_webmail.prototype.toolbox_delete_forward_address_row = function(obj) {
    $(obj).closest('tr').remove();

    this.env.forward_address_count--;

    if ($('#forward-addresses-table tbody').children('tr:visible').length == 0) {
        $('#forward-addresses-table tbody').children('tr.noaddress').show();
        $('#rcmfd_keepcopies').prop('checked', true);
    }
}

$(document).ready(function() {
    if (!window.rcmail) {
        return;
    }

    // Use Toolbox's shared table-sort helper, as Aliases already does.
    rcmail.toolbox_init_sort_table('#forward-addresses-table');

    rcmail.addEventListener('init', function() {
        if (rcmail.env.cur_section != 'forward'
            || (rcmail.env.action != 'plugin.toolbox.edit' && rcmail.env.action != 'plugin.toolbox.add')) {
            return;
        }

        $('#rcmfd_keepcopies').on("click", function() {
            if (($('#forward-addresses-table tbody').children('tr.forward').length == 0) && !($(this).is(':checked'))){
                rcmail.alert_dialog(rcmail.get_label('forward-atleastoneaddress','toolbox'));
                $(this).prop('checked', true);
            }
        });

        rcmail.register_command('plugin.toolbox.delete_forward_address', function(props, obj) {
            rcmail.confirm_dialog(rcmail.get_label('forward-deleteaddress','toolbox'), 'delete', function(e, ref) {
                    ref.toolbox_delete_forward_address_row(obj);
                });
            return false;
        }, true);

        rcmail.register_command('plugin.toolbox.add_forward_address', function() {
            var input_forward = $('#rcmfd_forwardaddress');
            // remove some invalid characters from the end of the input
            var input_value = input_forward.val().trim().replace(/[\s\t.,;]+$/, '');
            if (input_value == '') {
                rcmail.display_message(rcmail.get_label('forward-emptyaddress','toolbox'), 'warning');
                input_forward.addClass(rcmail.env.toolbox_input_error_class);
                input_forward.focus();
                return false;
            }
            else if (!rcube_check_email(input_value, false)) {
                rcmail.display_message(rcmail.get_label('forward-invalidaddress','toolbox'), 'warning');
                input_forward.addClass(rcmail.env.toolbox_input_error_class);
                input_forward.focus();
                return false;
            }
            else {
                if (!rcmail.toolbox_insert_forward_address_row({'address': input_value})) {
                    rcmail.display_message(rcmail.get_label('forward-addressexists','toolbox'), 'warning');
                    input_forward.addClass(rcmail.env.toolbox_input_error_class);
                    input_forward.focus();
                    return false;
                }
                else {
                    input_forward.removeClass(rcmail.env.toolbox_input_error_class);
                    input_forward.val('');
                }
            }
        }, true);

        rcmail.register_command('plugin.toolbox.delete_all_addresses', function() {
            rcmail.confirm_dialog(rcmail.get_label('forward-deletealladdresses','toolbox'), 'delete', function(e, ref) {
                    $.each($('#forward-addresses-table tbody tr:visible'), function() { ref.toolbox_delete_forward_address_row(this); });
                });
            return false;
        }, true);

        rcmail.register_command('plugin.toolbox.table_sort', function(props, obj) {
            var id = props;
            var idx = $(obj).parent('th').index();
            var asc = !$(obj).parent('th').hasClass(rcmail.env.toolbox_table_sort_asc);

            rcmail.toolbox_table_sort(id, idx, asc);

            $(obj).parents('thead:first').find('th').removeClass(rcmail.env.toolbox_table_sort_asc).removeClass(rcmail.env.toolbox_table_sort_desc);
            $(obj).parent('th').addClass(asc ? rcmail.env.toolbox_table_sort_asc : rcmail.env.toolbox_table_sort_desc);

            rcmail.env.table_sort[id] = [idx, asc];
            rcmail.save_pref({name: 'table_sort', value: rcmail.env.table_sort, env: true});

            return false;
        }, true);
    });
});

// END OF FILE
