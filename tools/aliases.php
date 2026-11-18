<?php

/**
 * Alias management module for Roundcube Toolbox.
 *
 * Renders alias controls, validates and prepares submitted alias names,
 * and delegates database changes to the existing Toolbox storage backend.
 */
class toolbox_tool_aliases extends toolbox_tool_module
{
    /**
     * Activate the module. Existing Toolbox action routes remain registered.
     *
     * @return void
     */
    public function init(): void
    {
    }

    /**
     * Build the alias settings form and associated client-side environment.
     *
     * @param object $storage Existing storage instance for the aliases tool.
     * @param array $attrib Form attributes used by the row builder.
     * @return array Alias form data for the shared Toolbox fieldset renderer.
     */
    public function render_form($storage, $attrib): array
    {
        toolbox_logger::step('aliases', 'settings_render');
        // Add JS labels if needed
        $this->rcube->output->add_label(
            'toolbox.aliases-aliasupdated',
            'toolbox.aliases-aliasupdatederror',
            'toolbox.aliases-novalidalias',
            'toolbox.aliases-aliasexists',
            'toolbox.aliases-aliasdeleteconfirm',
            'toolbox.aliases-deleted'
            );
        // define table sorting
        $sorts = [
            '#alias-addresses-table' => [0, 'true']
        ];
        $this->rcube->output->set_env('table_sort', $sorts);

        $tooldata = ['name' => rcmail::Q($this->plugin->gettext('aliases-manage')), 'class' => 'toolbox-aliasestable', 'cols' => 2];

        $settings = $storage->load_tool_data($this->rcube->user->get_username());
        $aliases = [];
        if (!empty($settings['aliases'])) {
            foreach ($settings['aliases'] as $alias) {
                $active = $alias['active'];
                $address = trim($alias['address']);
                // Multi-domain mode displays the complete address; single-domain mode hides its domain.
                $elements = $this->rcube->config->get('toolbox_aliases_multiple_domains') ? [$address] : explode("@", $address, 2);
                if ($elements[0] != "") {
                    $aliases[] = [$address, 'name' => $elements[0], 'domain' => ($elements[1] ?? null), 'active' => $active];
                }
            }
        }
        sort($aliases);

        $field_id = 'rcmfd_newaliasname';
        $input_newalias = new html_inputfield(['name' => '_newaliasname', 'id' => $field_id, 'title' => rcmail::Q($this->plugin->gettext('aliases-aliasname')), 'size' => 80, 'placeholder' => rcmail::Q($this->plugin->gettext('aliases-aliasname'))]);
        $field_id = 'rcmfd_newaliasactive';
        $input_newactive = new html_select(['name' => '_newaliasactive', 'id' => $field_id]);
        $input_newactive->add([rcmail::Q($this->plugin->gettext('toolbox-enabled')),rcmail::Q($this->plugin->gettext('toolbox-disabled'))], ['true','false']);

        $field_id = 'rcmbtn_addalias';
        $button_addalias = $this->rcube->output->button(['id' => $field_id, 'command' => 'plugin.toolbox.add_alias', 'type' => 'input', 'class' => 'button', 'label' => 'toolbox.aliases-addaddress']);

        $tooldata['intro'] = html::div('address-input grouped', $input_newalias->show() . $input_newactive->show('enabled') . $button_addalias);

        $table = new html_table(['class' => 'addressprefstable propform', 'cols' => 2]);

        $address_table = new html_table(['id' => 'alias-addresses-table', 'class' => 'records-table sortable-table alias-addresses-table fixedheader', 'cellspacing' => '0', 'cols' => 3]);
        $address_table->add_header('email', $this->rcube->output->button(['command' => 'plugin.toolbox.table_sort', 'prop' => '#alias-addresses-table', 'type' => 'link', 'label' => 'toolbox.aliases-aliasname', 'title' => 'sortby']));
        $address_table->add_header('status', $this->rcube->output->button(['command' => 'plugin.toolbox.table_sort', 'prop' => '#alias-addresses-table', 'type' => 'link', 'label' => 'toolbox.toolbox-enabled', 'title' => 'sortby']));
        $address_table->add_header('control', '&nbsp;');

        $this->rcube->output->set_env('alias_addresses_count', !empty($aliases) ? count($aliases) : 0);
        foreach ($aliases as $alias) {
            if ($alias['name'] != '') {
                $this->alias_address_row($address_table, 'alias', $alias, $attrib);
            }
        }

        // add no address and new address row at the end
        $noaddresses = !empty($aliases) ? 'display: none;' : null;

        $address_table->set_row_attribs(['class' => 'noaddress', 'style' => $noaddresses]);
        $address_table->add(['colspan' => '3'], rcube_utils::rep_specialchars_output(rcmail::Q($this->plugin->gettext('aliases-noaliases'))));

        $this->alias_address_row($address_table, null, null, $attrib, ['class' => 'newaddress']);

        $table->add(['colspan' => 2, 'class' => 'scroller'], html::div(['id' => 'alias-addresses-cont'], $address_table->show()));

        $tooldata['content'] = $table->show();

        return $tooldata;
    }

