/**
 * Toolbox Tags: manage parent tag names and nested values in the settings UI.
 *
 * Actions submit the existing Roundcube settings form with its CSRF token.
 * No message flags are modified by this editor.
 */

"use strict";

$(function () {
    const labels = (window.rcmail && window.rcmail.env && window.rcmail.env.toolbox_tags_labels) || {};

    // Use the strings exported by the server for the current Roundcube language.
    function tagText(key) {
        return Object.prototype.hasOwnProperty.call(labels, key) ? labels[key] : key;
    }

    const manager = $('#toolbox-tags-manager');
    if (!manager.length) {
        return;
    }

    const form = manager.closest('form')[0];
    const editor = $('#toolbox-tags-editor');
    const title = $('#toolbox-tags-editor-title');
    const parentLabel = $('#toolbox-tags-parent-label');
    const actionInput = $('#rcmfd_tags_action');
    const idInput = $('#rcmfd_tags_id');
    const groupInput = $('#rcmfd_tags_group_id');
    const nameInput = $('#rcmfd_tags_name');
    const valueInput = $('#rcmfd_tags_value');
    const colorInput = $('#rcmfd_tags_color');
    const nameRow = $('#toolbox-tags-name-row');
    const valueRow = $('#toolbox-tags-value-row');
    const colorRow = $('#toolbox-tags-color-row');

    if (!form) {
        return;
    }

    // Reset the editor before switching between group and value operations.
    function resetEditor() {
        actionInput.val('');
        idInput.val('');
        groupInput.val('');
        nameInput.val('').prop('required', false);
        valueInput.val('').prop('required', false);
        colorInput.val('#6478C4');
        nameRow.prop('hidden', true);
        valueRow.prop('hidden', true);
        colorRow.prop('hidden', true);
        parentLabel.text('').prop('hidden', true);
        editor.prop('hidden', true);
    }

    // Open the appropriate inline editor with existing values when editing.
    function openEditor(action, data) {
        resetEditor();
        actionInput.val(action);
        idInput.val(data.id || '');
        groupInput.val(data.group || '');

        const isGroup = action === 'create_group' || action === 'rename_group';
        nameRow.prop('hidden', !isGroup);
        valueRow.prop('hidden', isGroup);
        colorRow.prop('hidden', isGroup);
        nameInput.prop('required', isGroup).val(data.name || '');
        valueInput.prop('required', !isGroup).val(data.value || '');
        colorInput.val(data.color || '#6478C4');

        if (action === 'create_group') {
            title.text(tagText('tags-newname'));
        } else if (action === 'rename_group') {
            title.text(tagText('tags-editnametitle'));
        } else if (action === 'create_value') {
            title.text(tagText('tags-newvalue'));
        } else {
            title.text(tagText('tags-editvalue'));
        }

        if (!isGroup) {
            parentLabel.text(tagText('tags-parentprefix') + (data.name || '')).prop('hidden', false);
        }
        editor.prop('hidden', false);
        (isGroup ? nameInput : valueInput).trigger('focus');
    }

    // Submit a non-edit operation immediately, with all editor validation off.
    function submitAction(action, id) {
        resetEditor();
        actionInput.val(action);
        idInput.val(id);
        if (typeof form.requestSubmit === 'function') {
            form.requestSubmit();
        } else {
            form.submit();
        }
    }

    manager.on('click', '[data-tags-action]', function (event) {
        event.preventDefault();
        const button = $(this);
        const action = button.attr('data-tags-action');
        const id = button.attr('data-tags-id') || '';
        const name = button.attr('data-tags-name') || '';
        const value = button.attr('data-tags-value') || '';

        switch (action) {
            case 'new-group':
                openEditor('create_group', {});
                break;
            case 'edit-group':
                openEditor('rename_group', {id: id, name: name});
                break;
            case 'new-value':
                openEditor('create_value', {
                    group: button.attr('data-tags-group') || '',
                    name: button.attr('data-tags-group-name') || ''
                });
                break;
            case 'edit-value':
                openEditor('update_value', {
                    id: id,
                    name: name,
                    value: value,
                    color: button.attr('data-tags-color') || '#6478C4'
                });
                break;
            case 'delete-group':
                if (window.confirm(tagText('tags-confirmdeletegroup').replace('{name}', () => name)
                    + '\n' + tagText('tags-deleteirreversible'))) {
                    submitAction('delete_group', id);
                }
                break;
            case 'delete-value':
                if (window.confirm(tagText('tags-confirmdeletevalue')
                    .replace('{value}', () => value).replace('{name}', () => name)
                    + '\n' + tagText('tags-deleteirreversible'))) {
                    submitAction('delete_value', id);
                }
                break;
            case 'cancel':
                resetEditor();
                break;
        }
    });

    resetEditor();
});

// END OF FILE

