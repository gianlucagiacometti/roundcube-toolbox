<?php

/**
 * Roundcube Toolbox Cloud Settings view.
 *
 * Generates the existing Cloud controls with Roundcube's HTML helpers. This
 * class deliberately owns presentation only: Cloud actions, OAuth callbacks,
 * session handling, and storage remain in their current modules.
 */
final class toolbox_cloud_view
{
    /** @var callable(string): string Localisation callback provided by the Toolbox plugin. */
    private $translator;

    /**
     * Create a renderer bound to the current user's Roundcube language.
     *
     * @param callable $translator Toolbox's gettext callback.
     */
    private function __construct(callable $translator)
    {
        $this->translator = $translator;
    }

    /**
     * Retrieve a translated Cloud Settings label.
     *
     * @param string $key Cloud label suffix.
     * @return string Translated label.
     */
    private function label(string $key): string
    {
        return ($this->translator)('cloud-ui-' . $key);
    }

    /**
     * Build the complete Cloud Settings fragment for the Toolbox form.
     *
     * @param array $oauth Availability flags keyed by OAuth provider ID.
     * @param callable $translator Toolbox gettext callback.
     * @return string Cloud Settings HTML.
     */
    public static function render(array $oauth, callable $translator): string
    {
        $view = new self($translator);
        return html::tag('div', [
            'id' => 'toolbox-cloud-settings',
            'class' => 'toolbox-cloud-settings',
        ], $view->create_vault()
            . $view->existing_vault($oauth)
            . $view->connection_dialog()
            . $view->browse_dialog()
            . $view->recovery_key());
    }

    /**
     * Create an action button without wiring it to Roundcube's command system.
     * Cloud's JavaScript already binds these buttons by their stable IDs.
     *
     * @param string $id Existing DOM ID.
     * @param string $label Visible action label.
     * @param string $style Existing Elastic button class suffix.
     * @param array $extra Additional HTML attributes.
     * @return string Button HTML.
     */
    private function button(string $id, string $label, string $style = 'secondary', array $extra = []): string
    {
        return html::tag('button', array_merge([
            'id' => $id,
            'type' => 'button',
            'class' => 'button btn btn-' . $style,
        ], $extra), rcmail::Q($label));
    }

    /**
     * Create a labelled input with the same IDs and attributes as the old form.
     *
     * @param string $id Existing DOM ID.
     * @param string $label Field label.
     * @param string $type HTML input type.
     * @param array $attributes Additional input attributes.
     * @param bool $lineBreak Whether the original field contained a break.
     * @return string Paragraph containing the label and input.
     */
    private function input(string $id, string $label, string $type, array $attributes = [], bool $lineBreak = false): string
    {
        $attrs = array_merge(['id' => $id, 'type' => $type], $attributes);
        $content = html::label($id, rcmail::Q($label))
            . ($lineBreak ? html::tag('br', null) : '')
            . html::tag('input', $attrs);

        return html::tag('p', null, $content);
    }

    /**
     * Build the new-vault controls; preserve IDs for Cloud's JavaScript.
     *
     * @return string Hidden new-vault section.
     */
    private function create_vault(): string
    {
        $recovery = html::tag('label', null,
            html::tag('input', [
                'id' => 'tbx-cloud-create-recovery',
                'type' => 'checkbox',
                'checked' => 'checked',
            ]) . ' ' . rcmail::Q($this->label('generate-recovery')));

        return html::tag('section', ['id' => 'tbx-cloud-create', 'hidden' => 'hidden'],
            html::tag('h3', null, rcmail::Q($this->label('create-title')))
            . html::tag('p', null, rcmail::Q($this->label('create-explanation')))
            . $this->input('tbx-cloud-new-password', $this->label('new-password-label'), 'password', [
                'autocomplete' => 'new-password',
                'maxlength' => '4096',
                'class' => 'form-control',
            ], true)
            . $this->input('tbx-cloud-confirm-password', $this->label('confirm-password-label'), 'password', [
                'autocomplete' => 'new-password',
                'maxlength' => '4096',
                'class' => 'form-control',
            ], true)
            . html::tag('p', null, $recovery)
            . $this->button('tbx-cloud-create-button', $this->label('create-vault'), 'primary'));
    }

