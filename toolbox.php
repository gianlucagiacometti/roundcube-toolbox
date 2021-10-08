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

class toolbox extends rcube_plugin
{
    public $task = 'login|mail|settings|tasks|addressbook';

    private $storage;
    private $tools;
    private $rcube;
    private $sections = [];
    private $cur_section;
    private $user_prefs;
    private $skins;
    private $skin;
    private $skins_allowed = [];
    private $loglevel;
    private $logfile;
    private $logo_types = [
        'customise-logo-type-all' => "",
        'customise-logo-type-favicon' => "[favicon]",
        'customise-logo-type-print' => "[print]",
        'customise-logo-type-small' => "[small]",
        'customise-logo-type-dark' => "[dark]",
        'customise-logo-type-small-dark' => "[small-dark]"
    ];
    private $attachments;
    private $lifespan;

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
        $this->attachments = slashify($this->rcube->config->get('toolbox_detach_storage', 'plugins/toolbox/attachments'));
        $this->lifespan = $this->rcube->config->get('toolbox_detach_lifespan', 30);
        $this->detach_total = $this->rcube->config->get('toolbox_detach_total', 1024 * 1024 * 50);
        $this->detach_single = $this->rcube->config->get('toolbox_detach_single', 1024 * 1024 * 25);

        $this->cur_section = rcube_utils::get_input_value('_section', rcube_utils::INPUT_GPC);

