<?php

/**
 * Toolbox
 *
 * Plugin providing a set of tools for Roundcube
 *
 * @requires jQueryUI plugin
 *
 * @author Gianluca Giacometti
 *
 * Copyright (C) Gianluca Giacometti
 *
 * This program is a Roundcube (https://roundcube.net) plugin.
 * For more information see README.md.
 * For configuration see config.inc.php.dist.
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Roundcube. If not, see https://www.gnu.org/licenses/.
 */

require_once __DIR__ . '/lib/Toolbox/logger.php';

class toolbox extends rcube_plugin
{
    public $task = 'login|mail|settings|tasks|addressbook';

    private $storage;
    private $tools;
    private $rcube;
    private $sections = [];
    private $cur_section;
    private $loglevel;
    private $logfile;
    private $modules = [];

    public function init()
    {
        $this->rcube = rcube::get_instance();
        $this->load_config();
        $this->loglevel = $this->rcube->config->get('toolbox_loglevel', 1);
        if ($this->loglevel > 0) {
            $this->logfile = $this->rcube->config->get('toolbox_logfile', 'toolbox.log');
        }
        toolbox_logger::step('toolbox', 'init_start');
        $this->add_texts('localization/');
        $this->tools = $this->rcube->config->get('toolbox_tools');
        $this->cur_section = rcube_utils::get_input_value('_section', rcube_utils::INPUT_GPC);

        // Initialise modules and apply optional runtime configuration.
        $this->_load_modules();
        foreach ($this->modules as $module) {
            $module->configure_runtime(fn() => $this->module_storage());
        }

        if ($this->rcube->task == 'settings') {

            $this->include_stylesheet($this->local_skin_path() . '/tabstyles.css');

            // Apply each module's own access rules before registering sections.
            $this->_init_storage();
            foreach ($this->tools as $index => $name) {
                if (isset($this->modules[$name])
                    && (!$this->modules[$name]->is_visible_in_settings()
                        || !$this->modules[$name]->can_access_settings($this->storage))) {
                    unset($this->tools[$index]);
                }
            }
            $this->tools = array_values($this->tools);

            foreach ($this->tools as $tool) {
                toolbox_logger::step('toolbox', 'init_register_tool');
                $this->sections[$tool] = ['id' => $tool, 'class' => $tool, 'section' => rcmail::Q($this->gettext($tool))];
            }

            toolbox_logger::step('toolbox', 'init_prepare_settings_tab');
            // Do not show an empty Toolbox tab when only background modules
            // (such as password_hashes) are enabled.
            if ($this->tools) {
                $this->add_hook('settings_actions', [$this, 'settings_tab']);
            }

            toolbox_logger::step('toolbox', 'init_register_actions');
            $this->register_action('plugin.toolbox', [$this, 'init_html']);
            $this->register_action('plugin.toolbox.edit', [$this, 'init_html']);
            $this->register_action('plugin.toolbox.check', [$this, 'check']);
            $this->register_action('plugin.toolbox.delete', [$this, 'delete']);
            $this->register_action('plugin.toolbox.toggle', [$this, 'toggle']);
            $this->register_action('plugin.toolbox.save', [$this, 'save']);

            // Load optional settings assets from enabled and accessible modules.
            foreach ($this->tools as $name) {
                if (isset($this->modules[$name])) {
                    $this->modules[$name]->include_settings_assets();
                }
            }
        }

    }

    public function settings_tab($p)
    {
        $p['actions'][] = ['action' => 'plugin.toolbox', 'class' => 'toolbox', 'label' => 'toolbox.toolbox', 'title' => 'toolbox.toolbox-description', 'role' => 'button', 'aria-disabled' => 'false', 'tabindex' => '99'];
        return $p;
    }

    public function init_html()
    {
        $this->rcube->output->set_pagetitle(rcmail::Q($this->gettext('toolbox')));
        $this->include_script('js/toolbox.js');
        $this->include_stylesheet($this->local_skin_path() . '/toolbox.css');

        if ($this->rcube->action == 'plugin.toolbox.edit') {

            // use jQuery for popup window
            toolbox_logger::step('toolbox', 'edit_prepare_output');
            toolbox_logger::step('toolbox', 'edit_load_jquery');
            $this->require_plugin('jqueryui');
            $this->rcube->output->include_script('list.js');
            $this->rcube->output->add_handler('toolbox', [$this, 'tool_render_form']);
            $this->rcube->output->add_handler('sectionname', [$this, 'tool_section_name']);

            // initialise html editor
            toolbox_logger::step('toolbox', 'edit_init_editor');
            $this->rcube->html_editor('toolbox');
            $this->rcube->output->add_script(sprintf("window.rcmail_editor_settings = %s", $this->_config_editor()), 'head');

            toolbox_logger::step('toolbox', 'edit_select_tool');
            $this->rcube->output->set_env('cur_section', $this->cur_section);

            // render template
            toolbox_logger::step('toolbox', 'edit_render_template');
            $template = 'toolbox.tooledit';
            // Each enabled module declares its own editor assets.
            $module = $this->_get_module($this->cur_section);
            if ($module) {
                $module->include_edit_assets();
            }
            $this->rcube->output->send($template);

        }
        else {
            toolbox_logger::step('toolbox', 'section_list_prepare_output');
            $this->rcube->output->add_handler('tbsectionslist', [$this, 'tool_section_list']);

            // render template
            toolbox_logger::step('toolbox', 'section_list_render_template');
            $this->rcube->output->send('toolbox.toolbox');
        }

    }

