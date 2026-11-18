<?php


$labels = array();
$messages = array();


/* TOOLBOX */
/* Author:  */

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
$messages['toolbox-datasuccessfullysaved'] = 'Daten erfolgreich gespeichert';
$messages['toolbox-datasaveerror'] = 'Error saving data';


/* Aliases */
/* Author: Stephan Blanke */

$labels['aliases'] = 'Aliase';
$labels['aliases-manage'] = 'Verwalte Aliase';
$labels['aliases-aliasname'] = 'Alias';
$labels['aliases-addaddress'] = 'Add Alias';

$messages['aliases-aliasexists'] = 'Alias existiert bereits';
$messages['aliases-aliasexistsindomain'] = 'Alias existiert bereits in Domäne';
$messages['aliases-aliasdeleteconfirm'] = 'Sind Sie sicher, dass Sie diesen Alias löschen wollen?';
$messages['aliases-aliasdeleted'] = 'Alias erfolgreich gelöscht';
$messages['aliases-aliasdeletederror'] = 'Alias konnte nicht gelöscht werden';
$messages['aliases-aliascreated'] = 'Alias erfolgreich angelegt';
$messages['aliases-aliascreatederror'] = 'Alias konnte nicht angelegt werden';
$messages['aliases-aliasupdated'] = 'Alias erfolgreich aktualisiert';
$messages['aliases-aliasupdatederror'] = 'Alias konnte nicht aktualisiert werden';
$messages['aliases-aliasnameerror'] = 'Alias contains characters not allowed';
$messages['aliases-noaliases'] = 'Keine Aliase gefunden';
$messages['aliases-novalidalias'] = 'Alias-Name leer';


/* Forward */
/* Author: Jan B. Fiedler */
/* Author: Björn Barz */

$labels['forward'] = 'Weiterleitungen';
$labels['forward-manage'] = 'Weiterleitungen verwalten';
$labels['forward-address'] = 'Weiterleitungs-Ziel';
$labels['forward-addaddress'] = 'Weiterleitung hinzufügen';
$labels['forward-keepcopies'] = 'Original-Nachricht behalten';

$messages['forward-invalidaddress'] = 'E-Mail-Adresse ist nicht gültig';
$messages['forward-atleastoneaddress'] = 'Wenn keine Kopie der Original-Nachricht behalten wird, muss mindestens eine Weiterleitung eingerichtet sein';
$messages['forward-emptyaddress'] = 'Die Ziel-Adresse darf nicht leer sein';
$messages['forward-deleteaddress'] = 'Möchten Sie diese Weiterleitungs-Adresse wirklich löschen?';
$messages['forward-deletealladdresses'] = 'Alle Weiterleitungen löschen';
$messages['forward-addressexists'] = 'Diese Adresse existiert bereits';
$messages['forward-noaddress'] = 'Keine Weiterleitungs-Ziele eingerichtet';


/* Vacation */
/* Author: Boris Huisgen */
/* Author: Christian Boltz */
/* Author: Björn Barz */

$labels['vacation'] = 'Abwesenheit';
$labels['vacation-manage'] = 'Abwesenheitsnachricht verwalten';
$labels['vacation-subject'] = 'Betreff:';
$labels['vacation-body'] = 'Nachricht:';
$labels['vacation-active'] = 'Aktiv';
$labels['vacation-activefrom'] = 'Aktiv ab dem:';
$labels['vacation-activeuntil'] = 'Aktiv bis zum:';
$labels['vacation-intervaltime'] = 'Antworttyp:';
$labels['vacation-replyonce'] = 'Einmal antworten';
$labels['vacation-replytoeverymail'] = 'Jede Mail beantworten';
$labels['vacation-replyonceperday'] = 'Einmal pro Tag antworten';
$labels['vacation-replyonceperweek'] = 'Einmal pro Woche antworten';
$labels['vacation-htmleditor'] = 'HTML-Editor verwenden';

$messages['vacation-invalidstartdate'] = 'Bitte wählen Sie ein gültiges Startdatum.';
$messages['vacation-invalidenddate'] = 'Bitte wählen Sie ein gültiges Enddatum.';
$messages['vacation-invaliddateinterval'] = 'Das Antwort-Intervall ist ungültig.';
$messages['vacation-nosubject'] = 'Bitte geben Sie den Betreff Ihrer Abwesenheitsnachricht an.';
$messages['vacation-nomessage'] = 'Bitte geben Sie den Text Ihrer Abwesenheitsnachricht an.';
$messages['vacation-driverdefaulterror'] = 'Unbekannter Ausführungsfehler';
$messages['vacation-driverconnecterror'] = 'Fehler beim Verbindungsaufbau';
$messages['vacation-driverprocesserror'] = 'Fehler bei der Verarbeitung';