    /**
     * Build the unlocked-vault section and provider connections panel.
     *
     * @param array $oauth OAuth availability flags.
     * @return string Existing-vault section.
     */
    private function existing_vault(array $oauth): string
    {
        return html::tag('section', ['id' => 'tbx-cloud-existing', 'hidden' => 'hidden'],
            $this->vault_panel() . $this->connections_panel($oauth));
    }

    /**
     * Build lock, unlock, and vault key-management controls.
     *
     * @return string Vault panel HTML.
     */
    private function vault_panel(): string
    {
        $lockRow = html::tag('div', ['class' => 'tbx-cloud-lock-row'],
            html::tag('strong', [
                'id' => 'tbx-cloud-lock-status',
                'class' => 'tbx-cloud-lock-status',
                'role' => 'status',
                'aria-live' => 'polite',
            ], rcmail::Q($this->label('locked')))
            . html::tag('span', ['id' => 'tbx-cloud-lock-expiry'], rcmail::Q($this->label('unlock-connections')))
            . $this->button('tbx-cloud-renew', $this->label('reset-timer'), 'secondary', ['hidden' => 'hidden'])
            . $this->button('tbx-cloud-lock', $this->label('lock-now'), 'secondary', ['hidden' => 'hidden']));

        $unlockMethod = html::tag('select', [
            'id' => 'tbx-cloud-unlock-method',
            'class' => 'form-control',
        ], html::tag('option', ['value' => 'password'], rcmail::Q($this->label('vault-password')))
            . html::tag('option', ['value' => 'recovery'], rcmail::Q($this->label('recovery-key'))));

        $securityActions = html::tag('div', ['class' => 'tbx-cloud-security-actions'],
            $this->button('tbx-cloud-change-password', $this->label('set-change-password'))
            . $this->button('tbx-cloud-rotate-recovery', $this->label('generate-new-recovery')));

        $security = html::tag('details', ['class' => 'tbx-cloud-security-card'],
            html::tag('summary', null, rcmail::Q($this->label('vault-security')) . ' '
                . html::tag('span', null, rcmail::Q($this->label('change-security-help'))))
            . $this->input('tbx-cloud-replacement-password', $this->label('new-vault-password'), 'password', [
                'autocomplete' => 'new-password',
                'maxlength' => '4096',
                'class' => 'form-control',
            ], true)
            . $this->input('tbx-cloud-replacement-confirm', $this->label('confirm-new-password'), 'password', [
                'autocomplete' => 'new-password',
                'maxlength' => '4096',
                'class' => 'form-control',
            ], true)
            . $securityActions
            . html::tag('p', null, rcmail::Q($this->label('rotate-warning'))));

        return html::tag('section', [
            'class' => 'tbx-cloud-vault-panel',
            'aria-labelledby' => 'tbx-cloud-vault-title',
        ], html::tag('h3', ['id' => 'tbx-cloud-vault-title'], rcmail::Q($this->label('personal-vault')))
            . $lockRow
            . html::tag('p', null, rcmail::Q($this->label('unlock-info')))
            . html::tag('p', null,
                html::label('tbx-cloud-unlock-method', rcmail::Q($this->label('unlock-method')))
                . html::tag('br', null) . $unlockMethod)
            . $this->input('tbx-cloud-unlock-secret', $this->label('unlock-secret'), 'password', [
                'autocomplete' => 'off',
                'maxlength' => '4096',
                'class' => 'form-control',
            ], true)
            . $this->button('tbx-cloud-refresh', $this->label('unlock-vault'), 'primary')
            . $security);
    }

