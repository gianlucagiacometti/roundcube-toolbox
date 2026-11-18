<?php

/**
 * Authenticated Roundcube action controller for Toolbox Cloud.
 *
 * Every request is POST with Roundcube CSRF protection. A successfully unlocked
 * raw vault key resides temporarily in the authenticated server-side PHP
 * session, never in a cookie or browser storage. Session expiration or explicit
 * lock destroys it. A one-time recovery key is returned only when generated.
 */

require_once __DIR__ . '/../../lib/Toolbox/logger.php';
require_once __DIR__ . '/service.php';
require_once __DIR__ . '/google_oauth.php';
require_once __DIR__ . '/microsoft_oauth.php';
require_once __DIR__ . '/dropbox_oauth.php';

/**
 * Validate and dispatch authenticated Cloud operations to the existing service.
 *
 * The controller uses a session-local backoff. This prevents repeated guesses
 * within one session but is not a cross-session or distributed rate limiter.
 */
final class toolbox_cloud_controller
{
    private const MAX_FAILURES = 5;
    private const WINDOW_SECONDS = 900;
    private const MAX_SECRET_BYTES = 4096;
    private const SESSION_KEY = 'toolbox_cloud_unlocked';
    private const IDLE_SECONDS = 300;
    private const MAX_SESSION_SECONDS = 900;
    private const GOOGLE_OAUTH_SESSION = 'toolbox_cloud_google_oauth';
    private const MICROSOFT_OAUTH_SESSION = 'toolbox_cloud_microsoft_oauth';
    private const MICROSOFT_PERSONAL_SESSION = 'toolbox_cloud_microsoft_personal_oauth';
    private const DROPBOX_OAUTH_SESSION = 'toolbox_cloud_dropbox_oauth';

    private rcube $rcube;
    private \Closure $translate;

    /**
     * Bind the Roundcube instance and its existing Toolbox translation method.
     *
     * @param rcube $rcube Authenticated Roundcube instance.
     * @param callable $translate Toolbox plugin gettext callback.
     */
    public function __construct(rcube $rcube, callable $translate)
    {
        $this->rcube = $rcube;
        $this->translate = \Closure::fromCallable($translate);
    }

    /**
     * Resolve a fixed user-visible backend translation, not an exception text.
     *
     * @param string $key Localisation suffix from the controller's fixed set.
     * @return string Localised message from Toolbox's language catalogue.
     */
    private function backend_label(string $key): string
    {
        return (string) ($this->translate)('cloud-backend-' . $key);
    }

