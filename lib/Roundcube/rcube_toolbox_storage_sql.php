<?php

/**
 * Toolbox storage class
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

require_once __DIR__ . '/../Toolbox/logger.php';

class rcube_toolbox_storage_sql extends rcube_toolbox_storage
{
    private $db;
    private $postfix_dsnw;
    private $postfix_dsnr;
    private $postfix_db_persistent;
    private $postfix_sql_alias_table_name;
    private $postfix_sql_address_field_in_alias;
    private $postfix_sql_domain_field_in_alias;
    private $postfix_sql_goto_field_in_alias;
    private $postfix_sql_created_field_in_alias;
    private $postfix_sql_modified_field_in_alias;
    private $postfix_sql_active_field_in_alias;
    private $postfix_sql_vacation_table_name;
    private $postfix_sql_email_field_in_vacation;
    private $postfix_sql_domain_field_in_vacation;
    private $postfix_sql_subject_field_in_vacation;
    private $postfix_sql_body_field_in_vacation;
    private $postfix_sql_active_field_in_vacation;
    private $postfix_sql_activefrom_field_in_vacation;
    private $postfix_sql_activeuntil_field_in_vacation;
    private $postfix_sql_interval_time_field_in_vacation;
    private $postfix_sql_modified_field_in_vacation;
    private $postfix_sql_vacation_notification_table_name;
    private $postfix_sql_on_vacation_field_in_vacation_notification;
    private $postfix_sql_mailbox_table_name;
    private $postfix_sql_username_field_in_mailbox;
    private $postfix_sql_domain_table_name;
    private $postfix_sql_domain_field_in_domain;
    private $postfix_sql_domain_admins_table_name;
    private $postfix_sql_username_field_in_domain_admins;
    private $postfix_sql_domain_field_in_domain_admins;
    private $postfixadmin_vacation_domain;
    private $roundcube_dsnw;
    private $roundcube_dsnr;
    private $roundcube_db_persistent;
    private $vacation_dateformat;
    private $domain_admins;
    private $use_postfixadmin_domain_admins;

    protected $helper;

    public function __construct($config, $tool)
    {

        $this->tool = $tool;

        $this->domain_admins = $config->get('toolbox_domain_admins');
        $this->use_postfixadmin_domain_admins = $config->get('toolbox_use_postfixadmin_domain_admins');

        $this->postfix_dsnw = $config->get('toolbox_postfix_dsnw');
        $this->postfix_dsnr = $config->get('toolbox_postfix_dsnr') != '' ? $config->get('toolbox_postfix_dsnr') : $config->get('toolbox_postfix_dsnw');
        $this->postfix_db_persistent = $config->get('toolbox_postfix_db_persistent');
        $this->postfix_sql_alias_table_name = $config->get('toolbox_postfix_sql_alias_table_name');
        $this->postfix_sql_address_field_in_alias = $config->get('toolbox_postfix_sql_address_field_in_alias');
        $this->postfix_sql_domain_field_in_alias = $config->get('toolbox_postfix_sql_domain_field_in_alias');
        $this->postfix_sql_goto_field_in_alias = $config->get('toolbox_postfix_sql_goto_field_in_alias');
        $this->postfix_sql_created_field_in_alias = $config->get('toolbox_postfix_sql_created_field_in_alias');
        $this->postfix_sql_modified_field_in_alias = $config->get('toolbox_postfix_sql_modified_field_in_alias');
        $this->postfix_sql_active_field_in_alias = $config->get('toolbox_postfix_sql_active_field_in_alias');
        $this->postfix_sql_vacation_table_name = $config->get('toolbox_postfix_sql_vacation_table_name');
        $this->postfix_sql_email_field_in_vacation = $config->get('toolbox_postfix_sql_email_field_in_vacation');
        $this->postfix_sql_domain_field_in_vacation = $config->get('toolbox_postfix_sql_domain_field_in_vacation');
        $this->postfix_sql_subject_field_in_vacation = $config->get('toolbox_postfix_sql_subject_field_in_vacation');
        $this->postfix_sql_body_field_in_vacation = $config->get('toolbox_postfix_sql_body_field_in_vacation');
        $this->postfix_sql_active_field_in_vacation = $config->get('toolbox_postfix_sql_active_field_in_vacation');
        $this->postfix_sql_activefrom_field_in_vacation = $config->get('toolbox_postfix_sql_activefrom_field_in_vacation');
        $this->postfix_sql_activeuntil_field_in_vacation = $config->get('toolbox_postfix_sql_activeuntil_field_in_vacation');
        $this->postfix_sql_interval_time_field_in_vacation = $config->get('toolbox_postfix_sql_interval_time_field_in_vacation');
        $this->postfix_sql_modified_field_in_vacation = $config->get('toolbox_postfix_sql_modified_field_in_vacation');
        $this->postfix_sql_vacation_notification_table_name = $config->get('toolbox_postfix_sql_vacation_notification_table_name');
        $this->postfix_sql_on_vacation_field_in_vacation_notification = $config->get('toolbox_postfix_sql_on_vacation_field_in_vacation_notification');
        $this->postfix_sql_mailbox_table_name = $config->get('toolbox_postfix_sql_mailbox_table_name');
        $this->postfix_sql_username_field_in_mailbox = $config->get('toolbox_postfix_sql_username_field_in_mailbox');
        $this->postfix_sql_domain_table_name = $config->get('toolbox_postfix_sql_domain_table_name');
        $this->postfix_sql_domain_field_in_domain = $config->get('toolbox_postfix_sql_domain_field_in_domain');
        $this->postfix_sql_domain_admins_table_name = $config->get('toolbox_postfix_sql_domain_admins_table_name');
        $this->postfix_sql_username_field_in_domain_admins = $config->get('toolbox_postfix_sql_username_field_in_domain_admins');
        $this->postfix_sql_domain_field_in_domain_admins = $config->get('toolbox_postfix_sql_domain_field_in_domain_admins');

        $this->postfixadmin_vacation_domain = $config->get('toolbox_postfixadmin_vacation_domain');

        $this->roundcube_dsnw = $config->get('toolbox_roundcube_dsnw');
        $this->roundcube_dsnr = $config->get('toolbox_roundcube_dsnr') != '' ? $config->get('toolbox_roundcube_dsnr') : $config->get('toolbox_roundcube_dsnw');
        $this->roundcube_db_persistent = $config->get('toolbox_roundcube_db_persistent');

        $this->vacation_dateformat = $config->get('toolbox_vacation_dateformat');

        $this->loglevel = $config->get('toolbox_loglevel', 1);
        if ($this->loglevel > 0) {
            $this->logfile = $config->get('toolbox_logfile', 'toolbox.log');
        }

    }

    public function is_domain_admin($user)
    {

        // Domain-specific administration requires a username with a domain.
        $parts = explode('@', $user);
        if (!isset($parts[1]) || $parts[0] === '' || $parts[1] === '') {
            return false;
        }

        $domains = [];

        // add postfixadmin domain admins
        if ($this->use_postfixadmin_domain_admins) {

            if ($this->loglevel > 2) {
                toolbox_logger::step('storage.sql', 'domain_admins_db_connect');
            }
            $this->_db_connect('postfix', 'r');

            if ($this->loglevel > 1) {
                toolbox_logger::sql('storage.sql', 'SELECT configured_domain FROM configured_domain_admins WHERE configured_username = ?');
            }
            $sql_result = $this->db->query(
                "SELECT `{$this->postfix_sql_domain_field_in_domain_admins}` FROM `{$this->postfix_sql_domain_admins_table_name}` WHERE `{$this->postfix_sql_username_field_in_domain_admins}` = ?;",
                $user);
            if ($this->loglevel > 0) {
                if ($err_str = $this->db->is_error()) {
                    toolbox_logger::error('storage.sql', 'domain_admins_load', 'query_failed');
                }
            }

            while ($sql_result && ($sql_arr = $this->db->fetch_assoc($sql_result))) {
                $domains[] = $sql_arr[$this->postfix_sql_domain_field_in_domain_admins];
            }
            if ($this->loglevel > 1) {
                toolbox_logger::step('storage.sql', 'domain_admins_load_complete');
            }

        }

        // add static domain admins avoiding duplicates
        foreach ($this->domain_admins as $key => $val) {
            if ($key == $user) {
                if (($val == 'ALL') && !in_array($val, $domains)) {
                    $domains = ['ALL'];
                }
                else {
                    foreach (explode(',', $val) as $domain) {
                        if (!in_array($domain, $domains)) {
                            $domains[] = $domain;
                        }
                    }
                }
            }
        }

        return in_array($parts[1], $domains) || in_array('ALL', $domains) ? true : false;

    }

    public function load_customised_config($user, $skin)
    {
        $parts = explode('@', $user);
        if (!isset($parts[1]) || $parts[0] === '' || $parts[1] === '') {
            // Never use an absent domain to look up another domain's settings.
            return [];
        }

        if ($this->loglevel > 2) {
            toolbox_logger::step('storage.sql', 'customise_config_db_connect');
        }
        $this->_db_connect('roundcube', 'r');

        if ($this->loglevel > 1) {
            toolbox_logger::sql('storage.sql', 'SELECT configured_skin_fields FROM toolbox_customise_skins_view WHERE domain_name = ? AND skin = ?');
        }
        $sql_result = $this->db->query(
            "SELECT
                `domain_name`,
                `skin`,
                `customise_blankpage`,
                `blankpage_type`,
                `blankpage_image`,
                `blankpage_url`,
                `blankpage_custom`,
                `customise_css`,
                `additional_css`,
                `customise_logo`,
                `customised_logo`
            FROM `toolbox_customise_skins_view`
            WHERE `domain_name` = ?
            AND `skin` = ?;",
            $parts[1],
            $skin);
        if ($this->loglevel > 0) {
            if ($err_str = $this->db->is_error()) {
                toolbox_logger::error('storage.sql', 'customise_config_load', 'query_failed');
            }
            elseif ($this->loglevel > 1) {
                toolbox_logger::step('storage.sql', 'customise_config_load_complete');
            }
        }

        $config = [];
        if ($this->db->affected_rows() > 0) {
            while ($sql_result && ($sql_arr = $this->db->fetch_assoc($sql_result))) {
                $config['customise_blankpage'] = $sql_arr['customise_blankpage'];
                $config['blankpage_type'] = $sql_arr['blankpage_type'];
                $config['blankpage_image'] = $sql_arr['blankpage_image'];
                $config['blankpage_url'] = $sql_arr['blankpage_url'];
                $config['blankpage_custom'] = $sql_arr['blankpage_custom'];
                $config['customise_css'] = $sql_arr['customise_css'];
                $config['additional_css'] = $sql_arr['additional_css'];
                $config['customise_logo'] = $sql_arr['customise_logo'];
                $config['customised_logo'] = $sql_arr['customised_logo'];
            }
        }

        return $config;
    }

    public function load_tool_data($user, $attrib='')
    {
        $settings = [];
        $parts = explode('@', $user);
        if ((!isset($parts[1]) || $parts[0] === '' || $parts[1] === '')
            && in_array($this->tool, ['aliases', 'vacation', 'customise'], true)) {
            return $settings;
        }

        if ($this->loglevel > 2) {
            toolbox_logger::step('storage.sql', 'load_start');
        }
        switch ($this->tool) {

            case 'aliases':

                if ($this->loglevel > 2) {
                    toolbox_logger::step('storage.sql', 'aliases_db_connect');
                }
                $this->_db_connect('postfix', 'r');

                switch ($attrib) {

                    case 'allaliases':
                        // The SQL query used to select all domain aliases but user's.
                        // Need to avoid alias duplicates in the domain.
                        if ($this->loglevel > 1) {
                            toolbox_logger::sql('storage.sql', 'SELECT configured_alias_fields FROM configured_aliases WHERE domain = ? AND destination != ?');
                        }
                        $sql_result = $this->db->query(
                            "SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_domain_field_in_alias}` = ? AND `{$this->postfix_sql_goto_field_in_alias}` != ?;",
                            $parts[1],
                            $user);
                        if ($this->loglevel > 0) {
                            if ($err_str = $this->db->is_error()) {
                                toolbox_logger::error('storage.sql', 'aliases_load_domain', 'query_failed');
                            }
                            elseif ($this->loglevel > 1) {
                                toolbox_logger::step('storage.sql', 'aliases_load_domain_complete');
                            }
                        }
                        break;

                    default:
                        // The SQL query used to select all mailbox aliases
                        // default mailbox alias to itself is excluded and managed by forward tool
                        if ($this->loglevel > 1) {
                            toolbox_logger::sql('storage.sql', 'SELECT configured_alias_fields FROM configured_aliases WHERE destination = ? AND domain = ? AND address != ?');
                        }
                        if (rcube::get_instance()->config->get('toolbox_aliases_multiple_domains')) {
                            $sql_result = $this->db->query(
                                "SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_goto_field_in_alias}` = ? AND `{$this->postfix_sql_address_field_in_alias}` != ?;",
                                $user,
                                $user);
                        } else {
                            $sql_result = $this->db->query(
                                "SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_goto_field_in_alias}` = ? AND `{$this->postfix_sql_domain_field_in_alias}` = ? AND `{$this->postfix_sql_address_field_in_alias}` != ?;",
                                $user,
                                $parts[1],
                                $user);
                        }
                        if ($this->loglevel > 0) {
                            if ($err_str = $this->db->is_error()) {
                                toolbox_logger::error('storage.sql', 'aliases_load_user', 'query_failed');
                            }
                            elseif ($this->loglevel > 1) {
                                toolbox_logger::step('storage.sql', 'aliases_load_user_complete');
                            }
                        }
                        break;

                }

                if ($this->db->affected_rows() > 0) {
                    while ($sql_result && ($sql_arr = $this->db->fetch_assoc($sql_result))) {
                        $settings['aliases'][] = $sql_arr;
                    }
                }

                break;

            case 'forward':

                if ($this->loglevel > 2) {
                    toolbox_logger::step('storage.sql', 'forward_db_connect');
                }
                $this->_db_connect('postfix', 'r');

                if ($this->loglevel > 1) {
                    toolbox_logger::sql('storage.sql', 'SELECT configured_alias_fields FROM configured_aliases WHERE address = ?');
                }
                $sql_result = $this->db->query(
                    "SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ?;",
                    $user);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        toolbox_logger::error('storage.sql', 'forward_load', 'query_failed');
                    }
                    elseif ($this->loglevel > 1) {
                        toolbox_logger::step('storage.sql', 'forward_load_complete');
                    }
                }

                if ($this->db->affected_rows() > 0) {
                    while ($sql_result && ($sql_arr = $this->db->fetch_assoc($sql_result))) {
                        $settings['address'] = $sql_arr[$this->postfix_sql_address_field_in_alias];
                        $settings['goto'] = $sql_arr[$this->postfix_sql_goto_field_in_alias];
                    }
                }

                break;

            case 'vacation':

                if ($this->loglevel > 2) {
                    toolbox_logger::step('storage.sql', 'vacation_db_connect');
                }
                $this->_db_connect('postfix', 'r');

                if ($this->loglevel > 2) {
                    toolbox_logger::step('storage.sql', 'vacation_date_format_prepare');
                }
                $activefrom = $this->helper->to_char($this->postfix_sql_activefrom_field_in_vacation, $this->vacation_dateformat);
                $activeuntil = $this->helper->to_char($this->postfix_sql_activeuntil_field_in_vacation, $this->vacation_dateformat);

                if ($this->loglevel > 1) {
                    toolbox_logger::sql('storage.sql', 'SELECT configured_vacation_fields FROM configured_vacation WHERE email = ? AND domain = ?');
                }
                $sql_result = $this->db->query(
                    "SELECT `{$this->postfix_sql_subject_field_in_vacation}`, `{$this->postfix_sql_body_field_in_vacation}`, `{$this->postfix_sql_active_field_in_vacation}`, $activefrom, $activeuntil, `{$this->postfix_sql_interval_time_field_in_vacation}` FROM `{$this->postfix_sql_vacation_table_name}` WHERE `{$this->postfix_sql_email_field_in_vacation}` = ? AND `{$this->postfix_sql_domain_field_in_vacation}` = ?;",
                    $user,
                    $parts[1]);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        toolbox_logger::error('storage.sql', 'vacation_load', 'query_failed');
                    }
                    elseif ($this->loglevel > 1) {
                        toolbox_logger::step('storage.sql', 'vacation_load_complete');
                    }
                }

                while ($sql_result && ($sql_arr = $this->db->fetch_assoc($sql_result))) {
                    $settings['subject'] = $sql_arr[$this->postfix_sql_subject_field_in_vacation];
                    $settings['body'] = $sql_arr[$this->postfix_sql_body_field_in_vacation];
                    $settings['active'] = $sql_arr[$this->postfix_sql_active_field_in_vacation];
                    $settings['activefrom'] = $sql_arr[$this->postfix_sql_activefrom_field_in_vacation];
                    $settings['activeuntil'] = $sql_arr[$this->postfix_sql_activeuntil_field_in_vacation];
                    $settings['interval_time'] = $sql_arr[$this->postfix_sql_interval_time_field_in_vacation];
                }

                break;

            case 'customise':

                // Read domain defaults using the query result itself. SELECT rowCount()
                // is not reliable across the database drivers supported by Roundcube.
                $this->_db_connect('roundcube', 'r');
                $sql_result = $this->db->query(
                    "SELECT `purge_trash`, `purge_junk`
                     FROM `toolbox_customise_domains`
                     WHERE `domain_name` = ?;",
                    $parts[1]
                );

                if (!$sql_result || ($err_str = $this->db->is_error())) {
                    if ($this->loglevel > 0) {
                        toolbox_logger::error('storage.sql', 'customise_domain_load', 'query_failed');
                    }
                    $settings['trash'] = 0;
                    $settings['junk'] = 0;
                } else {
                    $domain_row = $this->db->fetch_assoc($sql_result);
                    $settings['trash'] = $domain_row ? (int) $domain_row['purge_trash'] : 0;
                    $settings['junk'] = $domain_row ? (int) $domain_row['purge_junk'] : 0;
                }

                // Likewise, consume skin rows instead of testing affected_rows().
                $sql_result = $this->db->query(
                    "SELECT
                        `domain_name`, `skin`, `customise_blankpage`, `blankpage_type`,
                        `blankpage_image`, `blankpage_url`, `blankpage_custom`,
                        `customise_css`, `additional_css`, `customise_logo`, `customised_logo`
                     FROM `toolbox_customise_skins_view`
                     WHERE `domain_name` = ?;",
                    $parts[1]
                );

                $settings['skins'] = [];
                if (!$sql_result || ($err_str = $this->db->is_error())) {
                    if ($this->loglevel > 0) {
                        toolbox_logger::error('storage.sql', 'customise_skin_load', 'query_failed');
                    }
                } else {
                    while ($sql_arr = $this->db->fetch_assoc($sql_result)) {
                        $settings['skins'][$sql_arr['skin']] = [
                            'customise_blankpage' => $sql_arr['customise_blankpage'],
                            'blankpage_type' => $sql_arr['blankpage_type'],
                            'blankpage_image' => $sql_arr['blankpage_image'],
                            'blankpage_url' => $sql_arr['blankpage_url'],
                            'blankpage_custom' => $sql_arr['blankpage_custom'],
                            'customise_css' => $sql_arr['customise_css'],
                            'additional_css' => $sql_arr['additional_css'],
                            'customise_logo' => $sql_arr['customise_logo'],
                            'customised_logo' => $sql_arr['customised_logo'],
                        ];
                    }
                }

                break;

        }

        return $settings;

    }

    public function delete_tool_data($user, $data)
    {

        $result = true;

        $parts = explode('@', $user);
        if (!isset($parts[1]) || $parts[0] === '' || $parts[1] === '') {
            return false;
        }

        if ($this->loglevel > 2) {
            toolbox_logger::step('storage.sql', 'delete_start');
        }
        switch ($data['section']) {

            case 'aliases':

                if ($this->loglevel > 2) {
                    toolbox_logger::step('storage.sql', 'aliases_delete_db_connect');
                }
                $this->_db_connect('postfix', 'w');

                $address = $this->normalise_alias_address($user, (string) ($data['aliasname'] ?? ''));
                if ($address === null) {
                    $result = false;
                    break;
                }

                if ($this->loglevel > 1) {
                    toolbox_logger::sql('storage.sql', 'DELETE FROM configured_aliases WHERE address = ? AND destination = ?');
                }
                $sql_result = $this->db->query(
                    "DELETE FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_goto_field_in_alias}` = ?;",
                    $address,
                    $user);
                if (!$sql_result || $this->db->is_error()) {
                    toolbox_logger::error('storage.sql', 'aliases_delete', 'query_failed');
                    $result = false;
                    break;
                }
                if (!$this->db->affected_rows()) {
                    toolbox_logger::error('storage.sql', 'aliases_delete', 'no_rows_changed');
                    $result = false;
                    break;
                }
                toolbox_logger::step('storage.sql', 'aliases_delete_complete');

                break;

        }

        return $result;

    }

    public function toggle_tool_data($user, $data)
    {

        $result = true;

        $settings = $data['new_settings'];
        $parts = explode('@', $user);
        if (!isset($parts[1]) || $parts[0] === '' || $parts[1] === '') {
            return false;
        }

        if ($this->loglevel > 2) {
            toolbox_logger::step('storage.sql', 'toggle_start');
        }
        switch ($data['section']) {

            case 'aliases':

                if ($this->loglevel > 2) {
                    toolbox_logger::step('storage.sql', 'aliases_toggle_db_connect');
                }
                $this->_db_connect('postfix', 'w');

                $address = $this->normalise_alias_address($user, (string) ($settings['aliasname'] ?? ''));
                if ($address === null) {
                    $result = false;
                    break;
                }

                if ($this->loglevel > 1) {
                    toolbox_logger::sql('storage.sql', 'UPDATE configured_aliases SET enabled = NOT enabled WHERE address = ? AND destination = ?');
                }
                $sql_result = $this->db->query(
                    "UPDATE `{$this->postfix_sql_alias_table_name}` SET `{$this->postfix_sql_active_field_in_alias}` = NOT `{$this->postfix_sql_active_field_in_alias}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_goto_field_in_alias}` = ?;",
                    $address,
                    $user);
                if (!$sql_result || $this->db->is_error()) {
                    toolbox_logger::error('storage.sql', 'aliases_toggle', 'query_failed');
                    $result = false;
                    break;
                }
                if (!$this->db->affected_rows()) {
                    toolbox_logger::error('storage.sql', 'aliases_toggle', 'no_rows_changed');
                    $result = false;
                    break;
                }
                toolbox_logger::step('storage.sql', 'aliases_toggle_complete');

                break;

        }

        return $result;

    }

    public function save_tool_data($user, $data)
    {

        $result = true;

        $settings = $data['new_settings'];
        $parts = explode('@', $user);
        if ((!isset($parts[1]) || $parts[0] === '' || $parts[1] === '')
            && in_array($data['section'] ?? '', ['aliases', 'forward', 'vacation', 'customise'], true)) {
            // Refuse domain-specific writes without a valid username domain.
            return false;
        }

        if ($this->loglevel > 2) {
            toolbox_logger::step('storage.sql', 'save_start');
        }
        switch ($data['section']) {

            case 'aliases':

                // Validate again at write time: AJAX validation can be bypassed.
                // Only configured boolean SQL literals are allowed in the INSERT.
                $active = $settings['main']['active'] ?? null;
                if (!in_array($active, ['true', 'false'], true)) {
                    $result = false;
                    break;
                }
                $aliasname = (string) ($settings['main']['aliasname'] ?? '');
                $status = $this->alias_creation_error($user, $aliasname, 'w');
                if ($status !== null) {
                    if ($this->loglevel > 0) {
                        toolbox_logger::error('storage.sql', 'aliases_create', 'request_rejected');
                    }
                    $result = false;
                    break;
                }
                $address = $this->normalise_alias_address($user, $aliasname);
                $address_domain = substr(strrchr($address, '@'), 1);

                if ($this->loglevel > 1) {
                    toolbox_logger::sql('storage.sql', 'INSERT INTO configured_aliases VALUES (?, ?, ?, ...)');
                }
                // Boolean literal is strictly allowlisted above; other values are bound.
                $sql_result = $this->db->query(
                    "INSERT INTO `{$this->postfix_sql_alias_table_name}` (`{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_domain_field_in_alias}`, `{$this->postfix_sql_created_field_in_alias}`, `{$this->postfix_sql_modified_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}`) VALUES (?, ?, ?, {$this->db->now()}, {$this->db->now()}, {$active});",
                    $address,
                    $user,
                    $address_domain);
                if (!$sql_result || $this->db->is_error()) {
                    toolbox_logger::error('storage.sql', 'aliases_create', 'query_failed');
                    $result = false;
                    break;
                }
                if (!$this->db->affected_rows()) {
                    toolbox_logger::error('storage.sql', 'aliases_create', 'no_rows_changed');
                    $result = false;
                    break;
                }
                toolbox_logger::step('storage.sql', 'aliases_create_complete');

                break;

            case 'forward':

                if ($this->loglevel > 2) {
                    toolbox_logger::step('storage.sql', 'forward_save_db_connect');
                }
                $this->_db_connect('postfix', 'w');

                $autoreply = $parts[0] . '#' . $parts[1] . '@' . $this->postfixadmin_vacation_domain;

                $settings['main']['modified'] = $this->db->now();

                $addresses = [];
                foreach ($settings['main']['addresses'] as $address) {
                    if ($address['value'] != '') {
                        $addresses[] = $address['value'];
                    }
                }

                // check if keepcopies is set and add user address to the goto addresses
                if (($settings['main']['keepcopies'] !== false) && !in_array($user, $addresses)) {
                    if ($this->loglevel > 2) {
                        toolbox_logger::step('storage.sql', 'forward_keep_copies_enabled');
                    }
                    $addresses[] = $user;
                }
                // if keepcopies is not set and the user address is in the list must be deleted
                elseif (($key = array_search($user, $addresses)) !== false) {
                    unset($addresses[$key]);
                }

                // check if autoreply is set and add it to the goto addresses
                if ($this->loglevel > 1) {
                    toolbox_logger::sql('storage.sql', 'SELECT configured_alias_fields FROM configured_aliases WHERE address = ?');
                }
                $sql_result = $this->db->query(
                    "SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ?;",
                    $user);
                if (!$sql_result || $this->db->is_error()) {
                    toolbox_logger::error('storage.sql', 'forward_save_read', 'query_failed');
                    $result = false;
                    break;
                }
                toolbox_logger::step('storage.sql', 'forward_save_read_complete');

                if ($this->db->affected_rows() > 0) {
                    while ($sql_result && ($sql_arr = $this->db->fetch_assoc($sql_result))) {
                        $address = $sql_arr[$this->postfix_sql_address_field_in_alias];
                        $goto = $sql_arr[$this->postfix_sql_goto_field_in_alias];
                    }
                    if (strpos($goto, $autoreply) !== false) {
                        if ($this->loglevel > 2) {
                            toolbox_logger::step('storage.sql', 'forward_autoreply_found');
                        }
                        $addresses[] = $autoreply;
                    }
                }

                // double check to avoid messing up the database
                if (empty($addresses)) {
                    if ($this->loglevel > 0) {
                        toolbox_logger::error('storage.sql', 'forward_save', 'invalid_destinations');
                    }
                    $result = false;
                    break;
                }

                // update alias table
                if ($this->loglevel > 1) {
                    toolbox_logger::sql('storage.sql', 'UPDATE configured_aliases SET destination = ? WHERE address = ? AND domain = ?');
                }
                $sql_result = $this->db->query(
                    "UPDATE {$this->postfix_sql_alias_table_name} SET `{$this->postfix_sql_goto_field_in_alias}` = ?, `{$this->postfix_sql_modified_field_in_alias}` = {$this->db->now()} WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_domain_field_in_alias}` = ?;",
                    implode(',', $addresses),
                    $user,
                    $parts[1]);
                if (!$sql_result || $this->db->is_error()) {
                    toolbox_logger::error('storage.sql', 'forward_save_write', 'query_failed');
                    $result = false;
                    break;
                }
                toolbox_logger::step('storage.sql', 'forward_save_write_complete');

                break;

            case 'vacation':

                if ($this->loglevel > 2) {
                    toolbox_logger::step('storage.sql', 'vacation_save_db_connect');
                }
                $this->_db_connect('postfix', 'w');

                $queries = [];

                // The boolean expression is a strictly whitelisted SQL literal.
                $active = $settings['main']['active'] == 1 ? 'true' : 'false';
                toolbox_logger::step('storage.sql', 'vacation_date_prepare');
                $activefrom = $this->vacation_date_parameter((string) $settings['main']['activefrom']);
                $activeuntil = $this->vacation_date_parameter((string) $settings['main']['activeuntil']);
                if ($activefrom === null || $activeuntil === null) {
                    toolbox_logger::error('storage.sql', 'vacation_save', 'invalid_date');
                    $result = false;
                    break;
                }

                // check if vacation record already exists for user and domain
                if ($this->loglevel > 1) {
                    toolbox_logger::sql('storage.sql', 'SELECT configured_vacation_fields FROM configured_vacation WHERE email = ? AND domain = ?');
                }
                $sql_result = $this->db->query(
                    "SELECT `{$this->postfix_sql_email_field_in_vacation}` FROM `{$this->postfix_sql_vacation_table_name}` WHERE `{$this->postfix_sql_email_field_in_vacation}` = ? AND `{$this->postfix_sql_domain_field_in_vacation}` = ?;",
                    $user,
                    $parts[1]);
                if (!$sql_result || $this->db->is_error()) {
                    toolbox_logger::error('storage.sql', 'vacation_save_lookup', 'query_failed');
                    $result = false;
                    break;
                }
                toolbox_logger::step('storage.sql', 'vacation_save_lookup_complete');

                // Only trusted configured identifiers and fixed SQL expressions enter
                // the statement. User-submitted dates, content and intervals are bound.
                $columns = [
                    $this->postfix_sql_active_field_in_vacation,
                    $this->postfix_sql_activefrom_field_in_vacation,
                    $this->postfix_sql_activeuntil_field_in_vacation,
                    $this->postfix_sql_interval_time_field_in_vacation,
                    $this->postfix_sql_subject_field_in_vacation,
                    $this->postfix_sql_body_field_in_vacation,
                    $this->postfix_sql_modified_field_in_vacation,
                ];
                $expressions = [
                    $active,
                    $activefrom['expression'],
                    $activeuntil['expression'],
                    '?',
                    '?',
                    '?',
                    $this->db->now(),
                ];
                $values = array_merge(
                    $activefrom['values'],
                    $activeuntil['values'],
                    [(int) $settings['main']['interval_time'], (string) $settings['main']['subject'], (string) $settings['main']['body']]
                );

                if ($this->db->affected_rows() > 0) {
                    $assignments = [];
                    foreach ($columns as $index => $column) {
                        $assignments[] = "`{$column}` = {$expressions[$index]}";
                    }
                    $queries[] = [
                        'sql' => "UPDATE `{$this->postfix_sql_vacation_table_name}` SET " . implode(', ', $assignments)
                            . " WHERE `{$this->postfix_sql_email_field_in_vacation}` = ? AND `{$this->postfix_sql_domain_field_in_vacation}` = ?;",
                        'data' => array_merge($values, [$user, $parts[1]]),
                    ];
                } else {
                    $quoted_columns = array_map(static fn($column) => "`{$column}`", $columns);
                    $queries[] = [
                        'sql' => "INSERT INTO `{$this->postfix_sql_vacation_table_name}` (`{$this->postfix_sql_email_field_in_vacation}`, `{$this->postfix_sql_domain_field_in_vacation}`, "
                            . implode(', ', $quoted_columns) . ", `created`) VALUES (?, ?, "
                            . implode(', ', $expressions) . ", {$this->db->now()});",
                        'data' => array_merge([$user, $parts[1]], $values),
                    ];
                }

                // delete old vacation notifications
                $queries[] = [
                                'sql' => "DELETE FROM {$this->postfix_sql_vacation_notification_table_name} WHERE `{$this->postfix_sql_on_vacation_field_in_vacation_notification}` = ?;",
                                'data' => [$user],
                                'type' => 'delete'
                ];

                //remove autoreply address from alias list, but keep custom aliases
                $concat = $this->db->concat('?', '?', '?', '?', '?', '?');
                $queries[] = [
                    'sql' => "UPDATE {$this->postfix_sql_alias_table_name} SET `{$this->postfix_sql_goto_field_in_alias}` = replace({$this->postfix_sql_goto_field_in_alias}, {$concat}, ''), `{$this->postfix_sql_modified_field_in_alias}` = {$this->db->now()} WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_domain_field_in_alias}` = ?;",
                    'data' => [',', $parts[0], '#', $parts[1], '@', $this->postfixadmin_vacation_domain, $user, $parts[1]],
                ];

                //add autoreply address as alias
                if ($settings['main']['active'] == 'true') {
                    $concat = $this->db->concat($this->postfix_sql_goto_field_in_alias, '?', '?', '?', '?', '?', '?');
                    $queries[] = [
                        'sql' => "UPDATE {$this->postfix_sql_alias_table_name} SET `{$this->postfix_sql_goto_field_in_alias}` = {$concat}, `{$this->postfix_sql_modified_field_in_alias}` = {$this->db->now()} WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_domain_field_in_alias}` = ? AND `{$this->postfix_sql_active_field_in_alias}` = true;",
                        'data' => [',', $parts[0], '#', $parts[1], '@', $this->postfixadmin_vacation_domain, $user, $parts[1]],
                    ];
                }

                foreach ($queries as $query) {

                    $sql_result = $this->db->query($query['sql'], ...$query['data']);

                    if (!$sql_result || $this->db->is_error()) {
                        toolbox_logger::error('storage.sql', 'vacation_save_query', 'query_failed');
                        $result = false;
                        break;
                    }
                    toolbox_logger::sql('storage.sql', 'statement_withheld_vacation_dml');
                    toolbox_logger::step('storage.sql', 'vacation_save_query_complete');

                }

                break;

            case 'customise':

                if (!$this->is_domain_admin($user) || !isset($settings['domain']) || !is_array($settings['domain'])) {
                    return false;
                }

                $this->_db_connect('roundcube', 'w');

                // Fetch the actual row; affected_rows() on SELECT is not portable.
                $sql_result = $this->db->query(
                    "SELECT `id` FROM `toolbox_customise_domains` WHERE `domain_name` = ?;",
                    $parts[1]
                );
                if (!$sql_result || ($err_str = $this->db->is_error())) {
                    if ($this->loglevel > 0) {
                        toolbox_logger::error('storage.sql', 'customise_domain_lookup', 'query_failed');
                    }
                    return false;
                }

                $domain_row = $this->db->fetch_assoc($sql_result);
                if (!$domain_row) {
                    // The schema sets a default for modified. Do not bind now()
                    // as if it were a timestamp value.
                    $sql_result = $this->db->query(
                        "INSERT INTO `toolbox_customise_domains`
                            (`domain_name`, `purge_trash`, `purge_junk`, `modified_by`)
                         VALUES (?, ?, ?, ?);",
                        $parts[1],
                        $settings['domain']['purge_trash'],
                        $settings['domain']['purge_junk'],
                        $user
                    );
                    if (!$sql_result || ($err_str = $this->db->is_error())) {
                        if ($this->loglevel > 0) {
                            toolbox_logger::error('storage.sql', 'customise_domain_insert', 'query_failed');
                        }
                        return false;
                    }

                    // Reload the ID rather than relying on driver-specific insert_id().
                    $sql_result = $this->db->query(
                        "SELECT `id` FROM `toolbox_customise_domains` WHERE `domain_name` = ?;",
                        $parts[1]
                    );
                    $domain_row = $sql_result ? $this->db->fetch_assoc($sql_result) : false;
                    if (!$domain_row || $this->db->is_error()) {
                        if ($this->loglevel > 0) {
                            toolbox_logger::error('storage.sql', 'customise_domain_insert', 'missing_insert_id');
                        }
                        return false;
                    }
                } else {
                    // An unchanged value is a successful update, even if the
                    // database reports zero affected rows.
                    $sql_result = $this->db->query(
                        "UPDATE `toolbox_customise_domains`
                         SET `purge_trash` = ?, `purge_junk` = ?,
                             `modified` = CURRENT_TIMESTAMP, `modified_by` = ?
                         WHERE `id` = ?;",
                        $settings['domain']['purge_trash'],
                        $settings['domain']['purge_junk'],
                        $user,
                        $domain_row['id']
                    );
                    if (!$sql_result || ($err_str = $this->db->is_error())) {
                        if ($this->loglevel > 0) {
                            toolbox_logger::error('storage.sql', 'customise_domain_update', 'query_failed');
                        }
                        return false;
                    }
                }

                $domain_id = $domain_row['id'];
                foreach (($settings['skins'] ?? []) as $skin => $values) {
                    $values['customise_blankpage'] = $values['customise_blankpage'] == 1 ? 'true' : 'false';
                    $values['customise_css'] = $values['customise_css'] == 1 ? 'true' : 'false';
                    $values['customise_logo'] = $values['customise_logo'] == 1 ? 'true' : 'false';

                    $sql_result = $this->db->query(
                        "SELECT `id` FROM `toolbox_customise_skins`
                         WHERE `toolbox_customise_domain_id` = ? AND `skin` = ?;",
                        $domain_id,
                        $skin
                    );
                    if (!$sql_result || ($err_str = $this->db->is_error())) {
                        if ($this->loglevel > 0) {
                            toolbox_logger::error('storage.sql', 'customise_skin_lookup', 'query_failed');
                        }
                        return false;
                    }

                    $skin_row = $this->db->fetch_assoc($sql_result);
                    if (!$skin_row) {
                        $sql_result = $this->db->query(
                            "INSERT INTO `toolbox_customise_skins`
                                (`toolbox_customise_domain_id`, `skin`, `customise_blankpage`,
                                 `blankpage_type`, `blankpage_image`, `blankpage_url`,
                                 `blankpage_custom`, `customise_css`, `additional_css`,
                                 `customise_logo`, `customised_logo`, `modified_by`)
                             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?);",
                            $domain_id,
                            $skin,
                            $values['customise_blankpage'],
                            $values['blankpage_type'],
                            $values['blankpage_image'],
                            $values['blankpage_url'],
                            $values['blankpage_custom'],
                            $values['customise_css'],
                            $values['additional_css'],
                            $values['customise_logo'],
                            $values['customised_logo'],
                            $user
                        );
                        if (!$sql_result || ($err_str = $this->db->is_error())) {
                            if ($this->loglevel > 0) {
                                toolbox_logger::error('storage.sql', 'customise_skin_insert', 'query_failed');
                            }
                            return false;
                        }
                    } else {
                        // These are the only fields accepted from the module payload.
                        $allowed_fields = [
                            'customise_blankpage', 'blankpage_type', 'blankpage_image',
                            'blankpage_url', 'blankpage_custom', 'customise_css',
                            'additional_css', 'customise_logo', 'customised_logo',
                        ];
                        $updates = [];
                        $parameters = [];
                        foreach ($allowed_fields as $field) {
                            if (!array_key_exists($field, $values)) {
                                continue;
                            }
                            if ($field === 'blankpage_image'
                                && ($values['blankpage_image_control'] ?? null) == '1') {
                                continue;
                            }
                            // Column names come exclusively from the fixed allowlist.
                            $updates[] = "`{$field}` = ?";
                            $parameters[] = $values[$field];
                        }

                        // A skin with no submitted fields has not been modified.
                        if ($updates) {
                            $updates[] = '`modified` = CURRENT_TIMESTAMP';
                            $updates[] = '`modified_by` = ?';
                            $parameters[] = $user;
                            $parameters[] = $domain_id;
                            $parameters[] = $skin;

                            $sql_result = $this->db->query(
                                "UPDATE `toolbox_customise_skins` SET " . implode(', ', $updates)
                                . " WHERE `toolbox_customise_domain_id` = ? AND `skin` = ?;",
                                ...$parameters
                            );
                            if (!$sql_result || ($err_str = $this->db->is_error())) {
                                if ($this->loglevel > 0) {
                                    toolbox_logger::error('storage.sql', 'customise_skin_update', 'query_failed');
                                }
                                return false;
                            }
                        }
                    }
                }

                break;

        }

        return $result;

    }

    /**
     * Normalize an alias address according to the configured single/multiple-domain mode.
     *
     * Unqualified aliases continue to refer to the user's own domain. An explicit
     * domain is permitted syntactically only in multiple-domain mode. An invalid
     * address or username is rejected instead of silently guessing a domain.
     *
     * @param string $user Authenticated full mailbox address.
     * @param string $alias Submitted alias name or full email address.
     * @return ?string Lowercase full alias address, or null when invalid.
     */
    public function normalise_alias_address(string $user, string $alias): ?string
    {
        $user_parts = explode('@', $user);
        if (count($user_parts) !== 2 || $user_parts[0] === '' || $user_parts[1] === '') {
            return null;
        }

        $alias = strtolower(trim($alias));
        if (!$this->toolbox_multiple_domains_enabled() && str_contains($alias, '@')) {
            return null;
        }

        $address = str_contains($alias, '@') ? $alias : $alias . '@' . strtolower($user_parts[1]);
        $address_parts = explode('@', $address);
        if (count($address_parts) !== 2 || strlen($address) > 254) {
            return null;
        }

        [$local, $domain] = $address_parts;
        if (!preg_match('/\A[_a-z0-9-]+(?:\.[_a-z0-9-]+)*\z/', $local)
            || !preg_match('/\A[a-z0-9](?:[a-z0-9-]*[a-z0-9])?(?:\.[a-z0-9](?:[a-z0-9-]*[a-z0-9])?)*\z/', $domain)) {
            return null;
        }

        foreach (explode('.', $domain) as $label) {
            if (strlen($label) > 63) {
                return null;
            }
        }

        return $address;
    }

    /**
     * Read the multiple-domain alias configuration from Roundcube.
     *
     * @return bool Whether fully qualified cross-domain alias names are enabled.
     */
    private function toolbox_multiple_domains_enabled(): bool
    {
        return (bool) rcube::get_instance()->config->get('toolbox_aliases_multiple_domains', false);
    }

    /**
     * Validate an alias creation request against authorization and global uniqueness.
     *
     * Cross-domain creation requires an existing static or PostfixAdmin domain-admin
     * assignment. Same-domain aliases remain available to regular mailbox users.
     * Checks use the selected PostfixAdmin SQL connection mode and fail closed.
     *
     * @param string $user Authenticated mailbox address.
     * @param string $alias Proposed alias name/address.
     * @param string $mode Database connection mode: read for AJAX check, write for INSERT.
     * @return ?string Null when permitted/available; otherwise invalid, unauthorized, exists, or storage.
     */
    public function alias_creation_error(string $user, string $alias, string $mode = 'r'): ?string
    {
        $address = $this->normalise_alias_address($user, $alias);
        if ($address === null) {
            return 'invalid';
        }

        $user_parts = explode('@', strtolower($user), 2);
        $address_parts = explode('@', $address, 2);
        $domain = $address_parts[1];
        $this->_db_connect('postfix', $mode);

        if ($domain !== $user_parts[1]) {
            $authorized = false;
            foreach ((array) $this->domain_admins as $admin => $assigned) {
                if (strcasecmp((string) $admin, $user) !== 0) {
                    continue;
                }
                $allowed_domains = array_map('trim', explode(',', strtolower((string) $assigned)));
                if (in_array('all', $allowed_domains, true) || in_array($domain, $allowed_domains, true)) {
                    $authorized = true;
                    break;
                }
            }

            if (!$authorized && $this->use_postfixadmin_domain_admins) {
                $sql_result = $this->db->query(
                    "SELECT `{$this->postfix_sql_domain_field_in_domain_admins}` FROM `{$this->postfix_sql_domain_admins_table_name}` WHERE `{$this->postfix_sql_username_field_in_domain_admins}` = ? AND (`{$this->postfix_sql_domain_field_in_domain_admins}` = ? OR `{$this->postfix_sql_domain_field_in_domain_admins}` = 'ALL');",
                    $user,
                    $domain
                );
                if (!$sql_result || $this->db->is_error()) {
                    return 'storage';
                }
                $authorized = (bool) $this->db->fetch_assoc($sql_result);
            }

            if (!$authorized) {
                return 'unauthorized';
            }
        }

        // Never enumerate unrelated domains in AJAX. Check exactly one address.
        $sql_result = $this->db->query(
            "SELECT `{$this->postfix_sql_address_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ?;",
            $address
        );
        if (!$sql_result || $this->db->is_error()) {
            return 'storage';
        }

        return $this->db->fetch_assoc($sql_result) ? 'exists' : null;
    }

    /**
     * Convert a configured Vacation date into a safe, parameterized SQL expression.
     *
     * PostgreSQL and Oracle require an explicit timestamp conversion, while the
     * other supported engines accept a normalized datetime bound as a value.
     * Return null for an invalid date instead of incorporating user text in SQL.
     *
     * @param string $value Date submitted in the configured PHP date format.
     * @return ?array SQL expression and its ordered bind parameters, or null.
     */
    private function vacation_date_parameter(string $value): ?array
    {
        $format = (string) ($this->vacation_dateformat['php'] ?? '');
        if ($format === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat('!' . $format, $value);
        if (!$date || $date->format($format) !== $value) {
            return null;
        }

        $dsn = rcube_db::parse_dsn($this->postfix_dsnw);
        $driver = $dsn['phptype'] ?? '';
        if ($driver === 'pgsql' || $driver === 'oracle') {
            $sql_format = $this->vacation_dateformat[$driver] ?? null;
            if (!is_string($sql_format) || $sql_format === '') {
                return null;
            }
            return ['expression' => 'to_timestamp(?, ?)', 'values' => [$value, $sql_format]];
        }

        // Date values are normalized before binding, not interpolated into SQL.
        if (in_array($driver, ['mysql', 'mysqli', 'sqlite', 'mssql', 'sqlsrv'], true)) {
            $normalized_format = $driver === 'mssql' || $driver === 'sqlsrv'
                ? 'Y-m-d\TH:i:s'
                : 'Y-m-d H:i:s';
            return ['expression' => '?', 'values' => [$date->format($normalized_format)]];
        }

        return null;
    }

    private function _db_connect($type, $mode)
    {
        $dsnw = $type . '_dsnw';
        $dsnr = $type . '_dsnr';
        $db_persistent = $type . '_db_persistent';

        if (!$this->db) {
            $this->db = rcube_db::factory($this->$dsnw, $this->$dsnr, $this->$db_persistent);
        }
        else {
            // we want to get the protected properties db_dsnw and db_dsnr from $this->db
            // PHP 7 has an elegant way, otherwise we need a hack
            $ver = (float)phpversion();
            if ($ver > 7.0) {
                $get_dsn = function() {
                    return ['w' => $this->db_dsnw, 'r' => $this->db_dsnr];
                };
                $db_dsn = $get_dsn->call($this->db);
            }
            else {
                $array = (array)$this->db;
                $db_dsn = ['w' => $array[chr(0).'*'.chr(0).db_dsnw], 'r' => $array[chr(0).'*'.chr(0).db_dsnr]];
            }
            if (($db_dsn['w'] != $dsnw) || ($db_dsn['r'] != $dsnr)) {
                $this->db = rcube_db::factory($this->$dsnw, $this->$dsnr, $this->$db_persistent);
            }
        }

        $this->db->set_debug((bool) rcube::get_instance()->config->get('sql_debug'));
        $this->db->db_connect($mode);

        // check DB connections and exit on failure
        if ($err_str = $this->db->is_error()) {
            if ($this->loglevel > 0) {
                toolbox_logger::error('storage.sql', 'db_connect', 'connection_failed');
            }
            rcube::raise_error([
                'code' => 603,
                'type' => 'db',
                'message' => $err_str
            ], false, true);
        }
        else {
            // try to instantiate helper class
            $class = get_class($this) . '_helper';
            if (class_exists($class)) {
                $this->helper = new $class($this->db, $mode == 'w' ? $this->$dsnw : $this->$dsnr);
            }
            else {
                // no storage found, raise error
                if ($this->loglevel > 0) {
                    toolbox_logger::error('storage.sql', 'db_connect', 'helper_not_found');
                }
                rcube::raise_error(['code' => 604, 'type' => 'toolbox',
                    'line' => __LINE__, 'file' => __FILE__,
                    'message' => "Failed to find storage helper class: {$class}"
                ], true, true);
            }

        }

    }

}

// END OF FILE
