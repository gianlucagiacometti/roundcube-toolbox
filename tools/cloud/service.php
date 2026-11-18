<?php

/**
 * Request-scoped Toolbox Cloud service for WebDAV, Google Drive, OneDrive, Dropbox and Compose.
 *
 * Requires the existing personal-vault classes and provider adapters. This is a
 * server-side service, not an HTTP controller; the future Toolbox module must
 * authenticate requests, check CSRF tokens and throttle unlock attempts.
 */

require_once __DIR__ . '/accounts.php';
require_once __DIR__ . '/webdav.php';
require_once __DIR__ . '/google_drive.php';
require_once __DIR__ . '/google_oauth.php';
require_once __DIR__ . '/onedrive.php';
require_once __DIR__ . '/microsoft_oauth.php';
require_once __DIR__ . '/dropbox_oauth.php';
require_once __DIR__ . '/dropbox.php';

/**
 * Coordinate an authenticated user's vault, owned accounts and file imports.
 *
 * Provider credentials are never returned from public methods. An explicitly
 * unlocked server-side session may hold a short-lived raw vault key; the
 * controller validates its ownership and expiry before passing it here.
 */
final class toolbox_cloud_service
{
    private const DEFAULT_MAX_FILE_BYTES = 104857600; // 100 MiB per file.

    private rcube $rcube;
    private int $user_id;
    private toolbox_cloud_vaults $vaults;
    private ?string $session_key;

    /**
     * Bind to Roundcube's authenticated user and its own user database.
     *
     * @param rcube $rcube Authenticated Roundcube instance.
     */
    public function __construct(rcube $rcube, ?string $session_key = null)
    {
        $id = (int) ($rcube->user->ID ?? 0);
        if ($id < 1) {
            throw new RuntimeException('Toolbox Cloud requires an authenticated user');
        }
        $db = $rcube->get_dbh();
        if (!$db instanceof rcube_db) {
            throw new RuntimeException('Roundcube user database is unavailable');
        }
        $this->rcube = $rcube;
        $this->user_id = $id;
        $this->vaults = new toolbox_cloud_vaults($db, $id);
        $this->session_key = $session_key;
    }

    /**
     * Report whether the current user has an initialized personal Cloud vault.
     */
    public function has_vault(): bool
    {
        return $this->vaults->exists();
    }

    /**
     * List configured vault unlock methods for the authenticated user.
     * This reveals no password, recovery value or raw vault key.
     *
     * @return array{password: bool, recovery: bool}
     */
    public function vault_unlock_methods(): array
    {
        return $this->vaults->unlock_methods();
    }

    /**
     * Create a personal vault with a password, recovery key, or both.
     *
     * The optional recovery secret must be displayed exactly once over HTTPS;
     * never log it or put it into session, cookies or persistent browser state.
     *
     * @return ?string Recovery secret, when requested; otherwise null.
     */
    public function create_vault(?string $password, bool $with_recovery): ?string
    {
        return $this->vaults->create($password, $with_recovery);
    }

    /**
     * Change or add a personal vault password after unlocking the vault.
     * Account credentials remain encrypted under the unchanged vault key.
     */
    public function change_vault_password(string $method, string $secret, string $new_password): void
    {
        if (strlen($new_password) < 12 || strlen($new_password) > 4096) {
            throw new InvalidArgumentException('Vault password length is invalid');
        }
        $key = $this->unlock_vault($method, $secret);
        try {
            $this->vaults->set_password($key, $new_password);
        } finally {
            sodium_memzero($key);
        }
    }

    /**
     * Regenerate the one-time recovery key after successful vault unlocking.
     * Return the key only for the initiating HTTPS response; never persist it.
     */
    public function rotate_vault_recovery(string $method, string $secret): string
    {
        $key = $this->unlock_vault($method, $secret);
        try {
            return $this->vaults->rotate_recovery($key);
        } finally {
            sodium_memzero($key);
        }
    }

