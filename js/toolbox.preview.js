/**
 * Toolbox Preview: disable loading the hidden message preview in Elastic.
 * Native Roundcube double-click continues opening a message normally.
 */

'use strict';

$(function () {
    if (!window.rcmail) {
        return;
    }

    rcmail.addEventListener('init', function () {
        // This file is included by Preview only on mail-list pages.
        if (rcmail.task !== 'mail') {
            return;
        }

        // Roundcube checks env.contentframe before scheduling a preview.
        // Clearing this prevents a single click from loading a hidden iframe
        // and thereby accidentally marking the selected message as read.
        // The built-in dblclick handler does not depend on this value.
        rcmail.env.contentframe = null;
    });
});

// END OF FILE

