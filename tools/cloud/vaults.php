<?php

/** Toolbox Cloud vaults persistence module; intended for authenticated server-side use. */

require_once __DIR__ . '/../../lib/Toolbox/logger.php';
require_once __DIR__ . '/crypto.php';

/**
 * Per-user vault creation and unlocking. Call only with the trusted numeric
 * Roundcube user ID, never a user ID supplied by a browser request.
 *
 * Important: returned key bytes must remain in request-local memory. Never
 * persist them in a PHP session, a cookie, a log or a browser response.
 */
final class toolbox_cloud_vaults
{
    private rcube_db $db;
    private int $user_id;

    /** Bind the repository to one authenticated Roundcube account. */
    public function __construct(rcube_db $db, int $user_id)
    {
        if ($user_id < 1) {
            throw new InvalidArgumentException('Authenticated Roundcube user required');
        }
        toolbox_cloud_crypto::check_runtime();
        $this->db = $db;
        $this->user_id = $user_id;
    }

    /**
     * Initialize the user's vault with a password, recovery key or both.
     *
     * The generated recovery secret is returned ONLY once and must be shown
     * directly to the authenticated user over HTTPS. Do not log it.
     *
     * @return ?string Newly generated recovery secret, if enabled.
     */
    public function create(?string $password, bool $with_recovery): ?string
    {
        if (($password === null || $password === '') && !$with_recovery) {
            throw new InvalidArgumentException('At least one unlock method is required');
        }
        if ($this->exists()) {
            throw new RuntimeException('Cloud vault already exists');
        }
        $vault_key = toolbox_cloud_crypto::new_vault_key();
        $salt = null;
        $ops = null;
        $memory = null;
        $password_wrap = null;
        $recovery_wrap = null;
        $recovery_secret = null;
        $key_check = toolbox_cloud_crypto::seal(
            'Toolbox Cloud vault key verification v1', $vault_key,
            toolbox_cloud_crypto::vault_aad($this->user_id, 'verify')
        );
        try {
            if ($password !== null && $password !== '') {
                $salt_raw = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
                $salt = base64_encode($salt_raw);
                $ops = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE;
                $memory = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE;
                $wrap_key = toolbox_cloud_crypto::password_key($password, $salt_raw, $ops, $memory);
                $password_wrap = toolbox_cloud_crypto::seal(
                    $vault_key, $wrap_key, toolbox_cloud_crypto::vault_aad($this->user_id, 'password')
                );
                sodium_memzero($wrap_key);
            }
            if ($with_recovery) {
                $recovery_secret = toolbox_cloud_crypto::new_recovery_secret();
                $wrap_key = toolbox_cloud_crypto::recovery_key($recovery_secret);
                $recovery_wrap = toolbox_cloud_crypto::seal(
                    $vault_key, $wrap_key, toolbox_cloud_crypto::vault_aad($this->user_id, 'recovery')
                );
                sodium_memzero($wrap_key);
            }
            $this->run('INSERT INTO toolbox_cloud_vaults '
                . '(user_id, password_salt, password_ops, password_memory, password_wrap, recovery_wrap, key_check) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$this->user_id, $salt, $ops, $memory, $password_wrap, $recovery_wrap, $key_check]);
            return $recovery_secret;
        } finally {
            sodium_memzero($vault_key);
        }
    }

    /** Return whether this user already created a Cloud vault. */
    public function exists(): bool
    {
        return $this->vault_row() !== null;
    }

    /**
     * Report configured unlock mechanisms without exposing wrapping material.
     * These boolean flags are UI hints, never authentication or authorization.
     *
     * @return array{password: bool, recovery: bool}
     */
    public function unlock_methods(): array
    {
        $row = $this->vault_row();
        return [
            'password' => $row !== null && !empty($row['password_wrap']) && !empty($row['password_salt']),
            'recovery' => $row !== null && !empty($row['recovery_wrap']),
        ];
    }

    /**
     * Unlock using the user's personal password. Return the 32-byte raw key
     * for immediate use by toolbox_cloud_accounts within the same request.
     * The caller MUST zero it with sodium_memzero() after use.
     */
    public function unlock_password(string $password): string
    {
        $row = $this->vault_row();
        if (!$row || !$row['password_wrap'] || !$row['password_salt']) {
            throw new RuntimeException('Password unlock not configured');
        }
        $salt = base64_decode($row['password_salt'], true);
        if ($salt === false) {
            throw new RuntimeException('Invalid password salt');
        }
        $key = toolbox_cloud_crypto::password_key(
            $password, $salt, (int) $row['password_ops'], (int) $row['password_memory']
        );
        try {
            return toolbox_cloud_crypto::open(
                $row['password_wrap'], $key,
                toolbox_cloud_crypto::vault_aad($this->user_id, 'password')
            );
        } finally {
            sodium_memzero($key);
        }
    }

    /**
     * Unlock using the generated 256-bit recovery key, if enabled.
     * Caller MUST erase returned key bytes after use with sodium_memzero().
     */
    public function unlock_recovery(string $secret): string
    {
        $row = $this->vault_row();
        if (!$row || !$row['recovery_wrap']) {
            throw new RuntimeException('Recovery unlock not configured');
        }
        $key = toolbox_cloud_crypto::recovery_key($secret);
        try {
            return toolbox_cloud_crypto::open(
                $row['recovery_wrap'], $key,
                toolbox_cloud_crypto::vault_aad($this->user_id, 'recovery')
            );
        } finally {
            sodium_memzero($key);
        }
    }

    /**
     * Set or replace a password-based unlock method using an ALREADY unlocked
     * vault key. Existing account credentials remain encrypted as before.
     */
    public function set_password(string $vault_key, string $new_password): void
    {
        $this->assert_unlocked($vault_key);
        if ($new_password === '') {
            throw new InvalidArgumentException('Vault password must not be empty');
        }
        $salt_raw = random_bytes(SODIUM_CRYPTO_PWHASH_SALTBYTES);
        $ops = SODIUM_CRYPTO_PWHASH_OPSLIMIT_INTERACTIVE;
        $memory = SODIUM_CRYPTO_PWHASH_MEMLIMIT_INTERACTIVE;
        $key = toolbox_cloud_crypto::password_key($new_password, $salt_raw, $ops, $memory);
        try {
            $wrapped = toolbox_cloud_crypto::seal(
                $vault_key, $key,
                toolbox_cloud_crypto::vault_aad($this->user_id, 'password')
            );
        } finally {
            sodium_memzero($key);
        }
        $this->run('UPDATE toolbox_cloud_vaults SET password_salt = ?, password_ops = ?, '
            . 'password_memory = ?, password_wrap = ?, modified = CURRENT_TIMESTAMP WHERE user_id = ?',
            [base64_encode($salt_raw), $ops, $memory, $wrapped, $this->user_id]);
    }

    /**
     * Replace/create a recovery method using the already unlocked vault key.
     * Existing recovery secret immediately becomes invalid on successful update.
     *
     * @return string Newly generated recovery secret, displayed only once.
     */
    public function rotate_recovery(string $vault_key): string
    {
        $this->assert_unlocked($vault_key);
        $secret = toolbox_cloud_crypto::new_recovery_secret();
        $key = toolbox_cloud_crypto::recovery_key($secret);
        try {
            $wrapped = toolbox_cloud_crypto::seal(
                $vault_key, $key,
                toolbox_cloud_crypto::vault_aad($this->user_id, 'recovery')
            );
        } finally {
            sodium_memzero($key);
        }
        $this->run('UPDATE toolbox_cloud_vaults SET recovery_wrap = ?, modified = CURRENT_TIMESTAMP WHERE user_id = ?',
            [$wrapped, $this->user_id]);
        return $secret;
    }

    /**
     * Verify an in-memory key against the existing vault, not merely its length.
     * This prevents callers supplying an unrelated 32-byte string and silently
     * creating inaccessible encrypted accounts.
     */
    public function assert_unlocked(string $vault_key): void
    {
        if (strlen($vault_key) !== 32) {
            throw new RuntimeException('Cloud vault is locked');
        }
        $row = $this->vault_row();
        if (!$row) {
            throw new RuntimeException('Cloud vault not initialized');
        }
        // A random vault verifier sealed with the vault key, added to the schema.
        $verification = toolbox_cloud_crypto::open(
            $row['key_check'], $vault_key,
            toolbox_cloud_crypto::vault_aad($this->user_id, 'verify')
        );
        if (!hash_equals('Toolbox Cloud vault key verification v1', $verification)) {
            throw new RuntimeException('Invalid Cloud vault key');
        }
    }

    /** Read exactly this user's vault metadata. */
    private function vault_row(): ?array
    {
        $result = $this->run('SELECT user_id, password_salt, password_ops, password_memory, '
            . 'password_wrap, recovery_wrap, key_check FROM toolbox_cloud_vaults WHERE user_id = ?',
            [$this->user_id]);
        return $this->db->fetch_assoc($result) ?: null;
    }

    /** Execute a parameterized SQL statement with Roundcube's DB adapter. */
    private function run(string $sql, array $args = [])
    {
        toolbox_logger::sql('cloud', $sql);
        $result = $this->db->query($sql, ...$args);
        if ($result === false || $this->db->is_error()) {
            throw new RuntimeException('Toolbox Cloud vault database operation failed');
        }
        return $result;
    }
}

// END OF FILE