/* Purge */
/* Author: Philipp Kapfer */

$labels['purge'] = 'Ordnerbereinigung';
$labels['purge-manage'] = 'Manage purge options';
$labels['purge-domainsettings'] = 'Domain settings';
$labels['purge-trash'] = 'Nachrichten im Papierkorb behalten für';
$labels['purge-junk'] = 'Nachrichten im Spamordner behalten für';
$labels['purge-domainvalue'] = 'default domain value';
$labels['purge-always'] = 'immer';
$labels['purge-day'] = 'tag';
$labels['purge-days'] = 'tage';


/* Preview */
/* Author:  */

$labels['preview'] = 'Message preview';
$labels['preview-manage'] = 'Configure message preview';
$labels['preview-disable-message'] = 'Disable message preview';
$labels['preview-markasread-doubleclick'] = 'Mark a message read also by double clicking with mouse';


/* Customise */
/* Author:  */

$labels['customise'] = 'Customise';
$labels['customise-manage'] = 'Customise layout for domain';
$labels['customise-purge'] = 'Ordnerbereinigung';
$labels['customise-purge-trash'] = 'Nachrichten im Papierkorb behalten für';
$labels['customise-purge-junk'] = 'Nachrichten im Spamordner behalten für';
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
/* Author:  */

$labels['attachment-expiry-date'] = 'Download verfällt am';
$labels['attachment-maxuploadsize'] = 'NOTE: If one attachment is greater than $singlesize MB or the entire set of attachments is grater than $totalsize MB the recipient will receive a link to a zip file, which will expire in $lifespan days';


/* Tags */
/* Author:  */

$labels['tags'] = 'Tags';
$labels['tags-label'] = 'Tag';
$labels['tags-labels'] = 'Tags';

$labels['tags-title'] = 'Persönliche Tags';
$labels['tags-empty'] = 'Noch keine Tags vorhanden. Wählen Sie Neuer Tag, um einen zu erstellen.';
$labels['tags-newtag'] = 'Neuer Tag';
$labels['tags-showinlist'] = 'Tags in der Nachrichtenliste anzeigen';
$labels['tags-listhelp'] = 'Nachrichten mit Tags belegen eine zusätzliche Zeile in der Liste.';
$labels['tags-emptyvalues'] = 'Keine Werte vorhanden. Wählen Sie Neuer Wert, um einen hinzuzufügen.';
$labels['tags-newvalue'] = 'Neuer Wert';
$labels['tags-editname'] = 'Namen bearbeiten';
$labels['tags-deletename'] = 'Tag-Namen löschen';
$labels['tags-editvalue'] = 'Wert bearbeiten';
$labels['tags-deletevalue'] = 'Wert löschen';
$labels['tags-name'] = 'Tag-Name';
$labels['tags-value'] = 'Wert';
$labels['tags-color'] = 'Farbe';
$labels['tags-choosecolor'] = 'Tag-Farbe auswählen';
$labels['tags-save'] = 'Speichern';
$labels['tags-cancel'] = 'Abbrechen';
$labels['tags-newname'] = 'Neuer Tag-Name';
$labels['tags-editnametitle'] = 'Tag-Namen bearbeiten';
$labels['tags-parentprefix'] = 'Tag: ';
$labels['tags-confirmdeletegroup'] = '„{name}“ und alle zugehörigen Werte dauerhaft löschen?';
$labels['tags-confirmdeletevalue'] = '„{value}“ aus „{name}“ dauerhaft löschen?';
$labels['tags-deleteirreversible'] = 'Dieser Vorgang kann nicht rückgängig gemacht werden.';
$labels['tags-unavailable'] = 'Tags sind nicht verfügbar.';
$labels['tags-noneassigned'] = 'Keine Tags zugewiesen';
$labels['tags-listprefix'] = 'Tags: ';
$labels['tags-edit'] = 'Tags bearbeiten';
$labels['tags-editorfailed'] = 'Der Tag-Editor konnte nicht geladen werden. Prüfen Sie Ihre Sitzung und laden Sie den Editor erneut.';
$labels['tags-defssaving'] = 'Tag-Definitionen werden gespeichert…';
$labels['tags-definitions'] = 'Tag-Definitionen';
$labels['tags-defsloading'] = 'Tag-Definitionen werden geladen…';
$labels['tags-opendefs'] = 'Öffnen Sie Tag-Definitionen, um Tag-Namen und Werte anzulegen.';
$labels['tags-loading'] = 'Tags werden geladen…';
$labels['tags-saving'] = 'Tag wird gespeichert…';
$labels['tags-saveunconfirmed'] = 'Die Änderung konnte nicht bestätigt werden. Laden Sie die Tags vor einem erneuten Versuch neu.';
$labels['tags-loadfailed'] = 'Tags konnten nicht geladen werden. Versuchen Sie es erneut.';
$labels['tags-updated'] = 'Tags aktualisiert.';
$labels['tags-updateunconfirmed'] = 'Die Tag-Änderung konnte nicht bestätigt werden.';
$labels['tags-reloadhint'] = 'Laden Sie die Tags vor einem erneuten Versuch neu.';
$labels['tags-selectone'] = 'Wählen Sie genau eine Nachricht aus, um ihre Tags zu bearbeiten.';
$labels['tags-savepending'] = 'Eine Tag-Änderung für diese Nachricht wird noch gespeichert. Bitte warten.';
$labels['tags-options'] = 'Tag-Optionen';
$labels['tags-message'] = 'Nachrichten-Tags';
$labels['tags-reloadeditor'] = 'Editor neu laden';
$labels['tags-reload'] = 'Tags neu laden';
$labels['tags-defssavingclose'] = 'Tag-Definitionen werden gespeichert… Das Dialogfenster wird anschließend geschlossen.';

