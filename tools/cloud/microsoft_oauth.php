<?php

/**
 * Microsoft Entra OAuth 2.0 authorization-code client for Toolbox Cloud.
 *
 * Supports work/school tenants and a separate personal-account consumers authority.
 * The application owner supplies a confidential Web client and a redirect URL.
 * OAuth tokens are passed to the encrypted per-user account repository.
 */
final class toolbox_cloud_microsoft_oauth
{
    public const SCOPE = 'offline_access User.Read Files.Read';

    /**
     * Resolve a complete Microsoft OneDrive Business OAuth configuration.
     *
     * Prefer the new Business configuration for new connections. The previous
     * settings remain valid for already connected accounts and existing sites.
     * Never combine individual values from the two independent configurations.
     *
     * @param rcube $rcube Authenticated Roundcube application.
     * @param ?string $profile 'business', 'legacy', 'personal', or null for Business selection.
     * @return array Validated confidential OAuth client and organizational authority.
     */
    public static function settings(rcube $rcube, ?string $profile = null): array
    {
        if ($profile !== null && !in_array($profile, ['business', 'legacy', 'personal'], true)) {
            throw new InvalidArgumentException('Unknown Microsoft OAuth configuration');
        }

        $profiles = $profile === null ? ['business', 'legacy'] : [$profile];
        foreach ($profiles as $candidate) {
            $prefix = match ($candidate) {
                'business' => 'toolbox_cloud_microsoft_business_',
                'personal' => 'toolbox_cloud_microsoft_personal_',
                default => 'toolbox_cloud_microsoft_',
            };
            $config = [
                'client_id' => trim((string) $rcube->config->get($prefix . 'client_id', '')),
                'client_secret' => (string) $rcube->config->get($prefix . 'client_secret', ''),
                'tenant_id' => $candidate === 'personal' ? 'consumers' : trim((string) $rcube->config->get($prefix . 'tenant_id', '')),
                'redirect_uri' => trim((string) $rcube->config->get($prefix . 'redirect_uri', '')),
                'profile' => $candidate,
            ];
            if (self::valid_settings($config)) {
                return $config;
            }
        }
        throw new RuntimeException('Microsoft OAuth application is not configured');
    }

    /**
     * Validate each OAuth client as one indivisible set of administrator values.
     * The 'organizations' authority is supported only for work/school accounts.
     */
    private static function valid_settings(array $config): bool
    {
        $id = $config['client_id'];
        $secret = $config['client_secret'];
        $tenant = $config['tenant_id'];
        $redirect = $config['redirect_uri'];
        $parts = parse_url($redirect);
        $guid = '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD';
        $personal = ($config['profile'] ?? '') === 'personal';
        $common = preg_match($guid, $id) === 1
            && ($personal ? $tenant === 'consumers' : ($tenant === 'organizations' || preg_match($guid, $tenant) === 1))
            && $secret !== '' && strlen($secret) <= 4096
            && !preg_match('/[\r\n]/', $secret)
            && is_array($parts) && ($parts['scheme'] ?? null) === 'https'
            && !empty($parts['host']) && !isset($parts['user']) && !isset($parts['pass'])
            && !isset($parts['fragment']) && strlen($redirect) <= ($personal ? 256 : 2048);
        if (!$common) {
            return false;
        }
        if ($personal) {
            // Personal Microsoft apps cannot register query-bearing redirect URIs.
            // A Roundcube root (possibly under a subdirectory) is required.
            $path = $parts['path'] ?? '/';
            return !isset($parts['query'])
                && preg_match('#^/(?:[A-Za-z0-9_~-]+/)*$#D', $path) === 1;
        }
        return ($parts['path'] ?? '/') === '/'
            && self::has_query_value($parts['query'] ?? '', '_task', 'settings')
            && self::has_query_value($parts['query'] ?? '', '_action', 'plugin.toolbox.cloud.microsoft.callback');
    }

    /** Check for one exact callback query parameter without accepting arrays. */
    private static function has_query_value(string $query, string $name, string $expected): bool
    {
        parse_str($query, $params);
        return isset($params[$name]) && is_string($params[$name]) && $params[$name] === $expected;
    }

    /** Encode random bytes using PKCE's unpadded URL-safe alphabet. */
    public static function base64url(string $bytes): string
    {
        return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
    }

