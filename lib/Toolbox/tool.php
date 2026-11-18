<?php

/**
 * Shared base for independently loaded Roundcube Toolbox tool modules.
 *
 * Every tool can access the host plugin, Roundcube instance and shared
 * preference service without relying on Toolbox's private implementation details.
 */

require_once __DIR__ . '/logger.php';

abstract class toolbox_tool_module
{
    protected toolbox $plugin;
    protected rcube $rcube;
    protected toolbox_preferences $preferences;
    protected int $loglevel;
    protected string $logfile;

    /**
     * Initialise the shared context for a Toolbox module.
     *
     * @param toolbox $plugin Parent plugin instance.
     * @param rcube $rcube Roundcube instance.
     * @param toolbox_preferences $preferences Live, on-demand user preference access.
     * @param int $loglevel Configured Toolbox logging level.
     * @param string $logfile Toolbox logging destination.
     */
    public function __construct(toolbox $plugin, rcube $rcube, toolbox_preferences $preferences, int $loglevel, string $logfile)
    {
        $this->plugin = $plugin;
        $this->rcube = $rcube;
        $this->preferences = $preferences;
        $this->loglevel = $loglevel;
        $this->logfile = $logfile;
    }

    /**
     * Activate the module's hooks, actions or presentation features.
     *
     * @return void
     */
    abstract public function init(): void;
    /**
     * Apply module-specific settings after all modules have been initialized.
     * Use the storage supplier only when a module needs persistence at startup.
     *
     * @param callable $storage_supplier Lazy shared storage supplier.
     * @return void
     */
    public function configure_runtime(callable $storage_supplier): void
    {
    }

    /**
     * State whether this module should appear as a Toolbox settings section.
     *
     * Background-only modules override this method to return false. They
     * remain loaded for hooks, but never appear in the Toolbox settings list.
     *
     * @return bool True for normal user-facing Toolbox tools.
     */
    public function is_visible_in_settings(): bool
    {
        return true;
    }

    /**
     * Check if the current user is allowed to see this module in Settings.
     *
     * @param object $storage Active Toolbox storage backend.
     * @return bool Whether the module is accessible.
     */
    public function can_access_settings($storage): bool
    {
        return true;
    }

    /**
     * Load settings-page assets when a module is enabled and accessible.
     *
     * @return void
     */
    public function include_settings_assets(): void
    {
    }

    /**
     * Render the current section using the module's existing form builder.
     * A module with a different form signature can override this method.
     *
     * @param object $storage Shared Toolbox storage backend.
     * @param array $attributes Roundcube form-renderer attributes.
     * @param callable $fieldset Shared fieldset renderer.
     * @return array Body HTML and whether Toolbox appends the closing form tag.
     */
    public function render_section($storage, array $attributes, callable $fieldset): array
    {
        if (!method_exists($this, 'render_form')) {
            return ['content' => '', 'append_form_end' => true];
        }

        return [
            'content' => $fieldset($this->render_form($storage, $attributes), 'main'),
            'append_form_end' => true,
        ];
    }

    /**
     * Prepare data for Toolbox's common storage and toolbox_save hook.
     * Return null to abort the save, for example after a preference error.
     *
     * @param object $storage Shared Toolbox storage backend.
     * @return ?array Save data including new_settings and optional messages.
     */
    public function prepare_save($storage): ?array
    {
        return [
            'new_settings' => method_exists($this, 'prepare_settings')
                ? $this->prepare_settings() : [],
        ];
    }

    /**
     * Dispatch an optional module-specific AJAX operation.
     *
     * @param string $action Operation name (toggle, check or delete).
     * @param object $storage Shared Toolbox storage backend.
     * @return bool True when this module handled the operation.
     */
    public function handle_action(string $action, $storage): bool
    {
        return false;
    }

    /**
     * Include any module-specific CSS or JS for its settings editor.
     *
     * @return void
     */
    public function include_edit_assets(): void
    {
    }

}

// END OF FILE


