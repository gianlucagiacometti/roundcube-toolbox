# Roundcube Toolbox

A modular collection of tools for [Roundcube Webmail](https://github.com/roundcube/roundcubemail).
Several tools integrate with [PostfixAdmin](https://github.com/postfixadmin/postfixadmin)
and Postfix SQL backends. Each enabled tool owns its own PHP and (when needed)
JavaScript module.

## Compatibility and requirements

- **Toolbox release:** 1.7.7.
- **Roundcube:** 1.7.2 or newer (the recommended Composer minimum). Use the
  latest Roundcube 1.7 security release in production.
- **PHP:** 8.1 or newer, as required by Roundcube 1.7.
- **Skin:** Elastic is the bundled, tested skin. Toolbox still supports dynamic
  skin configuration; other skins are not included in this release.
- **PostfixAdmin/SQL access:** required only for tools that manage mailbox,
  alias, forwarding, vacation or domain records, including password hashes.
- **Roundcube `jqueryui` plugin:** Toolbox loads this plugin for its Settings
  editor. Vacation's optional calendar datepickers also use it.
- **Tags:** requires an IMAP server that accepts custom keywords and permission
  to change message flags in the selected mailbox. The optional `contextmenu`
  plugin provides the message-list context-menu entry; the toolbar and message
  header controls are available independently.
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
| `tags` | Manage personal tag names and values, assign them to messages, display badges, and search by tag name or value. |
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
6. Before using Tags, [compile its LESS stylesheet](#compile-the-tags-stylesheet)
   to the CSS path loaded by Roundcube.

If downloading a GitHub archive, rename its extracted plugin directory to
`toolbox`. Composer installations can use the Roundcube plugin installer.

### Updating an existing installation

Back up `config.inc.php` and your database before upgrading. Copy new options
from `config.inc.php.dist` into your existing private configuration instead of
replacing it wholesale. Review enabled tools and any new database permissions.

To enable Tags in an existing installation, add `tags` to the existing
`$config['toolbox_tools']` array in your private configuration, then compile its
stylesheet and reload Roundcube. Tags stores definitions and display options in
Roundcube user preferences and requires no new database tables or schema
migration.

### Compile the Tags stylesheet

The repository supplies Tags styles as the standalone LESS source
`skins/elastic/toolbox.tags.less`. Compile it before using Tags. From the plugin
root, with `lessc` available, run:

```sh
lessc skins/elastic/toolbox.tags.less skins/elastic/toolbox.tags.css
```

The generated `skins/elastic/toolbox.tags.css` is the file loaded by Roundcube.
You can use your own LESS compiler to produce this file; no automatic build
step is required.

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
    'tags',
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

### Personal message tags

Enable `tags` in `toolbox_tools`, then open **Settings > Toolbox > Tags**.
Create parent names, such as **Project**, and child values, such as
**Graduation**. The tree editor provides New, Edit and Delete controls, with a
colour for each value. Names, values and colours are personal preferences;
message assignments use stable IMAP keywords. Renaming a definition preserves
its assignments. Deleting a definition hides its existing message keywords
from the Tags interface.

Open the Tags dialog from the mail toolbar, the optional message-list context
menu, or **Edit tags** beside an opened message's badges. The toolbar button is
placed beside Thunderbird Labels when that plugin is present. The dialog has
two views: **Message tags** for assignment and **Tag definitions** for editing
the same definition tree without leaving the mail page. Applied tags remain
visible in the message header outside the collapsed Details section.

**Tag changes apply to one message at a time.** The context-menu action targets
the clicked message. A collapsed thread row represents its own message; child
messages keep their own assignments. The server's write result and confirmed
flags determine whether a change succeeds. A rejected write is reported in the
dialog rather than displayed as a successful assignment.

#### Message-list display

**Show tags in the message list** is a per-user checkbox in the Tags settings
and the modal definition editor. It defaults to enabled when no preference has
been saved. An existing saved opt-out remains disabled.

Tagged rows show up to two compact badges below the subject, followed by `+N`
for additional tags. Full names and values remain available in the tooltip and
accessible row description. Untagged messages have no extra line. Turning off
list badges leaves assignments, header badges and tag searches available.
Saved names, colours and display preferences update the visible list directly.

#### Search by a name or value

Use Roundcube's normal search box:

- `Project` includes messages with any active value under that parent name.
- `Graduation` includes messages with that value under any active parent.
- Matching uses the complete name or value and ignores case. Multiword names
  work directly or as a single quoted phrase, such as `"Final Project"`.

Results include the ordinary matches in the selected search fields **or**
messages carrying a matching personal tag. Existing Type/Unread and Date
filters apply to both branches. An unknown or partial name leaves normal text
search unchanged. Explicit Roundcube search fields, Boolean operators and
negative quoted terms retain their existing meaning. Tag searching works
whether list badges are enabled or hidden.

For a search submitted in the current mail view, a confirmed assignment or
definition change can refresh the results when the dialog closes. That refresh
uses the submitted search and returns to page one; unfinished edits in the
search controls are preserved.

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

Tags uses `tools/tags.php` for settings, mail actions and search integration,
with its definition store in `tools/tags/definitions.php`. The settings editor
uses `js/toolbox.tags.js`; message assignment, badges and search refresh use
`js/toolbox.tags.message.js`.

## Developer checks

The optional test dependencies are separate from the plugin runtime. With
Node.js 18 or newer, run `npm install` followed by `npm test` from `tests/`.
The v1.7.7 Tags checks cover 76 JavaScript behaviors and PHP 8.1 syntax parsing.
The JavaScript suite executes the actual mail script with isolated DOM and
Roundcube fixtures.

The separate PHP search suite loads the production classes and pinned
Roundcube 1.7.2 parser sources. With PHP 8.1+, `mbstring`, and a local Roundcube
1.7.2 checkout, run this from the plugin root:

```sh
TAGS_ROUNDCUBE_ROOT=/path/to/roundcubemail-1.7.2 php tests/mail-search-tests.php
```

It reports `SKIP` when its prerequisites are unavailable. The standalone PHP
search suite was not executed as part of this release's local validation; Tags
behavior was separately confirmed on the development installation. These test
scripts are run manually and do not connect to a live IMAP server.

## License

Toolbox is released under
[GNU GPL-3.0-or-later](https://www.gnu.org/licenses/gpl.html), except skin
assets, which use the
[Creative Commons Attribution-ShareAlike License](https://creativecommons.org/licenses/by-sa/3.0/).

See [CHANGELOG.md](CHANGELOG.md) for release history.
