<?php

/**
 * Login Monitor: optional Matomo Device Detector adapter.
 *
 * Uses the local Composer autoloader when installed, or another already
 * registered Composer autoloader. Parses request headers only when required
 * by collection policy; raw User-Agent data is never logged or sent remotely.
 */

/** Convert Matomo parser results into bounded, optional login metadata. */
final class toolbox_login_monitor_detector
{
    /**
     * Parse a browser environment with the locally installed Matomo library.
     *
     * @param string $userAgent Untrusted HTTP User-Agent; never logged here.
     * @param array $policies Resolved collect/visible policies.
     * @return array<string, string|int|null> Recognized parser fields only.
     */
    public static function describe(string $userAgent, array $policies): array
    {
        $fields = [
            'device_type', 'os_name', 'os_version', 'browser_name',
            'browser_version', 'device_brand', 'device_model',
            'browser_engine', 'bot_detection',
        ];
        $result = array_fill_keys($fields, null);

        $wanted = false;
        foreach ($fields as $field) {
            $wanted = $wanted || toolbox_login_monitor_fields::collect($policies, $field);
        }
        if (!$wanted) {
            toolbox_logger::step('login_monitor', 'device_detection_not_collected');
            return $result;
        }

        if ($userAgent === '' || strlen($userAgent) > 4096) {
            toolbox_logger::step('login_monitor', 'user_agent_unavailable');
            return $result;
        }

        try {
            // Prefer the component-local installation. If it is absent,
            // another registered Composer autoloader may provide Matomo.
            $autoload = __DIR__ . '/vendor/autoload.php';
            if (is_file($autoload)) {
                require_once $autoload;
            }

            if (!class_exists(\DeviceDetector\DeviceDetector::class)) {
                toolbox_logger::step('login_monitor', 'device_detector_unavailable');
                return $result;
            }

            // Use request Client Hints when available. Some privacy-oriented
            // browsers intentionally identify as Chrome in the User-Agent but
            // provide their browser brand in Sec-CH-UA. Never persist headers.
            $clientHints = \DeviceDetector\ClientHints::factory($_SERVER);
            $detector = new \DeviceDetector\DeviceDetector($userAgent, $clientHints);
            $detector->parse();

            $bot = $detector->isBot();
            if (toolbox_login_monitor_fields::collect($policies, 'bot_detection')) {
                $result['bot_detection'] = $bot ? 1 : 0;
            }

            // Matomo deliberately stops parsing other details for recognized bots.
            // A bot is not a physical device type; do not store it as one.
            if ($bot) {
                toolbox_logger::step('login_monitor', 'device_detection_completed');
                return $result;
            }

            $values = [
                'device_type' => self::type_code($detector->getDeviceName()),
                'os_name' => self::label($detector->getOs('name'), 100),
                'os_version' => self::label($detector->getOs('version'), 50),
                'browser_name' => self::label($detector->getClient('name'), 100),
                'browser_version' => self::label($detector->getClient('version'), 50),
                'device_brand' => self::label($detector->getBrandName(), 100),
                'device_model' => self::label($detector->getModel(), 150),
                'browser_engine' => self::label($detector->getClient('engine'), 100),
            ];

            foreach ($values as $field => $value) {
                if (toolbox_login_monitor_fields::collect($policies, $field)) {
                    $result[$field] = $value;
                }
            }

            toolbox_logger::step('login_monitor', 'device_detection_completed');
        } catch (\Throwable $exception) {
            // Never log exceptions containing request data or library internals.
            toolbox_logger::error('login_monitor', 'detect_device', 'detector_failed');
        }

        return $result;
    }

    /**
     * Normalize a device category for eventual translation in the history UI.
     *
     * @param mixed $value Matomo device type description.
     * @return ?string Stable lowercase machine key or null.
     */
    private static function type_code($value): ?string
    {
        if (!is_string($value) || trim($value) === '') {
            return null;
        }
        $code = strtolower(trim($value));
        $code = preg_replace('/[^a-z0-9]+/', '_', $code);
        $code = trim($code ?? '', '_');
        return $code !== '' && strlen($code) <= 32 ? $code : null;
    }

    /**
     * Bound a parsed display label to a database field without extra extensions.
     *
     * Presentation code must still HTML-escape any value before displaying it.
     *
     * @param mixed $value Parser-produced value, possibly unknown.
     * @param int $maxCharacters Column maximum in Unicode characters.
     * @return ?string Clean, bounded name/version or null.
     */
    private static function label($value, int $maxCharacters): ?string
    {
        if (!is_string($value)) {
            return null;
        }
        $value = trim($value);
        if ($value === '' || strtoupper($value) === 'UNK' || strcasecmp($value, 'unknown') === 0) {
            return null;
        }
        $value = preg_replace('/[\x00-\x1f\x7f]/', '', $value);
        if (!is_string($value) || $value === '') {
            return null;
        }
        if (preg_match('/\A.{0,' . $maxCharacters . '}/us', $value, $matches) !== 1) {
            return null;
        }
        return $matches[0] !== '' ? $matches[0] : null;
    }
}

// END OF FILE

