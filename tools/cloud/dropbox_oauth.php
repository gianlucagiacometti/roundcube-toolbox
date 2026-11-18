<?php

/**
 * Dropbox OAuth 2 authorization-code and account identification for Toolbox Cloud.
 *
 * Uses a confidential server-side Dropbox app, PKCE S256 and offline access.
 * Only the fixed Dropbox authentication and account-profile API endpoints may
 * receive credentials. Encrypted storage belongs to toolbox_cloud_accounts.
 */
final class toolbox_cloud_dropbox_oauth
{
    public const SCOPE = 'account_info.read files.metadata.read files.content.read';
    private const TOKEN_URL = 'https://api.dropboxapi.com/oauth2/token';
    private const PROFILE_URL = 'https://api.dropboxapi.com/2/users/get_current_account';

    /** Validate the Dropbox app credentials and exact registered HTTPS callback. */
    public static function settings(rcube $rcube): array
    {
        $id = trim((string) $rcube->config->get('toolbox_cloud_dropbox_app_key', ''));
        $secret = (string) $rcube->config->get('toolbox_cloud_dropbox_app_secret', '');
        $redirect = trim((string) $rcube->config->get('toolbox_cloud_dropbox_redirect_uri', ''));
        $parts = parse_url($redirect);
        if ($id === '' || strlen($id) > 256 || !preg_match('/^[A-Za-z0-9_-]+$/D', $id)
            || $secret === '' || strlen($secret) > 4096 || preg_match('/[\r\n]/', $secret)
            || !is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['fragment']) || strlen($redirect) > 2048
            || ($parts['path'] ?? '/') !== '/'
            || !self::query_value($parts['query'] ?? '', '_task', 'settings')
            || !self::query_value($parts['query'] ?? '', '_action', 'plugin.toolbox.cloud.dropbox.callback')) {
            throw new RuntimeException('Dropbox OAuth is not configured');
        }
        return ['client_id' => $id, 'client_secret' => $secret, 'redirect_uri' => $redirect];
    }

    /** Check the required Roundcube callback fields without accepting array values. */
    private static function query_value(string $query, string $key, string $expected): bool
    {
        parse_str($query, $values);
        return isset($values[$key]) && is_string($values[$key]) && $values[$key] === $expected;
    }

    /** Encode random bytes in the URL-safe, unpadded base64 alphabet used by PKCE. */
    public static function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /** Generate the Dropbox consent address requesting only read scopes and offline access. */
    public static function authorization_url(array $config, string $state, string $challenge): string
    {
        if (!preg_match('/^[A-Za-z0-9_-]{30,128}$/D', $state)
            || !preg_match('/^[A-Za-z0-9_-]{43,128}$/D', $challenge)) {
            throw new InvalidArgumentException('Invalid Dropbox OAuth challenge');
        }
        return 'https://www.dropbox.com/oauth2/authorize?' . http_build_query([
            'client_id' => $config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $config['redirect_uri'],
            'token_access_type' => 'offline',
            'scope' => self::SCOPE,
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Exchange a single-use authorization code for access and refresh credentials. */
    public static function exchange(array $config, string $code, string $verifier): array
    {
        if ($code === '' || strlen($code) > 4096
            || !preg_match('/^[A-Za-z0-9_-]{43,128}$/D', $verifier)) {
            throw new RuntimeException('Invalid Dropbox OAuth authorization result');
        }
        $response = self::request(self::TOKEN_URL, [
            'code' => $code,
            'grant_type' => 'authorization_code',
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'code_verifier' => $verifier,
        ]);
        if (!is_string($response['access_token'] ?? null) || $response['access_token'] === ''
            || !is_string($response['refresh_token'] ?? null) || $response['refresh_token'] === '') {
            throw new RuntimeException('Dropbox did not provide an offline refresh token');
        }
        $expires_in = filter_var($response['expires_in'] ?? null, FILTER_VALIDATE_INT);
        if ($expires_in === false || $expires_in < 1 || $expires_in > 86400) {
            throw new RuntimeException('Invalid Dropbox access-token lifetime');
        }
        if (isset($response['scope'])) {
            if (!is_string($response['scope'])) {
                throw new RuntimeException('Invalid Dropbox granted scopes');
            }
            $scopes = preg_split('/\s+/', trim($response['scope'])) ?: [];
            foreach (explode(' ', self::SCOPE) as $required) {
                if (!in_array($required, $scopes, true)) {
                    throw new RuntimeException('Dropbox did not grant required read permissions');
                }
            }
        }
        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'scope' => self::SCOPE,
            'token_type' => 'Bearer',
            'expires' => time() + $expires_in,
        ];
    }

    /**
     * Refresh an expired short-lived Dropbox access token using offline access.
     * The caller must re-encrypt the updated credentials in the user's vault.
     *
     * @param array $config Validated Dropbox application configuration.
     * @param string $refresh_token Existing user-authorized refresh token.
     * @return array{access_token: string, refresh_token: ?string, expires: int}
     */
    public static function refresh(array $config, string $refresh_token): array
    {
        if ($refresh_token === '' || strlen($refresh_token) > 8192
            || preg_match('/[\r\n]/', $refresh_token)) {
            throw new InvalidArgumentException('Invalid Dropbox refresh token');
        }
        $response = self::request(self::TOKEN_URL, [
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
        ]);
        $access = $response['access_token'] ?? null;
        $lifetime = filter_var($response['expires_in'] ?? null, FILTER_VALIDATE_INT);
        if (!is_string($access) || $access === '' || $lifetime === false
            || $lifetime < 1 || $lifetime > 86400) {
            throw new RuntimeException('Dropbox access token refresh failed');
        }
        $rotated = $response['refresh_token'] ?? null;
        return [
            'access_token' => $access,
            'refresh_token' => is_string($rotated) && $rotated !== '' ? $rotated : null,
            'expires' => time() + $lifetime,
        ];
    }

    /** Fetch a display-only account name from the authorized Dropbox user profile. */
    public static function account_name(string $access_token): string
    {
        if ($access_token === '' || preg_match('/[\r\n]/', $access_token)) {
            throw new RuntimeException('Invalid Dropbox access token');
        }
        $profile = self::request(self::PROFILE_URL, [], $access_token);
        $name = $profile['email'] ?? ($profile['name']['display_name'] ?? '');
        if (!is_string($name) || trim($name) === '' || strlen($name) > 220) {
            return 'Dropbox';
        }
        return 'Dropbox - ' . trim($name);
    }

    /** Send a bounded, nonredirecting HTTPS POST to a fixed Dropbox API endpoint. */
    private static function request(string $url, array $form, ?string $token = null): array
    {
        if (!in_array($url, [self::TOKEN_URL, self::PROFILE_URL], true)
            || !function_exists('curl_init')) {
            throw new RuntimeException('Dropbox OAuth transport unavailable');
        }
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Dropbox OAuth transport unavailable');
        }
        $body = '';
        $max = 65536;
        $headers = ['Accept: application/json'];
        if ($token === null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        } else {
            $headers[] = 'Authorization: Bearer ' . $token;
            $headers[] = 'Content-Type: application/json';
        }
        $options = [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $token === null ? http_build_query($form, '', '&', PHP_QUERY_RFC3986) : 'null',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body, $max): int {
                if (strlen($body) + strlen($chunk) > $max) {
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ];
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[CURLOPT_PROTOCOLS_STR] = 'https';
        } else {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        try {
            curl_setopt_array($curl, $options);
            $ok = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if ($ok === false || $status !== 200) {
                throw new RuntimeException('Dropbox OAuth API request failed');
            }
        } finally {
            curl_close($curl);
        }
        $data = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid Dropbox OAuth response');
        }
        return $data;
    }
}

// END OF FILE