    /**
     * Build the provider selection and connected-account container.
     *
     * @param array $oauth OAuth availability flags.
     * @return string Connections panel HTML.
     */
    private function connections_panel(array $oauth): string
    {
        $cards = $this->provider_card('Nextcloud',
            $this->label('nextcloud-description'), $this->label('connect-account'),
            $this->provider_icon('nextcloud'), ['data-cloud-provider' => 'nextcloud', 'aria-haspopup' => 'dialog'])
            . $this->provider_card('ownCloud',
                $this->label('owncloud-description'), $this->label('connect-account'),
                $this->provider_icon('owncloud'), ['data-cloud-provider' => 'owncloud', 'aria-haspopup' => 'dialog'])
            . $this->provider_card($this->label('generic-webdav'),
                $this->label('webdav-description'), $this->label('connect-account'),
                $this->provider_icon('webdav'), ['data-cloud-provider' => 'webdav', 'aria-haspopup' => 'dialog']);

        $oauthProviders = [
            'google' => [
                'Google Drive',
                $this->label('google-description'),
                $this->label('connect-google'),
                'data-cloud-google',
                $this->label('google-aria')
            ],
            'microsoft' => [
                $this->label('onedrive-business'),
                $this->label('onedrive-business-description'),
                $this->label('connect-business'),
                'data-cloud-microsoft',
                $this->label('onedrive-business-aria')
            ],
            'microsoft_personal' => [
                $this->label('onedrive-personal'),
                $this->label('onedrive-personal-description'),
                $this->label('connect-personal'),
                'data-cloud-microsoft-personal',
                $this->label('onedrive-personal-aria')
            ],
            'dropbox' => [
                'Dropbox',
                $this->label('dropbox-description'),
                $this->label('connect-dropbox'),
                'data-cloud-dropbox',
                $this->label('connect-dropbox')
            ],
        ];
        foreach ($oauthProviders as $provider => $info) {
            if (!empty($oauth[$provider])) {
                $cards .= $this->provider_card($info[0], $info[1], $info[2],
                    $this->provider_icon($provider), [$info[3] => '1', 'aria-label' => $info[4]]);
            }
        }

        return html::tag('section', [
            'class' => 'tbx-cloud-connections-panel',
            'aria-labelledby' => 'tbx-cloud-connections-title',
        ], html::tag('h3', ['id' => 'tbx-cloud-connections-title'], rcmail::Q($this->label('cloud-connections')))
            . html::tag('p', null, rcmail::Q($this->label('choose-provider')))
            . html::tag('div', ['class' => 'tbx-cloud-provider-grid'], $cards)
            . html::tag('h4', null, rcmail::Q($this->label('connected-accounts')))
            . html::tag('div', [
                'id' => 'tbx-cloud-accounts',
                'aria-live' => 'polite',
            ], rcmail::Q($this->label('unlock-to-list'))));
    }

    /**
     * Construct a provider card with its unchanged dataset attributes.
     *
     * @param string $name Visible provider name.
     * @param string $description Card description.
     * @param string $action Action label.
     * @param string $icon Static provider icon markup.
     * @param array $attributes Existing card-specific dataset and ARIA attributes.
     * @return string Provider button HTML.
     */
    private function provider_card(string $name, string $description, string $action, string $icon, array $attributes): string
    {
        return html::tag('button', array_merge([
            'type' => 'button',
            'class' => 'tbx-cloud-provider-card',
        ], $attributes), $icon
            . html::tag('span', ['class' => 'tbx-cloud-provider-name'], rcmail::Q($name))
            . html::tag('span', ['class' => 'tbx-cloud-provider-description'], rcmail::Q($description))
            . html::tag('span', ['class' => 'tbx-cloud-provider-action'], rcmail::Q($action)));
    }

