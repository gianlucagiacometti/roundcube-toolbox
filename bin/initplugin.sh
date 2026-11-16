#!/usr/bin/env php
<?php

/**
 * Prepare Toolbox's writable directories for a standard Roundcube installation.
 *
 * Customise uses plugins/toolbox/tmp; detached attachments belong in
 * Roundcube's public_html/attachments, not in the plugin directory.
 */

$plugin_path = realpath(__DIR__ . '/..');
$public_html_path = realpath(__DIR__ . '/../../../public_html');

if ($plugin_path === false || $public_html_path === false) {
    fwrite(STDERR, "ERROR: Cannot locate Toolbox or Roundcube public_html. No directories created.\n");
    exit(1);
}

$directories = [
    $plugin_path . '/tmp',
    $public_html_path . '/attachments',
];

foreach ($directories as $directory) {
    if (!is_dir($directory) && !mkdir($directory, 0777)) {
        fwrite(STDERR, "ERROR: Cannot create directory: {$directory}\n");
        exit(1);
    }

    if (!chmod($directory, 0777)) {
        fwrite(STDERR, "ERROR: Cannot set permissions on directory: {$directory}\n");
        exit(1);
    }
}

// END OF FILE