    /**
     * Unlock the user's vault explicitly for a short-lived authenticated server session.
     * The caller owns the returned key and must erase its local copy after use.
     *
     * @param string $method Valid user-controlled unlock method.
     * @param string $secret User-supplied password or recovery key.
     * @return string Raw 32-byte vault key; do not log or return to the browser.
     */
    public function unlock_for_session(string $method, string $secret): string
    {
        if ($method === 'session') {
            throw new InvalidArgumentException('Explicit unlock requires a personal secret');
        }
        return $this->unlock_vault($method, $secret);
    }

    /** Unlock an existing user vault and return its raw key for this request. */
    private function unlock_vault(string $method, string $secret): string
    {
        if ($secret === '' && $method !== 'session') {
            throw new InvalidArgumentException('Unlock secret required');
        }
        return match ($method) {
            'password' => $this->vaults->unlock_password($secret),
            'recovery' => $this->vaults->unlock_recovery($secret),
            'session' => $this->session_key !== null && strlen($this->session_key) === 32
                ? (string) $this->session_key : throw new RuntimeException('Cloud vault locked'),
            default => throw new InvalidArgumentException('Unknown vault unlock method'),
        };
    }

    /**
     * List only this user's account metadata, never decrypted credentials.
     *
     * @param string $method Either 'password' or 'recovery'.
     * @param string $secret Password/recovery secret supplied for this request.
     */
    public function list_accounts(string $method, string $secret): array
    {
        return $this->with_accounts($method, $secret, static function (toolbox_cloud_accounts $accounts): array {
            return $accounts->list_accounts();
        });
    }

    /**
     * Store a Google Drive OAuth grant under the authenticated user's vault.
     * The caller must complete state/PKCE verification and contact Google first.
     *
     * @param array $tokens Validated Google tokens, including a refresh token.
     * @param string $name Trusted account label returned by Google Drive.
     * @return string New numeric account identifier.
     */
    public function add_google_account(array $tokens, string $name): string
    {
        if (!is_string($tokens['refresh_token'] ?? null) || $tokens['refresh_token'] === '') {
            throw new InvalidArgumentException('Missing Google refresh token');
        }
        return $this->with_accounts('session', '', static function (toolbox_cloud_accounts $accounts) use ($tokens, $name): string {
            return $accounts->create('google_drive', $name, null, [
                'refresh_token' => $tokens['refresh_token'],
                'access_token' => $tokens['access_token'],
                'scope' => $tokens['scope'],
                'token_type' => 'Bearer',
            ], (int) $tokens['expires']);
        });
    }

    /**
     * Persist a user's delegated Microsoft Graph grant in their encrypted vault.
     *
     * OAuth state and PKCE checks are enforced by the controller before call.
     * The OAuth application binding is kept with the encrypted account data.
     */
    public function add_microsoft_account(array $tokens, string $name, array $config): string
    {
        if (!is_string($tokens['refresh_token'] ?? null) || $tokens['refresh_token'] === '') {
            throw new InvalidArgumentException('Missing Microsoft refresh token');
        }
        if (!in_array($config['profile'] ?? null, ['business', 'legacy', 'personal'], true)
            || !is_string($config['client_id'] ?? null)) {
            throw new InvalidArgumentException('Missing Microsoft OAuth application binding');
        }
        return $this->with_accounts('session', '', static function (toolbox_cloud_accounts $accounts) use ($tokens, $name, $config): string {
            return $accounts->create($config['profile'] === 'personal' ? 'onedrive_personal' : 'onedrive', $name, null, [
                'refresh_token' => $tokens['refresh_token'],
                'access_token' => $tokens['access_token'],
                'scope' => $tokens['scope'],
                'token_type' => 'Bearer',
                'oauth_profile' => $config['profile'],
                'client_id' => $config['client_id'],
            ], (int) $tokens['expires']);
        });
    }

    /**
     * Persist a Dropbox offline grant under the authenticated user's vault.
     * State and PKCE verification happen in the controller; both subsequent
     * browsing and attachment imports use these encrypted credentials.
     */
    public function add_dropbox_account(array $tokens, string $name): string
    {
        if (!is_string($tokens['refresh_token'] ?? null) || $tokens['refresh_token'] === '') {
            throw new InvalidArgumentException('Missing Dropbox refresh token');
        }
        return $this->with_accounts('session', '', static function (toolbox_cloud_accounts $accounts) use ($tokens, $name): string {
            return $accounts->create('dropbox', $name, null, [
                'refresh_token' => $tokens['refresh_token'],
                'access_token' => $tokens['access_token'],
                'scope' => $tokens['scope'],
                'token_type' => 'Bearer',
            ], (int) $tokens['expires']);
        });
    }

