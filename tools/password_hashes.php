<?php

/**
 * Background Toolbox module for collecting additional mailbox password hashes.
 *
 * Automatically loaded when 'password_hashes' is enabled in toolbox_tools.
 * The successful password_change hook runs after Roundcube's Password plugin
 * updates the primary password. No Toolbox settings section is displayed.
 */
final class toolbox_tool_password_hashes extends toolbox_tool_module
{
    private bool $hook_registered = false;

    /**
     * Register the Password plugin's post-success event once per module.
     *
     * @return void
     */
    public function init(): void
    {
        if ($this->hook_registered) {
            return;
        }

        $this->plugin->add_hook('password_change', [$this, 'password_change']);
        $this->hook_registered = true;
    }

    /**
     * Hide this background-only integration from Toolbox Settings.
     *
     * @return bool Always false; password hashes have no settings form.
     */
    public function is_visible_in_settings(): bool
    {
        return false;
    }

    /**
     * Generate and save all configured additional hashes after password change.
     *
     * The password and hashes are never logged. SQL debug output contains only
     * parameter placeholders, never actual bound values. The callback returns the
     * untouched hook arguments so Roundcube can update the session normally.
     * A failure in this secondary, post-change operation cannot undo the
     * successful primary password change.
     *
     * @param array $args Roundcube hook arguments including new_pass.
     * @return array Original hook arguments, unchanged.
     */
    public function password_change(array $args): array
    {
        $entries = $this->rcube->config->get('toolbox_password_additional_algorithms', []);
        if ($entries === []) {
            return $args;
        }

        $stage = 'configuration validation';
        $this->log_message(3, 'STEP in [function password_change]: password change hook triggered');

        try {
            if (!is_array($entries)) {
                throw new InvalidArgumentException('Invalid algorithm configuration');
            }

            $password = $args['new_pass'] ?? null;
            $username = $this->rcube->get_user_name();
            $dsn = $this->rcube->config->get('toolbox_postfix_dsnw');
            if (!is_string($password) || $password === '' || !is_string($username) || $username === ''
                || !is_string($dsn) || $dsn === '') {
                throw new InvalidArgumentException('Missing password or database configuration');
            }

            $storage = $this->parse_storage();

            $stage = 'hash definition validation';
            $specifications = [];
            foreach ($entries as $entry) {
                $specification = $this->parse_algorithm_entry($entry, $storage);
                $column = $specification['field_name'];
                if (isset($specifications[$column])) {
                    throw new InvalidArgumentException('Duplicate password hash field name');
                }
                $specifications[$column] = $specification;
            }

            $stage = 'hash generation';
            $this->log_message(3, 'STEP in [function password_change]: validated '
                . count($specifications) . ' additional hash definition(s)');

            $hashes = [];
            foreach ($specifications as $column => $specification) {
                $hashes[$column] = $this->make_hash($password, $specification);
            }

            $stage = 'database update';
            $this->log_message(3, 'STEP in [function password_change]: generated '
                . count($hashes) . ' additional hash(es)');
            $this->save_hashes($storage, $username, $hashes, $dsn);
            $this->log_message(2, 'INFO in [function password_change]: stored '
                . count($hashes) . ' additional hash(es)');
        } catch (Throwable $exception) {
            // Never log exception messages or traces: driver errors may embed
            // actual SQL values, including generated password hashes.
            $this->log_message(1, 'ERROR in [function password_change]: additional hash collection failed');
            $this->log_message(2, 'ERROR in [function password_change]: failed during ' . $stage);
        }

        return $args;
    }

    /**
     * Check that a column or SQL identifier segment is safe to interpolate.
     *
     * @param mixed $identifier Candidate SQL identifier.
     * @return bool Whether it is a simple lowercase SQL identifier.
     */
    private function is_safe_identifier($identifier): bool
    {
        return is_string($identifier)
            && (bool) preg_match('/^[a-z_][a-z0-9_]*$/D', $identifier);
    }

