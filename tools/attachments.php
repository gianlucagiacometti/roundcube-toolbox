<?php

/**
 * Attachments tool for Roundcube Toolbox.
 *
 * Handles detached large attachments, creates expiring download links,
 * and displays size/lifetime guidance in the compose form.
 *
 * Migrated from the original Toolbox plugin without changing the
 * attachment storage, link generation or download algorithms.
 */
class toolbox_tool_attachments extends toolbox_tool_module
{
    private $attachments;
    private $lifespan;
    private $detach_total;
    private $detach_single;

    /**
     * Register Attachments hooks and initialize the attachment settings.
     *
     * @return void
     */
    public function init(): void
    {
        $this->attachments = slashify($this->rcube->config->get('toolbox_detach_storage', 'attachments'));
        $this->lifespan = $this->rcube->config->get('toolbox_detach_lifespan', 30);
        $this->detach_total = $this->rcube->config->get('toolbox_detach_total', 1024 * 1024 * 50);
        $this->detach_single = $this->rcube->config->get('toolbox_detach_single', 1024 * 1024 * 25);

        toolbox_logger::step('attachments', 'init_start');

        $this->plugin->add_hook('attachment_upload', [$this, 'detach_attachment']);
        $this->plugin->add_hook('message_compose', [$this, 'remove_session']);
        $this->plugin->add_hook('startup', [$this, 'download_detached']);
        $this->plugin->add_hook('toolbox_attachment_label', [$this, 'attachment_label']);

        if (rcube_utils::get_input_value('_action', rcube_utils::INPUT_GPC) == 'compose') {
            toolbox_logger::step('attachments', 'compose_hint_register');
            $this->plugin->add_hook('template_object_composeattachmentform', [$this, 'update_hint']);
        }
    }

    /**
     * Display the active attachment tool and its configured limits.
     *
     * Attachments has no user-editable settings. Configuration is managed
     * by the server administrator through config.inc.php.
     *
     * @param object $storage Shared Toolbox storage backend, unused.
     * @param array $attributes Form attributes, unused.
     * @param callable $fieldset Shared fieldset renderer, unused.
     * @return array Informational content and closing-form disposition.
     */
    public function render_section($storage, array $attributes, callable $fieldset): array
    {
        $status = rcmail::Q($this->plugin->gettext('toolbox-enabled'));

        $description = rcmail::Q($this->plugin->gettext([
            'name' => 'attachment-maxuploadsize',
            'vars' => [
                'totalsize' => round($this->detach_total / (1024 * 1024)),
                'singlesize' => round($this->detach_single / (1024 * 1024)),
                'lifespan' => $this->lifespan,
            ],
        ]));

        return [
            'content' => html::tag('p', null, html::tag('strong', null, $status))
                . html::tag('p', null, $description),
            'append_form_end' => true,
        ];
    }

