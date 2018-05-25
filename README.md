Roundcube Toolbox
=================
This plugin is a set of tools for RoundCube.



ATTENTION
---------
This plugin works with RC 1.4.x.

Requirements
------------
* [Roundcube jQueryUI plugin](https://github.com/roundcube/roundcubemail/tree/master/plugins/jqueryui)

License
-------
This plugin is released under the [GNU General Public License Version 3+](https://www.gnu.org/licenses/gpl.html) exept skins, which are subject to the [Creative Commons Attribution-ShareAlike License](http://creativecommons.org/licenses/by-sa/3.0).

Install
-------
* Place this plugin folder into plugins directory of Roundcube
* Add toolbox to $config['plugins'] in your Roundcube config file

**NB:** When downloading the plugin from GitHub you will need to create a
directory called toolbox and place the files in there, ignoring the root
directory in the downloaded archive.

Update database using the appropriate file in the SQl folder.
+
Config
------
The default config file is plugins/toolbox/config.inc.php.dist
Copy 'config.inc.php.dist' to 'config.inc.php'.
Edit the plugin configuration file 'config.inc.php' and choose the appropriate options:
```
$rcmail_config['toolbox_driver'] = 'sql';
```
so far only sql is available
```
$rcmail_config['toolbox_sql_dsn'] = value;
```
example value: 'pgsql://username:password@host/database'
example value: 'mysql://username:password@host/database'

