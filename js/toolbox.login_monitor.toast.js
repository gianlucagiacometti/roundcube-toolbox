/**
 * Login Monitor: one-time native Roundcube confirmation after successful login.
 *
 * Builds escaped markup for Roundcube's built-in notification container.
 * Optional flags use Composer-installed flag-icons SVGs embedded by PHP;
 * the Unicode country flag remains visible if an image is unavailable.
 */

"use strict";

(function () {
    /** Encode untrusted text before passing markup to Roundcube. */
    function escapeHtml(value) {
        return String(value).replace(/[&<>"']/g, function (character) {
            return {"&": "&amp;", "<": "&lt;", ">": "&gt;", "\"": "&quot;", "'": "&#39;"}[character];
        });
    }

    /** Return the national flag emoji for a validated ISO country code. */
    function flagEmoji(code) {
        return String.fromCodePoint(...Array.from(code).map(function (letter) {
            return 127397 + letter.charCodeAt(0);
        }));
    }

    /** Display with the same successful-message UI as Roundcube itself. */
    function show() {
        if (typeof rcmail === "undefined" || !rcmail.env || !rcmail.env.toolbox_lm_toast
            || typeof rcmail.display_message !== "function") {
            return;
        }

        const data = rcmail.env.toolbox_lm_toast;
        // Avoid subsequent re-use by other scripts on this page.
        delete rcmail.env.toolbox_lm_toast;
        const line = ['<span class="toolbox-login-monitor-toast">'];
        line.push('<span>', escapeHtml(data.hello), ': <strong>', escapeHtml(data.name), '</strong></span>');
        // Explicit line breaks work even before the optional toast stylesheet loads.
        line.push('<br><span>', escapeHtml(data.last), ': <strong>',
            escapeHtml(data.date || data.none), '</strong></span>');
        const country = String(data.country || "").toLowerCase();
        if (data.city) {
            line.push('<br><span>', escapeHtml(data.from), ': <strong>', escapeHtml(data.city), '</strong>');
            if (/^[a-z]{2}$/.test(country)) {
                const countryUp = country.toUpperCase();
                // Readable fallback if the Composer-provided icon is unavailable.
                line.push(' <span class="toolbox-lm-toast-flag" aria-label="',
                    escapeHtml(countryUp), '" role="img">', flagEmoji(countryUp), '</span>');
            }
            line.push('</span>');
        }
        line.push('</span>');

        rcmail.display_message(line.join(""), "confirmation", 10000, "toolbox-login-monitor-welcome");
        if (/^[a-z]{2}$/.test(country) && data.city) {
            const flag = document.querySelector('#messagestack .toolbox-lm-toast-flag')
                || document.querySelector('.toolbox-login-monitor-toast .toolbox-lm-toast-flag');
            if (flag) {
                const img = new Image();
                img.alt = country.toUpperCase();
                img.className = 'toolbox-lm-toast-flag-image';
                // Only the SVG supplied from local Composer assets may replace the emoji.
                img.addEventListener('load', function () {
                    if (flag.isConnected) {
                        flag.textContent = '';
                        flag.appendChild(img);
                    }
                });
                // Only embedded SVG image data is accepted: never load an external URL.
                const source = String(data.flag_data_uri || "");
                if (/^data:image\/svg\+xml;base64,[A-Za-z0-9+/]+={0,2}$/.test(source)) {
                    img.src = source;
                }
            }
        }
    }

    // Roundcube registers its message container before emitting the init event.
    // DOMContentLoaded alone can run too early and lose the SVG flag update.
    if (typeof rcmail !== "undefined" && typeof rcmail.addEventListener === "function") {
        rcmail.addEventListener("init", show);
        if (rcmail.loaded) {
            show();
        }
    } else if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", show);
    } else {
        show();
    }
})();

// END OF FILE

