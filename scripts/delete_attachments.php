#!/usr/bin/php

<?php

/**
 * Toolbox delete attachment script
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

/**
 * Delete expired files from Toolbox's detached-attachment directory.
 *
 * Run daily with PHP CLI. The storage directory and retention period come
 * from toolbox_detach_storage and toolbox_detach_lifespan, respectively.
 * This script does not traverse subdirectories or follow symbolic links.
 */

if (PHP_SAPI !== 'cli') {
    exit("This script must be run from PHP CLI.\n");
}

define('INSTALL_PATH', __DIR__ . '/../../../');
require_once INSTALL_PATH . 'program/include/clisetup.php';

if (!$rcmail->plugins->load_plugin('toolbox', true)) {
    fwrite(STDERR, "ERROR: Could not load the Toolbox plugin.\n");
    exit(1);
}

$storage = $rcmail->config->get('toolbox_detach_storage');
$lifespan = filter_var(
    $rcmail->config->get('toolbox_detach_lifespan', 30),
    FILTER_VALIDATE_INT,
    ['options' => ['min_range' => 1]]
);

if ($lifespan === false) {
    fwrite(STDERR, "ERROR: toolbox_detach_lifespan must be a positive integer (days).\n");
    exit(1);
}

// A relative path could resolve differently in cron and in a web request.
if (!is_string($storage) || $storage === '' || !(
    str_starts_with($storage, '/')
    || preg_match('~^(?:[a-zA-Z]:[\\\\/]|\\\\\\\\)~', $storage)
)) {
    fwrite(STDERR, "ERROR: toolbox_detach_storage must be an absolute directory path.\n");
    exit(1);
}

$directory = realpath($storage);
if ($directory === false || !is_dir($directory) || dirname($directory) === $directory) {
    fwrite(STDERR, "ERROR: Attachment storage directory is missing or unsafe.\n");
    exit(1);
}

if (!is_readable($directory) || !is_writable($directory)) {
    fwrite(STDERR, "ERROR: Attachment storage directory is not readable and writable.\n");
    exit(1);
}

$cutoff = time() - $lifespan * 86400;
$deleted = 0;
$failed = 0;

try {
    $entries = new FilesystemIterator($directory, FilesystemIterator::SKIP_DOTS);
    foreach ($entries as $entry) {
        // The configured directory is dedicated to detached attachments.
        // Skip directories and links; delete only expired regular files.
        try {
            if ($entry->isLink() || !$entry->isFile() || $entry->getMTime() > $cutoff) {
                continue;
            }

            if (@unlink($entry->getPathname())) {
                ++$deleted;
            } else {
                ++$failed;
                fwrite(STDERR, "ERROR: Could not delete " . $entry->getFilename() . "\n");
            }
        } catch (Throwable $e) {
            ++$failed;
            fwrite(STDERR, "ERROR: Could not process an attachment: " . $e->getMessage() . "\n");
        }
    }
} catch (Throwable $e) {
    fwrite(STDERR, "ERROR: Could not scan attachment storage: " . $e->getMessage() . "\n");
    exit(1);
}

$summary = "ATTACHMENTS: deleted {$deleted} expired file(s); {$failed} error(s)";
if ((int) $rcmail->config->get('toolbox_script_loglevel', 2) > 0) {
    $rcmail->write_log(
        $rcmail->config->get('toolbox_script_logfile', 'toolbox.script'),
        $summary
    );
}

echo $summary . "\n";
exit($failed > 0 ? 1 : 0);

// END OF FILE