/* Tags backend errors */
/* Localised only at the user-facing response boundary. */
$messages['tags-error-invalidrequest'] = 'Ungültige Tag-Anfrage';
$messages['tags-error-missingmessage'] = 'Diese Nachricht ist in diesem Ordner nicht verfügbar';
$messages['tags-error-missingtag'] = 'Diese Tag-Definition existiert nicht mehr';
$messages['tags-error-tagsnotallowed'] = 'Dieser Ordner erlaubt keine benutzerdefinierten Tags';
$messages['tags-error-updateunconfirmed'] = 'Der Mailserver hat die Tag-Änderung nicht bestätigt';
$messages['tags-error-verifyfailed'] = 'Die aktualisierte Tag-Zuweisung konnte nicht überprüft werden';
$messages['tags-error-invalidoperation'] = 'Ungültiger Tag-Vorgang';
$messages['tags-error-unknownoperation'] = 'Unbekannter Tag-Vorgang';
$messages['tags-error-listsavefailed'] = 'Die Anzeigeoption für die Nachrichtenliste konnte nicht gespeichert werden';
$messages['tags-error-invalidlistoption'] = 'Ungültige Anzeigeoption für die Nachrichtenliste';
$messages['tags-error-invalidformvalue'] = 'Ungültiger Wert im Tag-Formular';
$messages['tags-error-toomanynames'] = 'Die maximale Anzahl an Tag-Namen wurde erreicht';
$messages['tags-error-unknownname'] = 'Unbekannter Tag-Name';
$messages['tags-error-unknowntag'] = 'Unbekannte persönliche Tag-Definition';
$messages['tags-error-toomanyvalues'] = 'Die maximale Anzahl an Tag-Definitionen wurde erreicht';
$messages['tags-error-restorefirst'] = 'Stellen Sie den Tag vor der Bearbeitung wieder her';
$messages['tags-error-invalidstore'] = 'Ungültiger Speicher für Tag-Definitionen';
$messages['tags-error-corrupteddefinition'] = 'Beschädigte persönliche Tag-Definition';
$messages['tags-error-corruptedtext'] = 'Beschädigter Text eines persönlichen Tags';
$messages['tags-error-duplicategroupid'] = 'Doppelte Tag-Gruppenkennung';
$messages['tags-error-corruptedgroups'] = 'Beschädigte Tag-Gruppen';
$messages['tags-error-corruptedgroup'] = 'Beschädigte Tag-Gruppe';
$messages['tags-error-corruptedgroupname'] = 'Beschädigter Tag-Gruppenname';
$messages['tags-error-duplicategroupname'] = 'Doppelte Tag-Gruppennamen';
$messages['tags-error-noparent'] = 'Ein Tag-Wert hat keine gültige übergeordnete Gruppe';
$messages['tags-error-definitionssavefailed'] = 'Persönliche Tag-Definitionen konnten nicht gespeichert werden';
$messages['tags-error-inactive'] = 'Der Tag-Name ist nicht aktiv';
$messages['tags-error-nameexists'] = 'Dieser Tag-Name existiert bereits';
$messages['tags-error-valueexists'] = 'Dieser Tag-Wert existiert bereits';
$messages['tags-error-invalidid'] = 'Ungültige Tag-Kennung';
$messages['tags-error-invalidname'] = 'Der Tag-Name muss gültigen, nicht leeren Text enthalten';
$messages['tags-error-invalidvalue'] = 'Der Tag-Wert muss gültigen, nicht leeren Text enthalten';
$messages['tags-error-invalidcolor'] = 'Ungültige Tag-Farbe';
$messages['tags-error-unexpected'] = 'Der Tag-Vorgang konnte nicht abgeschlossen werden';

