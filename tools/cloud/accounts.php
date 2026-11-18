<?php

/** Toolbox Cloud accounts persistence module; intended for authenticated server-side use. */

require_once __DIR__ . '/../../lib/Toolbox/logger.php';
require_once __DIR__ . '/vaults.php';

/**
 * Per-user encrypted Cloud account repository for the Roundcube database.
 *
 * Credentials are encrypted with the user's unlocked vault key, never with
 * a server-wide secret. This class does not hold provider HTTP connections.
 */
final class toolbox_cloud_accounts
{
    private rcube_db $db;
    private int $user_id;
    private string $key;

    /**
     * Bind the repository to the authenticated user and their unlocked vault.
     *
     * @param rcube_db $db Roundcube user database handle.
     * @param int $user_id Trusted numeric rcube user ID (not browser input).
     * @param string $unlocked_key Raw 32-byte key from the current unlock call.
     */
    public function __construct(rcube_db $db, int $user_id, string $unlocked_key)
    {
        if ($user_id < 1) {
            throw new InvalidArgumentException('Authenticated user required');
        }
        (new toolbox_cloud_vaults($db, $user_id))->assert_unlocked($unlocked_key);
        $this->db = $db;
        $this->user_id = $user_id;
        $this->key = $unlocked_key;
    }

    /**
     * Add a provider account for the current user; return numeric auto-increment ID.
     * Never store credentials in metadata or endpoint URLs.
     */
    public function create(string $provider, string $display_name, ?string $endpoint, array $credentials, ?int $expires_at = null): string
    {
        $this->validate_metadata($provider, $display_name, $endpoint);
        // The random crypto_id is an internal, permanent AAD binding; unlike
        // the public ID it can be known before the INSERT is performed.
        $crypto_id = bin2hex(random_bytes(16));
        $encrypted = $this->seal_credentials($crypto_id, $provider, $credentials);
        $this->run('INSERT INTO toolbox_cloud_accounts '
            . '(crypto_id, user_id, provider, display_name, endpoint, credentials, expires) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$crypto_id, $this->user_id, $provider, $display_name, $endpoint, $encrypted, $expires_at]);
        // Roundcube's PostgreSQL driver resolves the table-specific _seq name.
        $id = $this->db->insert_id('toolbox_cloud_accounts');
        if (!is_scalar($id) || !preg_match('/^[1-9][0-9]{0,18}$/D', (string) $id)) {
            throw new RuntimeException('Cloud account identifier was not returned by database');
        }
        return (string) $id;
    }

    /** List current user's account metadata without secret fields. */
    public function list_accounts(): array
    {
        $result = $this->run('SELECT id, provider, display_name, endpoint, expires, created, modified '
            . 'FROM toolbox_cloud_accounts WHERE user_id = ? ORDER BY display_name, id', [$this->user_id]);
        $rows = [];
        while ($row = $this->db->fetch_assoc($result)) {
            $rows[] = $row;
        }
        return $rows;
    }

    /** Find one owned account without returning any secrets. */
    public function find(string $id): ?array
    {
        $result = $this->run('SELECT id, provider, display_name, endpoint, expires, created, modified '
            . 'FROM toolbox_cloud_accounts WHERE user_id = ? AND id = ?', [$this->user_id, $id]);
        return $this->db->fetch_assoc($result) ?: null;
    }

    /**
     * Decrypt credentials for an owned account, for server-side driver use only.
     * Never serialize the result to the browser or log it.
     */
    public function credentials_for(string $id): ?array
    {
        $result = $this->run('SELECT provider, credentials, crypto_id FROM toolbox_cloud_accounts '
            . 'WHERE user_id = ? AND id = ?', [$this->user_id, $id]);
        $row = $this->db->fetch_assoc($result);
        if (!$row) {
            return null;
        }
        $json = toolbox_cloud_crypto::open(
            $row['credentials'], $this->key,
            toolbox_cloud_crypto::account_aad($this->user_id, $row['crypto_id'], $row['provider'])
        );
        $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Stored Cloud credentials are invalid');
        }
        return $data;
    }

    /** Replace credentials on an owned account; false if it is not owned. */
    public function replace_credentials(string $id, array $credentials, ?int $expires_at = null): bool
    {
        $account = $this->find($id);
        if (!$account) {
            return false;
        }
        $crypto_id = $this->crypto_id_for($id);
        if ($crypto_id === null) {
            return false;
        }
        $encrypted = $this->seal_credentials($crypto_id, $account['provider'], $credentials);
        $this->run('UPDATE toolbox_cloud_accounts SET credentials = ?, expires = ?, modified = CURRENT_TIMESTAMP '
            . 'WHERE user_id = ? AND id = ?',
            [$encrypted, $expires_at, $this->user_id, $id]);
        return true;
    }

