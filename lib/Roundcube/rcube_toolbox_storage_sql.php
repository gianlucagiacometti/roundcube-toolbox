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
    private $postfix_sql_modified_in_vacation;
    private $postfix_sql_vacation_notification;
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

        $domains = [];

        // add postfixadmin domain admins
        if ($this->use_postfixadmin_domain_admins) {

            if ($this->loglevel > 2) {
                rcube::write_log($this->logfile, "STEP in [storage].[sql].[function is_domain_admin]: connecting to postfix database in read mode");
            }
            $this->_db_connect('postfix', 'r');

            if ($this->loglevel > 1) {
                rcube::write_log($this->logfile, "SQL in [storage].[sql].[function is_domain_admin]: execute query [SELECT `{$this->postfix_sql_domain_field_in_domain_admins}` FROM `{$this->postfix_sql_domain_admins_table_name}` WHERE `{$this->postfix_sql_username_field_in_domain_admins}` = '{$user}';]");
            }
            $sql_result = $this->db->query(
                "SELECT `{$this->postfix_sql_domain_field_in_domain_admins}` FROM `{$this->postfix_sql_domain_admins_table_name}` WHERE `{$this->postfix_sql_username_field_in_domain_admins}` = ?;",
                $user);
            if ($this->loglevel > 0) {
                if ($err_str = $this->db->is_error()) {
                    rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function is_domain_admin]: cannot read domain admins from database: " . $err_str);
                }
            }

            while ($sql_result && ($sql_arr = $this->db->fetch_assoc($sql_result))) {
                $domains[] = $sql_arr[$this->postfix_sql_domain_field_in_domain_admins];
            }
            if ($this->loglevel > 1) {
                rcube::write_log($this->logfile, "SQL in [storage].[sql].[function is_domain_admin]: found " . count($domains) . " domains (" .  implode(', ', $domains) . ")");
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

        $parts = explode('@', $user);

        return in_array($parts[1], $domains) || in_array('ALL', $domains) ? true : false;

    }

    public function load_customised_config($user, $skin)
    {
        $parts = explode('@', $user);

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [storage].[sql].[function load_customised_config]: connecting to roundcube database in read mode");
        }
        $this->_db_connect('roundcube', 'r');

        if ($this->loglevel > 1) {
            rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_customised_config]: execute query [SELECT domain_name`, `skin`, `customise_blankpage`, `blankpage_type`, `blankpage_image` `blankpage_url`, `blankpage_custom`, `customise_css`, `additional_css`, `customise_logo`, `customised_logo` FROM `toolbox_customise_skins_view` WHERE `domain_name` = '{$parts[1]}' AND `skin` = '{$skin}';]");
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
                rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function load_customised_config]: cannot read customise skins from database: " . $err_str);
            }
            elseif ($this->loglevel > 1) {
                rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_customised_config]: {$this->db->affected_rows()} rows matching");
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

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [storage].[sql].[function load_tool_data]: enter {$this->tool} section");
        }
        switch ($this->tool) {

            case 'aliases':

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function load_tool_data]: connecting to postfix database in read mode");
                }
                $this->_db_connect('postfix', 'r');

                switch ($attrib) {

                    case 'allaliases':
                        // The SQL query used to select all domain aliases but user's.
                        // Need to avoid alias duplicates in the domain.
                        if ($this->loglevel > 1) {
                            rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_tool_data]: execute query [SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_domain_field_in_alias}` = '{$parts[1]}' AND `{$this->postfix_sql_goto_field_in_alias}` != '{$user}';]");
                        }
                        $sql_result = $this->db->query(
                            "SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_domain_field_in_alias}` = ? AND `{$this->postfix_sql_goto_field_in_alias}` != ?;",
                            $parts[1],
                            $user);
                        if ($this->loglevel > 0) {
                            if ($err_str = $this->db->is_error()) {
                                rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function load_tool_data]: cannot read all aliases from database: " . $err_str);
                            }
                            elseif ($this->loglevel > 1) {
                                rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_tool_data]: {$this->db->affected_rows()} rows matching");
                            }
                        }
                        break;

                    default:
                        // The SQL query used to select all mailbox aliases
                        // default mailbox alias to itself is excluded and managed by forward tool
                        if ($this->loglevel > 1) {
                            rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_tool_data]: execute query [SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_goto_field_in_alias}` = '{$user}' AND `{$this->postfix_sql_domain_field_in_alias}` = '{$parts[1]}' AND `{$this->postfix_sql_address_field_in_alias}` != '{$user}';]");
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
                                rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function load_tool_data]: cannot read alias from database: " . $err_str);
                            }
                            elseif ($this->loglevel > 1) {
                                rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_tool_data]: {$this->db->affected_rows()} rows matching");
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
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function load_tool_data]: connecting to postfix database in read mode");
                }
                $this->_db_connect('postfix', 'r');

                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_tool_data]: execute query [SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = '{$user}';]");
                }
                $sql_result = $this->db->query(
                    "SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ?;",
                    $user);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function load_tool_data]: cannot read alias from database: " . $err_str);
                    }
                    elseif ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_tool_data]: {$this->db->affected_rows()} rows matching");
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
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function load_tool_data]: connecting to postfix database in read mode");
                }
                $this->_db_connect('postfix', 'r');

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function load_tool_data]: call helper->to_char()");
                }
                $activefrom = $this->helper->to_char($this->postfix_sql_activefrom_field_in_vacation, $this->vacation_dateformat);
                $activeuntil = $this->helper->to_char($this->postfix_sql_activeuntil_field_in_vacation, $this->vacation_dateformat);

                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_tool_data]: execute query [SELECT `{$this->postfix_sql_subject_field_in_vacation}`, `{$this->postfix_sql_body_field_in_vacation}`, `{$this->postfix_sql_active_field_in_vacation}`, $activefrom, $activeuntil, `{$this->postfix_sql_interval_time_field_in_vacation}` FROM `{$this->postfix_sql_vacation_table_name}` WHERE `{$this->postfix_sql_email_field_in_vacation}` = '{$user}' AND `{$this->postfix_sql_domain_field_in_vacation}` = '{$parts[1]}';]");
                }
                $sql_result = $this->db->query(
                    "SELECT `{$this->postfix_sql_subject_field_in_vacation}`, `{$this->postfix_sql_body_field_in_vacation}`, `{$this->postfix_sql_active_field_in_vacation}`, $activefrom, $activeuntil, `{$this->postfix_sql_interval_time_field_in_vacation}` FROM `{$this->postfix_sql_vacation_table_name}` WHERE `{$this->postfix_sql_email_field_in_vacation}` = ? AND `{$this->postfix_sql_domain_field_in_vacation}` = ?;",
                    $user,
                    $parts[1]);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function load_tool_data]: cannot read vacation from database: " . $err_str);
                    }
                    elseif ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "SQL in [storage].[sql].[function load_tool_data]: {$this->db->affected_rows()} rows matching");
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
                        rcube::write_log($this->logfile, "ERROR loading Customise domain settings: " . ($err_str ?? 'query failed'));
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
                        rcube::write_log($this->logfile, "ERROR loading Customise skin settings: " . ($err_str ?? 'query failed'));
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

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [storage].[sql].[function delete_tool_data]: enter {$data['section']} section");
        }
        switch ($data['section']) {

            case 'aliases':

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function delete_tool_data]: connecting to postfix database in write mode");
                }
                $this->_db_connect('postfix', 'w');

                $address = rcube::get_instance()->config->get('toolbox_aliases_multiple_domains') ? $data['aliasname'] : $data['aliasname'] . '@' . $parts[1];

                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "SQL in [storage].[sql].[function delete_tool_data]: execute query [DELETE FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = '{$address}' AND `{$this->postfix_sql_goto_field_in_alias}` = '{$user}';]");
                }
                $this->db->query(
                    "DELETE FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_goto_field_in_alias}` = ?;",
                    $address,
                    $user);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function delete_tool_data]: cannot delete from alias: " . $err_str);
                        $result = false;
                        break;
                    }
                    $sql_result = $this->db->affected_rows();
                    if (!$sql_result) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function delete_tool_data]: no record deleted in alias [values: {$this->postfix_sql_address_field_in_alias} = '{$settings['main']['address']}', {$this->postfix_sql_goto_field_in_alias} = '{$user}']");
                        $result = false;
                        break;
                    }
                    elseif ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "SQL in [storage].[sql].[function delete_tool_data]: {$this->db->affected_rows()} rows deleted");
                    }
                }

                break;

        }

        return $result;

    }

    public function toggle_tool_data($user, $data)
    {

        $result = true;

        $settings = $data['new_settings'];
        $parts = explode('@', $user);

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [storage].[sql].[function toggle_tool_data]: enter {$data['section']} section");
        }
        switch ($data['section']) {

            case 'aliases':

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function toggle_tool_data]: connecting to postfix database in write mode");
                }
                $this->_db_connect('postfix', 'w');

                $address = rcube::get_instance()->config->get('toolbox_aliases_multiple_domains') ? $settings['aliasname'] : $settings['aliasname'] . '@' . $parts[1];

                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "SQL in [storage].[sql].[function toggle_tool_data]: execute query [UPDATE `{$this->postfix_sql_alias_table_name}` SET `{$this->postfix_sql_active_field_in_alias}` = NOT `{$this->postfix_sql_active_field_in_alias}` WHERE `{$this->postfix_sql_address_field_in_alias}` = '{$address}' AND `{$this->postfix_sql_goto_field_in_alias}` = '{$user}';]");
                }
                $this->db->query(
                    "UPDATE `{$this->postfix_sql_alias_table_name}` SET `{$this->postfix_sql_active_field_in_alias}` = NOT `{$this->postfix_sql_active_field_in_alias}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_goto_field_in_alias}` = ?;",
                    $address,
                    $user);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function toggle_tool_data]: cannot toggle alias activation: " . $err_str);
                        $result = false;
                        break;
                    }
                    $sql_result = $this->db->affected_rows();
                    if (!$sql_result) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function toggle_tool_data]: no activation toggled in alias [values: {$this->postfix_sql_address_field_in_alias} = '{$settings['main']['address']}', {$this->postfix_sql_goto_field_in_alias} = '{$user}']");
                        $result = false;
                        break;
                    }
                    elseif ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "SQL in [storage].[sql].[function toggle_tool_data]: {$this->db->affected_rows()} rows deleted");
                    }
                }

                break;

        }

        return $result;

    }

    public function save_tool_data($user, $data)
    {

        $result = true;

        $settings = $data['new_settings'];
        $parts = explode('@', $user);

        if ($this->loglevel > 2) {
            rcube::write_log($this->logfile, "STEP in [storage].[sql].[function save_tool_data]: enter {$data['section']} section");
        }
        switch ($data['section']) {

            case 'aliases':

                // double check to avoid messing up the database
                if ($settings['main']['aliasname'] == '') {
                    if ($this->loglevel > 0) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function save_tool_data]: {$this->postfix_sql_address_field_in_alias} cannot be empty");
                    }
                    $result = false;
                    break;
                }

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function save_tool_data]: connecting to postfix database in write mode");
                }
                $this->_db_connect('postfix', 'w');

                $address = $settings['main']['aliasname'];
                if (!str_contains($address, '@') || !rcube::get_instance()->config->get('toolbox_aliases_multiple_domains')) $address .= '@' . $parts[1];

                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: execute query [INSERT INTO `{$this->postfix_sql_alias_table_name}` (`{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_domain_field_in_alias}`, `{$this->postfix_sql_created_field_in_alias}`, `{$this->postfix_sql_modified_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}`) VALUES ('{$address}', '{$user}', '{$parts[1]}', {$this->db->now()}, {$this->db->now()}, '{$settings['main']['active']}');]");
                }
                $this->db->query(
                    "INSERT INTO `{$this->postfix_sql_alias_table_name}` (`{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}`, `{$this->postfix_sql_domain_field_in_alias}`, `{$this->postfix_sql_created_field_in_alias}`, `{$this->postfix_sql_modified_field_in_alias}`, `{$this->postfix_sql_active_field_in_alias}`) VALUES (?, ?, ?, {$this->db->now()}, {$this->db->now()}, {$settings['main']['active']});",
                    $address,
                    $user,
                    $parts[1]);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function save_tool_data]: cannot insert into alias: " . $err_str);
                        $result = false;
                        break;
                    }
                    $sql_result = $this->db->affected_rows();
                    if (!$sql_result) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function save_tool_data]: record not inserted in alias [values: {$this->postfix_sql_address_field_in_alias} = '{$address}', {$this->postfix_sql_goto_field_in_alias} = '{$user}', {$this->postfix_sql_domain_field_in_alias} = '{$parts[1]}', created = '{$this->db->now()}', modified = '{$this->db->now()}', {$this->postfix_sql_active_field_in_alias} = '{$settings['main']['active']}']");
                        $result = false;
                        break;
                    }
                    elseif ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: {$this->db->affected_rows()} rows inserted");
                    }
                }

                break;

            case 'forward':

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function save_tool_data]: connecting to postfix database in write mode");
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
                        rcube::write_log($this->logfile, "STEP in [storage].[sql].[function save_tool_data]: Found keepcopies option, user address added: {$user}");
                    }
                    $addresses[] = $user;
                }
                // if keepcopies is not set and the user address is in the list must be deleted
                elseif (($key = array_search($user, $addresses)) !== false) {
                    unset($addresses[$key]);
                }

                // check if autoreply is set and add it to the goto addresses
                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: execute query [SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = '{$user}';]");
                }
                $sql_result = $this->db->query(
                    "SELECT `{$this->postfix_sql_address_field_in_alias}`, `{$this->postfix_sql_goto_field_in_alias}` FROM `{$this->postfix_sql_alias_table_name}` WHERE `{$this->postfix_sql_address_field_in_alias}` = ?;",
                    $user);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function load_tool_data]: cannot read alias from database: " . $err_str);
                        $result = false;
                        break;
                    }
                    elseif ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: {$this->db->affected_rows()} rows matching");
                    }
                }

                if ($this->db->affected_rows() > 0) {
                    while ($sql_result && ($sql_arr = $this->db->fetch_assoc($sql_result))) {
                        $address = $sql_arr[$this->postfix_sql_address_field_in_alias];
                        $goto = $sql_arr[$this->postfix_sql_goto_field_in_alias];
                    }
                    if (strpos($goto, $autoreply) !== false) {
                        if ($this->loglevel > 2) {
                            rcube::write_log($this->logfile, "STEP in [storage].[sql].[function save_tool_data]: found autoreply address {$autoreply}");
                        }
                        $addresses[] = $autoreply;
                    }
                }

                // double check to avoid messing up the database
                if (empty($addresses)) {
                    if ($this->loglevel > 0) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function save_tool_data]: {$this->postfix_sql_goto_field_in_alias} cannot be empty");
                    }
                    $result = false;
                    break;
                }

                // update alias table
                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: execute query [UPDATE {$this->postfix_sql_alias_table_name} SET `{$this->postfix_sql_goto_field_in_alias}` = " . implode(',', $addresses) . ", `{$this->postfix_sql_modified_field_in_alias}` = {$this->db->now()} WHERE `{$this->postfix_sql_address_field_in_alias}` = '{$user}' AND `{$this->postfix_sql_domain_field_in_alias}` = '{$parts[1]}';]");
                }
                $sql_result = $this->db->query(
                    "UPDATE {$this->postfix_sql_alias_table_name} SET `{$this->postfix_sql_goto_field_in_alias}` = '" . implode(',', $addresses) . "', `{$this->postfix_sql_modified_field_in_alias}` = {$this->db->now()} WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_domain_field_in_alias}` = ?;",
                    $user,
                    $parts[1]);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function load_tool_data]: cannot update table alias: " . $err_str);
                        $result = false;
                        break;
                    }
                    elseif ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: {$this->db->affected_rows()} rows matching");
                    }
                }

                break;

            case 'vacation':

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function save_tool_data]: connecting to postfix database in write mode");
                }
                $this->_db_connect('postfix', 'w');

                $queries = [];

                $settings['main']['active'] = $settings['main']['active'] == 1 ? 'true' : 'false';
                $settings['main']['modified'] = $this->db->now();
                $settings['main']['subject'] = $this->db->quote($settings['main']['subject']);
                $settings['main']['body'] = $this->db->quote($settings['main']['body']);

                if ($this->loglevel > 2) {
                    rcube::write_log($this->logfile, "STEP in [storage].[sql].[function save_tool_data]: call helper->to_timestamp");
                }
                $settings['main']['activefrom'] = $this->helper->to_timestamp($settings['main']['activefrom'], $this->vacation_dateformat);
                $settings['main']['activeuntil'] = $this->helper->to_timestamp($settings['main']['activeuntil'], $this->vacation_dateformat);

                // check if vacation record already exists for user and domain
                if ($this->loglevel > 1) {
                    rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: execute query [SELECT `{$this->postfix_sql_email_field_in_vacation}` FROM `{$this->postfix_sql_vacation_table_name}` WHERE `{$this->postfix_sql_email_field_in_vacation}` = '{$user}' AND `{$this->postfix_sql_domain_field_in_vacation}` = '{$parts[1]}';]");
                }
                $this->db->query("SELECT `{$this->postfix_sql_email_field_in_vacation}` FROM `{$this->postfix_sql_vacation_table_name}` WHERE `{$this->postfix_sql_email_field_in_vacation}` = ? AND `{$this->postfix_sql_domain_field_in_vacation}` = ?;",
                    $user,
                    $parts[1]);
                if ($this->loglevel > 0) {
                    if ($err_str = $this->db->is_error()) {
                        rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function save_tool_data]: cannot read vacation from database: " . $err_str);
                        $result = false;
                        break;
                    }
                    elseif ($this->loglevel > 1) {
                        rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: {$this->db->affected_rows()} rows matching");
                    }
                }
                // if exists update else insert
                if ($this->db->affected_rows() > 0) {
                    $set = [];
                    foreach ($settings['main'] as $field => $value) {
                        $set[] = "`{$field}` = {$value}";
                    }
                    $queries[] = [
                                'sql' => "UPDATE {$this->postfix_sql_vacation_table_name} SET "  . implode(', ', $set) .  " WHERE `{$this->postfix_sql_email_field_in_vacation}` = ? AND `{$this->postfix_sql_domain_field_in_vacation}` = ?;",
                                'data' => [$user, $parts[1]],
                                'type' => 'update'
                    ];
                }
                else {
                    $settings['main']['created'] = $this->db->now();
                    $queries[] = [
                                'sql' => "INSERT INTO {$this->postfix_sql_vacation_table_name} (email, domain, " . implode(', ', array_keys($settings['main'])) . ") VALUES ('" . $user . "','" . $parts[1] . "'," . implode(',',array_values($settings['main'])) . ");",
                                'data' => [],
                                'type' => 'insert'
                    ];
                }

                // delete old vacation notifications
                $queries[] = [
                                'sql' => "DELETE FROM {$this->postfix_sql_vacation_notification_table_name} WHERE `{$this->postfix_sql_on_vacation_field_in_vacation_notification}` = ?;",
                                'data' => [$user],
                                'type' => 'delete'
                ];

                //remove autoreply address from alias list, but keep custom aliases
                $concat = $this->db->concat($this->db->quote(","),$this->db->quote($parts[0]),$this->db->quote('#'),$this->db->quote($parts[1]),$this->db->quote('@'),$this->db->quote($this->postfixadmin_vacation_domain));
                $queries[] = [
                                'sql' => "UPDATE {$this->postfix_sql_alias_table_name} SET `{$this->postfix_sql_goto_field_in_alias}` = replace({$this->postfix_sql_goto_field_in_alias}, {$concat}, ''), `{$this->postfix_sql_modified_field_in_alias}` = {$this->db->now()} WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_domain_field_in_alias}` = ?;",
                                'data' => [$user, $parts[1]],
                                'type' => 'update'
                ];

                //add autoreply address as alias
                if ($settings['main']['active'] == 'true') {
                    $concat = $this->db->concat($this->postfix_sql_goto_field_in_alias,$this->db->quote(","),$this->db->quote($parts[0]),$this->db->quote('#'),$this->db->quote($parts[1]),$this->db->quote('@'),$this->db->quote($this->postfixadmin_vacation_domain));
                    $queries[] = [
                                    'sql' => "UPDATE {$this->postfix_sql_alias_table_name} SET `{$this->postfix_sql_goto_field_in_alias}` = {$concat}, `{$this->postfix_sql_modified_field_in_alias}` = {$this->db->now()} WHERE `{$this->postfix_sql_address_field_in_alias}` = ? AND `{$this->postfix_sql_domain_field_in_alias}` = ? AND `{$this->postfix_sql_active_field_in_alias}` = true;",
                                    'data' => [$user, $parts[1]],
                                    'type' => 'update'
                    ];
                }

                foreach ($queries as $query) {

                    $this->db->query($query['sql'], $query['data']);

                    if ($this->loglevel > 0) {
                        $query['sql'] = vsprintf(str_replace('?', "'%s'", $query['sql']), $query['data']);
                        if ($err_str = $this->db->is_error()) {
                            rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function save_tool_data]: cannot execute query [{$query['sql']}] - " . $err_str);
                            $result = false;
                            break;
                        }
                        elseif ($this->loglevel > 1) {
                            rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: execute query: [{$query['sql']}]");
                            rcube::write_log($this->logfile, "SQL in [storage].[sql].[function save_tool_data]: {$this->db->affected_rows()} rows matching");
                        }
                    }

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
                        rcube::write_log($this->logfile, "ERROR finding Customise domain: " . ($err_str ?? 'query failed'));
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
                            rcube::write_log($this->logfile, "ERROR inserting Customise domain: " . ($err_str ?? 'query failed'));
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
                            rcube::write_log($this->logfile, 'ERROR retrieving newly inserted Customise domain ID');
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
                            rcube::write_log($this->logfile, "ERROR updating Customise domain: " . ($err_str ?? 'query failed'));
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
                            rcube::write_log($this->logfile, "ERROR finding Customise skin: " . ($err_str ?? 'query failed'));
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
                                rcube::write_log($this->logfile, "ERROR inserting Customise skin: " . ($err_str ?? 'query failed'));
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
                        foreach ($allowed_fields as $field) {
                            if (!array_key_exists($field, $values)) {
                                continue;
                            }
                            if ($field === 'blankpage_image'
                                && ($values['blankpage_image_control'] ?? null) == '1') {
                                continue;
                            }
                            $sql_result = $this->db->query(
                                "UPDATE `toolbox_customise_skins` SET `{$field}` = ?
                                 WHERE `toolbox_customise_domain_id` = ? AND `skin` = ?;",
                                $values[$field],
                                $domain_id,
                                $skin
                            );
                            if (!$sql_result || ($err_str = $this->db->is_error())) {
                                if ($this->loglevel > 0) {
                                    rcube::write_log($this->logfile, "ERROR updating Customise skin field {$field}: " . ($err_str ?? 'query failed'));
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
                rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function _db_connect]: connection failed: " . $err_str);
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
                    rcube::write_log($this->logfile, "ERROR in [storage].[sql].[function _db_connect]: failed to find storage helper class: {$class}");
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
