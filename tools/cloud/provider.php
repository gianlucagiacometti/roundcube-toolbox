<?php

/**
 * Common provider contract for the Toolbox Cloud module.
 *
 * This file defines the boundary between account-specific cloud providers and
 * Toolbox. It intentionally does not handle OAuth redirects, persistence,
 * encryption, HTTP responses, or Roundcube attachment creation.
 *
 * Every provider must scope operations to the authenticated account supplied
 * by the Cloud service. Remote file identifiers must never be interpreted as
 * local filesystem paths or trusted URLs.
 */
interface toolbox_cloud_provider
{
    /**
     * Return the stable provider identifier, such as 'webdav' or 'google'.
     *
     * @return string Lowercase identifier unique among registered providers.
     */
    public function id(): string;

    /**
     * List a page of files/folders visible to the connected account.
     *
     * Account credentials are resolved by the Cloud service and supplied only
     * to the selected provider; they must never be returned in response data.
     *
     * @param array $account Authenticated account context, with secrets supplied
     *                       only by the Cloud service at request time.
     * @param string|null $folder_id Opaque provider folder identifier, or null
     *                               to start at the account's root.
     * @param string|null $cursor Opaque pagination cursor, or null for first page.
     * @return array{items: list<array{id: string, name: string, is_dir: bool,
     *                size: ?int, mime_type: ?string}>, next_cursor: ?string}
     *                A page of metadata; never return credentials or file bytes.
     */
    public function list_files(array $account, ?string $folder_id, ?string $cursor): array;

    /**
     * Stream a selected remote file into an already opened local destination.
     *
     * The Cloud service creates and owns the temporary file, validates user
     * ownership of the account, enforces download size/time limits, and later
     * passes the completed file to Roundcube's attachment lifecycle.
     * Implementations must stream in chunks and enforce the supplied byte cap;
     * never load an entire large file into PHP memory.
     *
     * @param array $account Authenticated account context.
     * @param string $file_id Opaque identifier obtained from this provider.
     * @param resource $destination Writable stream owned by the Cloud service.
     * @param int $max_bytes Maximum accepted file size in bytes.
     * @return array{name: string, mime_type: string, size: int}
     *               Verified downloaded file metadata.
     * @throws RuntimeException When the file cannot be fetched safely or fully.
     */
    public function download_file(array $account, string $file_id, $destination, int $max_bytes): array;
}

// END OF FILE

