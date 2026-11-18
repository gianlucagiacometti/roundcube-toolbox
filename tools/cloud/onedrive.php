<?php

/**
 * Microsoft Graph OneDrive provider for Toolbox Cloud.
 *
 * Lists the authorized user's own drive and streams ordinary files via
 * Graph-issued, short-lived download URLs. Never sends OAuth tokens to CDN
 * hosts; rejects private network destinations and insecure redirects.
 */
require_once __DIR__ . '/provider.php';

/**
 * Implement folder listings and native attachment downloads using Graph v1.0.
 */
final class toolbox_cloud_onedrive implements toolbox_cloud_provider
{
    private const GRAPH = 'https://graph.microsoft.com/v1.0/me/drive/';
    private const MAX_RESPONSE = 1048576;
    private const MAX_DOWNLOAD_URL = 16384;

    /** Return the stable OneDrive provider name. */
    public function id(): string
    {
        return 'onedrive';
    }

    /**
     * List up to 100 items in the user's OneDrive folder.
     * Pagination cursors contain only encoded Graph nextLink URLs bound to the
     * exact requested folder path, never arbitrary user-chosen destinations.
     */
    public function list_files(array $account, ?string $folder_id, ?string $cursor): array
    {
        $token = self::token($account);
        $base = self::folder_url($folder_id);
        if ($cursor !== null && $cursor !== '') {
            $raw = self::decode_cursor($cursor);
            if (!str_starts_with($raw, $base . '?') || strlen($raw) > 3500) {
                throw new InvalidArgumentException('Invalid OneDrive listing cursor');
            }
            $url = $raw;
        } else {
            $url = $base . '?' . http_build_query([
                '$top' => '100',
                '$select' => 'id,name,size,file,folder',
            ], '', '&', PHP_QUERY_RFC3986);
        }
        $result = self::graph_json($url, $token);
        if (!is_array($result['value'] ?? null)) {
            throw new RuntimeException('Invalid OneDrive listing');
        }
        $items = [];
        foreach ($result['value'] as $entry) {
            if (!is_array($entry) || !is_string($entry['id'] ?? null)
                || !self::valid_id($entry['id']) || !is_string($entry['name'] ?? null)) {
                continue;
            }
            $directory = isset($entry['folder']) && is_array($entry['folder']);
            if (!$directory && !isset($entry['file'])) {
                continue;
            }
            $size = $entry['size'] ?? null;
            $size = is_int($size) && $size >= 0 ? $size : null;
            $mime = $directory ? null : ($entry['file']['mimeType'] ?? 'application/octet-stream');
            $items[] = [
                'id' => $entry['id'],
                'name' => mb_substr($entry['name'], 0, 255),
                'is_dir' => $directory,
                'size' => $directory ? null : $size,
                'mime_type' => is_string($mime) ? $mime : 'application/octet-stream',
            ];
        }
        $next = $result['@odata.nextLink'] ?? null;
        if ($next !== null && (!is_string($next) || !str_starts_with($next, $base . '?')
            || strlen($next) > 1450)) {
            throw new RuntimeException('Invalid OneDrive pagination response');
        }
        $cursor_value = $next === null ? null : rtrim(strtr(base64_encode($next), '+/', '-_'), '=');
        return ['items' => $items, 'next_cursor' => $cursor_value];
    }

    /**
     * Download an ordinary OneDrive file into the caller's temporary stream.
     * The preauthenticated URL comes directly from Microsoft Graph, must use
     * a public HTTPS DNS hostname, and carries no Authorization header.
     */
    public function download_file(array $account, string $file_id, $destination, int $max_bytes): array
    {
        if (!self::valid_id($file_id) || !is_resource($destination)
            || $max_bytes < 1 || $max_bytes > 104857600) {
            throw new InvalidArgumentException('Invalid OneDrive download request');
        }
        $token = self::token($account);
        $url = self::GRAPH . 'items/' . rawurlencode($file_id) . '?'
            . http_build_query(['$select' => 'id,name,size,file,folder,@microsoft.graph.downloadUrl'], '', '&', PHP_QUERY_RFC3986);
        $file = self::graph_json($url, $token);
        if (($file['id'] ?? null) !== $file_id || !is_array($file['file'] ?? null) || isset($file['folder'])) {
            throw new RuntimeException('OneDrive item is not an ordinary downloadable file');
        }
        $name = $file['name'] ?? null;
        if (!is_string($name) || $name === '' || strlen($name) > 1024
            || preg_match('~[\x00-\x1f/\\\\]~', $name)) {
            throw new RuntimeException('Invalid OneDrive filename');
        }
        if (isset($file['size']) && is_int($file['size']) && $file['size'] > $max_bytes) {
            throw new RuntimeException('OneDrive file exceeds attachment limit');
        }
        $download = $file['@microsoft.graph.downloadUrl'] ?? null;
        if (!is_string($download) || $download === '') {
            // Some work/school drives omit the ephemeral URL from metadata.
            // Graph's /content endpoint supplies the same URL via a 302 Location.
            $download = self::content_redirect_url($file_id, $token);
        }
        if (!self::download_url_allowed($download)) {
            throw new RuntimeException('OneDrive download URL host rejected');
        }
        $bytes = self::stream_download($download, $destination, $max_bytes);
        $mime = $file['file']['mimeType'] ?? 'application/octet-stream';
        return [
            'name' => $name,
            'size' => $bytes,
            'mime_type' => is_string($mime) ? $mime : 'application/octet-stream',
        ];
    }

