<?php


$labels = array();
$messages = array();


/* TOOLBOX */
/* Author: Gianluca Giacometti */

$labels['toolbox'] = 'Toolbox';
$labels['toolbox-description'] = 'Set of tools to improve the webmail experience';
$labels['toolbox-usersettings'] = 'User settings';
$labels['toolbox-arialabeltoolform'] = 'Tool settings form';
$labels['toolbox-addresses'] = 'Addresses';
$labels['toolbox-deleteall'] = 'Delete all';
$labels['toolbox-enabled'] = 'Enabled';
$labels['toolbox-disabled'] = 'Disabled';

$messages['toolbox-driverconnecterror'] = 'Driver connection error';
$messages['toolbox-driverprocesserror'] = 'Driver execution error';
$messages['toolbox-driverdefaulterror'] = 'Unknown driver execution error';
$messages['toolbox-unknowntool'] = 'Unknown tool';
$messages['toolbox-internalerror'] = 'Unknown execution error';
$messages['toolbox-datasuccessfullysaved'] = 'Data successfully saved';
$messages['toolbox-datasaveerror'] = 'Error saving data';


/* Aliases */
/* Author: Gianluca Giacometti */

$labels['aliases'] = 'Aliases';
$labels['aliases-manage'] = 'Manage aliases';
$labels['aliases-aliasname'] = 'Alias';
$labels['aliases-addaddress'] = 'Add alias';

$messages['aliases-aliasexists'] = 'Alias already exists';
$messages['aliases-aliasexistsindomain'] = 'Alias already exists in domain';
$messages['aliases-aliasdeleteconfirm'] = 'Are you sure you want to delete this alias?';
$messages['aliases-aliasdeleted'] = 'Alias deleted successfully';
$messages['aliases-aliasdeletederror'] = 'Error while deleting alias';
$messages['aliases-aliascreated'] = 'Alias created successfully';
$messages['aliases-aliascreatederror'] = 'Error while creating alias';
$messages['aliases-aliasupdated'] = 'Alias updated successfully';
$messages['aliases-aliasupdatederror'] = 'Error while updating alias';
$messages['aliases-aliasnameerror'] = 'Alias contains characters not allowed';
$messages['aliases-noaliases'] = 'No aliases found';
$messages['aliases-novalidalias'] = 'Alias name empty';


/* Forward */
/* Author: Gianluca Giacometti */

$labels['forward'] = 'Forward addresses';
$labels['forward-manage'] = 'Manage forward addresses';
$labels['forward-address'] = 'Forward address';
$labels['forward-addaddress'] = 'Add forward address';
$labels['forward-keepcopies'] = 'Keep copies of original messages';

$messages['forward-invalidaddress'] = 'E-mail address not valid';
$messages['forward-atleastoneaddress'] = 'At least one forward address is required if no copy is kept in mailbox';
$messages['forward-emptyaddress'] = 'The forward address cannot be empty';
$messages['forward-deleteaddress'] = 'Do you really want to delete this address?';
$messages['forward-deletealladdresses'] = 'Delete all addresses';
$messages['forward-addressexists'] = 'This address already exists';
$messages['forward-noaddress'] = 'No address found';


/* Vacation */
/* Author: Gianluca Giacometti */

$labels['vacation'] = 'Out of office';
$labels['vacation-manage'] = 'Manage out of office message';
$labels['vacation-subject'] = 'Subject:';
$labels['vacation-body'] = 'Message:';
$labels['vacation-active'] = 'Active';
$labels['vacation-activefrom'] = 'Active from:';
$labels['vacation-activeuntil'] = 'Active until:';
$labels['vacation-intervaltime'] = 'Reply type:';
$labels['vacation-replyonce'] = 'Reply once to each address';
$labels['vacation-replytoeverymail'] = 'Reply to every message';
$labels['vacation-replyonceperday'] = 'Reply once per day to each address';
$labels['vacation-replyonceperweek'] = 'Reply once per week to each address';
$labels['vacation-htmleditor'] = 'Activate HTML editor';

