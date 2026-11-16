<?php

/**
 * Forwarding management module for Roundcube Toolbox.
 *
 * Builds the forwarding settings form, including the keep-copies option,
 * and prepares the submitted forwarding addresses for the existing storage
 * backend. Toolbox continues to dispatch saves and perform database writes.
 */
class toolbox_tool_forward extends toolbox_tool_module
{
    /**
     * Initialise the Forwarding module.
     *
     * No direct hooks are needed; Toolbox calls its form and save methods.
     *
     * @return void
     */
    public function init(): void
    {
    }

    /**
     * Build forwarding address controls using existing Toolbox storage data.
     *
     * @param object $storage Active Toolbox storage instance.
     * @param array $attrib Form renderer attributes, retained for compatibility.
     * @return array Form data passed to Toolbox's fieldset renderer.
     */
    public function render_form($storage, $attrib): array
    {
        $parts = explode('@', $this->rcube->user->get_username());

        // Add JS labels if needed
        $this->rcube->output->add_label(
            'toolbox.forward-invalidaddress',
            'toolbox.forward-atleastoneaddress',
            'toolbox.forward-emptyaddress',
            'toolbox.forward-deleteaddress',
            'toolbox.forward-deletealladdresses',
            'toolbox.forward-addressexists'
            );
        // define table sorting
        $sorts = [
            '#forward-addresses-table' => [0, 'true']
        ];
        $this->rcube->output->set_env('table_sort', $sorts);

        $tooldata = ['name' => rcmail::Q($this->plugin->gettext('forward-manage')), 'class' => 'toolbox-forwardtable', 'cols' => 2];

        $settings = $storage->load_tool_data($this->rcube->user->get_username());
        $addresses = explode(',', $settings['goto']);

        // check user's address presence (keep copies in mailbox), remove from list and set $keepcopies to true
        $keepcopies = false;
        $key = array_search($this->rcube->user->get_username(), $addresses);
        if ($key !== false) {
            array_splice($addresses, $key, 1);
            $keepcopies = true;
        }

        // remove vacation domain if exists
        $key = array_search($parts[0] . '#' . $parts[1] . '@' . $this->rcube->config->get('toolbox_postfixadmin_vacation_domain'), $addresses);
        if ($key !== false) {
            array_splice($addresses, $key, 1);
        }

        sort($addresses);

        $field_id = 'rcmfd_forwardaddress';
        $input_forwardaddress = new html_inputfield(['name' => '_forwardaddress', 'id' => $field_id, 'title' => rcmail::Q($this->plugin->gettext('forward-address')), 'size' => 80, 'placeholder' => rcmail::Q($this->plugin->gettext('forward-address'))]);

        $field_id = 'rcmbtn_add_address';
        $button_addaddress = $this->rcube->output->button(['id' => $field_id, 'command' => 'plugin.toolbox.add_forward_address', 'type' => 'input', 'class' => 'button', 'label' => 'toolbox.forward-addaddress']);

        $tooldata['intro'] = html::div('address-input grouped', $input_forwardaddress->show() . $button_addaddress);

        $delete_all = $this->rcube->output->button(['class' => 'delete-all', 'command' => 'plugin.toolbox.delete_all_addresses', 'type' => 'link', 'label' => 'toolbox.toolbox-deleteall', 'title' => 'toolbox.forward-deletealladdresses']);

        $table = new html_table(['class' => 'addressprefstable propform', 'cols' => 2]);
        $table->add(['colspan' => 2, 'id' => 'listcontrols'], $delete_all);

        $address_table = new html_table(['id' => 'forward-addresses-table', 'class' => 'records-table sortable-table forward-addresses-table fixedheader', 'cellspacing' => '0', 'cols' => 2]);
        $address_table->add_header('email', $this->rcube->output->button(['command' => 'plugin.toolbox.table_sort', 'prop' => '#forward-addresses-table', 'type' => 'link', 'label' => 'toolbox.toolbox-addresses', 'title' => 'sortby']));
        $address_table->add_header('control', '&nbsp;');

        $this->rcube->output->set_env('forward_addresses_count', !empty($addresses) ? count($addresses) : 0);
        foreach ($addresses as $address) {
            if ($address != '') {
                $this->forward_address_row($address_table, 'forward', $address, $attrib);
            }
        }

        // add no address and new address row at the end
        $noaddresses = !empty($addresses) ? 'display: none;' : '';

        $address_table->set_row_attribs(['class' => 'noaddress', 'style' => $noaddresses]);
        $address_table->add(['colspan' => '2'], rcube_utils::rep_specialchars_output(rcmail::Q($this->plugin->gettext('forward-noaddress'))));

        $this->forward_address_row($address_table, null, null, $attrib, ['class' => 'newaddress']);

        $table->add(['colspan' => 2, 'class' => 'scroller'], html::div(['id' => 'forward-addresses-cont'], $address_table->show()));

        $field_id = 'rcmfd_keepcopies';
        $input_keepcopies = new html_checkbox(['name' => '_forwardkeepcopies', 'id' => $field_id, 'value' => '1']);

        $tooldata['rows']['keepcopies'] = [
            'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('forward-keepcopies'))),
            'content' => $input_keepcopies->show($keepcopies)
        ];

        $tooldata['content'] = $table->show();

        return $tooldata;
    }

    /**
     * Prepare forwarding entries and the keep-copies flag for storage.
     *
     * This method deliberately does not save to the database; the existing
     * Toolbox save dispatcher and storage hooks perform that operation.
     *
     * @return array Settings array accepted by Toolbox storage.
     */
    public function prepare_settings(): array
    {
        $new_settings = [];

        // save forward addresses
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function save]: save forward addresses");
        }
        $addresses = rcube_utils::get_input_value('_forwardaddresses', rcube_utils::INPUT_POST);
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function save]: new forward addresses = [" . implode(', ', $addresses) . "]");
        }

        foreach ($addresses as $ids => $address) {
            $new_settings['main']['addresses'][] = ['value' => mb_convert_case(trim($address), MB_CASE_LOWER, RCUBE_CHARSET)];
        }

        $new_settings['main']['keepcopies'] = rcube_utils::get_input_value('_forwardkeepcopies', rcube_utils::INPUT_POST) ?: false;

        return $new_settings;
    }

    /**
     * Load Forward-specific commands and controls only on the Forward editor.
     *
     * @return void
     */
    public function include_edit_assets(): void
    {
        $this->plugin->include_script('js/toolbox.forward.js');
    }

    /**
     * Append one forwarding-address row to the existing HTML table.
     *
     * @param html_table $address_table Target address table, modified in place.
     * @param ?string $class Row CSS class or null for the hidden template row.
     * @param ?string $value Forwarding address for this row.
     * @param array $attrib Original form attributes, kept for compatibility.
     * @param array $row_attrib Extra row attributes.
     * @return void
     */
    private function forward_address_row(&$address_table, $class, $value, $attrib, $row_attrib = [])
    {
        $hidden_field = new html_hiddenfield(['name' => '_forwardaddresses[]', 'value' => $value]);

        $row_attrib = !isset($class) ? array_merge($row_attrib, ['style' => 'display: none;']) : array_merge($row_attrib, ['class' => $class]);
        $address_table->set_row_attribs($row_attrib);

        $address_table->add(['class' => 'email'], $value);

        $del_button = $this->rcube->output->button(['command' => 'plugin.toolbox.delete_forward_address', 'type' => 'link', 'class' => 'delete', 'label' => 'delete', 'content' => ' ', 'title' => 'delete']);
        $address_table->add('control', $del_button . $hidden_field->show());
    }
}

// END OF FILE
