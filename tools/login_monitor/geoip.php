<?php

/**
 * Login Monitor: optional offline city/country lookup using local MMDB files.
 *
 * City and Country paths can point to GeoLite2 or GeoIP2 databases, including
 * different editions and directories. A City database can provide both fields;
 * a separate Country file is optional. IPs are not sent to an external service.
 */

/** Resolve enabled city and country fields from administrator-selected MMDB files. */
final class toolbox_login_monitor_geoip
{
    /**
     * Look up an eligible public client IP without interrupting authentication.
     *
     * City and Country are configured independently. If the Country path is
     * empty, the City database also supplies the country. The older single-path
     * setting is accepted as a fallback for existing installations.
     *
     * Neither addresses, file paths, results nor third-party errors are logged.
     *
     * @param mixed $address Raw REMOTE_ADDR value supplied by the web server.
     * @param array $policies Validated per-field collection/visibility settings.
     * @param object $config Roundcube configuration object exposing get().
     * @return array{city: ?string, country: ?string} Country uses ISO 3166-1 alpha-2.
     */
    public static function locate($address, array $policies, $config): array
    {
        $result = ['city' => null, 'country' => null];
        $wantCity = toolbox_login_monitor_fields::collect($policies, 'city');
        $wantCountry = toolbox_login_monitor_fields::collect($policies, 'country');

        if (!$wantCity && !$wantCountry) {
            toolbox_logger::step('login_monitor', 'geoip_collection_disabled');
            return $result;
        }

        // Never trust client-supplied forwarding headers without a separately
        // configured and validated trusted-proxy policy.
        if (!is_string($address) || filter_var(
            $address,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false) {
            toolbox_logger::step('login_monitor', 'geoip_address_unavailable');
            return $result;
        }

        $cityPath = $config->get('toolbox_login_monitor_geoip_city_database', '');
        if ($cityPath === '') {
            // Compatibility with the original Stage 2 configuration.
            $cityPath = $config->get('toolbox_login_monitor_geoip_database', '');
        }

        $countryPath = $config->get('toolbox_login_monitor_geoip_country_database', '');
        if ($countryPath === '') {
            // City MMDBs also contain country.iso_code.
            $countryPath = $cityPath;
        }

        if ((!$wantCity || $cityPath === '' || $cityPath === null)
            && (!$wantCountry || $countryPath === '' || $countryPath === null)) {
            toolbox_logger::step('login_monitor', 'geoip_not_configured');
            return $result;
        }

        try {
            // Composer can be installed locally in the module, or an existing
            // Roundcube autoloader may already provide the reader class.
            $autoload = __DIR__ . '/vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }

            if (!class_exists(\MaxMind\Db\Reader::class)) {
                toolbox_logger::step('login_monitor', 'geoip_reader_unavailable');
                return $result;
            }
        } catch (\Throwable $exception) {
            toolbox_logger::error('login_monitor', 'geoip_lookup', 'reader_unavailable');
            return $result;
        }

        $cityRecord = null;
        if ($wantCity && $cityPath !== '' && $cityPath !== null) {
            $cityRecord = self::read_record($cityPath, $address);
            if (is_array($cityRecord)) {
                $result['city'] = self::city_name($cityRecord['city']['names']['en'] ?? null);
            }
        }

        if ($wantCountry && $countryPath !== '' && $countryPath !== null) {
            // Reuse the record when both fields use the same City database.
            $countryRecord = ($wantCity && $countryPath === $cityPath)
                ? $cityRecord : self::read_record($countryPath, $address);
            if (is_array($countryRecord)) {
                $result['country'] = self::country_code($countryRecord['country']['iso_code'] ?? null);
            }
        }

        toolbox_logger::step('login_monitor', 'geoip_lookup_completed');
        return $result;
    }

    /**
     * Read one local MMDB record with path validation and non-fatal failures.
     *
     * @param mixed $path Configured absolute path to an MMDB file.
     * @param string $address Valid public IP address.
     * @return ?array Matched MMDB record, or null if unavailable.
     */
    private static function read_record($path, string $address): ?array
    {
        if (!is_string($path) || strpos($path, "\0") !== false || !self::is_absolute_path($path)) {
            toolbox_logger::error('login_monitor', 'geoip_lookup', 'invalid_database_path');
            return null;
        }

        if (!is_file($path) || !is_readable($path)) {
            toolbox_logger::error('login_monitor', 'geoip_lookup', 'database_unreadable');
            return null;
        }

        try {
            $reader = new \MaxMind\Db\Reader($path);
            try {
                $record = $reader->get($address);
            } finally {
                $reader->close();
            }

            if (is_array($record)) {
                return $record;
            }

            toolbox_logger::step('login_monitor', 'geoip_no_match');
        } catch (\Throwable $exception) {
            toolbox_logger::error('login_monitor', 'geoip_lookup', 'lookup_failed');
        }

        return null;
    }

    /**
     * Check that an MMDB path is absolute on POSIX or Windows.
     *
     * @param string $path Configured file path.
     * @return bool Whether the path is absolute.
     */
    private static function is_absolute_path(string $path): bool
    {
        return str_starts_with($path, '/')
            || preg_match('/\A[A-Za-z]:[\\\\\/]/', $path) === 1
            || str_starts_with($path, '\\\\');
    }

    /**
     * Clean and bound a city name to fit the database column.
     *
     * @param mixed $name City name returned by the MMDB reader.
     * @return ?string Cleaned name or null.
     */
    private static function city_name($name): ?string
    {
        if (!is_string($name)) {
            return null;
        }

        $name = trim($name);
        $name = preg_replace('/[\x00-\x1f\x7f]/', '', $name);
        if (!is_string($name) || $name === '') {
            return null;
        }

        if (preg_match('/\A.{1,150}/us', $name, $matches) !== 1) {
            return null;
        }

        return $matches[0];
    }

    /**
     * Store a locale-independent ISO country code.
     *
     * @param mixed $code Country code from the MMDB record.
     * @return ?string Uppercase two-letter code or null.
     */
    private static function country_code($code): ?string
    {
        if (!is_string($code)) {
            return null;
        }

        $code = strtoupper($code);
        return preg_match('/\A[A-Z]{2}\z/D', $code) === 1 ? $code : null;
    }
}

// END OF FILE

