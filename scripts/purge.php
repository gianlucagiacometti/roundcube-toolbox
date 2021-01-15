#!/usr/bin/php

<?php

/**
 * Toolbox purge script
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

define('INSTALL_PATH', __DIR__ . '/../../../');
require_once INSTALL_PATH . 'program/include/clisetup.php';

$rcmail->plugins->load_plugin('toolbox', true);

$dsnw_postfix = $rcmail->config->get('toolbox_postfix_dsnw');
$dsnr_postfix = $rcmail->config->get('toolbox_postfix_dsnr') != '' ? $rcmail->config->get('toolbox_postfix_dsnr') : $rcmail->config->get('toolbox_postfix_dsnw');
$mailbox_table_name = $rcmail->config->get('toolbox_postfix_sql_mailbox_table_name');
$username_field_in_mailbox = $rcmail->config->get('toolbox_postfix_sql_username_field_in_mailbox');
$domain_field_in_mailbox = $rcmail->config->get('toolbox_postfix_sql_domain_field_in_mailbox');
$local_part_field_in_mailbox = $rcmail->config->get('toolbox_postfix_sql_local_part_field_in_mailbox');
$maildir_field_in_mailbox = $rcmail->config->get('toolbox_postfix_sql_maildir_field_in_mailbox');
$domain_table_name = $rcmail->config->get('toolbox_postfix_sql_domain_table_name');
$domain_field_in_domain = $rcmail->config->get('toolbox_postfix_sql_domain_field_in_domain');
$dsnw_roundcube = $rcmail->config->get('toolbox_roundcube_dsnw');
$dsnr_roundcube = $rcmail->config->get('toolbox_roundcube_dsnr') != '' ? $rcmail->config->get('toolbox_roundcube_dsnr') : $rcmail->config->get('toolbox_roundcube_dsnw');
$loglevel = $rcmail->config->get('toolbox_script_loglevel', 2);

if ($loglevel > 0) {
    $logfile = $rcmail->config->get('toolbox_script_logfile', 'toolbox.script.log');
    $rcmail->write_log($logfile, "PURGE: operation started");
}

if ($loglevel > 3) {
    $rcmail->write_log($logfile, "STEP: connecting to postfix database");
}
$db_postfix = rcube_db::factory($dsnw_postfix, $dsnr_postfix, false);
$db_postfix->set_debug((bool) rcube::get_instance()->config->get('sql_debug'));
$db_postfix->db_connect('r');

// check DB connections and exit on failure
if ($err_str = $db_postfix->is_error()) {
    if ($loglevel > 1) {
        $rcmail->write_log($logfile, "ERROR: cannoct connect to database postfix: " . $err_str);
    }
    exit($err_str . "\n");
}

if ($loglevel > 3) {
    $rcmail->write_log($logfile, "STEP: connecting to roundcube database");
}
$db_roundcube = rcube_db::factory($dsnw_roundcube, $dsnr_roundcube, false);
$db_roundcube->set_debug((bool) rcube::get_instance()->config->get('sql_debug'));
$db_roundcube->db_connect('r');

// check DB connections and exit on failure
if ($err_str = $db_roundcube->is_error()) {
    if ($loglevel > 1) {
        $rcmail->write_log($logfile, "ERROR: cannoct connect to database roundcube: " . $err_str);
    }
    exit($err_str . "\n");
}

if ($loglevel > 2) {
    $rcmail->write_log($logfile, "SQL: SELECT `{$username_field_in_mailbox}`, `{$domain_field_in_mailbox}`, `{$local_part_field_in_mailbox}`, `{$maildir_field_in_mailbox}` FROM `{$mailbox_table_name}`;");
}
$sql_result = $db_postfix->query(
    "SELECT `{$username_field_in_mailbox}`, `{$domain_field_in_mailbox}`, `{$local_part_field_in_mailbox}`, `{$maildir_field_in_mailbox}` FROM `{$mailbox_table_name}`;"
    );
if ($err_str = $db_postfix->is_error()) {
    if ($loglevel > 1) {
        $rcmail->write_log($logfile, "ERROR: cannot read mailboxes from database: " . $err_str);
    }
    exit($err_str . '\n');
}

$mailboxes = [];
while ($sql_result && ($sql_arr = $db_postfix->fetch_assoc($sql_result))) {
    $mailboxes[] = [
        'username' => $sql_arr[$username_field_in_mailbox],
        'domain' => $sql_arr[$domain_field_in_mailbox],
        'local_part' => $sql_arr[$local_part_field_in_mailbox],
        'maildir' => $sql_arr[$maildir_field_in_mailbox],
    ];
}
if ($loglevel > 2) {
    $rcmail->write_log($logfile, "SQL: " . count($mailboxes) . " mailboxes found");
}


if ($loglevel > 3) {
    $rcmail->write_log($logfile, "STEP: iterate through mailboxes");
}
foreach ($mailboxes as $mailbox) {

    if ($loglevel > 0) {
        $rcmail->write_log($logfile, 'User ' .  $mailbox['local_part'] . '@' . $mailbox['domain']);
    }

    // load user prefs
    if ($loglevel > 2) {
        $rcmail->write_log($logfile, "SQL: SELECT `username`, `preferences` FROM `users` WHERE `username` = '{$mailbox['username']}';");
    }
    $sql_result = $db_roundcube->query(
        "SELECT `username`, `preferences` FROM `users` WHERE `username` = '{$mailbox['username']}';"
        );
    if ($err_str = $db_roundcube->is_error()) {
        if ($loglevel > 1) {
            $rcmail->write_log($logfile, 'ERROR: cannot read preferences from database for user ' . $mailbox['username'] . ': ' . $err_str);
        }
        exit($err_str . '\n');
    }
    $preferences = [];
    while ($sql_result && ($sql_arr = $db_postfix->fetch_assoc($sql_result))) {
        $preferences = (array)unserialize($sql_arr['preferences']);
    }
    if (($loglevel > 2) && !empty($preferences)) {
        $rcmail->write_log($logfile, "SQL: user's preferences found");
    }
    $purge_trash = isset($preferences['toolbox_purge_trash']) ? $preferences['toolbox_purge_trash'] : 'NULL';
    $purge_junk = isset($preferences['toolbox_purge_junk']) ? $preferences['toolbox_purge_junk'] : 'NULL';

    if (($purge_trash == 'NULL' || $purge_junk == 'NULL')) {
        if ($loglevel > 2) {
            $rcmail->write_log($logfile, "SQL: SELECT `purge_trash`, `purge_junk` FROM `toolbox_customise_domains` WHERE `domain_name` = '{$mailbox['domain']}';");
        }
        $sql_result = $db_roundcube->query(
            "SELECT `purge_trash`, `purge_junk` FROM `toolbox_customise_domains` WHERE `domain_name` = ?;",
            $mailbox['domain']
            );
        if ($err = $db_roundcube->is_error()) {
            if ($loglevel > 1) {
                $rcmail->write_log($logfile, 'ERROR: cannot read customised domain data for ' . $mailbox['domain'] . ': ' . $err);
            }
            exit($err . '\n');
        }
        $sql_arr = $db_roundcube->fetch_assoc($sql_result);
        if ($purge_trash == 'NULL') {
            $purge_trash = !empty($sql_arr['purge_trash']) ? $sql_arr['purge_trash'] : 0;
        }
        if ($purge_junk == 'NULL') {
            $purge_junk = !empty($sql_arr['purge_junk']) ? $sql_arr['purge_junk'] : 0;
        }
    }

    $purge = ['Trash' => intval($purge_trash), 'Junk' => intval($purge_junk)];
    foreach ($purge as $key => $val) {
        $count = 0;
        $last = $val > 0 ? ($val == 1 ? ' 1 day' : ' ' . strval($val) . ' days') : 'ever';
        $folder = $rcmail->config->get('toolbox_purge_maildir_path') . '/' . $mailbox['domain'] . '/' . $mailbox['local_part'] . '/Maildir/.' . $key . '/cur';
        if (!file_exists($folder)) {
            $rcmail->write_log($logfile, 'WARNNING: ' . $key . ' folder does not exist! (' . $folder . ')');
        }
        if ($loglevel > 0) {
            $rcmail->write_log($logfile, '     keeps messages in ' . $key  . ' folder for' . $last);
        }
        if ($val > 0) {
            $files = array_diff(scandir($folder), ['..', '.']);
            if (!empty($files)) {
                foreach ($files as $file) {
                    if (file_exists($folder . '/' . $file) && ((time() - filemtime($folder . '/' . $file)) > ($val * 60 * 60 * 24))) {
                        if ($loglevel > 0) {
                            $rcmail->write_log($logfile, '     purging file ' . $file . ' (dated ' . date('Y-m-d H:i:s', filemtime($folder . '/' . $file)) . ') from ' . $key . ' folder of user ' .  $mailbox['local_part'] . '@' . $mailbox['domain']);
                        }
                        unlink($folder . '/' . $file);
                        $count++;
                    }
                }
                if (($count > 0) && $loglevel > 0) {
                    $rcmail->write_log($logfile, '     ' . $count . ' messages purged from ' . $key . ' folder of user ' .  $mailbox['local_part'] . '@' . $mailbox['domain']);
                }
            }
        }
    }

}

if ($loglevel > 0) {
    $rcmail->write_log($logfile, "PURGE: operation ended\n");
}

// END OF FILE
