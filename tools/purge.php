<?php

/**
 * Purge preferences tool for Roundcube Toolbox.
 *
 * Builds the per-user Trash/Junk retention controls and saves user
 * preferences. The scheduled purge scripts and domain defaults remain
 * in their existing implementations.
 */
class toolbox_tool_purge extends toolbox_tool_module
{
    /**
     * Initialize the Purge module.
     *
     * Purge has no runtime hooks; its UI and save methods are called by
     * the Toolbox settings dispatcher.
     *
     * @return void
     */
    public function init(): void
    {
    }

    /**
     * Build the existing per-user Purge settings form data.
     *
     * @param object $storage Active Toolbox storage instance.
     * @return array Form fieldset data for the central Toolbox renderer.
     */
    public function render_form($storage): array
    {
        // load data
        $selected = $storage->load_tool_data($this->rcube->user->get_username());

        $folders = ["trash", "junk"];

        $tooldata = ['intro' => '', 'name' => rcmail::Q($this->plugin->gettext('purge-manage')), 'class' => 'toolbox-purgetable', 'cols' => 2];

        foreach ($folders as $folder) {

            $field_name = '_user' . $folder;
            $field_id = 'rcmfd' . $field_name;

            if (!isset($selected[$folder])) { $selected[$folder] = 0; }
            switch ($selected[$folder]) {
                case 0:
                    $domainoption = rcmail::Q($this->plugin->gettext('purge-always'));
                    break;
                case 1:
                    $domainoption = rcmail::Q('1 ' . $this->plugin->gettext('purge-day'));
                    break;
                default:
                    $domainoption = rcmail::Q($selected[$folder] . ' ' . $this->plugin->gettext('purge-days'));
                    break;
            }
            $domainoption .= ' (' . rcmail::Q($this->plugin->gettext('purge-domainvalue')) . ')';

            $select = new html_select(['name' => $field_name, 'id' => $field_id]);
            $select->add($domainoption, 'NULL');
            $select->add('──────────', NULL, ['disabled' => 'disabled']);
            $select->add(rcmail::Q($this->plugin->gettext('purge-always')), '0');
            $select->add(rcmail::Q('1 ' . $this->plugin->gettext('purge-day')), '1');
            $options = ['3', '7', '15', '30', '45', '60', '90', '120', '150', '180', '270', '360'];
            foreach($options as $option) {
                $select->add(rcmail::Q($option . ' ' . $this->plugin->gettext('purge-days')), $option);
            }

            $tooldata['rows'][$folder] = [
                'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('purge-'.$folder))),
                'content' => $select->show($this->preferences->get('toolbox_purge_' . $folder, 'NULL'))
            ];

        }

        return $tooldata;
    }

    /**
     * Save Trash and Junk preferences using Roundcube's user profile API.
     *
     * @return bool True on success; false if Roundcube rejected the save.
     */
    public function save_preferences(): bool
    {
        // save user's preferences
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function save]: save user's preferences");
        }
        if (!$this->preferences->save([
            'toolbox_purge_trash' => rcube_utils::get_input_value('_usertrash', rcube_utils::INPUT_POST),
            'toolbox_purge_junk' => rcube_utils::get_input_value('_userjunk', rcube_utils::INPUT_POST),
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

