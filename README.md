# Roundcube Toolbox

A modular collection of tools for [Roundcube Webmail](https://github.com/roundcube/roundcubemail).
Several tools integrate with [PostfixAdmin](https://github.com/postfixadmin/postfixadmin)
and Postfix SQL backends. Each enabled tool owns its own PHP and (when needed)
JavaScript module.

## Compatibility and requirements

- **Toolbox release:** 1.7.11.
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
- **Cloud:** optional; requires PHP `sodium`, `curl`, `mbstring` and XML/DOM
  support, HTTPS, and write access to the Roundcube user database for the
  Cloud-specific tables. Provider integrations also require separate OAuth
  application registrations and permissions; WebDAV requires a reachable
  HTTPS endpoint. Provider production approval is handled independently
  of this plugin release.
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
| `folders` | View read-only IMAP folder properties, message counts and on-demand folder size. |
| `cloud` | Connect read-only cloud storage accounts, browse remote files and attach selected files to composed messages. |
| `login_monitor` | Record successful logins and review recent account activity, device details and optional offline location information. |
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
5. Prepare the writable `plugins/toolbox/tmp` directory for Customise and
   `plugins/toolbox/attachments` for Attachments, when these tools are enabled.
   Run `bin/initplugin` from the plugin directory to create the standard
   directories, or create them manually with appropriate permissions. If
   `toolbox_detach_storage` specifies another location, create that directory
   separately.
6. Before using Tags, [compile its LESS stylesheet](#compile-the-tags-stylesheet)
   to the CSS path loaded by Roundcube.
7. Before using Cloud, create its tables in the Roundcube database using
   the applicable `SQL/mysql.cloud.sql` or `SQL/postgres.cloud.sql` script,
   configure at least one provider or WebDAV, and verify HTTPS callbacks.
   See [Cloud storage attachments](#cloud-storage-attachments).
8. To use Login Monitor, install its SQL tables and local Composer dependencies
   as described in [Login Monitor — Login Activity](#login-monitor--login-activity).

If downloading a GitHub archive, rename its extracted plugin directory to
`toolbox`. Composer installations can use the Roundcube plugin installer.

### Updating an existing installation

Back up `config.inc.php` and your database before upgrading. Copy new options
from `config.inc.php.dist` into your existing private configuration instead of
replacing it wholesale. Review enabled tools and any new database permissions.

Cloud is optional and introduces its own database tables; existing Toolbox
installations do not need those tables unless the `cloud` tool is enabled.
Back up the Roundcube database and review the Cloud SQL schema for your
database engine before applying it. An upgrade does not create the Cloud
tables automatically.

For Attachments, review the `toolbox_detach_storage` setting in your existing
`config.inc.php`: upgrading does not replace private configuration. The new
default is `__DIR__ . '/attachments'` (inside the Toolbox plugin). If moving
storage, copy existing detached files without renaming them to preserve their
current download links. Keep the attachment directory when replacing or
reinstalling the plugin.

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
    'folders',
    'cloud',
    'login_monitor',
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

### Mailbox aliases

The **Aliases** tool lets mailbox users manage additional email addresses that
route to their mailbox through PostfixAdmin. Open **Settings > Toolbox > Aliases**
to add an alias, enable or disable an existing one, or remove it. Changes are
written to the configured PostfixAdmin alias table; this tool does not create
new mailboxes.

The `toolbox_aliases_multiple_domains` option controls whether aliases are
entered as full addresses or as names within the user's domain. Cross-domain
alias creation is restricted to users authorised to administer the target
domain; domain administrators can be defined in PostfixAdmin or with
`toolbox_domain_admins`. The PostfixAdmin database connection must have the
permissions needed to read and update alias records.

### Mail forwarding

The **Forward** tool manages the delivery destinations associated with a mailbox
in PostfixAdmin. In **Settings > Toolbox > Forward**, add or remove destination
addresses and choose whether to **keep copies of original messages** in the
mailbox. With that option disabled, delivery may be directed only to the
configured forwarding recipients. Multiple destinations are supported.

Forwarding is implemented through PostfixAdmin's alias routing, not through an
IMAP message rule. The tool preserves the special vacation destination used by
the PostfixAdmin configuration, so forwarding and automatic replies can coexist.
A writable PostfixAdmin connection and correctly mapped alias fields are
required.

### Vacation auto-replies

The **Vacation** tool provides an editor for PostfixAdmin's out-of-office
responses. Users can enable or disable automatic replies, set the active date
range, edit the subject and message body, and choose how often to reply to the
same sender. Optional HTML editing and jQuery UI date pickers are available.

The defaults and available reply intervals are configured through
`toolbox_vacation_subject`, `toolbox_vacation_message`,
`toolbox_vacation_interval_time`, and the corresponding date/calendar options in
`config.inc.php.dist`. Toolbox updates the PostfixAdmin vacation records;
**PostfixAdmin's vacation delivery mechanism must be configured separately** to
send the replies. The vacation domain and database column mappings must match
the mail system.

### Trash and Junk retention

The **Purge** tool lets users choose how many days messages should remain in
their Trash and Junk folders, or inherit the domain's retention settings.
Domain administrators can set the default periods under **Customise**. A value
of **ever** (zero days in the configuration) means messages are retained
indefinitely by the purge script.

Actual deletion is performed by the separate command-line script
`scripts/purge.php`, which administrators must schedule themselves. The script
reads user preferences and domain defaults, then removes files older than the
selected positive retention period from the Maildir `.Trash/cur` and
`.Junk/cur` directories. It needs access to the PostfixAdmin and Roundcube
databases and to the local Maildir storage layout configured by
`toolbox_purge_maildir_path`. It does not purge remote IMAP folders through the
IMAP protocol. Review the paths and permissions before enabling scheduled
**permanent deletion**.

### Message preview

The **Preview** tool adds a per-user setting to disable the message preview
pane in the Elastic mail interface. Messages can still be opened normally,
including with Roundcube's native double-click action. It does not modify
Roundcube's message read/unread timing; configure that separately using
Roundcube's standard **Mailbox View** preferences.

### Domain customisation

The **Customise** tool is available to authorised domain administrators. It
allows per-domain retention defaults for Trash and Junk, and per-skin changes
to the blank page, additional CSS and logos. Blank pages can use a URL, an
image or custom HTML. Settings saved for a domain override applicable defaults
in `toolbox_customise_skins`.

Domain administrator permissions can come from PostfixAdmin or the static
`toolbox_domain_admins` mapping, controlled by
`toolbox_use_postfixadmin_domain_admins`. Generated blank-page and CSS assets
require a writable `plugins/toolbox/tmp` directory. Grant domain administration
only to trusted users, since custom HTML and CSS change the webmail interface
for users of that domain.

### Large attachment links

The **Attachments** tool copies selected large outgoing attachments into a
dedicated storage directory and replaces the original email attachment with a
small HTML file containing a download link. Recipients use that link to obtain
the original file inside a ZIP archive. Toolbox's PHP download handler supports
arbitrary file extensions, including extensionless files; it does not depend
on Roundcube's `static.php`. Calendar attachments are excluded from detachment.

An attachment is detached when its size reaches `toolbox_detach_single` or the
combined attachment size reaches `toolbox_detach_total`. The default storage
path is `plugins/toolbox/attachments`, configured with
`$config['toolbox_detach_storage'] = __DIR__ . '/attachments';` in
`config.inc.php`. The directory must exist and be writable by the web-server
process. An absolute path can be used for a different storage location. PHP's
ZIP extension is required to serve downloads.

The `toolbox_detach_lifespan` setting specifies the displayed download lifetime
in days (30 by default). To remove expired files, schedule
`scripts/delete_attachments.php` once per day using PHP CLI, for example:

```cron
15 3 * * * /usr/bin/php /path/to/roundcube/plugins/toolbox/scripts/delete_attachments.php
```

The script reads the storage path and lifetime from Toolbox configuration,
requires no database connection, and deletes regular files whose modification
time is at least the configured number of days old. It does not scan
subdirectories or follow symbolic links. The cron account needs permission to
read the directory and delete its files.

**Expiration:** the download handler does not check expiration itself. Links
stop working when the corresponding file is deleted, so a daily cleanup may
leave downloads available until the next run after the displayed expiry date.
Review storage permissions and any direct web-server exposure for your
deployment.

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

### IMAP folder properties

Enable `folders` in `toolbox_tools`. In the **Mail** interface, select a
mailbox and choose **Folder properties** from the mail toolbar. If the optional
`contextmenu` plugin is installed, the same action also appears in the
folder-list right-click menu and targets the folder you clicked.

The dialog shows the folder name, total messages, unread and read counts,
whether the mailbox is a special folder, and its IMAP namespace. The
**Calculate size** button performs a separate, explicit server request.
On IMAP servers without `STATUS=SIZE` (or a fast vendor-specific metadata
implementation), Roundcube may need to examine message-size headers for the
whole folder. This can take time for very large folders.

Folders is read-only: it does not modify messages or folder subscriptions,
does not use the Toolbox SQL storage adapter and requires no database schema.
It has no Settings form. Styling is supplied by
`skins/elastic/toolbox.folders.less` and its compiled CSS.

### Cloud storage attachments

Enable `cloud` in `$config['toolbox_tools']` to add a Cloud section under
**Settings > Toolbox** and an **Attach from Cloud** picker in the mail composer.
Users can connect multiple storage accounts, browse folders and import a
selected remote file as a normal email attachment. The feature reads files;
it does not provide remote editing, file deletion or share-link creation.

Supported connections:

| Connection | Setup and access |
| --- | --- |
| Google Drive | Google OAuth Web client, Drive API, `drive.readonly` scope. |
| OneDrive Business | Microsoft Entra Web client for work/school accounts, delegated `Files.Read`, `User.Read` and `offline_access`. |
| OneDrive Personal | Separate Microsoft Entra Web client for consumer accounts, with the same delegated permissions. |
| Dropbox | Scoped Dropbox app with `account_info.read`, `files.metadata.read` and `files.content.read`. |
| Nextcloud, ownCloud, generic WebDAV | HTTPS WebDAV URL and account-specific username/app password. |

**Requirements and database setup**

1. Enable PHP `sodium`, `curl`, `mbstring` and DOM/XML support. Cloud uses
   libsodium to protect provider credentials at rest, and PHP cURL for remote
   API requests. Use HTTPS for Roundcube and provider endpoints.
2. In the **Roundcube user database**, create `toolbox_cloud_vaults` and
   `toolbox_cloud_accounts` using the matching `SQL/mysql.cloud.sql` or
   `SQL/postgres.cloud.sql` definitions. The Cloud tables are separate from
   the plugin's earlier `*.initial.sql` schemas. Use your database's normal
   backup, permissions and schema-review process; never re-run table-creation
   SQL against tables that already exist.
3. Add `cloud` to the enabled tools. For OAuth providers, populate the relevant
   `toolbox_cloud_*` settings in your private `config.inc.php`. A provider's
   connection card is available only when its required configuration is valid.
   Unconfigured OAuth providers do not prevent WebDAV connections.
4. Register the **exact HTTPS redirect URI** at each provider and ensure it
   matches the corresponding Toolbox setting. Google, OneDrive Business and
   Dropbox use their documented Roundcube Settings action callbacks; OneDrive
   Personal uses a query-free HTTPS callback URL (typically the Roundcube
   root). Consult the examples in `config.inc.php.dist`.
5. Open **Settings > Toolbox > Cloud** to create a personal vault, then connect
   accounts. Vault creation supports a password, a one-time recovery key, or
   both. Keep the recovery key in a safe place; it is not shown again.

**Credential and network handling**

Each authenticated Roundcube user has a separate vault. Provider credentials
and OAuth tokens are stored encrypted using a per-user vault key; the password
or recovery key is used to unlock that key. Unlocks expire automatically and
can be ended manually. Administrators still need to secure the Roundcube
server, its database, HTTPS configuration and backups.

WebDAV uses HTTPS and certificate validation, and rejects private/reserved
network destinations by default. If an administrator genuinely needs an
internal WebDAV server, `toolbox_cloud_trusted_webdav_hosts` allows explicit
host-to-private-IPv4 mappings. Do not configure broad exceptions.

**OAuth applications and production availability**

Toolbox being released on GitHub does **not** mean an OAuth app is verified or
approved by Google, Microsoft or Dropbox. Administrators must register their
own appropriate client applications, configure permissions, comply with each
provider's policies and complete any production or verification requirements.
Google's current `drive.readonly` permission is restricted and may entail
additional verification and security review. Never commit OAuth secrets or
private `config.inc.php` values to the repository.

**Language support**

Cloud Settings, the Compose picker and user-facing backend/OAuth messages use
Roundcube localisation for `en_US`, `en_GB`, `it_IT`, `fr_FR`, `de_DE` and
`es_ES`. Translations other than English/Italian should be reviewed by fluent
speakers before being treated as final.

### Login Monitor — Login Activity

**Login Monitor helps Roundcube users review their previous successful logins and recognise potentially suspicious access to their mailboxes.** A login's date and time, browser, operating system, device description and approximate location can help users distinguish familiar activity from an unexpected login.

The tool records successful logins; it does not record failed authentication attempts, prevent unauthorised access, verify a person's identity or uniquely identify a physical device. Browser and device information depends on what the client provides. In particular, desktop hardware models are often unavailable, and privacy-focused browsers may limit identification. An unfamiliar entry is a reason to investigate, not proof that an account was compromised.

Each user can review their own login history in Roundcube's **Login Activity** settings and choose which optional details to collect and display. History is restricted to the authenticated Roundcube account.

On the first mail page following a successful login, a one-time, dismissible Roundcube **confirmation** notification greets the user by their default identity name (or username), shows the previous successful login in the user's configured timezone and, if available and currently permitted for display, the approximate city. When a valid visible country code accompanies the city, the notification includes its flag. No location line is shown when the previous login has no GeoIP city, including private/local-network addresses. On the first recorded login, it displays a localized “no earlier login” message. The greeting is not repeated on mailbox refreshes.

Country flags use the MIT-licensed [flag-icons](https://github.com/lipis/flag-icons)
4×3 SVGs installed locally through the Login Monitor Composer project. PHP
reads the required SVG from `tools/login_monitor/vendor/` and embeds it in the
notification as an image data URI; the browser makes no CDN request. A Unicode
country flag is used if the SVG or its Composer dependency is unavailable.
Neither flags nor GeoIP lookups require external requests from the browser.


#### Installation

1. Install the Login Monitor history table in the **Roundcube user database**, using `SQL/mysql.login_monitor.sql` or `SQL/postgres.login_monitor.sql`, as appropriate. Login Monitor does not use the PostfixAdmin database. When upgrading an existing Login Monitor installation, apply the SQL migrations supplied for that version rather than running the initial schema again.
2. Enable `login_monitor` in the `toolbox_tools` array in `plugins/toolbox/config.inc.php`:

   ```php
   $config['toolbox_tools'] = [
       // Other enabled Toolbox modules...
       'login_monitor',
   ];
   ```

3. Configure the collection defaults and, if desired, the local GeoIP databases as described below.
4. Compile `skins/elastic/toolbox.login_monitor.less` to
   `skins/elastic/toolbox.login_monitor.css`. This single LESS source includes
   Login Monitor Settings, history, dialog and welcome-toast styles:

   ```sh
   lessc skins/elastic/toolbox.login_monitor.less skins/elastic/toolbox.login_monitor.css
   ```

Login Monitor uses the existing Roundcube user preferences for personal settings; no separate preferences database is required.

#### Browser and device detection

[Matomo Device Detector](https://github.com/matomo-org/device-detector) identifies the browser and version, operating system and version, device type, and, when available, device manufacturer and model. It uses the browser's User-Agent and supported Client Hints. Missing or ambiguous details are left empty rather than guessed.

For independent dependency management, the recommended installation uses the Composer project in `plugins/toolbox/tools/login_monitor/`:

```sh
cd plugins/toolbox/tools/login_monitor
composer install --no-dev --no-plugins --no-scripts
```

This installs Matomo Device Detector, the MaxMind database reader and
flag-icons from the component's own `composer.json` and `composer.lock`,
without changing Roundcube's root Composer files. The `vendor/` directory is
not distributed with Toolbox; install these dependencies on each deployment.

To update the component dependencies and lock file, run the included helper
from the Toolbox root:

```sh
./scripts/update_login_monitor_composer
```

The helper changes into `tools/login_monitor/` and runs
`composer-update --no-dev` when available, otherwise `composer update --no-dev`.
For reproducible deployment of the committed lock file, use `composer install`
instead. Compatible libraries may alternatively be provided by an existing
Composer autoloader, except that local flag images are loaded from the
Login Monitor component's own vendor installation.

Without the detector library, login recording continues, but unavailable
browser and device details remain empty. Without flag-icons, the toast uses a
Unicode flag when a country code is available.

#### Administrator defaults and user preferences

The administrator defines the initial field settings in `plugins/toolbox/config.inc.php` using `toolbox_login_monitor_fields`. Each field has two independent options:

- **Collect:** whether the value is stored when a future successful login occurs.
- **Visible:** whether an already stored value is returned for display in the user's own login history.

Users can adjust these choices in **Login Activity**. The preferences are saved
with the user's Roundcube preferences and apply only to that account. Where no
personal preference has been saved, the administrator's configured defaults
apply. Unavailable GeoIP databases prevent new city/country collection but do
not remove visibility controls for historical records.

Administrators may additionally restrict user choices using the optional `toolbox_login_monitor_user_permissions` setting. For each field, `collect => false` prohibits collection and `visible => false` prohibits disclosure, regardless of saved personal preferences or initial defaults. Omitted flags permit user choice. For example:

```php
$config['toolbox_login_monitor_user_permissions'] = [
    'ip_address' => ['collect' => false, 'visible' => false],
    'user_agent' => ['collect' => false, 'visible' => false],
];
```

The two configuration keys have different roles: `toolbox_login_monitor_fields` supplies initial settings; `toolbox_login_monitor_user_permissions` sets optional limits. Each user's selections are stored separately in their Roundcube preference `toolbox_login_monitor_user_fields`.

Collection and visibility are independent: stopping collection does not delete historical values, and a previously recorded value can remain visible if visibility is enabled. Disabling visibility hides previously recorded values as well as new ones, without deleting them. Re-enabling collection affects subsequent logins only; it does not populate missing fields in older records. The login timestamp remains part of every history entry.

The supplied defaults are:

| Information | Collect | Visible |
| --- | --- | --- |
| Browser name and version | Yes | Yes |
| Operating system name and version | Yes | Yes |
| Device type, brand and model | Yes | Yes |
| Approximate city and country | Yes | Yes |
| IP address | No | No |
| Raw User-Agent | No | No |
| Browser engine | No | No |
| Bot detection | No | No |

The equivalent administrator configuration is:

```php
$config['toolbox_login_monitor_fields'] = [
    'browser_name'    => ['collect' => true,  'visible' => true],
    'browser_version' => ['collect' => true,  'visible' => true],
    'os_name'         => ['collect' => true,  'visible' => true],
    'os_version'      => ['collect' => true,  'visible' => true],
    'device_type'     => ['collect' => true,  'visible' => true],
    'device_brand'    => ['collect' => true,  'visible' => true],
    'device_model'    => ['collect' => true,  'visible' => true],
    'city'            => ['collect' => true,  'visible' => true],
    'country'         => ['collect' => true,  'visible' => true],
    'ip_address'      => ['collect' => false, 'visible' => false],
    'user_agent'      => ['collect' => false, 'visible' => false],
    'browser_engine'  => ['collect' => false, 'visible' => false],
    'bot_detection'   => ['collect' => false, 'visible' => false],
];
```

IP address, raw User-Agent and other optional technical fields are not stored by default. When enabled by the user, their values are stored with that account's login records and subject to its visibility preferences. Administrators remain responsible for configuring the service and meeting applicable data-protection requirements.

#### Optional offline geolocation

Login Monitor can identify an **approximate city and country** from the server-observed client IP using a local MaxMind-format MMDB database and the optional [`maxmind-db/reader`](https://github.com/maxmind/MaxMind-DB-Reader-php) library. Lookups run locally; no IP address is submitted to a third-party geolocation API. The IP address itself is not retained unless its independent collection option is enabled.

City and country paths are configured separately in `plugins/toolbox/config.inc.php`:

```php
// City database, including the country information contained in City records.
$config['toolbox_login_monitor_geoip_city_database'] =
    __DIR__ . '/tools/login_monitor/GeoLite.mmdb/GeoLite2-City.mmdb';

// Optional separate Country database. Leave empty to use the City database.
$config['toolbox_login_monitor_geoip_country_database'] = '';
```

Both settings accept absolute filesystem paths to readable files, including
compatible commercial GeoIP2 editions. The Country database is optional because
City databases also contain country information. If only a Country database is
configured, country lookup is available without city lookup. If a configured
GeoIP database is missing or unreadable, new collection of its city or country
field is disabled, but previously stored values remain visible according to
administrator and user visibility preferences. City and country are handled
independently.

The recommended free-data setup uses [P3TERX/GeoLite.mmdb](https://github.com/P3TERX/GeoLite.mmdb). From `plugins/toolbox/`, run:

```sh
./scripts/install_login_monitor_geoip
```

The script checks out the repository's **`download` branch** into `tools/login_monitor/GeoLite.mmdb/`. That checkout includes `GeoLite2-City.mmdb`, `GeoLite2-Country.mmdb` and `GeoLite2-ASN.mmdb`. City alone is sufficient for both city and country lookup.

An administrator may choose any other location:

```sh
./scripts/install_login_monitor_geoip /var/lib/roundcube/geoip/GeoLite.mmdb
```

Or clone the database repository directly:

```sh
git clone --depth 1 --single-branch --branch download \
    https://github.com/P3TERX/GeoLite.mmdb.git /path/to/GeoLite.mmdb
```

After an alternative installation, point the relevant configuration setting to the actual MMDB file. The MMDB files are managed separately from Composer dependencies. To refresh the default checkout, run:

```sh
./scripts/install_login_monitor_geoip --update
```

Keep GeoIP databases current and follow the chosen provider's licensing and attribution requirements. P3TERX distributes MaxMind GeoLite2 data subject to MaxMind's terms; other compatible database providers may have different terms.

City and country are estimates based on network routing, not verified physical locations. VPNs, mobile networks and internet providers can produce misleading results. Private, loopback, invalid and reserved source addresses are not geolocated. If Roundcube is accessed through an internal network or reverse proxy, `REMOTE_ADDR` may contain a private or proxy address; untrusted forwarded headers are not used as a substitute.

#### Read-only login history

The **Login history** tab lists successful logins, newest first, in pages of 15
records. **Previous successful login** refers to the last recorded login before
the current authenticated session; it becomes available after signing in with
this version of Login Monitor. Each history entry has a **Details** dialog,
which displays only the optional fields permitted by the user's current
visibility preferences. Turning off collection does not conceal previously
stored data while visibility remains enabled.

Pagination reloads only the requested page from the Roundcube database. All
history requests are scoped to the signed-in Roundcube account. This release
does not maintain a persistent device registry or device-identification cookies,
and introduces no automated cleanup jobs or history-deletion controls.

#### Interpreting login history

Review the time and combination of browser, operating system, device type and location when deciding whether a login is familiar. Browser names and versions may change following software updates; a shared or privacy-focused browser may identify itself differently, while two distinct devices may report identical details. A missing city, country, brand or model does not by itself indicate an error or suspicious activity.

Login Monitor provides evidence to help users investigate unexpected activity, but does not replace account authentication, password security or other access controls.

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

Toolbox routes diagnostics from its dispatcher, tools and SQL adapter through
`lib/Toolbox/logger.php`, using the existing `toolbox_logfile` and
`toolbox_loglevel` settings in `config.inc.php`:

| `toolbox_loglevel` | Output |
| --- | --- |
| `0` | No Toolbox diagnostic messages. |
| `1` | `[ERROR]` failure stages and categories. |
| `2` | `[ERROR]` and `[SQL]` schematic query/operation descriptions. |
| `3` | `[ERROR]`, `[SQL]` and `[STEP]` execution stages. |

Messages have component names such as `[cloud]`, `[tags]`, `[attachments]`,
`[password_hashes]` and `[storage.sql]`. The logger avoids passwords, provider
tokens, hashes, usernames, file names, SQL parameter values and unfiltered
exception messages. SQL entries are intentionally redacted descriptions, not
replayable queries. Use level 3 for troubleshooting and restore your normal
setting afterward. Logs are not a substitute for correct error handling: a
failed database operation must fail regardless of the logging level.

## Code organisation

Each enabled tool has a module in `tools/`; tool-specific implementation files
remain in subdirectories such as `tools/cloud/` and `tools/tags/`. Shared
Toolbox classes live in `lib/Toolbox/`:

- `tool.php`: common module contract.
- `preferences.php`: Roundcube preference service.
- `logger.php`: central component-aware logging.

Database storage adapters remain in `lib/Roundcube/`. Tool-specific JavaScript
resides in `js/toolbox.<tool>.js`, while `js/toolbox.js` retains shared
functionality. Cloud uses `js/toolbox.cloud.js` for Settings and
`js/toolbox.cloud.compose.js` for the Compose picker, with Elastic styling in
`skins/elastic/toolbox.cloud.less` and its compiled CSS. Tags uses
`tools/tags/definitions.php`, `js/toolbox.tags.js` and
`js/toolbox.tags.message.js`.

The Cloud Settings view uses Roundcube HTML helpers in `tools/cloud/view.php`;
the standalone OAuth popup callbacks retain their minimal response pages.
The old `tools/logger.php`, `tools/preferences.php`, `tools/tool.php` and
`tools/cloud/logger.php` compatibility files are no longer used and should not
be shipped alongside the relocated implementation.

## Developer checks

The release archive does not include a `tests/` directory or an npm-based
regression-test runner. Run the following syntax checks from the plugin root
using PHP 8.1 or later and Node.js (for the JavaScript check):

```sh
find . -type f -name '*.php' -not -path './vendor/*' -exec php -l {} \;
php -l config.inc.php.dist
find js -maxdepth 1 -type f -name 'toolbox*.js' -exec node --check {} \;
```

These commands check syntax only; they do not test Roundcube integration,
provider OAuth callbacks, database schemas or IMAP behavior. Test each enabled
tool on a staging Roundcube installation before upgrading production.

## License

Toolbox is released under
[GNU GPL-3.0-or-later](https://www.gnu.org/licenses/gpl.html), except skin
assets, which use the
[Creative Commons Attribution-ShareAlike License](https://creativecommons.org/licenses/by-sa/3.0/).

See [CHANGELOG.md](CHANGELOG.md) for release history.
