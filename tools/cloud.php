<?php

/**
 * Toolbox Cloud module: registers its authenticated request controller.
 *
 * Exposes the Cloud vault and account-management UI in Toolbox Settings.
 * Compose integration injects its dedicated picker only on mail/compose.
 */

require_once __DIR__ . '/../lib/Toolbox/logger.php';
require_once __DIR__ . '/cloud/controller.php';
require_once __DIR__ . '/cloud/view.php';

/** Register Cloud actions while preserving existing Toolbox tool contracts. */
final class toolbox_tool_cloud extends toolbox_tool_module
{
    /** Register the Cloud action in mail and settings task contexts. */
    public function init(): void
    {
        toolbox_logger::step('cloud', 'module_initialised');
        // Personal-account applications require a query-free callback URI.
        // The authenticated ready hook dispatches that callback, not an open PHP file.
        $this->plugin->add_hook('ready', [$this, 'on_ready']);
        if (in_array($this->rcube->task, ['mail', 'settings'], true)) {
            $this->plugin->register_action('plugin.toolbox.cloud', [$this, 'handle_cloud_request']);
            if ($this->rcube->task === 'settings') {
                $this->plugin->register_action('plugin.toolbox.cloud.google.callback', [$this, 'handle_google_callback']);
                $this->plugin->register_action('plugin.toolbox.cloud.microsoft.callback', [$this, 'handle_microsoft_callback']);
                $this->plugin->register_action('plugin.toolbox.cloud.dropbox.callback', [$this, 'handle_dropbox_callback']);
            }
            if ($this->rcube->task === 'mail' && $this->rcube->action === 'compose') {
                // Compose uses a separate JS application and the same compiled Cloud stylesheet.
                $this->rcube->output->include_css(
                    'plugins/' . $this->plugin->ID . '/skins/elastic/toolbox.cloud.css'
                );
                $this->export_client_labels();
                $this->plugin->include_script('js/toolbox.cloud.compose.js');
            }
        }
    }