    /**
     * Serve a previously detached attachment as a ZIP download.
     *
     * Runs from Roundcube's startup hook, but only handles the Toolbox
     * attachment download action.
     *
     * @return void
     */
    public function download_detached() {
        if (rcube_utils::get_input_value('_action', rcube_utils::INPUT_GPC) == 'plugin.toolbox.attachment') {
            toolbox_logger::step('attachments', 'download_request');
            if ($file = rcube_utils::get_input_value('_filename', rcube_utils::INPUT_GPC)) {
                toolbox_logger::step('attachments', 'download_filename_received');
                $path = $this->attachments . urlencode($file);
                if (file_exists($path)) {
                    toolbox_logger::step('attachments', 'download_file_found');
                    $temp_dir = $this->rcube->config->get('temp_dir');
                    $tmpfname = tempnam($temp_dir, 'zip');
                    $zip = new ZipArchive();
                    $zip->open($tmpfname, ZIPARCHIVE::OVERWRITE);
                    $zip->addFile($path, preg_replace("/[^0-9A-Za-z_.]/", '_', substr($file, 33)));
                    $zip->close();
                    toolbox_logger::step('attachments', 'download_zip_created');
                    $browser = new rcube_browser;
                    $this->rcube->output->nocacheing_headers();
                    // send download headers
                    toolbox_logger::step('attachments', 'download_headers_send');
                    header("Content-Type: application/octet-stream");
                    if ($browser->ie)
                        header("Content-Type: application/force-download");
                    // don't kill the connection if download takes more than 30 sec.
                    @set_time_limit(0);
                    header("Content-Disposition: attachment; filename=\"". substr($file, 33) .".zip\"");
                    header("Content-length: " . filesize($tmpfname));
                    toolbox_logger::step('attachments', 'download_stream_start');
                    readfile($tmpfname);
                    toolbox_logger::step('attachments', 'download_temp_cleanup');
                    unlink($tmpfname);
                }
                else {
                    toolbox_logger::error('attachments', 'download_lookup', 'file_not_found');
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

    /**
     * Reset the aggregate attachment size when a compose session begins.
     *
     * @param array $args Hook arguments.
     * @return array Unmodified hook arguments.
     */
    public function remove_session($args) {
        toolbox_logger::step('attachments', 'compose_session_reset');
        $this->rcube->session->remove('attachments_total');
        return $args;
    }

    /**
     * Provide the localized detached attachment label.
     *
     * @param array $args Hook arguments.
     * @return array Hook arguments with the label set.
     */
    public function attachment_label($args) {
        toolbox_logger::step('attachments', 'detached_label_set');
        $args['label'] = rcmail::Q($this->plugin->gettext('attachment-expiry-date'));
        return $args;
    }

    /**
     * Append configured attachment limits to the compose form hint.
     *
     * @param array $args Template hook arguments.
     * @return array Updated template arguments.
     */
    public function update_hint($args) {
        if ($args['mode'] == 'hint') {
            toolbox_logger::step('attachments', 'compose_hint_update');
            $args['content'] = $args['content'] . "<div class='hint'>" . rcmail::Q($this->plugin->gettext(['name' => 'attachment-maxuploadsize', 'vars' => ['totalsize' => round($this->detach_total/(1024 * 1024)), 'singlesize' => round($this->detach_single/(1024 * 1024)), 'lifespan' => $this->lifespan]])) . "</div>";
        }
        return $args;
    }

    /**
     * Replace large outgoing attachments with an expiring download link.
     *
     * @param array $args Attachment upload hook arguments.
     * @return array Updated attachment metadata.
     */
    static public function detach_attachment($args) {
        $rcmail = rcube::get_instance();
        toolbox_logger::step('attachments', 'upload_start');
        $file = $args['path'];
        $total = 0;
        if(isset($_SESSION['attachments_total'])){
            $total = $_SESSION['attachments_total'];
        }
        $size = filesize($file);
        $total = $total + $size;
        $_SESSION['attachments_total'] = $total;
        if ($total >= $rcmail->config->get('toolbox_detach_total', 1024 * 1024 * 50) || $size >= $rcmail->config->get('toolbox_detach_single', 1024 * 1024 * 25)) {
            toolbox_logger::step('attachments', 'detach_threshold_reached');
            $mime = strtolower($args['mimetype']);
            if ($mime == 'text/calendar' || $mime == 'text/ical' || $mime == 'application/ics') {
                toolbox_logger::step('attachments', 'detach_calendar_skipped');
                return $args;
            }
            $plugin = $rcmail->plugins->exec_hook('toolbox_attachment_label', ['label' => '']);
            $label = $plugin['label'];
            $args['id'] = md5(session_id() . microtime());
            $filename = urlencode($args['id'] . '_' . $args['name']);
            $dest = slashify($rcmail->config->get('toolbox_detach_storage', 'attachments')) . $filename;
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
                toolbox_logger::step('attachments', 'detach_copy_complete');
            }
            else {
                toolbox_logger::error('attachments', 'detach_copy', 'storage_failed');
            }
        }
        return $args;
    }
}

// END OF FILE