/* Cloud */
/* Author:  */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Insert a link to a file';
$labels['cloud-insertfile'] = 'Attach a file';

/* Cloud Settings (PHP view) */
$labels['cloud-ui-generate-recovery'] = 'Wiederherstellungsschlüssel erstellen';
$labels['cloud-ui-create-title'] = 'Persönlichen Cloud-Tresor erstellen';
$labels['cloud-ui-create-explanation'] = 'Wählen Sie ein Tresorpasswort (mindestens 12 Zeichen), einen generierten Wiederherstellungsschlüssel oder beides. Der Schlüssel wird nur einmal angezeigt.';
$labels['cloud-ui-new-password-label'] = 'Tresorpasswort (optional bei Auswahl eines Wiederherstellungsschlüssels)';
$labels['cloud-ui-confirm-password-label'] = 'Tresorpasswort bestätigen';
$labels['cloud-ui-create-vault'] = 'Tresor erstellen';
$labels['cloud-ui-locked'] = 'Gesperrt';
$labels['cloud-ui-unlock-connections'] = 'Entsperren Sie den Tresor, um Verbindungen zu verwalten.';
$labels['cloud-ui-reset-timer'] = 'Zeitlimit zurücksetzen';
$labels['cloud-ui-lock-now'] = 'Jetzt sperren';
$labels['cloud-ui-vault-password'] = 'Tresorpasswort';
$labels['cloud-ui-recovery-key'] = 'Wiederherstellungsschlüssel';
$labels['cloud-ui-set-change-password'] = 'Passwort festlegen oder ändern';
$labels['cloud-ui-generate-new-recovery'] = 'Neuen Wiederherstellungsschlüssel erstellen';
$labels['cloud-ui-vault-security'] = 'Tresorsicherheit';
$labels['cloud-ui-change-security-help'] = 'Passwort oder Wiederherstellungsschlüssel ändern';
$labels['cloud-ui-new-vault-password'] = 'Neues Tresorpasswort';
$labels['cloud-ui-confirm-new-password'] = 'Neues Tresorpasswort bestätigen';
$labels['cloud-ui-rotate-warning'] = 'Das Erstellen eines neuen Wiederherstellungsschlüssels macht den bisherigen dauerhaft ungültig.';
$labels['cloud-ui-personal-vault'] = 'Persönlicher Tresor';
$labels['cloud-ui-unlock-info'] = 'Entsperren Sie den Tresor, um Cloud-Verbindungen zu verwalten. Der Server sperrt ihn nach 5 Minuten ohne Cloud-Aktivität oder 15 Minuten nach der letzten Entsperrung bzw. manuellen Zurücksetzung des Zeitlimits erneut. Klicken Sie im entsperrten Zustand auf Zeitlimit zurücksetzen, um beide Fristen zu erneuern, oder auf Jetzt sperren, um ihn sofort zu sperren. Der entsperrte Schlüssel wird vorübergehend in Ihrer authentifizierten Serversitzung gespeichert.';
$labels['cloud-ui-unlock-method'] = 'Entsperrmethode';
$labels['cloud-ui-unlock-secret'] = 'Tresorpasswort / Wiederherstellungsschlüssel';
$labels['cloud-ui-unlock-vault'] = 'Tresor entsperren';
$labels['cloud-ui-nextcloud-description'] = 'Mit der Nextcloud-WebDAV-URL und einem App-Passwort verbinden.';
$labels['cloud-ui-connect-account'] = 'Konto verbinden';
$labels['cloud-ui-owncloud-description'] = 'Mit der ownCloud-WebDAV-URL und Ihren Zugangsdaten verbinden.';
$labels['cloud-ui-generic-webdav'] = 'Allgemeines WebDAV';
$labels['cloud-ui-webdav-description'] = 'Mit jedem unterstützten öffentlichen HTTPS-WebDAV-Endpunkt verbinden.';
$labels['cloud-ui-google-description'] = 'Schreibgeschützten Zugriff auf Google Drive autorisieren.';
$labels['cloud-ui-connect-google'] = 'Google-Konto verbinden';
$labels['cloud-ui-google-aria'] = 'Google-Drive-Konto verbinden';
$labels['cloud-ui-onedrive-business'] = 'OneDrive Business';
$labels['cloud-ui-onedrive-business-description'] = 'Schreibgeschützten Zugriff auf das OneDrive Ihrer Arbeit oder Bildungseinrichtung autorisieren.';
$labels['cloud-ui-connect-business'] = 'Geschäftskonto verbinden';
$labels['cloud-ui-onedrive-business-aria'] = 'OneDrive-Business-Konto verbinden';
$labels['cloud-ui-onedrive-personal'] = 'OneDrive Personal';
$labels['cloud-ui-onedrive-personal-description'] = 'Schreibgeschützten Zugriff auf Ihr persönliches Microsoft OneDrive autorisieren.';
$labels['cloud-ui-connect-personal'] = 'Privatkonto verbinden';
$labels['cloud-ui-onedrive-personal-aria'] = 'OneDrive-Personal-Konto verbinden';
$labels['cloud-ui-dropbox-description'] = 'Schreibgeschützten Zugriff auf Ihr Dropbox-Konto autorisieren.';
$labels['cloud-ui-connect-dropbox'] = 'Dropbox-Konto verbinden';
$labels['cloud-ui-cloud-connections'] = 'Cloud-Verbindungen';
$labels['cloud-ui-choose-provider'] = 'Wählen Sie einen Anbieter. WebDAV verwendet App-Zugangsdaten; OAuth-Anbieter müssen vom Administrator konfiguriert werden.';
$labels['cloud-ui-connected-accounts'] = 'Verbundene Konten';
$labels['cloud-ui-unlock-to-list'] = 'Entsperren Sie den Tresor, um Konten anzuzeigen.';
$labels['cloud-ui-connect-cloud-account'] = 'Cloud-Konto verbinden';
$labels['cloud-ui-close'] = 'Schließen';
$labels['cloud-ui-close-connection'] = 'Verbindungsdialog schließen';
$labels['cloud-ui-account-name'] = 'Kontoname';
$labels['cloud-ui-my-cloud-account'] = 'Mein Cloud-Konto';
$labels['cloud-ui-public-webdav-url'] = 'Vollständige öffentliche HTTPS-WebDAV-URL';
$labels['cloud-ui-webdav-username'] = 'WebDAV-Benutzername';
$labels['cloud-ui-webdav-password'] = 'WebDAV-App-Passwort / Passwort';
$labels['cloud-ui-connection-needs-unlock'] = 'Der Cloud-Tresor muss entsperrt werden, bevor ein Konto verbunden werden kann.';
$labels['cloud-ui-add-account'] = 'Konto hinzufügen';
$labels['cloud-ui-browse-files'] = 'Cloud-Dateien durchsuchen';
$labels['cloud-ui-browse-explanation'] = 'Durchsuchen Sie Ordner und verfügbare Dateien. Verwenden Sie in der Nachrichtenerstellung Aus Cloud anhängen, um Dateien anzuhängen.';
$labels['cloud-ui-up-folder'] = 'Übergeordneter Ordner';
$labels['cloud-ui-next-entries'] = 'Nächste 100 Einträge';
$labels['cloud-ui-save-recovery-title'] = 'Wiederherstellungsschlüssel jetzt sichern';
$labels['cloud-ui-recovery-once-help'] = 'Der Wiederherstellungsschlüssel kann nicht erneut angezeigt werden. Bewahren Sie eine Kopie an einem sicheren Ort außerhalb dieses Webmail-Kontos auf.';
$labels['cloud-ui-recovery-saved'] = 'Ich habe ihn gesichert — Schlüssel ausblenden';


