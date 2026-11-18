<?php

/**
 * Personal tag settings, message tagging and name search for Roundcube Toolbox.
 *
 * Users manage parent tag names and child values with explicit New, Edit,
 * and Delete controls. Definitions are stored in Roundcube user
 * preferences; editing this screen never changes IMAP message flags.
 */
final class toolbox_tool_tags extends toolbox_tool_module
{
    private const LIST_PREFERENCE = 'toolbox_tags_show_in_list';

    /** Stable translation identifiers for user-visible backend failures. */
    private const ERROR_TRANSLATIONS = [
        'Invalid tag request' => 'tags-error-invalidrequest',
        'Message is not available in this mailbox' => 'tags-error-missingmessage',
        'This tag definition no longer exists' => 'tags-error-missingtag',
        'This mailbox does not allow custom tags' => 'tags-error-tagsnotallowed',
        'The tag update was not confirmed by the mail server' => 'tags-error-updateunconfirmed',
        'Could not verify the updated message tag' => 'tags-error-verifyfailed',
        'Invalid tag operation' => 'tags-error-invalidoperation',
        'Unknown tag operation' => 'tags-error-unknownoperation',
        'Could not save the message-list display option' => 'tags-error-listsavefailed',
        'Invalid message-list display option' => 'tags-error-invalidlistoption',
        'Invalid tag form value' => 'tags-error-invalidformvalue',
        'Too many tag names' => 'tags-error-toomanynames',
        'Unknown tag name' => 'tags-error-unknownname',
        'Unknown personal tag definition' => 'tags-error-unknowntag',
        'Too many personal tag definitions' => 'tags-error-toomanyvalues',
        'Restore the tag before editing it' => 'tags-error-restorefirst',
        'Invalid personal tag definition store' => 'tags-error-invalidstore',
        'Corrupted personal tag definition' => 'tags-error-corrupteddefinition',
        'Corrupted personal tag text' => 'tags-error-corruptedtext',
        'Duplicate tag group identifier' => 'tags-error-duplicategroupid',
        'Corrupted tag groups' => 'tags-error-corruptedgroups',
        'Corrupted tag group' => 'tags-error-corruptedgroup',
        'Corrupted tag group name' => 'tags-error-corruptedgroupname',
        'Duplicate tag group names' => 'tags-error-duplicategroupname',
        'Tag value has no valid parent group' => 'tags-error-noparent',
        'Could not save personal tag definitions' => 'tags-error-definitionssavefailed',
        'Tag name is not active' => 'tags-error-inactive',
        'Tag name already exists' => 'tags-error-nameexists',
        'Tag value already exists' => 'tags-error-valueexists',
        'Invalid tag identifier' => 'tags-error-invalidid',
        'Tag name must contain valid, nonempty text' => 'tags-error-invalidname',
        'Tag value must contain valid, nonempty text' => 'tags-error-invalidvalue',
        'Invalid tag color' => 'tags-error-invalidcolor',
    ];

    private ?toolbox_tag_definitions $definitions = null;
    private ?array $mail_definitions = null;
    private array $message_tag_states = [];

