/**
 * Toolbox Customise editor JavaScript.
 *
 * Moves the existing domain/skin controls, CodeMirror, TinyMCE,
 * image previews and logo management out of the shared Toolbox script.
 * Copyright (C) Gianluca Giacometti
 * GPL-3.0-or-later (same license as the original Toolbox script).
 */

"use strict";

rcube_webmail.prototype.toolbox_insert_customised_logo_row = function(p) {
    var error = false;
    var logotemplates = [];
    var logotypes = [];
    $.each($('input[name="_customisedlogotemplate_'+p.skin+'[]"]'), function() {
        logotemplates.push($(this).val());
    });
    $.each($('input[name="_customisedlogotype_'+p.skin+'[]"]'), function() {
        logotypes.push($(this).val());
    });
    var logos = [];
    $(logotemplates).each(function(key, i) {
        if (i != '') {
            logos.push({'template': i, 'type': logotypes[key]});
        }
    });
    $(logos).each(function(key, logo) {
        if (logo.template == p.template && logo.type == p.type) {
            error = true;
            return false;
        }
    });
    if (error)
        return false;

    var logoTable = $('#customised-logo-table-'+p.skin+' tbody');
    var new_row = $(logoTable).children('tr.newlogo').clone();
    new_row.removeClass('newlogo').addClass('logo');
    new_row.children('td').eq(0).text(p.template_text);
    new_row.children('td').eq(1).text(p.type_text);
    new_row.children('td').eq(2).children('img').first().attr('src', p.image);
    new_row.find('input[name="_customisedlogotemplate_'+p.skin+'[]"]').val(p.template);
    new_row.find('input[name="_customisedlogotype_'+p.skin+'[]"]').val(p.type);
    new_row.find('input[name="_customisedlogoimage_'+p.skin+'[]"]').val(p.image);
    $(new_row).show().appendTo('#customised-logo-table-'+p.skin+' tbody');

    $(logoTable).children('tr.nologo').hide();

    this.env['customised_logo_count_'+p.skin]++;

    return true;
}

rcube_webmail.prototype.toolbox_delete_customised_logo_row = function(obj) {
    var skin = $(obj).data('skin');
    $(obj).closest('tr').remove();

    this.env['customised_logo_count_'+skin]--;

    if ($('#customised-logo-table-'+skin+' tbody').children('tr:visible').length == 0) {
        $('#customised-logo-table-'+skin+' tbody').children('tr.nologo').show();
    }
}

