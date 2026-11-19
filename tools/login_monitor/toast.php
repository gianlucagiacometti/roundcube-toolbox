<?php

/**
 * Login Monitor: previous-login payload for the native Roundcube notification.
 *
 * History visibility applies to optional location fields at query time.
 * Nothing is transmitted to an external geolocation service.
 * Optional flag-icons SVGs are read from the module Composer vendor directory.
 */

/** Prepare the user's preceding successful login for an escaped client UI. */
final class toolbox_login_monitor_toast
{
    /**
     * Read only permitted columns from one previous successful login.
     *
     * @param object $rcube Authenticated Roundcube application.
     * @param object $db Roundcube database adapter.
     * @param int $userId Authenticated user's numeric ID.
     * @param int $currentId Recorded login row for the current session.
     * @param array $policy Effective field visibility settings.
     * @return array Safe raw values; the browser escapes before rendering HTML.
     */
    public static function payload($rcube, $db, int $userId, int $currentId, array $policy): array
    {
        if ($userId < 1 || $currentId < 1) {
            throw new \InvalidArgumentException('Invalid login marker');
        }

        $name = '';
        $identity = $rcube->user->get_identity();
        if (is_array($identity) && is_string($identity['name'] ?? null)) {
            $name = trim($identity['name']);
        }
        if ($name === '') {
            $name = (string) $rcube->user->get_username();
        }
        $name = function_exists('mb_substr') ? mb_substr($name, 0, 160) : substr($name, 0, 160);

        $columns = ['logged_in_at'];
        $cityVisible = toolbox_login_monitor_fields::visible($policy, 'city');
        $countryVisible = toolbox_login_monitor_fields::visible($policy, 'country');
        if ($cityVisible) {
            $columns[] = 'city';
        }
        if ($countryVisible) {
            $columns[] = 'country';
        }

        $table = $db->table_name('toolbox_login_monitor_logins', true);
        $sql = 'SELECT ' . implode(', ', $columns) . " FROM {$table}"
            . ' WHERE user_id = ? AND id < ? ORDER BY logged_in_at DESC, id DESC';
        $result = $db->limitquery($sql, 0, 1, $userId, $currentId);
        if ($result === false || $db->is_error($result)) {
            throw new \RuntimeException('Previous login unavailable');
        }

        $row = $db->fetch_assoc($result) ?: [];
        $timestamp = $row['logged_in_at'] ?? null;
        $date = is_numeric($timestamp) && (int) $timestamp > 0
            ? (string) $rcube->format_date((int) $timestamp, 'Y-m-d H:i:s') : '';

        // A private/local-network address produces no GeoIP city. Never guess.
        $city = $cityVisible && is_string($row['city'] ?? null)
            ? trim($row['city']) : '';
        $country = $city !== '' && $countryVisible && is_string($row['country'] ?? null)
            ? strtoupper(trim($row['country'])) : '';
        if (!preg_match('/^[A-Z]{2}$/D', $country)) {
            $country = '';
        }

        return ['name' => $name, 'date' => $date, 'city' => $city, 'country' => $country];
    }

    /**
     * Build a local image URL from an SVG installed by Composer.
     *
     * Composer autoload registers the package metadata; the asset itself is
     * not a PHP class and cannot be autoloaded. A data URI avoids making the
     * vendor directory web-accessible or adding a new unauthenticated route.
     * Missing dependencies or unsupported country codes leave the emoji fallback.
     *
     * @param string $country Validated ISO 3166-1 alpha-2 country code.
     * @return string Base64 SVG image URI, or an empty string when unavailable.
     */
    public static function flag_data_uri(string $country): string
    {
        if (!preg_match('/^[A-Z]{2}$/D', $country)) {
            return '';
        }

        // Match the separate Composer project already used for GeoIP and device detection.
        $vendor = __DIR__ . '/vendor';
        $autoload = $vendor . '/autoload.php';
        if (!is_file($autoload)) {
            return '';
        }

        try {
            require_once $autoload;
            if (!class_exists(\Composer\InstalledVersions::class)
                || !\Composer\InstalledVersions::isInstalled('lipis/flag-icons')) {
                return '';
            }

            $installPath = \Composer\InstalledVersions::getInstallPath('lipis/flag-icons');
            if (!is_string($installPath) || $installPath === '') {
                return '';
            }

            $filename = $installPath . '/flags/4x3/' . strtolower($country) . '.svg';
            if (!is_file($filename) || !is_readable($filename)) {
                return '';
            }

            // Prevent unexpectedly large vendor assets from bloating page output.
            $size = filesize($filename);
            if ($size === false || $size < 1 || $size > 262144) {
                return '';
            }

            $svg = file_get_contents($filename);
            if (!is_string($svg) || !preg_match('/^<svg(?:\s|>)/i', ltrim($svg))) {
                return '';
            }

            return 'data:image/svg+xml;base64,' . base64_encode($svg);
        } catch (\Throwable $exception) {
            // The toast must never interrupt Roundcube when Composer is unavailable.
            return '';
        }
    }
}

// END OF FILE
