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
$labels['tags-label'] = 'Etichetta';
$labels['tags-labels'] = 'Etichette';


/* Cloud */
/* Author:  */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Insert a link to a file';
$labels['cloud-insertfile'] = 'Attach a file';


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