$(document).ready(function() {
    if (!window.rcmail) {
        return;
    }

    rcmail.addEventListener('init', function() {
        if (rcmail.env.cur_section !== 'customise' ||
            (rcmail.env.action !== 'plugin.toolbox.edit' &&
             rcmail.env.action !== 'plugin.toolbox.add')) {
            return;
        }

        rcmail.register_command('plugin.toolbox.reset_image', function(props, obj) {
            var inputfile = $(props).next('input');
            $('#'+$(obj).data('image')).val('0');
            $(props).attr('src', '/static.php/program/resources/blank.gif');
            return false
        }, true);

        $('.mce_editor').each(function() {
            rcmail.editor_init(null, this.id);
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

        $('.customise-logo-selector').on("click", function() {
            if ($(this).is(':checked')) {
                $('#'+this.id+'_content').show();
            }
            else {
                $('#'+this.id+'_content').hide();
            }
        });

        $('.blankpage-image-upload').each(function() {
            $('#'+this.id).on("change", function() {
                var input = this;
                var img = $(this).data('image');
                var url = $(this).val();
                var ext = url.substring(url.lastIndexOf('.') + 1).toLowerCase();
                if (input.files && input.files[0] && (ext == "gif" || ext == "png" || ext == "jpeg" || ext == "jpg" || ext == "svg")) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        $('#'+img).attr('src', e.target.result);
                    }
                    reader.readAsDataURL(input.files[0]);
                    $('#'+this.id+'_control').val('0');
                }
                else {
                    $('#'+img).attr('src', '/static.php/program/resources/blank.gif');
                }
            });
        });

        rcmail.register_command('plugin.toolbox.add_customised_logo', function(customisedlogo, obj) {
            var skin = $(obj).data('skin');
            var input_customisedlogoimage = $('#rcmfd_newcustomisedlogoimage_'+skin);
            if (input_customisedlogoimage.val()) {
                var newLogoImage = new FileReader();
                newLogoImage.readAsDataURL(input_customisedlogoimage.get(0).files[0]);
                newLogoImage.onload = function() {
                    var input_logoimage = newLogoImage.result;
                    var input_customisedlogotemplate = $('#rcmfd_newcustomisedlogotemplate_'+skin);
                    var input_customisedlogotype = $('#rcmfd_newcustomisedlogotype_'+skin);
                    var input_logotemplate = $('#rcmfd_newcustomisedlogotemplate_'+skin+' option:selected').val();
                    var input_logotemplate_text = $('#rcmfd_newcustomisedlogotemplate_'+skin+' option:selected').text();
                    var input_logotype = $('#rcmfd_newcustomisedlogotype_'+skin+' option:selected').val();
                    var input_logotype_text = $('#rcmfd_newcustomisedlogotype_'+skin+' option:selected').text();
                    if (input_logotemplate == '') {
                        rcmail.display_message(rcmail.get_label('customise-logo-emptycustomisedlogotemplate','toolbox'), 'warning');
                        input_customisedlogotemplate.addClass(rcmail.env.toolbox_input_error_class);
                        input_customisedlogotemplate.focus();
                        return false;
                    }
                    else if (input_logoimage == '') {
                        rcmail.display_message(rcmail.get_label('customise-logo-emptycustomisedlogoimage','toolbox'), 'warning');
                        input_customisedlogoimage.addClass(rcmail.env.toolbox_input_error_class);
                        input_customisedlogoimage.focus();
                        return false;
                    }
                    else {
                        if (!rcmail.toolbox_insert_customised_logo_row({'skin': skin, 'template': input_logotemplate, 'template_text': input_logotemplate_text, 'type': input_logotype, 'type_text': input_logotype_text, 'image': input_logoimage})) {
                            rcmail.display_message(rcmail.get_label('customise-logo-customisedlogoexists','toolbox'), 'warning');
                            input_customisedlogotype.addClass(rcmail.env.toolbox_input_error_class);
                            input_customisedlogotype.focus();
                            return false;
                        }
                        else {
                            input_customisedlogotemplate.removeClass(rcmail.env.toolbox_input_error_class);
                            input_customisedlogotype.removeClass(rcmail.env.toolbox_input_error_class);
                            input_customisedlogoimage.removeClass(rcmail.env.toolbox_input_error_class);
                            input_customisedlogotemplate.val('*');
                            input_customisedlogotype.val('');
                            input_customisedlogoimage.val(null);
                            input_customisedlogoimage.siblings('label').html(rcmail.get_label('choosefile'));
                        }
                    }
                };
                newLogoImage.onerror = function() {
                    rcmail.display_message(rcmail.get_label('customise-logo-invalidcustomisedlogoimage','toolbox'), 'warning');
                    input_customisedlogoimage.addClass(rcmail.env.toolbox_input_error_class);
                    input_customisedlogoimage.focus();
                    return false;
                };
            }
            else {
                rcmail.display_message(rcmail.get_label('customise-logo-emptycustomisedlogoimage','toolbox'), 'warning');
                input_customisedlogoimage.addClass(rcmail.env.toolbox_input_error_class);
                input_customisedlogoimage.focus();
                return false;
            }
        }, true);

        rcmail.register_command('plugin.toolbox.delete_customised_logo', function(props, obj) {
            rcmail.confirm_dialog(rcmail.get_label('customise-logo-deletecustomisedlogo','toolbox'), 'delete', function(e, ref) {
                    ref.toolbox_delete_customised_logo_row(obj);
                });
            return false;
        }, true);

        rcmail.register_command('plugin.toolbox.delete_all_customisedlogos', function(props, obj) {
            rcmail.confirm_dialog(rcmail.get_label('customise-logo-deleteallcustomisedlogos','toolbox'), 'delete', function(e, ref) {
                    $.each($('#customised-logo-table-'+$(obj).data('skin')+' tbody tr:visible'), function() { ref.toolbox_delete_customised_logo_row(this); });
                });
            return false;
        }, true);
    });
});

// END OF FILE