    /**
     * Update one owned account while keeping its permanent encryption binding.
     * A blank username or password preserves its existing encrypted value.
     * The caller must validate the WebDAV endpoint before invoking this method.
     *
     * @param string $id Numeric public account identifier.
     * @param string $display_name New human-readable name.
     * @param string $endpoint New public HTTPS WebDAV URL.
     * @param string $username New username, or empty to preserve the old one.
     * @param string $app_password New password, or empty to preserve the old one.
     * @return bool False when the account is not owned by the current user.
     */
    public function update_webdav(
        string $id,
        string $display_name,
        string $endpoint,
        string $username,
        string $app_password
    ): bool {
        $account = $this->find($id);
        if (!$account || $account['provider'] !== 'webdav') {
            return false;
        }
        $this->validate_metadata('webdav', $display_name, $endpoint);
        $existing = $this->credentials_for($id);
        if ($existing === null) {
            return false;
        }
        $credentials = [
            'username' => $username !== '' ? $username : (string) ($existing['username'] ?? ''),
            'app_password' => $app_password !== '' ? $app_password : (string) ($existing['app_password'] ?? ''),
        ];
        if ($credentials['username'] === '' || $credentials['app_password'] === '') {
            throw new InvalidArgumentException('Cloud account credentials are incomplete');
        }
        $crypto_id = $this->crypto_id_for($id);
        if ($crypto_id === null) {
            return false;
        }
        $encrypted = $this->seal_credentials($crypto_id, 'webdav', $credentials);
        $this->run('UPDATE toolbox_cloud_accounts '
            . 'SET display_name = ?, endpoint = ?, credentials = ?, modified = CURRENT_TIMESTAMP '
            . 'WHERE user_id = ? AND id = ?',
            [$display_name, $endpoint, $encrypted, $this->user_id, $id]);
        return true;
    }

    /** Delete one owned account and leave all other users' accounts untouched. */
    public function delete(string $id): bool
    {
        $result = $this->run('DELETE FROM toolbox_cloud_accounts WHERE user_id = ? AND id = ?',
            [$this->user_id, $id]);
        return $this->db->affected_rows($result) > 0;
    }

    /**
     * Retrieve the internal encryption binding for an owned account.
     *
     * The crypto_id must not change when the public auto-increment ID changes.
     *
     * @param string $id Public account ID.
     * @return ?string Random identifier used by authenticated encryption.
     */
    private function crypto_id_for(string $id): ?string
    {
        $result = $this->run('SELECT crypto_id FROM toolbox_cloud_accounts WHERE user_id = ? AND id = ?',
            [$this->user_id, $id]);
        $row = $this->db->fetch_assoc($result);
        return $row ? (string) $row['crypto_id'] : null;
    }

    /** Encode a credential map with account-specific authenticated context. */
    private function seal_credentials(string $crypto_id, string $provider, array $credentials): string
    {
        $json = json_encode($credentials, JSON_THROW_ON_ERROR);
        return toolbox_cloud_crypto::seal(
            $json, $this->key,
            toolbox_cloud_crypto::account_aad($this->user_id, $crypto_id, $provider)
        );
    }

    /** Validate metadata shape; actual WebDAV SSRF checks belong to drivers. */
    private function validate_metadata(string $provider, string $display_name, ?string $endpoint): void
    {
        if (!preg_match('/^[a-z][a-z0-9_]{0,31}$/D', $provider)) {
            throw new InvalidArgumentException('Invalid cloud provider identifier');
        }
        if (trim($display_name) === '' || strlen($display_name) > 255) {
            throw new InvalidArgumentException('Invalid account display name');
        }
        if ($endpoint !== null) {
            if (strlen($endpoint) > 2048 || !filter_var($endpoint, FILTER_VALIDATE_URL)) {
                throw new InvalidArgumentException('Invalid Cloud endpoint');
            }
            $parts = parse_url($endpoint);
            if ($parts === false || strtolower($parts['scheme'] ?? '') !== 'https'
                || isset($parts['user']) || isset($parts['pass'])
                || isset($parts['query']) || isset($parts['fragment'])) {
                throw new InvalidArgumentException('Cloud endpoint requires an HTTPS URL without credentials or query');
            }
        }
    }

    /** Execute a parameterized query using Roundcube's database abstraction. */
    private function run(string $sql, array $args = [])
    {
        toolbox_logger::sql('cloud', $sql);
        $result = $this->db->query($sql, ...$args);
        if ($result === false || $this->db->is_error()) {
            throw new RuntimeException('Cloud account database operation failed');
        }
        return $result;
    }
}

// END OF FILE
