<?php

/**
 * Read-only Google Drive folder browser for Toolbox Cloud.
 *
 * Uses fixed Google HTTPS endpoints, an authenticated account context, and
 * an already authorized bearer token. Does not download or export content.
 */
require_once __DIR__ . '/provider.php';

/**
 * List Google Drive folders and file metadata without exporting file bytes.
 */
final class toolbox_cloud_google_drive implements toolbox_cloud_provider
{
    private const FOLDER_MIME = 'application/vnd.google-apps.folder';
    private const MAX_RESPONSE_BYTES = 1048576;

    /** Return the persisted provider identifier. */
    public function id(): string
    {
        return 'google_drive';
    }

    /**
     * List a single page from the user's My Drive folder, including files.
     *
     * @param array $account Owned account with a decrypted access_token.
     * @param ?string $folder_id Opaque Drive folder ID or null for My Drive root.
     * @param ?string $cursor Opaque Google page token.
     * @return array Normalized metadata and optional pagination token.
     */
    public function list_files(array $account, ?string $folder_id, ?string $cursor): array
    {
        $token = $account['credentials']['access_token'] ?? null;
        if (!is_string($token) || !preg_match('/^[\x21-\x7E]{12,8192}$/D', $token)) {
            throw new RuntimeException('Google access token unavailable');
        }
        $folder = $folder_id === null || $folder_id === '' ? 'root' : $folder_id;
        if (!self::valid_id($folder)) {
            throw new InvalidArgumentException('Invalid Google folder identifier');
        }
        if ($cursor !== null && ($cursor === '' || strlen($cursor) > 2048
            || !preg_match('/^[A-Za-z0-9_\-\.=\/+:]+$/D', $cursor))) {
            throw new InvalidArgumentException('Invalid Google pagination token');
        }
        $params = [
            'q' => "'" . $folder . "' in parents and trashed = false",
            'fields' => 'nextPageToken,files(id,name,mimeType,size)',
            'pageSize' => 100,
            'orderBy' => 'folder,name_natural',
            'spaces' => 'drive',
        ];
        if ($cursor !== null) {
            $params['pageToken'] = $cursor;
        }
        $url = 'https://www.googleapis.com/drive/v3/files?' . http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $payload = self::get_json($url, $token);
        if (!is_array($payload['files'] ?? null)) {
            throw new RuntimeException('Invalid Google Drive listing');
        }
        $items = [];
        foreach ($payload['files'] as $file) {
            if (!is_array($file) || !is_string($file['id'] ?? null)
                || !self::valid_id($file['id']) || !is_string($file['name'] ?? null)) {
                continue;
            }
            $mime = is_string($file['mimeType'] ?? null) ? $file['mimeType'] : 'application/octet-stream';
            $folder_entry = $mime === self::FOLDER_MIME;
            $size = $file['size'] ?? null;
            $size = $size !== null && ctype_digit((string) $size) && (float) $size <= PHP_INT_MAX
                ? (int) $size : null;
            $items[] = [
                'id' => $file['id'],
                'name' => mb_substr($file['name'], 0, 255),
                'is_dir' => $folder_entry,
                'size' => $folder_entry ? null : $size,
                'mime_type' => $folder_entry ? null : $mime,
            ];
        }
        $next = $payload['nextPageToken'] ?? null;
        if ($next !== null && (!is_string($next) || strlen($next) > 2048)) {
            throw new RuntimeException('Invalid Google Drive pagination response');
        }
        return ['items' => $items, 'next_cursor' => $next];
    }

    /**
     * Download a Drive file or export a Google Doc/Sheet/Slide as PDF.
     * Validates metadata on the server and enforces a strict streaming limit.
     *
     * @param array $account Owned, decrypted Google account.
     * @param string $file_id Opaque Google Drive file identifier.
     * @param resource $destination Caller-owned writable stream.
     * @param int $max_bytes Allowed maximum output size.
     * @return array Downloaded file metadata.
     */
    public function download_file(array $account, string $file_id, $destination, int $max_bytes): array
    {
        $token = $account['credentials']['access_token'] ?? null;
        if (!is_string($token) || !preg_match('/^[\x21-\x7E]{12,8192}$/D', $token)
            || !self::valid_id($file_id) || !is_resource($destination)
            || $max_bytes < 1 || $max_bytes > 104857600) {
            throw new InvalidArgumentException('Invalid Google download context');
        }
        $base = 'https://www.googleapis.com/drive/v3/files/' . rawurlencode($file_id);
        $info = self::get_json($base . '?fields=' . rawurlencode('id,name,mimeType,size,capabilities(canDownload),trashed'), $token);
        if (($info['id'] ?? null) !== $file_id || ($info['trashed'] ?? false) === true
            || ($info['capabilities']['canDownload'] ?? false) !== true) {
            throw new RuntimeException('Google file is not downloadable');
        }
        $name = (string) ($info['name'] ?? '');
        if ($name === '' || strlen($name) > 1024 || preg_match('~[\x00-\x1f/\\\\]~', $name)) {
            throw new RuntimeException('Invalid remote filename');
        }
        $mime = (string) ($info['mimeType'] ?? 'application/octet-stream');
        if ($mime === self::FOLDER_MIME) {
            throw new RuntimeException('Folders cannot be attached');
        }
        if (in_array($mime, [
            'application/vnd.google-apps.document',
            'application/vnd.google-apps.spreadsheet',
            'application/vnd.google-apps.presentation',
        ], true)) {
            $url = $base . '/export?mimeType=application%2Fpdf';
            $name .= '.pdf';
            $mime = 'application/pdf';
            $max_bytes = min($max_bytes, 10 * 1024 * 1024);
        } else {
            if (str_starts_with($mime, 'application/vnd.google-apps.')) {
                throw new RuntimeException('Unsupported Google-native file format');
            }
            if (isset($info['size']) && ctype_digit((string) $info['size'])
                && (float) $info['size'] > $max_bytes) {
                throw new RuntimeException('Google file exceeds attachment limit');
            }
            $url = $base . '?alt=media';
        }
        $size = self::stream_file($url, $token, $destination, $max_bytes);
        return ['name' => $name, 'size' => $size, 'mime_type' => $mime];
    }