    /** Build a tenant-appropriate consent URL with state and PKCE S256. */
    public static function authorization_url(array $config, string $state, string $challenge): string
    {
        return self::base($config) . '/authorize?' . http_build_query([
            'client_id' => $config['client_id'],
            'response_type' => 'code',
            'redirect_uri' => $config['redirect_uri'],
            'response_mode' => 'query',
            'scope' => self::SCOPE,
            'state' => $state,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /** Exchange one authorization code for a short-lived access and offline refresh token. */
    public static function exchange(array $config, string $code, string $verifier): array
    {
        if ($code === '' || strlen($code) > 4096
            || !preg_match('/^[A-Za-z0-9_-]{43,128}$/D', $verifier)) {
            throw new RuntimeException('Invalid Microsoft OAuth authorization result');
        }
        $response = self::request(self::base($config) . '/token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $config['redirect_uri'],
            'code_verifier' => $verifier,
            'scope' => self::SCOPE,
        ]);
        if (!is_string($response['access_token'] ?? null)
            || $response['access_token'] === ''
            || !is_string($response['refresh_token'] ?? null)
            || $response['refresh_token'] === '') {
            throw new RuntimeException('Microsoft did not return an offline refresh token');
        }
        $lifetime = (int) ($response['expires_in'] ?? 0);
        if ($lifetime < 1 || $lifetime > 86400) {
            throw new RuntimeException('Invalid Microsoft access token lifetime');
        }
        $scopes = preg_split('/\s+/', strtolower(trim((string) ($response['scope'] ?? '')))) ?: [];
        if ($scopes && (!in_array('files.read', $scopes, true)
                || !in_array('user.read', $scopes, true))) {
            throw new RuntimeException('Microsoft did not grant the required delegated permissions');
        }
        return [
            'access_token' => $response['access_token'],
            'refresh_token' => $response['refresh_token'],
            'scope' => self::SCOPE,
            'token_type' => 'Bearer',
            'expires' => time() + $lifetime,
        ];
    }

    /**
     * Exchange a user's delegated refresh token for a new access token.
     * Microsoft can rotate refresh tokens; return a replacement when supplied.
     * The caller must persist it under the user's encrypted vault key.
     */
    public static function refresh(array $config, string $refresh_token): array
    {
        if ($refresh_token === '' || strlen($refresh_token) > 8192
            || preg_match('/[\r\n]/', $refresh_token)) {
            throw new InvalidArgumentException('Invalid Microsoft refresh token');
        }
        $response = self::request(self::base($config) . '/token', [
            'client_id' => $config['client_id'],
            'client_secret' => $config['client_secret'],
            'grant_type' => 'refresh_token',
            'refresh_token' => $refresh_token,
            'scope' => self::SCOPE,
        ]);
        $access = $response['access_token'] ?? null;
        $lifetime = (int) ($response['expires_in'] ?? 0);
        if (!is_string($access) || $access === '' || $lifetime < 1 || $lifetime > 86400) {
            throw new RuntimeException('Microsoft access token refresh failed');
        }
        $rotated = $response['refresh_token'] ?? null;
        return [
            'access_token' => $access,
            'refresh_token' => is_string($rotated) && $rotated !== '' ? $rotated : null,
            'expires' => time() + $lifetime,
        ];
    }

    /** Fetch a safe display label from the authorized user's Microsoft Graph profile. */
    public static function account_name(string $access_token): string
    {
        if ($access_token === '' || preg_match('/[\r\n]/', $access_token)) {
            throw new RuntimeException('Invalid Microsoft access token');
        }
        $profile = self::request(
            'https://graph.microsoft.com/v1.0/me?$select=displayName,mail,userPrincipalName',
            null,
            $access_token
        );
        $name = $profile['mail'] ?? $profile['userPrincipalName'] ?? $profile['displayName'] ?? '';
        if (!is_string($name) || trim($name) === '' || strlen($name) > 220) {
            return 'OneDrive';
        }
        return 'OneDrive - ' . trim($name);
    }

    /** Return the validated organizational OAuth 2.0 v2 endpoint. */
    private static function base(array $config): string
    {
        $tenant = (string) ($config['tenant_id'] ?? '');
        if ($tenant !== 'organizations' && $tenant !== 'consumers'
            && !preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/iD', $tenant)) {
            throw new RuntimeException('Invalid Microsoft tenant identifier');
        }
        return 'https://login.microsoftonline.com/' . $tenant . '/oauth2/v2.0';
    }

    /** Make a bounded TLS-verified request to fixed Microsoft endpoints without redirects. */
    private static function request(string $url, ?array $form, ?string $bearer = null): array
    {
        $allowed = preg_match('~^https://login\.microsoftonline\.com/(?:organizations|consumers|[0-9a-f]{8}(?:-[0-9a-f]{4}){3}-[0-9a-f]{12})/oauth2/v2\.0/token$~iD', $url)
            || $url === 'https://graph.microsoft.com/v1.0/me?$select=displayName,mail,userPrincipalName';
        if (!$allowed || !function_exists('curl_init')) {
            throw new RuntimeException('Microsoft OAuth transport unavailable');
        }
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Microsoft OAuth transport unavailable');
        }
        $body = '';
        $limit = 65536;
        $headers = ['Accept: application/json'];
        if ($form !== null) {
            $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        if ($bearer !== null) {
            if (preg_match('/[\r\n]/', $bearer)) {
                throw new RuntimeException('Invalid Microsoft access token');
            }
            $headers[] = 'Authorization: Bearer ' . $bearer;
        }
        $options = [
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
            $options[CURLOPT_PROTOCOLS_STR] = 'https';
        } else {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        if ($form !== null) {
            $options[CURLOPT_POST] = true;
            $options[CURLOPT_POSTFIELDS] = http_build_query($form, '', '&', PHP_QUERY_RFC3986);
        }
        try {
            curl_setopt_array($handle, $options);
            $ok = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if ($ok === false || $status !== 200) {
                throw new RuntimeException('Microsoft OAuth or Graph request failed');
            }
        } finally {
            curl_close($handle);
        }
        $data = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid Microsoft API response');
        }
        return $data;
    }
}

// END OF FILE
