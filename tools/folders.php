<?php

/**
 * Read-only IMAP folder properties tool for Roundcube Toolbox.
 *
 * Shows mailbox counters and metadata. Folder size is calculated only after
 * a separate explicit request because some IMAP servers need to enumerate
 * every message to obtain it.
 */
final class toolbox_tool_folders extends toolbox_tool_module
{
    /**
     * Register the Properties AJAX action and Mail toolbar controls.
     *
     * @return void
     */
    public function init(): void
    {
        if ($this->rcube->task !== 'mail') {
            return;
        }

        $this->plugin->register_action('plugin.toolbox.folders.properties', [$this, 'properties']);

        if (!in_array($this->rcube->action, ['', 'show', 'preview'], true)) {
            return;
        }

        toolbox_logger::step('folders', 'init_mail_controls');
        $this->rcube->output->set_env('toolbox_folders_labels', $this->client_labels());
        $this->plugin->include_stylesheet('skins/elastic/toolbox.folders.css');
        $this->plugin->include_script('js/toolbox.folders.js');
        $this->plugin->add_button([
            'id' => 'rcmbtntoolboxfolders',
            'command' => 'plugin.toolbox.folders',
            'type' => 'link',
            'class' => 'toolbox-folders disabled',
            'classact' => 'toolbox-folders',
            'aria-label' => $this->plugin->gettext('folders-properties'),
            'content' => html::tag('span', ['class' => 'inner'],
                rcmail::Q($this->plugin->gettext('folders-properties'))),
        ], 'toolbar');
    }

    /**
     * Folders is a Mail toolbar action, not a Settings form section.
     *
     * @return bool Always false.
     */
    public function is_visible_in_settings(): bool
    {
        return false;
    }

    /**
     * Export the messages needed by the Mail and ContextMenu interfaces.
     *
     * @return array<string, string> Keyed translated user-facing strings.
     */
    private function client_labels(): array
    {
        $keys = [
            'folders-properties', 'folders-folder', 'folders-total',
            'folders-unread', 'folders-read', 'folders-size',
            'folders-calculate', 'folders-notcalculated', 'folders-calculating',
            'folders-sizewarning', 'folders-namespace', 'folders-special',
            'folders-yes', 'folders-no', 'folders-loading',
            'folders-loadfailed', 'folders-sizefailed',
            'folders-namespace-personal', 'folders-namespace-other',
            'folders-namespace-shared', 'folders-namespace-unknown',
        ];

        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = $this->plugin->gettext($key);
        }
        return $labels;
    }

    /**
     * Return properties for a mailbox accessible to the current IMAP session.
     *
     * A size-only request explicitly opts in to Roundcube's folder_size().
     * On servers without a fast STATUS=SIZE implementation that method may
     * fetch message-size headers for the entire folder, so it is never called
     * by the initial properties request.
     *
     * @return void
     */
    public function properties(): void
    {
        $folder = rcube_utils::get_input_value('_mbox', rcube_utils::INPUT_POST);
        $request = rcube_utils::get_input_value('_folders_request', rcube_utils::INPUT_POST);
        $size_only = rcube_utils::get_input_value('_size', rcube_utils::INPUT_POST) === '1';
        $mode = $size_only ? 'size' : 'stats';
        $result = [
            'folder' => is_string($folder) ? $folder : '',
            'request' => is_string($request) ? $request : '',
            'mode' => $mode,
            'success' => false,
        ];

        if (!self::valid_folder($folder) || !self::valid_request($request)) {
            toolbox_logger::error('folders', 'properties_request', 'invalid_input');
            $this->send_properties($result);
            return;
        }

        try {
            $storage = $this->rcube->get_storage();
            if (!$storage->folder_exists($folder)) {
                toolbox_logger::error('folders', 'properties_request', 'folder_unavailable');
                $this->send_properties($result);
                return;
            }

            $info = (array) $storage->folder_info($folder);
            if (!empty($info['noselect'])) {
                toolbox_logger::error('folders', 'properties_request', 'folder_not_selectable');
                $this->send_properties($result);
                return;
            }

            if ($size_only) {
                toolbox_logger::step('folders', 'calculate_size_start');
                $bytes = $storage->folder_size($folder);
                if ($bytes === false || !is_numeric($bytes) || $bytes < 0) {
                    throw new RuntimeException('Folder size unavailable');
                }
                $result['size'] = (int) $bytes;
                $result['success'] = true;
                toolbox_logger::step('folders', 'calculate_size_complete');
            } else {
                toolbox_logger::step('folders', 'properties_read_start');
                // A separate AJAX request has no active message search, so ALL
                // and UNSEEN refer to the requested mailbox, not search results.
                $total = $storage->count($folder, 'ALL', true);
                $unread = $storage->count($folder, 'UNSEEN', true);
                if (!is_numeric($total) || !is_numeric($unread)
                    || $total < 0 || $unread < 0 || $unread > $total) {
                    throw new RuntimeException('Inconsistent IMAP counters');
                }
                $namespace = $info['namespace'] ?? 'unknown';
                if (!in_array($namespace, ['personal', 'other', 'shared'], true)) {
                    $namespace = 'unknown';
                }
                $result += [
                    'total' => (int) $total,
                    'unread' => (int) $unread,
                    'read' => (int) $total - (int) $unread,
                    'namespace' => $namespace,
                    'special' => !empty($info['special']),
                ];
                $result['success'] = true;
                toolbox_logger::step('folders', 'properties_read_complete');
            }
        } catch (Throwable $exception) {
            // IMAP server details and mailbox paths never enter Toolbox logs.
            toolbox_logger::error('folders', 'properties_request', 'imap_failed');
        }

        $this->send_properties($result);
    }

    /**
     * Restrict user-supplied mailbox names before sending them to IMAP.
     *
     * @param mixed $folder Untrusted mailbox identifier.
     * @return bool Whether its shape is safe for IMAP lookup.
     */
    private static function valid_folder($folder): bool
    {
        return is_string($folder) && $folder !== '' && strlen($folder) <= 1024
            && !preg_match('/[\x00-\x1F\x7F]/', $folder);
    }

    /**
     * Check the short client-generated correlation token.
     *
     * @param mixed $request Untrusted request ID.
     * @return bool Whether it is an expected unsigned numeric token.
     */
    private static function valid_request($request): bool
    {
        return is_string($request) && (bool) preg_match('/\A[1-9][0-9]{0,12}\z/D', $request);
    }

    /**
     * Send a callback in the normal Roundcube AJAX response format.
     *
     * @param array $result Public statistics, or an unqualified failure.
     * @return void
     */
    private function send_properties(array $result): void
    {
        $this->rcube->output->command('toolbox_folders_properties_result', $result);
        $this->rcube->output->send();
    }
}

// END OF FILE