    /**
     * Prepare the submitted alias data and corresponding result messages.
     *
     * Database changes are still performed by Toolbox's existing storage
     * class after its toolbox_save hook is executed.
     *
     * @return array Data for the shared save dispatcher.
     */
    public function prepare_settings(): array
    {
        $new_settings = [];

        // save alias
        toolbox_logger::step('aliases', 'settings_save_prepare');
        $new_settings['main']['aliasname'] = mb_convert_case(trim((string) rcube_utils::get_input_value('_newaliasname', rcube_utils::INPUT_POST)), MB_CASE_LOWER, RCUBE_CHARSET);
        $new_settings['main']['active'] = rcube_utils::get_input_value('_newaliasactive', rcube_utils::INPUT_POST);
        toolbox_logger::step('aliases', 'settings_save_prepared');

        $message_success = rcmail::Q($this->plugin->gettext('aliases-aliascreated'));
        $message_error = rcmail::Q($this->plugin->gettext('aliases-aliascreatederror'));

        return [
            'new_settings' => $new_settings,
            'message_success' => $message_success,
            'message_error' => $message_error,
        ];
    }

    /**
     * Enable or disable an existing alias using the established storage API.
     *
     * @param object $storage Initialised aliases storage instance.
     * @return void
     */
    public function toggle_alias($storage): void
    {
        $data = ['section' => 'aliases'];
        $error = '';
        $rcmail = rcmail::get_instance();

        $data['new_settings']['aliasname'] = mb_convert_case(rcube_utils::get_input_value('_aliasname', rcube_utils::INPUT_POST, true), MB_CASE_LOWER, RCUBE_CHARSET);

        $this->plugin->api->output->add_label('toolbox.aliases-aliasupdated');

        toolbox_logger::step('aliases', 'toggle_start');

        $success = $storage->toggle_tool_data($this->rcube->user->get_username(), $data);
        if (!$success) {
            toolbox_logger::error('aliases', 'toggle', 'persistence_failed');
            $error = rcmail::Q($this->plugin->gettext('aliases-aliasupdatederror'));
        }
        else {
            toolbox_logger::step('aliases', 'toggle_complete');
        }

        // A stable event name lets the client associate the result with its
        // pending alias row without embedding addresses in callback names.
        $rcmail->output->command('plugin.toolbox.alias_toggle_result', [
            'aliasname' => $data['new_settings']['aliasname'],
            'success' => $success,
            'error' => $error,
        ]);
    }

    /**
     * Check an alias name against existing entries and allowed characters.
     *
     * @param object $storage Initialised aliases storage instance.
     * @return void
     */
    public function check_alias($storage): void
    {
        $rcmail = rcmail::get_instance();

        $new_alias = (string) rcube_utils::get_input_value('_newaliasname', rcube_utils::INPUT_POST, true);

        // Check the complete address globally, without listing other users' aliases.
        // Storage enforces the same validation again on INSERT (not just via AJAX).
        toolbox_logger::step('aliases', 'check_start');
        $status = $storage->alias_creation_error($this->rcube->user->get_username(), $new_alias);
        if ($status === 'exists') {
            $error = rcmail::Q($this->plugin->gettext('aliases-aliasexistsindomain'));
        }
        elseif ($status !== null) {
            // Do not disclose cross-domain authorization details to this endpoint.
            $error = rcmail::Q($this->plugin->gettext('aliases-aliasnameerror'));
        }
        else {
            $error = '';
        }

        toolbox_logger::step('aliases', $status === null ? 'check_accepted' : 'check_rejected');
        $rcmail->output->command('plugin.toolbox.check_alias', $error);
    }

