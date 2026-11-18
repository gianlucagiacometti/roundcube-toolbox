/**
 * Toolbox Tags: mail assignment, modal definition editing, and optional list badges.
 *
 * Requests keep the message UID, mailbox and request token captured when the
 * chooser opens. Only confirmed server state updates the message's badges.
 */

"use strict";

(function ($) {
    const client = window.rcmail;
    if (!client) {
        return;
    }

    /** Read a translated Tags string supplied by the current server session. */
    function tagText(key) {
        const labels = client.env.toolbox_tags_labels || {};
        return Object.prototype.hasOwnProperty.call(labels, key) ? labels[key] : key;
    }

    const command = 'plugin.toolbox.tags';
    const requests = new Map();
    const listActions = ['list', 'search', 'refresh', 'check-recent'];
    const listRowStates = new Map();
    const searchParameters = ['_q', '_headers', '_filter', '_interval', '_scope', '_mbox', '_layout'];
    let sequence = 0;
    let active = null;
    let initialized = false;
    let listDefinitionSource = null;
    let listDefinitionCache = [];
    let listGeneration = 0;
    let listConfigurationGeneration = 0;
    let listResponseGeneration = null;
    let submittedSearch = null;
    let currentSearch = null;
    let replaySearch = null;
    let queuedSearch = null;
    let searchRefreshScheduled = false;

    /** Return a validated, independent copy of one message's identity. */
    function targetOf(value) {
        if (!value || !/^[1-9][0-9]{0,15}$/.test(String(value.uid))
            || typeof value.folder !== 'string' || !value.folder) {
            return null;
        }

        return {uid: String(value.uid), folder: value.folder};
    }

    /** Compare both UID and mailbox; UIDs alone are not unique across folders. */
    function sameTarget(first, second) {
        return first && second && String(first.uid) === String(second.uid)
            && first.folder === second.folder;
    }

    /** Resolve a list row identifier, including a multi-folder search result. */
    function targetFromRow(uid) {
        if (!uid) {
            return null;
        }

        const params = client.params_from_uid(uid);
        return targetOf({uid: params._uid, folder: params._mbox});
    }

    /** Capture the normal toolbar selection without changing its single-message rule. */
    function selectionTarget() {
        return client.select_all_mode ? null : targetFromRow(client.get_single_uid());
    }

    /** Capture the actual right-clicked row before ContextMenu clears its source. */
    function commandTarget(source) {
        const list = client.message_list;
        if (!source || !source.nodeType || !list) {
            return selectionTarget();
        }

        const menu = Object.values(client.env.contextmenus || {}).find(function (candidate) {
            const container = candidate && candidate.container && candidate.container[0];
            return candidate && candidate.list_object === 'message_list'
                && container && container.contains(source);
        });
        if (!menu) {
            return selectionTarget();
        }
        if (client.select_all_mode || !menu.selected_object) {
            return null;
        }

        // ContextMenu can expand one selected thread into several message UIDs.
        // Its source row is exact; list.selection retains the actual row selection.
        const uid = list.get_row_uid(menu.selected_object);
        if (!uid || (list.in_selection(uid) && list.selection.length !== 1)) {
            return null;
        }

        return targetFromRow(uid);
    }

    /** Keep the toolbar and its ContextMenu-derived entry selection-aware. */
    function updateCommand() {
        client.enable_command(command, Boolean(selectionTarget()));
    }

    /** Place the standard toolbar entry immediately after Thunderbird Labels. */
    function positionToolbar() {
        const button = $('#rcmbtntoolboxtags').attr('title', tagText('tags'));
        const thunderbird = $('#tb-label-menulink');
        const item = button.closest('#toolbar-menu > li');
        const anchor = thunderbird.closest('#toolbar-menu > li');

        if (item.length && anchor.length && item.parent()[0] === anchor.parent()[0]) {
            item.insertAfter(anchor);
        } else if (button.parent().is('#mailtoolbar')) {
            const wrapper = thunderbird.closest('.dropbutton');
            if (wrapper.parent().is('#mailtoolbar')) {
                button.insertAfter(wrapper);
            }
        }
    }

    /** Normalize public definition data shared by message and list displays. */
    function definitionsOf(definitions) {
        return definitions.filter(function (definition) {
            return definition && /^[a-f0-9]{24}$/.test(definition.id)
                && typeof definition.name === 'string' && typeof definition.value === 'string';
        }).map(function (definition) {
            return {
                id: definition.id,
                name: definition.name,
                value: definition.value,
                color: /^#[a-f0-9]{6}$/i.test(definition.color) ? definition.color : '#6478C4'
            };
        });
    }

    /** Make display data safe to render without interpreting user text as HTML. */
    function stateOf(payload) {
        const target = targetOf(payload);
        if (!target || !Array.isArray(payload.definitions) || !Array.isArray(payload.selected)) {
            return null;
        }

        const definitions = definitionsOf(payload.definitions);

        return Object.assign(target, {
            definitions: definitions,
            selected: payload.selected.filter(function (id) {
                return definitions.some(function (definition) { return definition.id === id; });
            }),
            error: typeof payload.error === 'string' ? payload.error : ''
        });
    }

    /** Render coloured labels using text nodes and a validated swatch colour. */
    function renderBadges(container, state) {
        container.empty();
        const selected = new Set(state.selected);

        state.definitions.forEach(function (definition) {
            if (selected.has(definition.id)) {
                $('<span>', {'class': 'toolbox-tags-badge'})
                    .append($('<span>', {'class': 'toolbox-tags-swatch', 'aria-hidden': 'true'})
                        .css('background-color', definition.color))
                    .append($('<span>').text(definition.name + ': ' + definition.value))
                    .appendTo(container);
            }
        });

        if (!container.children().length) {
            $('<span>', {'class': 'toolbox-tags-none'})
                .text(state.error ? tagText('tags-unavailable') : tagText('tags-noneassigned'))
                .appendTo(container);
        }
    }

    /** Normalize the current list catalog once per server-supplied array. */
    function listDefinitions() {
        const source = client.env.toolbox_tags_definitions;
        if (source !== listDefinitionSource) {
            listDefinitionSource = source;
            listDefinitionCache = Array.isArray(source) ? definitionsOf(source) : [];
        }
        return listDefinitionCache;
    }

    /** Keep UID namespaces separate when remembering current row state. */
    function listStateKey(target) {
        return target.folder + '\0' + target.uid;
    }

    /** Read an echoed ordering marker; initial or legacy requests start at zero. */
    function listResponseVersion(environment) {
        const value = environment && environment.toolbox_tags_generation;
        return /^[0-9]{1,10}$/.test(String(value)) ? Number(value) : 0;
    }

    /** Stamp existing core requests so late responses cannot undo newer tag edits. */
    function stampListRequest(parameters) {
        parameters._toolbox_tags_generation = String(++listGeneration);
        return parameters;
    }

    /** Copy only search criteria, leaving request locks and ordering markers independent. */
    function copySearchParameters(parameters) {
        const snapshot = {};
        searchParameters.forEach(function (name) {
            if (Object.prototype.hasOwnProperty.call(parameters, name)) {
                snapshot[name] = parameters[name];
            }
        });
        return snapshot;
    }

    /** Replay submitted criteria without reading or changing unsent search controls. */
    function restoreSearchParameters(parameters, snapshot) {
        searchParameters.forEach(function (name) {
            delete parameters[name];
            if (Object.prototype.hasOwnProperty.call(snapshot, name)) {
                parameters[name] = snapshot[name];
            }
        });
    }

    /** Forget queued work when the user resets or replaces the active search. */
    function clearSearchTracking() {
        submittedSearch = null;
        currentSearch = null;
        replaySearch = null;
        queuedSearch = null;
    }

    /** Capture initial native searches and keep their continuations on the same criteria. */
    function trackSearchRequest(parameters) {
        if (parameters._continue) {
            if (currentSearch && parameters._continue === currentSearch.request) {
                restoreSearchParameters(parameters, currentSearch.parameters);
            }
            return parameters;
        }

        if (replaySearch) {
            restoreSearchParameters(parameters, replaySearch.parameters);
        }
        clearSearchTracking();
        submittedSearch = {
            parameters: copySearchParameters(parameters),
            generation: listResponseVersion({toolbox_tags_generation: parameters._toolbox_tags_generation}),
            dirty: false
        };
        submittedSearch.matched = searchMatchesDefinitions(submittedSearch);
        return parameters;
    }

    /** Bind a submitted snapshot only to its matching, server-confirmed result set. */
    function confirmSearchResponse(event) {
        const response = event && event.response;
        const environment = response && response.env;
        if (!response || response.action !== 'search' || !environment
            || typeof environment.toolbox_tags_search_expanded !== 'boolean'
            || !submittedSearch
            || listResponseVersion(environment) !== submittedSearch.generation) {
            return;
        }

        const submitted = submittedSearch;
        submittedSearch = null;
        const query = typeof submitted.parameters._q === 'string' ? submitted.parameters._q.trim() : '';
        if (!query || environment.toolbox_tags_search_query !== query
            || typeof environment.search_request !== 'string' || !environment.search_request
            || environment.search_request !== client.env.search_request) {
            return;
        }

        currentSearch = Object.assign(submitted, {
            request: environment.search_request,
            mailbox: client.env.mailbox,
            expanded: environment.toolbox_tags_search_expanded
        });
        if (submitted.dirty) {
            queuedSearch = currentSearch;
        }
    }

    /** Keep snapshots on pagination, but discard them when a different listing is requested. */
    function trackSearchListing(parameters) {
        if (submittedSearch || (currentSearch && (parameters._search !== currentSearch.request
            || parameters._mbox !== currentSearch.mailbox))) {
            clearSearchTracking();
        }
        return parameters;
    }

    /** Compare one plain whole name/value using the same syntax boundary as the server. */
    function searchMatchesDefinitions(search) {
        let name = typeof search.parameters._q === 'string' ? search.parameters._q.trim() : '';
        if (/^"(?:[^"\\]|\\.)*"$/u.test(name)) {
            name = name.slice(1, -1).replace(/\\"/g, '"');
        } else if (name.includes('"') || /(?:^|\s)(?:-?[a-zA-Z_-]+:|(?:OR|AND)(?:\s|$))/iu.test(name)) {
            return false;
        }
        if (!name) {
            return false;
        }
        name = name.toLowerCase();
        return listDefinitions().some(function (definition) {
            return definition.name.toLowerCase() === name || definition.value.toLowerCase() === name;
        });
    }

    /** Compare only definition identity and searchable names, excluding display preferences. */
    function definitionSearchSignature(definitions) {
        return JSON.stringify(definitionsOf(definitions).map(function (definition) {
            return [definition.id, definition.name.toLowerCase(), definition.value.toLowerCase()];
        }).sort(function (first, second) {
            return first[0].localeCompare(second[0]);
        }));
    }

    /** Schedule one check after modal-close handlers and any replacement chooser finish. */
    function scheduleSearchRefresh() {
        if (!queuedSearch || searchRefreshScheduled) {
            return;
        }
        searchRefreshScheduled = true;
        window.setTimeout(function () {
            searchRefreshScheduled = false;
            flushSearchRefresh();
        }, 0);
    }

    /** Queue one native rerun when confirmed tag changes can affect the current results. */
    function queueSearchRefresh() {
        if (submittedSearch) {
            if (submittedSearch.matched || searchMatchesDefinitions(submittedSearch)) {
                submittedSearch.dirty = true;
            }
            return;
        }
        if (!currentSearch || currentSearch.request !== client.env.search_request
            || currentSearch.mailbox !== client.env.mailbox
            || (!currentSearch.expanded && !searchMatchesDefinitions(currentSearch))) {
            return;
        }
        queuedSearch = currentSearch;
        if (!active) {
            scheduleSearchRefresh();
        }
    }

    /** Refresh after the chooser closes, using submitted criteria and core search behavior. */
    function flushSearchRefresh() {
        if (!queuedSearch) {
            return;
        }
        if (queuedSearch !== currentSearch || currentSearch.request !== client.env.search_request
            || currentSearch.mailbox !== client.env.mailbox) {
            queuedSearch = null;
            return;
        }
        if (active || client.busy || !client.message_list || typeof client.qsearch !== 'function') {
            return;
        }

        replaySearch = queuedSearch;
        queuedSearch = null;
        try {
            client.qsearch(replaySearch.parameters._q);
        } finally {
            replaySearch = null;
        }
    }

    /** Protect newer configuration before Roundcube merges a native response environment. */
    function beginListResponse(event) {
        const response = event && event.response;
        if (!response || !listActions.includes(response.action)) {
            return;
        }

        const environment = response.env || {};
        listResponseGeneration = listResponseVersion(environment);
        if (listResponseGeneration < listConfigurationGeneration) {
            delete environment.toolbox_tags_definitions;
            delete environment.toolbox_tags_show_in_list;
        } else if (Object.prototype.hasOwnProperty.call(environment, 'toolbox_tags_definitions')
            || Object.prototype.hasOwnProperty.call(environment, 'toolbox_tags_show_in_list')) {
            listConfigurationGeneration = listResponseGeneration;
        }
    }

    /** End native-response ordering after core has inserted and refreshed its rows. */
    function endListResponse(event) {
        if (event && event.response && listActions.includes(event.response.action)) {
            listResponseGeneration = null;
        }
    }

    /** Read selected IDs from the latest metadata for one message row. */
    function listRowSelection(uid, row) {
        const message = (client.env.messages && client.env.messages[uid]) || row;
        const flags = message.flags || row.flags || {};
        return Array.isArray(flags.toolbox_tags) ? flags.toolbox_tags : [];
    }

    /** Decorate a subject cell with at most two badges and a true remainder count. */
    function renderListRow(uid, row) {
        const list = client.message_list;
        row = row || (list && list.rows && list.rows[uid]);
        if (!row || !row.obj) {
            return;
        }

        const subject = $(row.obj).children('td.subject').first();
        subject.children('.toolbox-tags-list-line').remove();
        if (!subject.length || client.env.toolbox_tags_show_in_list !== true) {
            return;
        }

        const selected = new Set(listRowSelection(uid, row));
        const definitions = listDefinitions().filter(function (definition) {
            return selected.has(definition.id);
        });
        if (!definitions.length) {
            return;
        }

        const description = tagText('tags-listprefix') + definitions.map(function (definition) {
            return definition.name + ': ' + definition.value;
        }).join('; ');
        const line = $('<span>', {'class': 'toolbox-tags-list-line', title: description});
        $('<span>', {'class': 'voice toolbox-tags-list-description'}).text(description).appendTo(line);

        definitions.slice(0, 2).forEach(function (definition) {
            $('<span>', {'class': 'toolbox-tags-list-badge', 'aria-hidden': 'true'})
                .append($('<span>', {'class': 'toolbox-tags-swatch'})
                    .css('background-color', definition.color))
                .append($('<span>', {'class': 'toolbox-tags-list-text'})
                    .text(definition.name + ': ' + definition.value))
                .appendTo(line);
        });
        if (definitions.length > 2) {
            $('<span>', {'class': 'toolbox-tags-list-more', 'aria-hidden': 'true'})
                .text('+' + (definitions.length - 2)).appendTo(line);
        }
        line.appendTo(subject);
    }

    /** Reconcile row rebuilds with any state confirmed after their request began. */
    function reconcileListRow(uid, row) {
        row = row || (client.message_list && client.message_list.rows && client.message_list.rows[uid]);
        if (!row || !row.obj) {
            return;
        }
        const target = targetFromRow(uid);
        if (target && listResponseGeneration !== null) {
            const key = listStateKey(target);
            const previous = listRowStates.get(key);
            if (previous && previous.generation > listResponseGeneration) {
                storeListSelection(uid, previous.selected);
                return;
            }
            listRowStates.set(key, {
                generation: listResponseGeneration,
                selected: listRowSelection(uid, row).slice()
            });
        }
        renderListRow(uid, row);
    }

    /** Repaint current rows from their existing metadata without fetching messages. */
    function renderMessageList() {
        const rows = client.message_list && client.message_list.rows;
        const current = new Set();
        Object.keys(rows || {}).forEach(function (uid) {
            const target = targetFromRow(uid);
            if (target) {
                current.add(listStateKey(target));
            }
            renderListRow(uid, rows[uid]);
        });
        listRowStates.forEach(function (state, key) {
            if (!current.has(key)) {
                listRowStates.delete(key);
            }
        });
    }

    /** Store one row's confirmed tag IDs while preserving other plugins' flags. */
    function storeListSelection(uid, selected) {
        const row = client.message_list && client.message_list.rows && client.message_list.rows[uid];
        if (!row) {
            return;
        }

        const ids = selected.filter(function (id) { return /^[a-f0-9]{24}$/.test(id); });
        const message = client.env.messages && client.env.messages[uid];
        [message, row].forEach(function (item) {
            if (item) {
                if (!item.flags || typeof item.flags !== 'object') {
                    item.flags = {};
                }
                item.flags.toolbox_tags = ids.slice();
            }
        });
        renderListRow(uid, row);
    }

    /** Update only rows belonging to the confirmed UID and mailbox, even while hidden. */
    function refreshListState(state) {
        const rows = client.message_list && client.message_list.rows;
        if (!rows) {
            return;
        }
        const matching = Object.keys(rows || {}).filter(function (uid) {
            return sameTarget(targetFromRow(uid), state);
        });
        listRowStates.set(listStateKey(state), {
            generation: ++listGeneration,
            selected: state.selected.slice()
        });
        matching.forEach(function (uid) {
            storeListSelection(uid, state.selected);
        });
    }

    /** Consume core's batched keyword changes before it removes recent_flags. */
    function refreshListFlags(event) {
        const environment = event && event.response && event.response.env;
        const rows = client.message_list && client.message_list.rows;
        if (!environment || !environment.recent_flags || !rows) {
            return;
        }

        const generation = listResponseVersion(environment);
        Object.keys(environment.recent_flags).forEach(function (uid) {
            const flags = environment.recent_flags[uid];
            const separator = uid.indexOf('-');
            const target = targetOf({
                uid: separator < 0 ? uid : uid.slice(0, separator),
                folder: separator < 0 ? environment.toolbox_tags_refresh_mailbox : uid.slice(separator + 1)
            });
            if (!rows[uid] || !flags || typeof flags !== 'object'
                || !sameTarget(target, targetFromRow(uid))) {
                return;
            }

            const key = listStateKey(target);
            const previous = listRowStates.get(key);
            if (previous && previous.generation > generation) {
                storeListSelection(uid, previous.selected);
                return;
            }

            const selected = [];
            Object.keys(flags).forEach(function (keyword) {
                const match = /^tbx1_([a-f0-9]{24})$/i.exec(keyword);
                if (match && flags[keyword]) {
                    selected.push(match[1].toLowerCase());
                }
            });
            // A supplied row with no matching keywords clears tags; omitted rows stay unchanged.
            listRowStates.set(key, {generation: generation, selected: selected.slice()});
            storeListSelection(uid, selected);
        });
    }

    /** Apply saved display options and repaint every row after definition edits. */
    function updateListConfiguration(definitions, enabled) {
        listConfigurationGeneration = ++listGeneration;
        client.env.toolbox_tags_definitions = definitionsOf(definitions);
        if (typeof enabled === 'boolean') {
            client.env.toolbox_tags_show_in_list = enabled;
        }
        renderMessageList();
    }

    /** Render the opened message's tag strip outside Elastic's hidden details. */
    function renderMessage(state) {
        const header = $('#message-header');
        if (!header.length) {
            return;
        }

        let strip = $('#toolbox-tags-message');
        if (!strip.length) {
            strip = $('<div>', {
                id: 'toolbox-tags-message',
                'class': 'toolbox-tags-summary',
                'aria-label': tagText('tags')
            }).append($('<strong>', {'class': 'toolbox-tags-summary-label'}).text(tagText('tags')));

            $('<div>', {'class': 'toolbox-tags-badges', 'aria-live': 'polite'}).appendTo(strip);
            $('<button>', {type: 'button', 'class': 'toolbox-tags-edit'})
                .text(tagText('tags-edit')).on('click', function () {
                    const target = targetOf(client.env.toolbox_tags_message);
                    if (!target) {
                        return;
                    }

                    openChooser(target);
                }).appendTo(strip);
            strip.appendTo(header);
        }

        renderBadges(strip.find('.toolbox-tags-badges'), state);
    }

    /** Refresh matching list rows and the message currently displayed in this window. */
    function refreshMessage(payload) {
        const state = stateOf(payload);
        if (!state) {
            return;
        }
        refreshListState(state);
        if (!sameTarget(state, client.env.toolbox_tags_message)
            || String(client.env.uid) !== state.uid || client.env.mailbox !== state.folder) {
            return;
        }

        client.env.toolbox_tags_message = state;
        renderMessage(state);
    }

    /** Deliver confirmed state to the matching parent/preview without navigating. */
    function publishState(state) {
        refreshMessage(state);

        try {
            const parentClient = window.parent !== window ? window.parent.rcmail : null;
            const owner = parentClient || client;

            if (parentClient && typeof parentClient.toolbox_tags_refresh === 'function') {
                parentClient.toolbox_tags_refresh(state);
            }

            const frame = owner.env.contentframe && owner.get_frame_window(owner.env.contentframe);
            if (frame && frame !== window && frame.rcmail
                && typeof frame.rcmail.toolbox_tags_refresh === 'function') {
                frame.rcmail.toolbox_tags_refresh(state);
            }
        } catch (error) {
            // A preview may navigate between obtaining its window and updating it.
        }
    }

    /** Set a chooser's live status without inserting server text as markup. */
    function showStatus(context, message, error) {
        context.status.text(message || '').toggleClass('is-error', Boolean(error));
        context.status.attr('role', error ? 'alert' : 'status');
    }

    /** Enable inputs only while their server state is known and no write is pending. */
    function updateControls(context) {
        if (context.closed) {
            return;
        }

        const busy = Boolean(context.pending) || context.editorSaving;
        context.root.attr('aria-busy', busy ? 'true' : 'false');
        context.options.find('input').prop('disabled', busy || context.uncertain);
        context.reload.prop('disabled', busy).prop('hidden', !context.uncertain);
        context.views.prop('disabled', busy);
        context.editorReload.prop('disabled', busy);
    }

    /** Read fresh message state after an editor load, serializing any pending request. */
    function flushDefinitionRefresh(context) {
        if (!context.closed && context.refreshQueued && !context.pending && !context.editorSaving) {
            context.refreshQueued = false;
            sendRequest(context, 'get');
        }
    }

    /** Show a recoverable editor-load error without navigating the mail window. */
    function editorFailed(context) {
        if (!context.closed) {
            context.editorSaving = false;
            context.closeAfterEditorSave = false;
            if (context.editorFrame) {
                context.editorFrame.prop('hidden', true);
            }
            context.editorStatus.text(tagText('tags-editorfailed'))
                .addClass('is-error').attr('role', 'alert');
            context.editorReload.prop('hidden', false);
            updateControls(context);
            flushDefinitionRefresh(context);
        }
    }

    /** Keep the editor alive while its own settings form is being submitted. */
    function watchEditorSave(context, frame) {
        const saving = function () {
            if (context.closed || !context.editorFrame || context.editorFrame[0] !== frame) {
                return;
            }

            context.editorSaving = true;
            context.editorStatus.text(tagText('tags-defssaving')).removeClass('is-error').attr('role', 'status');
            updateControls(context);
        };

        const form = frame.contentDocument.querySelector('#toolbox-tags-manager').closest('form');
        if (form) {
            form.addEventListener('submit', saving);
        }
        // Native form.submit(), used by Toolbox's footer Save command, skips submit events.
        frame.contentWindow.addEventListener('beforeunload', saving);
    }

    /** Receive the existing settings form and its public definition metadata. */
    function editorLoaded(context) {
        if (context.closed) {
            return;
        }

        let definitions;
        let showInList;
        try {
            const frame = context.editorFrame[0];
            const document = frame.contentDocument;
            if (document && document.URL === 'about:blank') {
                return;
            }

            const editorClient = frame.contentWindow && frame.contentWindow.rcmail;
            definitions = editorClient && editorClient.env.toolbox_tags_definitions;
            showInList = editorClient && editorClient.env.toolbox_tags_show_in_list;
            if (!document || !document.querySelector('#toolbox-tags-manager')
                || !Array.isArray(definitions)) {
                editorFailed(context);
                return;
            }
        } catch (error) {
            editorFailed(context);
            return;
        }

        const saved = context.editorSaving;
        const signature = definitionSearchSignature(definitions);
        const searchChanged = context.editorSearchSignature !== signature;
        context.editorSearchSignature = signature;
        context.editorStatus.text('').removeClass('is-error').attr('role', 'status');
        context.editorReload.prop('hidden', true);
        context.editorFrame.prop('hidden', false);
        context.editorSaving = false;
        watchEditorSave(context, context.editorFrame[0]);
        context.definitionsRevision++;
        updateListConfiguration(definitions, showInList);

        // Definition edits change names/colours, not message flags. Update labels now.
        if (context.state) {
            context.state = stateOf(Object.assign({}, context.state, {definitions: definitions, error: ''}));
            renderChooser(context);
            publishState(context.state);
        }

        if (saved && searchChanged) {
            queueSearchRefresh();
        }

        if (context.closeAfterEditorSave) {
            context.popup.dialog('close');
            return;
        }

        context.refreshQueued = true;
        flushDefinitionRefresh(context);
    }

    /** Load the existing framed settings editor only when the user asks for it. */
    function loadDefinitionEditor(context) {
        if (context.editorFrame) {
            const previous = context.editorFrame;
            context.editorFrame = null;
            previous.remove();
        }

        context.editorFrame = $('<iframe>', {
            id: 'toolbox-tags-definition-frame',
            name: 'toolbox-tags-definitions-frame',
            'class': 'toolbox-tags-editor-frame',
            title: tagText('tags-definitions')
        }).on('load', function () {
            if (context.editorFrame && context.editorFrame[0] === this) {
                editorLoaded(context);
            }
        }).on('error', function () {
            if (context.editorFrame && context.editorFrame[0] === this) {
                editorFailed(context);
            }
        }).appendTo(context.definitionPanel);

        context.editorStatus.text(tagText('tags-defsloading')).removeClass('is-error').attr('role', 'status');
        context.editorReload.prop('hidden', true);
        context.editorFrame.attr('src', client.url('settings/plugin.toolbox.edit', {
            _section: 'tags', _framed: 1, _extwin: 0
        }));
    }

    /** Switch between assignment and definition editing inside the same modal. */
    function showView(context, view) {
        if (context.closed || context.pending || context.editorSaving || context.view === view) {
            return;
        }

        context.view = view;
        const editing = view === 'definitions';
        context.assignmentPanel.prop('hidden', editing);
        context.definitionPanel.prop('hidden', !editing);
        context.views.each(function () {
            $(this).attr('aria-pressed', $(this).attr('data-tags-view') === view ? 'true' : 'false');
        });

        context.popup.dialog('option', {
            width: Math.min(editing ? 760 : 460, Math.max(240, window.innerWidth - 40)),
            height: Math.min(editing ? 620 : 420, Math.max(240, window.innerHeight - 60))
        });

        if (editing) {
            if (!context.editorFrame) {
                loadDefinitionEditor(context);
            }
        } else {
            context.refreshQueued = true;
            flushDefinitionRefresh(context);
        }
    }

    /** Draw a grouped chooser from the last confirmed definitions and flags. */
    function renderChooser(context) {
        if (context.closed || !context.state) {
            return;
        }

        context.options.empty();
        const groups = new Map();
        const selected = new Set(context.state.selected);

        context.state.definitions.forEach(function (definition) {
            if (!groups.has(definition.name)) {
                const group = $('<fieldset>', {'class': 'toolbox-tags-option-group'})
                    .append($('<legend>').text(definition.name)).appendTo(context.options);
                groups.set(definition.name, group);
            }

            $('<label>', {'class': 'toolbox-tags-choice'})
                .append($('<input>', {type: 'checkbox', 'data-tags-id': definition.id})
                    .prop('checked', selected.has(definition.id)))
                .append($('<span>', {'class': 'toolbox-tags-swatch', 'aria-hidden': 'true'})
                    .css('background-color', definition.color))
                .append($('<span>').text(definition.value))
                .appendTo(groups.get(definition.name));
        });

        if (!context.state.definitions.length) {
            $('<p>').text(tagText('tags-opendefs'))
                .appendTo(context.options);
        }

        updateControls(context);
    }

    /** Recover from a cancelled, failed or incomplete request without assuming a write failed. */
    function requestFailed(operation, message) {
        if (!requests.has(operation.request)) {
            return;
        }

        requests.delete(operation.request);
        const context = operation.context;
        if (context.pending === operation) {
            context.pending = null;
        }

        if (!context.closed) {
            context.uncertain = true;
            renderChooser(context);
            showStatus(context, message, true);
            updateControls(context);
        }
        flushDefinitionRefresh(context);
        if (!active) {
            scheduleSearchRefresh();
        }
    }

    /** Send one correlated request while preserving the chooser's original target. */
    function sendRequest(context, kind, fields) {
        const token = 'tags_' + Date.now().toString(36) + '_' + (++sequence).toString(36);
        const operation = {
            request: token,
            context: context,
            kind: kind,
            target: context.target,
            state: context.state,
            definitionsRevision: context.definitionsRevision,
            listGeneration: ++listGeneration,
            id: fields && fields._tags_id,
            wanted: fields && fields._tags_state === '1'
        };

        const data = Object.assign({
            _uid: context.target.uid,
            _mbox: context.target.folder,
            _tags_request: token
        }, fields || {});

        requests.set(token, operation);
        context.pending = operation;
        updateControls(context);
        showStatus(context, kind === 'get' ? tagText('tags-loading') : tagText('tags-saving'), false);

        const lock = client.set_busy(true, 'loading');
        let xhr;
        try {
            xhr = client.http_post('plugin.toolbox.tags.' + kind, data, lock);
        } catch (error) {
            client.set_busy(false, null, lock);
        }

        const failure = kind === 'set'
            ? tagText('tags-saveunconfirmed')
            : tagText('tags-loadfailed');

        if (!xhr || typeof xhr.fail !== 'function' || typeof xhr.done !== 'function') {
            client.set_busy(false, null, lock);
            requestFailed(operation, failure);
            return;
        }

        xhr.fail(function () { requestFailed(operation, failure); });
        xhr.done(function () {
            // Roundcube dispatches server commands before jQuery's done handlers.
            if (requests.has(token)) {
                requestFailed(operation, failure);
            }
        });
    }

    /** Accept a response only for the exact outstanding request and message. */
    function responseOperation(payload, kind) {
        const operation = payload && requests.get(payload.request);
        if (!operation || operation.kind !== kind || !sameTarget(operation.target, payload)) {
            return null;
        }

        if (kind === 'set' && (payload.id !== operation.id
            || payload.wanted !== operation.wanted || typeof payload.success !== 'boolean'
            || (payload.selected !== null && !Array.isArray(payload.selected))
            || (payload.success && !Array.isArray(payload.selected)))) {
            return null;
        }

        return operation;
    }

    /** Complete a validated response independently of the currently open chooser. */
    function completeRequest(operation) {
        requests.delete(operation.request);
        if (operation.context.pending === operation) {
            operation.context.pending = null;
        }
    }

    /** Receive fresh tag definitions and the selected message's current flags. */
    client.toolbox_tags_state = function (payload) {
        const operation = responseOperation(payload, 'get');
        const state = payload && stateOf(payload);
        if (!operation || !state) {
            return;
        }

        completeRequest(operation);
        const context = operation.context;
        if (context.closed) {
            return;
        }

        // A settings save may have completed while this read was already in flight.
        if (operation.definitionsRevision < context.definitionsRevision
            || operation.listGeneration < listConfigurationGeneration) {
            context.refreshQueued = true;
            flushDefinitionRefresh(context);
            return;
        }

        if (state.error) {
            if (!context.closed) {
                context.uncertain = true;
                showStatus(context, state.error, true);
                updateControls(context);
            }
            flushDefinitionRefresh(context);
            return;
        }

        context.state = state;
        context.uncertain = false;
        updateListConfiguration(state.definitions);
        if (!context.closed) {
            renderChooser(context);
            showStatus(context, '', false);
        }
        publishState(state);
        flushDefinitionRefresh(context);
    };

    /** Apply authoritative state after a tag change, including a known failure state. */
    client.toolbox_tags_update_result = function (payload) {
        const operation = responseOperation(payload, 'set');
        if (!operation) {
            return;
        }

        completeRequest(operation);
        const context = operation.context;
        if (Array.isArray(payload.selected)) {
            const state = stateOf(Object.assign({}, context.state || operation.state, {
                selected: payload.selected,
                error: ''
            }));
            context.state = state;
            context.uncertain = false;
            publishState(state);
            if (payload.success) {
                queueSearchRefresh();
            }
        } else {
            context.uncertain = true;
        }

        if (!context.closed) {
            renderChooser(context);
            showStatus(context, payload.success ? tagText('tags-updated')
                : (payload.error || tagText('tags-updateunconfirmed'))
                    + (context.uncertain ? ' ' + tagText('tags-reloadhint') : ''), !payload.success);
            updateControls(context);
        }
        flushDefinitionRefresh(context);
    };

    /** Return a live dialog through the jQuery instance that actually owns it. */
    function livePopup(context) {
        const node = context && context.popup && context.popup[0];
        if (!context || context.closed || !node
            || !node.ownerDocument.documentElement.contains(node)) {
            return null;
        }

        try {
            const popup = (node.jqref || $)(node);
            return popup.dialog('instance') && popup.dialog('isOpen') ? popup : null;
        } catch (error) {
            // Navigation or another plugin may already have destroyed this widget.
            return null;
        }
    }

    /** Release an unusable chooser without dropping any unconfirmed message write. */
    function discardChooser(context) {
        context.closed = true;
        context.editorSaving = false;
        context.closeAfterEditorSave = false;
        if (active === context) {
            active = null;
        }

        const node = context.popup && context.popup[0];
        if (node) {
            const popup = (node.jqref || $)(node);
            try {
                if (popup.dialog('instance')) {
                    popup.dialog('destroy');
                }
            } catch (error) {
                // Removing a stale node is safe even if its dialog is already gone.
            }
            popup.remove();
        } else {
            context.root.remove();
        }
        scheduleSearchRefresh();
    }

    /** Open one chooser owned by the main mail window for a captured message. */
    function openChooser(value) {
        const target = targetOf(value);
        if (!target) {
            client.display_message(tagText('tags-selectone'), 'notice');
            return false;
        }

        // Keep every entry point's dialog, requests and callbacks out of a preview
        // that will be replaced when the user selects another message.
        let parentClient;
        try {
            parentClient = window.parent !== window && window.parent.rcmail;
        } catch (error) {
            // A standalone or inaccessible parent uses this window's UI.
        }
        if (parentClient && parentClient !== client
            && typeof parentClient.toolbox_tags_open === 'function') {
            return parentClient.toolbox_tags_open(target);
        }

        if (active && !livePopup(active)) {
            discardChooser(active);
        }

        // A closed chooser's write must settle before this message can be edited again.
        const pendingWrite = Array.from(requests.values()).find(function (operation) {
            return operation.kind === 'set' && sameTarget(operation.target, target);
        });
        if (pendingWrite) {
            const popup = livePopup(pendingWrite.context);
            if (popup) {
                popup.dialog('moveToTop');
            } else {
                if (!pendingWrite.context.closed) {
                    discardChooser(pendingWrite.context);
                }
                client.display_message(tagText('tags-savepending'), 'notice');
            }
            return false;
        }

        if (active && !active.closed) {
            if (sameTarget(active.target, target)) {
                active.popup.dialog('moveToTop');
                return false;
            }
            const previous = active;
            previous.popup.dialog('close');
            if (!previous.closed && !livePopup(previous)) {
                discardChooser(previous);
            }
            if (!previous.closed) {
                return false;
            }
        }

        const root = $('<div>', {id: 'toolbox-tags-dialog', 'class': 'toolbox-tags-dialog'});
        const navigation = $('<div>', {'class': 'toolbox-tags-views', role: 'group', 'aria-label': tagText('tags-options')})
            .appendTo(root);
        $('<button>', {
            type: 'button', 'class': 'toolbox-tags-view', 'data-tags-view': 'message',
            'aria-pressed': 'true', 'aria-controls': 'toolbox-tags-assignment'
        }).text(tagText('tags-message')).appendTo(navigation);
        $('<button>', {
            type: 'button', 'class': 'toolbox-tags-view', 'data-tags-view': 'definitions',
            'aria-pressed': 'false', 'aria-controls': 'toolbox-tags-definition-panel'
        }).text(tagText('tags-definitions')).appendTo(navigation);
        const assignmentPanel = $('<div>', {
            id: 'toolbox-tags-assignment', 'class': 'toolbox-tags-assignment'
        }).appendTo(root);
        const definitionPanel = $('<div>', {
            id: 'toolbox-tags-definition-panel', 'class': 'toolbox-tags-definition-panel', hidden: true
        }).appendTo(root);
        const context = {
            target: target,
            root: root,
            views: navigation.find('button'),
            view: 'message',
            assignmentPanel: assignmentPanel,
            definitionPanel: definitionPanel,
            editorFrame: null,
            editorSaving: false,
            closeAfterEditorSave: false,
            editorStatus: $('<p>', {'class': 'toolbox-tags-status', role: 'status', 'aria-live': 'polite'})
                .appendTo(definitionPanel),
            editorReload: $('<button>', {type: 'button', 'class': 'button', 'data-tags-editor-reload': 'true', hidden: true})
                .text(tagText('tags-reloadeditor')).appendTo(definitionPanel),
            definitionsRevision: 0,
            refreshQueued: false,
            options: $('<div>', {'class': 'toolbox-tags-options'}).appendTo(assignmentPanel),
            status: $('<p>', {'class': 'toolbox-tags-status', role: 'status', 'aria-live': 'polite'}).appendTo(assignmentPanel),
            reload: $('<button>', {type: 'button', 'class': 'button', 'data-tags-reload': 'true', hidden: true})
                .text(tagText('tags-reload')).appendTo(assignmentPanel),
            state: null,
            pending: null,
            uncertain: false,
            closed: false,
            popup: null
        };

        context.views.on('click', function () {
            showView(context, $(this).attr('data-tags-view'));
        });
        context.editorReload.on('click', function () {
            if (!context.pending && !context.editorSaving) {
                loadDefinitionEditor(context);
            }
        });

        root.on('change', 'input[data-tags-id]', function () {
            if (context.pending || context.editorSaving || context.uncertain || !context.state) {
                renderChooser(context);
                return;
            }

            sendRequest(context, 'set', {
                _tags_id: $(this).attr('data-tags-id'),
                _tags_state: $(this).prop('checked') ? '1' : '0'
            });
        });
        context.reload.on('click', function () {
            if (!context.pending && !context.editorSaving) {
                sendRequest(context, 'get');
            }
        });

        active = context;
        context.popup = client.show_popup_dialog(root, tagText('tags'), [{
            text: client.get_label('close'),
            'class': 'cancel mainaction',
            click: function () { this.jqref(this).dialog('close'); }
        }], {
            width: 460,
            height: 420,
            dialogClass: 'toolbox-tags-popup',
            beforeClose: function () {
                if (context.editorSaving) {
                    context.closeAfterEditorSave = true;
                    context.editorStatus.text(tagText('tags-defssavingclose'));
                    return false;
                }
                return true;
            },
            close: function () {
                context.closed = true;
                if (active === context) {
                    active = null;
                }
                (this.jqref || $)(this).remove();
                scheduleSearchRefresh();
            }
        });
        sendRequest(context, 'get');
        return false;
    }

    // These methods let the parent mail window and its preview share confirmed state.
    client.toolbox_tags_open = openChooser;
    client.toolbox_tags_refresh = refreshMessage;

    client.addEventListener('init', function () {
        if (initialized || client.env.task !== 'mail') {
            return;
        }
        initialized = true;

        client.register_command(command, function (props, source) {
            return openChooser(commandTarget(source));
        }, false);
        if (client.message_list) {
            client.message_list.addEventListener('select', updateCommand);
        }
        client.addEventListener('insertrow', function (event) {
            if (event && event.uid) {
                reconcileListRow(event.uid, event.row);
            }
        });
        client.addEventListener('listupdate', function () {
            updateCommand();
            renderMessageList();
        });
        client.addEventListener('responsebeforerefresh', refreshListFlags);
        client.addEventListener('responsebeforecheck-recent', refreshListFlags);
        client.addEventListener('responsebefore', beginListResponse);
        client.addEventListener('responseafter', endListResponse);
        listActions.forEach(function (action) {
            client.addEventListener('request' + action, stampListRequest);
        });
        client.addEventListener('requestsearch', trackSearchRequest);
        client.addEventListener('requestlist', trackSearchListing);
        client.addEventListener('beforereset-search', clearSearchTracking);
        client.addEventListener('responseafter', function (event) {
            confirmSearchResponse(event);
            if (!active) {
                scheduleSearchRefresh();
            }
        });
        positionToolbar();
        updateCommand();
        renderMessageList();

        const state = stateOf(client.env.toolbox_tags_message);
        if (state) {
            client.env.toolbox_tags_message = state;
            renderMessage(state);
        }
    });
})(jQuery);

// END OF FILE