    /**
     * Generate provider SVG icons using HTML element/attribute escaping helpers.
     * The path geometry and colors remain identical to the existing controls.
     *
     * @param string $provider Provider icon identifier.
     * @return string SVG markup.
     */
    private function provider_icon(string $provider): string
    {
        $attributes = [
            'class' => 'tbx-cloud-provider-icon',
            'viewBox' => '0 0 64 64',
            'aria-hidden' => 'true',
        ];
        switch ($provider) {
            case 'nextcloud':
                $attributes += ['fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '4'];
                $shapes = html::tag('circle', ['cx' => '32', 'cy' => '32', 'r' => '13'], '')
                    . html::tag('circle', ['cx' => '11', 'cy' => '32', 'r' => '6'], '')
                    . html::tag('circle', ['cx' => '53', 'cy' => '32', 'r' => '6'], '')
                    . html::tag('path', ['d' => 'M17 32h2m26 0h2'], '');
                break;
            case 'owncloud':
                $attributes += [
                    'fill' => 'none',
                    'stroke' => 'currentColor',
                    'stroke-width' => '3.5',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round'
                ];
                $shapes = html::tag('path', ['d' => 'M17 46h31a11 11 0 0 0 0-22 17 17 0 0 0-32-2 12 12 0 0 0 1 24Z'], '')
                    . html::tag('path', ['d' => 'M25 35h14m-7-7v14'], '');
                break;
            case 'webdav':
                $attributes += [
                    'fill' => 'none',
                    'stroke' => 'currentColor',
                    'stroke-width' => '3.5',
                    'stroke-linecap' => 'round',
                    'stroke-linejoin' => 'round'
                ];
                $shapes = html::tag('path', ['d' => 'M7 20h19l6 7h25v25H7Z'], '')
                    . html::tag('path', ['d' => 'M7 27V14h22l6 6'], '')
                    . html::tag('path', ['d' => 'M25 40h14m-5-5 5 5-5 5'], '');
                break;
            case 'google':
                $attributes += ['fill' => 'none'];
                $shapes = html::tag('path', ['d' => 'M24 8h15l21 36H45Z', 'fill' => '#ffba00'], '')
                    . html::tag('path', ['d' => 'M24 8h15L18 44H3Z', 'fill' => '#00875a'], '')
                    . html::tag('path', ['d' => 'M18 44h42l-8 12H10Z', 'fill' => '#2684fc'], '');
                break;
            case 'microsoft':
            case 'microsoft_personal':
                $attributes += ['fill' => 'none'];
                $shapes = html::tag('path', ['d' => 'M26 17c5-9 20-10 27-1l-1 11c7 1 10 7 8 14H15c-8-10-2-23 11-24Z', 'fill' => '#0364b8'], '')
                    . html::tag('path', ['d' => 'M12 29c4-9 18-10 23-1l13 13H9C4 37 6 31 12 29Z', 'fill' => '#1490df'], '')
                    . html::tag('path', ['d' => 'M16 40c4-8 15-9 20-2h17c10 0 10 15 0 15H16c-10 0-10-13 0-13Z', 'fill' => '#28a8ea'], '');
                break;
            case 'dropbox':
                $attributes += ['fill' => 'currentColor'];
                $shapes = html::tag('path', ['d' => 'M17 8 3 18l14 10 14-10L17 8Zm30 0L33 18l14 10 14-10L47 8ZM3 38l14 10 14-10-14-10L3 38Zm44-10L33 38l14 10 14-10-14-10ZM17 51l15 9 15-9-15-10-15 10Z'], '');
                break;
            default:
                return '';
        }
        return html::tag('svg', $attributes, $shapes);
    }

    /**
     * Build the WebDAV account connection dialog.
     *
     * @return string Connection dialog HTML.
     */
    private function connection_dialog(): string
    {
        $header = html::tag('div', ['class' => 'tbx-cloud-dialog-header'],
            html::tag('h3', ['id' => 'tbx-cloud-provider-dialog-title'], rcmail::Q($this->label('connect-cloud-account')))
            . $this->button('tbx-cloud-close-dialog', $this->label('close'), 'secondary', [
                'aria-label' => $this->label('close-connection'),
            ]));

        return html::tag('dialog', [
            'id' => 'tbx-cloud-provider-dialog',
            'class' => 'tbx-cloud-provider-dialog',
            'aria-labelledby' => 'tbx-cloud-provider-dialog-title',
        ], $header
            . html::tag('p', ['id' => 'tbx-cloud-provider-dialog-help'], '')
            . $this->input('tbx-cloud-name', $this->label('account-name'), 'text', [
                'maxlength' => '255',
                'class' => 'form-control',
                'placeholder' => $this->label('my-cloud-account'),
            ])
            . $this->input('tbx-cloud-endpoint', $this->label('public-webdav-url'), 'url', [
                'maxlength' => '2048',
                'class' => 'form-control',
                'placeholder' => 'https://cloud.example.org/remote.php/dav/files/USERNAME/',
            ])
            . $this->input('tbx-cloud-username', $this->label('webdav-username'), 'text', [
                'maxlength' => '255',
                'autocomplete' => 'off',
                'class' => 'form-control',
            ])
            . $this->input('tbx-cloud-app-password', $this->label('webdav-password'), 'password', [
                'maxlength' => '4096',
                'autocomplete' => 'off',
                'class' => 'form-control',
            ])
            . html::tag('p', ['id' => 'tbx-cloud-credential-guidance'], rcmail::Q($this->label('connection-needs-unlock')))
            . $this->button('tbx-cloud-add', $this->label('add-account'), 'primary'));
    }

    /**
     * Build the existing Cloud file browser dialog.
     *
     * @return string Browser dialog HTML.
     */
    private function browse_dialog(): string
    {
        return html::tag('dialog', [
            'id' => 'tbx-cloud-browse-dialog',
            'class' => 'tbx-cloud-provider-dialog tbx-cloud-browse-dialog',
            'aria-labelledby' => 'tbx-cloud-browse-title',
        ], html::tag('div', ['class' => 'tbx-cloud-dialog-header'],
                html::tag('h3', ['id' => 'tbx-cloud-browse-title'], rcmail::Q($this->label('browse-files')))
                . $this->button('tbx-cloud-close-browse', $this->label('close')))
            . html::tag('p', ['class' => 'tbx-cloud-browse-note'],
                rcmail::Q($this->label('browse-explanation')))
            . html::tag('div', ['class' => 'tbx-cloud-browse-toolbar'],
                $this->button('tbx-cloud-browse-up', $this->label('up-folder'))
                . html::tag('span', ['id' => 'tbx-cloud-browse-path', 'class' => 'tbx-cloud-browse-path'], '/'))
            . html::tag('div', [
                'id' => 'tbx-cloud-browse-results',
                'class' => 'tbx-cloud-browse-results',
                'aria-live' => 'polite',
            ], '')
            . $this->button('tbx-cloud-browse-next', $this->label('next-entries'), 'secondary', ['hidden' => 'hidden']));
    }

    /**
     * Build the one-time recovery-key display.
     *
     * @return string Hidden recovery-key panel HTML.
     */
    private function recovery_key(): string
    {
        return html::tag('section', ['id' => 'tbx-cloud-recovery', 'hidden' => 'hidden'],
            html::tag('h3', null, rcmail::Q($this->label('save-recovery-title')))
            . html::tag('p', null, rcmail::Q($this->label('recovery-once-help')))
            . html::tag('textarea', [
                'id' => 'tbx-cloud-recovery-value',
                'class' => 'form-control',
                'rows' => '3',
                'readonly' => 'readonly',
                'autocomplete' => 'off',
                'spellcheck' => 'false',
            ], '')
            . html::tag('p', null, $this->button('tbx-cloud-hide-recovery', $this->label('recovery-saved'))));
    }
}

// END OF FILE