/* Cloud JavaScript interface */
/* Author: Gianluca Giacometti */

$labels['cloud-ui-unlocked'] = 'Entsperrt';
$labels['cloud-ui-automatic-lock-in'] = 'Automatische Sperre in %s';
$labels['cloud-ui-unlock-expired'] = 'Entsperrung abgelaufen.';
$labels['cloud-ui-request-in-progress'] = 'Ein Cloud-Vorgang läuft bereits.';
$labels['cloud-ui-processing-request'] = 'Cloud-Anfrage wird verarbeitet…';
$labels['cloud-ui-request-send-failed'] = 'Cloud-Anfrage konnte nicht gesendet werden.';
$labels['cloud-ui-unlock-secret-required'] = 'Gib für diesen Vorgang das Tresorpasswort oder den Wiederherstellungsschlüssel ein.';
$labels['cloud-ui-paste-recovery'] = 'Wiederherstellungsschlüssel einfügen';
$labels['cloud-ui-enter-vault-password'] = 'Tresorpasswort eingeben';
$labels['cloud-ui-nextcloud-webdav-help'] = 'Verwende die WebDAV-Adresse aus den Nextcloud-Dateieinstellungen.';
$labels['cloud-ui-owncloud-webdav-help'] = 'Verwende die WebDAV-Adresse deiner ownCloud-Installation.';
$labels['cloud-ui-webdav-url-help'] = 'Gib die vollständige öffentliche HTTPS-URL deines WebDAV-Ordners ein.';
$labels['cloud-ui-dialog-unsupported'] = 'Dein Browser unterstützt den Cloud-Verbindungsdialog nicht.';
$labels['cloud-ui-unlock-cloud-first'] = 'Entsperre zuerst deinen Cloud-Tresor.';
$labels['cloud-ui-connect-named'] = '%s verbinden';
$labels['cloud-ui-my-named'] = 'Mein %s';
$labels['cloud-ui-account-unavailable'] = 'Das Cloud-Konto ist nicht mehr verfügbar. Aktualisiere die Liste.';
$labels['cloud-ui-edit-named'] = '%s bearbeiten';
$labels['cloud-ui-cloud-account-default'] = 'Cloud-Konto';
$labels['cloud-ui-cloud-account-title'] = 'Cloud-Konto';
$labels['cloud-ui-edit-webdav-help'] = 'Aktualisiere deine WebDAV-Verbindung. Gespeicherte Zugangsdaten werden nie angezeigt.';
$labels['cloud-ui-keep-username'] = 'Leer lassen, um den aktuellen Benutzernamen beizubehalten';
$labels['cloud-ui-keep-app-password'] = 'Leer lassen, um das aktuelle App-Passwort beizubehalten';
$labels['cloud-ui-keep-credentials-help'] = 'Lass Benutzername oder Passwort leer, um den gespeicherten Wert beizubehalten.';
$labels['cloud-ui-save-changes'] = 'Änderungen speichern';
$labels['cloud-ui-unlock-before-browse'] = 'Entsperre den Tresor, bevor du Dateien durchsuchst.';
$labels['cloud-ui-my-drive'] = 'Meine Ablage';
$labels['cloud-ui-loading-folder'] = 'Ordner wird geladen…';
$labels['cloud-ui-folder-list-failed-start'] = 'Ordnerauflistung konnte nicht gestartet werden.';
$labels['cloud-ui-select-valid-account'] = 'Entsperre den Tresor und wähle ein gültiges WebDAV-Konto.';
$labels['cloud-ui-browse-files-named'] = 'Dateien durchsuchen: %s';
$labels['cloud-ui-folder-empty'] = 'Dieser Ordner ist leer.';
$labels['cloud-ui-bytes'] = 'Bytes';
$labels['cloud-ui-no-cloud-accounts'] = 'Keine Cloud-Konten verbunden.';
$labels['cloud-ui-browse-cloud-files'] = 'Cloud-Dateien durchsuchen';
$labels['cloud-ui-edit-cloud-account'] = 'Cloud-Konto bearbeiten';
$labels['cloud-ui-edit-account'] = 'Konto bearbeiten';
$labels['cloud-ui-delete-cloud-account'] = 'Cloud-Konto löschen';
$labels['cloud-ui-delete-account'] = 'Konto löschen';
$labels['cloud-ui-folder-list-failed'] = 'Ordnerauflistung fehlgeschlagen. Siehe Roundcube-Fehlermeldung.';
$labels['cloud-ui-operation-failed'] = 'Cloud-Vorgang fehlgeschlagen.';
$labels['cloud-ui-vault-unlocked'] = 'Cloud-Tresor entsperrt.';
$labels['cloud-ui-vault-locked'] = 'Cloud-Tresor gesperrt.';
$labels['cloud-ui-timer-reset'] = 'Tresor-Timer zurückgesetzt.';
$labels['cloud-ui-vault-created'] = 'Tresor erstellt. Speichere den Wiederherstellungsschlüssel, falls einer erzeugt wurde.';
$labels['cloud-ui-google-open-failed'] = 'Google-Autorisierung konnte nicht geöffnet werden. Erlaube Pop-ups und versuche es erneut.';
$labels['cloud-ui-microsoft-open-failed'] = 'Microsoft-Autorisierung konnte nicht geöffnet werden. Erlaube Pop-ups und versuche es erneut.';
$labels['cloud-ui-personal-open-failed'] = 'OneDrive-Personal-Autorisierung konnte nicht geöffnet werden. Erlaube Pop-ups und versuche es erneut.';
$labels['cloud-ui-dropbox-open-failed'] = 'Dropbox-Autorisierung konnte nicht geöffnet werden. Erlaube Pop-ups und versuche es erneut.';
$labels['cloud-ui-accounts-loaded'] = 'Cloud-Konten geladen.';
$labels['cloud-ui-account-added'] = 'Cloud-Konto hinzugefügt.';
$labels['cloud-ui-account-updated'] = 'Cloud-Konto aktualisiert.';
$labels['cloud-ui-account-removed'] = 'Cloud-Konto entfernt.';
$labels['cloud-ui-vault-password-updated'] = 'Tresorpasswort aktualisiert.';
$labels['cloud-ui-recovery-changed'] = 'Wiederherstellungsschlüssel geändert. Der alte Schlüssel funktioniert nicht mehr.';
$labels['cloud-ui-vault-password-mismatch'] = 'Tresorpasswort und Bestätigung stimmen nicht überein.';
$labels['cloud-ui-vault-password-length'] = 'Das Tresorpasswort muss mindestens 12 Zeichen enthalten.';
$labels['cloud-ui-vault-method-required'] = 'Wähle ein Tresorpasswort, einen Wiederherstellungsschlüssel oder beides.';
$labels['cloud-ui-google-popups'] = 'Erlaube Pop-ups für diese Webmail-Seite, um Google Drive zu verbinden.';
$labels['cloud-ui-google-connected'] = 'Google Drive verbunden.';
$labels['cloud-ui-google-auth-failed'] = 'Google Drive-Autorisierung fehlgeschlagen oder Tresor abgelaufen.';
$labels['cloud-ui-onedrive-popups'] = 'Erlaube Pop-ups für diese Webmail-Seite, um OneDrive zu verbinden.';
$labels['cloud-ui-onedrive-connected'] = 'Microsoft OneDrive verbunden.';
$labels['cloud-ui-onedrive-auth-failed'] = 'Microsoft OneDrive-Autorisierung fehlgeschlagen oder Tresor abgelaufen.';
$labels['cloud-ui-personal-popups'] = 'Erlaube Pop-ups für diese Webmail-Seite, um OneDrive Personal zu verbinden.';
$labels['cloud-ui-personal-connected'] = 'OneDrive Personal verbunden.';
$labels['cloud-ui-personal-auth-failed'] = 'OneDrive-Personal-Autorisierung fehlgeschlagen oder Tresor abgelaufen.';
$labels['cloud-ui-dropbox-popups'] = 'Erlaube Pop-ups für diese Webmail-Seite, um Dropbox zu verbinden.';
$labels['cloud-ui-dropbox-connected'] = 'Dropbox verbunden.';
$labels['cloud-ui-dropbox-auth-failed'] = 'Dropbox-Autorisierung fehlgeschlagen oder Tresor abgelaufen.';
$labels['cloud-ui-unlock-first'] = 'Entsperre zuerst den Tresor.';
$labels['cloud-ui-choose-provider-first'] = 'Wähle zuerst einen Cloud-Anbieter.';
$labels['cloud-ui-account-edit-required'] = 'Gib einen Kontonamen und eine öffentliche HTTPS-WebDAV-URL an.';
$labels['cloud-ui-account-new-required'] = 'Gib Kontonamen, öffentliche HTTPS-WebDAV-URL, Benutzernamen und App-Passwort an.';
$labels['cloud-ui-remove-account-confirm'] = 'Dieses Cloud-Konto entfernen?';
$labels['cloud-ui-new-password-mismatch'] = 'Neues Tresorpasswort und Bestätigung stimmen nicht überein.';
$labels['cloud-ui-new-password-length'] = 'Das neue Tresorpasswort muss mindestens 12 Zeichen enthalten.';
$labels['cloud-ui-rotate-recovery-confirm'] = 'Neuen Wiederherstellungsschlüssel erzeugen? Der bisherige funktioniert danach nicht mehr.';
$labels['cloud-ui-compose-send-failed'] = 'Cloud-Anfrage konnte nicht gesendet werden.';
$labels['cloud-ui-compose-choose-account'] = 'Cloud-Konto auswählen';
$labels['cloud-ui-compose-select-account'] = 'Wähle ein Konto zum Durchsuchen.';
$labels['cloud-ui-compose-no-accounts'] = 'Keine Konten verbunden. Füge eines in den Toolbox-Einstellungen hinzu.';
$labels['cloud-ui-compose-attach'] = 'Anhängen';
$labels['cloud-ui-compose-attach-named'] = '%s anhängen';
$labels['cloud-ui-compose-invalid-id'] = 'Cloud-Datei gespeichert, aber die zurückgegebene Anhang-ID ist ungültig. Entwurf aktualisieren.';
$labels['cloud-ui-compose-attachment-default'] = 'Cloud-Anhang';
$labels['cloud-ui-compose-remove-attachment'] = 'Anhang entfernen';
$labels['cloud-ui-compose-delete'] = 'Löschen';
$labels['cloud-ui-compose-attached'] = '%s aus der Cloud angehängt.';
$labels['cloud-ui-compose-request-failed'] = 'Cloud-Anfrage fehlgeschlagen.';
$labels['cloud-ui-compose-create-vault'] = 'Erstelle zuerst deinen Cloud-Tresor in den Toolbox-Einstellungen.';
$labels['cloud-ui-compose-attach-from-cloud'] = 'Aus Cloud anhängen';
$labels['cloud-ui-compose-unlock-help'] = 'Entsperre deinen Cloud-Tresor, um Dateien anzuhängen.';
$labels['cloud-ui-compose-secret-label'] = 'Passwort oder Wiederherstellungsschlüssel';
$labels['cloud-ui-compose-account'] = 'Cloud-Konto';
$labels['cloud-ui-compose-picker-unsupported'] = 'Dein Browser unterstützt die Cloud-Dateiauswahl nicht.';
$labels['cloud-ui-compose-secret-required'] = 'Gib dein Tresorpasswort oder den Wiederherstellungsschlüssel ein.';
$labels['cloud-ui-compose-importing'] = 'Cloud-Anhang wird importiert…';

