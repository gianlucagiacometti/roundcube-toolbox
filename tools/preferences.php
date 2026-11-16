<?php

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
        $current = $this->rcube->user->get_prefs();
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
        return $this->rcube->user->save_prefs($changes);
    }
}

// END OF FILE

