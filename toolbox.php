<?php

class toolbox extends rcube_plugin
{
    public $task = 'mail|settings|tasks|addressbook';

    private $storage;
    private $tools;
    private $rcube;
    private $sections = array();
    private $cur_section;
    private $user_prefs;
    private $skins;
    private $skin;
    private $skins_allowed = array();
    private $loglevel;
    private $logfile;

    public function init()
    {
        $this->rcube = rcube::get_instance();
        $this->load_config();
        $this->loglevel = $this->rcube->config->get('toolbox_loglevel', 1);
        if ($this->loglevel > 0) {
            $this->logfile = $this->rcube->config->get('toolbox_logfile', 'toolbox.log');
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: initialise plugin");
            }
        }
        $this->add_texts('localization/');
        $this->tools = $this->rcube->config->get('toolbox_tools');
        $this->skin = $this->rcube->config->get('skin');
        $this->skins_allowed = $this->rcube->config->get('skins_allowed');

        if (in_array('customise', $this->tools)) {

            // load user's preferences
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: load user's preferences");
            }
            $this->_load_prefs();

            // load customised skin configuration from config
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: skin: " . $this->user_prefs['skin']);
                rcube::write_log($this->logfile, "STEP in [function init]: load customised skin configuration from config");
            }
            $config = $this->rcube->config->get('toolbox_customise_skins', array());
            $config = isset($config[$this->user_prefs['skin']]) ? $config[$this->user_prefs['skin']] : (isset($config['default']) ? $config['default'] : array());

            // load customised skin configuration from database
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: load customised skin configuration from database");
            }
            $this->_init_storage();
            $customise = $this->storage->load_customised_config($this->rcube->user->get_username(), $this->user_prefs['skin']);

            // customise blank page
            $options = array('blankpage_type', 'blankpage_url', 'blankpage_image', 'blankpage_custom');
            if ($config['customise_blankpage'] !== false) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: 'customise blank page' database option selected: override config");
                }
                foreach ($options as $option) {
                    $config[$option] = $customise[$option];
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

            if (in_array($config['blankpage_type'], array('url', 'image', 'custom'))) {
                $parts = explode('@', $this->rcube->user->get_username());
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: customised blank page type: " . $config['blankpage_type']);
                }

                switch ($config['blankpage_type']) {
                    case "url":
                        if ($config['blankpage_url'] != '') {
                            $this->rcube->output->set_env('blankpage', $config['blankpage_url']);
                        }
                        break;
                    case "image":
                        if ($config['blankpage_image'] != '') {
                            $image = $config['blankpage_image'];
                            // we read the content of the file 'watermark.html' in the skin folder and change the url content with our cutomised image
                            if (file_exists(RCUBE_INSTALL_PATH . DIRECTORY_SEPARATOR . 'skins' . DIRECTORY_SEPARATOR . $this->skin . DIRECTORY_SEPARATOR . 'watermark.html')) {
                                $blankpage = file_get_contents(RCUBE_INSTALL_PATH . DIRECTORY_SEPARATOR . 'skins' . DIRECTORY_SEPARATOR . $this->skin . DIRECTORY_SEPARATOR . 'watermark.html');
                                if ($blankpage != '') {
                                    // we need a file in a folder named 'tmp' in plugin/toolbox (temp cannot be used for .htaccess limitations)
                                    // filename must contain a dot for .htaccess limitations
                                    // a file is needed since the 'blankpage' env variable needs a real file (loaded in apps.js)
                                    $tmp = RCUBE_INSTALL_PATH . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $this->ID . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'blankpage.' . $parts[1] . '.html';
                                    file_put_contents($tmp, preg_replace('!url\(.*?\)!U', "url(" . $config['blankpage_image'] . ")", $blankpage));
                                    $this->rcube->output->set_env('blankpage', '.' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $this->ID . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . basename($tmp));
                                }
                            }
                        }
                        break;
                    case "custom":
                        if ($config['blankpage_custom'] != '') {
                            // we need a file in a folder named 'tmp' in plugin/toolbox (temp cannot be used for .htaccess limitations)
                            // filename must contain a dot for .htaccess limitations
                            // a file is needed since the 'blankpage' env variable needs a real file (loaded in apps.js)
                            $tmp = RCUBE_INSTALL_PATH . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $this->ID . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'blankpage.' . $parts[1] . '.html';
                            file_put_contents($tmp, $config['blankpage_custom']);
                            $this->rcube->output->set_env('blankpage', '.' . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $this->ID . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . basename($tmp));
                        }
                        break;
                }
            }
            elseif ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: customised blank page type: Roundcube default");
            }

            // customise css
            if (($customise['customise_css'] !== false) && ($customise['additional_css'] != '')) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: 'customise additional css' database option selected: override config");
                }
                if ( ($pos = stripos($args['content'], '<script ')) || ($pos = stripos($args['content'], '</head>')) ) {
                    $args['content'] = substr_replace($args['content'], '<style>' . $customise['additional_css'] . '</style>', $pos, 0);
                }
            }
            elseif ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'customise additional css' database option not selected: loading from config");
                if (file_exists($config['additional_css'])) {
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function init]: valid customised additional css file defined, found and loaded: {$config['additional_css']}");
                    }
                    $this->rcube->output->include_css($config['additional_css']);
                }
                elseif ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: customised additional css file not found, empty or not defined: {$config['additional_css']}");
                }
            }

        } // end if customise

        if ($this->rcube->task == 'settings') {

            $this->include_stylesheet($this->local_skin_path() . '/tabstyles.css');

            foreach ($this->tools as $tool) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: load plugin tool {$tool}");
                }
                $this->sections[$tool] = array('id' => $tool, 'class' => $tool, 'section' => rcmail::Q($this->gettext($tool)));
            }
            $this->cur_section = rcube_utils::get_input_value('_section', rcube_utils::INPUT_GPC);

            // if not domain admin deactivate tool customise
            $this->_init_storage();
            if (in_array('customise', $this->tools)) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: initialise storage to check if domain admin (blank section)");
                }
                if (!$this->storage->is_domain_admin($this->rcube->user->get_username())) {
                    $key = array_search('customise', $this->tools);
                        if ($key !== false) {
                            array_splice($this->tools, $key, 1);
                        }
                }
            }

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: create toolbox tab in the list of settings");
            }
            $this->add_hook('settings_actions', array($this, 'settings_tab'));

            $this->register_action('plugin.toolbox', array($this, 'init_html'));
            $this->register_action('plugin.toolbox.edit', array($this, 'init_html'));
            $this->register_action('plugin.toolbox.check', array($this, 'check'));
            $this->register_action('plugin.toolbox.delete', array($this, 'delete'));
            $this->register_action('plugin.toolbox.toggle', array($this, 'toggle'));
            $this->register_action('plugin.toolbox.save', array($this, 'save'));

            if ($this->rcube->config->get('toolbox_vacation_jquery_calendar', false)) {
                $format = $this->rcube->config->get('toolbox_vacation_jquery_dateformat', 'mm/dd/yy');
                if ($this->rcube->output->type === "html")
                $this->rcube->output->add_script("calendar_format='" . $format . "';");
                $this->include_script('js/toolbox.calendar.js');
            }
        }

    }

    public function settings_tab($p)
    {
        $p['actions'][] = array('action' => 'plugin.toolbox', 'class' => 'toolbox', 'label' => 'toolbox.toolbox', 'title' => 'toolbox.toolbox-description', 'role' => 'button', 'aria-disabled' => 'false', 'tabindex' => '99');
        return $p;
    }

    public function init_html()
    {
        $this->rcube->output->set_pagetitle(rcmail::Q($this->gettext('toolbox')));
        $this->include_script('js/toolbox.js');
        $this->include_stylesheet($this->local_skin_path() . '/toolbox.css');

        if ($this->rcube->action == 'plugin.toolbox.edit') {

            // use jQuery for popup window
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init_html]: prepare html output for action edit");
                rcube::write_log($this->logfile, "STEP in [function init_html]: load jquery plugin");
            }
            $this->require_plugin('jqueryui');
            $this->rcube->output->include_script('list.js');
            $this->rcube->output->add_handler('toolbox', array($this, 'tool_render_form'));
            $this->rcube->output->add_handler('sectionname', array($this, 'tool_section_name'));

            // initialise html editor
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init_html]: initialise html editor");
            }
            $this->rcube->html_editor('toolbox');
            $this->rcube->output->add_script(sprintf("window.rcmail_editor_settings = %s", $this->_config_editor()), 'head');

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init_html]: selected tool: {$this->cur_section}");
            }
            $this->rcube->output->set_env('cur_section', $this->cur_section);

            // render template
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init_html]: render template for action {$this->rcube->action}");
            }
            $template = 'toolbox.tooledit';
            // load specific js and css before rendering
            switch($this->cur_section) {
                case 'customise':
                    $this->include_stylesheet('js/codemirror/lib/codemirror.css');
                    $this->include_script('js/codemirror/lib/codemirror.js');
                    $this->include_script('js/codemirror/addon/selection/active-line.js');
                    $this->include_script('js/codemirror/mode/css/css.js');
                    break;
            }
            $this->rcube->output->send($template);

        }
        else {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init_html]: prepare html output for the list of sections");
            }
            $this->rcube->output->add_handler('tbsectionslist', array($this, 'tool_section_list'));

            // render template
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init_html]: render template for action {$this->rcube->action}");
            }
            $this->rcube->output->send('toolbox.toolbox');
        }
    }

    public function tool_section_list($attrib)
    {
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function section_list]: prepare the list sections");
        }

        // add id to message list table if not specified
        if (!strlen($attrib['id'])) {
            $attrib['id'] = 'rcmsectionslist';
        }

        $sections = array();

        // if template overrides default array then rebuild the array in the new order
        if (isset($attrib['sections'])) {
            $new_sections = array();
            $keys = preg_split('/[\s,;]+/', str_replace(array("'", '"'), '', $attrib['sections']));
            foreach ($keys as $key) {
                $new_sections[] = $this->sections[$key];
            }
            $this->sections = $new_sections;
        }

        $data = $this->rcube->plugins->exec_hook('toolbox_sections_list', array('list' => $this->sections, 'cols' => array('section')));
        foreach ($data['list'] as $id => $block) {
            $sections[$id] = $block;
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function section_list]: add section {$block['id']}");
            }
        }

        // create HTML table
        $out = $this->rcube->table_output($attrib, $sections, $data['cols'], 'id');

        // set client env
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function section_list]: generate the list of sections");
        }
        $this->rcube->output->add_gui_object('sectionslist', $attrib['id']);
        $this->rcube->output->include_script('list.js');

        return $out;
    }

    public function tool_section_name()
    {
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function tool_section_name]: generate section title");
        }
        $data = $this->rcube->plugins->exec_hook('toolbox_section_name', array('section' => $this->cur_section, 'title' => $this->sections[$this->cur_section]['section']));
        return $data['title'];
    }

    public function tool_render_form($attrib)
    {

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function tool_render_form]: selected tool: {$this->cur_section}");
            rcube::write_log($this->logfile, "STEP in [function tool_render_form]: initialise storage for tool {$this->cur_section}");
        }
        $this->_init_storage();

        $parts = explode('@', $this->rcube->user->get_username());

        $form_content = '';

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function tool_render_form]: prepare form for tool {$this->cur_section}");
        }
        switch ($this->cur_section) {

            case 'customise':

                if ($this->storage->is_domain_admin($this->rcube->user->get_username())) {

                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function tool_render_form]: user is domain admin");
                    }

                    // load data
                    $selected = $this->storage->load_tool_data($this->rcube->user->get_username());

                    $form_content .= html::div(array('class' => 'tool-title'), rcmail::Q($this->gettext('customise-manage')) . ' ' . $parts[1]);

                    // purge trash and junk folders
                    $folders = array("trash", "junk");

                    $tooldata = array('name' => rcmail::Q($this->gettext('customise-purge')), 'class' => 'toolbox-purgetable', 'cols' => 2);

                    foreach ($folders as $folder) {

                        $field_name = '_domain' . $folder;
                        $field_id = 'rcmfd' . $field_name;

                        $select = new html_select(array('name' => $field_name, 'id' => $field_id));
                        $select->add(rcmail::Q($this->gettext('purge-always')), 0);
                        $select->add(rcmail::Q('1 ' . $this->gettext('purge-day')), 1);
                        $options = array('3', '7', '15', '30', '45', '60', '90', '120', '150', '180', '270', '360');
                        foreach($options as $option) {
                            $select->add(rcmail::Q($option . ' ' . $this->gettext('purge-days')), intval($option));
                        }

                        $tooldata['rows'][$folder] = array(
                            'title' => html::label($field_id, rcmail::Q($this->gettext('purge-'.$folder))),
                            'content' => $select->show($selected[$folder])
                        );

                    }

                    $form_content .= $this->_tool_render_fieldset($tooldata, 'purge');

                    // blank page settings
                    $form_content .= html::div(array('class' => 'tool-subtitle'), rcmail::Q($this->gettext('skin')));

                    // set settings for each skin
                    foreach ($this->_get_skins() as $skin => $header) {

                        // set field visibility
                        $row_attribs = array(
                            'blankpage' => array('style' => 'display: none;'),
                            'url' => array('style' => 'display: none;'),
                            'image' => array('style' => 'display: none;'),
                            'custom' => array('style' => 'display: none;'),
                            'css' => array('style' => 'display: none;')
                        );
                        if (isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin])) {
                            if ($selected['skins'][$skin]['customise_blankpage'] !== false) {
                                $row_attribs['blankpage'] = array();
                            }
                            if (isset($selected['skins'][$skin]['blankpage_type']) && in_array($selected['skins'][$skin]['blankpage_type'], array('url', 'image', 'custom'))) {
                                $row_attribs[$selected['skins'][$skin]['blankpage_type']] = array();
                            }
                            if ($selected['skins'][$skin]['customise_css'] !== false) {
                                $row_attribs['css'] = array();
                            }
                        }

                        // skin header
                        $form_content .= $header;

                        // blank page
                        $tooldata = array('name' => rcmail::Q($this->gettext('customise-blankpage-skin')), 'class' => 'toolbox-customisetable', 'cols' => 1);

                        $button_id = 'rcmfd_blankpageskin_selector_' . $skin;
                        $input_blankpageselector = new html_checkbox(array('name' => '_blankpageselector_' . $skin, 'id' => $button_id, 'value' => '1', 'class' => 'customise-blankpage-selector', 'title' => rcmail::Q($this->gettext('customise-blankpage'))));

                        $tooldata['rows']['skinblankpage'] = array(
                            'content' => $input_blankpageselector->show($selected['skins'][$skin]['customise_blankpage']) . html::label(array('for' => $button_id, 'class' => 'customise-blankpage-label'), rcmail::Q($this->gettext('customise-blankpage')))
                        );

                        // blank page type table
                        $blankpagetype_table = new html_table(array('id' => 'customise_blankpage_type_table_' . $skin, 'class' => 'customise-blankpage-type-table', 'cols' => 1));

                        // Roundcube default blank page
                        $button_id = 'rcmrb_blankpageskindefault_' . $skin;
                        $input_skindefault = new html_radiobutton(array('name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => '', 'class' => 'customise-blankpage-skin-selector'));

                        $blankpagetype_table->add(array('class' => 'blankpage-type-check'), $input_skindefault->show($selected['skins'][$skin]['blankpage_type']) . html::label($button_id, rcmail::Q($this->gettext('customise-blankpage-skin-default'))));

                        // blank page as url
                        $button_id = 'rcmrb_blankpageskinurl_' . $skin;
                        $input_skinurl = new html_radiobutton(array('name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'url', 'class' => 'customise-blankpage-skin-selector'));

                        $blankpagetype_table->add(array('class' => 'blankpage-type-check'), $input_skinurl->show($selected['skins'][$skin]['blankpage_type']) . html::label($button_id, rcmail::Q($this->gettext('customise-blankpage-skin-url'))));

                        $field_id = 'rcmfd_blankpageurl_' . $skin;
                        $input_blankpageurl = new html_inputfield(array('name' => '_blankpageurl_' . $skin, 'class' => 'tool-skin-blankpage-url', 'type' => 'url', 'id' => $field_id, 'title' => rcmail::Q($this->gettext('customise-blankpage-url')), 'placeholder' => rcmail::Q($this->gettext('customise-blankpage-url'))));

                        $blankpagetype_table->set_row_attribs($row_attribs['url']);
                        $blankpagetype_table->add(array('class' => 'blankpage-type-content'), $input_blankpageurl->show($selected['skins'][$skin]['blankpage_url']) . html::span(array('id' => $button_id . '_content'), ''));

                        // blank page with custom image
                        $button_id = 'rcmrb_blankpageskinimage_' . $skin;
                        $input_skinimage = new html_radiobutton(array('name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'image', 'class' => 'customise-blankpage-skin-selector'));

                        $blankpagetype_table->add(array('class' => 'blankpage-type-check'), $input_skinimage->show($selected['skins'][$skin]['blankpage_type']) . html::label($button_id, rcmail::Q($this->gettext('customise-blankpage-skin-image'))));

                        $image_id = 'rcmbtn_modifyblankpageimage_' . $skin;
                        $blankpageimage = html::img(array(
                            'id'  => $image_id,
                            'src'     => (isset($selected['skins'][$skin]['blankpage_image']) && ($selected['skins'][$skin]['blankpage_image'] != '') ? $selected['skins'][$skin]['blankpage_image'] : 'program/resources/blank.gif'),
                            'class'   => 'blankpage-image',
                            'width'   => 256,
                            'onerror' => "this.src = rcmail.assets_path('program/resources/blank.gif'); this.onerror = null",
                        ));

                        $input_blankpageimage = new html_inputfield(array('id' => '_blankpageimage_' . $skin, 'type' => 'file', 'name' => '_blankpageimage_' . $skin, 'class' => 'blankpage-image-upload', 'data-image' => $image_id));
                        $hidden_blankpageimagecontrol = new html_hiddenfield(array('id' => '_blankpageimage_' . $skin . '_control', 'name' => '_blankpageimage_' . $skin . '_control', 'value' => (isset($selected['skins'][$skin]['blankpage_image']) && ($selected['skins'][$skin]['blankpage_image'] != '') ? '1' : '0')));

                        $field_id = 'rcmbtn_deleteblankpageimage_' . $skin;
                        $button_deleteblankpageimage = $this->rcube->output->button(array('id' => $field_id, 'command' => 'plugin.toolbox.reset_image', 'prop' => '#'.$image_id, 'type' => 'link', 'class' => 'blankpage-image-delete-button', 'title' => 'delete', 'label' => 'delete', 'content' => ' ', 'data-image' => '_blankpageimage_' . $skin . '_control'));

                        $blankpagewrapper =
                            html::label(array('class' => 'blankpage-item-image blankpage-drop-target'),
                                $blankpageimage .
                                $input_blankpageimage->show()
                            ) .
                            html::span(array('class' => 'blankpage-image-delete', 'title' => rcmail::Q($this->gettext('delete'))), $button_deleteblankpageimage);

                        $blankpagetype_table->set_row_attribs($row_attribs['image']);
                        $blankpagetype_table->add(array('class' => 'blankpage-type-content'), $blankpagewrapper . html::span(array('id' => $button_id . '_content'), '') . $hidden_blankpageimagecontrol->show());

                        // custom blank page
                        $button_id = 'rcmrb_blankpageskincustom_' . $skin;
                        $input_skincustom = new html_radiobutton(array('name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'custom', 'class' => 'customise-blankpage-skin-selector'));

                        $blankpagetype_table->add(array('class' => 'blankpage-type-check'), $input_skincustom->show($selected['skins'][$skin]['blankpage_type']) . html::label($button_id, rcmail::Q($this->gettext('customise-blankpage-skin-custom'))));

                        $field_id = 'rcmfd_blankpagecustom_' . $skin;
                        $input_blankpagecustom = new html_textarea(array('name' => '_blankpagecustom_' . $skin, 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 92, 'class' => 'mce_editor'));

                        $blankpagetype_table->set_row_attribs($row_attribs['custom']);
                        $blankpagetype_table->add(array('class' => 'blankpage-type-content'), $input_blankpagecustom->show($selected['skins'][$skin]['blankpage_custom']) . html::span(array('id' => $button_id . '_content'), ''));

                        // show blank page type table
                        $tooldata['rows']['blankpage'] = array(
                            'content' => $blankpagetype_table->show(),
                            'row_attribs' => $row_attribs['blankpage']
                        );

                        $form_content .= $this->_tool_render_fieldset($tooldata, 'blankpage');

                        // css
                        $tooldata = array('name' => 'CSS', 'class' => 'toolbox-customisetable', 'cols' => 1);

                        $button_id = 'rcmfd_additionalcss_selector_' . $skin;
                        $input_additionalcssselector = new html_checkbox(array('name' => '_additionalcssselector_' . $skin, 'id' => $button_id, 'value' => '1', 'class' => 'customise-additional-css-selector', 'title' => rcmail::Q($this->gettext('customise-additional-css'))));

                        $tooldata['rows']['skinadditionalcss'] = array(
                            'content' => $input_additionalcssselector->show($selected['skins'][$skin]['customise_css']) . html::label(array('for' => $button_id, 'class' => 'customise-additional-css-label'), rcmail::Q($this->gettext('customise-additional-css')))
                        );

                        $field_id = 'rcmfd_additionalcss_' . $skin;
                        $input_additionalcss = new html_textarea(array('name' => '_additionalcss_' . $skin, 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 92, 'class' => 'tool-skin-additional-css'));

                        $tooldata['rows']['additionalcss'] = array(
                            'content' => $input_additionalcss->show($selected['skins'][$skin]['additional_css']) . html::span(array('id' => $button_id . '_content'), ''),
                            'row_attribs' => $row_attribs['css']
                        );

                        $form_content .= $this->_tool_render_fieldset($tooldata, 'additionalcss');

                    }

                }

                break;

            case 'aliases':

                // Add JS labels if needed
                $this->rcube->output->add_label(
                    'toolbox.aliases-aliasupdated',
                    'toolbox.aliases-novalidalias',
                    'toolbox.aliases-aliasexists',
                    'toolbox.aliases-aliasdeleteconfirm',
                    'toolbox.aliases-deleted'
                    );
                // define table sorting
                $sorts = array(
                    '#alias-addresses-table' => array(0, 'true')
                    );
                $this->rcube->output->set_env('table_sort', $sorts);

                $tooldata = array('name' => rcmail::Q($this->gettext('aliases-manage')), 'class' => 'toolbox-aliasestable', 'cols' => 2);

                $settings = $this->storage->load_tool_data($this->rcube->user->get_username());
                $aliases = array();
                if (!empty($settings['aliases'])) {
                    foreach ($settings['aliases'] as $alias) {
                        $active = $alias['active'];
                        $elements = explode("@", trim($alias['address']));
                        if ($elements[0] != "") {
                            $aliases[] = array("name" => $elements[0], "domain" => $elements[1], "active" => $active);
                        }
                    }
                }
                sort($aliases);

                $field_id = 'rcmfd_newaliasname';
                $input_newalias = new html_inputfield(array('name' => '_newaliasname', 'id' => $field_id, 'title' => rcmail::Q($this->gettext('aliases-aliasname')), 'size' => 80, 'placeholder' => rcmail::Q($this->gettext('aliases-aliasname'))));
                $field_id = 'rcmfd_newaliasactive';
                $input_newactive = new html_select(array('name' => '_newaliasactive', 'id' => $field_id));
                $input_newactive->add(array(rcmail::Q($this->gettext('toolbox-enabled')),rcmail::Q($this->gettext('toolbox-disabled'))), array('true','false'));

                $field_id = 'rcmbtn_addalias';
                $button_addalias = $this->rcube->output->button(array('id' => $field_id, 'command' => 'plugin.toolbox.add_alias', 'type' => 'input', 'class' => 'button', 'label' => 'toolbox.aliases-addaddress'));

                $tooldata['intro'] = html::div('address-input grouped', $input_newalias->show() . $input_newactive->show('enabled') . $button_addalias);

                $table = new html_table(array('class' => 'addressprefstable propform', 'cols' => 2));

                $address_table = new html_table(array('id' => 'alias-addresses-table', 'class' => 'records-table sortable-table alias-addresses-table fixedheader', 'cellspacing' => '0', 'cols' => 3));
                $address_table->add_header('email', $this->rcube->output->button(array('command' => 'plugin.toolbox.table_sort', 'prop' => '#alias-addresses-table', 'type' => 'link', 'label' => 'toolbox.aliases-aliasname', 'title' => 'sortby')));
                $address_table->add_header('status', $this->rcube->output->button(array('command' => 'plugin.toolbox.table_sort', 'prop' => '#alias-addresses-table', 'type' => 'link', 'label' => 'toolbox.toolbox-enabled', 'title' => 'sortby')));
                $address_table->add_header('control', '&nbsp;');

                $this->rcube->output->set_env('alias_addresses_count', !empty($aliases) ? count($aliases) : 0);
                foreach ($aliases as $alias) {
                    if ($alias['name'] != '') {
                        $this->_alias_address_row($address_table, 'alias', $alias, $attrib);
                    }
                }

                // add no address and new address rows at the end
                if (!empty($aliases)) {
                    $noaddresses = 'display: none;';
                }

                $address_table->set_row_attribs(array('class' => 'noaddress', 'style' => $noaddresses));
                $address_table->add(array('colspan' => '3'), rcube_utils::rep_specialchars_output(rcmail::Q($this->gettext('aliases-noaliases'))));

                $this->_alias_address_row($address_table, null, null, $attrib, array('class' => 'newaddress'));

                $table->add(array('colspan' => 2, 'class' => 'scroller'), html::div(array('id' => 'alias-addresses-cont'), $address_table->show()));

                $tooldata['content'] = $table->show();

                $form_content .= $this->_tool_render_fieldset($tooldata, 'main');

                break;

            case 'forward':

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
                $sorts = array(
                    '#forward-addresses-table' => array(0, 'true')
                    );
                $this->rcube->output->set_env('table_sort', $sorts);

                $tooldata = array('name' => rcmail::Q($this->gettext('forward-manage')), 'class' => 'toolbox-forwardtable', 'cols' => 2);

                $settings = $this->storage->load_tool_data($this->rcube->user->get_username());
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
                $input_forwardaddress = new html_inputfield(array('name' => '_forwardaddress', 'id' => $field_id, 'title' => rcmail::Q($this->gettext('forward-address')), 'size' => 80, 'placeholder' => rcmail::Q($this->gettext('forward-address'))));

                $field_id = 'rcmbtn_add_address';
                $button_addaddress = $this->rcube->output->button(array('id' => $field_id, 'command' => 'plugin.toolbox.add_forward_address', 'type' => 'input', 'class' => 'button', 'label' => 'toolbox.forward-addaddress'));

                $tooldata['intro'] = html::div('address-input grouped', $input_forwardaddress->show() . $button_addaddress);

                $delete_all = $this->rcube->output->button(array('class' => 'delete-all', 'command' => 'plugin.toolbox.delete_all_addresses', 'type' => 'link', 'label' => 'toolbox.toolbox-deleteall', 'title' => 'toolbox.forward-deletealladdresses'));

                $table = new html_table(array('class' => 'addressprefstable propform', 'cols' => 2));
                $table->add(array('colspan' => 2, 'id' => 'listcontrols'), $delete_all);

                $address_table = new html_table(array('id' => 'forward-addresses-table', 'class' => 'records-table sortable-table forward-addresses-table fixedheader', 'cellspacing' => '0', 'cols' => 2));
                $address_table->add_header('email', $this->rcube->output->button(array('command' => 'plugin.toolbox.table_sort', 'prop' => '#forward-addresses-table', 'type' => 'link', 'label' => 'toolbox.toolbox-addresses', 'title' => 'sortby')));
                $address_table->add_header('control', '&nbsp;');

                $this->rcube->output->set_env('forward_addresses_count', !empty($addresses) ? count($addresses) : 0);
                foreach ($addresses as $address) {
                    if ($address != '') {
                        $this->_forward_address_row($address_table, 'forward', $address, $attrib);
                    }
                }

                // add no address and new address rows at the end
                if (!empty($addresses)) {
                    $noaddresses = 'display: none;';
                }

                $address_table->set_row_attribs(array('class' => 'noaddress', 'style' => $noaddresses));
                $address_table->add(array('colspan' => '2'), rcube_utils::rep_specialchars_output(rcmail::Q($this->gettext('forward-noaddress'))));

                $this->_forward_address_row($address_table, null, null, $attrib, array('class' => 'newaddress'));

                $table->add(array('colspan' => 2, 'class' => 'scroller'), html::div(array('id' => 'forward-addresses-cont'), $address_table->show()));

                $field_id = 'rcmfd_keepcopies';
                $input_keepcopies = new html_checkbox(array('name' => '_forwardkeepcopies', 'id' => $field_id, 'value' => '1'));

                $tooldata['rows']['keepcopies'] = array(
                    'title' => html::label($field_id, rcmail::Q($this->gettext('forward-keepcopies'))),
                    'content' => $input_keepcopies->show($keepcopies)
                );

                $tooldata['content'] = $table->show();

                $form_content .= $this->_tool_render_fieldset($tooldata, 'main');

                break;

            case 'vacation':

                // Add JS labels if needed
                $this->rcube->output->add_label(
                    'editorwarning'
                    );

                $tooldata = array('name' => rcmail::Q($this->gettext('vacation-manage')), 'class' => 'toolbox-vacationtable', 'cols' => 2);

                $selected = $this->storage->load_tool_data($this->rcube->user->get_username());

                $field_id = 'rcmfd_vacationactive';
                $input_vacationactive = new html_checkbox(array('name' => '_vacationactive', 'id' => $field_id, 'value' => '1'));

                $tooldata['rows']['vacationactive'] = array(
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-active'))),
                    'content' => $input_vacationactive->show($selected['active'])
                );

                $field_id = 'rcmfd_vacationactivefrom';
                $input_vacationactivefrom = new html_inputfield(array('name' => '_vacationactivefrom', 'id' => $field_id, 'value' => ''));

                $tooldata['rows']['vacationactivefrom'] = array(
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-activefrom'))),
                    'content' => $input_vacationactivefrom->show($selected['activefrom'])
                );

                $field_id = 'rcmfd_vacationactiveuntil';
                $input_vacationactiveuntil = new html_inputfield(array('name' => '_vacationactiveuntil', 'id' => $field_id, 'value' => ''));

                $tooldata['rows']['vacationactiveuntil'] = array(
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-activeuntil'))),
                    'content' => $input_vacationactiveuntil->show($selected['activeuntil'])
                );

                $field_id = 'rcmfd_vacationintervaltime';
                $input_vacationintervaltime = new html_select(array('name' => '_vacationintervaltime', 'id' => $field_id));

                $options = $this->rcube->config->get('toolbox_vacation_interval_time');
                foreach($options as $name => $option) {
                    $input_vacationintervaltime->add(rcmail::Q($this->gettext('vacation-'.$name)), intval($option));
                }

                $tooldata['rows']['vacationintervaltime'] = array(
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-intervaltime'))),
                    'content' => $input_vacationintervaltime->show($selected['interval_time'])
                );

                $field_id = 'rcmfd_vacationsubject';
                $input_vacationsubject = new html_inputfield(array('name' => '_vacationsubject', 'id' => $field_id, 'value' => '', 'size' => 95));

                $tooldata['rows']['vacationsubject'] = array(
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-subject'))),
                    'content' => $input_vacationsubject->show($selected['subject'])
                );

                $field_id = 'rcmfd_vacationhtmleditor';
                $input_vacationhtmleditor = new html_checkbox(array('name' => '_vacationhtmleditor', 'id' => $field_id, 'value' => '1'));

                $tooldata['rows']['vactionhtmleditor'] = array(
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-htmleditor'))),
                    'content' => $input_vacationhtmleditor->show($this->user_prefs['toolbox_vacation_html_editor'])
                );

                $field_id = 'rcmfd_vacationbody';
                $input_vacationbody = new html_textarea(array('name' => '_vacationbody', 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 70, 'class' => $this->user_prefs['toolbox_vacation_html_editor'] ? 'mce_editor' : ''));

                $tooldata['rows']['vacationbody'] = array(
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-body'))),
                    'content' => $input_vacationbody->show($selected['body'])
                );

                $form_content .= $this->_tool_render_fieldset($tooldata, 'main');

                break;

            case 'purge':

                $folders = array("trash", "junk");

                $tooldata = array('name' => rcmail::Q($this->gettext('purge-manage')), 'class' => 'toolbox-purgetable', 'cols' => 2);

                foreach ($folders as $folder) {

                    $field_name = '_user' . $folder;
                    $field_id = 'rcmfd' . $field_name;

                    switch ($selected[$folder]) {
                        case 0:
                            $domainoption = rcmail::Q($this->gettext('purge-always'));
                            break;
                        case 1:
                            $domainoption = rcmail::Q('1 ' . $this->gettext('purge-day'));
                            break;
                        default:
                            $domainoption = rcmail::Q($selected[$folder] . ' ' . $this->gettext('purge-days'));
                            break;
                    }
                    $domainoption .= ' (' . rcmail::Q($this->gettext('purge-domainvalue')) . ')';

                    $select = new html_select(array('name' => $field_name, 'id' => $field_id));
                    $select->add($domainoption, 'NULL');
                    $select->add('──────────', NULL, array('disabled' => 'disabled'));
                    $select->add(rcmail::Q($this->gettext('purge-always')), '0');
                    $select->add(rcmail::Q('1 ' . $this->gettext('purge-day')), '1');
                    $options = array('3', '7', '15', '30', '45', '60', '90', '120', '150', '180', '270', '360');
                    foreach($options as $option) {
                        $select->add(rcmail::Q($option . ' ' . $this->gettext('purge-days')), $option);
                    }

                    $tooldata['rows'][$folder] = array(
                        'title' => html::label($field_id, rcmail::Q($this->gettext('purge-'.$folder))),
                        'content' => $select->show($this->user_prefs['toolbox_purge_'.$folder])
                    );

                }

                $form_content .= $this->_tool_render_fieldset($tooldata, 'main');

                break;

        }

        // define input error class
        $this->rcube->output->set_env('toolbox_input_error_class', $attrib['input_error_class'] ?: 'error');

        unset($attrib['form']);
        list($form_start, $form_end) = get_form_tags($attrib + array('enctype' => 'multipart/form-data'), 'plugin.toolbox.save', null, array('name' => '_section', 'value' => $this->cur_section));

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function tool_render_form]: render form for tool {$this->cur_section}");
        }
        return $this->cur_section != 'customise' ? $form_start . $form_content . $form_end : $form_start . $form_content;

    }

    public function toggle()
    {
        $rcmail = rcmail::get_instance();

        $this->cur_section = rcube_utils::get_input_value('_section', rcube_utils::INPUT_POST, true);

        $data = array('section' => $this->cur_section);
        $error = '';

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function toggle: selected tool: {$this->cur_section}");
        }
        switch ($this->cur_section) {

            case 'aliases':

                $data['new_settings']['aliasname'] = mb_convert_case(rcube_utils::get_input_value('_aliasname', rcube_utils::INPUT_POST, true), MB_CASE_LOWER, RCUBE_CHARSET);

                $this->api->output->add_label('toolbox.aliases-aliasupdated');

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function toggle]: initialise storage for tool {$this->cur_section}");
                }
                $this->_init_storage();

                if (!$this->storage->toggle_tool_data($this->rcube->user->get_username(), $data)) {
                    $error = rcmail::Q($this->gettext('aliases-aliasupdatederror'));
                }

                $rcmail->output->command('plugin.toolbox.check_toggle', $error);

                break;

        }

    }

    public function check()
    {
        $rcmail = rcmail::get_instance();

        $this->cur_section = rcube_utils::get_input_value('_section', rcube_utils::INPUT_POST);

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function check]: selected tool: {$this->cur_section}");
        }
        switch ($this->cur_section) {

            case 'aliases':

                $new_alias = rcube_utils::get_input_value('_newaliasname', rcube_utils::INPUT_POST, true);

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function check]: initialise storage for tool {$this->cur_section}");
                }
                $this->_init_storage();

                $error = '';

                // check alias existence in domain
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function check]: check alias existence in domain");
                }
                $selected = $this->storage->load_tool_data($this->rcube->user->get_username(), 'allaliases');
                foreach ($selected['aliases'] as $alias) {
                    $element = explode('@', trim($alias['address']));
                    if (($error == '') && ($element[0] == $new_alias)) {
                        $error = rcmail::Q($this->gettext('aliases-aliasexistsindomain'));
                    }
                }

                // check wrong chars in alias
                if (($error == '') && !preg_match("/^[_a-z0-9-]+(\.[_a-z0-9-]+)*/", mb_convert_case($new_alias, MB_CASE_LOWER, RCUBE_CHARSET))) {
                    $error = rcmail::Q($this->gettext('aliases-aliasnameerror'));
                }

                $rcmail->output->command('plugin.toolbox.check_alias', $error);

                break;

        }

    }

    public function delete()
    {
        $rcmail = rcmail::get_instance();

        $this->cur_section = rcube_utils::get_input_value('_section', rcube_utils::INPUT_POST, true);

        $data = array('section' => $this->cur_section);
        $error = '';

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function delete: selected tool: {$this->cur_section}");
        }
        switch ($this->cur_section) {

            case 'aliases':

                $data['aliasname'] = mb_convert_case(rcube_utils::get_input_value('_aliasname', rcube_utils::INPUT_POST, true), MB_CASE_LOWER, RCUBE_CHARSET);

                $this->api->output->add_label('toolbox.aliases-aliasdeleted');

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function delete]: initialise storage for tool {$this->cur_section}");
                }
                $this->_init_storage();

                if (!$this->storage->delete_tool_data($this->rcube->user->get_username(), $data)) {
                    $error = rcmail::Q($this->gettext('aliases-aliasdeletederror'));
                }

                $rcmail->output->command('plugin.toolbox.check_delete', $error);

                break;

        }

    }

    public function save()
    {

        $message_success = rcmail::Q($this->gettext('toolbox-datasuccessfullysaved'));
        $message_error = rcmail::Q($this->gettext('toolbox-datasaveerror'));

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function save]: start save action");
            rcube::write_log($this->logfile, "STEP in [function save]: initialise storage for tool {$this->cur_section}");
        }
        $this->_init_storage();

        $new_settings = array();

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function save]: selected tool: {$this->cur_section}");
        }
        switch ($this->cur_section) {

            case 'customise':

                if ($this->storage->is_domain_admin($this->rcube->user->get_username())) {

                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function save]: prepare domain settings");
                    }
                    $new_settings['domain']['purge_trash'] = rcube_utils::get_input_value('_domaintrash', rcube_utils::INPUT_POST) ?: 0;
                    $new_settings['domain']['purge_junk'] = rcube_utils::get_input_value('_domainjunk', rcube_utils::INPUT_POST) ?: 0;

                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function save]: prepare skin settings");
                    }
                    foreach ($this->_get_skins() as $skin => $header) {
                        $new_settings['skins'][$skin]['customise_blankpage'] = rcube_utils::get_input_value('_blankpageselector_'.$skin, rcube_utils::INPUT_POST) ?: false;
                        $new_settings['skins'][$skin]['blankpage_type'] = rcube_utils::get_input_value('_blankpagetype_'.$skin, rcube_utils::INPUT_POST) ?: null;
                        $new_settings['skins'][$skin]['blankpage_url'] = rcube_utils::get_input_value('_blankpageurl_' . $skin, rcube_utils::INPUT_POST) ?: null;
                        $allowed_types = array(
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
                            'image/ico'
                            );
                        $base64 = null;
                        if ($filepath = $_FILES['_blankpageimage_' . $skin]['tmp_name']) {
                            $filetype = $_FILES['_blankpageimage_' . $skin]['type'];
                            if (in_array($filetype, $allowed_types)) {
                                $filecont = file_get_contents($filepath);
                                $base64 = 'data:' . $filetype . ';base64,' . base64_encode($filecont);
                            }
                        }
                        $new_settings['skins'][$skin]['blankpage_image'] = $base64 ?: null;
                        $new_settings['skins'][$skin]['blankpage_image_control'] = rcube_utils::get_input_value('_blankpageimage_' . $skin . '_control', rcube_utils::INPUT_POST);
                        $new_settings['skins'][$skin]['blankpage_custom'] = $_POST['_blankpagecustom_' . $skin] ?: null;
                        $new_settings['skins'][$skin]['customise_css'] = rcube_utils::get_input_value('_additionalcssselector_' . $skin, rcube_utils::INPUT_POST) ?: false;
                        $new_settings['skins'][$skin]['additional_css'] = rcube_utils::get_input_value('_additionalcss_' . $skin, rcube_utils::INPUT_POST) ?: null;
                    }

                }

                break;

            case 'aliases':

                // save alias
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: save alias");
                }
                $new_settings['main']['aliasname'] = mb_convert_case(trim(rcube_utils::get_input_value('_newaliasname', rcube_utils::INPUT_POST)), MB_CASE_LOWER, RCUBE_CHARSET);
                $new_settings['main']['active'] = rcube_utils::get_input_value('_newaliasactive', rcube_utils::INPUT_POST);
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: new alias name = '{$new_settings['main']['aliasname']}', new alias active = '{$new_settings['main']['active']}'");
                }

                $message_success = rcmail::Q($this->gettext('aliases-aliascreated'));
                $message_error = rcmail::Q($this->gettext('aliases-aliascreatederror'));

                break;

            case 'forward':

                // save forward addresses
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: save forward addresses");
                }
                $addresses = rcube_utils::get_input_value('_forwardaddresses', rcube_utils::INPUT_POST);
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: new forward addresses = [" . implode(', ', $addresses) . "]");
                }

                foreach ($addresses as $ids => $address) {
                    $new_settings['main']['addresses'][] = array('value' => mb_convert_case(trim($address), MB_CASE_LOWER, RCUBE_CHARSET));
                }

                $new_settings['main']['keepcopies'] = rcube_utils::get_input_value('_forwardkeepcopies', rcube_utils::INPUT_POST) ?: false;

                break;

            case 'purge':

                // save user's preferences
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: save user's preferences");
                }
                $this->user_prefs['toolbox_purge_trash'] = rcube_utils::get_input_value('_usertrash', rcube_utils::INPUT_POST);
                $this->user_prefs['toolbox_purge_junk'] = rcube_utils::get_input_value('_userjunk', rcube_utils::INPUT_POST);
                if (!$this->rcube->user->save_prefs($this->user_prefs)) {
                    $this->rcube->display_server_error('errorsaving');
                    return;
                }
                break;

            case 'vacation':

                // save user's preferences
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: save user's preferences");
                }
                $this->user_prefs['toolbox_vacation_html_editor'] = rcube_utils::get_input_value('_vacationhtmleditor', rcube_utils::INPUT_POST);
                if (!$this->rcube->user->save_prefs($this->user_prefs)) {
                    $this->rcube->display_server_error('errorsaving');
                    return;
                }

                // save vacation settings
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: prepare vacation settings");
                }
                $new_settings['main']['active'] = rcube_utils::get_input_value('_vacationactive', rcube_utils::INPUT_POST) ?: false;
                $new_settings['main']['activefrom'] = rcube_utils::get_input_value('_vacationactivefrom', rcube_utils::INPUT_POST) ?: date("Y-m-d H:i:s");
                $new_settings['main']['activeuntil'] = rcube_utils::get_input_value('_vacationactiveuntil', rcube_utils::INPUT_POST) ?: date('"Y-m-d H:i:s"', strtotime("+1 week"));
                $new_settings['main']['interval_time'] = rcube_utils::get_input_value('_vacationintervaltime', rcube_utils::INPUT_POST) ?: $this->rcube->config->get('toolbox_vacation_interval_time')['replyonce'];
                $new_settings['main']['subject'] = rcube_utils::get_input_value('_vacationsubject', rcube_utils::INPUT_POST) ?: $this->rcube->config->get('toolbox_vacation_subject');
                $new_settings['main']['body'] = rcube_utils::get_input_value('_vacationbody', rcube_utils::INPUT_POST) ?: $this->rcube->config->get('toolbox_vacation_body');

                break;

        }

        $data = $this->rcube->plugins->exec_hook('toolbox_save', array('section' => $this->cur_section, 'new_settings' => $new_settings));

        if (!$data['abort']) {
            // save settings
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function save]: send data to storage");
            }
            if ($this->storage->save_tool_data($this->rcube->user->get_username(), $data)) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: data successfully stored");
                }
                $this->rcube->output->command('display_message', $message_success, 'confirmation');
            }
            else {
                if ($this->loglevel > 0) {
                    rcube::write_log($this->logfile, "ERROR in [function save]: data not stored");
                }
                $this->rcube->output->command('display_message', $message_error, 'error');
            }
        }
        else {
            if ($this->loglevel > 0) {
                rcube::write_log($this->logfile, "ERROR in [function save]: data not stored");
            }
            $this->rcube->output->command('display_message', $data['message'] ? $data['message'] : $message_error, 'error');
        }

        // go to next step
        $this->rcube->overwrite_action('plugin.toolbox.edit');
        $this->init_html();
    }

    private function _tool_render_fieldset($tabledata, $class)
    {
        $out = $tabledata['intro'];

        if (!empty($tabledata['rows'])) {
            $table = new html_table(array('class' => 'propform ' . $tabledata['class'], 'cols' => $tabledata['cols']));
            foreach ($tabledata['rows'] as $row) {
                if (isset($row['row_attribs'])) {
                    $table->set_row_attribs($row['row_attribs']);
                }
                if (isset($row['title'])) {
                    $table->add('title', $row['title']);
                }
                $table->add($row['content_attribs'], $row['content']);
                if (isset($row['help'])) {
                    $table->add('help', $row['help']);
                }
            }
            $out .= $table->show();
        }

        if (!empty($tabledata['content'])) {
            $out .= $tabledata['content'];
        }

        return !empty($out) ? html::tag('fieldset', $class, ($tabledata['name'] != '' ? html::tag('legend', null, $tabledata['name']) : '') . $out) : '';
    }

    private function _alias_address_row(&$address_table, $class, $alias, $attrib, $row_attrib = array())
    {
        $hidden_name = new html_hiddenfield(array('name' => '_aliasname[]', 'value' => $alias['name']));

        $row_attrib = !isset($class) ? array_merge($row_attrib, array('style' => 'display: none;')) : array_merge($row_attrib, array('class' => $class));
        $address_table->set_row_attribs($row_attrib);

        $button_type = $alias['active'] !== false ? 'enabled' : 'disabled';
        $address_table->add(array('class' => 'email ' . $button_type), $alias['name']);

        $toggle = $alias['active'] !== false ? rcmail::Q($this->gettext('toolbox-disable')) : rcmail::Q($this->gettext('toolbox-enable'));
        $enable_button = $this->rcube->output->button(array('command' => 'plugin.toolbox.toggle_alias', 'type' => 'link', 'class' => $button_type, 'label' => 'toolbox.toolbox-'.$button_type, 'content' => ' ', 'title' => 'toolbox.toolbox-'.$button_type));
        $input_active = new html_checkbox(array('name' => '_aliasactive[]', 'value' => '1', 'style' => 'display: none;'));
        $address_table->add('status', $enable_button . $input_active->show($alias['active']));

        $del_button = $this->rcube->output->button(array('command' => 'plugin.toolbox.delete_alias', 'type' => 'link', 'class' => 'delete', 'label' => 'delete', 'content' => ' ', 'title' => 'delete'));
        $address_table->add('control', $del_button . $hidden_name->show());
    }

    private function _forward_address_row(&$address_table, $class, $value, $attrib, $row_attrib = array())
    {
        $hidden_field = new html_hiddenfield(array('name' => '_forwardaddresses[]', 'value' => $value));

        $row_attrib = !isset($class) ? array_merge($row_attrib, array('style' => 'display: none;')) : array_merge($row_attrib, array('class' => $class));
        $address_table->set_row_attribs($row_attrib);

        $address_table->add(array('class' => 'email'), $value);

        $del_button = $this->rcube->output->button(array('command' => 'plugin.toolbox.delete_forward_address', 'type' => 'link', 'class' => 'delete', 'label' => 'delete', 'content' => ' ', 'title' => 'delete'));
        $address_table->add('control', $del_button . $hidden_field->show());
    }

    private function _load_prefs()
    {
        $this->user_prefs = $this->rcube->user->get_prefs();

        isset($this->user_prefs['toolbox_purge_trash']) || $this->user_prefs['toolbox_purge_trash'] = 'NULL';
        isset($this->user_prefs['toolbox_purge_junk']) || $this->user_prefs['toolbox_purge_junk'] = 'NULL';
        isset($this->user_prefs['toolbox_vacation_html_editor']) || $this->user_prefs['toolbox_vacation_html_editor'] = false;
        isset($this->user_prefs['toolbox_safelogin_history']) || $this->user_prefs['toolbox_safelogin_history'] = true;
        isset($this->user_prefs['skin']) && in_array($this->user_prefs['skin'], $this->_skins_allowed) || $this->user_prefs['skin'] = $this->skin;
    }

    private function _config_editor($mode = '')
    {
        switch ($mode) {
            // default: full configuration
            default:
                $config = json_encode(array(
                    'plugins' => 'advlist anchor autolink autoresize charmap code contextmenu colorpicker fullscreen help hr image imagetools importcss link lists nonbreaking paste preview searchreplace tabfocus table textcolor visualchars',
                    'toolbar' => array(
                        'cut copy paste | bold italic underline alignleft aligncenter alignright alignjustify | outdent indent | visualchars charmap | link unlink | searchreplace code help',
                        'fullscreen preview | fontselect fontsizeselect forecolor backcolor | image table | hr numlist bullist nonbreaking'
                    ),
                    'menubar' => 'edit insert view format table tools',
                    'autoresize_max_height' => '500',
                    'paste_data_images' => true
                ));
            break;
        }
    return $config;
    }

    private function _minify_html($html)
    {

        $search = array(
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        );

        $replace = array(
            '>',
            '<',
            '\\1',
            ''
        );

        return preg_replace($search, $replace, $html);
    }

    private function _get_skins()
    {
        $path  = RCUBE_INSTALL_PATH . 'skins';
        $skins = array();
        $out   = array();
        $dir   = opendir($path);

        if ($dir) {

            while (($file = readdir($dir)) !== false) {
                $filename = $path . DIRECTORY_SEPARATOR . $file;
                if (!preg_match('/^\./', $file) && is_dir($filename) && is_readable($filename)) {
                    $skins[] = $file;
                }
            }
            closedir($dir);

            sort($skins);
            foreach ($skins as $skin) {
                if (in_array($skin, $this->skins_allowed)) {
                    $name = ucfirst($skin);
                    $meta = @json_decode(@file_get_contents(INSTALL_PATH . "skins" . DIRECTORY_SEPARATOR . "$skin" . DIRECTORY_SEPARATOR . "meta.json"), true);
                    if (is_array($meta) && $meta['name']) {
                        $name    = $meta['name'];
                        $author  = $meta['url'] ? html::a(array('href' => $meta['url'], 'target' => '_blank'), rcube::Q($meta['author'])) : rcube::Q($meta['author']);
                    }
                    $thumbnail = html::img(array(
                        'src'     => "skins/$skin/thumbnail.png",
                        'class'   => 'skinthumb',
                        'alt'     => $skin,
                        'width'   => 32,
                        'height'  => 32,
                        'onerror' => "this.src = rcmail.assets_path('program/resources/blank.gif'); this.onerror = null",
                    ));

                    $out[$skin] = html::label(array('class' => 'tool-skin'),
                        html::span('skinitem', $thumbnail) .
                        html::span('skinitem', html::span('skinname', rcube::Q($name)) . html::br() . html::span('skinauthor', $author ? 'by ' . $author : ''))
                    );
                }
            }
        }

        return $out;
    }

    private function _init_storage()
    {

        if (!$this->storage) {

            // Add include path for internal classes
            $include_path = $this->home . DIRECTORY_SEPARATOR . 'lib' . PATH_SEPARATOR;
            $include_path .= ini_get('include_path');
            set_include_path($include_path);

            $class = $this->rcube->config->get('toolbox_storage', 'sql');
            $class = 'rcube_toolbox_storage_' . $class;

            // try to instantiate class
            if (class_exists($class)) {
                $this->storage = new $class($this->rcube->config, $this->cur_section);
            }
            else {
                // no storage found, raise error
                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "ERROR in [function _init_storage]: failed to find storage class {$class}");
                }
                rcube::raise_error(array('code' => 604, 'type' => 'toolbox',
                    'line' => __LINE__, 'file' => __FILE__,
                    'message' => "Failed to find storage class {$class}"
                ), true, true);
            }

        }

    }

}

// END OF FILE
