<?php

/**
 * Login Monitor: successful Roundcube login recording with configurable fields.
 *
 * Captures bounded browser/environment snapshots without altering Roundcube
 * authentication. Collection and eventual user visibility are independently
 * controlled by administrator defaults and individual user preferences.
 * Administrator permissions cap what each user may enable. Offline geolocation
 * stores allowed city/country fields, not the lookup IP by default.
 * Persistent browser identification and database-backed history are separate features.
 */

require_once __DIR__ . '/login_monitor/fields.php';
require_once __DIR__ . '/login_monitor/user_preferences.php';
require_once __DIR__ . '/login_monitor/detector.php';
require_once __DIR__ . '/login_monitor/geoip.php';
require_once __DIR__ . '/login_monitor/history.php';
require_once __DIR__ . '/login_monitor/toast.php';

/** Optional Toolbox module recording one event per successful login hook. */
final class toolbox_tool_login_monitor extends toolbox_tool_module
{
    /**
     * Register the login recorder and prepare the one-shot mail notification.
     *
     * @return void
     */
    public function init(): void
    {
        if ($this->rcube->task === 'login') {
            $this->plugin->add_hook('login_after', [$this, 'record_successful_login']);
            toolbox_logger::step('login_monitor', 'hook_registered');
        } elseif ($this->rcube->task === 'mail' && !$this->rcube->action) {
            $this->prepare_login_toast();
        }
    }

    /**
     * Present the preceding successful login once on the first mail page.
     *
     * Only an ID written by the successful-login hook is accepted, and every
     * optional field is gated by the user's current effective visibility rules.
     * A failed query never interrupts mail; no sensitive values are logged.
     *
     * @return void
     */
    private function prepare_login_toast(): void
    {
        $pending = $_SESSION['toolbox_lm_toast_pending'] ?? null;
        $marker = $_SESSION['toolbox_lm_current_login'] ?? null;
        $userId = $this->rcube->user->ID ?? null;
        if (!is_array($pending) || !is_array($marker)
            || !is_numeric($userId) || (int) $userId < 1
            || (int) ($pending['user_id'] ?? 0) !== (int) $userId
            || (int) ($marker['user_id'] ?? 0) !== (int) $userId
            || (int) ($pending['id'] ?? 0) !== (int) ($marker['id'] ?? 0)
            || (int) ($pending['id'] ?? 0) < 1) {
            return;
        }

        try {
            $saved = $this->preferences->get(toolbox_login_monitor_user_preferences::KEY, []);
            $policy = toolbox_login_monitor_user_preferences::effective($this->rcube->config, $saved);
            $payload = toolbox_login_monitor_toast::payload(
                $this->rcube, $this->rcube->get_dbh(), (int) $userId,
                (int) $pending['id'], $policy
            );
            $payload['hello'] = $this->plugin->gettext('login-monitor-toast-hello');
            $payload['last'] = $this->plugin->gettext('login-monitor-toast-last');
            $payload['from'] = $this->plugin->gettext('login-monitor-toast-from');
            $payload['none'] = $this->plugin->gettext('login-monitor-toast-none');
            $payload['flag_data_uri'] = $payload['city'] !== '' && $payload['country'] !== ''
                ? toolbox_login_monitor_toast::flag_data_uri($payload['country']) : '';

            // Consume only after the response has its data and assets attached.
            $this->rcube->output->set_env('toolbox_lm_toast', $payload);
            $this->plugin->include_stylesheet($this->plugin->local_skin_path() . '/toolbox.login_monitor.toast.css');
            $this->plugin->include_script('js/toolbox.login_monitor.toast.js');
            unset($_SESSION['toolbox_lm_toast_pending']);
        } catch (\Throwable $exception) {
            // A later full mail page can retry a transient failure.
            toolbox_logger::error('login_monitor', 'toast_prepare', 'unavailable');
        }
    }

    /**
     * Expose individual collection and visibility choices in Toolbox Settings.
     *
     * @return bool True for the personal settings form.
     */
    public function is_visible_in_settings(): bool
    {
        return true;
    }

    /**
     * Load the layout assets only when editing this tool's Settings section.
     *
     * @return void
     */
    public function include_edit_assets(): void
    {
        $this->plugin->include_stylesheet($this->plugin->local_skin_path() . '/toolbox.login_monitor.css');
        $this->plugin->include_script('js/toolbox.login_monitor.js');
    }

