Roundcube Toolbox
=================
This plugin is a set of tools for [Roundcube Webmail](https://github.com/roundcube/roundcubemail)<br />
Some tools are designed to work with [PostfixAdmin](https://github.com/postfixadmin/postfixadmin) / [Postfix with sql backend](http://www.postfix.org/DATABASE_README.html)

ATTENTION
---------
This plugin works with RC 1.5.

Release 1.5.0.0 of the plugin works with 1.5 of Roundcube Webmail

Master branch works with the Git version of Roundcube Webmail

Available tools
---------------
* **aliases** [tool to manage aliases in postfixadmin]
* **forward** [tool to manage forward addresses in postfixadmin]
* **vacation** [tool to manage postfixadmin vacation]
* **purge** [tool to periodically purge Trash and Junk folders]
* **preview** [tool to disable message preview]
* **customise** [tool to customise Blank Page and CSS per domain]
* **attachments** [tool to replace large attachments with a link in outgoing messages]

Requirements
------------
* [Roundcube jQueryUI plugin](https://github.com/roundcube/roundcubemail/tree/master/plugins/jqueryui)

License
-------
This plugin is released under the [GNU General Public License Version 3+](https://www.gnu.org/licenses/gpl.html) except skins, which are subject to the [Creative Commons Attribution-ShareAlike License](http://creativecommons.org/licenses/by-sa/3.0).

Install
-------
* Place this plugin folder into plugins directory of Roundcube
* Change the folder name to 'toolbox'
* Add 'toolbox' to `$config['plugins']` in your Roundcube config file

**NB:** When downloading the plugin from GitHub you will need to create a directory called toolbox and place the files in there, ignoring the root directory in the downloaded archive.

Update database using the appropriate file in the SQL folder.

Customise tool needs a folder called 'tmp' writeable by the web browser.<br />
Attachments tool needs a folder called attachments writeable by the web browser.<br />
The file /bin/initplugin.sh can do the job for you.

Config
------
The default config file is plugins/toolbox/config.inc.php.dist<br />
Copy 'config.inc.php.dist' to 'config.inc.php'<br />
Edit the plugin configuration file 'config.inc.php' and choose the appropriate options<br />

**$rcmail_config['toolbox_dsn']**<br />
Sets the connection to the data source<br />
Examples:
```
$rcmail_config['toolbox_dsn'] = 'pgsql://username:password@localhost/database';
$rcmail_config['toolbox_dsn'] = 'mysql://username:password@host/database';
```

**$config['toolbox_sql_table_name']**<br />
Sets the name of the sql table<br />
Default value:
```
$config['toolbox_sql_table_name'] = 'toolbox';
```

**$config['toolbox_sql_username_field']**<br />
Sets the name of the username field in the toolbox sql table<br />
Default value:
```
$config['toolbox_sql_username_field'] = 'username';
```