$messages['vacation-invalidstartdate'] = 'Please select a valid start date.';
$messages['vacation-invalidenddate'] = 'Please select a valid end date.';
$messages['vacation-invaliddateinterval'] = 'The date interval is not valid.';
$messages['vacation-nosubject'] = 'Please fill the subject of your out-of-office.';
$messages['vacation-nomessage'] = 'Please fill the message of your out-of-office.';
$messages['vacation-driverdefaulterror'] = 'Unknown execution error';
$messages['vacation-driverconnecterror'] = 'Error during connection';
$messages['vacation-driverprocesserror'] = 'Error during processing';


/* Purge */
/* Author: Gianluca Giacometti */

$labels['purge'] = 'Purge';
$labels['purge-manage'] = 'Manage purge options';
$labels['purge-domainsettings'] = 'Domain settings';
$labels['purge-trash'] = 'Keep messages in Rubbish folder for';
$labels['purge-junk'] = 'Keep messages in Spam folder for';
$labels['purge-domainvalue'] = 'default domain value';
$labels['purge-always'] = 'ever';
$labels['purge-day'] = 'day';
$labels['purge-days'] = 'days';


/* Preview */
/* Author:  */

$labels['preview'] = 'Message preview';
$labels['preview-manage'] = 'Configure message preview';
$labels['preview-disable-message'] = 'Disable message preview';
$labels['preview-markasread-doubleclick'] = 'Mark a message read also by double clicking with mouse';


/* Customise */
/* Author: Gianluca Giacometti */

$labels['customise'] = 'Customise';
$labels['customise-manage'] = 'Customise layout for domain';
$labels['customise-purge'] = 'Purge';
$labels['customise-purge-trash'] = 'Keep messages in Rubbish folder for';
$labels['customise-purge-junk'] = 'Keep messages in Spam folder for';
$labels['customise-blankpage'] = 'Customised blank page';
$labels['customise-blankpage-skin'] = 'Blank page';
$labels['customise-blankpage-skin-default'] = 'Default Roundcube blank page';
$labels['customise-blankpage-skin-image'] = 'Blank page with just a centered image';
$labels['customise-blankpage-skin-url'] = 'Local or external page url';
$labels['customise-blankpage-skin-custom'] = 'Custom-made page';
$labels['customise-blankpage-image'] = 'Image';
$labels['customise-blankpage-url'] = 'Address';
$labels['customise-additional-css'] = 'Customised CSS';
$labels['customise-logo'] = 'Customised logo';
$labels['customise-logo-template'] = 'Template';
$labels['customise-logo-template-new'] = 'New customised logo';
$labels['customise-logo-template-all'] = 'All templates';
$labels['customise-logo-type'] = 'Type';
$labels['customise-logo-type-all'] = 'All types';
$labels['customise-logo-type-favicon'] = 'Favicon';
$labels['customise-logo-type-print'] = 'Print';
$labels['customise-logo-type-small'] = 'Small';
$labels['customise-logo-type-dark'] = 'Logo in dark mode';
$labels['customise-logo-type-small-dark'] = 'Small in dark mode';
$labels['customise-logo-add-template'] = 'Add template';
$labels['customise-logo-image'] = 'Image';

$messages['customise-logo-customisedlogoexists'] = 'Customised logo already exists';
$messages['customise-logo-deletecustomisedlogo'] = 'Do you really want to delete this customised logo?';
$messages['customise-logo-deleteallcustomisedlogos'] = 'Do you really want to delete all customised logos for this skin?';
$messages['customise-logo-emptycustomisedlogotemplate'] = 'The logo template cannot be empty';
$messages['customise-logo-emptycustomisedlogoimage'] = 'The logo image cannot be empty';
$messages['customise-logo-invalidcustomisedlogoimage'] = 'The logo image is invalid';
$messages['customise-logo-nocustomisedlogo'] = 'No customised logo found';


