/**
 * Login Monitor Settings tabs and Save-button presentation.
 *
 * Keeps the existing Toolbox form and its CSRF/save workflow intact.
 * History pagination uses read-only Settings URLs; details stay in the page.
 */

"use strict";

(function () {
    /** Display the selected panel and sync accessible tab state. */
    function activate(root, name, focus) {
        const tabs = Array.from(root.querySelectorAll('[role="tab"][data-lm-tab]'));
        if (name !== "history" && name !== "preferences") {
            return;
        }

        for (const tab of tabs) {
            const selected = tab.getAttribute("data-lm-tab") === name;
            const panel = document.getElementById(tab.getAttribute("aria-controls"));
            tab.setAttribute("aria-selected", selected ? "true" : "false");
            tab.tabIndex = selected ? 0 : -1;
            tab.classList.toggle("is-active", selected);
            if (panel) {
                panel.hidden = !selected;
            }
            if (selected && focus) {
                tab.focus();
            }
        }

        const state = document.getElementById("toolbox-lm-active-tab");
        if (state) {
            state.value = name;
        }
        // The shared Toolbox template places the Save button outside the form.
        // It is meaningful only while editing preferences.
        for (const actions of document.querySelectorAll(".formbuttons")) {
            actions.hidden = name !== "preferences";
        }
    }

    /** Bind the History/Preferences controls after Roundcube renders the page. */
    function init() {
        const root = document.getElementById("toolbox-lm-root");
        if (!root) {
            return;
        }

        const tabs = Array.from(root.querySelectorAll('[role="tab"][data-lm-tab]'));
        for (const [index, tab] of tabs.entries()) {
            tab.addEventListener("click", function () {
                activate(root, tab.getAttribute("data-lm-tab"), false);
            });
            tab.addEventListener("keydown", function (event) {
                let next;
                if (event.key === "ArrowRight") {
                    next = (index + 1) % tabs.length;
                } else if (event.key === "ArrowLeft") {
                    next = (index + tabs.length - 1) % tabs.length;
                } else if (event.key === "Home") {
                    next = 0;
                } else if (event.key === "End") {
                    next = tabs.length - 1;
                } else {
                    return;
                }
                event.preventDefault();
                activate(root, tabs[next].getAttribute("data-lm-tab"), true);
            });
        }

        // Read-only detail dialogs are rendered server-side from visible fields.
        // The buttons are never submitted with the Toolbox preferences form.
        root.addEventListener("click", function (event) {
            const opener = event.target.closest("[data-lm-detail]");
            if (opener && root.contains(opener)) {
                const id = opener.getAttribute("data-lm-detail");
                const dialog = document.getElementById(id);
                if (dialog && root.contains(dialog)) {
                    if (typeof dialog.showModal === "function") {
                        dialog.showModal();
                    } else {
                        dialog.setAttribute("open", "");
                    }
                }
                return;
            }

            const close = event.target.closest("[data-lm-close]");
            if (close && root.contains(close)) {
                const dialog = close.closest("dialog");
                if (dialog) {
                    if (typeof dialog.close === "function") {
                        dialog.close();
                    } else {
                        dialog.removeAttribute("open");
                    }
                }
            }
        });

        for (const dialog of root.querySelectorAll(".toolbox-login-monitor-dialog")) {
            dialog.addEventListener("click", function (event) {
                // Clicking outside the dialog content closes its backdrop.
                if (event.target === dialog && typeof dialog.close === "function") {
                    const bounds = dialog.getBoundingClientRect();
                    if (event.clientX < bounds.left || event.clientX > bounds.right
                        || event.clientY < bounds.top || event.clientY > bounds.bottom) {
                        dialog.close();
                    }
                }
            });
        }

        activate(root, root.getAttribute("data-initial-tab") || "history", false);
    }

    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", init);
    } else {
        init();
    }
})();

// END OF FILE