    /**
     * Load tag definitions and register the mail controls and AJAX actions.
     *
     * @return void
     */
    public function init(): void
    {
        toolbox_logger::step('tags', 'init_start');
        require_once __DIR__ . '/tags/definitions.php';
        $this->definitions = new toolbox_tag_definitions($this->preferences);

        if ($this->rcube->task === 'mail') {
            $this->plugin->register_action('plugin.toolbox.tags.get', [$this, 'get_message_tags']);
            $this->plugin->register_action('plugin.toolbox.tags.set', [$this, 'set_message_tag']);
            $this->plugin->add_hook('messages_list', [$this, 'message_tags_list']);
            $this->plugin->add_hook('imap_search_before', [$this, 'message_tags_search']);
            $list_actions = ['list', 'search', 'refresh', 'check-recent'];
            if (in_array($this->rcube->action, ['', 'show', 'preview'], true)
                || in_array($this->rcube->action, $list_actions, true)) {
                try {
                    $definitions = $this->mail_tag_definitions();
                } catch (LogicException | RuntimeException $exception) {
                    toolbox_logger::error('tags', 'init_definitions', 'read_failed');
                    // A broken definition store must not prevent reading mail.
                    $definitions = [];
                }
                $this->export_tag_environment($definitions);
            }
            if (in_array($this->rcube->action, $list_actions, true)) {
                $generation = rcube_utils::get_input_value('_toolbox_tags_generation', rcube_utils::INPUT_GPC);
                $this->rcube->output->set_env('toolbox_tags_generation',
                    is_string($generation) && preg_match('/\A[0-9]{1,10}\z/D', $generation)
                        ? $generation : '0');
            }
            if ($this->rcube->action === 'search'
                && rcube_utils::get_input_string('_continue', rcube_utils::INPUT_GET) === '') {
                $this->rcube->output->set_env('toolbox_tags_search_expanded', false);
                $this->rcube->output->set_env('toolbox_tags_search_query',
                    trim(rcube_utils::get_input_string('_q', rcube_utils::INPUT_GET, true)));
            }
            if (in_array($this->rcube->action, ['refresh', 'check-recent'], true)) {
                $folder = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_GPC);
                $this->rcube->output->set_env('toolbox_tags_refresh_mailbox',
                    self::valid_mailbox($folder) ? $folder : '');
            }
            if (in_array($this->rcube->action, ['show', 'preview'], true)) {
                $this->plugin->add_hook('message_headers_output', [$this, 'message_tags_header']);
            }
            if (in_array($this->rcube->action, ['', 'show', 'preview'], true)) {
                $this->plugin->include_stylesheet('skins/elastic/toolbox.tags.css');
                $this->plugin->include_script('js/toolbox.tags.message.js');
                $this->plugin->add_button([
                    'id' => 'rcmbtntoolboxtags',
                    'command' => 'plugin.toolbox.tags',
                    'type' => 'link',
                    'class' => 'toolbox-tags disabled',
                    'classact' => 'toolbox-tags',
                    'aria-label' => $this->plugin->gettext('tags'),
                    'content' => html::tag('span', ['class' => 'inner'], rcmail::Q($this->plugin->gettext('tags'))),
                ], 'toolbar');
            }
        }
    }

    /**
     * Expose current definitions and display options for the settings editor.
     *
     * @return void
     */
    public function include_edit_assets(): void
    {
        toolbox_logger::step('tags', 'settings_assets');
        $this->export_tag_environment($this->definitions->all());
        $this->plugin->include_stylesheet('skins/elastic/toolbox.tags.css');
        $this->plugin->include_script('js/toolbox.tags.js');
    }

    /**
     * Share current display definitions and the per-user message-list option.
     *
     * Mail-list responses, including check-recent, carry this environment so the
     * browser can reconcile newly fetched flags with current definitions.
     * Settings exports read live preferences after a successful form save.
     * Tag get/set actions use their own response guards instead of exporting
     * global configuration that could overwrite a newer settings change.
     *
     * @param array $definitions Active definitions from the preference store.
     * @return void
     */
    private function export_tag_environment(array $definitions): void
    {
        $this->rcube->output->set_env('toolbox_tags_definitions',
            self::public_tag_definitions($definitions));
        $this->rcube->output->set_env(self::LIST_PREFERENCE, $this->show_tags_in_list());
        $this->rcube->output->set_env('toolbox_tags_labels', $this->client_labels());
    }

    /**
     * Export only the Tags strings used by its Settings and mail scripts.
     *
     * @return array Localised client labels indexed by their stable key.
     */
    private function client_labels(): array
    {
        $keys = [
            'tags', 'tags-newname', 'tags-editnametitle', 'tags-newvalue',
            'tags-editvalue', 'tags-parentprefix', 'tags-confirmdeletegroup', 'tags-confirmdeletevalue',
            'tags-deleteirreversible', 'tags-unavailable', 'tags-noneassigned', 'tags-listprefix',
            'tags-edit', 'tags-editorfailed', 'tags-defssaving', 'tags-definitions',
            'tags-defsloading', 'tags-opendefs', 'tags-loading', 'tags-saving',
            'tags-saveunconfirmed', 'tags-loadfailed', 'tags-updated', 'tags-updateunconfirmed',
            'tags-reloadhint', 'tags-selectone', 'tags-savepending', 'tags-options',
            'tags-message', 'tags-reloadeditor', 'tags-reload', 'tags-defssavingclose',
        ];
        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = $this->plugin->gettext($key);
        }
        return $labels;
    }

    /**
     * Read the display preference, defaulting to enabled for an unset value.
     *
     * @return bool Whether personal tag badges should appear in message rows.
     */
    private function show_tags_in_list(): bool
    {
        return (bool) $this->preferences->get(self::LIST_PREFERENCE, true);
    }

    /**
     * Reuse one active-definition lookup throughout a mail request.
     *
     * Mail actions never edit definitions. Settings uses uncached reads so
     * its form and iframe environment reflect the save in the same request.
     *
     * @return array Active definitions including their server keywords.
     */
    private function mail_tag_definitions(): array
    {
        if ($this->mail_definitions === null) {
            $this->mail_definitions = $this->definitions->all();
        }
        return $this->mail_definitions;
    }

    /**
     * Include matching personal-tag assignments in a plain mailbox search.
     *
     * A whole parent name matches each active child; a whole value matches all
     * active definitions using that value. The comparison ignores case. The
     * normal text criterion stays inside the same OR, while Type and Date
     * restrictions apply to both branches. User text never becomes an IMAP
     * keyword: only stable IDs from the validated definition store are used.
     *
     * Roundcube retains the expanded criterion for pagination and refreshes.
     * Continuations and already modified criteria must not be expanded again.
     *
     * @param array $args Search parameters supplied by imap_search_before.
     * @return array Search parameters with an optional personal-tag OR branch.
     */
    public function message_tags_search(array $args): array
    {
        if ($this->rcube->task !== 'mail' || $this->rcube->action !== 'search'
            || ($args['result'] ?? null) !== null || !is_string($args['search'] ?? null)
            || !class_exists('rcmail_action_mail_search', false)
            || rcube_utils::get_input_string('_continue', rcube_utils::INPUT_GET) !== '') {
            return $args;
        }

        $query = trim(rcube_utils::get_input_string('_q', rcube_utils::INPUT_GET, true));
        $name = self::plain_tag_search_name($query);
        if ($name === null) {
            return $args;
        }

        $headers = trim(rcube_utils::get_input_string('_headers', rcube_utils::INPUT_GET));
        $filter = trim(rcube_utils::get_input_string('_filter', rcube_utils::INPUT_GET));
        $interval = trim(rcube_utils::get_input_string('_interval', rcube_utils::INPUT_GET));
        $original = rcmail_action_mail_search::search_input($query, $headers, $filter, $interval);
        if ($args['search'] !== $original) {
            // Preserve another plugin's query and Roundcube's stored search set.
            return $args;
        }

        try {
            $definitions = $this->mail_tag_definitions();
        } catch (LogicException | RuntimeException $exception) {
            toolbox_logger::error('tags', 'search_definitions', 'read_failed');
            return $args;
        }

        $name = mb_strtolower($name, 'UTF-8');
        $keywords = [];
        foreach ($definitions as $definition) {
            if (mb_strtolower($definition['name'], 'UTF-8') === $name
                || mb_strtolower($definition['value'], 'UTF-8') === $name) {
                $keyword = toolbox_tag_definitions::keyword($definition['id']);
                $keywords[$keyword] = 'KEYWORD ' . $keyword;
            }
        }
        if ($keywords === []) {
            return $args;
        }

        $text = rcmail_action_mail_search::search_input($query, $headers);
        if ($text === '') {
            return $args;
        }
        $constraints = rcmail_action_mail_search::search_input('', '', $filter, $interval);
        $tags = str_repeat('OR ', count($keywords) - 1) . implode(' ', $keywords);
        $args['search'] = trim($constraints . ' OR (' . $text . ') (' . $tags . ')');
        $this->rcube->output->set_env('toolbox_tags_search_expanded', true);
        toolbox_logger::step('tags', 'search_expanded');

        return $args;
    }

    /**
     * Read one complete tag name or value without reinterpreting search syntax.
     *
     * Multiword names work directly or as one quoted phrase. Explicit fields,
     * Boolean operators and negation keep Roundcube's existing meaning. A
     * quoted complete name may contain punctuation or reserved words.
     *
     * @param string $query Trimmed text from the normal message search box.
     * @return ?string Literal complete name, or null for a structured query.
     */
    private static function plain_tag_search_name(string $query): ?string
    {
        if ($query === '') {
            return null;
        }
        if (preg_match('/^"(?:[^"\\\\]|\\\\.)*"$/uD', $query)) {
            $name = str_replace('\"', '"', substr($query, 1, -1));
            return $name !== '' ? $name : null;
        }
        if (str_contains($query, '"')
            || preg_match('/(?:^|\s)(?:-?[a-zA-Z_-]+:|(?:OR|AND)(?:\s|$))/iu', $query)) {
            return null;
        }
        return $query;
    }

    /**
     * Render tag names as parent folders, with values as nested children.
     *
     * @return array Settings fieldset compatible with Toolbox's renderer.
     */
    public function render_form(): array
    {
        toolbox_logger::step('tags', 'settings_render');
        $groups = $this->definitions->groups();
        $items = $this->definitions->all();
        $children = [];
        foreach ($items as $item) {
            $key = mb_strtolower($item['name'], 'UTF-8');
            $children[$key][] = $item;
        }
        foreach ($children as &$values) {
            usort($values, static fn(array $a, array $b): int =>
                strcmp(mb_strtolower($a['value'], 'UTF-8'), mb_strtolower($b['value'], 'UTF-8')));
        }
        unset($values);

        $active = '';
        foreach ($groups as $group) {
            $key = mb_strtolower($group['name'], 'UTF-8');
            $active .= $this->render_group($group, $children[$key] ?? []);
        }

        if ($active === '') {
            $active = html::tag('li', ['class' => 'toolbox-tags-empty'],
                rcmail::Q($this->plugin->gettext('tags-empty')));
        }

        $manager = html::tag('div', ['id' => 'toolbox-tags-manager', 'class' => 'toolbox-tags-manager'],
            $this->render_list_preference()
            . html::tag('div', ['class' => 'toolbox-tags-toolbar'],
                html::tag('button', [
                    'type' => 'button', 'class' => 'button primary',
                    'data-tags-action' => 'new-group',
                ], self::icon('plus') . rcmail::Q($this->plugin->gettext('tags-newtag')))
            )
            . html::tag('ul', ['class' => 'toolbox-tags-tree'], $active)
            . $this->render_editor()
        );

        return [
            'intro' => $manager,
            'name' => rcmail::Q($this->plugin->gettext('tags-title')),
            'class' => 'toolbox-tagstable',
            'cols' => 1,
            'rows' => [],
        ];
    }

    /**
     * Render the optional message-list badges setting above the tag tree.
     *
     * The hidden marker distinguishes an unchecked box from older forms
     * that do not include this option and must leave its saved value alone.
     *
     * @return string Escaped checkbox, label and row-height explanation.
     */
    private function render_list_preference(): string
    {
        $input = new html_checkbox([
            'name' => '_tags_show_in_list',
            'id' => 'rcmfd_tags_show_in_list',
            'value' => '1',
            'aria-describedby' => 'toolbox-tags-preference-help',
        ]);

        return html::tag('div', ['class' => 'toolbox-tags-preference'],
            html::tag('input', [
                'type' => 'hidden', 'name' => '_tags_options_present', 'value' => '1',
            ])
            . $input->show($this->show_tags_in_list() ? '1' : '')
            . html::label('rcmfd_tags_show_in_list', rcmail::Q($this->plugin->gettext('tags-showinlist')))
            . html::tag('p', [
                'id' => 'toolbox-tags-preference-help', 'class' => 'toolbox-tags-preference-help',
            ], rcmail::Q($this->plugin->gettext('tags-listhelp'))));
    }

    /**
     * Attach selected tag IDs to rows using the headers already fetched.
     *
     * This hook performs no IMAP queries. Roundcube 1.7 casts header flags
     * to integers, so nested metadata uses the supported list_flags merge.
     * Preserve other plugins' extra_flags, including Thunderbird labels.
     * IDs are sent even when badges are hidden, allowing an immediate opt-in
     * without reloading the current mailbox search or selection.
     *
     * @param array $args Messages and columns supplied by messages_list.
     * @return array The same list with personal-tag row metadata attached.
     */
    public function message_tags_list(array $args): array
    {
        try {
            $definitions = $this->mail_tag_definitions();
        } catch (LogicException | RuntimeException $exception) {
            toolbox_logger::error('tags', 'list_definitions', 'read_failed');
            $definitions = [];
        }

        $keywords = [];
        foreach ($definitions as $definition) {
            $keywords[$definition['keyword']] = $definition['id'];
        }

        foreach ($args['messages'] ?? [] as $header) {
            if (!is_object($header)) {
                continue;
            }
            $selected = [];
            foreach ((array) ($header->flags ?? []) as $keyword => $applied) {
                if ($applied && isset($keywords[$keyword])) {
                    $selected[] = $keywords[$keyword];
                }
            }
            $list_flags = (array) ($header->list_flags ?? []);
            $extra_flags = (array) ($list_flags['extra_flags'] ?? []);
            $extra_flags['toolbox_tags'] = $selected;
            $list_flags['extra_flags'] = $extra_flags;
            $header->list_flags = $list_flags;
        }

        return $args;
    }

    /**
     * Render a parent group and its child values.
     *
     * @param array $group Parent group data.
     * @param array $values Child value definitions.
     * @return string Escaped HTML tree item.
     */
    private function render_group(array $group, array $values): string
    {
        $current = '';
        foreach ($values as $value) {
            $current .= $this->render_value($value);
        }
        if ($current === '') {
            $current = html::tag('li', ['class' => 'toolbox-tags-empty'],
                rcmail::Q($this->plugin->gettext('tags-emptyvalues')));
        }

        $actions = self::action_button('new-value', $this->plugin->gettext('tags-newvalue'), 'plus', [
            'data-tags-group' => $group['id'],
            'data-tags-group-name' => $group['name'],
        ]) . self::action_button('edit-group', $this->plugin->gettext('tags-editname'), 'pencil', [
            'data-tags-id' => $group['id'],
            'data-tags-name' => $group['name'],
        ]) . self::action_button('delete-group', $this->plugin->gettext('tags-deletename'), 'trash', [
            'data-tags-id' => $group['id'],
            'data-tags-name' => $group['name'],
        ]);

        $heading = html::tag('summary', null,
            self::icon('folder') . html::tag('strong', null, rcmail::Q($group['name']))
            . html::tag('span', ['class' => 'toolbox-tags-count'], (string) count($values)));

        return html::tag('li', ['class' => 'toolbox-tags-group'],
            html::tag('details', ['open' => 'open'], $heading
                . html::tag('div', ['class' => 'toolbox-tags-actions'], $actions)
                . html::tag('ul', ['class' => 'toolbox-tags-values'], $current)));
    }

    /**
     * Render one child value with its color and Edit/Delete controls.
     *
     * @param array $value Child definition.
     * @return string Escaped HTML value entry.
     */
    private function render_value(array $value): string
    {
        $actions = self::action_button('edit-value', $this->plugin->gettext('tags-editvalue'), 'pencil', [
            'data-tags-id' => $value['id'],
            'data-tags-name' => $value['name'],
            'data-tags-value' => $value['value'],
            'data-tags-color' => $value['color'],
        ]) . self::action_button('delete-value', $this->plugin->gettext('tags-deletevalue'), 'trash', [
            'data-tags-id' => $value['id'],
            'data-tags-name' => $value['name'],
            'data-tags-value' => $value['value'],
        ]);

        return html::tag('li', ['class' => 'toolbox-tags-value'],
            html::tag('span', ['class' => 'toolbox-tags-swatch',
                'style' => 'background-color:' . $value['color']], '')
            . html::tag('span', ['class' => 'toolbox-tags-value-name'], rcmail::Q($value['value']))
            . html::tag('span', ['class' => 'toolbox-tags-actions'], $actions));
    }

    /**
     * Render the inline editor, shown only after New or Edit is selected.
     *
     * @return string HTML containing CSRF-protected form inputs and buttons.
     */
    private function render_editor(): string
    {
        $hidden = '';
        foreach (['_tags_action', '_tags_id', '_tags_group_id'] as $key) {
            $hidden .= html::tag('input', ['type' => 'hidden', 'name' => $key,
                'id' => 'rcmfd' . $key, 'value' => '']);
        }
        $name = new html_inputfield([
            'type' => 'text', 'name' => '_tags_name', 'id' => 'rcmfd_tags_name',
            'maxlength' => 120, 'autocomplete' => 'off',
        ]);
        $value = new html_inputfield([
            'type' => 'text', 'name' => '_tags_value', 'id' => 'rcmfd_tags_value',
            'maxlength' => 255, 'autocomplete' => 'off',
        ]);
        $name_row = html::tag('div', ['id' => 'toolbox-tags-name-row', 'class' => 'toolbox-tags-field'],
            html::label('rcmfd_tags_name', rcmail::Q($this->plugin->gettext('tags-name'))) . $name->show(''));
        $value_row = html::tag('div', ['id' => 'toolbox-tags-value-row', 'class' => 'toolbox-tags-field'],
            html::label('rcmfd_tags_value', rcmail::Q($this->plugin->gettext('tags-value'))) . $value->show(''));
        $color_row = html::tag('div', ['id' => 'toolbox-tags-color-row', 'class' => 'toolbox-tags-field'],
            html::label('rcmfd_tags_color', rcmail::Q($this->plugin->gettext('tags-color')))
            . html::tag('input', [
                'type' => 'color', 'name' => '_tags_color', 'id' => 'rcmfd_tags_color',
                'value' => '#6478C4', 'title' => $this->plugin->gettext('tags-choosecolor'),
            ]));

        return html::tag('div', ['id' => 'toolbox-tags-editor', 'class' => 'toolbox-tags-editor',
            'hidden' => 'hidden'],
            $hidden
            . html::tag('h3', ['id' => 'toolbox-tags-editor-title'], rcmail::Q($this->plugin->gettext('tags-newtag')))
            . html::tag('p', ['id' => 'toolbox-tags-parent-label', 'class' => 'toolbox-tags-parent'], '')
            . $name_row . $value_row . $color_row
            . html::tag('div', ['class' => 'toolbox-tags-editor-actions'],
                html::tag('button', ['type' => 'submit', 'class' => 'button primary'], rcmail::Q($this->plugin->gettext('tags-save')))
                . html::tag('button', ['type' => 'button', 'class' => 'button',
                    'data-tags-action' => 'cancel'], rcmail::Q($this->plugin->gettext('tags-cancel')))));
    }

    /**
     * Render an accessible action button with a simple SVG icon.
     *
     * @param string $action Client-side operation name.
     * @param string $label Accessible action label.
     * @param string $icon Icon type, from the internal whitelist.
     * @param array $extra Additional escaped data attributes.
     * @return string HTML button.
     */
    private static function action_button(string $action, string $label, string $icon, array $extra = []): string
    {
        $class = $icon === 'trash' ? 'toolbox-tags-button danger' : 'toolbox-tags-button';
        return html::tag('button', [
            'type' => 'button', 'class' => $class, 'data-tags-action' => $action,
            'title' => $label, 'aria-label' => $label,
        ] + $extra, self::icon($icon) . ($action === 'new-value' ? rcmail::Q($label) : ''));
    }

    /**
     * Generate fixed, non-user-supplied SVG icon markup.
     *
     * @param string $name One of folder, plus, pencil, trash.
     * @return string Decorative SVG icon with aria-hidden.
     */
    private static function icon(string $name): string
    {
        $shapes = [
            'folder' => '<path d="M3 7a2 2 0 0 1 2-2h4l2 2h8a2 2 0 0 1 2 2v10H3z"/>',
            'plus' => '<path d="M12 5v14M5 12h14"/>',
            'pencil' => '<path d="m16 5 3 3M4 20l4-.8L20 7a2 2 0 0 0-3-3L5 16z"/>',
            'trash' => '<path d="M3 6h18M8 6V4h8v2m3 0-1 14H6L5 6m5 4v7m4-7v7"/>',
        ];
        return '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"'
            . ' viewBox="0 0 24 24" fill="none" stroke="currentColor"'
            . ' stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"'
            . ' aria-hidden="true">' . ($shapes[$name] ?? '') . '</svg>';
    }

    /**
     * Export the opened message's tags for the visible message-header strip.
     *
     * Elastic renders individual headers and the header table separately.
     * The state cache prevents those hook calls from fetching flags twice.
     *
     * @param array $args Roundcube message_headers_output hook arguments.
     * @return array Unchanged message-header output.
     */
    public function message_tags_header(array $args): array
    {
        $uid = $args['uid'] ?? null;
        $folder = $args['folder'] ?? null;
        if (self::valid_message_reference($uid, $folder)) {
            $this->rcube->output->set_env('toolbox_tags_message',
                $this->message_tag_state((string) $uid, $folder));
        }

        return $args;
    }

    /**
     * Return definitions and checked tags for one requested mailbox message.
     *
     * A client correlation token lets the UI discard responses for a dialog
     * that has closed or moved to another message while the request ran.
     *
     * @return void
     */
    public function get_message_tags(): void
    {
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $folder = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_POST);
        $request = rcube_utils::get_input_value('_tags_request', rcube_utils::INPUT_POST);
        $state = [
            'uid' => is_string($uid) || is_int($uid) ? (string) $uid : '',
            'folder' => is_string($folder) ? $folder : '',
            'definitions' => [],
            'selected' => [],
            'error' => $this->plugin->gettext('tags-error-invalidrequest'),
        ];

        if (self::valid_message_reference($uid, $folder) && self::valid_request_token($request)) {
            $state = $this->message_tag_state((string) $uid, $folder);
        }
        $state['request'] = is_string($request) ? $request : '';

        $this->rcube->output->command('toolbox_tags_state', $state);
        $this->rcube->output->send();
    }

    /**
     * Project active definitions into display data shared with the browser.
     *
     * Settings dialogs and message controls use the same stable IDs, names,
     * values and colors. Keywords are omitted from this payload; their stable
     * format is TBX1_ followed by the uppercase ID (see definitions::keyword).
     * Roundcube's check-recent response lowercases those keys, which lets the
     * browser reconcile external tag changes without extra IMAP requests.
     *
     * @param array $definitions Active definitions from the preference store.
     * @return array Public definitions containing id, name, value and color.
     */
    private static function public_tag_definitions(array $definitions): array
    {
        $public = [];
        foreach ($definitions as $definition) {
            $public[] = [
                'id' => $definition['id'],
                'name' => $definition['name'],
                'value' => $definition['value'],
                'color' => $definition['color'],
            ];
        }
        return $public;
    }

    /**
     * Read and cache one message's current tags without changing preferences.
     *
     * This endpoint exposes display data and definition IDs. Even an empty
     * definition list still verifies that the requested message exists.
     *
     * @param string $uid Validated message UID.
     * @param string $folder Validated mailbox name.
     * @return array Message identity, public definitions, checked IDs and error.
     */
    private function message_tag_state(string $uid, string $folder): array
    {
        $key = $folder . "\0" . $uid;
        if (isset($this->message_tag_states[$key])) {
            return $this->message_tag_states[$key];
        }

        $state = [
            'uid' => $uid,
            'folder' => $folder,
            'definitions' => [],
            'selected' => [],
            'error' => '',
        ];
        try {
            $definitions = $this->mail_tag_definitions();
            $flags = $this->rcube->get_storage()->list_flags($folder, [$uid]);
            if (!isset($flags[$uid]) || !is_array($flags[$uid])) {
                throw new RuntimeException('Message is not available in this mailbox');
            }
            $state['definitions'] = self::public_tag_definitions($definitions);
            $state['selected'] = self::selected_tag_ids($definitions, $flags[$uid]);
        } catch (LogicException | RuntimeException $exception) {
            toolbox_logger::error('tags', 'message_state', 'read_failed');
            $state['definitions'] = [];
            $state['selected'] = [];
            $state['error'] = $this->localized_error($exception);
        }

        $this->message_tag_states[$key] = $state;
        return $state;
    }

    /**
     * Apply or remove one defined tag and return its verified message state.
     *
     * Roundcube supplies authentication and POST CSRF protection. Requests
     * can write only the keyword belonging to an active personal definition;
     * no other message flags are replaced or cleared.
     *
     * @return void
     */
    public function set_message_tag(): void
    {
        $uid = rcube_utils::get_input_value('_uid', rcube_utils::INPUT_POST);
        $folder = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_POST);
        $request = rcube_utils::get_input_value('_tags_request', rcube_utils::INPUT_POST);
        $id = rcube_utils::get_input_value('_tags_id', rcube_utils::INPUT_POST);
        $state = rcube_utils::get_input_value('_tags_state', rcube_utils::INPUT_POST);
        $wanted = $state === '1';
        $result = [
            'uid' => is_string($uid) || is_int($uid) ? (string) $uid : '',
            'folder' => is_string($folder) ? $folder : '',
            'request' => is_string($request) ? $request : '',
            'id' => is_string($id) ? $id : '',
            'success' => false,
            'wanted' => $wanted,
            'selected' => null,
            'error' => $this->plugin->gettext('tags-error-invalidrequest'),
        ];
        $valid = self::valid_message_reference($uid, $folder)
            && self::valid_request_token($request)
            && is_string($id) && preg_match('/\A[a-f0-9]{24}\z/D', $id)
            && in_array($state, ['0', '1'], true);

        if (!$valid) {
            toolbox_logger::error('tags', 'message_update', 'invalid_request');
            $this->send_message_tag_result($result);
            return;
        }

        toolbox_logger::step('tags', 'message_update_start');
        try {
            $definitions = $this->mail_tag_definitions();
            $definition = null;
            foreach ($definitions as $candidate) {
                if ($candidate['id'] === $id) {
                    $definition = $candidate;
                    break;
                }
            }
            if ($definition === null) {
                throw new InvalidArgumentException('This tag definition no longer exists');
            }

            $uid = (string) $uid;
            $storage = $this->rcube->get_storage();
            $keyword = $definition['keyword'];
            $before = $storage->list_flags($folder, [$uid]);
            if (!isset($before[$uid]) || !is_array($before[$uid])) {
                throw new RuntimeException('Message is not available in this mailbox');
            }
            $result['selected'] = self::selected_tag_ids($definitions, $before[$uid]);
            $has_tag = !empty($before[$uid][$keyword]);
            if ($has_tag !== $wanted) {
                $permanent = (array) $storage->get_permflags($folder);
                // Let Roundcube decide when the server omits PERMANENTFLAGS.
                $permitted = empty($permanent);
                foreach ($permanent as $flag) {
                    if (is_string($flag)
                        && (strcasecmp($flag, '\\*') === 0 || strcasecmp($flag, $keyword) === 0)) {
                        $permitted = true;
                        break;
                    }
                }
                if (!$permitted) {
                    throw new RuntimeException('This mailbox does not allow custom tags');
                }

                // A STORE attempt makes the preceding snapshot insufficient.
                $result['selected'] = null;
                $command = ($wanted ? '' : 'UN') . $keyword;
                $stored = $storage->set_flag($uid, $command, $folder);
                $after = $storage->list_flags($folder, [$uid]);
                $verified = isset($after[$uid]) && is_array($after[$uid]);
                if ($verified) {
                    $result['selected'] = self::selected_tag_ids($definitions, $after[$uid]);
                }
                if (!$stored) {
                    throw new RuntimeException('The tag update was not confirmed by the mail server');
                }
                if (!$verified || !empty($after[$uid][$keyword]) !== $wanted) {
                    throw new RuntimeException('Could not verify the updated message tag');
                }
            }

            $result['success'] = true;
            $result['error'] = '';
            toolbox_logger::step('tags', 'message_update_complete');
        } catch (LogicException | RuntimeException $exception) {
            toolbox_logger::error('tags', 'message_update', 'operation_failed');
            $result['error'] = $this->localized_error($exception);
        }

        $this->send_message_tag_result($result);
    }

    /**
     * Match active definitions against one message's fetched IMAP flags.
     *
     * @param array $definitions Active definitions including server keywords.
     * @param array $flags Roundcube's uppercase keyword-to-state map.
     * @return array Stable IDs of the currently applied personal tags.
     */
    private static function selected_tag_ids(array $definitions, array $flags): array
    {
        $selected = [];
        foreach ($definitions as $definition) {
            if (!empty($flags[$definition['keyword']])) {
                $selected[] = $definition['id'];
            }
        }
        return $selected;
    }

    /**
     * Validate a single UID and mailbox before passing them to IMAP methods.
     *
     * @param mixed $uid Untrusted UID input.
     * @param mixed $folder Untrusted mailbox input.
     * @return bool Whether both values identify a permitted request shape.
     */
    private static function valid_message_reference($uid, $folder): bool
    {
        return (is_string($uid) || is_int($uid))
            && preg_match('/\A[1-9][0-9]{0,15}\z/D', (string) $uid)
            && self::valid_mailbox($folder);
    }

    /**
     * Validate mailbox context for message requests and refresh responses.
     *
     * Refresh metadata must identify the originating mailbox so a late
     * response cannot apply unqualified UIDs to a newly selected folder.
     *
     * @param mixed $folder Untrusted mailbox input.
     * @return bool Whether the value is a bounded, nonempty mailbox string.
     */
    private static function valid_mailbox($folder): bool
    {
        return is_string($folder) && $folder !== '' && strlen($folder) <= 1024
            && !preg_match('/[\x00-\x1F\x7F]/', $folder);
    }

    /**
     * Accept only bounded, scalar correlation tokens from the message UI.
     *
     * @param mixed $request Untrusted request correlation token.
     * @return bool Whether the token can be returned to the waiting client.
     */
    private static function valid_request_token($request): bool
    {
        return is_string($request) && preg_match('/\A[A-Za-z0-9_-]{1,80}\z/D', $request);
    }

    /**
     * Finish one asynchronous update with a message-scoped response object.
     *
     * @param array $result Correlated identity, success, checked IDs and error.
     * @return void
     */
    private function send_message_tag_result(array $result): void
    {
        $this->rcube->output->command('toolbox_tags_update_result', $result);
        $this->rcube->output->send();
    }

    /**
     * Persist the tree operation and list option through the preference API.
     *
     * @param object $storage Existing Toolbox storage adapter (not used here).
     * @return ?array Empty storage payload on success or null on error.
     */
    public function prepare_save($storage): ?array
    {
        toolbox_logger::step('tags', 'settings_save_start');
        $operation = rcube_utils::get_input_value('_tags_action', rcube_utils::INPUT_POST);
        $id = rcube_utils::get_input_value('_tags_id', rcube_utils::INPUT_POST);
        $group_id = rcube_utils::get_input_value('_tags_group_id', rcube_utils::INPUT_POST);
        $name = rcube_utils::get_input_value('_tags_name', rcube_utils::INPUT_POST);
        $value = rcube_utils::get_input_value('_tags_value', rcube_utils::INPUT_POST);
        $color = rcube_utils::get_input_value('_tags_color', rcube_utils::INPUT_POST);

        try {
            // Validate the option before any definition-changing operation.
            $show_in_list = self::posted_list_preference();
            if ($operation !== null && !is_string($operation)) {
                throw new InvalidArgumentException('Invalid tag operation');
            }
            switch ($operation) {
                case null:
                case '':
                    break;
                case 'create_group':
                    $this->definitions->create_group(self::require_post_string($name));
                    break;
                case 'rename_group':
                    $this->definitions->rename_group(self::require_post_string($id), self::require_post_string($name));
                    break;
                case 'delete_group':
                    $this->definitions->delete_group(self::require_post_string($id));
                    break;
                case 'create_value':
                    $this->definitions->create_value(self::require_post_string($group_id),
                        self::require_post_string($value), self::require_post_string($color));
                    break;
                case 'update_value':
                    $this->definitions->update_value(self::require_post_string($id),
                        self::require_post_string($value), self::require_post_string($color));
                    break;
                case 'delete_value':
                    $this->definitions->delete(self::require_post_string($id));
                    break;
                default:
                    throw new InvalidArgumentException('Unknown tag operation');
            }
            if ($show_in_list !== null && $show_in_list !== $this->show_tags_in_list()
                && !$this->preferences->save([self::LIST_PREFERENCE => $show_in_list])) {
                throw new RuntimeException('Could not save the message-list display option');
            }
        } catch (InvalidArgumentException | LogicException | OutOfBoundsException
            | OverflowException | UnexpectedValueException | RuntimeException $exception) {
            toolbox_logger::error('tags', 'settings_save', 'operation_failed');
            $this->rcube->output->command('display_message',
                rcmail::Q($this->localized_error($exception)), 'error');
            return null;
        }
        toolbox_logger::step('tags', 'settings_save_complete');
        return ['new_settings' => []];
    }


    /**
     * Translate known Tags backend errors before they reach the browser.
     *
     * Definitions keep their original exception text for internal callers;
     * unknown exception messages are never exposed to the user.
     *
     * @param Throwable $exception Failed Tags operation.
     * @return string Localised, user-safe error message.
     */
    private function localized_error(Throwable $exception): string
    {
        $key = self::ERROR_TRANSLATIONS[$exception->getMessage()] ?? 'tags-error-unexpected';
        return $this->plugin->gettext($key);
    }

    /**
     * Validate a posted display option while preserving older settings forms.
     *
     * @return ?bool Checkbox state, or null when the form has no option marker.
     */
    private static function posted_list_preference(): ?bool
    {
        $marker = rcube_utils::get_input_value('_tags_options_present', rcube_utils::INPUT_POST);
        if ($marker === null) {
            return null;
        }
        $value = rcube_utils::get_input_value('_tags_show_in_list', rcube_utils::INPUT_POST);
        if ($marker !== '1' || ($value !== null && $value !== '1')) {
            throw new InvalidArgumentException('Invalid message-list display option');
        }
        return $value === '1';
    }

    /**
     * Reject missing or array-valued POST data before calling the store.
     *
     * @param mixed $value Submitted field.
     * @return string Valid scalar field.
     */
    private static function require_post_string($value): string
    {
        if (!is_string($value)) {
            throw new InvalidArgumentException('Invalid tag form value');
        }
        return $value;
    }
}

// END OF FILE