/* Detach Attachments */
/* Author: Gianluca Giacometti */

$labels['attachment-expiry-date'] = 'Download expires on';
$labels['attachment-maxuploadsize'] = 'NOTE: If one attachment is greater than $singlesize MB or the entire set of attachments is grater than $totalsize MB the recipient will receive a link to a zip file, which will expire in $lifespan days';


/* Tags */
/* Author: Gianluca Giacometti */

$labels['tags'] = 'Tags';
$labels['tags-label'] = 'Tag';
$labels['tags-labels'] = 'Tags';

$labels['tags-title'] = 'Personal tags';
$labels['tags-empty'] = 'No tags yet. Select New tag to create one.';
$labels['tags-newtag'] = 'New tag';
$labels['tags-showinlist'] = 'Show tags in the message list';
$labels['tags-listhelp'] = 'Tagged messages use an extra line in the list.';
$labels['tags-emptyvalues'] = 'No values. Select New value to add one.';
$labels['tags-newvalue'] = 'New value';
$labels['tags-editname'] = 'Edit name';
$labels['tags-deletename'] = 'Delete tag name';
$labels['tags-editvalue'] = 'Edit value';
$labels['tags-deletevalue'] = 'Delete value';
$labels['tags-name'] = 'Tag name';
$labels['tags-value'] = 'Value';
$labels['tags-color'] = 'Colour';
$labels['tags-choosecolor'] = 'Choose tag colour';
$labels['tags-save'] = 'Save';
$labels['tags-cancel'] = 'Cancel';
$labels['tags-newname'] = 'New tag name';
$labels['tags-editnametitle'] = 'Edit tag name';
$labels['tags-parentprefix'] = 'Tag: ';
$labels['tags-confirmdeletegroup'] = 'Permanently delete "{name}" and all its values?';
$labels['tags-confirmdeletevalue'] = 'Permanently delete "{value}" from "{name}"?';
$labels['tags-deleteirreversible'] = 'This cannot be undone.';
$labels['tags-unavailable'] = 'Tags are unavailable.';
$labels['tags-noneassigned'] = 'No tags assigned';
$labels['tags-listprefix'] = 'Tags: ';
$labels['tags-edit'] = 'Edit tags';
$labels['tags-editorfailed'] = 'The tag editor could not be loaded. Check your session, then reload the editor.';
$labels['tags-defssaving'] = 'Saving tag definitions…';
$labels['tags-definitions'] = 'Tag definitions';
$labels['tags-defsloading'] = 'Loading tag definitions…';
$labels['tags-opendefs'] = 'Open Tag definitions to create tag names and values.';
$labels['tags-loading'] = 'Loading tags…';
$labels['tags-saving'] = 'Saving tag…';
$labels['tags-saveunconfirmed'] = 'The change could not be confirmed. Reload tags before trying again.';
$labels['tags-loadfailed'] = 'Tags could not be loaded. Please retry.';
$labels['tags-updated'] = 'Tags updated.';
$labels['tags-updateunconfirmed'] = 'The tag change could not be confirmed.';
$labels['tags-reloadhint'] = 'Reload tags before trying again.';
$labels['tags-selectone'] = 'Select one message to edit its tags.';
$labels['tags-savepending'] = 'A tag change for this message is still being saved. Please wait.';
$labels['tags-options'] = 'Tag options';
$labels['tags-message'] = 'Message tags';
$labels['tags-reloadeditor'] = 'Reload editor';
$labels['tags-reload'] = 'Reload tags';
$labels['tags-defssavingclose'] = 'Saving tag definitions… The dialog will close when saving finishes.';

