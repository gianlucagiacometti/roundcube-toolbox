<?php

/**
 * Domain Customise module for Roundcube Toolbox.
 *
 * Handles customised blank pages, CSS, logos and domain purge defaults.
 * Shared storage writes and save hooks remain managed by the parent plugin.
 * Customise loads its own editor JavaScript and CodeMirror dependencies.
 */
class toolbox_tool_customise extends toolbox_tool_module
{
    private $logo_types = [
        'customise-logo-type-all' => "",
        'customise-logo-type-favicon' => "[favicon]",
        'customise-logo-type-print' => "[print]",
        'customise-logo-type-small' => "[small]",
        'customise-logo-type-dark' => "[dark]",
        'customise-logo-type-small-dark' => "[small-dark]"
    ];

    /**
     * Interpret a stored Customise switch independently of SQL driver types.
     *
     * PostgreSQL can return boolean flags as "t"/"f", MySQL as 1/0,
     * while other adapters return booleans or "true"/"false" strings.
     *
     * @param mixed $value Raw persisted switch value.
     * @return bool Whether the Customise feature is enabled.
     */
    private function is_enabled($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_int($value)) {
            return $value === 1;
        }

        return is_string($value)
            && in_array(strtolower(trim($value)), ['t', 'true', '1', 'yes', 'on'], true);
    }

    /**
     * Initialise the Customise module.
     *
     * Its boot-time skin configuration is applied by the Toolbox dispatcher
     * after the storage backend has been initialised.
     *
     * @return void
     */
    public function init(): void
    {
    }

    /**
     * Determine the effective skin for this user's Customise overrides.
     *
     * Preserve the old whitelist behavior, but keep it entirely within the
     * Customise module. The fallback is Roundcube's configured active skin.
     *
     * @param string $fallback Roundcube's configured skin.
     * @return string Allowed user skin, or the configured fallback.
     */
    private function active_skin(string $fallback): string
    {
        $allowed = $this->rcube->config->get('skins_allowed', []);
        $preferred = $this->preferences->get('skin', $fallback);
        return is_array($allowed) && is_string($preferred)
            && in_array($preferred, $allowed, true) ? $preferred : $fallback;
    }

    /**
     * Apply the user's custom skin configuration at Toolbox startup.
     *
     * @param object $storage Current Toolbox storage backend.
     * @param string $skin Current Roundcube skin name.
     * @param string $plugin_id Plugin directory identifier.
     * @return void
     */
    public function apply_config($storage, string $skin, string $plugin_id): void
    {
        $mailbox_parts = explode('@', $this->rcube->user->get_username());
        if (!isset($mailbox_parts[1]) || $mailbox_parts[0] === '' || $mailbox_parts[1] === '') {
            // Domain-specific assets cannot be named without a domain.
            return;
        }

        // Resolve Roundcube's active skin in this module, not in Toolbox.
        $skin = $this->active_skin($skin);

        // load customised skin configuration from config
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function init]: skin: " . $skin);
            rcube::write_log($this->logfile, "STEP in [function init]: load customised skin configuration from config");
        }
        $config = $this->rcube->config->get('toolbox_customise_skins', []);
        $config = isset($config[$skin]) ? $config[$skin] : (isset($config['default']) ? $config['default'] : []);

        // load customised skin configuration from database
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function init]: load customised skin configuration from database");
        }
        $customise = $storage->load_customised_config($this->rcube->user->get_username(), $skin);
        $parts = explode('@', $this->rcube->user->get_username());

        // customise blank page
        $options = ['blankpage_type', 'blankpage_url', 'blankpage_image', 'blankpage_custom'];
        if ($this->is_enabled($customise['customise_blankpage'] ?? false)) {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'customise blank page' database option selected: override config");
            }
            foreach ($options as $option) {
                $config[$option] = $customise[$option] ?? '';
            }
        }
        else {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'customise blank page' database option not selected");
            }
            foreach ($options as $option) {
                if (!isset($config[$option])) {
                    $config[$option] = '';
                }
            }
        }

        if (isset($config['blankpage_type']) && (in_array($config['blankpage_type'], ['url', 'image', 'custom']))) {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: customised blank page type: " . $config['blankpage_type']);
            }

            switch ($config['blankpage_type']) {
                case "url":
                    if (isset($config['blankpage_url']) && ($config['blankpage_url'] != '')) {
                        $this->rcube->output->set_env('blankpage', $config['blankpage_url']);
                    }
                    break;
                case "image":
                    if (isset($config['blankpage_image']) && ($config['blankpage_image'] != '')) {
                        if ($this->loglevel > 2) {
                            rcube::write_log($this->logfile, "STEP in [function init]: found customised image");
                        }
                        $image = $config['blankpage_image'];
                        // we read the content of the file 'watermark.html' in the skin folder and change the url content with our cutomised image
                        if (file_exists(RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'skins' . \DIRECTORY_SEPARATOR . $skin . \DIRECTORY_SEPARATOR . 'watermark.html')) {
                            if ($this->loglevel > 2) {
                                rcube::write_log($this->logfile, "STEP in [function init]: found original watermark");
                            }
                            $blankpage = file_get_contents(RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'skins' . \DIRECTORY_SEPARATOR . $skin . \DIRECTORY_SEPARATOR . 'watermark.html');
                            if ($blankpage != '') {
                                // we need a file in a folder named 'tmp' in plugin/toolbox (temp cannot be used for .htaccess limitations)
                                // filename must contain a dot for .htaccess limitations
                                // a file is needed since the 'blankpage' env variable needs a real file (loaded in apps.js)
                                $tmp = RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'plugins' . \DIRECTORY_SEPARATOR . $plugin_id . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . 'blankpage.' . $parts[1] . '.html';
                                file_put_contents($tmp, preg_replace('!url\(.*?\)!U', "url(" . $config['blankpage_image'] . ")", $blankpage));
                                if ($this->loglevel > 2) {
                                    rcube::write_log($this->logfile, "STEP in [function init]: write new watermark in folder tmp");
                                }
                                $this->rcube->output->set_env('blankpage', 'static.php' . \DIRECTORY_SEPARATOR . 'plugins' . \DIRECTORY_SEPARATOR . $plugin_id . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . basename($tmp));
                            }
                        }
                    }
                    break;
                case "custom":
                    if (isset($config['blankpage_custom']) && ($config['blankpage_custom'] != '')) {
                        // we need a file in a folder named 'tmp' in plugin/toolbox (temp cannot be used for .htaccess limitations)
                        // filename must contain a dot for .htaccess limitations
                        // a file is needed since the 'blankpage' env variable needs a real file (loaded in apps.js)
                        $tmp = RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'plugins' . \DIRECTORY_SEPARATOR . $plugin_id . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . 'blankpage.' . $parts[1] . '.html';
                        file_put_contents($tmp, $config['blankpage_custom']);
                        $this->rcube->output->set_env('blankpage', 'static.php' . \DIRECTORY_SEPARATOR . 'plugins' . \DIRECTORY_SEPARATOR . $plugin_id . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . basename($tmp));
                    }
                    break;
            }
        }
        elseif ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function init]: customised blank page type: Roundcube default");
        }

        // customise css
        if ($this->is_enabled($customise['customise_css'] ?? false) && !empty($customise['additional_css'])) {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'customise additional css' database option selected: override config");
            }
            // we need a file in a folder named 'tmp' in plugin/toolbox (temp cannot be used for .htaccess limitations)
            // filename must contain a dot for .htaccess limitations
            $tmp = RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'plugins' . \DIRECTORY_SEPARATOR . $plugin_id . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . 'stylesheet.' . $parts[1] . '.css';
            file_put_contents($tmp, $customise['additional_css']);
            // css is loaded only under some circumstances (not when rcmail_output_json is called)
            if (method_exists($this->rcube->output, 'include_css')) {
                $this->rcube->output->include_css('plugins' . \DIRECTORY_SEPARATOR . $plugin_id . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . basename($tmp));
            }
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: customised additional css loaded");
            }
        }
        else {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'customise additional css' database option not selected: loading from config");
            }
            $additional_css = $config['additional_css'] ?? null;
            if (is_string($additional_css) && $additional_css !== '' && is_file($additional_css)) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: valid customised additional css file defined, found and loaded: {$additional_css}");
                }
                // css is loaded only under some circumstances (not when rcmail_output_json is called)
                if (method_exists($this->rcube->output, 'include_css')) {
                    $this->rcube->output->include_css($additional_css);
                }
            }
            elseif ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: customised additional css file not found, empty or not defined");
            }
        }

        // customise logo
        if ($this->is_enabled($customise['customise_logo'] ?? false) && !empty($customise['customised_logo'])) {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'customise logo' database option selected: override config");
            }
            $logo = json_decode($customise['customised_logo'], true) ?: [];
            if ((json_last_error() !== JSON_ERROR_NONE) && ($this->loglevel > 0)) {
                rcube::write_log($this->logfile, "ERROR in [function init]: 'customised logo' database value has not a proper json format");
            }
            $logo = array_map('base64_decode', $logo);
            // Is it better to merge or replace?
            // $this->rcube->config->set('skin_logo', array_merge($this->rcube->config->get('skin_logo'), $logo));
            $this->rcube->config->set('skin_logo', $logo);
        }
        elseif ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function init]: 'customise logo' database option not selected: loading from config");
        }
    }

    /**
     * Render domain administrative Customise settings without changing field IDs.
     *
     * @param object $storage Current Toolbox storage backend.
     * @param array $attrib Roundcube form attributes.
     * @param callable $render_fieldset Host Toolbox fieldset renderer.
     * @return string Full Customise form body.
     */
    public function render_form($storage, array $attrib, callable $render_fieldset): string
    {
        $parts = explode('@', $this->rcube->user->get_username());
        $form_content = '';
        if ($storage->is_domain_admin($this->rcube->user->get_username())) {

            // Add JS labels if needed
            $this->rcube->output->add_label(
                'toolbox.customise-logo-invalidaddress',
                'toolbox.customise-logo-emptycustomisedlogotemplate',
                'toolbox.customise-logo-emptycustomisedlogotype',
                'toolbox.customise-logo-emptycustomisedlogoimage',
                'toolbox.customise-logo-deletecustomisedlogo',
                'toolbox.customise-logo-deleteallcustomisedlogos',
                'toolbox.customise-logo-customisedlogoexists'
                );

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function tool_render_form]: user is domain admin");
            }

            // load data
            $selected = $storage->load_tool_data($this->rcube->user->get_username());

            $form_content .= html::div(['class' => 'tool-title'], rcmail::Q($this->plugin->gettext('customise-manage')) . ' ' . $parts[1]);

            // purge trash and junk folders
            $folders = ["trash", "junk"];

            $tooldata = ['intro' => '', 'name' => rcmail::Q($this->plugin->gettext('customise-purge')), 'class' => 'toolbox-purgetable', 'cols' => 2];

            foreach ($folders as $folder) {

                $field_name = '_domain' . $folder;
                $field_id = 'rcmfd' . $field_name;

                $select = new html_select(['name' => $field_name, 'id' => $field_id]);
                $select->add(rcmail::Q($this->plugin->gettext('purge-always')), 0);
                $select->add(rcmail::Q('1 ' . $this->plugin->gettext('purge-day')), 1);
                $options = ['3', '7', '15', '30', '45', '60', '90', '120', '150', '180', '270', '360'];
                foreach($options as $option) {
                    $select->add(rcmail::Q($option . ' ' . $this->plugin->gettext('purge-days')), intval($option));
                }

                $tooldata['rows'][$folder] = [
                    'title' => html::label($field_id, rcmail::Q($this->plugin->gettext('purge-'.$folder))),
                    'content' => $select->show($selected[$folder])
                ];

            }

            $form_content .= $render_fieldset($tooldata, 'purge');

            // blank page settings
            $form_content .= html::div(['class' => 'tool-subtitle'], rcmail::Q($this->plugin->gettext('skin')));

            // set settings for each skin
            foreach ($this->get_skins() as $skin => $header) {
                $skin_settings = $selected['skins'][$skin] ?? [];

                // set field visibility
                $row_attribs = [
                    'blankpage' => ['style' => 'display: none;'],
                    'url' => ['style' => 'display: none;'],
                    'image' => ['style' => 'display: none;'],
                    'custom' => ['style' => 'display: none;'],
                    'css' => ['style' => 'display: none;'],
                    'logo' => ['style' => 'display: none;']
                ];
                if (isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin])) {
                    if ($this->is_enabled($selected['skins'][$skin]['customise_blankpage'] ?? false)) {
                        $row_attribs['blankpage'] = [];
                    }
                    if (isset($selected['skins'][$skin]['blankpage_type']) && in_array($selected['skins'][$skin]['blankpage_type'], ['url', 'image', 'custom'])) {
                        $row_attribs[$selected['skins'][$skin]['blankpage_type']] = [];
                    }
                    if ($this->is_enabled($selected['skins'][$skin]['customise_css'] ?? false)) {
                        $row_attribs['css'] = [];
                    }
                    if ($this->is_enabled($selected['skins'][$skin]['customise_logo'] ?? false)) {
                        $row_attribs['logo'] = [];
                    }
                }

                // skin header
                $form_content .= $header;

                // blank page
                $tooldata = ['intro' => '', 'name' => rcmail::Q($this->plugin->gettext('customise-blankpage-skin')), 'class' => 'toolbox-customisetable', 'cols' => 1];

                $button_id = 'rcmfd_blankpageskin_selector_' . $skin;
                $input_blankpageselector = new html_checkbox(['name' => '_blankpageselector_' . $skin, 'id' => $button_id, 'value' => '1', 'class' => 'customise-blankpage-selector', 'title' => rcmail::Q($this->plugin->gettext('customise-blankpage'))]);

                $tooldata['rows']['skinblankpage'] = [
                    'content' => $input_blankpageselector->show($skin_settings['customise_blankpage'] ?? ' ') . html::label(['for' => $button_id, 'class' => 'customise-blankpage-label'], rcmail::Q($this->plugin->gettext('customise-blankpage')))
                ];

                // blank page type table
                $blankpagetype_table = new html_table(['id' => 'customise_blankpage_type_table_' . $skin, 'class' => 'customise-blankpage-type-table', 'cols' => 1]);

                // Roundcube default blank page
                $button_id = 'rcmrb_blankpageskindefault_' . $skin;
                $input_skindefault = new html_radiobutton(['name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => '', 'class' => 'customise-blankpage-skin-selector']);

                $blankpagetype_table->add(['class' => 'blankpage-type-check'], $input_skindefault->show($skin_settings['blankpage_type'] ?? '') . html::label($button_id, rcmail::Q($this->plugin->gettext('customise-blankpage-skin-default'))));

                // blank page as url
                $button_id = 'rcmrb_blankpageskinurl_' . $skin;
                $input_skinurl = new html_radiobutton(['name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'url', 'class' => 'customise-blankpage-skin-selector']);

                $blankpagetype_table->add(['class' => 'blankpage-type-check'], $input_skinurl->show($skin_settings['blankpage_type'] ?? '') . html::label($button_id, rcmail::Q($this->plugin->gettext('customise-blankpage-skin-url'))));

                $field_id = 'rcmfd_blankpageurl_' . $skin;
                $input_blankpageurl = new html_inputfield(['name' => '_blankpageurl_' . $skin, 'class' => 'tool-skin-blankpage-url', 'type' => 'url', 'id' => $field_id, 'title' => rcmail::Q($this->plugin->gettext('customise-blankpage-url')), 'placeholder' => rcmail::Q($this->plugin->gettext('customise-blankpage-url'))]);

                $blankpagetype_table->set_row_attribs($row_attribs['url']);
                $blankpagetype_table->add(['class' => 'blankpage-type-content'], $input_blankpageurl->show($skin_settings['blankpage_url'] ?? '') . html::span(['id' => $button_id . '_content'], ''));

                // blank page with custom image
                $button_id = 'rcmrb_blankpageskinimage_' . $skin;
                $input_skinimage = new html_radiobutton(['name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'image', 'class' => 'customise-blankpage-skin-selector']);

                $blankpagetype_table->add(['class' => 'blankpage-type-check'], $input_skinimage->show($skin_settings['blankpage_type'] ?? '') . html::label($button_id, rcmail::Q($this->plugin->gettext('customise-blankpage-skin-image'))));

                $image_id = 'rcmbtn_modifyblankpageimage_' . $skin;
                $blankpageimage = html::img([
                    'id'      => $image_id,
                    'src'     => (isset($selected['skins'][$skin]['blankpage_image']) && ($selected['skins'][$skin]['blankpage_image'] != '') ? $selected['skins'][$skin]['blankpage_image'] : $this->rcube->output->asset_url('program/resources/blank.gif')),
                    'class'   => 'blankpage-image',
                    'width'   => 256,
                    'onerror' => "this.src = rcmail.assets_path('program/resources/blank.gif'); this.onerror = null",
                ]);

                $input_blankpageimage = new html_inputfield(['id' => '_blankpageimage_' . $skin, 'type' => 'file', 'name' => '_blankpageimage_' . $skin, 'class' => 'blankpage-image-upload', 'data-image' => $image_id]);
                $hidden_blankpageimagecontrol = new html_hiddenfield(['id' => '_blankpageimage_' . $skin . '_control', 'name' => '_blankpageimage_' . $skin . '_control', 'value' => (isset($selected['skins'][$skin]['blankpage_image']) && ($selected['skins'][$skin]['blankpage_image'] != '') ? '1' : '0')]);

                $field_id = 'rcmbtn_deleteblankpageimage_' . $skin;
                $button_deleteblankpageimage = $this->rcube->output->button(['id' => $field_id, 'command' => 'plugin.toolbox.reset_image', 'prop' => '#'.$image_id, 'type' => 'link', 'class' => 'blankpage-image-delete-button', 'title' => 'delete', 'label' => 'delete', 'content' => ' ', 'data-image' => '_blankpageimage_' . $skin . '_control']);

                $blankpagewrapper =
                    html::label(['class' => 'blankpage-item-image blankpage-drop-target'],
                        $blankpageimage .
                        $input_blankpageimage->show()
                    ) .
                    html::span(['class' => 'blankpage-image-delete', 'title' => rcmail::Q($this->plugin->gettext('delete'))], $button_deleteblankpageimage);

                $blankpagetype_table->set_row_attribs($row_attribs['image']);
                $blankpagetype_table->add(['class' => 'blankpage-type-content'], $blankpagewrapper . html::span(['id' => $button_id . '_content'], '') . $hidden_blankpageimagecontrol->show());

                // custom blank page
                $button_id = 'rcmrb_blankpageskincustom_' . $skin;
                $input_skincustom = new html_radiobutton(['name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'custom', 'class' => 'customise-blankpage-skin-selector']);

                $blankpagetype_table->add(['class' => 'blankpage-type-check'], $input_skincustom->show($skin_settings['blankpage_type'] ?? '') . html::label($button_id, rcmail::Q($this->plugin->gettext('customise-blankpage-skin-custom'))));

                $field_id = 'rcmfd_blankpagecustom_' . $skin;
                $input_blankpagecustom = new html_textarea(['name' => '_blankpagecustom_' . $skin, 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 92, 'class' => 'mce_editor']);

                $blankpagetype_table->set_row_attribs($row_attribs['custom']);
                $blankpagetype_table->add(['class' => 'blankpage-type-content'], $input_blankpagecustom->show($skin_settings['blankpage_custom'] ?? '') . html::span(['id' => $button_id . '_content'], ''));

                // show blank page type table
                $tooldata['rows']['blankpage'] = [
                    'content' => $blankpagetype_table->show(),
                    'row_attribs' => $row_attribs['blankpage']
                ];

                $form_content .= $render_fieldset($tooldata, 'blankpage');

                // css
                $tooldata = ['intro' => '', 'name' => 'CSS', 'class' => 'toolbox-customisetable', 'cols' => 1];

                $button_id = 'rcmfd_additionalcss_selector_' . $skin;
                $input_additionalcssselector = new html_checkbox(['name' => '_additionalcssselector_' . $skin, 'id' => $button_id, 'value' => '1', 'class' => 'customise-additional-css-selector', 'title' => rcmail::Q($this->plugin->gettext('customise-additional-css'))]);

                $tooldata['rows']['skinadditionalcss'] = [
                    'content' => $input_additionalcssselector->show($skin_settings['customise_css'] ?? '') . html::label(['for' => $button_id, 'class' => 'customise-additional-css-label'], rcmail::Q($this->plugin->gettext('customise-additional-css')))
                ];

                $field_id = 'rcmfd_additionalcss_' . $skin;
                $input_additionalcss = new html_textarea(['name' => '_additionalcss_' . $skin, 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 92, 'class' => 'tool-skin-additional-css']);

                $tooldata['rows']['additionalcss'] = [
                    'content' => $input_additionalcss->show($skin_settings['additional_css'] ?? '') . html::span(['id' => $button_id . '_content'], ''),
                    'row_attribs' => $row_attribs['css']
                ];

                $form_content .= $render_fieldset($tooldata, 'additionalcss');

                // logo
                $tooldata = ['intro' => '', 'name' => 'Logo', 'class' => 'toolbox-customisetable', 'cols' => 1];

                $button_id = 'rcmfd_customiselogo_selector_' . $skin;
                $input_customiselogoselector = new html_checkbox(['name' => '_customiselogoselector_' . $skin, 'id' => $button_id, 'value' => '1', 'class' => 'customise-logo-selector', 'title' => rcmail::Q($this->plugin->gettext('customise-logo'))]);

                $tooldata['rows']['skincustomiselogo'] = [
                    'content' => $input_customiselogoselector->show($skin_settings['customise_logo'] ?? '') . html::label(['for' => $button_id, 'class' => 'customise-logo-label'], rcmail::Q($this->plugin->gettext('customise-logo')))
                ];

                $form_content .= $render_fieldset($tooldata, 'customiselogo');

                $tooldata = ['name' => rcmail::Q($this->plugin->gettext('customise-logo-template-new')), 'class' => 'toolbox-customiselogotable', 'cols' => 3];

                $field_id = 'rcmfd_newcustomisedlogotemplate_' . $skin;
                $input_newtemplate = new html_select(['name' => '_newcustomisedlogotemplate_' . $skin, 'id' => $field_id]);
                $input_newtemplate->add(array_merge([rcmail::Q($this->plugin->gettext('customise-logo-template-all'))], $this->get_templates($skin)), array_merge(['*'], $this->get_templates($skin)));
                $field_id = 'rcmfd_newcustomisedlogotype_' . $skin;
                $input_newlogotype = new html_select(['name' => '_newcustomisedlogotype_' . $skin, 'id' => $field_id]);
                $input_newlogotype->add(array_map(function($key) { return(rcmail::Q($this->plugin->gettext($key))); }, array_keys($this->logo_types)), array_values($this->logo_types));
                $field_id = 'rcmfd_newcustomisedlogoimage_' . $skin;
                $input_newlogoimage = new html_inputfield(['name' => '_newcustomisedlogoimage_' . $skin, 'id' => $field_id, 'type' => 'file']);

                $field_id = 'rcmbtn_addcustomisedlogo_' . $skin;
                $button_addcustomisedlogo = $this->rcube->output->button(['id' => $field_id, 'command' => 'plugin.toolbox.add_customised_logo', 'type' => 'input', 'class' => 'button', 'label' => 'toolbox.customise-logo-add-template', 'data-skin' => $skin]);

                $tooldata['intro'] = html::div('customise-template-input grouped input-group', $input_newtemplate->show() . $input_newlogotype->show('') . $input_newlogoimage->show('') . $button_addcustomisedlogo);

                $delete_all = $this->rcube->output->button(['class' => 'delete-all', 'command' => 'plugin.toolbox.delete_all_customisedlogos', 'type' => 'link', 'label' => 'toolbox.toolbox-deleteall', 'title' => 'toolbox.cunstomise-logo-deleteallcustomisedlogos', 'data-skin' => $skin]);

                $table = new html_table(['class' => 'toolbox-customiselogotable propform', 'cols' => 2]);
                $table->add(['colspan' => 2, 'id' => 'listcontrols'], $delete_all);

                $logo_table = new html_table(['id' => 'customised-logo-table-' . $skin, 'class' => 'records-table sortable-table toolbox-customisedlogotable fixedheader', 'cellspacing' => '0', 'cols' => 4]);
                $logo_table->add_header('logotemplate', rcmail::Q($this->plugin->gettext('customise-logo-template')));
                $logo_table->add_header('logotype', rcmail::Q($this->plugin->gettext('customise-logo-type')));
                $logo_table->add_header('logoimage', rcmail::Q($this->plugin->gettext('customise-logo-image')));
                $logo_table->add_header('control', '&nbsp;');

                $images = json_decode((string) ($skin_settings['customised_logo'] ?? ''), true);

                $this->rcube->output->set_env('customised_logo_count_' . $skin, (!empty($images) ? count($images) : 0));
                if (!empty($images)) {
                    $images = array_map('base64_decode', $images);
                    foreach ($images as $key => $image) {
                        $keyparts = explode(":", $key);
                        $model = explode("[", $keyparts[1]);
                        $logo = [
                            'skin' => $keyparts[0],
                            'template' => $model[0],
                            'type' => (isset($model[1]) ? '[' . $model[1] : ''),
                            'image' => $image
                        ];
                        $this->customised_logo_row($logo_table, 'logo', $logo, $attrib);
                    }
                    $nocustomisedlogo = 'display: none;';
                }

                // add new logo row at the end
                $logo_table->set_row_attribs(['class' => 'nologo', 'style' => isset($nocustomisedlogo) ? $nocustomisedlogo : '']);
                $logo_table->add(['colspan' => '4'], rcube_utils::rep_specialchars_output(rcmail::Q($this->plugin->gettext('customise-logo-nocustomisedlogo'))));

                $newlogo = [
                    'id' => 0,
                    'skin' => $skin,
                    'template' => '',
                    'type' => '',
                    'image' => ''
                ];
                $this->customised_logo_row($logo_table, null, $newlogo, $attrib, ['class' => 'newlogo']);

                $table->add(['colspan' => 3, 'class' => 'scroller'], html::div(['id' => 'customise-logo-customised-logo-cont-' . $skin], $logo_table->show()));

                $tooldata['content'] = $table->show();

                $form_content .= $render_fieldset($tooldata, array_merge(['class' => 'customiselogo-wrapper', 'id' => $button_id . '_content'], $row_attribs['logo']));

            }

        }
        return $form_content;
    }

    /**
     * Collect the existing domain-level and per-skin settings for storage.
     *
     * The parent dispatcher continues to run toolbox_save hooks and persist
     * this payload through the selected storage backend.
     *
     * @param object $storage Current Toolbox storage backend.
     * @return array Existing domain and skins settings structure.
     */
    public function prepare_settings($storage): array
    {
        $new_settings = [];
        if ($storage->is_domain_admin($this->rcube->user->get_username())) {

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function save]: prepare domain settings");
            }
            $new_settings['domain']['purge_trash'] = rcube_utils::get_input_value('_domaintrash', rcube_utils::INPUT_POST) ?: 0;
            $new_settings['domain']['purge_junk'] = rcube_utils::get_input_value('_domainjunk', rcube_utils::INPUT_POST) ?: 0;

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function save]: prepare skin settings");
            }
            foreach ($this->get_skins() as $skin => $header) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: processing skin " . $skin);
                }
                $new_settings['skins'][$skin]['customise_blankpage'] = rcube_utils::get_input_value('_blankpageselector_'.$skin, rcube_utils::INPUT_POST) ?: false;
                $new_settings['skins'][$skin]['blankpage_type'] = rcube_utils::get_input_value('_blankpagetype_'.$skin, rcube_utils::INPUT_POST) ?: null;
                $new_settings['skins'][$skin]['blankpage_url'] = rcube_utils::get_input_value('_blankpageurl_' . $skin, rcube_utils::INPUT_POST) ?: null;
                $allowed_types = [
                    'image/jpeg',
                    'image/jpg',
                    'image/jp2',
                    'image/tiff',
                    'image/tif',
                    'image/bmp',
                    'image/eps',
                    'image/gif',
                    'image/png',
                    'image/png8',
                    'image/png24',
                    'image/png32',
                    'image/svg',
                    'image/svg+xml',
                    'image/ico'
                ];
                $base64 = null;
                if ($filepath = ($_FILES['_blankpageimage_' . $skin]['tmp_name'] ?? null)) {
                    $filetype = $_FILES['_blankpageimage_' . $skin]['type'] ?? '';
                    if (in_array($filetype, $allowed_types)) {
                        $filecont = file_get_contents($filepath);
                        $base64 = 'data:' . $filetype . ';base64,' . base64_encode($filecont);
                    }
                }
                $new_settings['skins'][$skin]['blankpage_image'] = $base64 ?: null;
                $new_settings['skins'][$skin]['blankpage_image_control'] = rcube_utils::get_input_value('_blankpageimage_' . $skin . '_control', rcube_utils::INPUT_POST);
                $new_settings['skins'][$skin]['blankpage_custom'] = ($_POST['_blankpagecustom_' . $skin] ?? null) ?: null;
                $new_settings['skins'][$skin]['customise_css'] = rcube_utils::get_input_value('_additionalcssselector_' . $skin, rcube_utils::INPUT_POST) ?: false;
                $new_settings['skins'][$skin]['additional_css'] = rcube_utils::get_input_value('_additionalcss_' . $skin, rcube_utils::INPUT_POST) ?: null;
                $new_settings['skins'][$skin]['customise_logo'] = rcube_utils::get_input_value('_customiselogoselector_' . $skin, rcube_utils::INPUT_POST) ?: false;

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: process customised logos");
                }
                $customised_logotemplates = [];
                $input_logotemplate = rcube_utils::get_input_value('_customisedlogotemplate_' . $skin, rcube_utils::INPUT_POST) ?: [];
                foreach($input_logotemplate as $template) {
                    $customised_logotemplates[] = $template;
                }
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: " . count($customised_logotemplates) . " customised logos found for skin " . $skin);
                }
                $customised_logotypes = [];
                $input_logotype = rcube_utils::get_input_value('_customisedlogotype_' . $skin, rcube_utils::INPUT_POST) ?: [];
                foreach($input_logotype as $type) {
                    $customised_logotypes[] = $type;
                }
                $customised_logoimages = [];
                $input_logoimage = rcube_utils::get_input_value('_customisedlogoimage_' . $skin, rcube_utils::INPUT_POST) ?: [];
                foreach($input_logoimage as $image) {
                    $customised_logoimages[] = $image;
                }
                $customised_logo = [];
                foreach($customised_logotemplates as $key => $template) {
                    if ($template != '') {
                        $idx = $skin . ':' . $template . $customised_logotypes[$key];
                        $customised_logo[$idx] = $customised_logoimages[$key];
                    }
                }
                // we need a trick: to avoid escaping characters and problems with : in 'data:image' let's base64 encode again
                $new_settings['skins'][$skin]['customised_logo'] = json_encode(array_map('base64_encode', $customised_logo), JSON_FORCE_OBJECT);

            }

        }
        return $new_settings;
    }

    /**
     * Append a customised logo row with its hidden values and delete button.
     *
     * @param html_table $logo_table Table receiving the row.
     * @param ?string $class Row type (or null for the hidden template row).
     * @param array $logo Logo metadata and image data.
     * @param array $attrib Original form attributes (retained for compatibility).
     * @param array $row_attrib Extra row presentation attributes.
     * @return void
     */
    private function customised_logo_row(&$logo_table, $class, $logo, $attrib, $row_attrib = [])
    {
        $hidden_customisedlogotemplate = new html_hiddenfield(['name' => '_customisedlogotemplate_' . $logo['skin'] . '[]', 'value' => $logo['template']]);
        $hidden_customisedlogotype = new html_hiddenfield(['name' => '_customisedlogotype_' . $logo['skin'] . '[]', 'value' => $logo['type']]);
        $hidden_customisedlogoimage = new html_hiddenfield(['name' => '_customisedlogoimage_' . $logo['skin'] . '[]', 'value' => $logo['image']]);

        $row_attrib = !isset($class) ? array_merge($row_attrib, ['style' => 'display: none;']) : array_merge($row_attrib, ['class' => $class]);
        $logo_table->set_row_attribs($row_attrib);

        $logo_table->add(['class' => 'customised-logo-template'], ($logo['template'] == '*' ? rcmail::Q($this->plugin->gettext('customise-logo-template-all')) : $logo['template']));
        $logo_table->add(['class' => 'customised-logo-type'], rcmail::Q($this->plugin->gettext(array_flip($this->logo_types)[$logo['type']])));
        $logoimage = html::img([
            'src'     => $logo['image'] ?: $this->rcube->output->asset_url('program/resources/blank.gif'),
            'class'   => 'customised-logo-image-tag',
            'height'  => 36,
            'onerror' => "this.src = rcmail.assets_path('program/resources/blank.gif'); this.onerror = null",
        ]);
        $logo_table->add(['class' => 'customised-logo-image'], $logoimage);

        $del_button = $this->rcube->output->button(['command' => 'plugin.toolbox.delete_customised_logo', 'type' => 'link', 'class' => 'delete', 'label' => 'delete', 'content' => ' ', 'title' => 'delete', 'data-skin' => $logo['skin']]);
        $logo_table->add('control', $del_button . $hidden_customisedlogotemplate->show() . $hidden_customisedlogotype->show() . $hidden_customisedlogoimage->show());
    }

    /**
     * Enumerate allowed Roundcube skins and their display headers.
     *
     * @return array Skin identifier to rendered header.
     */
    private function get_skins(): array
    {
        $skins_allowed = $this->rcube->config->get('skins_allowed', []);
        $path  = RCUBE_INSTALL_PATH . 'skins';
        $skins = [];
        $out   = [];
        $dir   = opendir($path);

        if ($dir) {

            while (($file = readdir($dir)) !== false) {
                $filename = $path . \DIRECTORY_SEPARATOR . $file;
                if (!preg_match('/^\./', $file) && is_dir($filename) && is_readable($filename)) {
                    $skins[] = $file;
                }
            }
            closedir($dir);

            sort($skins);
            foreach ($skins as $skin) {
                if (in_array($skin, $skins_allowed)) {
                    $name = ucfirst($skin);
                    $meta = @json_decode(@file_get_contents(INSTALL_PATH . "skins" . \DIRECTORY_SEPARATOR . $skin . \DIRECTORY_SEPARATOR . "meta.json"), true);
                    if (is_array($meta) && $meta['name']) {
                        $name    = $meta['name'];
                        $author  = isset($meta['url']) ? html::a(['href' => $meta['url'], 'target' => '_blank'], rcube::Q($meta['author'])) : rcube::Q($meta['author']);
                    }
                    $thumbnail = html::img([
                        'src'     => $this->rcube->output->asset_url("skins/$skin/thumbnail.png"),
                        'class'   => 'skinthumb',
                        'alt'     => $skin,
                        'width'   => 32,
                        'height'  => 32,
                        'onerror' => "this.src = rcmail.assets_path('program/resources/blank.gif'); this.onerror = null",
                    ]);

                    $out[$skin] = html::label(['class' => 'tool-skin'],
                        html::span('skinitem', $thumbnail) .
                        html::span('skinitem', html::span('skinname', rcube::Q($name)) . html::br() . html::span('skinauthor', $author ? 'by ' . $author : ''))
                    );
                }
            }
        }

        return $out;
    }

    /**
     * Enumerate HTML template names in the selected skin.
     *
     * @param string $skin Roundcube skin identifier.
     * @return array Template base names.
     */
    private function get_templates($skin): array
    {
        $path  = RCUBE_INSTALL_PATH . 'skins' . \DIRECTORY_SEPARATOR . $skin . \DIRECTORY_SEPARATOR . 'templates';
        $templates = [];
        $dir   = opendir($path);

        if ($dir) {
            while (($file = readdir($dir)) !== false) {
                $filename = $path.'/'.$file;
                if (preg_match('/^[^\/]+\.html/', $file) && is_file($filename) && is_readable($filename)) {
                    $parts = pathinfo($filename);
                    $templates[] = $parts['filename'];
                }
            }
            closedir($dir);
        }

        return $templates;
    }
    /**
     * Apply domain and skin overrides after all modules have been loaded.
     *
     * @param callable $storage_supplier Lazy storage provider from Toolbox.
     * @return void
     */
    public function configure_runtime(callable $storage_supplier): void
    {
        $this->apply_config(
            $storage_supplier(),
            $this->rcube->config->get('skin'),
            $this->plugin->ID
        );
    }

    /**
     * Limit access to domain administrators without central Toolbox checks.
     *
     * @param object $storage Toolbox storage used for administrator lookup.
     * @return bool True for a domain administrator.
     */
    public function can_access_settings($storage): bool
    {
        return $storage->is_domain_admin($this->rcube->user->get_username());
    }

    /**
     * Render domain-level and per-skin settings using the shared renderer.
     * The previous Customise markup intentionally did not append form_end.
     *
     * @param object $storage Shared Toolbox storage backend.
     * @param array $attributes Original form-renderer attributes.
     * @param callable $fieldset Shared Toolbox fieldset renderer.
     * @return array Customise body and its existing form-wrapping policy.
     */
    public function render_section($storage, array $attributes, callable $fieldset): array
    {
        return [
            'content' => $this->render_form($storage, $attributes, $fieldset),
            'append_form_end' => false,
        ];
    }

    /**
     * Collect Customise settings without changing its per-skin database model.
     *
     * @param object $storage Active Toolbox storage backend.
     * @return array Prepared settings for the shared save dispatcher.
     */
    public function prepare_save($storage): ?array
    {
        return ['new_settings' => $this->prepare_settings($storage)];
    }

    /**
     * Load CodeMirror and the Customise JavaScript only on the edit screen.
     *
     * @return void
     */
    public function include_edit_assets(): void
    {
        $this->plugin->include_stylesheet('js/codemirror/lib/codemirror.css');
        $this->plugin->include_script('js/codemirror/lib/codemirror.js');
        $this->plugin->include_script('js/codemirror/addon/selection/active-line.js');
        $this->plugin->include_script('js/codemirror/mode/css/css.js');
        $this->plugin->include_script('js/toolbox.customise.js');
    }

}

// END OF FILE