    /**
     * Render the history-first tabs and a three-column personal policy table.
     *
     * The shared Toolbox handler owns the form, CSRF protection and save
     * action. History is read-only and all labels are escaped translations.
     *
     * @return array Fieldset data for the shared Toolbox renderer.
     */
    public function render_form(): array
    {
        toolbox_logger::step('login_monitor', 'preferences_render_start');
        $permissions = toolbox_login_monitor_user_preferences::permissions($this->rcube->config);
        $current = toolbox_login_monitor_user_preferences::effective(
            $this->rcube->config,
            $this->preferences->get(toolbox_login_monitor_user_preferences::KEY, [])
        );

        $label = fn(string $key): string => rcmail::Q($this->plugin->gettext($key));
        $historyLabel = $label('login-monitor-tab-history');
        $preferencesLabel = $label('login-monitor-tab-preferences');
        $collectLabel = $label('login-monitor-collect');
        $visibleLabel = $label('login-monitor-visible');
        $unavailableLabel = $label('login-monitor-unavailable');

        $rows = '';
        foreach ($current as $field => $flags) {
            if (!$permissions[$field]['collect'] && !$permissions[$field]['visible']) {
                continue;
            }

            $fieldLabel = $label('login-monitor-field-' . $field);
            $cells = html::tag('th', ['scope' => 'row'], $fieldLabel);
            foreach (['collect', 'visible'] as $flag) {
                if (!$permissions[$field][$flag]) {
                    $cell = html::tag(
                        'span',
                        ['class' => 'toolbox-login-monitor-unavailable', 'title' => $unavailableLabel],
                        '&mdash;'
                    );
                } else {
                    $inputId = 'toolbox_lm_' . $field . '_' . $flag;
                    $control = new html_checkbox([
                        'name' => '_lm_' . $field . '_' . $flag,
                        'id' => $inputId,
                        'value' => '1',
                    ]);
                    $flagLabel = $flag === 'collect' ? $collectLabel : $visibleLabel;
                    $cell = $control->show($flags[$flag]) . html::label(
                        ['for' => $inputId],
                        html::tag(
                            'span',
                            ['class' => 'toolbox-login-monitor-sr-only'],
                            $flagLabel . ': ' . $fieldLabel
                        )
                    );
                }
                $cells .= html::tag(
                    'td',
                    ['class' => 'toolbox-login-monitor-choice'],
                    html::tag('div', ['class' => 'toolbox-login-monitor-switch'], $cell)
                );
            }
            $rows .= html::tag('tr', null, $cells);
        }

        $head = html::tag('tr', null,
            html::tag('th', ['scope' => 'col'], $label('login-monitor-column-information'))
            . html::tag('th', ['scope' => 'col'], $collectLabel)
            . html::tag('th', ['scope' => 'col'], $visibleLabel)
        );
        $table = html::tag('table', ['class' => 'toolbox-login-monitor-grid'],
            html::tag('thead', null, $head)
            . html::tag('tbody', null, $rows)
        );

        // A Settings save returns to Preferences; a fresh visit starts in History.
        $active = rcube_utils::get_input_value('_lm_active_tab', rcube_utils::INPUT_POST) === 'preferences'
            ? 'preferences' : 'history';
        $navigation = '';
        foreach (['history' => $historyLabel, 'preferences' => $preferencesLabel] as $tab => $text) {
            $selected = $active === $tab;
            $navigation .= html::tag('button', [
                'type' => 'button',
                'id' => 'toolbox-lm-tab-' . $tab,
                'class' => 'toolbox-login-monitor-tab' . ($selected ? ' is-active' : ''),
                'role' => 'tab',
                'data-lm-tab' => $tab,
                'aria-controls' => 'toolbox-lm-panel-' . $tab,
                'aria-selected' => $selected ? 'true' : 'false',
                'tabindex' => $selected ? '0' : '-1',
            ], $text);
        }

        $history = html::tag('section', [
            'id' => 'toolbox-lm-panel-history',
            'class' => 'toolbox-login-monitor-panel',
            'role' => 'tabpanel',
            'aria-labelledby' => 'toolbox-lm-tab-history',
            'tabindex' => '0',
            'hidden' => $active === 'history' ? null : 'hidden',
        ], $this->render_history($current));
        $preferences = html::tag('section', [
            'id' => 'toolbox-lm-panel-preferences',
            'class' => 'toolbox-login-monitor-panel',
            'role' => 'tabpanel',
            'aria-labelledby' => 'toolbox-lm-tab-preferences',
            'tabindex' => '0',
            'hidden' => $active === 'preferences' ? null : 'hidden',
        ], html::tag('h2', ['class' => 'toolbox-login-monitor-heading'], $label('login-monitor-manage'))
            . html::tag('p', ['class' => 'toolbox-login-monitor-intro'], $label('login-monitor-intro'))
            . html::tag('div', ['class' => 'toolbox-login-monitor-table-scroll'], $table)
        );

        $content = html::tag('div', [
            'id' => 'toolbox-lm-root',
            'class' => 'toolbox-login-monitor',
            'data-initial-tab' => $active,
        ], html::tag('div', [
                'class' => 'toolbox-login-monitor-tabs',
                'role' => 'tablist',
                'aria-label' => $label('login_monitor'),
            ], $navigation)
            . $history . $preferences
            . '<input type="hidden" id="toolbox-lm-active-tab" name="_lm_active_tab" value="' . $active . '">'
        );

        toolbox_logger::step('login_monitor', 'preferences_render_complete');
        return [
            'intro' => $content,
            'name' => '',
            'class' => 'toolbox-login-monitor-wrapper',
            'cols' => 1,
            'rows' => [],
        ];
    }