    /** Validate an opaque Microsoft Graph driveItem ID. */
    private static function valid_id(string $id): bool
    {
        return strlen($id) <= 512 && preg_match('/^[A-Za-z0-9_!.-]+$/D', $id) === 1;
    }

    /** Restrict listing to the current user's drive and chosen folder ID. */
    private static function folder_url(?string $folder_id): string
    {
        if ($folder_id === null || $folder_id === '') {
            return self::GRAPH . 'root/children';
        }
        if (!self::valid_id($folder_id)) {
            throw new InvalidArgumentException('Invalid OneDrive folder identifier');
        }
        return self::GRAPH . 'items/' . rawurlencode($folder_id) . '/children';
    }

    /** Decode bounded, URL-safe pagination tokens without accepting arbitrary URLs. */
    private static function decode_cursor(string $cursor): string
    {
        if (strlen($cursor) > 2200 || !preg_match('/^[A-Za-z0-9_-]+$/D', $cursor)) {
            throw new InvalidArgumentException('Invalid OneDrive pagination token');
        }
        $decoded = base64_decode(strtr($cursor, '-_', '+/') . str_repeat('=', (4 - strlen($cursor) % 4) % 4), true);
        if (!is_string($decoded) || $decoded === '') {
            throw new InvalidArgumentException('Invalid OneDrive pagination token');
        }
        return $decoded;
    }

    /** Extract a bounded bearer token from an authenticated, owned account. */
    private static function token(array $account): string
    {
        $token = $account['credentials']['access_token'] ?? null;
        if (!is_string($token) || strlen($token) < 12 || strlen($token) > 8192
            || preg_match('/[\x00-\x20\x7f]/', $token)) {
            throw new RuntimeException('OneDrive access token unavailable');
        }
        return $token;
    }