    /**
     * Resolve mailbox table, username, and protected destinations from config.
     *
     * Additional hashes are never stored in protected columns. The username
     * column is also protected automatically because it identifies the row.
     * The primary password is neither read nor examined by this tool.
     *
     * @return array Validated storage identifiers and protected destinations.
     */
    private function parse_storage(): array
    {
        $config = $this->rcube->config;
        $table = $config->get('toolbox_postfix_sql_mailbox_table_name', 'mailbox');
        $username = $config->get('toolbox_postfix_sql_username_field_in_mailbox', 'username');
        $protected = $config->get('toolbox_password_hashes_protected_fields', []);

        if (!is_string($table) || !is_array($protected)
            || !$this->is_safe_identifier($username)) {
            throw new InvalidArgumentException('Invalid mailbox storage identifiers');
        }

        $parts = explode('.', $table);
        if (count($parts) > 2 || count($parts) < 1) {
            throw new InvalidArgumentException('Invalid mailbox table name');
        }
        foreach ($parts as $part) {
            if (!$this->is_safe_identifier($part)) {
                throw new InvalidArgumentException('Unsafe mailbox table name');
            }
        }
        foreach ($protected as $field) {
            if (!$this->is_safe_identifier($field)) {
                throw new InvalidArgumentException('Invalid protected field');
            }
        }

        // The username field identifies the mailbox and must not be changed.
        $protected[] = $username;

        return [
            'table_sql' => implode('.', array_map(static fn($part) => '`' . $part . '`', $parts)),
            'username_field' => $username,
            'protected_fields' => array_values(array_unique($protected)),
        ];
    }

    /**
     * Validate a hash destination and select the PHP hashing API dynamically.
     *
     * Algorithms provided by password_algos() use password_hash(), while
     * algorithms provided by hash_algos() use hash(). Digest algorithms do not
     * provide password-specific work factors or automatic salts; they may be
     * required by external applications for compatibility.
     *
     * @param mixed $entry One configured hash destination.
     * @param array $storage Validated mailbox identifiers and protected fields.
     * @return array Validated field, algorithm, options, prefix, and API.
     */
    private function parse_algorithm_entry($entry, array $storage): array
    {
        if (!is_array($entry)) {
            throw new InvalidArgumentException('Invalid password-hash entry');
        }

        $field = $entry['field_name'] ?? null;
        $algorithm = $entry['algorithm'] ?? null;
        $prefix = $entry['prefix'] ?? null;
        $options = $entry['options'] ?? [];

        if (!$this->is_safe_identifier($field)
            || in_array($field, $storage['protected_fields'], true)) {
            throw new InvalidArgumentException('Unsafe or protected target field');
        }

        if (!is_string($prefix) || !preg_match('/^(?:|\{[A-Z][A-Z0-9_-]*\})$/D', $prefix)
            || !is_array($options) || !is_string($algorithm) || $algorithm === '') {
            throw new InvalidArgumentException('Invalid hashing algorithm, prefix or options');
        }

        if (in_array($algorithm, password_algos(), true)) {
            $api = 'password_hash';
            if ($algorithm === PASSWORD_BCRYPT) {
                $allowed = ['cost'];
            } elseif ((defined('PASSWORD_ARGON2I') && $algorithm === PASSWORD_ARGON2I)
                || (defined('PASSWORD_ARGON2ID') && $algorithm === PASSWORD_ARGON2ID)) {
                $allowed = ['memory_cost', 'time_cost', 'threads'];
            } else {
                // New password_hash() algorithms may introduce new options.
                $allowed = null;
            }

            foreach ($options as $key => $value) {
                if (!is_string($key)
                    || ($allowed !== null && !in_array($key, $allowed, true))
                    || ($allowed !== null && (!is_int($value) || $value <= 0))) {
                    throw new InvalidArgumentException('Invalid password-hash option');
                }
                if ($algorithm === PASSWORD_BCRYPT && ($value < 4 || $value > 31)) {
                    throw new InvalidArgumentException('Invalid bcrypt cost');
                }
            }
        } elseif (in_array($algorithm, hash_algos(), true)) {
            // The hash() API does not accept password_hash() work factors.
            if ($options !== []) {
                throw new InvalidArgumentException('Digest algorithms do not support options');
            }
            $api = 'hash';
        } else {
            throw new InvalidArgumentException('Unsupported PHP hashing algorithm');
        }

        return [
            'field_name' => $field,
            'algorithm' => $algorithm,
            'options' => $options,
            'prefix' => $prefix,
            'api' => $api,
        ];
    }

