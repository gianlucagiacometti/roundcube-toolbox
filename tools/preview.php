<?php

/**
 * Preview settings tool for Roundcube Toolbox.
 *
 * Hides the Elastic preview pane and stops the background iframe preview
 * request. Roundcube's native double-click handler opens the message, and
 * Roundcube's standard mail_read_time preference controls its read status.
 */
class toolbox_tool_preview extends toolbox_tool_module
{
    /**
     * Enable the no-preview presentation for normal mail-list pages only.
     *
     * The JavaScript disables background preview requests, not the native
     * double-click-to-open action. The CSS only changes presentation.
     *
     * @return void
     */
    public function init(): void
    {
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function init]: 'preview' tool active and loaded");
        }

        // Read preferences only on mail-list pages, not during login or settings.
        if (rcube_utils::get_input_value('_task', rcube_utils::INPUT_GPC) !== 'mail'
            || in_array(rcube_utils::get_input_value('_action', rcube_utils::INPUT_GPC), ['show', 'compose'], true)) {
            return;
        }

        if (!$this->preferences->get('toolbox_message_preview', false)) {
            return;
        }

        if (method_exists($this->rcube->output, 'include_css')) {
            $this->rcube->output->include_css(
                'plugins' . DIRECTORY_SEPARATOR . $this->plugin->ID
                . DIRECTORY_SEPARATOR . 'skins' . DIRECTORY_SEPARATOR . 'elastic'
                . DIRECTORY_SEPARATOR . 'nopreview.css'
            );
        }

        $this->plugin->include_script('js/toolbox.preview.js');
    }

    /**
     * Render the single setting for disabling the message preview pane.
     *
     * @return array Data consumed by the existing shared fieldset renderer.
     */
    public function render_form(): array
    {
        $button_id = 'rcmfd_userpreview';
        $input = new html_checkbox([
            'name' => '_userpreview',
            'id' => $button_id,
            'value' => '1',
            'class' => 'preview-selector',
            'title' => rcmail::Q($this->plugin->gettext('preview-disable-message')),
        ]);

        return [
            'intro' => '',
            'name' => rcmail::Q($this->plugin->gettext('preview-manage')),
            'class' => 'toolbox-previewtable',
            'cols' => 1,
            'rows' => [
                'preview' => [
                    'content' => $input->show($this->preferences->get('toolbox_message_preview', false))
                        . html::label(
                            ['for' => $button_id, 'class' => 'preview-disable-label'],
                            rcmail::Q($this->plugin->gettext('preview-disable-message'))
                        ),
                ],
            ],
        ];
    }

    /**
     * Save only Toolbox's Preview setting, leaving Roundcube's native
     * mail_read_time preference and all other message preferences untouched.
     *
     * The obsolete Toolbox-only double-click preference is cleared using
     * Roundcube's null-removal convention. This does not modify the user's
     * native message read behavior; any legacy Never value must be adjusted
     * deliberately in Roundcube Settings > Preferences > Mailbox View.
     *
     * @return bool True if persisted, false otherwise.
     */
    public function save_preferences(): bool
    {
        if (!$this->preferences->save([
            'toolbox_message_preview' => rcube_utils::get_input_value('_userpreview', rcube_utils::INPUT_POST) ?: false,
            'toolbox_markasread_doubleclick' => null,
        ])) {
            $this->rcube->display_server_error('errorsaving');
            return false;
        }

        return true;
    }

    /**
     * Save Toolbox-only preferences using the existing module method.
     * Roundcube's mail_read_time preference is never changed by this adapter.
     *
     * @param object $storage Shared Toolbox storage backend.
     * @return ?array Empty storage payload or null after a save failure.
     */
    public function prepare_save($storage): ?array
    {
        if (!$this->save_preferences()) {
            return null;
        }

        return ['new_settings' => []];
    }

}

// END OF FILE

