<?php

/**
 * Roundcube Toolbox preference access and shared diagnostic integration.
 */
require_once __DIR__ . '/logger.php';

/**
 * Read and write current Roundcube user preferences for Toolbox modules.
 *
 * This service intentionally keeps no snapshot: after Roundcube saves a
 * preference, the same request's form render sees the updated value.
 * Modules supply their own defaults and write only keys that they own.
 */
final class toolbox_preferences
{
    private rcube $rcube;

    /**
     * Create a preference accessor for the active Roundcube user.
     *
     * @param rcube $rcube Current Roundcube instance.
     */
    public function __construct(rcube $rcube)
    {
        $this->rcube = $rcube;
    }

    /**
     * Return the current preference, or the calling module's default.
     *
     * No value is cached; this method reads Roundcube's live user state.
     * A stored null is treated as unset, matching the old isset() defaults.
     *
     * @param string $name Preference key.
     * @param mixed $default Value to use when the key is absent or null.
     * @return mixed Current preference value or supplied default.
     */
    public function get(string $name, $default = null)
    {
        toolbox_logger::step('preferences', 'get_start');
        try {
            $current = $this->rcube->user->get_prefs();
        } catch (\Throwable $e) {
            toolbox_logger::error('preferences', 'get', 'read_exception');
            throw $e;
        }

        toolbox_logger::step('preferences', 'get_complete');
        return $current[$name] ?? $default;
    }

    /**
     * Save only the preferences supplied by the calling module.
     *
     * Roundcube merges partial updates with existing user preferences and
     * refreshes its in-request state. No other tool's values are overwritten.
     *
     * @param array $changes Preference-key/value pairs to write.
     * @return bool True if Roundcube persisted the changes.
     */
    public function save(array $changes): bool
    {
        toolbox_logger::step('preferences', 'save_start');
        try {
            $saved = $this->rcube->user->save_prefs($changes);
        } catch (\Throwable $e) {
            toolbox_logger::error('preferences', 'save', 'write_exception');
            throw $e;
        }

        if (!$saved) {
            toolbox_logger::error('preferences', 'save', 'persistence_failed');
            return false;
        }

        toolbox_logger::step('preferences', 'save_complete');
        return true;
    }
}

// END OF FILE