/* Tags backend errors */
/* Localised only at the user-facing response boundary. */
$messages['tags-error-invalidrequest'] = 'Invalid tag request';
$messages['tags-error-missingmessage'] = 'Message is not available in this mailbox';
$messages['tags-error-missingtag'] = 'This tag definition no longer exists';
$messages['tags-error-tagsnotallowed'] = 'This mailbox does not allow custom tags';
$messages['tags-error-updateunconfirmed'] = 'The tag update was not confirmed by the mail server';
$messages['tags-error-verifyfailed'] = 'Could not verify the updated message tag';
$messages['tags-error-invalidoperation'] = 'Invalid tag operation';
$messages['tags-error-unknownoperation'] = 'Unknown tag operation';
$messages['tags-error-listsavefailed'] = 'Could not save the message-list display option';
$messages['tags-error-invalidlistoption'] = 'Invalid message-list display option';
$messages['tags-error-invalidformvalue'] = 'Invalid tag form value';
$messages['tags-error-toomanynames'] = 'Too many tag names';
$messages['tags-error-unknownname'] = 'Unknown tag name';
$messages['tags-error-unknowntag'] = 'Unknown personal tag definition';
$messages['tags-error-toomanyvalues'] = 'Too many personal tag definitions';
$messages['tags-error-restorefirst'] = 'Restore the tag before editing it';
$messages['tags-error-invalidstore'] = 'Invalid personal tag definition store';
$messages['tags-error-corrupteddefinition'] = 'Corrupted personal tag definition';
$messages['tags-error-corruptedtext'] = 'Corrupted personal tag text';
$messages['tags-error-duplicategroupid'] = 'Duplicate tag group identifier';
$messages['tags-error-corruptedgroups'] = 'Corrupted tag groups';
$messages['tags-error-corruptedgroup'] = 'Corrupted tag group';
$messages['tags-error-corruptedgroupname'] = 'Corrupted tag group name';
$messages['tags-error-duplicategroupname'] = 'Duplicate tag group names';
$messages['tags-error-noparent'] = 'Tag value has no valid parent group';
$messages['tags-error-definitionssavefailed'] = 'Could not save personal tag definitions';
$messages['tags-error-inactive'] = 'Tag name is not active';
$messages['tags-error-nameexists'] = 'Tag name already exists';
$messages['tags-error-valueexists'] = 'Tag value already exists';
$messages['tags-error-invalidid'] = 'Invalid tag identifier';
$messages['tags-error-invalidname'] = 'Tag name must contain valid, nonempty text';
$messages['tags-error-invalidvalue'] = 'Tag value must contain valid, nonempty text';
$messages['tags-error-invalidcolor'] = 'Invalid tag color';
$messages['tags-error-unexpected'] = 'Could not complete the tag operation';

/* Cloud */
/* Author: Gianluca Giacometti */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Insert a link to a file';
$labels['cloud-insertfile'] = 'Attach a file';

