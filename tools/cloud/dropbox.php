<?php

/**
 * Dropbox read-only file provider for Roundcube Toolbox Cloud.
 *
 * Uses Dropbox's fixed API and content hosts. Remote identifiers and listing
 * cursors are passed only as JSON to known endpoints, never interpreted as
 * local paths or arbitrary URLs. Access tokens are never returned to clients.
 */
require_once __DIR__ . '/provider.php';

/**
 * Browse an authorized user's Dropbox and stream ordinary files as attachments.
 */
final class toolbox_cloud_dropbox implements toolbox_cloud_provider
{
    private const RPC_LIST = 'https://api.dropboxapi.com/2/files/list_folder';
    private const RPC_CONTINUE = 'https://api.dropboxapi.com/2/files/list_folder/continue';
    private const CONTENT_DOWNLOAD = 'https://content.dropboxapi.com/2/files/download';
    private const MAX_JSON_BYTES = 2097152;
    private const MAX_DOWNLOAD_BYTES = 104857600;

    /** Return the stable account provider identifier. */
    public function id(): string
    {
        return 'dropbox';
    }

    /**
     * List one folder (100 entries/page), including Dropbox's opaque cursor.
     * Folder IDs, rather than paths, let the UI handle renamed folders safely.
     *
     * @param array $account Owned account with decrypted OAuth credentials.
     * @param string|null $folder_id Dropbox folder ID or null for account root.
     * @param string|null $cursor Dropbox continuation cursor, if any.
     * @return array{items: array, next_cursor: ?string} Provider-neutral listing.
     */
    public function list_files(array $account, ?string $folder_id, ?string $cursor): array
    {
        $token = self::access_token($account);
        $folder = $folder_id ?? '';
        if ($folder !== '' && !self::valid_item_id($folder)) {
            throw new InvalidArgumentException('Invalid Dropbox folder identifier');
        }
        if ($cursor !== null && $cursor !== '') {
            if (strlen($cursor) > 2048 || preg_match('/^[\x21-\x7e]+$/D', $cursor) !== 1) {
                throw new InvalidArgumentException('Invalid Dropbox pagination cursor');
            }
            $payload = self::rpc_json(self::RPC_CONTINUE, ['cursor' => $cursor], $token);
        } else {
            $payload = self::rpc_json(self::RPC_LIST, [
                'path' => $folder,
                'recursive' => false,
                'include_deleted' => false,
                'limit' => 100,
            ], $token);
        }
        if (!is_array($payload['entries'] ?? null)
            || !is_bool($payload['has_more'] ?? null)) {
            throw new RuntimeException('Invalid Dropbox folder listing response');
        }
        $items = [];
        foreach ($payload['entries'] as $entry) {
            if (!is_array($entry) || !in_array($entry['.tag'] ?? null, ['file', 'folder'], true)
                || !is_string($entry['id'] ?? null) || !self::valid_item_id($entry['id'])
                || !is_string($entry['name'] ?? null) || $entry['name'] === '') {
                continue;
            }
            $directory = $entry['.tag'] === 'folder';
            $size = $entry['size'] ?? null;
            $size = is_int($size) && $size >= 0 ? $size : null;
            $items[] = [
                'id' => $entry['id'],
                'name' => mb_substr($entry['name'], 0, 255),
                'is_dir' => $directory,
                'size' => $directory ? null : $size,
                'mime_type' => $directory ? null : 'application/octet-stream',
            ];
        }
        $next = null;
        if ($payload['has_more']) {
            $next = $payload['cursor'] ?? null;
            if (!is_string($next) || $next === '' || strlen($next) > 2048
                || preg_match('/^[\x21-\x7e]+$/D', $next) !== 1) {
                throw new RuntimeException('Invalid Dropbox listing cursor response');
            }
        }
        return ['items' => $items, 'next_cursor' => $next];
    }

