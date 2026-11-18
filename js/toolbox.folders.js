/**
 * Toolbox Folders: read-only IMAP folder properties in Mail.
 *
 * The folder-list context menu is optional. Explicit size requests are kept
 * separate from the fast message counters to avoid unexpected IMAP scans.
 */

"use strict";

(function ($) {
    const client = window.rcmail;
    if (!client) {
        return;
    }

    const command = 'plugin.toolbox.folders';
    const action = 'plugin.toolbox.folders.properties';
    let sequence = 0;
    let dialog = null;

    /** Resolve a translated string from the current Roundcube session. */
    function text(key) {
        const labels = client.env.toolbox_folders_labels || {};
        return Object.prototype.hasOwnProperty.call(labels, key) ? labels[key] : key;
    }

    /** Accept a real mailbox name, never a synthetic sidebar group. */
    function validFolder(value) {
        return typeof value === 'string' && value.length > 0 && value.length <= 1024
            && !/[\x00-\x1f\x7f]/.test(value);
    }

    /** Return the clicked folder, without carrying a stale context-menu target. */
    function selectedFolder(source) {
        const fromMenu = source && $(source).closest('div.contextmenu, #rcm_folderlist').length > 0;
        const folder = fromMenu ? client.env.context_menu_source_id : client.env.mailbox;
        return validFolder(folder) ? folder : null;
    }

    /** Add an accessible, escaped label/value pair to the properties grid. */
    function addProperty(container, key, value) {
        $('<dt>').text(text(key)).appendTo(container);
        $('<dd>').text(value).appendTo(container);
    }

    /** Display an integer with the browser's number-formatting locale. */
    function count(value) {
        return Number(value).toLocaleString();
    }

    /** Display size using binary multiples without interpreting server text. */
    function bytes(value) {
        const units = ['B', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
        let number = Number(value);
        let index = 0;
        while (number >= 1024 && index < units.length - 1) {
            number /= 1024;
            index += 1;
        }
        return number.toLocaleString(undefined, {
            maximumFractionDigits: index ? 1 : 0
        }) + ' ' + units[index];
    }

    /** Show the status of one request without creating interpreted HTML. */
    function status(context, message, failed) {
        context.status.toggleClass('error', Boolean(failed)).text(message);
    }

    /** Keep the folder statistics request and size calculation independent. */
    function request(context, mode) {
        if (context.closed || context.pending[mode]) {
            return;
        }
        const token = String(++sequence);
        context.pending[mode] = token;
        if (mode === 'size') {
            context.sizeButton.prop('disabled', true).text(text('folders-calculating'));
        } else {
            status(context, text('folders-loading'), false);
        }

        const lock = client.set_busy(true, 'loading');
        let xhr;
        try {
            xhr = client.http_post(action, {
                _mbox: context.folder,
                _folders_request: token,
                _size: mode === 'size' ? '1' : '0'
            }, lock);
        } catch (error) {
            client.set_busy(false, null, lock);
        }

        /** Report only the failure of this still-active request. */
        function failed() {
            if (context.closed || context.pending[mode] !== token) {
                return;
            }
            context.pending[mode] = null;
            if (mode === 'size') {
                context.sizeButton.prop('disabled', false).text(text('folders-calculate'));
                context.sizeValue.text(text('folders-sizefailed'));
            } else {
                status(context, text('folders-loadfailed'), true);
            }
        }

        if (!xhr || typeof xhr.done !== 'function' || typeof xhr.fail !== 'function') {
            failed();
            return;
        }
        xhr.fail(failed);
        xhr.done(function () {
            // Roundcube executes output->command() before the done callback.
            if (context.pending[mode] === token) {
                failed();
            }
        });
    }

    /** Render one confirmed statistics response from the IMAP server. */
    function showStats(context, data) {
        context.details.empty();
        addProperty(context.details, 'folders-folder', data.folder);
        addProperty(context.details, 'folders-total', count(data.total));
        addProperty(context.details, 'folders-unread', count(data.unread));
        addProperty(context.details, 'folders-read', count(data.read));
        const namespace = 'folders-namespace-' + data.namespace;
        addProperty(context.details, 'folders-namespace', text(namespace));
        addProperty(context.details, 'folders-special',
            text(data.special ? 'folders-yes' : 'folders-no'));
        context.sizeButton.prop('disabled', false);
        status(context, '', false);
    }

    /** Accept only the reply matching this dialog, mailbox and request mode. */
    client.toolbox_folders_properties_result = function (data) {
        const context = dialog;
        if (!context || context.closed || !data || data.folder !== context.folder
            || !['stats', 'size'].includes(data.mode)
            || context.pending[data.mode] !== data.request) {
            return;
        }
        const mode = data.mode;
        context.pending[mode] = null;
        if (mode === 'size') {
            context.sizeButton.prop('disabled', false).text(text('folders-calculate'));
            if (data.success && Number.isFinite(Number(data.size)) && Number(data.size) >= 0) {
                context.sizeValue.text(bytes(data.size));
            } else {
                context.sizeValue.text(text('folders-sizefailed'));
            }
        } else if (data.success) {
            showStats(context, data);
        } else {
            status(context, text('folders-loadfailed'), true);
        }
    };

    /** Open a read-only dialog for the selected or right-clicked mailbox. */
    function openProperties(source) {
        const folder = selectedFolder(source);
        if (!folder) {
            return false;
        }
        if (dialog && !dialog.closed) {
            // Roundcube places the content inside a separate dialog element.
            // Close that element, not the nested properties div.
            dialog.popup.dialog('close');
        }

        const root = $('<div>', {'class': 'toolbox-folders-properties'});
        // Roundcube measures the dialog height when opening it. Reserve all
        // property rows before the asynchronous IMAP response arrives;
        // otherwise the fixed-height popup clips everything below Messages.
        const details = $('<dl>', {'class': 'toolbox-folders-grid'}).appendTo(root);
        addProperty(details, 'folders-folder', folder);
        ['folders-total', 'folders-unread', 'folders-read',
            'folders-namespace', 'folders-special'].forEach(function (key) {
            addProperty(details, key, '—');
        });
        const sizePanel = $('<div>', {'class': 'toolbox-folders-size-panel'}).appendTo(root);
        const sizeHeading = $('<div>', {'class': 'toolbox-folders-size-line'}).appendTo(sizePanel);
        $('<strong>').text(text('folders-size')).appendTo(sizeHeading);
        const sizeValue = $('<span>').text(text('folders-notcalculated')).appendTo(sizeHeading);
        const sizeButton = $('<button>', {'type': 'button', 'class': 'button'})
            .text(text('folders-calculate')).prop('disabled', true).appendTo(sizePanel);
        $('<p>', {'class': 'toolbox-folders-size-note'})
            .text(text('folders-sizewarning')).appendTo(sizePanel);
        const info = $('<p>', {'class': 'toolbox-folders-status', 'role': 'status',
            'aria-live': 'polite'}).appendTo(root);
        const context = {
            root: root,
            folder: folder,
            details: details,
            sizePanel: sizePanel,
            sizeValue: sizeValue,
            sizeButton: sizeButton,
            status: info,
            pending: {stats: null, size: null},
            closed: false
        };
        dialog = context;
        sizeButton.on('click', function () {
            request(context, 'size');
        });
        context.popup = client.show_popup_dialog(root, text('folders-properties'), [{
            text: client.get_label('close'),
            'class': 'cancel mainaction',
            click: function () { this.jqref(this).dialog('close'); }
        }], {
            width: 440,
            // Initial rows are present before opening; cap the dialog to the
            // viewport and let the details scroll on narrow displays.
            maxHeight: Math.max(220, (window.innerHeight || 700) - 48),
            dialogClass: 'toolbox-folders-popup',
            close: function () {
                context.closed = true;
                if (dialog === context) {
                    dialog = null;
                }
                (this.jqref || $)(this).remove();
            }
        });
        request(context, 'stats');
        return false;
    }

    /** Keep the toolbar button usable only with a current mailbox. */
    function updateCommand() {
        client.enable_command(command, validFolder(client.env.mailbox));
    }

    // The optional ContextMenu plugin can extend the folder-list menu.
    client.addEventListener('contextmenu_init', function (menu) {
        if (!menu || menu.menu_name !== 'folderlist') {
            return;
        }
        menu.menu_source.push({
            label: text('folders-properties'),
            command: command,
            props: '',
            // ContextMenu versions differ on the documented field name.
            // Include both; the command-specific CSS remains the fallback.
            class: 'toolbox-folders-menuentry',
            classes: 'toolbox-folders-menuentry'
        });
        menu.addEventListener('activate', function (item) {
            if (item.command === command) {
                return validFolder(client.env.context_menu_source_id);
            }
        });
    });

    client.addEventListener('init', function () {
        if (client.env.task !== 'mail') {
            return;
        }
        client.register_command(command, function (props, source) {
            return openProperties(source);
        }, false);
        client.addEventListener('selectfolder', updateCommand);
        client.addEventListener('listupdate', updateCommand);
        updateCommand();
    });
})(jQuery);

// END OF FILE