    /**
     * Render the previous-login summary, one history page, and details dialogs.
     *
     * The database read is restricted to this Roundcube account and the
     * currently visible fields. All database-derived strings are HTML-escaped.
     *
     * @param array $policy Effective personal field visibility policy.
     * @return string History markup (no form controls are submitted).
     */
    private function render_history(array $policy): string
    {
        $label = fn(string $key): string => rcmail::Q($this->plugin->gettext($key));
        $userId = $this->rcube->user->ID ?? null;
        if ((!is_int($userId) && !(is_string($userId) && ctype_digit($userId))) || (int) $userId < 1) {
            toolbox_logger::error('login_monitor', 'history_read', 'user_id_unavailable');
            return html::tag('p', ['role' => 'alert'], $label('login-monitor-history-error'));
        }

        $rawPage = rcube_utils::get_input_value('_lm_page', rcube_utils::INPUT_GET);
        $page = is_string($rawPage) && ctype_digit($rawPage)
            ? (int) min(10000, (float) $rawPage) : 1;
        $page = max(1, $page);
        $marker = $_SESSION['toolbox_lm_current_login'] ?? null;
        $currentId = is_array($marker)
            && (int) ($marker['user_id'] ?? 0) === (int) $userId
            && isset($marker['id']) && is_numeric($marker['id'])
            && (int) $marker['id'] > 0
            ? (int) $marker['id'] : null;

        try {
            $data = toolbox_login_monitor_history::fetch(
                $this->rcube->get_dbh(), (int) $userId, $policy, $page, $currentId
            );
        } catch (\Throwable $exception) {
            toolbox_logger::error('login_monitor', 'history_read', 'query_failed');
            return html::tag('p', ['role' => 'alert'], $label('login-monitor-history-error'));
        }

        $intro = html::tag('p', ['class' => 'toolbox-login-monitor-intro'],
            $label('login-monitor-history-intro')
        );
        $previous = $data['previous'];
        $details = [];
        if ($previous) {
            $details[(string) $previous['id']] = $previous;
            $previousBody = html::tag('p', ['class' => 'toolbox-login-monitor-previous-date'],
                $this->history_date($previous)
            ) . html::tag('p', null, $this->history_summary($previous, $policy))
                . $this->history_detail_button($previous);
        } elseif ($currentId === null) {
            $previousBody = html::tag('p', null, $label('login-monitor-previous-unavailable'));
        } else {
            $previousBody = html::tag('p', null, $label('login-monitor-previous-none'));
        }
        $previousPanel = html::tag('div', ['class' => 'toolbox-login-monitor-previous'],
            html::tag('h2', ['class' => 'toolbox-login-monitor-heading'],
                $label('login-monitor-previous-title')
            ) . $previousBody
        );

        $rows = '';
        foreach ($data['rows'] as $record) {
            $details[(string) $record['id']] = $record;
            $time = $this->history_date($record);
            $current = $currentId !== null && (int) $record['id'] === $currentId;
            $dateCell = html::tag('time', [
                'datetime' => gmdate('Y-m-d\TH:i:s\Z', (int) $record['logged_in_at']),
            ], $time);
            if ($current) {
                $dateCell .= ' ' . html::tag('span', ['class' => 'toolbox-login-monitor-current'],
                    $label('login-monitor-current')
                );
            }
            $rows .= html::tag('tr', null,
                html::tag('td', null, $dateCell)
                . html::tag('td', null, $this->history_summary($record, $policy))
                . html::tag('td', ['class' => 'toolbox-login-monitor-row-action'],
                    $this->history_detail_button($record)
                )
            );
        }

        if ($rows === '') {
            $historyTable = html::tag('p', ['class' => 'toolbox-login-monitor-empty'],
                $label('login-monitor-history-empty')
            );
        } else {
            $historyTable = html::tag('div', ['class' => 'toolbox-login-monitor-table-scroll'],
                html::tag('table', ['class' => 'toolbox-login-monitor-history-table'],
                    html::tag('thead', null, html::tag('tr', null,
                        html::tag('th', ['scope' => 'col'], $label('login-monitor-column-date'))
                        . html::tag('th', ['scope' => 'col'], $label('login-monitor-column-summary'))
                        . html::tag('th', ['scope' => 'col'], $label('login-monitor-column-details'))
                    )) . html::tag('tbody', null, $rows)
                )
            );
        }

        $links = '';
        if ($page > 1) {
            $links .= html::tag('a', ['href' => $this->history_page_url($page - 1)],
                $label('login-monitor-page-previous'));
        }
        $links .= html::tag('span', ['aria-current' => 'page'],
            sprintf($label('login-monitor-page-number'), $page)
        );
        if ($data['has_next']) {
            $links .= html::tag('a', ['href' => $this->history_page_url($page + 1)],
                $label('login-monitor-page-next'));
        }
        $navigation = html::tag('nav', [
            'class' => 'toolbox-login-monitor-pagination',
            'aria-label' => $label('login-monitor-pagination'),
        ], $links);

        $dialogs = '';
        foreach ($details as $record) {
            $dialogs .= $this->history_dialog($record, $policy);
        }
        return $intro . $previousPanel
            . html::tag('h2', ['class' => 'toolbox-login-monitor-heading'],
                $label('login-monitor-history-title')
            ) . $historyTable . $navigation . $dialogs;
    }

