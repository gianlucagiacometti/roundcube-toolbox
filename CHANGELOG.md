# Roundcube Webmail Toolbox

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
