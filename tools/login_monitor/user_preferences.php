<?php

/**
 * Login Monitor: per-account collection and visibility preferences.
 *
 * Administrator defaults and user preferences have distinct names because
 * Roundcube merges saved user preferences into its runtime configuration.
 * The administrator can explicitly forbid collection or exposure of any field.
 */

/** Resolve and validate user choices within administrator-defined capabilities. */
final class toolbox_login_monitor_user_preferences
{
    public const KEY = 'toolbox_login_monitor_user_fields';

    /**
     * Return which field controls the administrator permits a user to enable.
     *
     * An omitted permission defaults to true. Explicit false means the field
     * cannot be collected or exposed, irrespective of saved user preferences.
     * City and country are unavailable without a readable configured MMDB file.
     *
     * @param object $config Roundcube configuration provider with get().
     * @return array<string, array{collect: bool, visible: bool}>
     */
    public static function permissions($config): array
    {
        $permissions = [];
        foreach (toolbox_login_monitor_fields::defaults() as $field => $flags) {
            $permissions[$field] = ['collect' => true, 'visible' => true];
        }

        $custom = $config->get('toolbox_login_monitor_user_permissions', []);
        if (!is_array($custom)) {
            toolbox_logger::error('login_monitor', 'user_permissions', 'invalid_config');
            $custom = [];
        }
        foreach ($custom as $field => $flags) {
            if (!isset($permissions[$field]) || !is_array($flags)) {
                toolbox_logger::error('login_monitor', 'user_permissions', 'invalid_field');
                continue;
            }
            foreach (['collect', 'visible'] as $name) {
                if (!array_key_exists($name, $flags)) {
                    continue;
                }
                if (!is_bool($flags[$name])) {
                    toolbox_logger::error('login_monitor', 'user_permissions', 'invalid_flag');
                    continue;
                }
                $permissions[$field][$name] = $flags[$name];
            }
        }

        $cityPath = $config->get('toolbox_login_monitor_geoip_city_database', '');
        if (!is_string($cityPath) || $cityPath === '') {
            $cityPath = $config->get('toolbox_login_monitor_geoip_database', '');
        }
        $countryPath = $config->get('toolbox_login_monitor_geoip_country_database', '');
        if (!is_string($countryPath) || $countryPath === '') {
            $countryPath = $cityPath;
        }

        if (!self::database_available($cityPath)) {
            $permissions['city'] = ['collect' => false, 'visible' => false];
        }
        if (!self::database_available($countryPath)) {
            $permissions['country'] = ['collect' => false, 'visible' => false];
        }

        return $permissions;
    }

    /**
     * Sanitize a stored preference map without accepting unknown field names.
     *
     * @param mixed $saved Untrusted data from the Roundcube user record.
     * @return array<string, array{collect?: bool, visible?: bool}>
     */
    public static function clean($saved): array
    {
        if (!is_array($saved)) {
            if ($saved !== null) {
                toolbox_logger::error('login_monitor', 'user_preferences', 'invalid_store');
            }
            return [];
        }

        $result = [];
        foreach (toolbox_login_monitor_fields::defaults() as $field => $defaults) {
            if (!isset($saved[$field]) || !is_array($saved[$field])) {
                continue;
            }
            foreach (['collect', 'visible'] as $flag) {
                if (isset($saved[$field][$flag]) && is_bool($saved[$field][$flag])) {
                    $result[$field][$flag] = $saved[$field][$flag];
                }
            }
        }

        return $result;
    }

    /**
     * Compute the effective policy for this user for capture or history output.
     *
     * Administrator policy supplies initial values, sanitized user choices can
     * override those values only when permitted, and administrator prohibitions
     * always win. Visibility does not depend on collection of new login events.
     *
     * @param object $config Roundcube configuration provider.
     * @param mixed $saved Personal preference map.
     * @return array<string, array{collect: bool, visible: bool}>
     */
    public static function effective($config, $saved): array
    {
        $policies = toolbox_login_monitor_fields::policies($config);
        $permissions = self::permissions($config);
        $overrides = self::clean($saved);

        foreach ($policies as $field => &$flags) {
            foreach (['collect', 'visible'] as $flag) {
                if (!$permissions[$field][$flag]) {
                    $flags[$flag] = false;
                } elseif (isset($overrides[$field]) && array_key_exists($flag, $overrides[$field])) {
                    $flags[$flag] = $overrides[$field][$flag];
                }
            }
        }
        unset($flags);

        return $policies;
    }

    /**
     * Merge editable checkbox values into stored user preferences.
     *
     * Missing checkboxes mean false; unavailable/forbidden fields are left
     * unchanged, so a temporarily missing GeoIP file does not erase choices.
     *
     * @param mixed $saved Previous personal settings.
     * @param array $permissions Effective administrative permissions.
     * @param callable $read Returns a submitted scalar for a checkbox name.
     * @return array<string, array{collect?: bool, visible?: bool}>
     */
    public static function submitted($saved, array $permissions, callable $read): array
    {
        $choices = self::clean($saved);
        foreach ($permissions as $field => $flags) {
            foreach (['collect', 'visible'] as $flag) {
                if (!empty($flags[$flag])) {
                    $choices[$field][$flag] = $read('_lm_' . $field . '_' . $flag) === '1';
                }
            }
        }
        return $choices;
    }

    /**
     * Check whether a GeoIP file path can be used by the offline lookup helper.
     *
     * @param mixed $path Configured absolute City or Country MMDB filename.
     * @return bool True only for an absolute, readable file.
     */
    private static function database_available($path): bool
    {
        if (!is_string($path) || $path === '' || str_contains($path, "\0")) {
            return false;
        }

        $absolute = str_starts_with($path, '/')
            || preg_match('/\A[A-Za-z]:[\\\\\/]/', $path) === 1
            || str_starts_with($path, '\\\\');

        return $absolute && is_file($path) && is_readable($path);
    }
}

// END OF FILE