/* Cloud Settings (PHP view) */
$labels['cloud-ui-generate-recovery'] = 'Generate a recovery key';
$labels['cloud-ui-create-title'] = 'Create your personal Cloud vault';
$labels['cloud-ui-create-explanation'] = 'Choose a vault password (at least 12 characters), a generated recovery key, or both. A recovery key is shown only once.';
$labels['cloud-ui-new-password-label'] = 'Vault password (optional if recovery key is selected)';
$labels['cloud-ui-confirm-password-label'] = 'Confirm vault password';
$labels['cloud-ui-create-vault'] = 'Create vault';
$labels['cloud-ui-locked'] = 'Locked';
$labels['cloud-ui-unlock-connections'] = 'Unlock the vault to manage connections.';
$labels['cloud-ui-reset-timer'] = 'Reset timer';
$labels['cloud-ui-lock-now'] = 'Lock now';
$labels['cloud-ui-vault-password'] = 'Vault password';
$labels['cloud-ui-recovery-key'] = 'Recovery key';
$labels['cloud-ui-set-change-password'] = 'Set or change password';
$labels['cloud-ui-generate-new-recovery'] = 'Generate a new recovery key';
$labels['cloud-ui-vault-security'] = 'Vault security';
$labels['cloud-ui-change-security-help'] = 'Change password or recovery key';
$labels['cloud-ui-new-vault-password'] = 'New vault password';
$labels['cloud-ui-confirm-new-password'] = 'Confirm new vault password';
$labels['cloud-ui-rotate-warning'] = 'Generating a new recovery key permanently invalidates the previous recovery key.';
$labels['cloud-ui-personal-vault'] = 'Personal vault';
$labels['cloud-ui-unlock-info'] = 'Unlock once to manage Cloud connections. The server relocks after 5 minutes without Cloud activity or 15 minutes after the last unlock or manual timer reset. Click Reset timer while unlocked to renew both limits, or Lock now to lock immediately. The unlocked key is held temporarily in your authenticated server session.';
$labels['cloud-ui-unlock-method'] = 'Unlock method';
$labels['cloud-ui-unlock-secret'] = 'Vault password / recovery key';
$labels['cloud-ui-unlock-vault'] = 'Unlock vault';
$labels['cloud-ui-nextcloud-description'] = 'Connect with your Nextcloud WebDAV URL and app password.';
$labels['cloud-ui-connect-account'] = 'Connect account';
$labels['cloud-ui-owncloud-description'] = 'Connect with your ownCloud WebDAV URL and credentials.';
$labels['cloud-ui-generic-webdav'] = 'Generic WebDAV';
$labels['cloud-ui-webdav-description'] = 'Connect any supported public HTTPS WebDAV endpoint.';
$labels['cloud-ui-google-description'] = 'Authorize read-only access to your Drive with Google.';
$labels['cloud-ui-connect-google'] = 'Connect Google account';
$labels['cloud-ui-google-aria'] = 'Connect Google Drive account';
$labels['cloud-ui-onedrive-business'] = 'OneDrive Business';
$labels['cloud-ui-onedrive-business-description'] = 'Authorize read-only access to your work or school OneDrive.';
$labels['cloud-ui-connect-business'] = 'Connect business account';
$labels['cloud-ui-onedrive-business-aria'] = 'Connect OneDrive Business account';
$labels['cloud-ui-onedrive-personal'] = 'OneDrive Personal';
$labels['cloud-ui-onedrive-personal-description'] = 'Authorize read-only access to your personal Microsoft OneDrive.';
$labels['cloud-ui-connect-personal'] = 'Connect personal account';
$labels['cloud-ui-onedrive-personal-aria'] = 'Connect OneDrive Personal account';
$labels['cloud-ui-dropbox-description'] = 'Authorize read-only access to your Dropbox account.';
$labels['cloud-ui-connect-dropbox'] = 'Connect Dropbox account';
$labels['cloud-ui-cloud-connections'] = 'Cloud connections';
$labels['cloud-ui-choose-provider'] = 'Choose a provider. WebDAV uses app credentials; OAuth providers require administrator configuration.';
$labels['cloud-ui-connected-accounts'] = 'Connected accounts';
$labels['cloud-ui-unlock-to-list'] = 'Unlock the vault to list accounts.';
$labels['cloud-ui-connect-cloud-account'] = 'Connect cloud account';
$labels['cloud-ui-close'] = 'Close';
$labels['cloud-ui-close-connection'] = 'Close connection dialog';
$labels['cloud-ui-account-name'] = 'Account name';
$labels['cloud-ui-my-cloud-account'] = 'My cloud account';
$labels['cloud-ui-public-webdav-url'] = 'Full public HTTPS WebDAV URL';
$labels['cloud-ui-webdav-username'] = 'WebDAV username';
$labels['cloud-ui-webdav-password'] = 'WebDAV app password / password';
$labels['cloud-ui-connection-needs-unlock'] = 'The Cloud vault must be unlocked before connecting an account.';
$labels['cloud-ui-add-account'] = 'Add account';
$labels['cloud-ui-browse-files'] = 'Browse Cloud files';
$labels['cloud-ui-browse-explanation'] = 'Browse folders and inspect available files. To attach files to a message, use Attach from Cloud in Compose.';
$labels['cloud-ui-up-folder'] = 'Up one folder';
$labels['cloud-ui-next-entries'] = 'Next 100 entries';
$labels['cloud-ui-save-recovery-title'] = 'Save your recovery key now';
$labels['cloud-ui-recovery-once-help'] = 'The recovery key cannot be shown again. Copy it to a safe place outside this webmail account.';
$labels['cloud-ui-recovery-saved'] = 'I\'ve saved it — hide key';