    /**
     * Save one WebDAV/Nextcloud/ownCloud connection owned by this user.
     *
     * The WebDAV URL must be the service's actual public HTTPS DAV root,
     * including the user's directory; no server hostname guessing is done.
     */
    public function add_webdav_account(
        string $method,
        string $secret,
        string $display_name,
        string $endpoint,
        string $username,
        string $app_password
    ): string {
        if ($username === '' || $app_password === '') {
            throw new InvalidArgumentException('WebDAV username and app password are required');
        }
        // Validate the public HTTPS URL shape before persisting credentials.
        // The WebDAV provider revalidates and pins DNS on every network request.
        self::validate_webdav_endpoint($endpoint);
        return $this->with_accounts($method, $secret,
            static function (toolbox_cloud_accounts $accounts) use ($display_name, $endpoint, $username, $app_password): string {
                return $accounts->create('webdav', $display_name, $endpoint, [
                    'username' => $username,
                    'app_password' => $app_password,
                ]);
            });
    }

    /**
     * Update an existing owned WebDAV account without disclosing stored secrets.
     * Empty credential inputs preserve the encrypted username or app password.
     *
     * @param string $method Authenticated vault unlock mode.
     * @param string $secret Unlock credential (empty for an unlocked session).
     * @param string $account_id Numeric owned Cloud account ID.
     * @param string $display_name Human-readable account label.
     * @param string $endpoint Public HTTPS WebDAV endpoint.
     * @param string $username Replacement username, or empty to keep the original.
     * @param string $app_password Replacement password, or empty to keep the original.
     * @return bool Whether the owned account was updated.
     */
    public function update_webdav_account(
        string $method,
        string $secret,
        string $account_id,
        string $display_name,
        string $endpoint,
        string $username,
        string $app_password
    ): bool {
        if (!preg_match('/^[1-9][0-9]{0,18}$/D', $account_id)) {
            throw new InvalidArgumentException('Invalid account ID');
        }
        self::validate_webdav_endpoint($endpoint);
        $updated = $this->with_accounts($method, $secret,
            static function (toolbox_cloud_accounts $accounts) use ($account_id, $display_name, $endpoint, $username, $app_password): bool {
                return $accounts->update_webdav($account_id, $display_name, $endpoint, $username, $app_password);
            });
        if (!$updated) {
            throw new RuntimeException('Cloud account not found');
        }
        return true;
    }

    /**
     * Browse a user-owned cloud account using an unlocked personal vault.
     *
     * @return array File entries and optional pagination cursor, no secrets.
     */
    public function list_files(
        string $method,
        string $secret,
        string $account_id,
        ?string $folder_id = null,
        ?string $cursor = null
    ): array {
        return $this->with_accounts($method, $secret,
            static function (toolbox_cloud_accounts $accounts) use ($account_id, $folder_id, $cursor): array {
                $account = self::owned_provider_account($accounts, $account_id);
                if ($account['provider'] === 'google_drive') {
                    $credentials = $account['credentials'];
                    $expires = (int) ($account['expires'] ?? 0);
                    if ($expires <= time() + 90) {
                        $google = toolbox_cloud_google_oauth::settings(rcube::get_instance());
                        $refreshed = toolbox_cloud_google_oauth::refresh($google, $credentials['refresh_token'] ?? '');
                        $credentials['access_token'] = $refreshed['access_token'];
                        $accounts->replace_credentials($account_id, $credentials, $refreshed['expires']);
                        $account['credentials'] = $credentials;
                    }
                    return (new toolbox_cloud_google_drive())->list_files($account, $folder_id, $cursor);
                }
                if (in_array($account['provider'], ['onedrive', 'onedrive_personal'], true)) {
                    $account = self::refresh_microsoft_if_needed($accounts, $account_id, $account);
                    return (new toolbox_cloud_onedrive())->list_files($account, $folder_id, $cursor);
                }
                if ($account['provider'] === 'dropbox') {
                    $account = self::refresh_dropbox_if_needed($accounts, $account_id, $account);
                    return (new toolbox_cloud_dropbox())->list_files($account, $folder_id, $cursor);
                }
                return (new toolbox_cloud_webdav())->list_files($account, $folder_id, $cursor);
            });
    }