    /**
     * Build the canonical read-only Settings URL for a history page.
     *
     * @param int $page One-based page number.
     * @return string Roundcube Settings URL.
     */
    private function history_page_url(int $page): string
    {
        return './?_task=settings&_action=plugin.toolbox.edit&_section=login_monitor&_lm_page=' . $page;
    }

    /**
     * Format a login timestamp using Roundcube's configured user timezone.
     *
     * @param array $record One history record with a Unix timestamp.
     * @return string Escaped, localized date/time.
     */
    private function history_date(array $record): string
    {
        return rcmail::Q($this->rcube->format_date(
            (int) $record['logged_in_at'], $this->rcube->config->get('date_long', 'Y-m-d H:i')
        ));
    }

    /**
     * Summarize only fields present in the permitted SQL projection.
     *
     * @param array $record Database record with allowed columns only.
     * @param array $policy Effective visibility policy.
     * @return string Escaped summary, or the translated missing-value marker.
     */
    private function history_summary(array $record, array $policy): string
    {
        $groups = [
            ['browser_name', 'browser_version'],
            ['os_name', 'os_version'],
            ['device_type', 'device_brand', 'device_model'],
            ['city', 'country'],
        ];
        $parts = [];
        foreach ($groups as $names) {
            $pieces = [];
            foreach ($names as $name) {
                if (toolbox_login_monitor_fields::visible($policy, $name)
                    && isset($record[$name]) && trim((string) $record[$name]) !== '') {
                    $pieces[] = trim((string) $record[$name]);
                }
            }
            if ($pieces) {
                $parts[] = implode(' ', $pieces);
            }
        }
        return $parts ? rcmail::Q(implode(' · ', $parts))
            : rcmail::Q($this->plugin->gettext('login-monitor-no-details'));
    }