/* Cloud JavaScript interface */
/* Author: Gianluca Giacometti */

$labels['cloud-ui-unlocked'] = 'Unlocked';
$labels['cloud-ui-automatic-lock-in'] = 'Automatic lock in %s';
$labels['cloud-ui-unlock-expired'] = 'Unlock expired.';
$labels['cloud-ui-request-in-progress'] = 'A Cloud operation is already in progress.';
$labels['cloud-ui-processing-request'] = 'Processing Cloud request…';
$labels['cloud-ui-request-send-failed'] = 'Cloud request could not be sent.';
$labels['cloud-ui-unlock-secret-required'] = 'Enter the vault password or recovery key for this operation.';
$labels['cloud-ui-paste-recovery'] = 'Paste your recovery key';
$labels['cloud-ui-enter-vault-password'] = 'Enter your vault password';
$labels['cloud-ui-nextcloud-webdav-help'] = 'Use the WebDAV address from Nextcloud Files settings.';
$labels['cloud-ui-owncloud-webdav-help'] = 'Use the WebDAV address shown by your ownCloud installation.';
$labels['cloud-ui-webdav-url-help'] = 'Enter the full public HTTPS URL to your WebDAV folder.';
$labels['cloud-ui-dialog-unsupported'] = 'Your browser does not support the cloud connection dialog.';
$labels['cloud-ui-unlock-cloud-first'] = 'Unlock your Cloud vault first.';
$labels['cloud-ui-connect-named'] = 'Connect %s';
$labels['cloud-ui-my-named'] = 'My %s';
$labels['cloud-ui-account-unavailable'] = 'Cloud account is no longer available. Refresh the list.';
$labels['cloud-ui-edit-named'] = 'Edit %s';
$labels['cloud-ui-cloud-account-default'] = 'cloud account';
$labels['cloud-ui-cloud-account-title'] = 'Cloud account';
$labels['cloud-ui-edit-webdav-help'] = 'Update your WebDAV connection. Saved credentials are never shown.';
$labels['cloud-ui-keep-username'] = 'Leave blank to keep current username';
$labels['cloud-ui-keep-app-password'] = 'Leave blank to keep current app password';
$labels['cloud-ui-keep-credentials-help'] = 'Leave username or password empty to keep its stored value.';
$labels['cloud-ui-save-changes'] = 'Save changes';
$labels['cloud-ui-unlock-before-browse'] = 'Unlock the vault before browsing files.';
$labels['cloud-ui-my-drive'] = 'My Drive';
$labels['cloud-ui-loading-folder'] = 'Loading folder…';
$labels['cloud-ui-folder-list-failed-start'] = 'Unable to start folder listing.';
$labels['cloud-ui-select-valid-account'] = 'Unlock the vault and select a valid WebDAV account.';
$labels['cloud-ui-browse-files-named'] = 'Browse files: %s';
$labels['cloud-ui-folder-empty'] = 'This folder is empty.';
$labels['cloud-ui-bytes'] = 'bytes';
$labels['cloud-ui-no-cloud-accounts'] = 'No cloud accounts connected.';
$labels['cloud-ui-browse-cloud-files'] = 'Browse cloud files';
$labels['cloud-ui-edit-cloud-account'] = 'Edit cloud account';
$labels['cloud-ui-edit-account'] = 'Edit account';
$labels['cloud-ui-delete-cloud-account'] = 'Delete cloud account';
$labels['cloud-ui-delete-account'] = 'Delete account';
$labels['cloud-ui-folder-list-failed'] = 'Folder listing failed. See the Roundcube error toast.';
$labels['cloud-ui-operation-failed'] = 'Cloud operation failed.';
$labels['cloud-ui-vault-unlocked'] = 'Cloud vault unlocked.';
$labels['cloud-ui-vault-locked'] = 'Cloud vault locked.';
$labels['cloud-ui-timer-reset'] = 'Vault timer reset.';
$labels['cloud-ui-vault-created'] = 'Vault created. Save your recovery key if one was generated.';
$labels['cloud-ui-google-open-failed'] = 'Could not open Google authorization. Enable popups and try again.';
$labels['cloud-ui-microsoft-open-failed'] = 'Could not open Microsoft authorization. Enable popups and try again.';
$labels['cloud-ui-personal-open-failed'] = 'Could not open OneDrive Personal authorization. Enable popups and try again.';
$labels['cloud-ui-dropbox-open-failed'] = 'Could not open Dropbox authorization. Enable popups and try again.';
$labels['cloud-ui-accounts-loaded'] = 'Cloud accounts loaded.';
$labels['cloud-ui-account-added'] = 'Cloud account added.';
$labels['cloud-ui-account-updated'] = 'Cloud account updated.';
$labels['cloud-ui-account-removed'] = 'Cloud account removed.';
$labels['cloud-ui-vault-password-updated'] = 'Vault password updated.';
$labels['cloud-ui-recovery-changed'] = 'Recovery key changed. The previous recovery key no longer works.';
$labels['cloud-ui-vault-password-mismatch'] = 'Vault password and confirmation do not match.';
$labels['cloud-ui-vault-password-length'] = 'Vault password must contain at least 12 characters.';
$labels['cloud-ui-vault-method-required'] = 'Choose a vault password, a recovery key, or both.';
$labels['cloud-ui-google-popups'] = 'Allow popups for this webmail site to connect Google Drive.';
$labels['cloud-ui-google-connected'] = 'Google Drive connected.';
$labels['cloud-ui-google-auth-failed'] = 'Google Drive authorization failed or the vault expired.';
$labels['cloud-ui-onedrive-popups'] = 'Allow popups for this webmail site to connect OneDrive.';
$labels['cloud-ui-onedrive-connected'] = 'Microsoft OneDrive connected.';
$labels['cloud-ui-onedrive-auth-failed'] = 'Microsoft OneDrive authorization failed or the vault expired.';
$labels['cloud-ui-personal-popups'] = 'Allow popups for this webmail site to connect OneDrive Personal.';
$labels['cloud-ui-personal-connected'] = 'OneDrive Personal connected.';
$labels['cloud-ui-personal-auth-failed'] = 'OneDrive Personal authorization failed or vault expired.';
$labels['cloud-ui-dropbox-popups'] = 'Allow popups for this webmail site to connect Dropbox.';
$labels['cloud-ui-dropbox-connected'] = 'Dropbox connected.';
$labels['cloud-ui-dropbox-auth-failed'] = 'Dropbox authorization failed or the vault expired.';
$labels['cloud-ui-unlock-first'] = 'Unlock the vault first.';
$labels['cloud-ui-choose-provider-first'] = 'Choose a cloud provider first.';
$labels['cloud-ui-account-edit-required'] = 'Provide an account name and public HTTPS WebDAV URL.';
$labels['cloud-ui-account-new-required'] = 'Provide an account name, public HTTPS WebDAV URL, username and app password.';
$labels['cloud-ui-remove-account-confirm'] = 'Remove this cloud account?';
$labels['cloud-ui-new-password-mismatch'] = 'New vault password and confirmation do not match.';
$labels['cloud-ui-new-password-length'] = 'New vault password must contain at least 12 characters.';
$labels['cloud-ui-rotate-recovery-confirm'] = 'Generate a new recovery key? Your previous recovery key will stop working.';
$labels['cloud-ui-compose-send-failed'] = 'Unable to send the Cloud request.';
$labels['cloud-ui-compose-choose-account'] = 'Choose a cloud account';
$labels['cloud-ui-compose-select-account'] = 'Select an account to browse.';
$labels['cloud-ui-compose-no-accounts'] = 'No connected accounts. Add one in Toolbox Settings.';
$labels['cloud-ui-compose-attach'] = 'Attach';
$labels['cloud-ui-compose-attach-named'] = 'Attach %s';
$labels['cloud-ui-compose-invalid-id'] = 'Cloud file was saved, but the returned attachment ID is invalid. Refresh the draft.';
$labels['cloud-ui-compose-attachment-default'] = 'Cloud attachment';
$labels['cloud-ui-compose-remove-attachment'] = 'Remove attachment';
$labels['cloud-ui-compose-delete'] = 'Delete';
$labels['cloud-ui-compose-attached'] = 'Attached %s from Cloud.';
$labels['cloud-ui-compose-request-failed'] = 'Cloud request failed.';
$labels['cloud-ui-compose-create-vault'] = 'Create your Cloud vault in Toolbox Settings first.';
$labels['cloud-ui-compose-attach-from-cloud'] = 'Attach from Cloud';
$labels['cloud-ui-compose-unlock-help'] = 'Unlock your Cloud vault to attach files.';
$labels['cloud-ui-compose-secret-label'] = 'Password or recovery key';
$labels['cloud-ui-compose-account'] = 'Cloud account';
$labels['cloud-ui-compose-picker-unsupported'] = 'Your browser does not support the Cloud picker.';
$labels['cloud-ui-compose-secret-required'] = 'Enter your vault password or recovery key.';
$labels['cloud-ui-compose-importing'] = 'Importing Cloud attachment…';