    /** Make an HTTPS-only Graph request and decode bounded JSON responses. */
    private static function graph_json(string $url, string $token): array
    {
        if (!str_starts_with($url, self::GRAPH) || strlen($url) > 4000
            || !function_exists('curl_init')) {
            throw new RuntimeException('OneDrive Graph transport unavailable');
        }
        $path = substr($url, strlen(self::GRAPH));
        if (!preg_match('~^(?:root/children|items/[A-Za-z0-9_%!.-]+(?:/children)?)(?:\?[^#]*)?$~D', $path)) {
            throw new RuntimeException('OneDrive Graph path rejected');
        }
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('OneDrive Graph transport unavailable');
        }
        $body = '';
        try {
            curl_setopt_array($curl, self::secure_options() + [
                CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $token],
                CURLOPT_TIMEOUT => 40,
                CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
                    if (strlen($body) + strlen($chunk) > self::MAX_RESPONSE) {
                        return 0;
                    }
                    $body .= $chunk;
                    return strlen($chunk);
                },
            ]);
            $ok = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if ($ok === false || $status !== 200) {
                throw new RuntimeException('OneDrive Graph request failed (HTTP ' . $status . ')');
            }
        } finally {
            curl_close($curl);
        }
        $data = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new RuntimeException('Invalid OneDrive Graph JSON');
        }
        return $data;
    }

    /**
     * Obtain the short-lived preauthenticated download URL via Graph /content.
     *
     * This request sends the bearer token only to graph.microsoft.com, does not
     * follow the redirect, and never writes its sensitive Location value to logs.
     * The returned URL is separately validated before the file is retrieved.
     *
     * @param string $file_id Validated opaque OneDrive item identifier.
     * @param string $token Current Microsoft Graph access token.
     * @return string HTTPS preauthenticated download URL.
     */
    private static function content_redirect_url(string $file_id, string $token): string
    {
        $url = self::GRAPH . 'items/' . rawurlencode($file_id) . '/content';
        if (!function_exists('curl_init')) {
            throw new RuntimeException('OneDrive Graph transport unavailable');
        }
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('OneDrive Graph transport unavailable');
        }
        $location = null;
        try {
            curl_setopt_array($curl, self::secure_options() + [
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $token],
                CURLOPT_CONNECTTIMEOUT => 12,
                CURLOPT_TIMEOUT => 40,
                CURLOPT_HEADERFUNCTION => static function ($handle, string $header) use (&$location): int {
                    if (stripos($header, 'Location:') === 0) {
                        $value = trim(substr($header, strlen('Location:')));
                        if ($value !== '' && strlen($value) <= self::MAX_DOWNLOAD_URL) {
                            $location = $value;
                        }
                    }
                    return strlen($header);
                },
                CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk): int {
                    // No response body is needed; reject unexpected file content.
                    return 0;
                },
            ]);
            $ok = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if ($ok === false || $status !== 302) {
                throw new RuntimeException('OneDrive Graph content redirect unavailable (HTTP ' . $status . ')');
            }
        } finally {
            curl_close($curl);
        }
        if (!is_string($location) || !self::download_url_allowed($location)) {
            throw new RuntimeException('OneDrive download URL host rejected');
        }
        return $location;
    }

    /**
     * Validate the shape of a temporary download URL obtained from Graph.
     *
     * Microsoft can vary the public CDN host used for OneDrive downloads.
     * The URL must originate from the authenticated Graph metadata response
     * or its /content Location header, never from a browser/user parameter.
     * Only HTTPS DNS hostnames are accepted. The subsequent transfer checks
     * every resolved address, pins a public IP and verifies the TLS hostname.
     *
     * @param string $url Graph-issued preauthenticated file download URL.
     * @return bool Whether its scheme, authority and host are acceptable.
     */
    private static function download_url_allowed(string $url): bool
    {
        if ($url === '' || strlen($url) > self::MAX_DOWNLOAD_URL
            || preg_match('/[\x00-\x20\x7f]/', $url)) {
            return false;
        }

        $parts = parse_url($url);
        if (!is_array($parts) || ($parts['scheme'] ?? '') !== 'https'
            || !is_string($parts['host'] ?? null)
            || isset($parts['port']) || isset($parts['user'])
            || isset($parts['pass']) || isset($parts['fragment'])) {
            return false;
        }

        $host = strtolower($parts['host']);
        if (strlen($host) > 253 || !str_contains($host, '.')
            || filter_var($host, FILTER_VALIDATE_IP)
            || filter_var($host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return false;
        }

        // Literal IPs, local-only names, ports and malformed authorities are
        // not needed for Microsoft CDN downloads. The actual network address
        // is checked and DNS-pinned separately immediately before cURL.
        return true;
    }

    /**
     * Resolve all DNS destinations and pin public IPs to prevent internal SSRF.
     * No redirect is followed, and no bearer token is sent to the URL.
     */
    private static function stream_download(string $url, $destination, int $limit): int
    {
        $hostname = (string) parse_url($url, PHP_URL_HOST);
        $records = dns_get_record($hostname, DNS_A | DNS_AAAA);
        if (!is_array($records) || !$records) {
            throw new RuntimeException('OneDrive download DNS lookup failed');
        }
        $addresses = [];
        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                throw new RuntimeException('OneDrive download destination blocked');
            }
            $addresses[] = $ip;
        }
        if (!$addresses || !function_exists('curl_init')) {
            throw new RuntimeException('OneDrive download transport unavailable');
        }
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('OneDrive download transport unavailable');
        }
        $bytes = 0;
        $exceeded = false;
        try {
            $options = self::secure_options() + [
                CURLOPT_HTTPHEADER => ['Accept: application/octet-stream'],
                CURLOPT_CONNECTTIMEOUT => 12,
                CURLOPT_TIMEOUT => 180,
                // Prevent environment proxies from bypassing DNS/IP pinning.
                CURLOPT_PROXY => '',
                CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use ($destination, $limit, &$bytes, &$exceeded): int {
                    $size = strlen($chunk);
                    if ($bytes + $size > $limit) {
                        $exceeded = true;
                        return 0;
                    }
                    $written = fwrite($destination, $chunk);
                    if ($written !== $size) {
                        return 0;
                    }
                    $bytes += $written;
                    return $size;
                },
            ];
            // HTTPS hostname still determines certificate and HTTP Host validation.
            $ip = $addresses[0];
            $options[CURLOPT_RESOLVE] = [$hostname . ':443:' . (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) ? '[' . $ip . ']' : $ip)];
            curl_setopt_array($curl, $options);
            $ok = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if ($exceeded) {
                throw new RuntimeException('OneDrive file exceeds attachment limit');
            }
            if ($ok === false || $status !== 200) {
                throw new RuntimeException('OneDrive download failed (HTTP ' . $status . ')');
            }
        } finally {
            curl_close($curl);
        }
        return $bytes;
    }

    /** Common HTTPS-only, redirect-free, TLS-verified cURL options. */
    private static function secure_options(): array
    {
        $options = [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ];
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[CURLOPT_PROTOCOLS_STR] = 'https';
        } else {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        return $options;
    }
}

// END OF FILE