    /**
     * Delete an alias using the established storage API.
     *
     * @param object $storage Initialised aliases storage instance.
     * @return void
     */
    public function delete_alias($storage): void
    {
        $data = ['section' => 'aliases'];
        $error = '';
        $rcmail = rcmail::get_instance();

        $data['aliasname'] = mb_convert_case(rcube_utils::get_input_value('_aliasname', rcube_utils::INPUT_POST, true), MB_CASE_LOWER, RCUBE_CHARSET);

        $this->plugin->api->output->add_label('toolbox.aliases-aliasdeleted');

        toolbox_logger::step('aliases', 'delete_start');

        if (!$storage->delete_tool_data($this->rcube->user->get_username(), $data)) {
            toolbox_logger::error('aliases', 'delete', 'persistence_failed');
            $error = rcmail::Q($this->plugin->gettext('aliases-aliasdeletederror'));
        }
        else {
            toolbox_logger::step('aliases', 'delete_complete');
        }

        $rcmail->output->command('plugin.toolbox.check_delete', $error);
    }

    /**
     * Append one existing, new, or hidden-template alias row to the form.
     *
     * @param html_table $address_table Target table, updated by reference.
     * @param ?string $class CSS class for an existing row or null.
     * @param ?array $alias Row information or null for a template row.
     * @param array $attrib Existing renderer attributes.
     * @param array $row_attrib Additional HTML attributes.
     * @return void
     */
    private function alias_address_row(&$address_table, $class, $alias, $attrib, $row_attrib = [])
    {
        $hidden_name = new html_hiddenfield(['name' => '_aliasname[]', 'value' => (isset($alias['name']) ? $alias['name'] : '')]);

        $row_attrib = !isset($class) ? array_merge($row_attrib, ['style' => 'display: none;']) : array_merge($row_attrib, ['class' => $class]);
        $address_table->set_row_attribs($row_attrib);

        $button_type = isset($alias['active']) && $alias['active'] != false ? 'enabled' : 'disabled';
        $address_table->add(['class' => 'email ' . $button_type], (isset($alias['name']) ? $alias['name'] : ''));

        // Use the same Roundcube command binding as Add and Delete. The command
        // is registered in js/toolbox.aliases.js during Roundcube's init event.
        $enable_button = $this->rcube->output->button([
            'command' => 'plugin.toolbox.toggle_alias',
            'type' => 'link',
            'class' => 'toolbox-alias-toggle ' . $button_type,
            'label' => 'toolbox.toolbox-' . $button_type,
            'content' => ' ',
            'title' => 'toolbox.toolbox-' . $button_type,
        ]);
        $input_active = new html_checkbox(['name' => '_aliasactive[]', 'value' => '1', 'style' => 'display: none;']);
        $address_table->add('status', $enable_button . $input_active->show(isset($alias['active']) ? $alias['active'] : ''));

        $del_button = $this->rcube->output->button(['command' => 'plugin.toolbox.delete_alias', 'type' => 'link', 'class' => 'delete', 'label' => 'delete', 'content' => ' ', 'title' => 'delete']);
        $address_table->add('control', $del_button . $hidden_name->show());
    }
    /**
     * Load the Aliases editor script only for the Aliases settings page.
     *
     * @return void
     */
    public function include_edit_assets(): void
    {
        $this->plugin->include_script('js/toolbox.aliases.js');
    }

    /**
     * Preserve the alias-specific success and error messages from preparation.
     *
     * @param object $storage Active storage backend (not needed for preparation).
     * @return array Settings and labels for the shared save dispatcher.
     */
    public function prepare_save($storage): ?array
    {
        return $this->prepare_settings();
    }

    /**
     * Route alias operations to the existing, tested implementations.
     *
     * @param string $action Name of the requested alias operation.
     * @param object $storage Shared storage backend.
     * @return bool True when the operation was performed.
     */
    public function handle_action(string $action, $storage): bool
    {
        switch ($action) {
            case 'toggle':
                $this->toggle_alias($storage);
                return true;
            case 'check':
                $this->check_alias($storage);
                return true;
            case 'delete':
                $this->delete_alias($storage);
                return true;
        }

        return false;
    }

}

// END OF FILE
