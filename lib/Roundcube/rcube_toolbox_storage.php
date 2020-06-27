<?php

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