    /**
     * Verify WebDAV GET downloads without attaching or returning file contents.
     *
     * Test-only operation: stream at most 20 MiB to a disposable PHP stream,
     * verify the provider's byte count, and close the stream on every path.
     * No local file is retained and no content is sent to the browser.
     *
     * @param string $method Vault unlock mode, assigned by the controller.
     * @param string $secret Vault secret or empty string for a valid session.
     * @param string $account_id Authenticated user's account identifier.
     * @param string $file_id Relative WebDAV file identifier from listing.
     * @return array{size: int, verified: bool} Verified download metadata.
     */
    public function test_download(
        string $method,
        string $secret,
        string $account_id,
        string $file_id
    ): array {
        $limit = 20 * 1024 * 1024;
        if ($file_id === '' || strlen($file_id) > 4096) {
            throw new InvalidArgumentException('Invalid remote file identifier');
        }
        $stream = fopen('php://temp/maxmemory:1048576', 'w+b');
        if ($stream === false) {
            throw new RuntimeException('Unable to create WebDAV verification stream');
        }
        try {
            $metadata = $this->with_accounts($method, $secret,
                static function (toolbox_cloud_accounts $accounts) use ($account_id, $file_id, $stream, $limit): array {
                    $account = self::owned_webdav_account($accounts, $account_id);
                    return (new toolbox_cloud_webdav())->download_file($account, $file_id, $stream, $limit);
                });
            $length = ftell($stream);
            if ($length === false || $length < 0 || $length > $limit
                || !isset($metadata['size']) || (int) $metadata['size'] !== $length) {
                throw new RuntimeException('WebDAV verification size mismatch');
            }
            return ['size' => (int) $length, 'verified' => true];
        } finally {
            fclose($stream);
        }
    }

    /**
     * Delete one owned account; never affects its user's vault or other users.
     */
    public function delete_account(string $method, string $secret, string $account_id): bool
    {
        return $this->with_accounts($method, $secret,
            static function (toolbox_cloud_accounts $accounts) use ($account_id): bool {
                return $accounts->delete($account_id);
            });
    }

