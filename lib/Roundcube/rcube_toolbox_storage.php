<?php

/**
 * Toolbox base storage class
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

abstract class rcube_toolbox_storage
{
    protected $config;
    protected $tool;
    protected $loglevel;
    protected $logfile;

    /**
     * Object constructor
     */
    public function __construct($config, $tool)
    {
        $this->config = $config;
        $this->tool = $tool;
    }

    /**
     * Retrieve user's role in postfixadmin
     *
     * @param  string $user username
     *
     * @return bool   True if admin, False if not
     */
    abstract function is_domain_admin($user);

    /**
     * Retrieve data for a specific tool
     *
     * @param  string $user username
     *
     * @return array  Array of settings in format Array($name => $value, ...)
     */
    abstract function load_tool_data($user);

    /**
     * Delete data for a specific tool
     *
     * @param  string $user  username
     * @param  array  $data  Array of settings to be deleted
     *
     * @return bool   True on success, False on error
     */
    abstract function delete_tool_data($user, $data);

    /**
     * Save data for a specific tool
     *
     * @param  string $user  username
     * @param  array  $data  Array of settings to be saved
     *
     * @return bool   True on success, False on error
     */
    abstract function save_tool_data($user, $data);

}

// END OF FILE
