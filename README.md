# Roundcube Toolbox

A modular collection of tools for [Roundcube Webmail](https://github.com/roundcube/roundcubemail).
Several tools integrate with [PostfixAdmin](https://github.com/postfixadmin/postfixadmin)
and Postfix SQL backends. Each enabled tool owns its own PHP and (when needed)
JavaScript module.

## Compatibility and requirements

- **Toolbox release:** 1.7.6.
- **Roundcube:** 1.7.2 or newer (the recommended Composer minimum). Use the
  latest Roundcube 1.7 security release in production.
- **PHP:** 8.1 or newer, as required by Roundcube 1.7.
- **Skin:** Elastic is the bundled, tested skin. Toolbox still supports dynamic
  skin configuration; other skins are not included in this release.
- **PostfixAdmin/SQL access:** required only for tools that manage mailbox,
  alias, forwarding, vacation or domain records, including password hashes.
- **Roundcube `jqueryui` plugin:** required only when Vacation's optional
  calendar datepickers are enabled.
- **Password Hashes:** needs a writable SQL connection to the mailbox table and
  an integration that emits Roundcube's `password_change` hook. Roundcube's
  standard `password` plugin emits this event after a successful change, but
  the hash generator itself is independent of that plugin and uses PHP's
  hashing APIs. Available algorithms depend on the PHP runtime.

## Available tools

| Tool | Purpose |
| --- | --- |
| `aliases` | Manage PostfixAdmin mailbox aliases. |
| `forward` | Manage forwarding addresses and keep-copies settings. |
| `vacation` | Manage PostfixAdmin vacation replies, with optional datepickers and HTML editing. |
| `purge` | Configure automated Trash/Junk cleanup periods. |
| `preview` | Manage message preview preferences; native Roundcube settings control read/unread timing. |
| `customise` | Configure per-domain Blank Page, CSS, logos and related options, including per-domain purge settings. |
| `attachments` | Detach large attachments and replace them with download links. |
| `password_hashes` | **Background only:** store configurable additional password hashes after a successful Roundcube password change. No Settings tab. |

## Installation

1. Install Toolbox in `plugins/toolbox` under your Roundcube installation.
2. Add `toolbox` to Roundcube's `$config['plugins']` list. Toolbox does not
   provide a password-change form: `password_hashes` reacts to an existing
   integration's `password_change` event. Roundcube's standard `password`
   plugin is one possible event source.
3. Copy `plugins/toolbox/config.inc.php.dist` to
   `plugins/toolbox/config.inc.php` and configure the enabled tools and SQL
   connections. **Keep your private configuration out of public Git repos.**
4. For a **new** installation requiring Toolbox's database tables, initialize
   them using the appropriate script in `SQL/postgres.initial.sql` or
   `SQL/mysql.initial.sql`. Do not rerun an initial schema against an existing
   installation without reviewing it first.
5. Set up the writable `tmp` directory needed by Customise and the public
   attachments storage directory needed by Attachments, if those tools are
   enabled. `bin/initplugin.sh` can help prepare directories. For Roundcube
   1.7, review the Attachments storage path and web-server rules under
   `public_html/` as described in `config.inc.php.dist`.

If downloading a GitHub archive, rename its extracted plugin directory to
`toolbox`. Composer installations can use the Roundcube plugin installer.

### Updating an existing installation

Back up `config.inc.php` and your database before upgrading. Copy new options
from `config.inc.php.dist` into your existing private configuration instead of
replacing it wholesale. Review enabled tools and any new database permissions.

## Configuration

Enable tools using the `toolbox_tools` array in
`plugins/toolbox/config.inc.php`, for example:

```php
$config['toolbox_tools'] = [
    'aliases',
    'forward',
    'vacation',
    'purge',
    'preview',
    'customise',
    'attachments',
    'password_hashes',
];
```

Toolbox uses separate configurable connections for PostfixAdmin/Postfix and
Roundcube data. The relevant settings are:

- `toolbox_postfix_dsnw` / `toolbox_postfix_dsnr`: PostfixAdmin/Postfix SQL
  write/read connection strings.
- `toolbox_roundcube_dsnw` / `toolbox_roundcube_dsnr`: Roundcube SQL
  write/read connection strings.
- `toolbox_postfix_sql_mailbox_table_name` and
  `toolbox_postfix_sql_username_field_in_mailbox`: mailbox table and login
  identifier column, reused by the Password Hashes tool.

Examples for a PostgreSQL installation (replace placeholders with your values):

```php
$config['toolbox_postfix_dsnw'] = 'pgsql://postfix_user:db_password@localhost/postfix';
$config['toolbox_postfix_dsnr'] = ''; // Empty: use the write connection for reads.

$config['toolbox_roundcube_dsnw'] = 'pgsql://roundcube_user:db_password@localhost/roundcubemail';
$config['toolbox_roundcube_dsnr'] = '';
```

Or for MySQL:

```php
$config['toolbox_postfix_dsnw'] = 'mysql://postfix_user:db_password@localhost/postfix';
$config['toolbox_postfix_dsnr'] = '';

$config['toolbox_roundcube_dsnw'] = 'mysql://roundcube_user:db_password@localhost/roundcubemail';
$config['toolbox_roundcube_dsnr'] = '';
```

These are example connection strings, not actual credentials. Configure a
read-only DSN in a `_dsnr` setting only when you use a separate reader.

Consult `config.inc.php.dist` for the remaining per-tool options, domain-admin
configuration, vacation formatting, attachment limits and storage settings.

### Additional password hashes

`password_hashes` is a **background module**. Enabling it in `toolbox_tools`
registers the `password_change` hook; it does not add a Toolbox Settings tab or
require an explicit PHP include in the configuration.

Put the **PASSWORD HASHES TOOL OPTIONS** section with the other tool settings,
immediately before **PLUGIN LOG/DEBUG OPTIONS**. Example:

```php
// PASSWORD HASHES TOOL OPTIONS

// Additional hash destinations must not overwrite these mailbox columns.
$config['toolbox_password_hashes_protected_fields'] = [
    'password',
    'created',
    'modified',
];

// Add as many independently configured hash columns as needed.
$config['toolbox_password_additional_algorithms'] = [
    'argon2id' => [
        'algorithm' => PASSWORD_ARGON2ID,
        'options' => [
            'memory_cost' => 65536,
            'time_cost'   => 3,
            'threads'     => 1,
        ],
        'prefix'     => '{ARGON2ID}',
        'field_name' => 'password_argon2id',
    ],

    // Example of another destination required by an external application:
    // 'external_sha256' => [
    //     'algorithm' => 'sha256',
    //     'options'    => [],
    //     'prefix'     => '{SHA256}',
    //     'field_name' => 'password_sha256',
    // ],
];
```

- Each entry independently specifies its `algorithm`, `options`, `prefix` and
  destination `field_name`. Any number of entries can use the same algorithm.
  PHP's `password_hash()` algorithms are supported dynamically using
  `password_algos()` (including `PASSWORD_DEFAULT`, bcrypt and available
  Argon2 variants). PHP `hash()` digest algorithms are available through
  `hash_algos()` (for example SHA-256, SHA-512, or MD5). This covers algorithms
  exposed by these two APIs; other PHP hashing mechanisms such as HMAC and
  `sodium_crypto_pwhash()` are not implemented by this module.
- For a `hash()` digest, set `algorithm` to its PHP algorithm name (for
  example `'sha256'`, `'sha512'`, or `'md5'`) and use `options => []`.
  The digest is stored as lowercase hexadecimal text, preceded by the
  configured `prefix`. These algorithms can be useful for compatibility with
  specific external applications, authentication protocols, and legacy systems.
  **Fast, unsalted digests are not suitable as standalone password-verification
  hashes.** The `hash_algos()` list also contains non-cryptographic checksums,
  which are not suitable for security-sensitive authentication. Administrators
  should choose each algorithm according to the consuming system's requirements.
  Available algorithms depend on the PHP installation.
- Destination columns **must already exist** in the configured mailbox table.
  Toolbox does **not** create them. Before enabling the tool, create each
  configured column and grant the `toolbox_postfix_dsnw` database user permission
  to update it. Otherwise, remove `password_hashes` from `toolbox_tools` until
  the schema is ready.
- Keep the primary password field in the protected list. If your primary
  password column is named `passwd`, replace `password` in the list with
  `passwd`. PostfixAdmin's standard `created` and `modified` columns should
  remain protected.
- The module **never reads, verifies or updates the primary password hash**.
  It identifies the mailbox by the existing configured username column and
  writes only explicitly configured additional-hash fields.
- The hook runs when a password-changing integration emits
  `password_change`. Roundcube's standard Password plugin emits it after a
  successful update, but is not required for the hashing implementation. Other
  integrations can participate if they emit the same event with `new_pass`.
  These writes are not one database transaction: if the additional-hash write
  fails, the primary password change has already succeeded. Review Toolbox
  logs for collection errors. Password changes outside Roundcube are not
  collected by this hook.
- The plugin's own password-change configuration (including Samba/NT hash
  updates, if configured) is not modified by Toolbox.

### Logging and troubleshooting

Toolbox uses `toolbox_logfile` and `toolbox_loglevel` in `config.inc.php`.
For the Password Hashes module:

| Log level | Output |
| --- | --- |
| `0` | No messages |
| `1` | Errors |
| `2` | Error stage and successful operation counts |
| `3` or above | Execution steps, affected row counts, and SQL **templates** with `?` placeholders |

The Password Hashes module deliberately avoids logging plaintext passwords,
generated hashes, usernames and SQL parameter values. Its SQL debug shows
columns, table and query shape, not expanded parameters. Use level 3 only
when troubleshooting, then restore your normal logging level.

## Code organisation

Tool-specific PHP modules live in `tools/`, with shared preferences in
`tools/preferences.php`. Tool-specific JavaScript is separated into
`js/toolbox.aliases.js`, `js/toolbox.forward.js`, `js/toolbox.vacation.js` and
`js/toolbox.customise.js`; `js/toolbox.js` retains shared functionality.
Vacation's datepicker integration is part of its module. The old
`toolbox.calendar.js` and `toolbox.doubleclick.js` are obsolete.

## License

Toolbox is released under
[GNU GPL-3.0-or-later](https://www.gnu.org/licenses/gpl.html), except skin
assets, which use the
[Creative Commons Attribution-ShareAlike License](https://creativecommons.org/licenses/by-sa/3.0/).

See [CHANGELOG.md](CHANGELOG.md) for release history.