        // load user's preferences
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function init]: load user's preferences");
        }
        $this->_load_prefs();

        // message preview
        if (in_array('preview', $this->tools)) {

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'preview' tool active and loaded");
            }

            if (isset($this->user_prefs['toolbox_message_preview']) && ($this->user_prefs['toolbox_message_preview'] !== false)) {
                if ((rcube_utils::get_input_value('_task', rcube_utils::INPUT_GPC) == "mail") && !in_array(rcube_utils::get_input_value('_action', rcube_utils::INPUT_GPC), ["show", "compose"])) {
                    if (method_exists($this->rcube->output, 'include_css')) {
                        if ($this->loglevel > 2) {
                            rcube::write_log($this->logfile, "STEP in [function init]: load disable message preview stylesheet");
                        }
                        $this->rcube->output->include_css('plugins' . \DIRECTORY_SEPARATOR . $this->ID . \DIRECTORY_SEPARATOR . 'skins' . \DIRECTORY_SEPARATOR . 'elastic' . \DIRECTORY_SEPARATOR . 'nopreview.css');
                    }
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function init]: load set mark as read by double click script");
                    }
                    $this->include_script('js' . \DIRECTORY_SEPARATOR . 'toolbox.doubleclick.js');
                }
            }
            elseif (isset($this->user_prefs['toolbox_message_preview']) && ($this->user_prefs['toolbox_message_preview'] !== false)) {
                if ((rcube_utils::get_input_value('_task', rcube_utils::INPUT_GPC) == "mail") && !in_array(rcube_utils::get_input_value('_action', rcube_utils::INPUT_GPC), ["show", "compose"])) {
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function init]: load set mark as read by double clicking with mouse script");
                    }
                    $this->include_script('js' . \DIRECTORY_SEPARATOR . 'toolbox.doubleclick.js');
                }
            }

        }

        if (in_array('customise', $this->tools)) {

            // load customised skin configuration from config
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: skin: " . $this->user_prefs['skin']);
                rcube::write_log($this->logfile, "STEP in [function init]: load customised skin configuration from config");
            }
            $config = $this->rcube->config->get('toolbox_customise_skins', []);
            $config = isset($config[$this->user_prefs['skin']]) ? $config[$this->user_prefs['skin']] : (isset($config['default']) ? $config['default'] : []);

            // load customised skin configuration from database
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: load customised skin configuration from database");
            }
            $this->_init_storage();
            $customise = $this->storage->load_customised_config($this->rcube->user->get_username(), $this->user_prefs['skin']);

            // customise blank page
            $options = ['blankpage_type', 'blankpage_url', 'blankpage_image', 'blankpage_custom'];
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

            if (isset($config['blankpage_type']) && (in_array($config['blankpage_type'], ['url', 'image', 'custom']))) {
                $parts = explode('@', $this->rcube->user->get_username());
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
                            if (file_exists(RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'skins' . \DIRECTORY_SEPARATOR . $this->skin . \DIRECTORY_SEPARATOR . 'watermark.html')) {
                                if ($this->loglevel > 2) {
                                    rcube::write_log($this->logfile, "STEP in [function init]: found original watermark");
                                }
                                $blankpage = file_get_contents(RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'skins' . \DIRECTORY_SEPARATOR . $this->skin . \DIRECTORY_SEPARATOR . 'watermark.html');
                                if ($blankpage != '') {
                                    // we need a file in a folder named 'tmp' in plugin/toolbox (temp cannot be used for .htaccess limitations)
                                    // filename must contain a dot for .htaccess limitations
                                    // a file is needed since the 'blankpage' env variable needs a real file (loaded in apps.js)
                                    $tmp = RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'plugins' . \DIRECTORY_SEPARATOR . $this->ID . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . 'blankpage.' . $parts[1] . '.html';
                                    file_put_contents($tmp, preg_replace('!url\(.*?\)!U', "url(" . $config['blankpage_image'] . ")", $blankpage));
                                    if ($this->loglevel > 2) {
                                        rcube::write_log($this->logfile, "STEP in [function init]: write new watermark in folder tmp");
                                    }
                                    $this->rcube->output->set_env('blankpage', 'plugins' . \DIRECTORY_SEPARATOR . $this->ID . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . basename($tmp));
                                }
                            }
                        }
                        break;
                    case "custom":
                        if (isset($config['blankpage_custom']) && ($config['blankpage_custom'] != '')) {
                            // we need a file in a folder named 'tmp' in plugin/toolbox (temp cannot be used for .htaccess limitations)
                            // filename must contain a dot for .htaccess limitations
                            // a file is needed since the 'blankpage' env variable needs a real file (loaded in apps.js)
                            $tmp = RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'plugins' . \DIRECTORY_SEPARATOR . $this->ID . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . 'blankpage.' . $parts[1] . '.html';
                            file_put_contents($tmp, $config['blankpage_custom']);
                            $this->rcube->output->set_env('blankpage', 'plugins' . \DIRECTORY_SEPARATOR . $this->ID . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . basename($tmp));
                        }
                        break;
                }
            }
            elseif ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: customised blank page type: Roundcube default");
            }

            // customise css
            if (isset($customise['customise_css']) && ($customise['customise_css'] !== false) && ($customise['additional_css'] != '')) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: 'customise additional css' database option selected: override config");
                }
                // we need a file in a folder named 'tmp' in plugin/toolbox (temp cannot be used for .htaccess limitations)
                // filename must contain a dot for .htaccess limitations
                $tmp = RCUBE_INSTALL_PATH . \DIRECTORY_SEPARATOR . 'plugins' . \DIRECTORY_SEPARATOR . $this->ID . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . 'stylesheet.' . $parts[1] . '.css';
                file_put_contents($tmp, $customise['additional_css']);
                // css is loaded only under some circumstances (not when rcmail_output_json is called)
                if (method_exists($this->rcube->output, 'include_css')) {
                    $this->rcube->output->include_css('plugins' . \DIRECTORY_SEPARATOR . $this->ID . \DIRECTORY_SEPARATOR . 'tmp' . \DIRECTORY_SEPARATOR . basename($tmp));
                }
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: customised additional css loaded");
                }
            }
            elseif ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'customise additional css' database option not selected: loading from config");
                if (file_exists($config['additional_css'])) {
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function init]: valid customised additional css file defined, found and loaded: {$config['additional_css']}");
                    }
                    // css is loaded only under some circumstances (not when rcmail_output_json is called)
                    if (method_exists($this->rcube->output, 'include_css')) {
                        $this->rcube->output->include_css($config['additional_css']);
                    }
                }
                elseif ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: customised additional css file not found, empty or not defined");
                }
            }

            // customise logo
            if (isset($customise['customise_logo']) && ($customise['customise_logo'] !== false) && ($customise['customised_logo'] != '')) {
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

        } // end if customise

        if (in_array('attachments', $this->tools)) {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: 'attachments' tool active and loaded");
            }
            $this->add_hook('attachment_upload', array($this, 'detach_attachment'));
            $this->add_hook('message_compose', array($this, 'remove_session'));
            $this->add_hook('startup', array($this, 'download_detached'));
            $this->add_hook('toolbox_attachment_label', array($this, 'attachment_label'));
            if (rcube_utils::get_input_value('_action', rcube_utils::INPUT_GPC) == 'compose') {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]:modify hint in compose window");
                }
                $this->add_hook('template_object_composeattachmentform', array($this, 'update_hint'));
            }
        } // end if attachments

        if ($this->rcube->task == 'settings') {

            $this->include_stylesheet($this->local_skin_path() . '/tabstyles.css');

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

            foreach ($this->tools as $tool) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: load plugin tool {$tool}");
                }
                $this->sections[$tool] = ['id' => $tool, 'class' => $tool, 'section' => rcmail::Q($this->gettext($tool))];
            }

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: create toolbox tab in the list of settings");
            }
            $this->add_hook('settings_actions', [$this, 'settings_tab']);

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: register actions");
            }
            $this->register_action('plugin.toolbox', [$this, 'init_html']);
            $this->register_action('plugin.toolbox.edit', [$this, 'init_html']);
            $this->register_action('plugin.toolbox.check', [$this, 'check']);
            $this->register_action('plugin.toolbox.delete', [$this, 'delete']);
            $this->register_action('plugin.toolbox.toggle', [$this, 'toggle']);
            $this->register_action('plugin.toolbox.save', [$this, 'save']);

            if ($this->rcube->config->get('toolbox_vacation_jquery_calendar', false)) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function init]: load calendar script");
                }
                $this->include_script('js/toolbox.calendar.js');
            }
        }

    }

    public function settings_tab($p)
    {
        $p['actions'][] = ['action' => 'plugin.toolbox', 'class' => 'toolbox', 'label' => 'toolbox.toolbox', 'title' => 'toolbox.toolbox-description', 'role' => 'button', 'aria-disabled' => 'false', 'tabindex' => '99'];
        return $p;
    }

    public function download_detached() {
        if (rcube_utils::get_input_value('_action', rcube_utils::INPUT_GPC) == 'plugin.toolbox.attachment') {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function download_detached]: GET call received");
            }
            if ($file = rcube_utils::get_input_value('_filename', rcube_utils::INPUT_GPC)) {
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function download_detached]: file required: {$file}");
                }
                $path = $this->attachments . urlencode($file);
                if (file_exists($path)) {
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function download_detached]: file found");
                    }
                    $temp_dir = $this->rcube->config->get('temp_dir');
                    $tmpfname = tempnam($temp_dir, 'zip');
                    $zip = new ZipArchive();
                    $zip->open($tmpfname, ZIPARCHIVE::OVERWRITE);
                    $zip->addFile($path, preg_replace("/[^0-9A-Za-z_.]/", '_', substr($file, 33)));
                    $zip->close();
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function download_detached]: temporary zip file created");
                    }
                    $browser = new rcube_browser;
                    $this->rcube->output->nocacheing_headers();
                    // send download headers
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function download_detached]: sending download headers");
                    }
                    header("Content-Type: application/octet-stream");
                    if ($browser->ie)
                        header("Content-Type: application/force-download");
                    // don't kill the connection if download takes more than 30 sec.
                    @set_time_limit(0);
                    header("Content-Disposition: attachment; filename=\"". substr($file, 33) .".zip\"");
                    header("Content-length: " . filesize($tmpfname));
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function download_detached]: sending file");
                    }
                    readfile($tmpfname);
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function download_detached]: delete temporary file");
                    }
                    unlink($tmpfname);
                }
                else {
                    if ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "ERROR in [function download_detached]: file not found: {$file}");
                    }
                    rcube::raise_error([
                        'code' => 404,
                        'type' => 'php',
                        'file' => __FILE__,
                        'line' => __LINE__,
                        'message' => "File not found {$path}"
                    ], true, true);
                }
                exit;
            }
        }
    }

    public function remove_session($args) {
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function remove_session]: remove attachments_total value from session");
        }
        $this->rcube->session->remove('attachments_total');
        return $args;
    }

    public function attachment_label($args) {
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function attachment_label]: set the label in the html message");
        }
        $args['label'] = rcmail::Q($this->gettext('attachment-expiry-date'));
        return $args;
    }

    public function update_hint($args) {
        if ($args['mode'] == 'hint') {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function upload_hint]: use hook to upload the content of the hint");
            }
            $args['content'] = $args['content'] . "<div class='hint'>" . rcmail::Q($this->gettext(['name' => 'attachment-maxuploadsize', 'vars' => ['totalsize' => round($this->detach_total/(1024 * 1024)), 'singlesize' => round($this->detach_single/(1024 * 1024)), 'lifespan' => $this->lifespan]])) . "</div>";
        }
        return $args;
    }

    static public function detach_attachment($args) {
        $rcmail = rcube::get_instance();
        $loglevel = $rcmail->config->get('toolbox_loglevel', 1);
        if ($loglevel > 0) {
            $logfile = $rcmail->config->get('toolbox_logfile', 'toolbox.log');
            if ($loglevel > 2) {
                rcube::write_log($logfile, "STEP in [function detach_attachment]: load function");
            }
        }
        $file = $args['path'];
        $total = 0;
        if(isset($_SESSION['attachments_total'])){
            $total = $_SESSION['attachments_total'];
        }
        $size = filesize($file);
        $total = $total + $size;
        $_SESSION['attachments_total'] = $total;
        if ($total >= $rcmail->config->get('toolbox_detach_total', 1024 * 1024 * 50) || $size >= $rcmail->config->get('toolbox_detach_single', 1024 * 1024 * 25)) {
            $mime = strtolower($args['mimetype']);
            if ($mime == 'text/calendar' || $mime == 'text/ical' || $mime == 'application/ics') {
                return $args;
            }
            $plugin = $rcmail->plugins->exec_hook('toolbox_attachment_label', ['label' => '']);
            $label = $plugin['label'];
            $args['id'] = md5(session_id() . microtime());
            $filename = urlencode($args['id'] . '_' . $args['name']);
            $dest = slashify($rcmail->config->get('toolbox_detach_storage', 'plugins/toolbox/attachments')) . $filename;
            if (copy($file, $dest)) {
                if (isset($_SERVER['HTTPS']) && ($_SERVER['HTTPS'] == '1' || strtolower($_SERVER['HTTPS']) == 'on')) {
                    $s = "s";
                }
                else {
                    $s = "";
                }
                $url = 'http' . $s . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?_action=plugin.toolbox.attachment&_filename=' . $filename;
                $html = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN">' . "\r\n";
                $html  = "<html><body><div>";
                if ($size > 1024 * 1024) {
                    $disp_size = round($total/1024/1024, 2) . " MBytes";
                }
                elseif ($size > 1024) {
                    $disp_size = round($total/1024, 2) . " KBytes";
                }
                else {
                    $disp_size = round($total, 2) . " Bytes";
                }
                $html .= "<a href='" . $url . "'>" . rcmail::Q(utf8_decode(htmlentities($args['name'], ENT_COMPAT, 'UTF-8'))) . "</a> (" . $disp_size . ") ";
                $html .= "[" . utf8_decode($label) . ": " . date($rcmail->config->get('date_format','d-m-Y') . ' ' . $rcmail->config->get('time_format','H:i'), time() + 86400 * (int) $rcmail->config->get('toolbox_detach_lifespan', 30)) . "]";
                $html .= "</div></body></html>";
                file_put_contents($file, $html);
                $args['name'] = $args['name'] . '.html';
                $args['mimetype'] = 'text/html';
            }
        }
        return $args;
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
            $this->rcube->output->add_handler('toolbox', [$this, 'tool_render_form']);
            $this->rcube->output->add_handler('sectionname', [$this, 'tool_section_name']);

            // initialise html editor
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init_html]: initialise html editor");
            }
            $this->rcube->html_editor('toolbox');
            $this->rcube->output->add_script(sprintf("window.rcmail_editor_settings = %s", $this->_config_editor()), 'head');

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function init]: set calendar date format");
            }
            $format = $this->rcube->config->get('toolbox_vacation_jquery_dateformat', 'mm/dd/yy');
            $this->rcube->output->add_script("calendar_format='" . $format . "';");

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
            $this->rcube->output->add_handler('tbsectionslist', [$this, 'tool_section_list']);

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
        $data = $this->rcube->plugins->exec_hook('toolbox_section_name', ['section' => $this->cur_section, 'title' => $this->sections[$this->cur_section]['section']]);
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
                $sorts = [
                    '#alias-addresses-table' => [0, 'true']
                ];
                $this->rcube->output->set_env('table_sort', $sorts);

                $tooldata = ['name' => rcmail::Q($this->gettext('aliases-manage')), 'class' => 'toolbox-aliasestable', 'cols' => 2];

                $settings = $this->storage->load_tool_data($this->rcube->user->get_username());
                $aliases = [];
                if (!empty($settings['aliases'])) {
                    foreach ($settings['aliases'] as $alias) {
                        $active = $alias['active'];
                        $elements = explode("@", trim($alias['address']));
                        if ($elements[0] != "") {
                            $aliases[] = ['name' => $elements[0], 'domain' => $elements[1], 'active' => $active];
                        }
                    }
                }
                sort($aliases);

                $field_id = 'rcmfd_newaliasname';
                $input_newalias = new html_inputfield(['name' => '_newaliasname', 'id' => $field_id, 'title' => rcmail::Q($this->gettext('aliases-aliasname')), 'size' => 80, 'placeholder' => rcmail::Q($this->gettext('aliases-aliasname'))]);
                $field_id = 'rcmfd_newaliasactive';
                $input_newactive = new html_select(['name' => '_newaliasactive', 'id' => $field_id]);
                $input_newactive->add([rcmail::Q($this->gettext('toolbox-enabled')),rcmail::Q($this->gettext('toolbox-disabled'))], ['true','false']);

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
                        $this->_alias_address_row($address_table, 'alias', $alias, $attrib);
                    }
                }

                // add no address and new address row at the end
                if (!empty($aliases)) {
                    $noaddresses = 'display: none;';
                }

                $address_table->set_row_attribs(['class' => 'noaddress', 'style' => $noaddresses]);
                $address_table->add(['colspan' => '3'], rcube_utils::rep_specialchars_output(rcmail::Q($this->gettext('aliases-noaliases'))));

                $this->_alias_address_row($address_table, null, null, $attrib, ['class' => 'newaddress']);

                $table->add(['colspan' => 2, 'class' => 'scroller'], html::div(['id' => 'alias-addresses-cont'], $address_table->show()));

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
                $sorts = [
                    '#forward-addresses-table' => [0, 'true']
                ];
                $this->rcube->output->set_env('table_sort', $sorts);

                $tooldata = ['name' => rcmail::Q($this->gettext('forward-manage')), 'class' => 'toolbox-forwardtable', 'cols' => 2];

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
                $input_forwardaddress = new html_inputfield(['name' => '_forwardaddress', 'id' => $field_id, 'title' => rcmail::Q($this->gettext('forward-address')), 'size' => 80, 'placeholder' => rcmail::Q($this->gettext('forward-address'))]);

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
                        $this->_forward_address_row($address_table, 'forward', $address, $attrib);
                    }
                }

                // add no address and new address row at the end
                $noaddresses = !empty($addresses) ? 'display: none;' : '';

                $address_table->set_row_attribs(['class' => 'noaddress', 'style' => $noaddresses]);
                $address_table->add(['colspan' => '2'], rcube_utils::rep_specialchars_output(rcmail::Q($this->gettext('forward-noaddress'))));

                $this->_forward_address_row($address_table, null, null, $attrib, ['class' => 'newaddress']);

                $table->add(['colspan' => 2, 'class' => 'scroller'], html::div(['id' => 'forward-addresses-cont'], $address_table->show()));

                $field_id = 'rcmfd_keepcopies';
                $input_keepcopies = new html_checkbox(['name' => '_forwardkeepcopies', 'id' => $field_id, 'value' => '1']);

                $tooldata['rows']['keepcopies'] = [
                    'title' => html::label($field_id, rcmail::Q($this->gettext('forward-keepcopies'))),
                    'content' => $input_keepcopies->show($keepcopies)
                ];

                $tooldata['content'] = $table->show();

                $form_content .= $this->_tool_render_fieldset($tooldata, 'main');

                break;

            case 'vacation':

                // Add JS labels if needed
                $this->rcube->output->add_label(
                    'editorwarning'
                    );

                $tooldata = ['intro' => '', 'name' => rcmail::Q($this->gettext('vacation-manage')), 'class' => 'toolbox-vacationtable', 'cols' => 2];

                $selected = $this->storage->load_tool_data($this->rcube->user->get_username());

                $field_id = 'rcmfd_vacationactive';
                $input_vacationactive = new html_checkbox(['name' => '_vacationactive', 'id' => $field_id, 'value' => '1']);

                $tooldata['rows']['vacationactive'] = [
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-active'))),
                    'content' => $input_vacationactive->show($selected['active'])
                ];

                $field_id = 'rcmfd_vacationactivefrom';
                $input_vacationactivefrom = new html_inputfield(['name' => '_vacationactivefrom', 'id' => $field_id, 'value' => '']);

                $tooldata['rows']['vacationactivefrom'] = [
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-activefrom'))),
                    'content' => $input_vacationactivefrom->show($selected['activefrom'])
                ];

                $field_id = 'rcmfd_vacationactiveuntil';
                $input_vacationactiveuntil = new html_inputfield(['name' => '_vacationactiveuntil', 'id' => $field_id, 'value' => '']);

                $tooldata['rows']['vacationactiveuntil'] = [
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-activeuntil'))),
                    'content' => $input_vacationactiveuntil->show($selected['activeuntil'])
                ];

                $field_id = 'rcmfd_vacationintervaltime';
                $input_vacationintervaltime = new html_select(['name' => '_vacationintervaltime', 'id' => $field_id]);

                $options = $this->rcube->config->get('toolbox_vacation_interval_time');
                foreach($options as $name => $option) {
                    $input_vacationintervaltime->add(rcmail::Q($this->gettext('vacation-'.$name)), intval($option));
                }

                $tooldata['rows']['vacationintervaltime'] = [
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-intervaltime'))),
                    'content' => $input_vacationintervaltime->show($selected['interval_time'])
                ];

                $field_id = 'rcmfd_vacationsubject';
                $input_vacationsubject = new html_inputfield(['name' => '_vacationsubject', 'id' => $field_id, 'value' => '', 'size' => 95]);

                $tooldata['rows']['vacationsubject'] = [
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-subject'))),
                    'content' => $input_vacationsubject->show($selected['subject'])
                ];

                $field_id = 'rcmfd_vacationhtmleditor';
                $input_vacationhtmleditor = new html_checkbox(['name' => '_vacationhtmleditor', 'id' => $field_id, 'value' => '1']);

                $tooldata['rows']['vactionhtmleditor'] = [
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-htmleditor'))),
                    'content' => $input_vacationhtmleditor->show($this->user_prefs['toolbox_vacation_html_editor'])
                ];

                $field_id = 'rcmfd_vacationbody';
                $input_vacationbody = new html_textarea(['name' => '_vacationbody', 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 70, 'class' => $this->user_prefs['toolbox_vacation_html_editor'] ? 'mce_editor' : '']);

                $tooldata['rows']['vacationbody'] = [
                    'title' => html::label($field_id, rcmail::Q($this->gettext('vacation-body'))),
                    'content' => $input_vacationbody->show($selected['body'])
                ];

                $form_content .= $this->_tool_render_fieldset($tooldata, 'main');

                break;

            case 'purge':

                // load data
                $selected = $this->storage->load_tool_data($this->rcube->user->get_username());

                $folders = ["trash", "junk"];

                $tooldata = ['intro' => '', 'name' => rcmail::Q($this->gettext('purge-manage')), 'class' => 'toolbox-purgetable', 'cols' => 2];

                foreach ($folders as $folder) {

                    $field_name = '_user' . $folder;
                    $field_id = 'rcmfd' . $field_name;

                    if (!isset($selected[$folder])) { $selected[$folder] = 0; }
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

                    $select = new html_select(['name' => $field_name, 'id' => $field_id]);
                    $select->add($domainoption, 'NULL');
                    $select->add('──────────', NULL, ['disabled' => 'disabled']);
                    $select->add(rcmail::Q($this->gettext('purge-always')), '0');
                    $select->add(rcmail::Q('1 ' . $this->gettext('purge-day')), '1');
                    $options = ['3', '7', '15', '30', '45', '60', '90', '120', '150', '180', '270', '360'];
                    foreach($options as $option) {
                        $select->add(rcmail::Q($option . ' ' . $this->gettext('purge-days')), $option);
                    }

                    $tooldata['rows'][$folder] = [
                        'title' => html::label($field_id, rcmail::Q($this->gettext('purge-'.$folder))),
                        'content' => $select->show($this->user_prefs['toolbox_purge_'.$folder])
                    ];

                }

                $form_content .= $this->_tool_render_fieldset($tooldata, 'main');

                break;

            case 'preview':

                // load data
                $selected = $this->storage->load_tool_data($this->rcube->user->get_username());

                $tooldata = ['intro' => '', 'name' => rcmail::Q($this->gettext('preview-manage')), 'class' => 'toolbox-previewtable', 'cols' => 1];

                $button_id = 'rcmfd_userpreview';
                $input_userpreview = new html_checkbox(['name' => '_userpreview', 'id' => $button_id, 'value' => '1', 'class' => 'preview-selector', 'title' => rcmail::Q($this->gettext('preview-disable-message'))]);

                $tooldata['rows']['preview'] = [
                    'content' => $input_userpreview->show($this->user_prefs['toolbox_message_preview']) . html::label(['for' => $button_id, 'class' => 'preview-disable-label'], rcmail::Q($this->gettext('preview-disable-message')))
                ];

                $button_id = 'rcmfd_userdoubleclick';
                $input_userdoubleclick = new html_checkbox(['name' => '_userdoubleclick', 'id' => $button_id, 'value' => '1', 'class' => 'doubleclick-selector', 'title' => rcmail::Q($this->gettext('preview-markasread-doubleclick'))]);

                $tooldata['rows']['doubleclick'] = [
                    'content' => $input_userdoubleclick->show($this->user_prefs['toolbox_markasread_doubleclick']) . html::label(['for' => $button_id, 'class' => 'preview-doubleclick-label'], rcmail::Q($this->gettext('preview-markasread-doubleclick')))
                ];

                $form_content .= $this->_tool_render_fieldset($tooldata, 'main');

                break;

            case 'customise':

                if ($this->storage->is_domain_admin($this->rcube->user->get_username())) {

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
                    $selected = $this->storage->load_tool_data($this->rcube->user->get_username());

                    $form_content .= html::div(['class' => 'tool-title'], rcmail::Q($this->gettext('customise-manage')) . ' ' . $parts[1]);

                    // purge trash and junk folders
                    $folders = ["trash", "junk"];

                    $tooldata = ['intro' => '', 'name' => rcmail::Q($this->gettext('customise-purge')), 'class' => 'toolbox-purgetable', 'cols' => 2];

                    foreach ($folders as $folder) {

                        $field_name = '_domain' . $folder;
                        $field_id = 'rcmfd' . $field_name;

                        $select = new html_select(['name' => $field_name, 'id' => $field_id]);
                        $select->add(rcmail::Q($this->gettext('purge-always')), 0);
                        $select->add(rcmail::Q('1 ' . $this->gettext('purge-day')), 1);
                        $options = ['3', '7', '15', '30', '45', '60', '90', '120', '150', '180', '270', '360'];
                        foreach($options as $option) {
                            $select->add(rcmail::Q($option . ' ' . $this->gettext('purge-days')), intval($option));
                        }

                        $tooldata['rows'][$folder] = [
                            'title' => html::label($field_id, rcmail::Q($this->gettext('purge-'.$folder))),
                            'content' => $select->show($selected[$folder])
                        ];

                    }

                    $form_content .= $this->_tool_render_fieldset($tooldata, 'purge');

                    // blank page settings
                    $form_content .= html::div(['class' => 'tool-subtitle'], rcmail::Q($this->gettext('skin')));

                    // set settings for each skin
                    foreach ($this->_get_skins() as $skin => $header) {

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
                            if ($selected['skins'][$skin]['customise_blankpage'] !== false) {
                                $row_attribs['blankpage'] = [];
                            }
                            if (isset($selected['skins'][$skin]['blankpage_type']) && in_array($selected['skins'][$skin]['blankpage_type'], ['url', 'image', 'custom'])) {
                                $row_attribs[$selected['skins'][$skin]['blankpage_type']] = [];
                            }
                            if ($selected['skins'][$skin]['customise_css'] !== false) {
                                $row_attribs['css'] = [];
                            }
                            if ($selected['skins'][$skin]['customise_logo'] !== false) {
                                $row_attribs['logo'] = [];
                            }
                        }

                        // skin header
                        $form_content .= $header;

                        // blank page
                        $tooldata = ['intro' => '', 'name' => rcmail::Q($this->gettext('customise-blankpage-skin')), 'class' => 'toolbox-customisetable', 'cols' => 1];

                        $button_id = 'rcmfd_blankpageskin_selector_' . $skin;
                        $input_blankpageselector = new html_checkbox(['name' => '_blankpageselector_' . $skin, 'id' => $button_id, 'value' => '1', 'class' => 'customise-blankpage-selector', 'title' => rcmail::Q($this->gettext('customise-blankpage'))]);

                        $tooldata['rows']['skinblankpage'] = [
                            'content' => $input_blankpageselector->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['customise_blankpage'] : ' ') . html::label(['for' => $button_id, 'class' => 'customise-blankpage-label'], rcmail::Q($this->gettext('customise-blankpage')))
                        ];

                        // blank page type table
                        $blankpagetype_table = new html_table(['id' => 'customise_blankpage_type_table_' . $skin, 'class' => 'customise-blankpage-type-table', 'cols' => 1]);

                        // Roundcube default blank page
                        $button_id = 'rcmrb_blankpageskindefault_' . $skin;
                        $input_skindefault = new html_radiobutton(['name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => '', 'class' => 'customise-blankpage-skin-selector']);

                        $blankpagetype_table->add(['class' => 'blankpage-type-check'], $input_skindefault->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['blankpage_type'] : '') . html::label($button_id, rcmail::Q($this->gettext('customise-blankpage-skin-default'))));

                        // blank page as url
                        $button_id = 'rcmrb_blankpageskinurl_' . $skin;
                        $input_skinurl = new html_radiobutton(['name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'url', 'class' => 'customise-blankpage-skin-selector']);

                        $blankpagetype_table->add(['class' => 'blankpage-type-check'], $input_skinurl->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['blankpage_type'] : '') . html::label($button_id, rcmail::Q($this->gettext('customise-blankpage-skin-url'))));

                        $field_id = 'rcmfd_blankpageurl_' . $skin;
                        $input_blankpageurl = new html_inputfield(['name' => '_blankpageurl_' . $skin, 'class' => 'tool-skin-blankpage-url', 'type' => 'url', 'id' => $field_id, 'title' => rcmail::Q($this->gettext('customise-blankpage-url')), 'placeholder' => rcmail::Q($this->gettext('customise-blankpage-url'))]);

                        $blankpagetype_table->set_row_attribs($row_attribs['url']);
                        $blankpagetype_table->add(['class' => 'blankpage-type-content'], $input_blankpageurl->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['blankpage_url'] : '') . html::span(['id' => $button_id . '_content'], ''));

                        // blank page with custom image
                        $button_id = 'rcmrb_blankpageskinimage_' . $skin;
                        $input_skinimage = new html_radiobutton(['name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'image', 'class' => 'customise-blankpage-skin-selector']);

                        $blankpagetype_table->add(['class' => 'blankpage-type-check'], $input_skinimage->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['blankpage_type'] : '') . html::label($button_id, rcmail::Q($this->gettext('customise-blankpage-skin-image'))));

                        $image_id = 'rcmbtn_modifyblankpageimage_' . $skin;
                        $blankpageimage = html::img([
                            'id'      => $image_id,
                            'src'     => (isset($selected['skins'][$skin]['blankpage_image']) && ($selected['skins'][$skin]['blankpage_image'] != '') ? $selected['skins'][$skin]['blankpage_image'] : 'program/resources/blank.gif'),
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
                            html::span(['class' => 'blankpage-image-delete', 'title' => rcmail::Q($this->gettext('delete'))], $button_deleteblankpageimage);

                        $blankpagetype_table->set_row_attribs($row_attribs['image']);
                        $blankpagetype_table->add(['class' => 'blankpage-type-content'], $blankpagewrapper . html::span(['id' => $button_id . '_content'], '') . $hidden_blankpageimagecontrol->show());

                        // custom blank page
                        $button_id = 'rcmrb_blankpageskincustom_' . $skin;
                        $input_skincustom = new html_radiobutton(['name' => '_blankpagetype_' . $skin, 'id' => $button_id, 'value' => 'custom', 'class' => 'customise-blankpage-skin-selector']);

                        $blankpagetype_table->add(['class' => 'blankpage-type-check'], $input_skincustom->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['blankpage_type'] : '') . html::label($button_id, rcmail::Q($this->gettext('customise-blankpage-skin-custom'))));

                        $field_id = 'rcmfd_blankpagecustom_' . $skin;
                        $input_blankpagecustom = new html_textarea(['name' => '_blankpagecustom_' . $skin, 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 92, 'class' => 'mce_editor']);

                        $blankpagetype_table->set_row_attribs($row_attribs['custom']);
                        $blankpagetype_table->add(['class' => 'blankpage-type-content'], $input_blankpagecustom->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['blankpage_custom'] : '') . html::span(['id' => $button_id . '_content'], ''));

                        // show blank page type table
                        $tooldata['rows']['blankpage'] = [
                            'content' => $blankpagetype_table->show(),
                            'row_attribs' => $row_attribs['blankpage']
                        ];

                        $form_content .= $this->_tool_render_fieldset($tooldata, 'blankpage');

                        // css
                        $tooldata = ['intro' => '', 'name' => 'CSS', 'class' => 'toolbox-customisetable', 'cols' => 1];

                        $button_id = 'rcmfd_additionalcss_selector_' . $skin;
                        $input_additionalcssselector = new html_checkbox(['name' => '_additionalcssselector_' . $skin, 'id' => $button_id, 'value' => '1', 'class' => 'customise-additional-css-selector', 'title' => rcmail::Q($this->gettext('customise-additional-css'))]);

                        $tooldata['rows']['skinadditionalcss'] = [
                            'content' => $input_additionalcssselector->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['customise_css'] : '') . html::label(['for' => $button_id, 'class' => 'customise-additional-css-label'], rcmail::Q($this->gettext('customise-additional-css')))
                        ];

                        $field_id = 'rcmfd_additionalcss_' . $skin;
                        $input_additionalcss = new html_textarea(['name' => '_additionalcss_' . $skin, 'id' => $field_id, 'spellcheck' => 1, 'rows' => 12, 'cols' => 92, 'class' => 'tool-skin-additional-css']);

                        $tooldata['rows']['additionalcss'] = [
                            'content' => $input_additionalcss->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['additional_css'] : '') . html::span(['id' => $button_id . '_content'], ''),
                            'row_attribs' => $row_attribs['css']
                        ];

                        $form_content .= $this->_tool_render_fieldset($tooldata, 'additionalcss');

                        // logo
                        $tooldata = ['intro' => '', 'name' => 'Logo', 'class' => 'toolbox-customisetable', 'cols' => 1];

                        $button_id = 'rcmfd_customiselogo_selector_' . $skin;
                        $input_customiselogoselector = new html_checkbox(['name' => '_customiselogoselector_' . $skin, 'id' => $button_id, 'value' => '1', 'class' => 'customise-logo-selector', 'title' => rcmail::Q($this->gettext('customise-logo'))]);

                        $tooldata['rows']['skincustomiselogo'] = [
                            'content' => $input_customiselogoselector->show(isset($selected['skins'][$skin]) && !empty($selected['skins'][$skin]) ? $selected['skins'][$skin]['customise_logo'] : '') . html::label(['for' => $button_id, 'class' => 'customise-logo-label'], rcmail::Q($this->gettext('customise-logo')))
                        ];

                        $form_content .= $this->_tool_render_fieldset($tooldata, 'customiselogo');

                        $tooldata = ['name' => rcmail::Q($this->gettext('customise-logo-template-new')), 'class' => 'toolbox-customiselogotable', 'cols' => 3];

                        $field_id = 'rcmfd_newcustomisedlogotemplate_' . $skin;
                        $input_newtemplate = new html_select(['name' => '_newcustomisedlogotemplate_' . $skin, 'id' => $field_id]);
                        $input_newtemplate->add(array_merge([rcmail::Q($this->gettext('customise-logo-template-all'))], $this->_get_templates($skin)), array_merge(['*'], $this->_get_templates($skin)));
                        $field_id = 'rcmfd_newcustomisedlogotype_' . $skin;
                        $input_newlogotype = new html_select(['name' => '_newcustomisedlogotype_' . $skin, 'id' => $field_id]);
                        $input_newlogotype->add(array_map(function($key) { return(rcmail::Q($this->gettext($key))); }, array_keys($this->logo_types)), array_values($this->logo_types));
                        $field_id = 'rcmfd_newcustomisedlogoimage_' . $skin;
                        $input_newlogoimage = new html_inputfield(['name' => '_newcustomisedlogoimage_' . $skin, 'id' => $field_id, 'type' => 'file']);

                        $field_id = 'rcmbtn_addcustomisedlogo_' . $skin;
                        $button_addcustomisedlogo = $this->rcube->output->button(['id' => $field_id, 'command' => 'plugin.toolbox.add_customised_logo', 'type' => 'input', 'class' => 'button', 'label' => 'toolbox.customise-logo-add-template', 'data-skin' => $skin]);

                        $tooldata['intro'] = html::div('customise-template-input grouped input-group', $input_newtemplate->show() . $input_newlogotype->show('') . $input_newlogoimage->show('') . $button_addcustomisedlogo);

                        $delete_all = $this->rcube->output->button(['class' => 'delete-all', 'command' => 'plugin.toolbox.delete_all_customisedlogos', 'type' => 'link', 'label' => 'toolbox.toolbox-deleteall', 'title' => 'toolbox.cunstomise-logo-deleteallcustomisedlogos', 'data-skin' => $skin]);

                        $table = new html_table(['class' => 'toolbox-customiselogotable propform', 'cols' => 2]);
                        $table->add(['colspan' => 2, 'id' => 'listcontrols'], $delete_all);

                        $logo_table = new html_table(['id' => 'customised-logo-table-' . $skin, 'class' => 'records-table sortable-table toolbox-customisedlogotable fixedheader', 'cellspacing' => '0', 'cols' => 4]);
                        $logo_table->add_header('logotemplate', rcmail::Q($this->gettext('customise-logo-template')));
                        $logo_table->add_header('logotype', rcmail::Q($this->gettext('customise-logo-type')));
                        $logo_table->add_header('logoimage', rcmail::Q($this->gettext('customise-logo-image')));
                        $logo_table->add_header('control', '&nbsp;');

                        $images = json_decode($selected['skins'][$skin]['customised_logo'], true);

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
                                $this->_customised_logo_row($logo_table, 'logo', $logo, $attrib);
                            }
                            $nocustomisedlogo = 'display: none;';
                        }

                        // add new logo row at the end
                        $logo_table->set_row_attribs(['class' => 'nologo', 'style' => isset($nocustomisedlogo) ? $nocustomisedlogo : '']);
                        $logo_table->add(['colspan' => '4'], rcube_utils::rep_specialchars_output(rcmail::Q($this->gettext('customise-logo-nocustomisedlogo'))));

                        $newlogo = [
                            'id' => 0,
                            'skin' => $skin,
                            'template' => '',
                            'type' => '',
                            'image' => ''
                        ];
                        $this->_customised_logo_row($logo_table, null, $newlogo, $attrib, ['class' => 'newlogo']);

                        $table->add(['colspan' => 3, 'class' => 'scroller'], html::div(['id' => 'customise-logo-customised-logo-cont-' . $skin], $logo_table->show()));

                        $tooldata['content'] = $table->show();

                        $form_content .= $this->_tool_render_fieldset($tooldata, array_merge(['class' => 'customiselogo-wrapper', 'id' => $button_id . '_content'], $row_attribs['logo']));

                    }

                }

                break;

        }

        // define input error class
        $this->rcube->output->set_env('toolbox_input_error_class', $attrib['input_error_class'] ?: 'error');

        unset($attrib['form']);
        list($form_start, $form_end) = rcmail_action::get_form_tags($attrib + ['enctype' => 'multipart/form-data'], 'plugin.toolbox.save', null, ['name' => '_section', 'value' => $this->cur_section]);

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function tool_render_form]: render form for tool {$this->cur_section}");
        }
        return $this->cur_section != 'customise' ? $form_start . $form_content . $form_end : $form_start . $form_content;

    }

    public function toggle()
    {
        $rcmail = rcmail::get_instance();

        $this->cur_section = rcube_utils::get_input_value('_section', rcube_utils::INPUT_POST, true);

        $data = ['section' => $this->cur_section];
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

        $data = ['section' => $this->cur_section];
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

        $new_settings = [];

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function save]: selected tool: {$this->cur_section}");
        }
        switch ($this->cur_section) {

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
                    $new_settings['main']['addresses'][] = ['value' => mb_convert_case(trim($address), MB_CASE_LOWER, RCUBE_CHARSET)];
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

            case 'preview':

                // save user's preview choice
                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [function save]: save user's preview choice");
                }
                $this->user_prefs['toolbox_message_preview'] = rcube_utils::get_input_value('_userpreview', rcube_utils::INPUT_POST) ?: false;
                $this->user_prefs['toolbox_markasread_doubleclick'] = rcube_utils::get_input_value('_userdoubleclick', rcube_utils::INPUT_POST) ?: false;
                // we need to set Mark as read = never when message disable preview is set to true; this todisable the mark as read with single click event
                if (($this->user_prefs['toolbox_message_preview'] !== false) || ($this->user_prefs['toolbox_markasread_doubleclick'] !== false)) {
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function save]: at least one between 'disable message preview' and 'mark as read by double clicking' is true: set mail_read_time to 'never'");
                    }
                    $this->user_prefs['mail_read_time'] = -1;
                    $this->user_prefs['toolbox_markasread_doubleclick'] = true;
                }
                // and we go back to normality when message disable preview is set to false
                elseif (($this->user_prefs['mail_read_time'] == -1) || ($this->user_prefs['toolbox_markasread_doubleclick'] == false)) {
                    if ($this->loglevel > 2) {
                        rcube::write_log($this->logfile, "STEP in [function save]: at least one between 'disable message preview' and 'mark as read by double clicking' is false: set mail_read_time to 'immediately'");
                    }
                    $this->user_prefs['mail_read_time'] = 0;
                }
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
                            rcube::write_log($this->logfile, "STEP in [function save]: " . count($customised_templates) . " customised logos found for skin " . $skin);
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

                break;

        }

        $data = $this->rcube->plugins->exec_hook('toolbox_save', ['section' => $this->cur_section, 'new_settings' => $new_settings]);

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

    private function _alias_address_row(&$address_table, $class, $alias, $attrib, $row_attrib = [])
    {
        $hidden_name = new html_hiddenfield(['name' => '_aliasname[]', 'value' => (isset($alias['name']) ? $alias['name'] : '')]);

        $row_attrib = !isset($class) ? array_merge($row_attrib, ['style' => 'display: none;']) : array_merge($row_attrib, ['class' => $class]);
        $address_table->set_row_attribs($row_attrib);

        $button_type = isset($alias['active']) && $alias['active'] !== false ? 'enabled' : 'disabled';
        $address_table->add(['class' => 'email ' . $button_type], (isset($alias['name']) ? $alias['name'] : ''));

        $toggle = isset($alias['active']) && $alias['active'] !== false ? rcmail::Q($this->gettext('toolbox-disable')) : rcmail::Q($this->gettext('toolbox-enable'));
        $enable_button = $this->rcube->output->button(['command' => 'plugin.toolbox.toggle_alias', 'type' => 'link', 'class' => $button_type, 'label' => 'toolbox.toolbox-'.$button_type, 'content' => ' ', 'title' => 'toolbox.toolbox-'.$button_type]);
        $input_active = new html_checkbox(['name' => '_aliasactive[]', 'value' => '1', 'style' => 'display: none;']);
        $address_table->add('status', $enable_button . $input_active->show(isset($alias['active']) ? $alias['active'] : ''));

        $del_button = $this->rcube->output->button(['command' => 'plugin.toolbox.delete_alias', 'type' => 'link', 'class' => 'delete', 'label' => 'delete', 'content' => ' ', 'title' => 'delete']);
        $address_table->add('control', $del_button . $hidden_name->show());
    }

    private function _forward_address_row(&$address_table, $class, $value, $attrib, $row_attrib = [])
    {
        $hidden_field = new html_hiddenfield(['name' => '_forwardaddresses[]', 'value' => $value]);

        $row_attrib = !isset($class) ? array_merge($row_attrib, ['style' => 'display: none;']) : array_merge($row_attrib, ['class' => $class]);
        $address_table->set_row_attribs($row_attrib);

        $address_table->add(['class' => 'email'], $value);

        $del_button = $this->rcube->output->button(['command' => 'plugin.toolbox.delete_forward_address', 'type' => 'link', 'class' => 'delete', 'label' => 'delete', 'content' => ' ', 'title' => 'delete']);
        $address_table->add('control', $del_button . $hidden_field->show());
    }

    private function _customised_logo_row(&$logo_table, $class, $logo, $attrib, $row_attrib = [])
    {
        $hidden_customisedlogotemplate = new html_hiddenfield(['name' => '_customisedlogotemplate_' . $logo['skin'] . '[]', 'value' => $logo['template']]);
        $hidden_customisedlogotype = new html_hiddenfield(['name' => '_customisedlogotype_' . $logo['skin'] . '[]', 'value' => $logo['type']]);
        $hidden_customisedlogoimage = new html_hiddenfield(['name' => '_customisedlogoimage_' . $logo['skin'] . '[]', 'value' => $logo['image']]);

        $row_attrib = !isset($class) ? array_merge($row_attrib, ['style' => 'display: none;']) : array_merge($row_attrib, ['class' => $class]);
        $logo_table->set_row_attribs($row_attrib);

        $logo_table->add(['class' => 'customised-logo-template'], ($logo['template'] == '*' ? rcmail::Q($this->gettext('customise-logo-template-all')) : $logo['template']));
        $logo_table->add(['class' => 'customised-logo-type'], rcmail::Q($this->gettext(array_flip($this->logo_types)[$logo['type']])));
        $logoimage = html::img([
            'src'     => $logo['image'] ?: 'program/resources/blank.gif',
            'class'   => 'customised-logo-image-tag',
            'height'  => 36,
            'onerror' => "this.src = rcmail.assets_path('program/resources/blank.gif'); this.onerror = null",
        ]);
        $logo_table->add(['class' => 'customised-logo-image'], $logoimage);

        $del_button = $this->rcube->output->button(['command' => 'plugin.toolbox.delete_customised_logo', 'type' => 'link', 'class' => 'delete', 'label' => 'delete', 'content' => ' ', 'title' => 'delete', 'data-skin' => $logo['skin']]);
        $logo_table->add('control', $del_button . $hidden_customisedlogotemplate->show() . $hidden_customisedlogotype->show() . $hidden_customisedlogoimage->show());
    }

    private function _load_prefs()
    {
        $this->user_prefs = $this->rcube->user->get_prefs();

        isset($this->user_prefs['toolbox_purge_trash']) || $this->user_prefs['toolbox_purge_trash'] = 'NULL';
        isset($this->user_prefs['toolbox_purge_junk']) || $this->user_prefs['toolbox_purge_junk'] = 'NULL';
        isset($this->user_prefs['toolbox_vacation_html_editor']) || $this->user_prefs['toolbox_vacation_html_editor'] = false;
        isset($this->user_prefs['toolbox_safelogin_history']) || $this->user_prefs['toolbox_safelogin_history'] = true;
        isset($this->user_prefs['skin']) && in_array($this->user_prefs['skin'], $this->skins_allowed) || $this->user_prefs['skin'] = $this->skin;
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

    private function _get_skins()
    {
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
                if (in_array($skin, $this->skins_allowed)) {
                    $name = ucfirst($skin);
                    $meta = @json_decode(@file_get_contents(INSTALL_PATH . "skins" . \DIRECTORY_SEPARATOR . $skin . \DIRECTORY_SEPARATOR . "meta.json"), true);
                    if (is_array($meta) && $meta['name']) {
                        $name    = $meta['name'];
                        $author  = isset($meta['url']) ? html::a(['href' => $meta['url'], 'target' => '_blank'], rcube::Q($meta['author'])) : rcube::Q($meta['author']);
                    }
                    $thumbnail = html::img([
                        'src'     => "skins/$skin/thumbnail.png",
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

    private function _get_templates($skin)
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
                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "ERROR in [function _init_storage]: failed to find storage class {$class}");
                }
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