    /**
     * Stream one Dropbox file directly into Roundcube's temporary destination.
     * The Dropbox-API-Result header is authenticated by the HTTPS connection
     * and must agree with the requested item ID and received byte count.
     *
     * @param array $account Owned account with decrypted OAuth credentials.
     * @param string $file_id Opaque Dropbox file ID returned by listing.
     * @param resource $destination Caller-owned writable temporary stream.
     * @param int $max_bytes Attachment size limit, up to 100 MiB.
     * @return array{name: string, mime_type: string, size: int} Verified file.
     */
    public function download_file(array $account, string $file_id, $destination, int $max_bytes): array
    {
        if (!self::valid_item_id($file_id) || !is_resource($destination)
            || $max_bytes < 1 || $max_bytes > self::MAX_DOWNLOAD_BYTES) {
            throw new InvalidArgumentException('Invalid Dropbox download request');
        }
        $token = self::access_token($account);
        $args = json_encode(['path' => $file_id], JSON_THROW_ON_ERROR);
        $handle = self::curl(self::CONTENT_DOWNLOAD);
        $bytes = 0;
        $exceeded = false;
        $result_header = null;
        $invalid_header = false;
        try {
            $options = self::secure_options() + [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => '',
                CURLOPT_CONNECTTIMEOUT => 12,
                CURLOPT_TIMEOUT => 180,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Dropbox-API-Arg: ' . $args,
                    'Content-Type:',
                ],
                CURLOPT_HEADERFUNCTION => static function ($curl, string $line) use (&$result_header, &$invalid_header): int {
                    $length = strlen($line);
                    if (stripos($line, 'Dropbox-API-Result:') === 0) {
                        if ($result_header !== null || $length > 16384) {
                            $invalid_header = true;
                        } else {
                            $result_header = trim(substr($line, strlen('Dropbox-API-Result:')));
                        }
                    }
                    return $length;
                },
                CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use ($destination, $max_bytes, &$bytes, &$exceeded): int {
                    $length = strlen($chunk);
                    if ($length > $max_bytes - $bytes) {
                        $exceeded = true;
                        return 0;
                    }
                    $written = fwrite($destination, $chunk);
                    if ($written !== $length) {
                        return 0;
                    }
                    $bytes += $written;
                    return $length;
                },
            ];
            curl_setopt_array($handle, $options);
            $ok = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if ($exceeded) {
                throw new RuntimeException('Dropbox file exceeds attachment limit');
            }
            if ($ok === false || $status !== 200) {
                throw new RuntimeException('Dropbox download failed (HTTP ' . $status . ')');
            }
        } finally {
            curl_close($handle);
        }
        if ($invalid_header || !is_string($result_header) || $result_header === '') {
            throw new RuntimeException('Dropbox download metadata unavailable');
        }
        try {
            $metadata = json_decode($result_header, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('Invalid Dropbox download metadata');
        }
        if (!is_array($metadata)
            || (isset($metadata['.tag']) && $metadata['.tag'] !== 'file')
            || ($metadata['id'] ?? null) !== $file_id
            || !is_int($metadata['size'] ?? null) || $metadata['size'] !== $bytes) {
            throw new RuntimeException('Dropbox download metadata or size mismatch');
        }
        $name = $metadata['name'] ?? null;
        if (!is_string($name) || $name === '' || strlen($name) > 1024
            || preg_match('~[\x00-\x1f/\\\\]~', $name)) {
            throw new RuntimeException('Invalid Dropbox filename');
        }
        return [
            'name' => $name,
            'size' => $bytes,
            'mime_type' => self::file_mime_type($destination),
        ];
    }

    /** Validate Dropbox's stable file/folder ID, never a URL or filesystem path. */
    private static function valid_item_id(string $id): bool
    {
        return strlen($id) <= 512 && preg_match('/^id:[A-Za-z0-9_-]+$/D', $id) === 1;
    }

    /** Retrieve and validate the short-lived access token from this owned account. */
    private static function access_token(array $account): string
    {
        $token = $account['credentials']['access_token'] ?? null;
        if (!is_string($token) || preg_match('/^[\x21-\x7e]{12,8192}$/D', $token) !== 1) {
            throw new RuntimeException('Dropbox access token unavailable');
        }
        return $token;
    }

    /** Send bounded JSON requests to the two fixed Dropbox folder endpoints. */
    private static function rpc_json(string $url, array $body, string $token): array
    {
        if (!in_array($url, [self::RPC_LIST, self::RPC_CONTINUE], true)) {
            throw new RuntimeException('Dropbox API destination unavailable');
        }
        $handle = self::curl($url);
        $response = '';
        $overflow = false;
        try {
            curl_setopt_array($handle, self::secure_options() + [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($body, JSON_THROW_ON_ERROR),
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_TIMEOUT => 35,
                CURLOPT_HTTPHEADER => [
                    'Authorization: Bearer ' . $token,
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                CURLOPT_WRITEFUNCTION => static function ($curl, string $chunk) use (&$response, &$overflow): int {
                    if (strlen($response) + strlen($chunk) > self::MAX_JSON_BYTES) {
                        $overflow = true;
                        return 0;
                    }
                    $response .= $chunk;
                    return strlen($chunk);
                },
            ]);
            $ok = curl_exec($handle);
            $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
            if ($overflow) {
                throw new RuntimeException('Dropbox folder listing response too large');
            }
            if ($ok === false || $status !== 200) {
                throw new RuntimeException('Dropbox listing failed (HTTP ' . $status . ')');
            }
        } finally {
            curl_close($handle);
        }
        try {
            $payload = json_decode($response, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $error) {
            throw new RuntimeException('Invalid Dropbox folder listing JSON');
        }
        if (!is_array($payload)) {
            throw new RuntimeException('Invalid Dropbox folder listing');
        }
        return $payload;
    }

    /** Recognize the content MIME type without trusting a Dropbox filename. */
    private static function file_mime_type($destination): string
    {
        $info = stream_get_meta_data($destination);
        $path = $info['uri'] ?? null;
        if (is_string($path) && is_file($path) && class_exists('finfo')) {
            try {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($path);
                if (is_string($mime)
                    && preg_match('~^[a-zA-Z0-9!#$&^_.+-]+/[a-zA-Z0-9!#$&^_.+-]+$~D', $mime)) {
                    return $mime;
                }
            } catch (Throwable $ignored) {
                // A safe generic MIME is sufficient; Roundcube retains the name.
            }
        }
        return 'application/octet-stream';
    }

    /** Initialize a curl handle for one non-configurable HTTPS Dropbox hostname. */
    private static function curl(string $url): CurlHandle
    {
        if (!in_array($url, [self::RPC_LIST, self::RPC_CONTINUE, self::CONTENT_DOWNLOAD], true)
            || !function_exists('curl_init')) {
            throw new RuntimeException('Dropbox transport unavailable');
        }
        $handle = curl_init($url);
        if (!$handle instanceof CurlHandle) {
            throw new RuntimeException('Dropbox transport unavailable');
        }
        return $handle;
    }

    /** Enforce HTTPS/TLS validation and disallow all redirects and other protocols. */
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
