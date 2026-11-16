/**
 * Toolbox Aliases editor JavaScript.
 *
 * Handles alias add/delete/toggle commands and Elastic custom-switch events.
 *
 * Copyright (C) Gianluca Giacometti
 * GPL-3.0-or-later (same license as the original Toolbox script).
 */

"use strict";

rcube_webmail.prototype.toolbox_toggle_alias = function(aliasname, aliasactive, aliastick) {
    aliasactive.prop('checked', !$(aliasactive).is(':checked'));
    if (aliasactive.prop('checked')) {
        aliastick.prop('title', this.get_label('enabled', 'toolbox')).removeClass('disabled').addClass('enabled');
        aliasname.removeClass('disabled').addClass('enabled');
    }
    else {
        aliastick.prop('title', this.get_label('disabled', 'toolbox')).removeClass('enabled').addClass('disabled');
        aliasname.removeClass('enabled').addClass('disabled');
    }
}

rcube_webmail.prototype.toolbox_delete_alias = function(aliasrow) {
    aliasrow.remove();
}

$(document).ready(function() {
    if (!window.rcmail) {
        return;
    }

    rcmail.toolbox_init_sort_table('#alias-addresses-table');

    rcmail.addEventListener('init', function() {
        if (rcmail.env.cur_section != 'aliases'
            || (rcmail.env.action != 'plugin.toolbox.edit' && rcmail.env.action != 'plugin.toolbox.add')) {
            return;
        }

        // Register Toggle in the same Roundcube init phase as Add and Delete.
        // The server's response is authoritative: never toggle optimistically.
        const pendingAliasToggles = new Map();

        rcmail.addEventListener('plugin.toolbox.alias_toggle_result', function(response) {
            if (!response || typeof response.aliasname !== 'string') {
                return;
            }

            const key = response.aliasname.toLowerCase();
            const aliasRow = pendingAliasToggles.get(key);
            if (!aliasRow) {
                return;
            }
            pendingAliasToggles.delete(key);

            if (response.success !== true) {
                rcmail.display_message(
                    response.error || rcmail.get_label('aliases-aliasupdatederror', 'toolbox'),
                    'warning'
                );
                return;
            }

            const checkbox = aliasRow.find('input[name="_aliasactive[]"]');
            const address = aliasRow.find('td.email');
            const control = aliasRow.find('a.toolbox-alias-toggle');
            const enabled = !checkbox.prop('checked');
            const statusLabel = rcmail.get_label(enabled ? 'toolbox-enabled' : 'toolbox-disabled', 'toolbox');

            checkbox.prop('checked', enabled);
            address.toggleClass('enabled', enabled).toggleClass('disabled', !enabled);
            control.toggleClass('enabled', enabled).toggleClass('disabled', !enabled);
            control.attr({title: statusLabel, 'aria-label': statusLabel});

            rcmail.display_message(
                rcmail.get_label('aliases-aliasupdated', 'toolbox') + ' (' + key + ')',
                'confirmation'
            );
        });

        rcmail.register_command('plugin.toolbox.toggle_alias', function(props, obj) {
            const aliasRow = $(obj).closest('tr');
            const aliasName = aliasRow.find('input[name="_aliasname[]"]').val();
            if (typeof aliasName !== 'string' || !aliasName.trim()) {
                return false;
            }

            const key = aliasName.trim().toLowerCase();
            if (pendingAliasToggles.has(key)) {
                return false;
            }

            pendingAliasToggles.set(key, aliasRow);
            rcmail.http_post('plugin.toolbox.toggle', {
                _section: 'aliases',
                _aliasname: aliasName
            });
            return false;
        }, true);

        // Elastic renders the checkbox as a custom switch. Its label
        // stops click propagation, so the sibling link never receives
        // clicks on the switch. Listen to the checkbox change instead.
        // Delegate so rows inserted later are handled the same way.
        $(document).on('change.toolboxAliasToggle',
            '#alias-addresses-table tbody input[name="_aliasactive[]"]',
            function() {
                const checkbox = $(this);
                const aliasRow = checkbox.closest('tr');
                const commandLink = aliasRow.find('a.toolbox-alias-toggle')[0];

                // The server owns the state: undo the browser's
                // immediate check/uncheck until AJAX confirms success.
                checkbox.prop('checked', !checkbox.prop('checked'));

                if (commandLink) {
                    rcmail.command('plugin.toolbox.toggle_alias', '', commandLink);
                }
            }
        );

        rcmail.register_command('plugin.toolbox.delete_alias', function(aliasname_, obj) {
            let aliasrow = $(obj).closest('tr');
            let aliasname = $(aliasrow).find('input[name^=_aliasname]').val();
            rcmail.confirm_dialog(rcmail.get_label('aliases-aliasdeleteconfirm','toolbox')+'<br><br><span style="font-weight: bold;">'+aliasname+'</span>', 'delete', function(e, ref) {
                rcmail.addEventListener('plugin.toolbox.check_delete', checkdelete);
                rcmail.http_post('plugin.toolbox.delete', { _section: rcmail.env.cur_section, _aliasname: aliasname });
                function checkdelete(response) {
                    if (response != "") {
                        rcmail.display_message(response, 'warning');
                        return false;
                    }
                    else {
                        rcmail.display_message(rcmail.get_label('aliases-aliasdeleted','toolbox'), 'confirmation');
                        rcmail.toolbox_delete_alias($(aliasrow));
                    }
                }
            });
            return false;
        }, true);

        rcmail.register_command('plugin.toolbox.add_alias', function() {
            var input_aliasname = $('input[name=_newaliasname]');
            // remove some invalid characters from the end of the input
            var input_newaliasname = input_aliasname.val().trim().replace(/[\s\t.,;]+$/, '');
            if (input_aliasname && input_newaliasname == '') {
                rcmail.display_message(rcmail.get_label('aliases-novalidalias','toolbox'), 'warning');
                input_aliasname.addClass(rcmail.env.toolbox_input_error_class);
                input_aliasname.focus();
                return false;
            }
            $('input[name^=_aliasname').each(function() {
                if (input_newaliasname == $(this).val()) {
                    rcmail.display_message(rcmail.get_label('aliases-aliasexists','toolbox'), 'warning');
                    input_aliasname.addClass(rcmail.env.toolbox_input_error_class);
                    input_aliasname.focus();
                    return false;
                }
            });
            rcmail.addEventListener('plugin.toolbox.check_alias', checkalias);
            rcmail.http_post('plugin.toolbox.check', { _section: rcmail.env.cur_section, _newaliasname: input_newaliasname });
            function checkalias(response) {
                if (response != "") {
                    rcmail.display_message(response, 'warning');
                    input_aliasname.addClass(rcmail.env.toolbox_input_error_class);
                    input_aliasname.focus();
                    return false;
                }
                else {
                    rcmail.gui_objects.editform.submit();
                }
            }
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