    /**
     * Stream from Google's fixed API host; reject redirects and excess bytes.
     * Error bodies are discarded by the caller if the request fails.
     *
     * @param string $url Fixed Google API URL.
     * @param string $token OAuth bearer token.
     * @param resource $destination Writable temporary stream.
     * @param int $limit Maximum bytes.
     * @return int Number of downloaded bytes.
     */
    private static function stream_file(string $url, string $token, $destination, int $limit): int
    {
        if (!preg_match('~^https://www\.googleapis\.com/drive/v3/files/[A-Za-z0-9_-]+(?:\?alt=media|/export\?mimeType=application%2Fpdf)$~D', $url)
            || !function_exists('curl_init')) {
            throw new RuntimeException('Google download transport unavailable');
        }
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Google download transport unavailable');
        }
        $bytes = 0;
        $exceeded = false;
        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: */*', 'Authorization: Bearer ' . $token],
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use ($destination, $limit, &$bytes, &$exceeded): int {
                $length = strlen($chunk);
                if ($bytes + $length > $limit) {
                    $exceeded = true;
                    return 0;
                }
                $written = fwrite($destination, $chunk);
                if ($written !== $length) {
                    return 0;
                }
                $bytes += $written;
                return $written;
            },
        ];
        if (defined('CURLOPT_PROTOCOLS_STR')) {
            $options[CURLOPT_PROTOCOLS_STR] = 'https';
        } else {
            $options[CURLOPT_PROTOCOLS] = CURLPROTO_HTTPS;
        }
        try {
            curl_setopt_array($curl, $options);
            $result = curl_exec($curl);
            $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if ($exceeded) {
                throw new RuntimeException('Google file exceeds attachment limit');
            }
            if ($result === false || $status !== 200) {
                throw new RuntimeException('Google download failed (HTTP ' . $status . ')');
            }
            return $bytes;
        } finally {
            curl_close($curl);
        }
    }

    /** Validate an opaque Drive ID; never use it as a URL or local path. */
    private static function valid_id(string $id): bool
    {
        return strlen($id) <= 256 && preg_match('/^[A-Za-z0-9_-]+$/D', $id) === 1;
    }

    /**
     * Send an authenticated JSON GET to Google's fixed Drive API host.
     * Redirects, insecure TLS, unexpected hosts, and oversized results fail.
     */
    private static function get_json(string $url, string $token): array
    {
        if (!(str_starts_with($url, 'https://www.googleapis.com/drive/v3/files?')
            || preg_match('~^https://www\.googleapis\.com/drive/v3/files/[A-Za-z0-9_-]+\?fields=[A-Za-z0-9%_,()]+$~D', $url))
            || !function_exists('curl_init')) {
            throw new RuntimeException('Google Drive API transport unavailable');
        }
        $curl = curl_init($url);
        if ($curl === false) {
            throw new RuntimeException('Google Drive API transport unavailable');
        }
        $body = '';
        $options = [
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $token],
            CURLOPT_WRITEFUNCTION => static function ($handle, string $chunk) use (&$body): int {
                if (strlen($body) + strlen($chunk) > self::MAX_RESPONSE_BYTES) {
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
            $result = curl_exec($curl);
            $code = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
            if ($result === false || $code !== 200) {
                throw new RuntimeException('Google Drive listing failed (HTTP ' . $code . ')');
            }
        } finally {
            curl_close($curl);
        }
        $json = json_decode($body, true, 32, JSON_THROW_ON_ERROR);
        if (!is_array($json)) {
            throw new RuntimeException('Invalid Google Drive JSON');
        }
        return $json;
    }
}

// END OF FILE
