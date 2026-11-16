<?php

/**
 * Vacation/automatic-reply settings module for Roundcube Toolbox.
 *
 * Extracts only the existing Vacation form and submitted settings handling.
 * Shared storage writes, the Toolbox save hook, the HTML editor setup and
 * client-side JavaScript remain in the original plugin code.
 */
class toolbox_tool_vacation extends toolbox_tool_module
{
    /**
     * Initialise the Vacation module.
     *
     * No new hooks are required because the Toolbox dispatcher calls this
     * module for the Vacation form and settings submission.
     *
     * @return void
     */
    public function init(): void
    {
    }

    /**
     * Build the original Vacation editor fields and load current values.
     *
     * @param object $storage Active Toolbox storage instance for Vacation.
     * @return array The original fieldset data for Toolbox's shared renderer.
     */
    public function render_form($storage): array
    {
        // Add JS labels if needed
        $this->rcube->output->add_label(
            'editorwarning'
            );

        $tooldata = ['intro' => '', 'name' => rcmail::Q($this->plugin->gettext('vacation-manage')), 'class' => 'toolbox-vacationtable', 'cols' => 2];

        $selected = $storage->load_tool_data($this->rcube->user->get_username());

        // Normalize values: Roundcube's html_select compares options strictly.
        $options = (array) $this->rcube->config->get('toolbox_vacation_interval_time', []);
        $allowed_intervals = array_map(static fn($value) => (string) (int) $value, array_values($options));
        $default_interval = isset($options['replyonce'])
            ? (string) (int) $options['replyonce']
            : ($allowed_intervals[0] ?? '0');
        $stored_interval = isset($selected['interval_time'])
            ? (string) (int) $selected['interval_time']
            : null;
        $selected['interval_time'] = $stored_interval !== null
            && in_array($stored_interval, $allowed_intervals, true)
                ? $stored_interval
                : $default_interval;

        $field_id = 'rcmfd_vacationactive';
        $input_vacationactive = new html_checkbox(['name' => '_vacationactive', 'id' => $field_id, 'value' => '1']);

        $tooldata['rows']['vacationactive'] = [
            'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('vacation-active'))),
            'content' => $input_vacationactive->show($selected['active'] ?? false)
        ];

        $field_id = 'rcmfd_vacationactivefrom';
        $input_vacationactivefrom = new html_inputfield(['name' => '_vacationactivefrom', 'id' => $field_id, 'value' => '']);

        $tooldata['rows']['vacationactivefrom'] = [
            'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('vacation-activefrom'))),
            'content' => $input_vacationactivefrom->show($selected['activefrom'] ?? '')
        ];

        $field_id = 'rcmfd_vacationactiveuntil';
        $input_vacationactiveuntil = new html_inputfield(['name' => '_vacationactiveuntil', 'id' => $field_id, 'value' => '']);

        $tooldata['rows']['vacationactiveuntil'] = [
            'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('vacation-activeuntil'))),
            'content' => $input_vacationactiveuntil->show($selected['activeuntil'] ?? '')
        ];

        $field_id = 'rcmfd_vacationintervaltime';
        $input_vacationintervaltime = new html_select(['name' => '_vacationintervaltime', 'id' => $field_id]);

        foreach ($options as $name => $option) {
            $input_vacationintervaltime->add(
                rcmail::Q($this->plugin->gettext('vacation-' . $name)),
                (string) (int) $option
            );
        }

        $tooldata['rows']['vacationintervaltime'] = [
            'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('vacation-intervaltime'))),
            'content' => $input_vacationintervaltime->show($selected['interval_time'] ?? ($options['replyonce'] ?? 0))
        ];

        $field_id = 'rcmfd_vacationsubject';
        $input_vacationsubject = new html_inputfield(['name' => '_vacationsubject', 'id' => $field_id, 'value' => '', 'size' => 95]);

        $tooldata['rows']['vacationsubject'] = [
            'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('vacation-subject'))),
            'content' => $input_vacationsubject->show($selected['subject'] ?? '')
        ];

        $field_id = 'rcmfd_vacationhtmleditor';
        $input_vacationhtmleditor = new html_checkbox(['name' => '_vacationhtmleditor', 'id' => $field_id, 'value' => '1']);

        $tooldata['rows']['vactionhtmleditor'] = [
            'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('vacation-htmleditor'))),
            'content' => $input_vacationhtmleditor->show($this->preferences->get('toolbox_vacation_html_editor', false))
        ];

        $field_id = 'rcmfd_vacationbody';
        $input_vacationbody = new html_textarea(['name' => '_vacationbody', 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 70, 'class' => $this->preferences->get('toolbox_vacation_html_editor', false) ? 'mce_editor' : '']);

        $tooldata['rows']['vacationbody'] = [
            'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('vacation-body'))),
            'content' => $input_vacationbody->show($selected['body'] ?? '')
        ];

        return $tooldata;
    }