    /**
     * Attach a remote cloud provider file to an existing compose session.
     *
     * This downloads with a strict byte cap, checks Roundcube's whole-message
     * size estimate, then calls its non-upload `attachment_save` lifecycle.
     * A successful return is metadata for a future JSON/AJAX controller; the
     * controller must update the compose attachment list client-side.
     *
     * @param string $compose_id Current session's trusted compose identifier.
     * @param int $max_bytes Maximum accepted file bytes; defaults to 100 MiB.
     * @return array{id: string, name: string, size: int, mimetype: string}
     */
    public function attach_file(
        string $method,
        string $secret,
        string $account_id,
        string $file_id,
        string $compose_id,
        int $max_bytes = self::DEFAULT_MAX_FILE_BYTES
    ): array {
        if ($max_bytes < 1 || $max_bytes > self::DEFAULT_MAX_FILE_BYTES) {
            throw new InvalidArgumentException('Cloud attachment exceeds configured service limit');
        }
        if ($compose_id === '' || strlen($compose_id) > 128
            || !preg_match('/^[a-zA-Z0-9_-]+$/D', $compose_id)
            || empty($_SESSION['compose_data_' . $compose_id])) {
            throw new RuntimeException('Invalid or expired Roundcube compose session');
        }
        if ($file_id === '' || strlen($file_id) > 4096) {
            throw new InvalidArgumentException('Invalid remote file identifier');
        }

        // Must be in Roundcube's configured temp directory: filesystem_attachments
        // verifies that path when the message is eventually sent.
        $temp_dir = (string) $this->rcube->config->get('temp_dir');
        if ($temp_dir === '' || !is_dir($temp_dir) || !is_writable($temp_dir)) {
            throw new RuntimeException('Roundcube temp directory is not writable');
        }
        $tmp = tempnam($temp_dir, 'tbxcloud_');
        if ($tmp === false || dirname($tmp) !== rtrim($temp_dir, '/')) {
            if ($tmp !== false) {
                @unlink($tmp);
            }
            throw new RuntimeException('Unable to create safe Cloud temporary file');
        }
        @chmod($tmp, 0600);
        $handoff = false;
        try {
            $stream = fopen($tmp, 'wb');
            if ($stream === false) {
                throw new RuntimeException('Unable to open Cloud temporary file');
            }
            try {
                $metadata = $this->with_accounts($method, $secret,
                    static function (toolbox_cloud_accounts $accounts) use ($account_id, $file_id, $stream, $max_bytes): array {
                        $account = self::owned_provider_account($accounts, $account_id);
                        if ($account['provider'] === 'google_drive') {
                            $credentials = $account['credentials'];
                            if ((int) ($account['expires'] ?? 0) <= time() + 90) {
                                $google = toolbox_cloud_google_oauth::settings(rcube::get_instance());
                                $refreshed = toolbox_cloud_google_oauth::refresh($google, $credentials['refresh_token'] ?? '');
                                $credentials['access_token'] = $refreshed['access_token'];
                                $accounts->replace_credentials($account_id, $credentials, $refreshed['expires']);
                                $account['credentials'] = $credentials;
                            }
                            return (new toolbox_cloud_google_drive())->download_file($account, $file_id, $stream, $max_bytes);
                        }
                        if (in_array($account['provider'], ['onedrive', 'onedrive_personal'], true)) {
                            $account = self::refresh_microsoft_if_needed($accounts, $account_id, $account);
                            return (new toolbox_cloud_onedrive())->download_file($account, $file_id, $stream, $max_bytes);
                        }
                        if ($account['provider'] === 'dropbox') {
                            $account = self::refresh_dropbox_if_needed($accounts, $account_id, $account);
                            return (new toolbox_cloud_dropbox())->download_file($account, $file_id, $stream, $max_bytes);
                        }
                        return (new toolbox_cloud_webdav())->download_file($account, $file_id, $stream, $max_bytes);
                    });
            } finally {
                fclose($stream);
            }
            $size = filesize($tmp);
            if ($size === false || $size > $max_bytes || (int) $metadata['size'] !== $size) {
                throw new RuntimeException('Cloud attachment size verification failed');
            }
            $name = self::safe_filename((string) ($metadata['name'] ?? ''));
            $mime = (string) ($metadata['mime_type'] ?? 'application/octet-stream');
            if (!preg_match('~^[a-zA-Z0-9!#$&^_.+-]+/[a-zA-Z0-9!#$&^_.+-]+$~D', $mime)) {
                $mime = 'application/octet-stream';
            }
            $this->assert_message_size((int) $size, $mime, $compose_id);
            $attachment = [
                'path' => $tmp,
                'name' => $name,
                'size' => (int) $size,
                'mimetype' => $mime,
                'group' => $compose_id,
            ];
            $inserted = $this->rcube->insert_uploaded_file($attachment, 'attachment_save');
            if (!$inserted || empty($attachment['id'])) {
                throw new RuntimeException('Roundcube could not register Cloud attachment');
            }
            // Roundcube now owns the file's lifetime; do not delete it here.
            $handoff = true;
            return [
                'id' => (string) $attachment['id'],
                'name' => $name,
                'size' => (int) $size,
                'mimetype' => $mime,
            ];
        } finally {
            if (!$handoff) {
                @unlink($tmp);
            }
        }
    }

    /**
     * Unlock with one user-controlled method and clear key after callback.
     *
     * @param callable $operation Function receiving a scoped account repository.
     * @return mixed The operation's non-secret result.
     */
    private function with_accounts(string $method, string $secret, callable $operation)
    {
        if ($method === 'session') {
            if ($this->session_key === null || strlen($this->session_key) !== 32) {
                throw new RuntimeException('Cloud vault locked');
            }
            $key = (string) $this->session_key;
        } elseif ($method === 'password') {
            $key = $this->vaults->unlock_password($secret);
        } elseif ($method === 'recovery') {
            $key = $this->vaults->unlock_recovery($secret);
        } else {
            throw new InvalidArgumentException('Unknown Cloud vault unlock method');
        }
        try {
            return $operation(new toolbox_cloud_accounts($this->rcube->get_dbh(), $this->user_id, $key));
        } finally {
            sodium_memzero($key);
        }
    }