    /**
     * Handle a single authenticated AJAX operation and send a plugin response.
     *
     * The controller never emits exception details to the browser. The future
     * front end listens for `plugin.cloud.response` and correlates `request_id`.
     */
    public function handle(): void
    {
        $operation = '';
        $request_id = '';
        $valid_operation = false;
        try {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'POST') {
                throw new RuntimeException('Request rejected');
            }
            if (!rcube_utils::https_check()) {
                throw new RuntimeException('Request rejected');
            }
            if (!$this->rcube->check_request(rcube_utils::INPUT_POST)) {
                throw new RuntimeException('Request rejected');
            }
            if ((int) ($this->rcube->user->ID ?? 0) < 1
                || !in_array($this->rcube->task, ['mail', 'settings'], true)) {
                throw new RuntimeException('Request rejected');
            }
            $request_id = self::field('_request_id', 64, false);
            $operation = self::field('_operation', 32);
            if (!in_array($operation, [
                'status', 'create_vault', 'list_accounts', 'add_webdav', 'update_webdav',
                'delete_account', 'list_files', 'test_download', 'attach_file',
                'set_password', 'rotate_recovery', 'unlock', 'lock', 'renew_unlock',
                'google_start', 'microsoft_start', 'microsoft_personal_start', 'dropbox_start',
            ], true)) {
                throw new InvalidArgumentException('Unknown Cloud operation');
            }

            $valid_operation = true;
            toolbox_logger::step('cloud', 'operation_' . $operation . '_started');

            // Applies to all secret-bearing operations, including actions which
            // attempt the unlock but fail for reasons unrelated to a password.
            if (in_array($operation, ['unlock', 'create_vault'], true)) {
                self::assert_not_throttled();
            }
            $session_key = $this->session_key();
            $service = new toolbox_cloud_service($this->rcube, $session_key);
            if ($session_key !== null) {
                sodium_memzero($session_key);
            }
            $result = $this->dispatch($service, $operation);
            if ($operation === 'unlock') {
                self::reset_failures();
            }
            toolbox_logger::step('cloud', 'operation_' . $operation . '_completed');
            $response = [
                'ok' => true,
                'operation' => $operation,
                'request_id' => $request_id,
                'data' => $result,
                'session_expires' => (int) ($_SESSION[self::SESSION_KEY]['expires'] ?? 0),
            ];
        } catch (Throwable $error) {
            if ($operation === 'unlock') {
                self::record_failure();
            }
            // Only fixed diagnostic labels are logged, never exception text,
            // provider responses, IDs, URLs, credentials or secrets.
            $category = $operation === 'attach_file'
                ? self::attachment_error_category($error)
                : ($error instanceof InvalidArgumentException ? 'invalid_request' : 'operation_failed');
            toolbox_logger::error('cloud', 'operation_' . ($valid_operation ? $operation : 'request'), $category);
            // No error detail: never reflect credentials, URLs, provider errors,
            // or password-validation information into the browser or PHP logs.
            $response = [
                'ok' => false,
                'operation' => $operation,
                'request_id' => $request_id,
                'error' => $this->backend_label($operation === 'unlock' ? 'unlock-failed' : 'operation-failed'),
                'locked' => $this->session_key() === null,
            ];
        }
        if (!headers_sent()) {
            header('Cache-Control: no-store, private');
            header('Pragma: no-cache');
        }
        $this->rcube->output->reset();
        $this->rcube->output->command('plugin.cloud.response', $response);
        $this->rcube->output->send('plugin');
    }

    /** Execute an approved operation with no browser-supplied user identifier. */
    private function dispatch(toolbox_cloud_service $service, string $operation): array
    {
        if ($operation === 'status') {
            return [
                'has_vault' => $service->has_vault(),
                'unlock_methods' => $service->vault_unlock_methods(),
                'unlocked' => $this->session_key() !== null,
                'expires' => (int) ($_SESSION[self::SESSION_KEY]['expires'] ?? 0),
            ];
        }
        if ($operation === 'create_vault') {
            self::assert_not_throttled();
            $password = self::field('_password', self::MAX_SECRET_BYTES, false);
            $with_recovery = self::field('_with_recovery', 1) === '1';
            if ($password !== '' && strlen($password) < 12) {
                throw new InvalidArgumentException('Vault password is too short');
            }
            if ($password === '' && !$with_recovery) {
                throw new InvalidArgumentException('Vault unlock method is required');
            }
            self::lock_session();
            $recovery = $service->create_vault($password === '' ? null : $password, $with_recovery);
            self::reset_failures();
            return ['has_vault' => true, 'recovery_key' => $recovery, 'unlock_methods' => $service->vault_unlock_methods()];
        }

        if ($operation === 'lock') {
            self::lock_session();
            return ['unlocked' => false];
        }
        if ($operation === 'renew_unlock') {
            // A renewal is an explicit authenticated action, never a timer-only
            // browser update. session_key() enforces expiry and user ownership.
            if ($this->session_key() === null) {
                throw new RuntimeException('Cloud vault locked');
            }
            $now = time();
            $_SESSION[self::SESSION_KEY]['started'] = $now;
            $_SESSION[self::SESSION_KEY]['expires'] = $now + self::IDLE_SECONDS;
            return [
                'unlocked' => true,
                'expires' => (int) $_SESSION[self::SESSION_KEY]['expires'],
            ];
        }
        if ($operation === 'unlock') {
            $method = self::field('_unlock_method', 16);
            $secret = self::field('_unlock_secret', self::MAX_SECRET_BYTES);
            $key = $service->unlock_for_session($method, $secret);
            try {
                $now = time();
                $_SESSION[self::SESSION_KEY] = [
                    'user_id' => (int) $this->rcube->user->ID,
                    'key' => base64_encode($key),
                    'started' => $now,
                    'expires' => $now + self::IDLE_SECONDS,
                ];
            } finally {
                sodium_memzero($key);
            }
            $unlocked = new toolbox_cloud_service($this->rcube, $this->session_key());
            return [
                'unlocked' => true,
                'expires' => (int) $_SESSION[self::SESSION_KEY]['expires'],
                'accounts' => $unlocked->list_accounts('session', ''),
            ];
        }

        // Only the server chooses the session method; the browser cannot
        // submit a raw key or request privileged operations while locked.
        if ($this->session_key() === null) {
            throw new RuntimeException('Cloud vault locked');
        }
        $method = 'session';
        $secret = '';

        if ($operation === 'google_start') {
            $result = $this->start_google_oauth();
            $this->touch_session();
            return $result;
        }
        if ($operation === 'microsoft_start') {
            $result = $this->start_microsoft_oauth();
            $this->touch_session();
            return $result;
        }
        if ($operation === 'microsoft_personal_start') {
            $result = $this->start_microsoft_personal_oauth();
            $this->touch_session();
            return $result;
        }
        if ($operation === 'dropbox_start') {
            $result = $this->start_dropbox_oauth();
            $this->touch_session();
            return $result;
        }

        $result = match ($operation) {
            'list_accounts' => ['accounts' => $service->list_accounts($method, $secret)],
            'set_password' => $this->set_vault_password($service, $method, $secret),
            'rotate_recovery' => ['recovery_key' => $service->rotate_vault_recovery($method, $secret)],
            'add_webdav' => ['account_id' => $service->add_webdav_account(
                $method,
                $secret,
                self::field('_display_name', 255),
                self::field('_endpoint', 2048),
                self::field('_username', 255),
                self::field('_app_password', self::MAX_SECRET_BYTES)
            )],
            'update_webdav' => ['updated' => $service->update_webdav_account(
                $method,
                $secret,
                self::field('_account_id', 32),
                self::field('_display_name', 255),
                self::field('_endpoint', 2048),
                self::field('_username', 255, false),
                self::field('_app_password', self::MAX_SECRET_BYTES, false)
            )],
            'delete_account' => ['deleted' => $service->delete_account(
                $method, $secret, self::field('_account_id', 32)
            )],
            'list_files' => ['listing' => $service->list_files(
                $method,
                $secret,
                self::field('_account_id', 32),
                self::field('_folder_id', 4096, false) ?: null,
                self::field('_cursor', 2048, false) ?: null
            )],
            'test_download' => ['download' => $service->test_download(
                $method,
                $secret,
                self::field('_account_id', 32),
                self::field('_file_id', 4096)
            )],
            'attach_file' => ['attachment' => $service->attach_file(
                $method,
                $secret,
                self::field('_account_id', 32),
                self::field('_file_id', 4096),
                self::field('_compose_id', 128)
            )],
            default => throw new InvalidArgumentException('Unknown Cloud operation'),
        };
        $this->touch_session();
        return $result;
    }

    /** Start a single pending OAuth authorization while the vault is unlocked. */
    private function start_google_oauth(): array
    {
        if ($this->rcube->task !== 'settings') {
            throw new RuntimeException('Google accounts can only be connected in Settings');
        }
        $config = toolbox_cloud_google_oauth::settings($this->rcube);
        $state = toolbox_cloud_google_oauth::base64url(random_bytes(32));
        $verifier = toolbox_cloud_google_oauth::base64url(random_bytes(32));
        $challenge = toolbox_cloud_google_oauth::base64url(hash('sha256', $verifier, true));
        $_SESSION[self::GOOGLE_OAUTH_SESSION] = [
            'state_hash' => hash('sha256', $state),
            'verifier' => $verifier,
            'user_id' => (int) $this->rcube->user->ID,
            'created' => time(),
        ];
        return ['authorization_url' => toolbox_cloud_google_oauth::authorization_url($config, $state, $challenge)];
    }

    /**
     * Handle Google's GET callback with a single-use state and PKCE verifier.
     * This is not an AJAX action: Google cannot send Roundcube's CSRF token.
     * State is verified against the user's server-side session instead.
     */
    public function handle_google_callback(): void
    {
        $success = false;
        $stage = 'context';
        toolbox_logger::step('cloud', 'google_callback_started');
        try {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'GET'
                || !rcube_utils::https_check()
                || $this->rcube->task !== 'settings'
                || (int) ($this->rcube->user->ID ?? 0) < 1) {
                throw new RuntimeException('Invalid OAuth callback context');
            }
            $pending = $_SESSION[self::GOOGLE_OAUTH_SESSION] ?? null;
            // One-time use even when authorization fails.
            unset($_SESSION[self::GOOGLE_OAUTH_SESSION]);
            $stage = 'state';
            $state = $_GET['state'] ?? null;
            $code = $_GET['code'] ?? null;
            if (!is_array($pending) || !is_string($state) || strlen($state) > 128
                || !is_string($code) || strlen($code) > 4096
                || ($pending['user_id'] ?? null) !== (int) $this->rcube->user->ID
                || time() - (int) ($pending['created'] ?? 0) > 600
                || time() < (int) ($pending['created'] ?? 0)
                || !hash_equals((string) ($pending['state_hash'] ?? ''), hash('sha256', $state))) {
                throw new RuntimeException('Invalid or expired OAuth state');
            }
            $stage = 'unlock';
            $key = $this->session_key();
            if ($key === null) {
                throw new RuntimeException('Unlock expired during Google authorization');
            }
            $stage = 'provider';
            $config = toolbox_cloud_google_oauth::settings($this->rcube);
            try {
                $tokens = toolbox_cloud_google_oauth::exchange($config, $code, (string) $pending['verifier']);
                $name = toolbox_cloud_google_oauth::account_name($tokens['access_token']);
                $stage = 'storage';
                $service = new toolbox_cloud_service($this->rcube, $key);
                $service->add_google_account($tokens, $name);
                $this->touch_session();
                $success = true;
                toolbox_logger::step('cloud', 'google_callback_completed');
            } finally {
                sodium_memzero($key);
            }
        } catch (Throwable $error) {
            toolbox_logger::error('cloud', 'google_callback', $stage . '_failed');
        }
        $this->render_google_callback($success);
    }

    /** Return a minimal popup completion page, messaging only the configured origin. */
    private function render_google_callback(bool $success): void
    {
        $origin = '';
        try {
            $redirect = toolbox_cloud_google_oauth::settings($this->rcube)['redirect_uri'];
            $parts = parse_url($redirect);
            $origin = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        } catch (Throwable $error) {
            // No working OAuth configuration; do not send any cross-window message.
            toolbox_logger::step('cloud', 'google_callback_origin_unavailable');
        }
        $nonce = toolbox_cloud_google_oauth::base64url(random_bytes(16));
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'none'; script-src 'nonce-" . $nonce . "'; base-uri 'none'; form-action 'none'");
        $message = $this->backend_label($success ? 'google-success' : 'google-failed');
        $event = json_encode(['type' => 'toolbox-cloud-google-oauth', 'ok' => $success], JSON_THROW_ON_ERROR);
        $target = json_encode($origin, JSON_THROW_ON_ERROR);
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Toolbox Cloud</title></head><body>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        if ($origin !== '') {
            // BroadcastChannel delivers completion even when an OAuth provider's
            // cross-origin opener policy severs window.opener. No credentials,
            // authorization codes or other secrets are included in the event.
            echo '<script nonce="' . $nonce . '">(function(){'
                . 'var result=' . $event . ';'
                . 'if(typeof BroadcastChannel==="function"){'
                . 'var channel=new BroadcastChannel("toolbox-cloud-google-oauth");'
                . 'channel.postMessage(result);channel.close();}'
                . 'if(window.opener){window.opener.postMessage(result,' . $target . ');}'
                . 'window.setTimeout(function(){window.close();},150);'
                . '})();</script>';
        }
        echo '</body></html>';
        exit;
    }

    /** Start OneDrive Business OAuth with state and PKCE while the vault is unlocked. */
    private function start_microsoft_oauth(): array
    {
        if ($this->rcube->task !== 'settings') {
            throw new RuntimeException('Microsoft accounts can only be connected in Settings');
        }
        $config = toolbox_cloud_microsoft_oauth::settings($this->rcube);
        $state = toolbox_cloud_microsoft_oauth::base64url(random_bytes(32));
        $verifier = toolbox_cloud_microsoft_oauth::base64url(random_bytes(32));
        $challenge = toolbox_cloud_microsoft_oauth::base64url(hash('sha256', $verifier, true));
        $_SESSION[self::MICROSOFT_OAUTH_SESSION] = [
            'state_hash' => hash('sha256', $state),
            'verifier' => $verifier,
            'profile' => $config['profile'],
            'client_id' => $config['client_id'],
            'user_id' => (int) $this->rcube->user->ID,
            'created' => time(),
        ];
        return ['authorization_url' => toolbox_cloud_microsoft_oauth::authorization_url($config, $state, $challenge)];
    }

    /**
     * Handle OneDrive Business OAuth callback, validate single-use state/PKCE,
     * and encrypt the delegated tokens under the current user's vault.
     */
    public function handle_microsoft_callback(): void
    {
        $success = false;
        $stage = 'context';
        toolbox_logger::step('cloud', 'microsoft_callback_started');
        $callbackProfile = null;
        try {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'GET'
                || !rcube_utils::https_check()
                || $this->rcube->task !== 'settings'
                || (int) ($this->rcube->user->ID ?? 0) < 1) {
                throw new RuntimeException('Invalid Microsoft OAuth callback context');
            }
            $pending = $_SESSION[self::MICROSOFT_OAUTH_SESSION] ?? null;
            $callbackProfile = is_array($pending) && is_string($pending['profile'] ?? null)
                ? $pending['profile'] : null;
            unset($_SESSION[self::MICROSOFT_OAUTH_SESSION]);
            $stage = 'state';
            $state = $_GET['state'] ?? null;
            $code = $_GET['code'] ?? null;
            if (!is_array($pending) || !is_string($state) || strlen($state) > 128
                || !is_string($code) || strlen($code) > 4096
                || ($pending['user_id'] ?? null) !== (int) $this->rcube->user->ID
                || time() - (int) ($pending['created'] ?? 0) > 600
                || time() < (int) ($pending['created'] ?? 0)
                || !hash_equals((string) ($pending['state_hash'] ?? ''), hash('sha256', $state))) {
                throw new RuntimeException('Invalid or expired Microsoft OAuth state');
            }
            $stage = 'unlock';
            $key = $this->session_key();
            if ($key === null) {
                throw new RuntimeException('Vault unlock expired during Microsoft authorization');
            }
            try {
                $stage = 'provider';
                $profile = $pending['profile'] ?? null;
                if (!is_string($profile) || !in_array($profile, ['business', 'legacy'], true)) {
                    throw new RuntimeException('Invalid Microsoft OAuth configuration profile');
                }
                $config = toolbox_cloud_microsoft_oauth::settings($this->rcube, $profile);
                if (!hash_equals((string) ($pending['client_id'] ?? ''), $config['client_id'])) {
                    throw new RuntimeException('Microsoft OAuth client configuration changed');
                }
                $tokens = toolbox_cloud_microsoft_oauth::exchange($config, $code, (string) $pending['verifier']);
                $label = toolbox_cloud_microsoft_oauth::account_name($tokens['access_token']);
                $stage = 'storage';
                (new toolbox_cloud_service($this->rcube, $key))->add_microsoft_account($tokens, $label, $config);
                $this->touch_session();
                $success = true;
                toolbox_logger::step('cloud', 'microsoft_callback_completed');
            } finally {
                sodium_memzero($key);
            }
        } catch (Throwable $error) {
            toolbox_logger::error('cloud', 'microsoft_callback', $stage . '_failed');
        }
        $this->render_microsoft_callback($success, $callbackProfile);
    }

    /** Render a CSP-restricted popup completion page with a metadata-only signal. */
    private function render_microsoft_callback(bool $success, ?string $profile): void
    {
        $origin = '';
        try {
            $redirect = toolbox_cloud_microsoft_oauth::settings($this->rcube, $profile)['redirect_uri'];
            $parts = parse_url($redirect);
            $origin = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        } catch (Throwable $error) {
            // No valid configuration; do not post a completion signal.
            toolbox_logger::step('cloud', 'microsoft_callback_origin_unavailable');
        }
        $nonce = toolbox_cloud_microsoft_oauth::base64url(random_bytes(16));
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'none'; script-src 'nonce-" . $nonce . "'; base-uri 'none'; form-action 'none'");
        $message = $this->backend_label($success ? 'business-success' : 'business-failed');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Toolbox Cloud</title></head><body>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        if ($origin !== '') {
            $event = json_encode(['type' => 'toolbox-cloud-microsoft-oauth', 'ok' => $success], JSON_THROW_ON_ERROR);
            $target = json_encode($origin, JSON_THROW_ON_ERROR);
            echo '<script nonce="' . $nonce . '">(function(){'
                . 'var result=' . $event . ';'
                . 'if(typeof BroadcastChannel==="function"){'
                . 'var channel=new BroadcastChannel("toolbox-cloud-microsoft-oauth");'
                . 'channel.postMessage(result);channel.close();}'
                . 'if(window.opener){window.opener.postMessage(result,' . $target . ');}'
                . 'window.setTimeout(function(){window.close();},150);'
                . '})();</script>';
        }
        echo '</body></html>';
        exit;
    }

    /**
     * Start a personal Microsoft consent flow, independent of Business sessions.
     * The state tag selects our root callback but grants no authorization.
     */
    private function start_microsoft_personal_oauth(): array
    {
        if ($this->rcube->task !== 'settings') {
            throw new RuntimeException('Microsoft accounts can only be connected in Settings');
        }
        $config = toolbox_cloud_microsoft_oauth::settings($this->rcube, 'personal');
        $state = 'tbxmp.' . toolbox_cloud_microsoft_oauth::base64url(random_bytes(32));
        $verifier = toolbox_cloud_microsoft_oauth::base64url(random_bytes(32));
        $challenge = toolbox_cloud_microsoft_oauth::base64url(hash('sha256', $verifier, true));
        $_SESSION[self::MICROSOFT_PERSONAL_SESSION] = [
            'state_hash' => hash('sha256', $state),
            'verifier' => $verifier,
            'client_id' => $config['client_id'],
            'user_id' => (int) $this->rcube->user->ID,
            'created' => time(),
        ];
        return ['authorization_url' => toolbox_cloud_microsoft_oauth::authorization_url($config, $state, $challenge)];
    }

    /**
     * Complete personal consent at a query-free registered Roundcube root.
     * All tokens are sealed under the current authenticated user's vault.
     */
    public function handle_microsoft_personal_callback(): void
    {
        $success = false;
        $stage = 'context';
        toolbox_logger::step('cloud', 'microsoft_personal_callback_started');
        try {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'GET'
                || !rcube_utils::https_check()
                || (int) ($this->rcube->user->ID ?? 0) < 1) {
                throw new RuntimeException('Invalid Microsoft Personal OAuth callback context');
            }
            $pending = $_SESSION[self::MICROSOFT_PERSONAL_SESSION] ?? null;
            unset($_SESSION[self::MICROSOFT_PERSONAL_SESSION]);
            $stage = 'state';
            $state = $_GET['state'] ?? null;
            $code = $_GET['code'] ?? null;
            if (!is_array($pending) || !is_string($state)
                || !preg_match('/^tbxmp\.[A-Za-z0-9_-]{43}$/D', $state)
                || !is_string($code) || strlen($code) > 4096
                || ($pending['user_id'] ?? null) !== (int) $this->rcube->user->ID
                || time() - (int) ($pending['created'] ?? 0) > 600
                || time() < (int) ($pending['created'] ?? 0)
                || !hash_equals((string) ($pending['state_hash'] ?? ''), hash('sha256', $state))) {
                throw new RuntimeException('Invalid or expired personal Microsoft OAuth state');
            }
            $stage = 'unlock';
            $key = $this->session_key();
            if ($key === null) {
                throw new RuntimeException('Cloud vault locked during Microsoft authorization');
            }
            try {
                $stage = 'provider';
                $config = toolbox_cloud_microsoft_oauth::settings($this->rcube, 'personal');
                if (!hash_equals((string) ($pending['client_id'] ?? ''), $config['client_id'])) {
                    throw new RuntimeException('Personal Microsoft OAuth application changed');
                }
                $tokens = toolbox_cloud_microsoft_oauth::exchange($config, $code, (string) $pending['verifier']);
                $label = toolbox_cloud_microsoft_oauth::account_name($tokens['access_token']);
                $stage = 'storage';
                (new toolbox_cloud_service($this->rcube, $key))->add_microsoft_account($tokens, $label, $config);
                $this->touch_session();
                $success = true;
                toolbox_logger::step('cloud', 'microsoft_personal_callback_completed');
            } finally {
                sodium_memzero($key);
            }
        } catch (Throwable $error) {
            toolbox_logger::error('cloud', 'microsoft_personal_callback', $stage . '_failed');
        }
        $this->render_microsoft_personal_callback($success);
    }

    /** Render a CSP-limited popup result; disclose only success or failure. */
    private function render_microsoft_personal_callback(bool $success): void
    {
        $origin = '';
        try {
            $redirect = toolbox_cloud_microsoft_oauth::settings($this->rcube, 'personal')['redirect_uri'];
            $parts = parse_url($redirect);
            $origin = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        } catch (Throwable $error) {
            // Never broadcast to an unconfigured origin.
            toolbox_logger::step('cloud', 'microsoft_personal_callback_origin_unavailable');
        }
        $nonce = toolbox_cloud_microsoft_oauth::base64url(random_bytes(16));
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'none'; script-src 'nonce-" . $nonce . "'; base-uri 'none'; form-action 'none'");
        $message = $this->backend_label($success ? 'personal-success' : 'personal-failed');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Toolbox Cloud</title></head><body>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        if ($origin !== '') {
            $event = json_encode(['type' => 'toolbox-cloud-microsoft-personal-oauth', 'ok' => $success], JSON_THROW_ON_ERROR);
            $target = json_encode($origin, JSON_THROW_ON_ERROR);
            echo '<script nonce="' . $nonce . '">(function(){'
                . 'var result=' . $event . ';'
                . 'if(typeof BroadcastChannel==="function"){'
                . 'var channel=new BroadcastChannel("toolbox-cloud-microsoft-personal-oauth");'
                . 'channel.postMessage(result);channel.close();}'
                . 'if(window.opener){window.opener.postMessage(result,' . $target . ');}'
                . 'window.setTimeout(function(){window.close();},150);'
                . '})();</script>';
        }
        echo '</body></html>';
        exit;
    }

    /** Start Dropbox authorization with a per-user, single-use state and PKCE. */
    private function start_dropbox_oauth(): array
    {
        if ($this->rcube->task !== 'settings') {
            throw new RuntimeException('Dropbox accounts can only be connected in Settings');
        }
        $config = toolbox_cloud_dropbox_oauth::settings($this->rcube);
        $state = toolbox_cloud_dropbox_oauth::base64url(random_bytes(32));
        $verifier = toolbox_cloud_dropbox_oauth::base64url(random_bytes(32));
        $challenge = toolbox_cloud_dropbox_oauth::base64url(hash('sha256', $verifier, true));
        $_SESSION[self::DROPBOX_OAUTH_SESSION] = [
            'state_hash' => hash('sha256', $state),
            'verifier' => $verifier,
            'user_id' => (int) $this->rcube->user->ID,
            'created' => time(),
        ];
        return ['authorization_url' => toolbox_cloud_dropbox_oauth::authorization_url($config, $state, $challenge)];
    }

    /** Validate Dropbox's one-time OAuth callback and seal tokens in the user's vault. */
    public function handle_dropbox_callback(): void
    {
        $success = false;
        $stage = 'context';
        toolbox_logger::step('cloud', 'dropbox_callback_started');
        try {
            if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'GET'
                || !rcube_utils::https_check()
                || $this->rcube->task !== 'settings'
                || (int) ($this->rcube->user->ID ?? 0) < 1) {
                throw new RuntimeException('Invalid Dropbox OAuth callback context');
            }
            $pending = $_SESSION[self::DROPBOX_OAUTH_SESSION] ?? null;
            unset($_SESSION[self::DROPBOX_OAUTH_SESSION]);
            $stage = 'state';
            $state = $_GET['state'] ?? null;
            $code = $_GET['code'] ?? null;
            if (!is_array($pending) || !is_string($state) || strlen($state) > 128
                || !is_string($code) || strlen($code) > 4096
                || ($pending['user_id'] ?? null) !== (int) $this->rcube->user->ID
                || time() - (int) ($pending['created'] ?? 0) > 600
                || time() < (int) ($pending['created'] ?? 0)
                || !hash_equals((string) ($pending['state_hash'] ?? ''), hash('sha256', $state))) {
                throw new RuntimeException('Invalid or expired Dropbox OAuth state');
            }
            $stage = 'unlock';
            $key = $this->session_key();
            if ($key === null) {
                throw new RuntimeException('Vault unlock expired during Dropbox authorization');
            }
            try {
                $stage = 'provider';
                $config = toolbox_cloud_dropbox_oauth::settings($this->rcube);
                $tokens = toolbox_cloud_dropbox_oauth::exchange($config, $code, (string) $pending['verifier']);
                $label = toolbox_cloud_dropbox_oauth::account_name($tokens['access_token']);
                $stage = 'storage';
                (new toolbox_cloud_service($this->rcube, $key))->add_dropbox_account($tokens, $label);
                $this->touch_session();
                $success = true;
                toolbox_logger::step('cloud', 'dropbox_callback_completed');
            } finally {
                sodium_memzero($key);
            }
        } catch (Throwable $error) {
            toolbox_logger::error('cloud', 'dropbox_callback', $stage . '_failed');
        }
        $this->render_dropbox_callback($success);
    }

    /** Render a CSP-protected popup completion page without sending credentials. */
    private function render_dropbox_callback(bool $success): void
    {
        $origin = '';
        try {
            $redirect = toolbox_cloud_dropbox_oauth::settings($this->rcube)['redirect_uri'];
            $parts = parse_url($redirect);
            $origin = 'https://' . $parts['host'] . (isset($parts['port']) ? ':' . $parts['port'] : '');
        } catch (Throwable $error) {
            // Do not send a message to an unconfigured origin.
            toolbox_logger::step('cloud', 'dropbox_callback_origin_unavailable');
        }
        $nonce = toolbox_cloud_dropbox_oauth::base64url(random_bytes(16));
        header('Content-Type: text/html; charset=UTF-8');
        header('Cache-Control: no-store, private');
        header('Referrer-Policy: no-referrer');
        header("Content-Security-Policy: default-src 'none'; script-src 'nonce-" . $nonce . "'; base-uri 'none'; form-action 'none'");
        $message = $this->backend_label($success ? 'dropbox-success' : 'dropbox-failed');
        echo '<!doctype html><html><head><meta charset="utf-8"><title>Toolbox Cloud</title></head><body>'
            . '<p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>';
        if ($origin !== '') {
            $event = json_encode(['type' => 'toolbox-cloud-dropbox-oauth', 'ok' => $success], JSON_THROW_ON_ERROR);
            $target = json_encode($origin, JSON_THROW_ON_ERROR);
            echo '<script nonce="' . $nonce . '">(function(){'
                . 'var result=' . $event . ';'
                . 'if(typeof BroadcastChannel==="function"){'
                . 'var channel=new BroadcastChannel("toolbox-cloud-dropbox-oauth");'
                . 'channel.postMessage(result);channel.close();}'
                . 'if(window.opener){window.opener.postMessage(result,' . $target . ');}'
                . 'window.setTimeout(function(){window.close();},150);'
                . '})();</script>';
        }
        echo '</body></html>';
        exit;
    }

    /**
     * Retrieve the active vault key only for the authenticated user and within
     * its absolute and idle expiration limits. Expired keys are erased.
     *
     * @return ?string Raw key for the current server-side request, or null.
     */
    private function session_key(): ?string
    {
        $item = $_SESSION[self::SESSION_KEY] ?? null;
        if (!is_array($item)) {
            return null;
        }
        $now = time();
        $key = base64_decode((string) ($item['key'] ?? ''), true);
        if ((int) ($item['user_id'] ?? 0) !== (int) $this->rcube->user->ID
            || (int) ($item['expires'] ?? 0) <= $now
            || (int) ($item['started'] ?? 0) + self::MAX_SESSION_SECONDS <= $now
            || $key === false || strlen($key) !== 32) {
            self::lock_session();
            return null;
        }
        return $key;
    }

    /** Refresh idle expiration, without exceeding the absolute session limit. */
    private function touch_session(): void
    {
        $item = $_SESSION[self::SESSION_KEY] ?? null;
        if (is_array($item)) {
            $_SESSION[self::SESSION_KEY]['expires'] = min(
                time() + self::IDLE_SECONDS,
                (int) $item['started'] + self::MAX_SESSION_SECONDS
            );
        }
    }

    /** Remove the unlocked vault key from the PHP session. */
    private static function lock_session(): void
    {
        unset($_SESSION[self::SESSION_KEY]);
    }

    /** Set an unlocked vault's new password, returning no sensitive data. */
    private function set_vault_password(toolbox_cloud_service $service, string $method, string $secret): array
    {
        $service->change_vault_password($method, $secret, self::field('_new_password', self::MAX_SECRET_BYTES));
        return ['updated' => true];
    }

    /**
     * Read a bounded scalar exclusively from POST, rejecting arrays and objects.
     *
     * @param string $key POST field name.
     * @param int $maximum Maximum number of bytes.
     * @param bool $required Reject missing and empty values if true.
     */
    private static function field(string $key, int $maximum, bool $required = true): string
    {
        $value = $_POST[$key] ?? null;
        if ($value === null && !$required) {
            return '';
        }
        if (!is_string($value) || strlen($value) > $maximum || ($required && $value === '')) {
            throw new InvalidArgumentException('Invalid Cloud request field');
        }
        return $value;
    }

    /** Deny further guesses after too many unsuccessful requests this session. */
    private static function assert_not_throttled(): void
    {
        $item = $_SESSION['toolbox_cloud_throttle'] ?? null;
        if (!is_array($item)) {
            return;
        }
        if ((int) ($item['since'] ?? 0) + self::WINDOW_SECONDS <= time()) {
            unset($_SESSION['toolbox_cloud_throttle']);
            return;
        }
        if ((int) ($item['failures'] ?? 0) >= self::MAX_FAILURES) {
            throw new RuntimeException('Cloud operation temporarily unavailable');
        }
    }

    /**
     * Map known attachment failures to fixed diagnostic categories.
     *
     * Used only during temporary troubleshooting. Do not log arbitrary
     * exception strings: Graph errors may contain sensitive request details.
     *
     * @param Throwable $error Original exception, never written to the log.
     * @return string Safe category without account IDs, filenames or URLs.
     */
    private static function attachment_error_category(Throwable $error): string
    {
        $message = $error->getMessage();
        $categories = [
            'OneDrive item is not an ordinary downloadable file' => 'onedrive_not_downloadable',
            'Invalid OneDrive filename' => 'onedrive_filename_invalid',
            'OneDrive file exceeds attachment limit' => 'onedrive_file_too_large',
            'OneDrive download URL host rejected' => 'onedrive_download_host_rejected',
            'OneDrive download DNS lookup failed' => 'onedrive_download_dns_failed',
            'OneDrive download destination blocked' => 'onedrive_download_address_blocked',
            'OneDrive download transport unavailable' => 'onedrive_download_transport_unavailable',
            'Invalid OneDrive download request' => 'onedrive_download_request_invalid',
            'OneDrive access token unavailable' => 'onedrive_access_token_unavailable',
            'OneDrive Graph transport unavailable' => 'onedrive_graph_transport_unavailable',
            'OneDrive Graph path rejected' => 'onedrive_graph_path_rejected',
            'Invalid OneDrive Graph JSON' => 'onedrive_graph_json_invalid',
            'Cloud attachment size verification failed' => 'attachment_size_mismatch',
            'Cloud returned an unsafe filename' => 'attachment_filename_unsafe',
            'Cloud returned an invalid filename' => 'attachment_filename_invalid',
            'Roundcube could not register Cloud attachment' => 'roundcube_attachment_save_failed',
            'Invalid or expired Roundcube compose session' => 'roundcube_compose_session_invalid',
            'Roundcube temp directory is not writable' => 'roundcube_temp_dir_unwritable',
            'Unable to create safe Cloud temporary file' => 'roundcube_temp_file_create_failed',
            'Unable to open Cloud temporary file' => 'roundcube_temp_file_open_failed',
            'Microsoft OAuth application no longer matches the connected account' => 'onedrive_oauth_application_mismatch',
            'Invalid Microsoft account configuration binding' => 'onedrive_oauth_binding_invalid',
            'Cloud vault locked' => 'vault_locked',
        ];
        if (isset($categories[$message])) {
            return $categories[$message];
        }
        if (preg_match('/^OneDrive Graph request failed \(HTTP ([0-9]{3})\)$/D', $message, $match)) {
            return 'onedrive_graph_http_' . $match[1];
        }
        if (preg_match('/^OneDrive Graph content redirect unavailable \(HTTP ([0-9]{3})\)$/D', $message, $match)) {
            return 'onedrive_content_redirect_http_' . $match[1];
        }
        if (preg_match('/^OneDrive download failed \(HTTP ([0-9]{3})\)$/D', $message, $match)) {
            return 'onedrive_download_http_' . $match[1];
        }
        return 'other_attachment_failure';
    }

    /** Record a failed secret-bearing operation without retaining any secrets. */
    private static function record_failure(): void
    {
        $now = time();
        $item = $_SESSION['toolbox_cloud_throttle'] ?? null;
        if (!is_array($item) || (int) ($item['since'] ?? 0) + self::WINDOW_SECONDS <= $now) {
            $item = ['since' => $now, 'failures' => 0];
        }
        $item['failures'] = min(self::MAX_FAILURES, (int) $item['failures'] + 1);
        $_SESSION['toolbox_cloud_throttle'] = $item;
    }

    /** Clear the session failure counter after an authenticated success. */
    private static function reset_failures(): void
    {
        unset($_SESSION['toolbox_cloud_throttle']);
    }
}

// END OF FILE