    /**
     * Save the HTML-editor preference and prepare Vacation storage values.
     *
     * Saves user preferences before returning storage data, matching the
     * previous central save dispatcher. On a preference-save failure, display
     * the existing Roundcube error and return null so storage is not written.
     *
     * @return ?array Vacation settings or null when preferences fail to save.
     */
    public function prepare_settings(): ?array
    {
        $new_settings = [];

        // save user's preferences
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function save]: save user's preferences");
        }
        if (!$this->preferences->save([
            'toolbox_vacation_html_editor' => rcube_utils::get_input_value('_vacationhtmleditor', rcube_utils::INPUT_POST),
        ])) {
            $this->rcube->display_server_error('errorsaving');
            return null;
        }

        // save vacation settings
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function save]: prepare vacation settings");
        }
        $new_settings['main']['active'] = rcube_utils::get_input_value('_vacationactive', rcube_utils::INPUT_POST) ?: false;
        $new_settings['main']['activefrom'] = rcube_utils::get_input_value('_vacationactivefrom', rcube_utils::INPUT_POST) ?: date("Y-m-d H:i:s");
        $new_settings['main']['activeuntil'] = rcube_utils::get_input_value('_vacationactiveuntil', rcube_utils::INPUT_POST) ?: date('"Y-m-d H:i:s"', strtotime("+1 week"));
        // Keep an explicit zero and reject submitted values not in the configured list.
        $interval_options = (array) $this->rcube->config->get('toolbox_vacation_interval_time', []);
        $allowed_intervals = array_map(static fn($value) => (string) (int) $value, array_values($interval_options));
        $default_interval = isset($interval_options['replyonce'])
            ? (int) $interval_options['replyonce']
            : (int) ($allowed_intervals[0] ?? 0);
        $submitted_interval = rcube_utils::get_input_value('_vacationintervaltime', rcube_utils::INPUT_POST);
        $new_settings['main']['interval_time'] = is_scalar($submitted_interval)
            && in_array((string) $submitted_interval, $allowed_intervals, true)
                ? (int) $submitted_interval
                : $default_interval;
        $new_settings['main']['subject'] = rcube_utils::get_input_value('_vacationsubject', rcube_utils::INPUT_POST) ?: $this->rcube->config->get('toolbox_vacation_subject');
        $new_settings['main']['body'] = rcube_utils::get_input_value('_vacationbody', rcube_utils::INPUT_POST) ?: $this->rcube->config->get('toolbox_vacation_body');

        return $new_settings;
    }
    /**
     * Include Vacation's own editor script and optional calendar configuration.
     *
     * The datepicker is initialized by toolbox.vacation.js only when enabled,
     * on the Vacation edit screen. No global calendar script is required.
     *
     * @return void
     */
    public function include_edit_assets(): void
    {
        $this->plugin->include_script('js/toolbox.vacation.js');

        if ($this->rcube->config->get('toolbox_vacation_jquery_calendar', false)) {
            $format = $this->rcube->config->get('toolbox_vacation_jquery_dateformat', 'mm/dd/yy');
            $this->rcube->output->set_env('toolbox_vacation_calendar_format', $format);
        }
    }

    /**
     * Prepare vacation data and persist the existing HTML-editor preference.
     *
     * @param object $storage Shared storage backend.
     * @return ?array Vacation payload or null on a preference-save failure.
     */
    public function prepare_save($storage): ?array
    {
        $settings = $this->prepare_settings();
        return $settings === null ? null : ['new_settings' => $settings];
    }

}

// END OF FILE

