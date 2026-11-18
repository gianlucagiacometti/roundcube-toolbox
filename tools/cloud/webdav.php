<?php

/**
 * Read-only HTTPS WebDAV provider for Toolbox Cloud.
 *
 * Intended for Nextcloud, ownCloud and generic WebDAV accounts. The caller
 * supplies a verified, owned account containing its configured WebDAV root URL
 * and request-local, decrypted username/app-password credentials.
 *
 * This driver never stores credentials, creates shares or changes remote files.
 * Public addresses are accepted by default. Explicit administrator-approved
 * hostname-to-private-IPv4 mappings may be allowed for trusted installations.
 */

require_once __DIR__ . '/provider.php';

/** Implement the common Cloud provider contract with safe WebDAV operations. */
final class toolbox_cloud_webdav implements toolbox_cloud_provider
{
    private const PAGE_SIZE = 100;
    private const MAX_ENTRIES = 2000;
    private const MAX_XML_BYTES = 2097152;
    private const CONNECT_TIMEOUT = 8;
    private const REQUEST_TIMEOUT = 90;

    /** Return the canonical provider identifier. */
    public function id(): string
    {
        return 'webdav';
    }

    /**
     * List direct children of a WebDAV collection; return a 100-item page.
     *
     * WebDAV has no standard server-side pagination, so the bounded collection
     * is fetched once per requested page. Large collections fail closed.
     */
    public function list_files(array $account, ?string $folder_id, ?string $cursor): array
    {
        $context = $this->context($account);
        $folder = $this->relative_path($folder_id ?? '', true);
        $offset = 0;
        if ($cursor !== null) {
            if (!preg_match('/^(0|[1-9][0-9]{0,5})$/D', $cursor)) {
                throw new InvalidArgumentException('Invalid WebDAV pagination cursor');
            }
            $offset = (int) $cursor;
        }
        $url = $this->resource_url($context['endpoint'], $folder, true);
        $xml = '';
        $body = '<?xml version="1.0" encoding="utf-8"?>'
            . '<d:propfind xmlns:d="DAV:"><d:prop><d:displayname/>'
            . '<d:resourcetype/><d:getcontentlength/><d:getcontenttype/>'
            . '</d:prop></d:propfind>';
        $status = $this->request($context, $url, 'PROPFIND',
            ['Depth: 1', 'Content-Type: application/xml; charset=utf-8'],
            static function (string $chunk) use (&$xml): int {
                if (strlen($xml) + strlen($chunk) > self::MAX_XML_BYTES) {
                    return 0;
                }
                $xml .= $chunk;
                return strlen($chunk);
            }, $body);
        if ($status !== 207) {
            throw new RuntimeException('WebDAV listing failed (HTTP ' . $status . ')');
        }
        $entries = $this->parse_multistatus($xml, $context['endpoint'], $folder);
        usort($entries, static function (array $left, array $right): int {
            return ($right['is_dir'] <=> $left['is_dir'])
                ?: strnatcasecmp($left['name'], $right['name'])
                ?: strcmp($left['id'], $right['id']);
        });
        return [
            'items' => array_slice($entries, $offset, self::PAGE_SIZE),
            'next_cursor' => ($offset + self::PAGE_SIZE < count($entries))
                ? (string) ($offset + self::PAGE_SIZE) : null,
        ];
    }

    /**
     * Download a remote file to a caller-owned stream, enforcing a byte cap.
     *
     * The destination is only written; the caller must discard it on errors.
     * The selected file should have come from the verified listing response.
     */
    public function download_file(array $account, string $file_id, $destination, int $max_bytes): array
    {
        if (!is_resource($destination) || get_resource_type($destination) !== 'stream'
            || $max_bytes < 1) {
            throw new InvalidArgumentException('Invalid WebDAV download destination or limit');
        }
        $context = $this->context($account);
        $path = $this->relative_path($file_id, false);
        $url = $this->resource_url($context['endpoint'], $path, false);
        $count = 0;
        $status = $this->request($context, $url, 'GET', [],
            static function (string $chunk) use ($destination, $max_bytes, &$count): int {
                $length = strlen($chunk);
                if ($count + $length > $max_bytes) {
                    return 0;
                }
                $written = fwrite($destination, $chunk);
                if ($written === false || $written !== $length) {
                    return 0;
                }
                $count += $length;
                return $written;
            });
        if ($status !== 200) {
            throw new RuntimeException('WebDAV download failed (HTTP ' . $status . ')');
        }
        $name = basename($path);
        return ['name' => $name, 'mime_type' => 'application/octet-stream', 'size' => $count];
    }