    public function tool_section_list($attrib)
    {
        toolbox_logger::step('toolbox', 'section_list_prepare');

        // add id to message list table if not specified
        if (!strlen($attrib['id'])) {
            $attrib['id'] = 'rcmsectionslist';
        }

        $sections = [];

        // if template overrides default array then rebuild the array in the new order
        if (isset($attrib['sections'])) {
            $new_sections = [];
            $keys = preg_split('/[\s,;]+/', str_replace(["'", '"'], '', $attrib['sections']));
            foreach ($keys as $key) {
                $new_sections[] = $this->sections[$key];
            }
            $this->sections = $new_sections;
        }

        $data = $this->rcube->plugins->exec_hook('toolbox_sections_list', ['list' => $this->sections, 'cols' => ['section']]);
        foreach ($data['list'] as $id => $block) {
            $sections[$id] = $block;
            toolbox_logger::step('toolbox', 'section_list_add');
        }

        // create HTML table
        $out = $this->rcube->table_output($attrib, $sections, $data['cols'], 'id');

        // set client env
        toolbox_logger::step('toolbox', 'section_list_render');
        $this->rcube->output->add_gui_object('sectionslist', $attrib['id']);
        $this->rcube->output->include_script('list.js');

        return $out;
    }

    public function tool_section_name()
    {
        toolbox_logger::step('toolbox', 'section_name_render');
        $data = $this->rcube->plugins->exec_hook('toolbox_section_name', ['section' => $this->cur_section, 'title' => $this->sections[$this->cur_section]['section']]);
        return $data['title'];
    }

    /**
     * Render the enabled module's form using the shared form and fieldset UI.
     *
     * @param array $attrib Roundcube form-renderer attributes.
     * @return string Form markup for the selected section.
     */
    public function tool_render_form($attrib)
    {
        $module = $this->_get_module($this->cur_section);
        if (!$module) {
            return '';
        }

        $this->_init_storage();
        $section = $this->cur_section;
        $rendered = $module->render_section(
            $this->storage,
            $attrib,
            fn($tooldata, $class) => $this->_tool_render_fieldset($tooldata, $class)
        );

        $this->rcube->output->set_env('toolbox_input_error_class', $attrib['input_error_class'] ?? 'error');
        unset($attrib['form']);
        list($form_start, $form_end) = rcmail_action::get_form_tags(
            $attrib + ['enctype' => 'multipart/form-data'],
            'plugin.toolbox.save',
            null,
            ['name' => '_section', 'value' => $section]
        );

        return $form_start . $rendered['content']
            . ($rendered['append_form_end'] ? $form_end : '');
    }

    /**
     * Process an enabled module's toggle action.
     *
     * @return void
     */
    public function toggle()
    {
        $this->_dispatch_module_action('toggle');
    }

    /**
     * Process an enabled module's validation action.
     *
     * @return void
     */
    public function check()
    {
        $this->_dispatch_module_action('check');
    }

    /**
     * Process an enabled module's deletion action.
     *
     * @return void
     */
    public function delete()
    {
        $this->_dispatch_module_action('delete');
    }

    /**
     * Forward a settings AJAX request to its enabled module.
     *
     * @param string $operation One of toggle, check or delete.
     * @return void
     */
    private function _dispatch_module_action(string $operation): void
    {
        $section = rcube_utils::get_input_value('_section', rcube_utils::INPUT_POST, $operation !== 'check');
        $module = $this->_get_module($section);
        if (!$module) {
            return;
        }

        $this->cur_section = $section;
        $this->_init_storage();
        $module->handle_action($operation, $this->storage);
    }

    /**
     * Validate a section and return only an enabled, loaded module.
     * Domain-administrator restrictions are enforced by the enabled tools list.
     *
     * @param mixed $section Section name received from Roundcube.
     * @return ?toolbox_tool_module Selected module, if available and enabled.
     */
    private function _get_module($section): ?toolbox_tool_module
    {
        if (!is_string($section)
            || !in_array($section, $this->tools, true)
            || !isset($this->modules[$section])) {
            toolbox_logger::error('toolbox', 'dispatcher_lookup', 'section_unavailable');
            return null;
        }

        return $this->modules[$section];
    }

