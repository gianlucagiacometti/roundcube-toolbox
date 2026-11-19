<?php

/**
 * Login Monitor: administrator field-collection and display policies.
 *
 * Each supported field has independent collect and visible flags. Only strict
 * booleans are accepted. Unknown fields cannot create database columns or
 * bypass the eventual history presentation whitelist.
 */

/** Resolve the safe default and administrator overrides for Login Monitor. */
final class toolbox_login_monitor_fields
{
    /**
     * Describe every supported field and its privacy-conscious default policy.
     *
     * City/country collection additionally requires an administrator-supplied
     * local city MMDB path and the optional MMDB reader dependency.
     *
     * @return array<string, array{collect: bool, visible: bool}>
     */
    public static function defaults(): array
    {
        return [
            'browser_name' => ['collect' => true, 'visible' => true],
            'browser_version' => ['collect' => true, 'visible' => true],
            'os_name' => ['collect' => true, 'visible' => true],
            'os_version' => ['collect' => true, 'visible' => true],
            'device_type' => ['collect' => true, 'visible' => true],
            'device_brand' => ['collect' => true, 'visible' => true],
            'device_model' => ['collect' => true, 'visible' => true],
            'city' => ['collect' => true, 'visible' => true],
            'country' => ['collect' => true, 'visible' => true],
            'ip_address' => ['collect' => false, 'visible' => false],
            'user_agent' => ['collect' => false, 'visible' => false],
            'browser_engine' => ['collect' => false, 'visible' => false],
            'bot_detection' => ['collect' => false, 'visible' => false],
        ];
    }

    /**
     * Apply strict administrator overrides to the complete default policy.
     *
     * A partial map is valid. Malformed entries are ignored and categorized
     * in logs without logging the administrator's values.
     *
     * @param object $config Roundcube configuration object exposing get().
     * @return array<string, array{collect: bool, visible: bool}>
     */
    public static function policies($config): array
    {
        $policies = self::defaults();
        $custom = $config->get('toolbox_login_monitor_fields', []);
        if (!is_array($custom)) {
            toolbox_logger::error('login_monitor', 'field_policy', 'invalid_config');
            return $policies;
        }

        foreach ($custom as $field => $settings) {
            if (!array_key_exists($field, $policies) || !is_array($settings)) {
                toolbox_logger::error('login_monitor', 'field_policy', 'invalid_field');
                continue;
            }

            foreach (['collect', 'visible'] as $flag) {
                if (!array_key_exists($flag, $settings)) {
                    continue;
                }
                if (!is_bool($settings[$flag])) {
                    toolbox_logger::error('login_monitor', 'field_policy', 'invalid_flag');
                    continue;
                }
                $policies[$field][$flag] = $settings[$flag];
            }
        }

        return $policies;
    }

    /**
     * Return whether collection is enabled for one supported field.
     *
     * @param array $policies Resolved field policies.
     * @param string $field A known field name.
     * @return bool True only when explicitly permitted by the policy.
     */
    public static function collect(array $policies, string $field): bool
    {
        return isset($policies[$field]['collect']) && $policies[$field]['collect'] === true;
    }

    /**
     * Return whether an existing value may be exposed in user history.
     *
     * This is independent of whether *future* login events collect the field.
     * The history controller must apply this whitelist before serialization.
     *
     * @param array $policies Resolved field policies.
     * @param string $field A known field name.
     * @return bool True only when the field may be visible.
     */
    public static function visible(array $policies, string $field): bool
    {
        return isset($policies[$field]['visible']) && $policies[$field]['visible'] === true;
    }
}

// END OF FILE