    /**
     * Validate and normalize a trusted, decrypted account context.
     *
     * Expected: endpoint and credentials containing username and app_password.
     */
    private function context(array $account): array
    {
        $endpoint = $account['endpoint'] ?? null;
        $credentials = $account['credentials'] ?? null;
        if (!is_string($endpoint) || !is_array($credentials)
            || !is_string($credentials['username'] ?? null)
            || !is_string($credentials['app_password'] ?? null)
            || $credentials['username'] === '' || $credentials['app_password'] === '') {
            throw new InvalidArgumentException('Incomplete WebDAV account');
        }
        $parts = parse_url($endpoint);
        if ($parts === false || strtolower($parts['scheme'] ?? '') !== 'https'
            || empty($parts['host']) || isset($parts['user']) || isset($parts['pass'])
            || isset($parts['query']) || isset($parts['fragment'])
            || !in_array($parts['port'] ?? 443, [443], true)) {
            throw new InvalidArgumentException('WebDAV requires a public HTTPS endpoint on port 443');
        }
        $hostname = strtolower($parts['host']);
        if (filter_var($hostname, FILTER_VALIDATE_IP) !== false
            || !preg_match('/^[a-z0-9](?:[a-z0-9.-]*[a-z0-9])?$/D', $hostname)
            || strlen($hostname) > 253 || !str_contains($hostname, '.')
            || str_ends_with($hostname, '.local')) {
            throw new InvalidArgumentException('WebDAV hostname must be public DNS');
        }
        $base = $parts['path'] ?? '/';
        if (preg_match('/%(?:2f|5c|2e|00)/i', $base)
            || str_contains($base, '\\') || str_contains($base, '//')
            || str_contains($base, '/../') || str_contains($base, '/./')) {
            throw new InvalidArgumentException('Unsafe WebDAV base path');
        }
        $endpoint = 'https://' . $hostname . rtrim($base, '/') . '/';
        return [
            'endpoint' => $endpoint,
            'hostname' => $hostname,
            'username' => $credentials['username'],
            'password' => $credentials['app_password'],
        ];
    }