    /**
     * Prepare and persist settings through the existing common storage API.
     *
     * @return void
     */
    public function save()
    {
        $module = $this->_get_module($this->cur_section);
        if (!$module) {
            return;
        }

        $message_success = rcmail::Q($this->gettext('toolbox-datasuccessfullysaved'));
        $message_error = rcmail::Q($this->gettext('toolbox-datasaveerror'));

        $this->_init_storage();
        $prepared = $module->prepare_save($this->storage);
        if ($prepared === null) {
            return;
        }

        $new_settings = $prepared['new_settings'] ?? [];
        $message_success = $prepared['message_success'] ?? $message_success;
        $message_error = $prepared['message_error'] ?? $message_error;
        $data = $this->rcube->plugins->exec_hook('toolbox_save', [
            'section' => $this->cur_section,
            'new_settings' => $new_settings,
        ]);

        if (empty($data['abort'])) {
            if ($this->storage->save_tool_data($this->rcube->user->get_username(), $data)) {
                $this->rcube->output->command('display_message', $message_success, 'confirmation');
            } else {
                toolbox_logger::error('toolbox', 'settings_save', 'persistence_failed');
                $this->rcube->output->command('display_message', $message_error, 'error');
            }
        } else {
            $this->rcube->output->command(
                'display_message',
                !empty($data['message']) ? $data['message'] : $message_error,
                'error'
            );
        }

        $this->rcube->overwrite_action('plugin.toolbox.edit');
        $this->init_html();
    }

    private function _tool_render_fieldset($tabledata, $class)
    {
        $out = $tabledata['intro'];

        if (!empty($tabledata['rows'])) {
            $table = new html_table(['class' => 'propform ' . $tabledata['class'], 'cols' => $tabledata['cols']]);
            foreach ($tabledata['rows'] as $row) {
                if (isset($row['row_attribs'])) {
                    $table->set_row_attribs($row['row_attribs']);
                }
                if (isset($row['title'])) {
                    $table->add('title', $row['title']);
                }
                $table->add(isset($row['content_attribs']) ? $row['content_attribs'] : null, $row['content']);
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

    private function _config_editor($mode = '')
    {
        switch ($mode) {
            // default: full configuration
            default:
                $config = json_encode([
                    'plugins' => 'advlist anchor autolink autoresize charmap code contextmenu colorpicker fullscreen help hr image imagetools importcss link lists nonbreaking paste preview searchreplace tabfocus table textcolor visualchars',
                    'toolbar' => [
                        'cut copy paste | bold italic underline alignleft aligncenter alignright alignjustify | outdent indent | visualchars charmap | link unlink | searchreplace code help',
                        'fullscreen preview | fontselect fontsizeselect forecolor backcolor | image table | hr numlist bullist nonbreaking'
                    ],
                    'menubar' => 'edit insert view format table tools',
                    'autoresize_max_height' => '500',
                    'paste_data_images' => true
                ]);
            break;
        }
    return $config;
    }

    private function _minify_html($html)
    {

        $search = [
            '/\>[^\S ]+/s',     // strip whitespaces after tags, except space
            '/[^\S ]+\</s',     // strip whitespaces before tags, except space
            '/(\s)+/s',         // shorten multiple whitespace sequences
            '/<!--(.|\s)*?-->/' // Remove HTML comments
        ];

        $replace = [
            '>',
            '<',
            '\\1',
            ''
        ];

        return preg_replace($search, $replace, $html);
    }

    /**
     * Load the enabled, independently implemented tool modules.
     *
     * Existing tools without a file in tools/ continue to use their legacy
     * implementation in toolbox.php until they are migrated separately.
     *
     * @return void
     */
    private function _load_modules(): void
    {
        require_once __DIR__ . '/lib/Toolbox/preferences.php';
        require_once __DIR__ . '/lib/Toolbox/tool.php';

        $preferences = new toolbox_preferences($this->rcube);

        foreach ($this->tools as $tool) {
            // Restrict filesystem lookups to well-formed module names.
            if (!is_string($tool) || !preg_match('/^[a-z][a-z0-9_]*$/', $tool)) {
                continue;
            }

            $path = __DIR__ . '/tools/' . $tool . '.php';
            if (!is_file($path)) {
                continue; // The tool has not yet been migrated.
            }

            require_once $path;
            $class = 'toolbox_tool_' . $tool;
            if (!class_exists($class, false) || !is_subclass_of($class, toolbox_tool_module::class)) {
                throw new RuntimeException('Invalid Toolbox module: ' . $tool);
            }

            $module = new $class(
                $this,
                $this->rcube,
                $preferences,
                (int) $this->loglevel,
                (string) ($this->logfile ?? '')
            );

            // Keep module objects alive for hooks registered by future modules.
            $this->modules[$tool] = $module;
            $module->init();
        }
    }

    /**
     * Get the lazily initialized storage backend for a module.
     *
     * @return object Current Toolbox storage adapter.
     */
    public function module_storage()
    {
        $this->_init_storage();
        return $this->storage;
    }

    private function _init_storage()
    {

        if (!$this->storage) {

            // Add include path for internal classes
            $include_path = $this->home . '/lib' . \PATH_SEPARATOR;
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
                toolbox_logger::error('toolbox', 'storage_init', 'adapter_missing');
                rcube::raise_error([
                    'code' => 604,
                    'type' => 'toolbox',
                    'line' => __LINE__,
                    'file' => __FILE__,
                    'message' => "Failed to find storage class {$class}"
                ], true, true);
            }

        }

    }

}

// END OF FILE