    /**
     * Produce a hash using PHP's configured password or digest hashing API.
     *
     * @param string $password New mailbox password, never logged.
     * @param array $specification Validated algorithm, options, prefix, and API.
     * @return string Prefixed hash for the configured destination.
     */
    private function make_hash(string $password, array $specification): string
    {
        if ($specification['api'] === 'hash') {
            $result = hash($specification['algorithm'], $password);
        } else {
            // PHP bcrypt silently truncates inputs longer than 72 bytes.
            if ($specification['algorithm'] === PASSWORD_BCRYPT && strlen($password) > 72) {
                throw new InvalidArgumentException('Bcrypt password too long');
            }
            $result = password_hash(
                $password,
                $specification['algorithm'],
                $specification['options']
            );
        }

        if (!is_string($result) || $result === '') {
            throw new RuntimeException('Password hashing failed');
        }

        return $specification['prefix'] . $result;
    }

    /**
     * Write all additional hashes in one SQL UPDATE identified by username.
     *
     * Only explicitly configured, validated hash columns are assigned. No
     * primary password is read, checked, or changed; no plaintext is sent to
     * the database. This runs after Roundcube's password_change hook fires.
     *
     * @param array $storage Validated table and field identifiers.
     * @param string $username Current user's mailbox name.
     * @param array $hashes Validated field names mapped to generated hashes.
     * @param string $dsn Roundcube/Postfix writable database DSN.
     * @return void
     */
    private function save_hashes(
        array $storage,
        string $username,
        array $hashes,
        string $dsn
    ): void {
        if (!$hashes) {
            return;
        }

        $assignments = [];
        $parameters = [];
        foreach ($hashes as $column => $hash) {
            $assignments[] = '`' . $column . '` = ?';
            $parameters[] = $hash;
        }
        $parameters[] = $username;

        $sql = 'UPDATE ' . $storage['table_sql'] . ' SET ' . implode(', ', $assignments)
            . ' WHERE `' . $storage['username_field'] . '` = ?';

        // Log the exact SQL template, not Roundcube's parameter-expanded SQL.
        // This reveals the targeted table, columns and placeholders, but none
        // of the password hashes or the mailbox username.
        $this->log_message(3, 'SQL in [function save_hashes]: ' . $sql);
        $this->log_message(3, 'STEP in [function save_hashes]: connecting to mailbox database');

        $db = rcube_db::factory($dsn, $dsn, false);
        // Roundcube expands query parameters before SQL execution. Suppress
        // SQL debug output and query-bearing database error logging for this
        // connection because those messages would expose generated hashes.
        $db->set_debug(false);
        $db->set_option('ignore_errors', true);
        $db->db_connect('w');

        if ($db->is_error()) {
            throw new RuntimeException('Additional password-hash connection failed');
        }

        $result = $db->query($sql, ...$parameters);
        if (!$result || $db->is_error($result)) {
            throw new RuntimeException('Additional password-hash update failed');
        }
        $affected = $db->affected_rows($result);
        $this->log_message(3, 'STEP in [function save_hashes]: SQL affected rows: ' . $affected);
        if ($affected !== 1) {
            throw new RuntimeException('Additional hash update did not match exactly one mailbox');
        }
    }

    /**
     * Write safe diagnostic events at the configured Toolbox log level.
     *
     * Level 1: failures. Level 2: failure stage and success counts.
     * Level 3: execution steps and parameterized SQL templates. Neither
     * passwords, generated hashes, SQL parameters nor usernames are logged.
     *
     * @param int $level Minimum logging level required for this message.
     * @param string $message Sanitized, non-sensitive diagnostic text.
     * @return void
     */
    private function log_message(int $level, string $message): void
    {
        if ((int) $this->rcube->config->get('toolbox_loglevel', 1) >= $level) {
            rcube::write_log(
                (string) $this->rcube->config->get('toolbox_logfile', 'toolbox.log'),
                'Toolbox password hashes: ' . $message
            );
        }
    }
}

// END OF FILE

