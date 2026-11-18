<?php

/**
 * Google Drive OAuth 2.0 authorization-code integration for Toolbox Cloud.
 *
 * The administrator configures a confidential Web OAuth client. Tokens are
 * exchanged only with fixed Google HTTPS endpoints; they are never logged.
 * The caller owns session state, PKCE verification, and per-user vault storage.
 */
final class toolbox_cloud_google_oauth
{
    public const SCOPE = 'https://www.googleapis.com/auth/drive.readonly';

    /** Return and validate the administrator's OAuth configuration. */
    public static function settings(rcube $rcube): array
    {
        $config = $rcube->config;
        $id = trim((string) $config->get('toolbox_cloud_google_client_id', ''));
        $secret = (string) $config->get('toolbox_cloud_google_client_secret', '');
        $redirect = trim((string) $config->get('toolbox_cloud_google_redirect_uri', ''));
        $url = parse_url($redirect);
        if ($id === '' || $secret === '' || !is_array($url)
            || ($url['scheme'] ?? '') !== 'https'
            || empty($url['host']) || isset($url['user']) || isset($url['pass'])
            || isset($url['fragment']) || strlen($redirect) > 2048
            || strpos($redirect, '_action=plugin.toolbox.cloud.google.callback') === false
            || strpos($redirect, '_task=settings') === false) {
            throw new RuntimeException('Google Drive OAuth is not configured');
        }
        return ['client_id' => $id, 'client_secret' => $secret, 'redirect_uri' => $redirect];
    }

    /** Build the OAuth authorization URL with state, PKCE S256 and offline access. */
    public static function authorization_url(array $config, string $state, string $challenge): string
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $config['client_id'],
            'redirect_uri' => $config['redirect_uri'],
            'response_type' => 'code',
            'scope' => self::SCOPE,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Encode raw bytes using PKCE's unpadded base64url alphabet. */
    public static function base64url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** Exchange a one-time authorization code for a refresh and access token. */
    public static function exchange(array $config, string $code, string $verifier): array
    {
        if (strlen($code) > 4096 || $code === '' || !preg_match('/^[A-Za-z0-9_\-]{43,128}$/D', $verifier)) {
            throw new RuntimeException('Invalid OAuth authorization result');
        }
        $payload = self::request('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'redirect_uri' => $config['redirect_uri'],
            'grant_type' => 'authorization_code',
            'code_verifier' => $verifier,
        ]);
        if (empty($payload['access_token']) || empty($payload['refresh_token'])
            || !is_string($payload['access_token']) || !is_string($payload['refresh_token'])) {
            throw new RuntimeException('Google did not return an offline refresh token');
        }
        $scopes = preg_split('/\s+/', trim((string) ($payload['scope'] ?? ''))) ?: [];
        if (!in_array(self::SCOPE, $scopes, true)) {
            throw new RuntimeException('Google did not grant the required read-only scope');
        }
        $lifetime = (int) ($payload['expires_in'] ?? 3600);
        if ($lifetime < 1 || $lifetime > 86400) {
            throw new RuntimeException('Invalid Google OAuth token expiry');
        }
        return [
            'access_token' => $payload['access_token'],
            'refresh_token' => $payload['refresh_token'],
            'scope' => self::SCOPE,
            'token_type' => 'Bearer',
            'expires' => time() + $lifetime,
        ];
    }

    /**
     * Refresh a short-lived access token using the encrypted offline grant.
     *
     * Returns only the new access token and expiration. Google does not
     * normally rotate the refresh token in this response; the repository
     * retains the original refresh token unless a replacement is supplied.
     */
    public static function refresh(array $config, string $refresh_token): array
    {
        if ($refresh_token === '' || strlen($refresh_token) > 8192
            || preg_match('/[\r\n]/', $refresh_token)) {
            throw new RuntimeException('Invalid Google refresh token');
        }
        $payload = self::request('https://oauth2.googleapis.com/token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
        ]);
        $access = $payload['access_token'] ?? null;
        $lifetime = (int) ($payload['expires_in'] ?? 0);
        if (!is_string($access) || $access === '' || $lifetime < 1 || $lifetime > 86400) {
            throw new RuntimeException('Google access token refresh failed');
        }
        return ['access_token' => $access, 'expires' => time() + $lifetime];
    }

    /** Resolve the current Google Drive account label without exposing tokens. */
    public static function account_name(string $access_token): string
    {
        if ($access_token === '' || preg_match('/[\r\n]/', $access_token)) {
            throw new RuntimeException('Invalid Google access token');
        }
        $response = self::request('https://www.googleapis.com/drive/v3/about?fields=user(emailAddress,displayName)', null, $access_token);
        $user = $response['user'] ?? [];
        $email = is_array($user) && is_string($user['emailAddress'] ?? null)
            ? trim($user['emailAddress']) : '';
        return $email !== '' && strlen($email) <= 220
            ? 'Google Drive - ' . $email : 'Google Drive';
    }

    /**
     * Send one bounded request to a fixed Google HTTPS API URL.
     * No redirects, insecure TLS or caller-provided remote hosts are allowed.
     */
    private static function request(string $url, ?array $form, ?string $bearer = null): array
    {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('PHP cURL is required for Google Drive');
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Google OAuth transport unavailable');
        }
        $headers = ['Accept: application/json'];
        if ($form !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        if ($bearer !== null) {
            $headers[] = 'Authorization: Bearer ' . $bearer;
        }
        $body = '';
        $limit = 65536;
        $opts = [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 25,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$body, $limit): int {
                if (strlen($body) + strlen($chunk) > $limit) {
                    return 0;
                }
                $body .= $chunk;
                return strlen($chunk);
            },
        ];
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $opts[CURLOPT_PROTOCOLS_STR] = 'https';
        } else {
            $opts[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        if ($form !== null) {
            $opts[CURLOPT_POST] = true;
            $opts[CURLOPT_POSTFIELDS] = http_build_query($form, '', '&', PHP_QUERY_RFC3986);
        }
        try {
            curl_setopt_array($handle, $opts);
            $ok = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if ($ok === false || $status !== 200) {
                throw new RuntimeException('Google OAuth/API request failed');
            }
        } finally {
            curl_close($handle);
        }
        $value = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($value)) {
            throw new RuntimeException('Invalid Google OAuth/API JSON response');
        }
        return $value;
    }
}

// END OF FILE
