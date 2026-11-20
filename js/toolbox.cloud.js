/**
 * Toolbox Cloud Settings interface.
 *
 * Passwords and recovery secrets are cleared after unlocking. The server
 * temporarily retains the unwrapped key in the authenticated PHP session.
 * The client holds only the public unlock expiration timestamp in memory.
 * All remote account metadata is rendered as text, never trusted HTML.
 */

"use strict";

(function ($) {
    var nextId = 0;
    var pending = null;
    var activeProvider = null;
    var editingAccountId = null;
    var googlePopup = null;
    var microsoftPopup = null;
    var microsoftPersonalPopup = null;
    var dropboxPopup = null;
    var dropboxChannel = null;
    var microsoftChannel = null;
    var microsoftPersonalChannel = null;
    var googleChannel = null;
    var accountRows = [];
    var unlocked = false;
    var unlockExpires = 0;
    var browsingAccountId = null;
    var browsingFolder = '';
    var browsingCursor = null;
    var browsingHistory = [];
    var browsingProvider = 'webdav';

    /** Return a string from the server-provided Roundcube Toolbox catalogue. */
    function t(key) {
        var labels = window.rcmail && rcmail.env && rcmail.env.toolbox_cloud_labels;
        return labels && Object.prototype.hasOwnProperty.call(labels, key) ? String(labels[key]) : key;
    }

    /** Substitute one external display name in a translated message. */
    function tf(key, name) {
        return t(key).replace('%s', String(name));
    }

    /** Read the single Cloud editor DOM field value. */
    function value(id) {
        return String($(id).val() || '');
    }

    /** Clear the current vault unlock field and any one-time WebDAV password. */
    function clearSecrets() {
        $('#tbx-cloud-unlock-secret, #tbx-cloud-app-password').val('');
    }

    /**
     * Show Cloud result messages through Roundcube's native notification system.
     * No inline error banner is rendered: feedback appears only as a toast.
     * @param {string} message Notification text, never treated as HTML.
     * @param {boolean} failure True for an error toast.
     */
    function feedback(message, failure) {
        var text = String(message || '');
        if (window.rcmail && typeof rcmail.display_message === 'function'
            && text !== t('processing-request')) {
            rcmail.display_message(text, failure ? 'error' : 'confirmation');
        }
    }

    /** Display a one-time recovery key, never saving it to browser storage. */
    function showRecovery(key) {
        $('#tbx-cloud-recovery-value').val(key);
        $('#tbx-cloud-recovery').prop('hidden', false);
    }

    /** Clear and hide the one-time recovery value. */
    function hideRecovery() {
        $('#tbx-cloud-recovery-value').val('');
        $('#tbx-cloud-recovery').prop('hidden', true);
    }

    /** Set locked/unlocked presentation; never store a password or recovery key. */
    function showLockState(isUnlocked, expires) {
        unlocked = !!isUnlocked;
        unlockExpires = unlocked ? Number(expires || 0) : 0;
        $('#tbx-cloud-lock-status').text(unlocked ? t('unlocked') : t('locked'))
            .toggleClass('is-unlocked', unlocked);
        $('#tbx-cloud-lock, #tbx-cloud-renew').prop('hidden', !unlocked);
        $('#tbx-cloud-refresh').prop('hidden', unlocked);
        $('#tbx-cloud-unlock-method, #tbx-cloud-unlock-secret').prop('disabled', unlocked);
        $('#toolbox-cloud-settings .tbx-cloud-provider-card').prop('disabled', !unlocked)
            .attr('aria-disabled', unlocked ? 'false' : 'true');
        $('#tbx-cloud-add').prop('disabled', !unlocked);
        if (!unlocked) {
            accountRows = [];
            $('#tbx-cloud-lock-expiry').text(t('unlock-connections'));
            $('#tbx-cloud-accounts').text(t('unlock-to-list'));
            closeProvider();
            closeBrowse();
        }
        updateLockCountdown();
    }

    /** Display time remaining without trusting this countdown for authorization. */
    function updateLockCountdown() {
        if (!unlocked) {
            return;
        }
        var remaining = Math.max(0, unlockExpires - Math.floor(Date.now() / 1000));
        $('#tbx-cloud-lock-expiry').text(remaining > 0
            ? tf('automatic-lock-in', Math.floor(remaining / 60) + ':' + String(remaining % 60).padStart(2, '0'))
            : t('unlock-expired'));
        if (remaining === 0) {
            showLockState(false, 0);
            if (pending === null) {
                send('lock', {});
            }
        }
    }

    /** Toggle the two states of the Cloud Settings editor. */
    function showVaultState(hasVault) {
        $('#tbx-cloud-create').prop('hidden', !!hasVault);
        $('#tbx-cloud-existing').prop('hidden', !hasVault);
    }

    /**
     * Submit one Cloud operation through Roundcube's native authenticated POST.
     * @param {string} operation Server-side Cloud operation.
     * @param {Object} fields Additional POST fields, including unlock secret.
     */
    function send(operation, fields) {
        if (pending !== null) {
            feedback(t('request-in-progress'), true);
            return false;
        }
        nextId += 1;
        var id = 'cloud-' + nextId;
        var data = $.extend({}, fields || {}, {
            _operation: operation,
            _request_id: id
        });
        pending = { id: id, operation: operation };
        $('#toolbox-cloud-settings button').prop('disabled', true);
        feedback(t('processing-request'), false);
        try {
            // http_post supplies Roundcube's normal request token.
            rcmail.http_post('plugin.toolbox.cloud', data);
        } catch (e) {
            pending = null;
            $('#toolbox-cloud-settings button').prop('disabled', false);
            showLockState(unlocked, unlockExpires);
            feedback(t('request-send-failed'), true);
            return false;
        }
        return true;
    }

    /** Get unlock fields for a single request; erase input immediately. */
    function unlockFields() {
        var secret = value('#tbx-cloud-unlock-secret');
        var method = value('#tbx-cloud-unlock-method');
        $('#tbx-cloud-unlock-secret').val('');
        if (!secret) {
            feedback(t('unlock-secret-required'), true);
            return null;
        }
        return { _unlock_method: method, _unlock_secret: secret };
    }

    /**
     * Disable unavailable unlock choices and select one the vault can use.
     * Capability flags are display hints; the server still validates unlocks.
     * @param {Object} methods Available password and recovery methods.
     */
    function configureUnlockMethods(methods) {
        var password = !!(methods && methods.password);
        var recovery = !!(methods && methods.recovery);
        var select = $('#tbx-cloud-unlock-method');
        select.find('option[value="password"]').prop('disabled', !password);
        select.find('option[value="recovery"]').prop('disabled', !recovery);
        var selected = select.val();
        if (!selected || (selected === 'password' && !password) || (selected === 'recovery' && !recovery)) {
            select.val(password ? 'password' : recovery ? 'recovery' : '');
        }
        updateUnlockHelp();
    }

    /** Match the unlock label and placeholder to the user's selected method. */
    function updateUnlockHelp() {
        var method = value('#tbx-cloud-unlock-method');
        var recovery = method === 'recovery';
        $('label[for="tbx-cloud-unlock-secret"]').text(recovery ? t('recovery-key') : t('vault-password'));
        $('#tbx-cloud-unlock-secret').attr('placeholder', recovery ? t('paste-recovery') : t('enter-vault-password'));
        $('#tbx-cloud-unlock-secret').val('');
    }

    /** Clear cloud connection inputs when a dialog is dismissed. */
    function clearConnection() {
        $('#tbx-cloud-name, #tbx-cloud-endpoint, #tbx-cloud-username, #tbx-cloud-app-password').val('');
        $('#tbx-cloud-username, #tbx-cloud-app-password').attr('placeholder', '');
    }

    /** Open a provider-specific WebDAV connection dialog, without constructing a URL. */
    function openProvider(provider) {
        var providers = {
            nextcloud: ['Nextcloud', t('nextcloud-webdav-help')],
            owncloud: ['ownCloud', t('owncloud-webdav-help')],
            webdav: [t('generic-webdav'), t('webdav-url-help')]
        };
        var dialog = document.getElementById('tbx-cloud-provider-dialog');
        if (!dialog || !Object.prototype.hasOwnProperty.call(providers, provider) || typeof dialog.showModal !== 'function') {
            feedback(t('dialog-unsupported'), true);
            return;
        }
        if (!unlocked) {
            feedback(t('unlock-cloud-first'), true);
            return;
        }
        activeProvider = provider;
        editingAccountId = null;
        clearConnection();
        $('#tbx-cloud-add').text(t('add-account'));
        $('#tbx-cloud-credential-guidance').text(t('connection-needs-unlock'));
        $('#tbx-cloud-provider-dialog-title').text(tf('connect-named', providers[provider][0]));
        $('#tbx-cloud-provider-dialog-help').text(providers[provider][1]);
        $('#tbx-cloud-name').attr('placeholder', tf('my-named', providers[provider][0]));
        dialog.showModal();
        $('#tbx-cloud-name').trigger('focus');
    }

    /** Close the dialog and erase any remote-account credentials. */
    function closeProvider() {
        var dialog = document.getElementById('tbx-cloud-provider-dialog');
        if (dialog && dialog.open) {
            dialog.close();
        }
        clearConnection();
        activeProvider = null;
        editingAccountId = null;
    }

    /** Open the existing dialog to edit owned account metadata and credentials. */
    function editAccount(id) {
        if (!unlocked) {
            feedback(t('unlock-cloud-first'), true);
            return;
        }
        var account = accountRows.find(function (row) { return String(row.id) === id; });
        if (!account || !/^[1-9][0-9]{0,18}$/.test(id)) {
            feedback(t('account-unavailable'), true);
            return;
        }
        var dialog = document.getElementById('tbx-cloud-provider-dialog');
        if (!dialog || typeof dialog.showModal !== 'function') {
            feedback(t('dialog-unsupported'), true);
            return;
        }
        clearConnection();
        editingAccountId = id;
        activeProvider = 'webdav';
        $('#tbx-cloud-provider-dialog-title').text(tf('edit-named', account.display_name || t('cloud-account-default')));
        $('#tbx-cloud-provider-dialog-help').text(t('edit-webdav-help'));
        $('#tbx-cloud-name').val(String(account.display_name || ''));
        $('#tbx-cloud-endpoint').val(String(account.endpoint || ''));
        $('#tbx-cloud-username').attr('placeholder', t('keep-username'));
        $('#tbx-cloud-app-password').attr('placeholder', t('keep-app-password'));
        $('#tbx-cloud-credential-guidance').text(t('keep-credentials-help'));
        $('#tbx-cloud-add').text(t('save-changes'));
        dialog.showModal();
        $('#tbx-cloud-name').trigger('focus');
    }

    /** Close the read-only WebDAV browser and discard its current selection. */
    function closeBrowse() {
        var dialog = $('#tbx-cloud-browse-dialog')[0];
        if (dialog && dialog.open) {
            dialog.close();
        }
        browsingAccountId = null;
        browsingFolder = '';
        browsingCursor = null;
        browsingHistory = [];
        browsingProvider = 'webdav';
        $('#tbx-cloud-browse-results').empty();
    }

    /** Request a bounded folder listing from the authenticated Cloud controller. */
    function browseFolder(folder, cursor) {
        if (!unlocked || !browsingAccountId) {
            feedback(t('unlock-before-browse'), true);
            return;
        }
        browsingFolder = folder;
        browsingCursor = cursor || null;
        var rootName = ['onedrive', 'onedrive_personal'].includes(browsingProvider) ? 'OneDrive'
            : browsingProvider === 'dropbox' ? 'Dropbox' : t('my-drive');
        $('#tbx-cloud-browse-path').text(['google_drive', 'onedrive', 'onedrive_personal', 'dropbox'].includes(browsingProvider)
            ? rootName + (browsingHistory.length ? ' / ' + browsingHistory.map(function (item) { return item.name; }).join(' / ') : '')
            : '/' + folder);
        $('#tbx-cloud-browse-up').prop('disabled', folder === '');
        $('#tbx-cloud-browse-results').text(t('loading-folder'));
        $('#tbx-cloud-browse-next').prop('hidden', true);
        if (!send('list_files', {
            _account_id: browsingAccountId,
            _folder_id: folder,
            _cursor: browsingCursor || ''
        })) {
            $('#tbx-cloud-browse-results').text(t('folder-list-failed-start'));
        }
    }

    /** Open the browser only for an account from the authenticated account list. */
    function openBrowse(id) {
        var account = accountRows.find(function (row) { return String(row.id) === id; });
        var dialog = $('#tbx-cloud-browse-dialog')[0];
        if (!unlocked || !account || !dialog || typeof dialog.showModal !== 'function') {
            feedback(t('select-valid-account'), true);
            return;
        }
        closeBrowse();
        browsingAccountId = id;
        browsingProvider = String(account.provider || 'webdav');
        browsingHistory = [];
        $('#tbx-cloud-browse-title').text(tf('browse-files-named', account.display_name || t('cloud-account-title')));
        dialog.showModal();
        browseFolder('', null);
    }

    /** Render normalized file metadata as text only, never remote HTML. */
    function showListing(listing) {
        var target = $('#tbx-cloud-browse-results').empty();
        var items = listing && Array.isArray(listing.items) ? listing.items : [];
        if (!items.length) {
            target.text(t('folder-empty'));
        } else {
            var list = $('<ul>').addClass('tbx-cloud-file-list');
            items.forEach(function (entry) {
                if (!entry || typeof entry.name !== 'string' || typeof entry.id !== 'string') {
                    return;
                }
                var row = $('<li>').appendTo(list);
                if (entry.is_dir) {
                    $('<button type="button">').addClass('tbx-cloud-file-folder')
                        .text('📁 ' + entry.name)
                        .attr('data-cloud-folder', entry.id)
                        .appendTo(row);
                } else {
                    var size = Number.isSafeInteger(Number(entry.size)) && Number(entry.size) >= 0
                        ? ' (' + Number(entry.size).toLocaleString() + ' ' + t('bytes') + ')' : '';
                    $('<span>').text('📄 ' + entry.name + size).appendTo(row);
                }
            });
            target.append(list);
        }
        var next = listing && typeof listing.next_cursor === 'string' ? listing.next_cursor : null;
        $('#tbx-cloud-browse-next').prop('hidden', !next).data('cursor', next);
    }

    /** Render owned account metadata as inert text nodes, not markup. */
    function showAccounts(accounts) {
        accountRows = Array.isArray(accounts) ? accounts.filter(function (account) {
            return account && /^[1-9][0-9]{0,18}$/.test(String(account.id || ''));
        }) : [];
        var target = $('#tbx-cloud-accounts').empty();
        if (!Array.isArray(accounts) || !accounts.length) {
            target.text(t('no-cloud-accounts'));
            return;
        }
        var list = $('<ul>').addClass('toolbox-cloud-account-list');
        accounts.forEach(function (account) {
            if (!account || !/^[1-9][0-9]{0,18}$/.test(String(account.id || ''))) {
                return;
            }
            var item = $('<li>').addClass('tbx-cloud-account-row');
            var description = $('<span>').addClass('tbx-cloud-account-description').appendTo(item);
            $('<strong>').text(String(account.display_name || t('compose-account'))).appendTo(description);
            description.append(document.createTextNode(' (' + (account.provider === 'onedrive' ? 'onedrive_business' : String(account.provider || 'webdav')) + ')'));
            var actions = $('<span>').addClass('tbx-cloud-account-actions').appendTo(item);
            if (['webdav', 'google_drive', 'onedrive', 'onedrive_personal', 'dropbox'].includes(account.provider)) {
                $('<button type="button">').addClass('tbx-cloud-account-icon browse')
                    .attr({ 'data-cloud-browse': String(account.id), 'aria-label': t('browse-cloud-files'), title: t('browse-cloud-files') })
                    .appendTo(actions);
            }
            if (account.provider === 'webdav') {
                $('<button type="button">').addClass('tbx-cloud-account-icon edit')
                    .attr({ 'data-cloud-edit': String(account.id), 'aria-label': t('edit-cloud-account'), title: t('edit-account') })
                    .appendTo(actions);
            }
            $('<button type="button">').addClass('tbx-cloud-account-icon delete')
                .attr({ 'data-cloud-delete': String(account.id), 'aria-label': t('delete-cloud-account'), title: t('delete-account') })
                .appendTo(actions);
            list.append(item);
        });
        target.append(list);
    }

    /** Handle exactly the matching response to a pending Cloud request. */
    function onResponse(response) {
        if (!pending || !response || response.request_id !== pending.id || response.operation !== pending.operation) {
            return;
        }
        var operation = pending.operation;
        pending = null;
        if (unlocked && Number(response.session_expires || 0) > 0) {
            unlockExpires = Number(response.session_expires);
        }
        $('#toolbox-cloud-settings button').prop('disabled', false);
        clearSecrets();
        showLockState(unlocked, unlockExpires);
        if (!response.ok) {
            if (operation === 'google_start' && googlePopup && !googlePopup.closed) {
                googlePopup.close();
            }
            if (operation === 'microsoft_start' && microsoftPopup && !microsoftPopup.closed) {
                microsoftPopup.close();
            }
            if (operation === 'microsoft_personal_start' && microsoftPersonalPopup && !microsoftPersonalPopup.closed) {
                microsoftPersonalPopup.close();
            }
            if (operation === 'dropbox_start' && dropboxPopup && !dropboxPopup.closed) {
                dropboxPopup.close();
            }
            if (response.locked) {
                showLockState(false, 0);
            }
            if (operation === 'list_files' && browsingAccountId) {
                $('#tbx-cloud-browse-results').text(t('folder-list-failed'));
            }
            feedback(String(response.error || t('operation-failed')), true);
            return;
        }
        var data = response.data || {};
        if (operation === 'status') {
            showVaultState(!!data.has_vault);
            if (data.has_vault) {
                configureUnlockMethods(data.unlock_methods);
            }
            showLockState(!!data.unlocked, data.expires);
            if (data.unlocked) {
                send('list_accounts', {});
            }
        } else if (operation === 'unlock') {
            showLockState(true, data.expires);
            showAccounts(data.accounts);
            feedback(t('vault-unlocked'), false);
        } else if (operation === 'lock') {
            showLockState(false, 0);
            feedback(t('vault-locked'), false);
        } else if (operation === 'renew_unlock') {
            showLockState(true, data.expires);
            feedback(t('timer-reset'), false);
        } else if (operation === 'create_vault') {
            $('#tbx-cloud-new-password, #tbx-cloud-confirm-password').val('');
            showVaultState(true);
            configureUnlockMethods(data.unlock_methods);
            showLockState(false, 0);
            if (data.recovery_key) {
                showRecovery(String(data.recovery_key));
            }
            feedback(t('vault-created'), false);
        } else if (operation === 'google_start') {
            var url = String(data.authorization_url || '');
            if (!googlePopup || googlePopup.closed || !/^https:\/\/accounts\.google\.com\/o\/oauth2\/v2\/auth\?/.test(url)) {
                feedback(t('google-open-failed'), true);
            } else {
                googlePopup.location.replace(url);
            }
        } else if (operation === 'microsoft_start') {
            var microsoftUrl = String(data.authorization_url || '');
            if (!microsoftPopup || microsoftPopup.closed
                || !/^https:\/\/login\.microsoftonline\.com\/(?:organizations|[0-9a-f-]{36})\/oauth2\/v2\.0\/authorize\?/.test(microsoftUrl)) {
                feedback(t('microsoft-open-failed'), true);
            } else {
                microsoftPopup.location.replace(microsoftUrl);
            }
        } else if (operation === 'microsoft_personal_start') {
            var personalUrl = String(data.authorization_url || '');
            if (!microsoftPersonalPopup || microsoftPersonalPopup.closed
                || !/^https:\/\/login\.microsoftonline\.com\/consumers\/oauth2\/v2\.0\/authorize\?/.test(personalUrl)) {
                feedback(t('personal-open-failed'), true);
            } else {
                microsoftPersonalPopup.location.replace(personalUrl);
            }
        } else if (operation === 'dropbox_start') {
            var dropboxUrl = String(data.authorization_url || '');
            if (!dropboxPopup || dropboxPopup.closed
                || !/^https:\/\/www\.dropbox\.com\/oauth2\/authorize\?/.test(dropboxUrl)) {
                feedback(t('dropbox-open-failed'), true);
            } else {
                dropboxPopup.location.replace(dropboxUrl);
            }
        } else if (operation === 'list_accounts') {
            showAccounts(data.accounts);
            feedback(t('accounts-loaded'), false);
        } else if (operation === 'add_webdav') {
            closeProvider();
            feedback(t('account-added'), false);
            send('list_accounts', {});
        } else if (operation === 'update_webdav') {
            closeProvider();
            feedback(t('account-updated'), false);
            send('list_accounts', {});
        } else if (operation === 'delete_account') {
            feedback(t('account-removed'), false);
            send('list_accounts', {});
        } else if (operation === 'list_files') {
            if (browsingAccountId) {
                showListing(data.listing);
            }
        } else if (operation === 'set_password') {
            $('#tbx-cloud-replacement-password, #tbx-cloud-replacement-confirm').val('');
            feedback(t('vault-password-updated'), false);
        } else if (operation === 'rotate_recovery') {
            if (data.recovery_key) {
                showRecovery(String(data.recovery_key));
            }
            feedback(t('recovery-changed'), false);
        }
    }

    /** Wire Cloud editor events without hijacking Toolbox's existing form. */
    function setup() {
        if (!window.rcmail || !$('#toolbox-cloud-settings').length) {
            return;
        }
        rcmail.addEventListener('plugin.cloud.response', onResponse);
        $('#tbx-cloud-unlock-method').on('change', updateUnlockHelp);
        $('#tbx-cloud-create-button').on('click', function () {
            var password = value('#tbx-cloud-new-password');
            var confirmation = value('#tbx-cloud-confirm-password');
            var recovery = $('#tbx-cloud-create-recovery').prop('checked');
            if (password !== confirmation) {
                feedback(t('vault-password-mismatch'), true);
                return;
            }
            if (password && password.length < 12) {
                feedback(t('vault-password-length'), true);
                return;
            }
            if (!password && !recovery) {
                feedback(t('vault-method-required'), true);
                return;
            }
            hideRecovery();
            var sent = send('create_vault', { _password: password, _with_recovery: recovery ? '1' : '0' });
            if (sent) {
                $('#tbx-cloud-new-password, #tbx-cloud-confirm-password').val('');
            }
        });
        $('#toolbox-cloud-settings').on('click', '[data-cloud-google]', function () {
            if (!unlocked) {
                feedback(t('unlock-cloud-first'), true);
                return;
            }
            // Open synchronously on the user gesture to avoid browser popup blocking.
            googlePopup = window.open('about:blank', 'toolbox_cloud_google', 'width=620,height=760');
            if (!googlePopup) {
                feedback(t('google-popups'), true);
                return;
            }
            if (!send('google_start', {})) {
                googlePopup.close();
            }
        });
        /** Refresh the account list when Google's callback completes. */
        function googleCompleted(result) {
            if (!googlePopup || !result || result.type !== 'toolbox-cloud-google-oauth') {
                return;
            }
            googlePopup = null;
            feedback(result.ok ? t('google-connected') : t('google-auth-failed'), !result.ok);
            if (result.ok) {
                send('list_accounts', {});
            }
        }
        // Google's popup isolation may sever window.opener. BroadcastChannel
        // works between same-origin pages without an opener relationship.
        if (typeof window.BroadcastChannel === 'function') {
            googleChannel = new window.BroadcastChannel('toolbox-cloud-google-oauth');
            googleChannel.onmessage = function (event) {
                googleCompleted(event.data);
            };
        }
        // Retain postMessage for browser compatibility; duplicate notifications
        // are ignored because googleCompleted clears googlePopup.
        window.addEventListener('message', function (event) {
            if (event.origin === window.location.origin && googlePopup && event.source === googlePopup) {
                googleCompleted(event.data);
            }
        });
        /** Start a Microsoft OAuth popup synchronously to avoid popup blockers. */
        $('#toolbox-cloud-settings').on('click', '[data-cloud-microsoft]', function () {
            if (!unlocked) {
                feedback(t('unlock-cloud-first'), true);
                return;
            }
            microsoftPopup = window.open('about:blank', 'toolbox_cloud_microsoft', 'width=620,height=760');
            if (!microsoftPopup) {
                feedback(t('onedrive-popups'), true);
                return;
            }
            if (!send('microsoft_start', {})) {
                microsoftPopup.close();
            }
        });
        /** Reload owned accounts only after the matching Microsoft OAuth popup completes. */
        function microsoftCompleted(result) {
            if (!microsoftPopup || !result || result.type !== 'toolbox-cloud-microsoft-oauth') {
                return;
            }
            microsoftPopup = null;
            feedback(result.ok ? t('onedrive-connected') : t('onedrive-auth-failed'), !result.ok);
            if (result.ok) {
                send('list_accounts', {});
            }
        }
        if (typeof window.BroadcastChannel === 'function') {
            microsoftChannel = new window.BroadcastChannel('toolbox-cloud-microsoft-oauth');
            microsoftChannel.onmessage = function (event) { microsoftCompleted(event.data); };
        }
        window.addEventListener('message', function (event) {
            if (event.origin === window.location.origin && microsoftPopup && event.source === microsoftPopup) {
                microsoftCompleted(event.data);
            }
        });
        /** Open OneDrive Personal authorization in an independent popup. */
        $('#toolbox-cloud-settings').on('click', '[data-cloud-microsoft-personal]', function () {
            if (!unlocked) {
                feedback(t('unlock-cloud-first'), true);
                return;
            }
            microsoftPersonalPopup = window.open('about:blank', 'toolbox_cloud_microsoft_personal', 'width=620,height=760');
            if (!microsoftPersonalPopup) {
                feedback(t('personal-popups'), true);
                return;
            }
            if (!send('microsoft_personal_start', {})) {
                microsoftPersonalPopup.close();
            }
        });
        /** Complete only the active Personal OAuth popup; never the Business popup. */
        function personalCompleted(result) {
            if (!microsoftPersonalPopup || !result || result.type !== 'toolbox-cloud-microsoft-personal-oauth') {
                return;
            }
            microsoftPersonalPopup = null;
            feedback(result.ok ? t('personal-connected') : t('personal-auth-failed'), !result.ok);
            if (result.ok) {
                send('list_accounts', {});
            }
        }
        if (typeof window.BroadcastChannel === 'function') {
            microsoftPersonalChannel = new window.BroadcastChannel('toolbox-cloud-microsoft-personal-oauth');
            microsoftPersonalChannel.onmessage = function (event) { personalCompleted(event.data); };
        }
        window.addEventListener('message', function (event) {
            if (event.origin === window.location.origin && microsoftPersonalPopup && event.source === microsoftPersonalPopup) {
                personalCompleted(event.data);
            }
        });
        /** Open Dropbox authorization synchronously to avoid popup blockers. */
        $('#toolbox-cloud-settings').on('click', '[data-cloud-dropbox]', function () {
            if (!unlocked) {
                feedback(t('unlock-cloud-first'), true);
                return;
            }
            dropboxPopup = window.open('about:blank', 'toolbox_cloud_dropbox', 'width=620,height=760');
            if (!dropboxPopup) {
                feedback(t('dropbox-popups'), true);
                return;
            }
            if (!send('dropbox_start', {})) {
                dropboxPopup.close();
            }
        });
        /** Reload the account list after a matching Dropbox callback. */
        function dropboxCompleted(result) {
            if (!dropboxPopup || !result || result.type !== 'toolbox-cloud-dropbox-oauth') {
                return;
            }
            dropboxPopup = null;
            feedback(result.ok ? t('dropbox-connected') : t('dropbox-auth-failed'), !result.ok);
            if (result.ok) {
                send('list_accounts', {});
            }
        }
        if (typeof window.BroadcastChannel === 'function') {
            dropboxChannel = new window.BroadcastChannel('toolbox-cloud-dropbox-oauth');
            dropboxChannel.onmessage = function (event) { dropboxCompleted(event.data); };
        }
        window.addEventListener('message', function (event) {
            if (event.origin === window.location.origin && dropboxPopup && event.source === dropboxPopup) {
                dropboxCompleted(event.data);
            }
        });
        $('#tbx-cloud-refresh').on('click', function () {
            var fields = unlockFields();
            if (fields) {
                send('unlock', fields);
            }
        });
        $('#tbx-cloud-lock').on('click', function () { send('lock', {}); });
        $('#tbx-cloud-renew').on('click', function () {
            if (!unlocked) {
                feedback(t('unlock-first'), true);
                return;
            }
            send('renew_unlock', {});
        });
        $('#toolbox-cloud-settings').on('click', '[data-cloud-provider]', function () {
            openProvider(String($(this).attr('data-cloud-provider') || ''));
        });
        $('#tbx-cloud-close-dialog').on('click', closeProvider);
        $('#tbx-cloud-provider-dialog').on('close', function () {
            clearConnection();
            activeProvider = null;
            editingAccountId = null;
        });
        $('#tbx-cloud-add').on('click', function () {
            if (!activeProvider) {
                feedback(t('choose-provider-first'), true);
                return;
            }
            var name = value('#tbx-cloud-name').trim();
            var endpoint = value('#tbx-cloud-endpoint').trim();
            var username = value('#tbx-cloud-username');
            var appPassword = value('#tbx-cloud-app-password');
            if (!name || !/^https:\/\//i.test(endpoint) || (!editingAccountId && (!username || !appPassword))) {
                feedback(editingAccountId
                    ? t('account-edit-required')
                    : t('account-new-required'), true);
                return;
            }
            if (!unlocked) {
                feedback(t('unlock-first'), true);
                return;
            }
            if (send(editingAccountId ? 'update_webdav' : 'add_webdav', {
                _account_id: editingAccountId || '',
                _display_name: name, _endpoint: endpoint,
                _username: username, _app_password: appPassword
            })) {
                $('#tbx-cloud-app-password').val('');
            }
        });
        $('#tbx-cloud-accounts').on('click', 'button[data-cloud-browse]', function () {
            openBrowse(String($(this).attr('data-cloud-browse') || ''));
        });
        $('#tbx-cloud-close-browse').on('click', closeBrowse);
        $('#tbx-cloud-browse-dialog').on('close', function () {
            browsingAccountId = null;
            browsingFolder = '';
            browsingCursor = null;
            browsingHistory = [];
        });
        $('#tbx-cloud-browse-results').on('click', 'button[data-cloud-folder]', function () {
            var id = String($(this).attr('data-cloud-folder') || '');
            // Google, OneDrive and Dropbox use opaque IDs; WebDAV uses paths.
            if (['google_drive', 'onedrive', 'onedrive_personal', 'dropbox'].includes(browsingProvider)) {
                var allowed = browsingProvider === 'dropbox'
                    ? /^id:[A-Za-z0-9_-]{1,509}$/.test(id)
                    : /^[A-Za-z0-9_!.-]{1,512}$/.test(id);
                if (allowed) {
                    browsingHistory.push({ id: id, name: $(this).text().replace(/^📁\s*/, '') });
                    browseFolder(id, null);
                }
            } else if (id && id.length <= 4096 && /^[^?#\\]+$/.test(id)) {
                browseFolder(id, null);
            }
        });
        $('#tbx-cloud-browse-up').on('click', function () {
            if (['google_drive', 'onedrive', 'onedrive_personal', 'dropbox'].includes(browsingProvider)) {
                browsingHistory.pop();
                var parent = browsingHistory.length ? browsingHistory[browsingHistory.length - 1].id : '';
                browseFolder(parent, null);
            } else {
                var parts = browsingFolder.split('/');
                parts.pop();
                browseFolder(parts.join('/'), null);
            }
        });
        $('#tbx-cloud-browse-next').on('click', function () {
            var cursor = $(this).data('cursor');
            if (typeof cursor === 'string' && cursor.length <= 2048
                && (browsingProvider === 'dropbox'
                    ? /^[\x21-\x7e]+$/.test(cursor)
                    : ['google_drive', 'onedrive'].includes(browsingProvider)
                        ? /^[A-Za-z0-9_\-.=\/+:]+$/.test(cursor)
                        : /^[0-9]{1,6}$/.test(cursor))) {
                browseFolder(browsingFolder, cursor);
            }
        });
        $('#tbx-cloud-accounts').on('click', 'button[data-cloud-edit]', function () {
            editAccount(String($(this).attr('data-cloud-edit') || ''));
        });
        $('#tbx-cloud-accounts').on('click', 'button[data-cloud-delete]', function () {
            var id = String($(this).attr('data-cloud-delete') || '');
            if (!/^[1-9][0-9]{0,18}$/.test(id) || !window.confirm(t('remove-account-confirm'))) {
                return;
            }
            if (!unlocked) {
                feedback(t('unlock-first'), true);
                return;
            }
            send('delete_account', { _account_id: id });
        });
        $('#tbx-cloud-change-password').on('click', function () {
            var newPassword = value('#tbx-cloud-replacement-password');
            if (newPassword !== value('#tbx-cloud-replacement-confirm')) {
                feedback(t('new-password-mismatch'), true);
                return;
            }
            if (newPassword.length < 12) {
                feedback(t('new-password-length'), true);
                return;
            }
            if (!unlocked) {
                feedback(t('unlock-first'), true);
                return;
            }
            if (send('set_password', { _new_password: newPassword })) {
                $('#tbx-cloud-replacement-password, #tbx-cloud-replacement-confirm').val('');
            }
        });
        $('#tbx-cloud-rotate-recovery').on('click', function () {
            if (!window.confirm(t('rotate-recovery-confirm'))) {
                return;
            }
            if (!unlocked) {
                feedback(t('unlock-first'), true);
                return;
            }
            hideRecovery();
            send('rotate_recovery', {});
        });
        $('#tbx-cloud-hide-recovery').on('click', hideRecovery);
        showLockState(false, 0);
        window.setInterval(updateLockCountdown, 1000);
        send('status', {});
    }

    $(setup);
})(jQuery);

// END OF FILE

