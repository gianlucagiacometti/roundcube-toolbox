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

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function init]: 'attachments' tool active and loaded");
        }

        $this->plugin->add_hook('attachment_upload', [$this, 'detach_attachment']);
        $this->plugin->add_hook('message_compose', [$this, 'remove_session']);
        $this->plugin->add_hook('startup', [$this, 'download_detached']);
        $this->plugin->add_hook('toolbox_attachment_label', [$this, 'attachment_label']);

        if (rcube_utils::get_input_value('_action', rcube_utils::INPUT_GPC) == 'compose') {
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, 'STEP in [function init]:modify hint in compose window');
            }
            $this->plugin->add_hook('template_object_composeattachmentform', [$this, 'update_hint']);
        }
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

    /**
     * Reset the aggregate attachment size when a compose session begins.
     *
     * @param array $args Hook arguments.
     * @return array Unmodified hook arguments.
     */
    public function remove_session($args) {
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function remove_session]: remove attachments_total value from session");
        }
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
        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [function attachment_label]: set the label in the html message");
        }
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
            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [function upload_hint]: use hook to upload the content of the hint");
            }
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
            }
        }
        return $args;
    }
}

// END OF FILE