    /**
     * Resolve one owned account and its credentials; fail on unsupported driver.
     *
     * WebDAV is available for downloads; Google Drive for listing. Credentials must never
     * leave the PHP request or be included in API responses or logs.
     */
    /**
     * Resolve an owned WebDAV or Google Drive account for read-only listing.
     * No decrypted credential is exposed outside this PHP request.
     */
    /**
     * Refresh a Microsoft Graph access token while the personal vault is open.
     * Preserve the old refresh token unless Microsoft provides a rotated one.
     * Resolve the account's original OAuth application (legacy or Business),
     * then save the complete credentials encrypted and owner-scoped.
     */
    private static function refresh_microsoft_if_needed(
        toolbox_cloud_accounts $accounts,
        string $account_id,
        array $account
    ): array {
        if ((int) ($account['expires'] ?? 0) > time() + 90) {
            return $account;
        }
        $credentials = $account['credentials'];
        $profile = $credentials['oauth_profile'] ?? 'legacy';
        if (!is_string($profile) || !in_array($profile, ['business', 'legacy', 'personal'], true)) {
            throw new RuntimeException('Invalid Microsoft account configuration binding');
        }
        try {
            $config = toolbox_cloud_microsoft_oauth::settings(rcube::get_instance(), $profile);
        } catch (RuntimeException $error) {
            // Accounts created before profile tracking may use the same Entra
            // application after its settings have moved to the Business group.
            if ($profile !== 'legacy') {
                throw $error;
            }
            $config = toolbox_cloud_microsoft_oauth::settings(rcube::get_instance(), 'business');
        }
        if (isset($credentials['client_id'])
            && (!is_string($credentials['client_id'])
                || !hash_equals($credentials['client_id'], $config['client_id']))) {
            throw new RuntimeException('Microsoft OAuth application no longer matches the connected account');
        }
        $refreshed = toolbox_cloud_microsoft_oauth::refresh($config, (string) ($credentials['refresh_token'] ?? ''));
        $credentials['access_token'] = $refreshed['access_token'];
        if ($refreshed['refresh_token'] !== null) {
            $credentials['refresh_token'] = $refreshed['refresh_token'];
        }
        $accounts->replace_credentials($account_id, $credentials, $refreshed['expires']);
        $account['credentials'] = $credentials;
        $account['expires'] = $refreshed['expires'];
        return $account;
    }

    /**
     * Refresh an expiring Dropbox token and re-encrypt its credential envelope.
     * Keep the existing refresh token if Dropbox does not rotate it.
     *
     * @param toolbox_cloud_accounts $accounts Owner-scoped encrypted repository.
     * @param string $account_id Stored numeric Cloud account identifier.
     * @param array $account Decrypted account context.
     * @return array Account context containing the current access token.
     */
    private static function refresh_dropbox_if_needed(
        toolbox_cloud_accounts $accounts,
        string $account_id,
        array $account
    ): array {
        if ((int) ($account['expires'] ?? 0) > time() + 90) {
            return $account;
        }
        $credentials = $account['credentials'];
        $config = toolbox_cloud_dropbox_oauth::settings(rcube::get_instance());
        $refreshed = toolbox_cloud_dropbox_oauth::refresh($config, (string) ($credentials['refresh_token'] ?? ''));
        $credentials['access_token'] = $refreshed['access_token'];
        if ($refreshed['refresh_token'] !== null) {
            $credentials['refresh_token'] = $refreshed['refresh_token'];
        }
        $accounts->replace_credentials($account_id, $credentials, $refreshed['expires']);
        $account['credentials'] = $credentials;
        $account['expires'] = $refreshed['expires'];
        return $account;
    }

