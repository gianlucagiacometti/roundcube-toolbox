

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

$(document).ready(function() {
    if (window.rcmail) {

        // set table sorting classes
        rcmail.env.toolbox_table_sort_asc = 'sorted-asc';
        rcmail.env.toolbox_table_sort_desc =  'sorted-desc';

        $.each(['#alias-addresses-table', '#forward-addresses-table'], function(idx, id) {
            if ($(id).length == 1) {
                // add classes for sorting
                var sorting_defaults = rcmail.env.table_sort[id];
                $(id).find('thead th').eq(sorting_defaults[0]).addClass(sorting_defaults[1] == "true" ? rcmail.env.toolbox_table_sort_asc : rcmail.env.toolbox_table_sort_desc);

                var temp_table = new rcube_list_widget($(id)[0], {});
                temp_table.init();

                // sort table according to user prefs
                rcmail.toolbox_table_sort(id);
            }

        });

        rcmail.addEventListener('init', function() {
            if (rcmail.env.action == 'plugin.toolbox.add' || rcmail.env.action == 'plugin.toolbox.edit') {

                rcmail.register_command('plugin.toolbox.save', function() { rcmail.gui_objects.editform.submit(); }, true);
                rcmail.enable_command('plugin.toolbox.save', true);

                if (rcmail.env.cur_section == 'customise') {

                    rcmail.register_command('plugin.toolbox.reset_image', function(props, obj) {
                        var inputfile = $(props).next('input');
                        $('#'+$(obj).data('image')).val('0');
                        $(props).attr('src', 'program/resources/blank.gif');
                        return false
                    }, true);

                    $('.mce_editor').each(function() {
                        rcmail.editor_init(window.rcmail_editor_settings, this.id);
                    });
                    var cmeditor = [];
                    $('.tool-skin-additional-css').each(function() {
                        var textArea = document.getElementById(this.id);
                        cmeditor[this.id] = CodeMirror.fromTextArea(textArea, {
                            mode: 'css',
                            styleActiveLine: true,
                            lineNumbers: false,
                        });
                    });

                    $('.customise-blankpage-selector').on("click", function() {
                        if ($(this).is(':checked')) {
                            $('#'+this.id).closest('tr').next('tr').show();
                        }
                        else {
                            $('#'+this.id).closest('tr').next('tr').hide();
                        }
                    });

                    $('.customise-blankpage-skin-selector').on("click", function() {
                        var name = $(this).attr('name');
                        $('.customise-blankpage-skin-selector[name="'+name+'"]').each(function() {
                            $('#'+this.id+'_content').closest('tr').hide();
                        });
                        if ($(this).is(':checked')) {
                            $('#'+this.id+'_content').closest('tr').show();
                        }
                    });

                    $('.customise-additional-css-selector').on("click", function() {
                        if ($(this).is(':checked')) {
                            $('#'+this.id+'_content').closest('tr').show();
                        }
                        else {
                            $('#'+this.id+'_content').closest('tr').hide();
                        }
                    });

                    $('.blankpage-image-upload').each(function() {
                        $('#'+this.id).on("change", function() {
                            var input = this;
                            var img = $(this).data('image');
                            var url = $(this).val();
                            var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
                            if (input.files && input.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg")) {
                                var reader = new FileReader();
                                reader.onload = function (e) {
                                    $('#'+img).attr('src', e.target.result);
                                }
                                reader.readAsDataURL(input.files[0]);
                                $('#'+this.id+'_control').val('0');
                            }
                            else {
                                $('#'+img).attr('src', 'program/resources/blank.gif');
                            }
                        });
                    });

                }

                if (rcmail.env.cur_section == 'aliases') {

                    rcmail.register_command('plugin.toolbox.toggle_alias', function(aliasname, obj) {
                        var aliasrow = $(obj).closest('tr');
                        var aliasname = $(aliasrow).find('input[name^=_aliasname]').val();
                        rcmail.addEventListener('plugin.toolbox.check_toggle', checktoggle);
                        rcmail.http_post('plugin.toolbox.toggle', { _section: rcmail.env.cur_section, _aliasname: aliasname });
                        function checktoggle(response) {
                            if (response != "") {
                                rcmail.display_message(response, 'warning');
                            }
                            else {
                                var name = $(aliasrow).find('td.email');
                                var active = $(aliasrow).find('input[name^=_aliasactive]');
                                rcmail.toolbox_toggle_alias($(name), $(active), $(obj));
                                rcmail.display_message(rcmail.get_label('aliases-aliasupdated','toolbox'), 'confirmation');
                            }
                            return false;
                        }
                        return false;
                    }, true);

                    rcmail.register_command('plugin.toolbox.delete_alias', function(aliasname, obj) {
                        var aliasrow = $(obj).closest('tr');
                        var aliasname = $(aliasrow).find('input[name^=_aliasname]').val();
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
                        input_newaliasname = input_aliasname.val().trim().replace(/[\s\t.,;]+$/, '');
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

                }

                if (rcmail.env.cur_section == 'forward') {

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

                }

                if (rcmail.env.cur_section == 'vacation') {

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

                }

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
