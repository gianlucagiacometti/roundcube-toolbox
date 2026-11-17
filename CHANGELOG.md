# Roundcube Webmail Toolbox


## Version 1.7.7 (2026-11-17, rc-1.7)

### New Tags tool

- Added the optional `tags` tool, enabled through `toolbox_tools`, with personal
  parent names, child values and colours stored in Roundcube preferences.
  Message assignments use stable IMAP keywords.
- Added a tree editor with explicit New, Edit and Delete controls. Definitions
  can be managed in Settings or in the Tag definitions view of the mail dialog.
- Assign or remove tags from one message at a time through the toolbar,
  optional message-list context menu, or Edit tags control in the message
  header. The toolbar entry sits beside Thunderbird Labels when available.
- Display applied tags outside the message header's collapsed Details section.
  Message-list badges show two values plus a remainder count, with full text
  available in tooltips and the accessible row description.
- Enable list badges by default for users without a saved preference, while
  preserving existing opt-outs. The per-user checkbox remains available in
  Settings and the modal definition editor.
- Extend the normal search box with complete tag names and values, ignoring
  case. Tag matches are combined with ordinary text matches; Type/Unread and
  Date filters continue to apply to both. Existing explicit search syntax is
  preserved.
- Keep message headers, list badges and submitted searches synchronized after
  confirmed changes, with request-order and mailbox checks for late responses.

### Installation and validation

- Supply Tags styles as standalone `skins/elastic/toolbox.tags.less`; compile
  it to `skins/elastic/toolbox.tags.css` with a LESS compiler before using the
  tool.
- Enable Tags in existing private configurations by adding `tags` to the
  existing tool list. No new database tables or schema migration are required.
- Clarified the existing `jqueryui` dependency in the README. This reflects
  Toolbox's existing Settings editor behavior; dependency-loading code is
  unchanged.
- Added 76 Tags JavaScript behavior checks, PHP 8.1 syntax parsing, and an optional
  PHP search suite using the pinned Roundcube 1.7.2 parser. The standalone PHP
  suite was not executed during release validation; feature behavior was
  confirmed separately on the development installation.


## Version 1.7.6 (2026-11-16, rc-1.7)

### New feature

- Added the optional background 'password_hashes' tool, enabled through
  $config['toolbox_tools'], with no visible Settings tab.
- Collect additional hashes when Roundcube's `password_change` hook is emitted.
  The standard Password plugin is one possible event source; hash generation
  itself uses PHP and does not depend on the Password plugin.
- Configure any number of additional-hash destinations independently, each
  with its own PHP algorithm, supported work factors, scheme prefix and target
  column. Reusing an algorithm for multiple columns is supported.
- Support available PHP `password_hash()` algorithms through `password_algos()`
  and PHP digest algorithms through `hash_algos()`, including fast hashes
  required by some external applications. Fast digests and non-cryptographic
  checksums are not appropriate as standalone password-verification hashes.
- Protect configured mailbox columns from additional-hash writes, including the
  primary password and PostfixAdmin's created/modified fields by default.
- Added password-hash diagnostic logging using Toolbox log levels, including
  parameterized SQL templates at the highest level without logging passwords,
  hashes, usernames or SQL parameter values.

### Modularisation

- Refactored the PHP plugin into independent tool modules, with a shared module
  interface and central preferences helper. Modules now own their settings,
  assets, access rules and event registrations.
- Split tool-specific JavaScript into Aliases, Forward, Vacation and Customise
  modules; retained shared sorting, Save and navigation helpers in toolbox.js.
- Consolidated Vacation's datepicker integration into its module, making the old
  toolbox.calendar.js obsolete; removed the redundant double-click handler.
- Kept the background-only password-hash module outside the Settings interface.
- Removed bundled legacy skin directories in favour of Elastic while retaining
  dynamic skin configuration and discovery in the PHP code.

### Bug fixes and compatibility

- Fixed the Aliases enable/disable switch events with Roundcube's Elastic skin.
- Fixed immediate settings refresh after saving Purge, Preview and Vacation.
- Fixed Customise's enabled/disabled flag handling across database boolean
  representations, including Custom CSS visibility and runtime application.
- Fixed saving per-domain Trash/Junk purge periods by correcting SQL row
  existence checks, no-op update handling and timestamp expressions.
- Fixed Customise HTML editor initialization and CodeMirror integration.
- Fixed Blank Page/logo thumbnail and fallback asset URLs for Roundcube's
  static.php resource handling.
- Improved Customise's CSS active-line contrast in Elastic dark mode.
- Restored Roundcube's native message read/unread handling, removing Toolbox's
  conflicting double-click toggle; native mail_read_time remains authoritative.
- Fixed PHP warnings caused by missing configuration values in the
  Customise and Vacation modules.
- Prevented undefined array key warnings when a username does not
  contain a valid domain, without changing valid username handling.
- Fixed Vacation Reply Type selection when reopening the form,
  preserving saved values including zero (PR #33).
- Fixed multiple-domain alias handling, including full-address
  duplicate checks, domain validation, authorization and correct
  SQL domain assignment (issue #29).
- Improved attachment directory initialization and temporary
  directory setup.
- Improved the reliability and safety of the purge cron script.


## Version 1.7.0 (2025-02-10, rc-1.7)

- Fix path for blankpage after RC introduced static.php to manage static resources
- Updated version number to follow the last Rouncube release


## Version 1.6.0 (2023-01-01, rc-1.6)

- Updated version number


## Version 1.5.0.0 (2021-10-19, rc-1.5)

- Updated version number to follow the last Rouncube release


## Version 1.4.11.0 (2021-06-03, rc-git-master)

- Changed license to GNU GPLv3+ with exceptions for skins
- Fixed mysql date format conversion in sql helper
- Fixed checkbox colours in dark mode


## Version 1.4.10.2 (2021-01-27, rc-git-master)

- Added tool Message Preview
- Fixed visualisation bugs when loading blankpage on small screens
- re-numbering sub-versions


## Version 1.4.10.1 (2021-01-16, rc-git-master)
- Added tool Detach Attachments
- Fixed load customised css from database


## Version 1.4.10.0 (2021-01-15, rc-1.4.10)

- Updated version number to follow the last Rouncube release


## Version 1.4.9.0 (2021-01-14, rc-1.4.9)

- Fixed function rcmail_action::get_form_tags()
- Fixed warnings in error.log for valiables not defined
- Fixed minimum requirement for Roundcube plugin installer
- Independent version of Codemirror


## Version 1.4.7.0 (2020-07-08, rc-1.4.7)

- Fixed set_env with blankpage. Setting a base64-encoded page instead of a link to a physical page makes the plugin hang.
- Added tool Customise


## Version 1.4.6.0 (2020-06-27, rc-1.4.6)

- Added tool Aliases
- Added tool Forward
- Added tool Purge
- Added tool Vacation