    /**
     * Create a button to open the corresponding read-only record dialog.
     *
     * @param array $record One scoped history record.
     * @return string HTML button.
     */
    private function history_detail_button(array $record): string
    {
        return html::tag('button', [
            'type' => 'button',
            'class' => 'button toolbox-login-monitor-detail-button',
            'data-lm-detail' => 'toolbox-lm-detail-' . (int) $record['id'],
            'aria-haspopup' => 'dialog',
        ], rcmail::Q($this->plugin->gettext('login-monitor-details-open')));
    }

    /**
     * Render one accessible details dialog from only currently visible fields.
     *
     * @param array $record One user-scoped history record.
     * @param array $policy Effective visibility policy.
     * @return string Escaped HTML dialog markup.
     */
    private function history_dialog(array $record, array $policy): string
    {
        $id = 'toolbox-lm-detail-' . (int) $record['id'];
        $title = $id . '-title';
        $list = html::tag('dt', null, rcmail::Q($this->plugin->gettext('login-monitor-column-date')))
            . html::tag('dd', null, $this->history_date($record));
        foreach (toolbox_login_monitor_fields::defaults() as $field => $_flags) {
            if (!toolbox_login_monitor_fields::visible($policy, $field)) {
                continue;
            }
            $raw = $record[$field] ?? null;
            $value = $raw === null || trim((string) $raw) === ''
                ? rcmail::Q($this->plugin->gettext('login-monitor-no-details'))
                : rcmail::Q((string) $raw);
            $list .= html::tag('dt', null, rcmail::Q($this->plugin->gettext('login-monitor-field-' . $field)))
                . html::tag('dd', null, $value);
        }
        return html::tag('dialog', [
            'id' => $id,
            'class' => 'toolbox-login-monitor-dialog',
            'aria-labelledby' => $title,
        ], html::tag('h2', ['id' => $title, 'class' => 'toolbox-login-monitor-heading'],
                rcmail::Q($this->plugin->gettext('login-monitor-details-title'))
            )
            . html::tag('dl', ['class' => 'toolbox-login-monitor-details'], $list)
            . html::tag('div', ['class' => 'toolbox-login-monitor-dialog-actions'],
                html::tag('button', ['type' => 'button', 'data-lm-close' => '1', 'class' => 'button mainaction'],
                    rcmail::Q($this->plugin->gettext('login-monitor-details-close'))
                )
            )
        );
    }

    /**
     * Save only personal Login Monitor preferences under their distinct key.
     *
     * The shared Toolbox handler supplies CSRF protection and subsequently
     * receives an empty legacy storage payload. Values for unavailable fields
     * are retained without becoming active while prohibited by the admin.
     *
     * @param object $storage Unused legacy Toolbox storage adapter.
     * @return ?array Empty legacy settings on success, null on failure.
     */
    public function prepare_save($storage): ?array
    {
        toolbox_logger::step('login_monitor', 'preferences_save_start');
        try {
            $saved = $this->preferences->get(toolbox_login_monitor_user_preferences::KEY, []);
            $permissions = toolbox_login_monitor_user_preferences::permissions($this->rcube->config);
            $choices = toolbox_login_monitor_user_preferences::submitted(
                $saved,
                $permissions,
                static fn(string $name) => rcube_utils::get_input_value($name, rcube_utils::INPUT_POST)
            );
            if (!$this->preferences->save([toolbox_login_monitor_user_preferences::KEY => $choices])) {
                toolbox_logger::error('login_monitor', 'preferences_save', 'persistence_failed');
                $this->rcube->display_server_error('errorsaving');
                return null;
            }
        } catch (\Throwable $exception) {
            toolbox_logger::error('login_monitor', 'preferences_save', 'storage_exception');
            $this->rcube->display_server_error('errorsaving');
            return null;
        }

        toolbox_logger::step('login_monitor', 'preferences_save_complete');
        return ['new_settings' => []];
    }