    /** Validate a relative remote path without accepting traversal or URLs. */
    private function relative_path(string $path, bool $directory): string
    {
        if (strlen($path) > 4096 || str_contains($path, '\\')
            || str_contains($path, '?') || str_contains($path, '#')
            || str_starts_with($path, '/') || str_contains($path, "\0")) {
            throw new InvalidArgumentException('Unsafe WebDAV file identifier');
        }
        $path = trim($path, '/');
        $segments = $path === '' ? [] : explode('/', $path);
        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..'
                || preg_match('/[\x00-\x1f\x7f]/', $segment)) {
                throw new InvalidArgumentException('Unsafe WebDAV path segment');
            }
        }
        if (!$directory && $path === '') {
            throw new InvalidArgumentException('File identifier is required');
        }
        return $path;
    }

    /** Assemble an HTTPS URL from a validated root and encoded path segments. */
    private function resource_url(string $endpoint, string $relative, bool $directory): string
    {
        $segments = $relative === '' ? [] : explode('/', $relative);
        $path = implode('/', array_map('rawurlencode', $segments));
        return $endpoint . $path . ($directory && $path !== '' ? '/' : '');
    }

    /**
     * Fetch using cURL with DNS-pinned approved destinations, no redirects or
     * proxies, TLS verification and bounded timeouts.
     */
    private function request(array $context, string $url, string $method, array $headers, callable $writer, ?string $body = null): int
    {
        if (!extension_loaded('curl')) {
            throw new RuntimeException('PHP cURL extension is required for WebDAV');
        }
        $ip = $this->resolve_approved_ip($context['hostname']);
        $handle = curl_init($url);
        if ($handle === false) {
            throw new RuntimeException('Unable to initialize WebDAV request');
        }
        try {
            $resolve = $context['hostname'] . ':443:' . (str_contains($ip, ':') ? '[' . $ip . ']' : $ip);
            $options = [
                CURLOPT_CUSTOMREQUEST => $method,
                CURLOPT_HTTPHEADER => $headers,
                CURLOPT_USERNAME => $context['username'],
                CURLOPT_PASSWORD => $context['password'],
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXREDIRS => 0,
                CURLOPT_PROXY => '',
                CURLOPT_NOPROXY => '*',
                CURLOPT_RESOLVE => [$resolve],
                CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
                CURLOPT_CONNECTTIMEOUT => self::CONNECT_TIMEOUT,
                CURLOPT_TIMEOUT => self::REQUEST_TIMEOUT,
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_WRITEFUNCTION => static function ($ch, string $chunk) use ($writer): int {
                    return $writer($chunk);
                },
                CURLOPT_USERAGENT => 'Roundcube-Toolbox-Cloud/1',
            ];
            if ($body !== null) {
                $options[CURLOPT_POSTFIELDS] = $body;
            }
            curl_setopt_array($handle, $options);
            if (curl_exec($handle) === false) {
                throw new RuntimeException('WebDAV connection or transfer failed');
            }
            return (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        } finally {
            curl_close($handle);
        }
    }

    /**
     * Resolve a destination to an approved IP, rejecting DNS rebinding risks.
     *
     * Public addresses remain allowed by default. A private IPv4 address is
     * allowed only when its exact DNS hostname AND address are present in the
     * administrator's toolbox_cloud_trusted_webdav_hosts setting. A hostname
     * with any unexpected DNS address is rejected in its entirety.
     *
     * @param string $host Validated lower-case DNS hostname.
     * @return string Approved address to pin via CURLOPT_RESOLVE.
     */
    private function resolve_approved_ip(string $host): string
    {
        $records = dns_get_record($host, DNS_A | DNS_AAAA);
        if ($records === false || $records === []) {
            throw new RuntimeException('WebDAV host DNS resolution failed');
        }

        $trusted = $this->trusted_private_ipv4($host);
        $addresses = [];
        foreach ($records as $record) {
            $ip = $record['ip'] ?? $record['ipv6'] ?? null;
            if (is_string($ip) && filter_var($ip, FILTER_VALIDATE_IP)) {
                $addresses[] = $ip;
            }
        }
        $addresses = array_values(array_unique($addresses));
        if ($addresses === []) {
            throw new RuntimeException('WebDAV host has no usable address');
        }

        foreach ($addresses as $ip) {
            $public = filter_var($ip, FILTER_VALIDATE_IP,
                FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false;
            if (!$public && !in_array($ip, $trusted, true)) {
                throw new RuntimeException('WebDAV host resolves to a forbidden network');
            }
        }

        // For explicitly trusted hosts, refuse unknown public addresses too.
        // The administrator has approved only the listed destination(s).
        if ($trusted !== []) {
            foreach ($addresses as $ip) {
                if (!in_array($ip, $trusted, true)) {
                    throw new RuntimeException('WebDAV trusted host DNS address mismatch');
                }
            }
        }

        return $addresses[0];
    }

    /**
     * Read and validate the administrator-controlled private IPv4 allowlist.
     *
     * The setting maps exact DNS names to arrays of explicit RFC1918 IPv4
     * addresses. Loopback, link-local, public IPs, CIDRs and wildcards cannot
     * be added through this setting. Invalid entries fail closed.
     *
     * @param string $host Validated lower-case DNS hostname.
     * @return array<string> Approved private IPv4 addresses for this host.
     */
    private function trusted_private_ipv4(string $host): array
    {
        $config = rcube::get_instance()->config->get('toolbox_cloud_trusted_webdav_hosts', []);
        if (!is_array($config)) {
            throw new RuntimeException('Invalid trusted WebDAV host configuration');
        }

        $addresses = $config[$host] ?? [];
        if (!is_array($addresses)) {
            throw new RuntimeException('Invalid trusted WebDAV IP configuration');
        }
        if (count($addresses) > 16) {
            throw new RuntimeException('Trusted WebDAV host has too many addresses');
        }
        $approved = [];
        foreach ($addresses as $ip) {
            if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
                || !$this->is_rfc1918_ipv4($ip)) {
                throw new RuntimeException('Invalid trusted WebDAV IPv4 address');
            }
            $approved[] = $ip;
        }

        return array_values(array_unique($approved));
    }

    /**
     * Check whether an address belongs to the RFC1918 private IPv4 ranges.
     *
     * @param string $ip Valid IPv4 literal.
     * @return bool True only for 10/8, 172.16/12 or 192.168/16.
     */
    private function is_rfc1918_ipv4(string $ip): bool
    {
        $octets = array_map('intval', explode('.', $ip));
        return $octets[0] === 10
            || ($octets[0] === 172 && $octets[1] >= 16 && $octets[1] <= 31)
            || ($octets[0] === 192 && $octets[1] === 168);
    }

    /** Parse a bounded WebDAV multistatus response into normalized child entries. */
    private function parse_multistatus(string $xml, string $endpoint, string $folder): array
    {
        $document = new DOMDocument();
        $old = libxml_use_internal_errors(true);
        try {
            if (!@$document->loadXML($xml, LIBXML_NONET | LIBXML_NOBLANKS)) {
                throw new RuntimeException('Invalid WebDAV XML response');
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($old);
        }
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('d', 'DAV:');
        $entries = [];
        $root_path = parse_url($endpoint, PHP_URL_PATH);
        $expected_parent = rawurldecode($root_path) . ($folder !== '' ? $folder . '/' : '');
        $root_origin = parse_url($endpoint);
        foreach ($xpath->query('/d:multistatus/d:response') as $response) {
            if (count($entries) >= self::MAX_ENTRIES) {
                throw new RuntimeException('WebDAV folder exceeds safe listing limit');
            }
            $href = trim($xpath->evaluate('string(d:href)', $response));
            $parts = parse_url($href);
            if ($parts === false) {
                continue;
            }
            if (isset($parts['host']) && (strtolower($parts['host']) !== strtolower($root_origin['host'])
                || strtolower($parts['scheme'] ?? '') !== 'https'
                || ($parts['port'] ?? 443) !== 443)) {
                continue;
            }
            if (isset($parts['query']) || isset($parts['fragment'])) {
                continue;
            }
            $path = rawurldecode($parts['path'] ?? '');
            if (!str_starts_with($path, $expected_parent)) {
                continue;
            }
            $child = substr($path, strlen($expected_parent));
            $child = rtrim($child, '/');
            if ($child === '' || str_contains($child, '/')) {
                continue; // Skip the requested directory and nested results.
            }
            try {
                $relative = $this->relative_path(($folder !== '' ? $folder . '/' : '') . $child, false);
            } catch (InvalidArgumentException $exception) {
                continue;
            }
            $props = $xpath->query('d:propstat[d:status[contains(., " 200 ")]]/d:prop', $response);
            if ($props->length === 0) {
                continue;
            }
            $prop = $props->item(0);
            $dir = $xpath->query('d:resourcetype/d:collection', $prop)->length > 0;
            $length = trim($xpath->evaluate('string(d:getcontentlength)', $prop));
            $mime = trim($xpath->evaluate('string(d:getcontenttype)', $prop));
            $size = $length !== '' && ctype_digit($length) && (float) $length <= PHP_INT_MAX
                ? (int) $length : null;
            $entries[] = [
                'id' => $relative,
                'name' => $child,
                'is_dir' => $dir,
                'size' => $dir ? null : $size,
                'mime_type' => $dir ? null : ($mime !== '' ? $mime : null),
            ];
        }
        return $entries;
    }
}

// END OF FILE
