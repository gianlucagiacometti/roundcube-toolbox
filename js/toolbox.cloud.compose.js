/**
 * Toolbox Cloud Compose file picker.
 *
 * Uses the authenticated Cloud controller and Roundcube's native attachment
 * list. The vault secret is submitted once and never persisted client-side.
 */

"use strict";

(function ($) {
    var requestNumber = 0;
    var pending = null;
    var unlocked = false;
    var accountId = '';
    var folderId = '';
    var nextCursor = null;
    var unlockExpiry = 0;
    var accounts = [];
    var opaqueFolders = [];
    var activeProvider = 'webdav';

    /** Return a string from the server-provided Roundcube Toolbox catalogue. */
    function t(key) {
        var labels = window.rcmail && rcmail.env && rcmail.env.toolbox_cloud_labels;
        return labels && Object.prototype.hasOwnProperty.call(labels, key) ? String(labels[key]) : key;
    }

    /** Substitute one external display name in a translated message. */
    function tf(key, name) {
        return t(key).replace('%s', String(name));
    }

    /** Escape translated text before inserting it into the Compose dialog markup. */
    function th(key) {
        return $('<span>').text(t(key)).html().replace(/"/g, '&quot;');
    }

    /** Display success and failure through Roundcube's native toast. */
    function notify(message, isError) {
        if (window.rcmail && typeof rcmail.display_message === 'function') {
            rcmail.display_message(String(message), isError ? 'error' : 'confirmation');
        }
    }

    /** Execute one CSRF-protected Cloud request at a time. */
    function request(operation, fields) {
        if (pending) {
            return false;
        }
        requestNumber += 1;
        var id = 'cloud-compose-' + requestNumber;
        pending = { id: id, operation: operation };
        $('#tbx-cloud-compose-dialog button, #tbx-cloud-compose-dialog select').prop('disabled', true);
        try {
            rcmail.http_post('plugin.toolbox.cloud', $.extend({}, fields || {}, {
                _operation: operation,
                _request_id: id
            }));
        } catch (error) {
            pending = null;
            enableControls();
            notify(t('compose-send-failed'), true);
            return false;
        }
        return true;
    }

    /** Reinstate controls when no AJAX operation is pending. */
    function enableControls() {
        $('#tbx-cloud-compose-dialog button').prop('disabled', false);
        $('#tbx-cloud-compose-account').prop('disabled', !unlocked);
        // Each request temporarily disables every select; restore the unlock selector.
        $('#tbx-cloud-compose-method').prop('disabled', unlocked);
        $('#tbx-cloud-compose-unlock').prop('disabled', unlocked);
        $('#tbx-cloud-compose-unlock').prop('hidden', unlocked);
        $('#tbx-cloud-compose-locked').prop('hidden', unlocked);
        $('#tbx-cloud-compose-browser').prop('hidden', !unlocked);
    }

    /** Render account names safely as text-only option labels. */
    function renderAccounts(rows) {
        accounts = Array.isArray(rows) ? rows.filter(function (account) {
            return account && ['webdav', 'google_drive', 'onedrive', 'onedrive_personal', 'dropbox'].includes(account.provider) && /^[1-9][0-9]{0,18}$/.test(String(account.id || ''));
        }) : [];
        var select = $('#tbx-cloud-compose-account').empty();
        $('<option>').val('').text(t('compose-choose-account')).appendTo(select);
        accounts.forEach(function (account) {
            $('<option>').val(String(account.id))
                .text(String(account.display_name || 'Cloud') + ' (' + String(account.provider || 'WebDAV') + ')')
                .appendTo(select);
        });
        accountId = '';
        opaqueFolders = [];
        activeProvider = 'webdav';
        folderId = '';
        nextCursor = null;
        $('#tbx-cloud-compose-path').text('/');
        $('#tbx-cloud-compose-files').text(accounts.length ? t('compose-select-account') : t('compose-no-accounts'));
        $('#tbx-cloud-compose-next').prop('hidden', true);
    }

    /** Request a folder listing inside the currently selected account. */
    function browse(folder, cursor) {
        if (!unlocked || !accountId) {
            return;
        }
        folderId = folder;
        var rootName = ['onedrive', 'onedrive_personal'].includes(activeProvider) ? '/OneDrive'
            : activeProvider === 'dropbox' ? '/Dropbox' : '/' + t('my-drive');
        $('#tbx-cloud-compose-path').text(['google_drive', 'onedrive', 'onedrive_personal', 'dropbox'].includes(activeProvider)
            ? rootName + (opaqueFolders.length ? '/' + opaqueFolders.map(function (f) { return f.name; }).join('/') : '')
            : '/' + folderId);
        $('#tbx-cloud-compose-up').prop('disabled', !folderId);
        $('#tbx-cloud-compose-files').text(t('loading-folder'));
        $('#tbx-cloud-compose-next').prop('hidden', true);
        request('list_files', {
            _account_id: accountId,
            _folder_id: folderId,
            _cursor: cursor || ''
        });
    }

    /** Draw a paginated list using safe DOM methods, never remote HTML. */
    function renderListing(listing) {
        var list = $('#tbx-cloud-compose-files').empty();
        var entries = listing && Array.isArray(listing.items) ? listing.items : [];
        if (!entries.length) {
            list.text(t('folder-empty'));
        }
        entries.forEach(function (entry) {
            if (!entry || typeof entry.id !== 'string' || typeof entry.name !== 'string') {
                return;
            }
            var row = $('<div>').addClass('tbx-cloud-compose-entry').appendTo(list);
            if (entry.is_dir) {
                $('<button type="button">').addClass('button btn btn-secondary')
                    .text('📁 ' + entry.name).attr('data-cloud-folder', entry.id).attr('data-cloud-name', entry.name).appendTo(row);
            } else {
                var size = entry.size == null ? NaN : Number(entry.size);
                $('<span>').text('📄 ' + entry.name + (Number.isSafeInteger(size) && size >= 0
                    ? ' (' + size.toLocaleString() + ' ' + t('bytes') + ')' : '')).appendTo(row);
                $('<button type="button">').addClass('button btn btn-primary')
                    .text(t('compose-attach')).attr({ 'data-cloud-file': entry.id, 'aria-label': tf('compose-attach-named', entry.name) })
                    .appendTo(row);
            }
        });
        nextCursor = listing && typeof listing.next_cursor === 'string' ? listing.next_cursor : null;
        $('#tbx-cloud-compose-next').prop('hidden', !nextCursor);
    }

    /** Register the cloud file in Roundcube's standard compose attachment list. */
    function showAttachment(file) {
        if (!file || !/^[a-zA-Z0-9_-]+$/.test(String(file.id || ''))) {
            notify(t('compose-invalid-id'), true);
            return;
        }
        var id = 'rcmfile' + String(file.id);
        var name = String(file.name || t('compose-attachment-default'));
        var mime = String(file.mimetype || 'application/octet-stream');
        var size = Number(file.size || 0);
        var safeName = $('<span>').text(name).html();
        var safeId = String(file.id);
        var visible = '<a href="#load" class="filename" onclick="return rcmail.command(\'load-attachment\',\'' + id + '\',this,event)">' +
            '<span class="attachment-name">' + safeName + '</span>' +
            '<span class="attachment-size">(' + (size >= 0 ? size.toLocaleString() : '?') + ' ' + th('bytes') + ')</span></a>' +
            '<a href="#delete" class="delete" aria-label="' + th('compose-remove-attachment') + '" title="' + th('compose-delete') + '" onclick="return rcmail.command(\'remove-attachment\',\'' + id + '\',this,event)"></a>';
        if (typeof rcmail.add2attachment_list === 'function') {
            rcmail.add2attachment_list(id, {
                html: visible,
                name: name,
                mimetype: mime,
                classname: 'file',
                complete: true
            });
        }
        notify(tf('compose-attached', name), false);
    }

    /** Handle only the response matching this Compose picker request. */
    function response(message) {
        if (!pending || !message || message.request_id !== pending.id || message.operation !== pending.operation) {
            return;
        }
        var operation = pending.operation;
        pending = null;
        if (!message.ok) {
            if (message.locked) {
                unlocked = false;
                renderAccounts([]);
            }
            enableControls();
            notify(message.error || t('compose-request-failed'), true);
            return;
        }
        var data = message.data || {};
        unlockExpiry = Number(message.session_expires || 0);
        if (operation === 'status') {
            if (!data.has_vault) {
                $('#tbx-cloud-compose-locked').text(t('compose-create-vault'));
            }
            unlocked = !!data.unlocked;
            if (data.unlock_methods) {
                var methods = data.unlock_methods;
                $('#tbx-cloud-compose-method option[value="password"]').prop('disabled', !methods.password);
                $('#tbx-cloud-compose-method option[value="recovery"]').prop('disabled', !methods.recovery);
                $('#tbx-cloud-compose-method').val(methods.password ? 'password' : methods.recovery ? 'recovery' : '');
            }
            if (unlocked) {
                request('list_accounts', {});
            }
        } else if (operation === 'unlock') {
            unlocked = true;
            renderAccounts(data.accounts);
            notify(t('vault-unlocked'), false);
        } else if (operation === 'list_accounts') {
            renderAccounts(data.accounts);
        } else if (operation === 'list_files') {
            renderListing(data.listing);
        } else if (operation === 'attach_file') {
            showAttachment(data.attachment);
        }
        enableControls();
    }

    /** Install the picker and keep the Compose message form independent. */
    function install() {
        if (!window.rcmail || rcmail.env.task !== 'mail' || rcmail.env.action !== 'compose'
            || !rcmail.env.compose_id || !$('#compose-attachments .upload-form').length) {
            return;
        }
        if ($('#tbx-cloud-compose-open').length) {
            return;
        }
        var button = $('<button type="button">').attr('id', 'tbx-cloud-compose-open')
            .addClass('button btn btn-secondary').text(t('compose-attach-from-cloud'));
        $('#compose-attachments .upload-form').append(button);
        var dialog = $('<dialog>').attr({ id: 'tbx-cloud-compose-dialog', 'aria-labelledby': 'tbx-cloud-compose-title' })
            .addClass('tbx-cloud-compose-dialog tbx-cloud-provider-dialog');
        dialog.append('<div class="tbx-cloud-dialog-header"><h3 id="tbx-cloud-compose-title">' + th('compose-attach-from-cloud') + '</h3><button type="button" id="tbx-cloud-compose-close" class="button btn btn-secondary">' + th('close') + '</button></div>');
        dialog.append('<div id="tbx-cloud-compose-locked"><p>' + th('compose-unlock-help') + '</p><label for="tbx-cloud-compose-method">' + th('unlock-method') + '</label><select id="tbx-cloud-compose-method" class="form-control"><option value="password">' + th('vault-password') + '</option><option value="recovery">' + th('recovery-key') + '</option></select><label for="tbx-cloud-compose-secret">' + th('compose-secret-label') + '</label><input id="tbx-cloud-compose-secret" class="form-control" type="password" autocomplete="off" maxlength="4096"><button type="button" id="tbx-cloud-compose-unlock" class="button btn btn-primary">' + th('unlock-vault') + '</button></div>');
        dialog.append('<div id="tbx-cloud-compose-browser" hidden><div class="tbx-cloud-compose-account-field"><label for="tbx-cloud-compose-account">' + th('compose-account') + '</label><select class="form-control" id="tbx-cloud-compose-account"></select></div><div class="tbx-cloud-browse-toolbar"><button type="button" id="tbx-cloud-compose-up" class="button btn btn-secondary">' + th('up-folder') + '</button><span id="tbx-cloud-compose-path">/</span></div><div id="tbx-cloud-compose-files" class="tbx-cloud-compose-files" aria-live="polite"></div><button type="button" id="tbx-cloud-compose-next" class="button btn btn-secondary" hidden>' + th('next-entries') + '</button></div>');
        $('body').append(dialog);
        rcmail.addEventListener('plugin.cloud.response', response);
        button.on('click', function () {
            if (typeof dialog[0].showModal !== 'function') {
                notify(t('compose-picker-unsupported'), true);
                return;
            }
            dialog[0].showModal();
            request('status', {});
        });
        dialog.on('click', '#tbx-cloud-compose-close', function () { dialog[0].close(); });
        dialog.on('close', function () { $('#tbx-cloud-compose-secret').val(''); });
        dialog.on('click', '#tbx-cloud-compose-unlock', function () {
            var method = String($('#tbx-cloud-compose-method').val() || '');
            var secret = String($('#tbx-cloud-compose-secret').val() || '');
            $('#tbx-cloud-compose-secret').val('');
            if (!secret || !method) {
                notify(t('compose-secret-required'), true);
                return;
            }
            request('unlock', { _unlock_method: method, _unlock_secret: secret });
        });
        dialog.on('change', '#tbx-cloud-compose-account', function () {
            accountId = String($(this).val() || '');
            if (accountId) {
                var selected = accounts.find(function (a) { return String(a.id) === accountId; });
                activeProvider = selected ? selected.provider : 'webdav';
                opaqueFolders = [];
                browse('', null);
            }
        });
        dialog.on('click', 'button[data-cloud-folder]', function () {
            var id = String($(this).attr('data-cloud-folder') || '');
            if (['google_drive', 'onedrive', 'onedrive_personal', 'dropbox'].includes(activeProvider)) {
                opaqueFolders.push({ id: id, name: String($(this).attr('data-cloud-name') || '') });
            }
            browse(id, null);
        });
        dialog.on('click', '#tbx-cloud-compose-up', function () {
            if (['google_drive', 'onedrive', 'onedrive_personal', 'dropbox'].includes(activeProvider)) {
                opaqueFolders.pop();
                browse(opaqueFolders.length ? opaqueFolders[opaqueFolders.length - 1].id : '', null);
            } else {
                var segments = folderId.split('/').filter(Boolean);
                segments.pop();
                browse(segments.join('/'), null);
            }
        });
        dialog.on('click', '#tbx-cloud-compose-next', function () {
            if (nextCursor) {
                browse(folderId, nextCursor);
            }
        });
        dialog.on('click', 'button[data-cloud-file]', function () {
            var fileId = String($(this).attr('data-cloud-file') || '');
            if (!accountId || !fileId || !rcmail.env.compose_id) {
                return;
            }
            notify(t('compose-importing'), false);
            request('attach_file', {
                _account_id: accountId,
                _file_id: fileId,
                _compose_id: String(rcmail.env.compose_id)
            });
        });
    }

    $(function () {
        if (window.rcmail) {
            rcmail.addEventListener('init', install);
            // Some Roundcube embeds initialize before plugin scripts execute.
            if (rcmail.env && rcmail.env.action === 'compose' && rcmail.gui_objects) {
                install();
            }
        }
    });
}(jQuery));

// END OF FILE