    /**
     * Record an authentication event without ever preventing a valid login.
     *
     * Only the explicitly enabled collection fields receive values. A fixed
     * SQL template is used for both database engines (MySQL and PostgreSQL).
     * User-supplied data, IPs, browser details and exception text are not logged.
     *
     * @param array $args Roundcube's unmodified login_after arguments.
     * @return array The same arguments, unchanged.
     */
    public function record_successful_login(array $args): array
    {
        try {
            $id = $this->rcube->user->ID ?? null;
            if ((!is_int($id) && !(is_string($id) && ctype_digit($id))) || (int) $id < 1) {
                toolbox_logger::error('login_monitor', 'record_login', 'user_id_unavailable');
                return $args;
            }

            // Preferences are read from the authenticated Roundcube account,
            // not the incoming login name. A failed preference read must not
            // record optional details contrary to a user's opt-out.
            try {
                $saved = $this->preferences->get(toolbox_login_monitor_user_preferences::KEY, []);
                $policies = toolbox_login_monitor_user_preferences::effective($this->rcube->config, $saved);
            } catch (\Throwable $exception) {
                toolbox_logger::error('login_monitor', 'record_login', 'preferences_unavailable');
                $policies = toolbox_login_monitor_fields::policies($this->rcube->config);
                foreach ($policies as &$flags) {
                    $flags['collect'] = false;
                    $flags['visible'] = false;
                }
                unset($flags);
            }
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $userAgent = is_string($userAgent) ? $userAgent : '';
            $parsed = toolbox_login_monitor_detector::describe($userAgent, $policies);

            $data = array_fill_keys(array_keys(toolbox_login_monitor_fields::defaults()), null);
            foreach ($parsed as $field => $value) {
                $data[$field] = $value;
            }

            // Use the server-observed IP transiently for the optional local
            // lookup. Never trust X-Forwarded-For without a trusted proxy policy.
            $remoteAddress = $_SERVER['REMOTE_ADDR'] ?? null;
            $location = toolbox_login_monitor_geoip::locate($remoteAddress, $policies, $this->rcube->config);
            foreach ($location as $field => $value) {
                $data[$field] = $value;
            }
            if (toolbox_login_monitor_fields::collect($policies, 'ip_address')
                && is_string($remoteAddress)
                && filter_var($remoteAddress, FILTER_VALIDATE_IP) !== false) {
                $data['ip_address'] = $remoteAddress;
            }

            if (toolbox_login_monitor_fields::collect($policies, 'user_agent')
                && $userAgent !== ''
                && strlen($userAgent) <= 4096
                && preg_match('//u', $userAgent) === 1) {
                // User opt-in subject to administrator permissions. Never log it.
                $data['user_agent'] = $userAgent;
            }

            $db = $this->rcube->get_dbh();
            $table = $db->table_name('toolbox_login_monitor_logins', true);
            $columns = array_keys($data);
            $columnList = 'user_id, logged_in_at, ' . implode(', ', $columns);
            $markers = implode(', ', array_fill(0, 2 + count($columns), '?'));
            $template = 'INSERT INTO toolbox_login_monitor_logins (' . $columnList
                . ') VALUES (' . $markers . ')';
            toolbox_logger::sql('login_monitor', $template);

            // SQL identifiers are static keys from defaults(); never config- or
            // request-controlled. table_name() supplies database-safe quoting.
            $sql = "INSERT INTO {$table} ({$columnList}) VALUES ({$markers})";
            $values = array_merge([(int) $id, time()], array_values($data));
            $insert = $db->query($sql, ...$values);

            if ($insert === false || $db->is_error($insert)) {
                unset($_SESSION['toolbox_lm_current_login'], $_SESSION['toolbox_lm_toast_pending']);
                toolbox_logger::error('login_monitor', 'record_login', 'insert_failed');
            } else {
                // Record the exact row for this authenticated session. Both
                // MySQL and PostgreSQL adapters resolve their own ID/sequence.
                // The marker contains no client fingerprint or personal data.
                unset($_SESSION['toolbox_lm_current_login'], $_SESSION['toolbox_lm_toast_pending']);
                $insertId = $db->insert_id('toolbox_login_monitor_logins');
                if ((is_int($insertId) || (is_string($insertId) && ctype_digit($insertId)))
                    && (int) $insertId > 0) {
                    $_SESSION['toolbox_lm_current_login'] = [
                        'user_id' => (int) $id,
                        'id' => (int) $insertId,
                    ];
                    $_SESSION['toolbox_lm_toast_pending'] = $_SESSION['toolbox_lm_current_login'];
                }
                toolbox_logger::step('login_monitor', 'login_recorded');
            }
        } catch (\Throwable $exception) {
            // No history-recording failure may change authentication behavior.
            toolbox_logger::error('login_monitor', 'record_login', 'storage_exception');
        }

        return $args;
    }
}

// END OF FILE