/* Safe login */
/* Author:  */

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
/* Author:  */

$labels['folders'] = 'Riepilogo cartelle';
$labels['folders-numberoffiles'] = 'Numero file';

/* Cloud backend responses: translated only at user-facing boundaries */
$labels['cloud-backend-unlock-failed'] = 'Tresor konnte nicht entsperrt werden. Prüfe das Passwort oder den Wiederherstellungsschlüssel.';
$labels['cloud-backend-operation-failed'] = 'Cloud-Vorgang fehlgeschlagen. Prüfe die Details und Serverprotokolle.';
$labels['cloud-backend-google-success'] = 'Google Drive-Konto verbunden. Du kannst dieses Fenster schließen.';
$labels['cloud-backend-google-failed'] = 'Google Drive-Verbindung fehlgeschlagen oder Tresorentsperrung abgelaufen. Du kannst dieses Fenster schließen.';
$labels['cloud-backend-business-success'] = 'OneDrive Business-Konto verbunden. Du kannst dieses Fenster schließen.';
$labels['cloud-backend-business-failed'] = 'OneDrive Business-Autorisierung fehlgeschlagen oder Tresorentsperrung abgelaufen. Du kannst dieses Fenster schließen.';
$labels['cloud-backend-personal-success'] = 'OneDrive Personal-Konto verbunden. Du kannst dieses Fenster schließen.';
$labels['cloud-backend-personal-failed'] = 'OneDrive Personal-Autorisierung fehlgeschlagen oder Tresorentsperrung abgelaufen. Du kannst dieses Fenster schließen.';
$labels['cloud-backend-dropbox-success'] = 'Dropbox-Konto verbunden. Du kannst dieses Fenster schließen.';
$labels['cloud-backend-dropbox-failed'] = 'Dropbox-Autorisierung fehlgeschlagen oder Tresorentsperrung abgelaufen. Du kannst dieses Fenster schließen.';

// END OF FILE