    /**
     * Handle only a tagged OneDrive Personal OAuth response at the configured
     * Roundcube root. The hook runs after session authentication. State, user
     * ownership, PKCE and the unlocked vault are checked by the controller.
     *
     * @param array $args Roundcube ready-hook arguments, returned unchanged.
     * @return array Unmodified Roundcube routing data for all other requests.
     */
    public function on_ready(array $args): array
    {
        $state = $_GET['state'] ?? null;
        if (strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? '')) !== 'GET'
            || !is_string($state)
            || !preg_match('/^tbxmp\.[A-Za-z0-9_-]{43}$/D', $state)
            || isset($_GET['_task']) || isset($_GET['_action'])
            || (!isset($_GET['code']) && !isset($_GET['error']))) {
            return $args;
        }
        try {
            $config = toolbox_cloud_microsoft_oauth::settings($this->rcube, 'personal');
            $path = parse_url($config['redirect_uri'], PHP_URL_PATH) ?: '/';
            $request_path = parse_url((string) ($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);
            if ($request_path !== $path || (int) ($this->rcube->user->ID ?? 0) < 1) {
                return $args;
            }
        } catch (RuntimeException $error) {
            toolbox_logger::error('cloud', 'personal_callback_routing', 'configuration_unavailable');
            return $args;
        }
        (new toolbox_cloud_controller($this->rcube, [$this->plugin, 'gettext']))->handle_microsoft_personal_callback();
        return $args;
    }

    /** Include the Cloud Settings UI script only on Cloud's edit screen. */
    public function include_edit_assets(): void
    {
        // Load the compiled Cloud LESS stylesheet through Roundcube.
        $this->rcube->output->include_css(
            'plugins/' . $this->plugin->ID . '/skins/elastic/toolbox.cloud.css'
        );
        $this->export_client_labels();
        $this->plugin->include_script('js/toolbox.cloud.js');
    }

    /**
     * Export the Cloud labels required by its Settings and Compose scripts.
     * Only translated interface strings are exported, never vault secrets.
     *
     * @return void
     */
    private function export_client_labels(): void
    {
        $keys = [
            'account-added', 'account-edit-required', 'account-new-required', 'account-removed', 'account-unavailable',
            'account-updated', 'accounts-loaded', 'add-account', 'automatic-lock-in', 'browse-cloud-files',
            'browse-files-named', 'bytes', 'choose-provider-first', 'close', 'cloud-account-default',
            'cloud-account-title', 'compose-account', 'compose-attach', 'compose-attach-from-cloud', 'compose-attach-named',
            'compose-attached', 'compose-attachment-default', 'compose-choose-account', 'compose-create-vault', 'compose-delete',
            'compose-importing', 'compose-invalid-id', 'compose-no-accounts', 'compose-picker-unsupported', 'compose-remove-attachment',
            'compose-request-failed', 'compose-secret-label', 'compose-secret-required', 'compose-select-account', 'compose-send-failed',
            'compose-unlock-help', 'connect-named', 'connection-needs-unlock', 'delete-account', 'delete-cloud-account',
            'dialog-unsupported', 'dropbox-auth-failed', 'dropbox-connected', 'dropbox-open-failed', 'dropbox-popups',
            'edit-account', 'edit-cloud-account', 'edit-named', 'edit-webdav-help', 'enter-vault-password',
            'folder-empty', 'folder-list-failed', 'folder-list-failed-start', 'generic-webdav', 'google-auth-failed',
            'google-connected', 'google-open-failed', 'google-popups', 'keep-app-password', 'keep-credentials-help',
            'keep-username', 'loading-folder', 'locked', 'microsoft-open-failed', 'my-drive',
            'my-named', 'new-password-length', 'new-password-mismatch', 'next-entries', 'nextcloud-webdav-help',
            'no-cloud-accounts', 'onedrive-auth-failed', 'onedrive-connected', 'onedrive-popups', 'operation-failed',
            'owncloud-webdav-help', 'paste-recovery', 'personal-auth-failed', 'personal-connected', 'personal-open-failed',
            'personal-popups', 'processing-request', 'recovery-changed', 'recovery-key', 'remove-account-confirm',
            'request-in-progress', 'request-send-failed', 'rotate-recovery-confirm', 'save-changes', 'select-valid-account',
            'timer-reset', 'unlock-before-browse', 'unlock-cloud-first', 'unlock-connections', 'unlock-expired',
            'unlock-first', 'unlock-method', 'unlock-secret-required', 'unlock-to-list', 'unlock-vault',
            'unlocked', 'up-folder', 'vault-created', 'vault-locked', 'vault-method-required',
            'vault-password', 'vault-password-length', 'vault-password-mismatch', 'vault-password-updated', 'vault-unlocked',
            'webdav-url-help',
        ];
        $labels = [];
        foreach ($keys as $key) {
            $labels[$key] = $this->plugin->gettext('cloud-ui-' . $key);
        }
        $this->rcube->output->set_env('toolbox_cloud_labels', $labels);
    }

    /**
     * Render Cloud controls inside Toolbox's existing editor form.
     * The controls use independent AJAX actions and never submit credentials
     * through the generic Toolbox Save operation.
     *
     * @param object $storage Existing Toolbox storage adapter, unused here.
     * @param array $attributes Existing editor attributes, unused here.
     * @param callable $fieldset Toolbox's fieldset renderer, unused here.
     * @return array Editor content and normal enclosing-form disposition.
     */
    public function render_section($storage, array $attributes, callable $fieldset): array
    {
        $oauth = [
            'google' => $this->oauth_configured('google'),
            'microsoft' => $this->oauth_configured('microsoft'),
            'microsoft_personal' => $this->oauth_configured('microsoft_personal'),
            'dropbox' => $this->oauth_configured('dropbox'),
        ];
        toolbox_logger::step('cloud', 'settings_render');
        $markup = toolbox_cloud_view::render($oauth, [$this->plugin, 'gettext']);
        return ['content' => $markup, 'append_form_end' => true];
    }

    /**
     * Cloud forms use independent verified AJAX actions, not Toolbox Save.
     *
     * @param object $storage Existing Toolbox storage adapter, unused.
     * @return null Always prevent generic Settings saves for this section.
     */
    public function prepare_save($storage): ?array
    {
        return null;
    }

    /**
     * Show a provider connection card only when its entire OAuth configuration
     * is valid. Stored accounts are never removed when a provider is disabled.
     *
     * @param string $provider Internal OAuth provider identifier.
     * @return bool Whether this provider is available for new connections.
     */
    private function oauth_configured(string $provider): bool
    {
        try {
            switch ($provider) {
                case 'google':
                    toolbox_cloud_google_oauth::settings($this->rcube);
                    break;
                case 'microsoft':
                    toolbox_cloud_microsoft_oauth::settings($this->rcube);
                    break;
                case 'microsoft_personal':
                    toolbox_cloud_microsoft_oauth::settings($this->rcube, 'personal');
                    break;
                case 'dropbox':
                    toolbox_cloud_dropbox_oauth::settings($this->rcube);
                    break;
                default:
                    return false;
            }
            return true;
        } catch (RuntimeException $error) {
            // A provider can intentionally be unconfigured; not an error.
            toolbox_logger::step('cloud', 'provider_unavailable_' . $provider);
            return false;
        }
    }

    /** Complete a Google OAuth popup using the authenticated Roundcube session. */
    public function handle_google_callback(): void
    {
        (new toolbox_cloud_controller($this->rcube, [$this->plugin, 'gettext']))->handle_google_callback();
    }

    /** Complete a Microsoft OAuth popup using the authenticated Roundcube session. */
    public function handle_microsoft_callback(): void
    {
        (new toolbox_cloud_controller($this->rcube, [$this->plugin, 'gettext']))->handle_microsoft_callback();
    }

    /** Complete a Dropbox OAuth popup using the authenticated Roundcube session. */
    public function handle_dropbox_callback(): void
    {
        (new toolbox_cloud_controller($this->rcube, [$this->plugin, 'gettext']))->handle_dropbox_callback();
    }

    /** Process authenticated Cloud actions through the standalone controller. */
    public function handle_cloud_request(): void
    {
        (new toolbox_cloud_controller($this->rcube, [$this->plugin, 'gettext']))->handle();
    }
}

// END OF FILE