    /** Resolve a user-owned supported provider and decrypt only its credentials. */
    private static function owned_provider_account(toolbox_cloud_accounts $accounts, string $id): array
    {
        if (!preg_match('/^[1-9][0-9]{0,18}$/D', $id)) {
            throw new InvalidArgumentException('Invalid Cloud account identifier');
        }
        $row = $accounts->find($id);
        if (!$row || !in_array($row['provider'], ['webdav', 'google_drive', 'onedrive', 'onedrive_personal', 'dropbox'], true)) {
            throw new RuntimeException('Cloud account not found or provider unavailable');
        }
        $credentials = $accounts->credentials_for($id);
        if (!is_array($credentials)) {
            throw new RuntimeException('Cloud account credentials unavailable');
        }
        $row['credentials'] = $credentials;
        return $row;
    }

    private static function owned_webdav_account(toolbox_cloud_accounts $accounts, string $id): array
    {
        if (!preg_match('/^[1-9][0-9]{0,18}$/D', $id)) {
            throw new InvalidArgumentException('Invalid Cloud account identifier');
        }
        $row = $accounts->find($id);
        if (!$row || $row['provider'] !== 'webdav') {
            throw new RuntimeException('Cloud account not found or provider unavailable');
        }
        $credentials = $accounts->credentials_for($id);
        if ($credentials === null) {
            throw new RuntimeException('Cloud account credentials are unavailable');
        }
        $row['credentials'] = $credentials;
        return $row;
    }

    /**
     * Reject URLs that would be rejected by the public WebDAV driver.
     *
     * The provider performs DNS resolution and strict public-network checks
     * again at request time; this check does not make a network request.
     */
    private static function validate_webdav_endpoint(string $endpoint): void
    {
        $url = parse_url($endpoint);
        if ($url === false || strtolower($url['scheme'] ?? '') !== 'https'
            || empty($url['host']) || isset($url['user']) || isset($url['pass'])
            || isset($url['query']) || isset($url['fragment'])
            || ($url['port'] ?? 443) !== 443) {
            throw new InvalidArgumentException('WebDAV requires a public HTTPS endpoint on port 443');
        }
        $host = strtolower($url['host']);
        if (filter_var($host, FILTER_VALIDATE_IP) !== false
            || strlen($host) > 253 || !str_contains($host, '.')
            || str_ends_with($host, '.local')
            || !preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/D', $host)) {
            throw new InvalidArgumentException('WebDAV endpoint host must be public DNS');
        }
        $path = $url['path'] ?? '/';
        if (preg_match('/%(?:2f|5c|2e|00)/i', $path)
            || str_contains($path, '\\') || str_contains($path, '//')
            || str_contains($path, '/../') || str_contains($path, '/./')) {
            throw new InvalidArgumentException('Unsafe WebDAV endpoint path');
        }
    }

    /**
     * Normalize an untrusted remote filename into one safe attachment name.
     */
    private static function safe_filename(string $name): string
    {
        if (str_contains($name, '/') || str_contains($name, '\\')) {
            throw new RuntimeException('Cloud returned an unsafe filename');
        }
        $name = trim($name);
        if ($name === '' || $name === '.' || $name === '..' || strlen($name) > 255
            || preg_match('/[\x00-\x1f\x7f]/', $name)) {
            throw new RuntimeException('Cloud returned an invalid filename');
        }
        return $name;
    }

    /**
     * Enforce configured Roundcube whole-message size, including attachments.
     *
     * Roundcube normally estimates base64 transport overhead at 1.33; this
     * implementation uses the more conservative 4/3 ratio for safety.
     * If no limit is configured, the per-file service limit still applies.
     */
    private function assert_message_size(int $new_size, string $mime, string $compose_id): void
    {
        $limit = $this->rcube->config->get('max_message_size');
        $max = is_numeric($limit) ? (int) $limit : (function_exists('parse_bytes') ? (int) parse_bytes($limit) : 0);
        if ($max <= 0) {
            return;
        }
        $total = 10240;
        foreach ($this->rcube->list_uploaded_files($compose_id) as $existing) {
            $size = max(0, (int) ($existing['size'] ?? 0));
            $total += ($existing['mimetype'] ?? '') === 'message/rfc822' ? $size : (int) ceil($size * 4 / 3);
        }
        $total += $mime === 'message/rfc822' ? $new_size : (int) ceil($new_size * 4 / 3);
        if ($total > $max) {
            throw new RuntimeException('Cloud file exceeds Roundcube maximum message size');
        }
    }
}

// END OF FILE