/* Safe login */
/* Author: Gianluca Giacometti */

$labels['safelogin'] = 'Safe login';
$labels['safelogin-continent'] = 'Continent';
$labels['safelogin-countryisocode'] = 'Country Code';
$labels['safelogin-countryname'] = 'Country';
$labels['safelogin-cityname'] = 'City';
$labels['safelogin-location'] = 'Location';
$labels['safelogin-lastlogin'] = 'Last login';
$labels['safelogin-history'] = 'Access history';
$labels['safelogin-browser'] = 'Browser';
$labels['safelogin-os'] = 'Operating system';


/* Folders */
/* Author: Gianluca Giacometti */

$labels['folders'] = 'Riepilogo cartelle';
$labels['folders-numberoffiles'] = 'Numero file';

/* Cloud backend responses: translated only at user-facing boundaries */
$labels['cloud-backend-unlock-failed'] = 'Unable to unlock the vault. Check your password or recovery key.';
$labels['cloud-backend-operation-failed'] = 'Cloud operation failed. Check the details and server logs.';
$labels['cloud-backend-google-success'] = 'Google Drive account connected. You may close this window.';
$labels['cloud-backend-google-failed'] = 'Google Drive connection failed or the vault unlock expired. You may close this window.';
$labels['cloud-backend-business-success'] = 'OneDrive Business account connected. You may close this window.';
$labels['cloud-backend-business-failed'] = 'OneDrive Business authorization failed or vault unlock expired. You may close this window.';
$labels['cloud-backend-personal-success'] = 'OneDrive Personal account connected. You may close this window.';
$labels['cloud-backend-personal-failed'] = 'OneDrive Personal authorization failed or vault unlock expired. You may close this window.';
$labels['cloud-backend-dropbox-success'] = 'Dropbox account connected. You may close this window.';
$labels['cloud-backend-dropbox-failed'] = 'Dropbox authorization failed or the vault unlock expired. You may close this window.';

// END OF FILE

