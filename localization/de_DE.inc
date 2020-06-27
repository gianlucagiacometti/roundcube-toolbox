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
$messages['aliases-noalias'] = 'Keine Aliase gefunden';
$messages['aliases-novalidalias'] = 'Alias-Name leer';


/* Forward */
/* Author: Jan B. Fiedler */

$labels['forward'] = 'Forward addresses';
$labels['forward-address'] = 'Forward address';
$labels['forward-addaddress'] = 'Add forward address';
$labels['forward-keepcopies'] = 'Original Nachricht behalten';

$messages['forward-invalidaddress'] = 'E-Mail Addresse ist nicht gültig';
$messages['forward-atleastoneaddress'] = 'At least one forward address is required if no copy is kept in mailbox';
$messages['forward-emptyaddress'] = 'The forward address cannot be empty';
$messages['forward-deleteaddress'] = 'Do you really want to delete this address?';
$messages['forward-deletealladdresses'] = 'Delete all addresses';
$messages['forward-addressexists'] = 'This address already exists';
$messages['forward-noaddress'] = 'No address found';


/* Vacation */
/* Author: Boris Huisgen */
/* Author: Christian Boltz */

$labels['vacation'] = 'Abwesenheit';
$labels['vacation-subject'] = 'Betreff:';
$labels['vacation-body'] = 'Nachricht:';
$labels['vacation-active'] = 'Aktiv';
$labels['vacation-activefrom'] = 'Aktive ab dem:';
$labels['vacation-activeuntil'] = 'Aktive bis zum:';
$labels['vacation-intervaltime'] = 'Antworttyp:';
$labels['vacation-replyonce'] = 'Einmal antworten';
$labels['vacation-replytoeverymail'] = 'Jede Mail beantworten';
$labels['vacation-replyonceperday'] = 'Einmal pro Tag antworten';
$labels['vacation-replyonceperweek'] = 'Einmal pro Woche antworten';
$labels['vacation-htmleditor'] = 'Activate HTML editor';

$messages['vacation-invalidstartdate'] = 'Bitte wählen Sie ein gültiges Startdatum.';
$messages['vacation-invalidenddate'] = 'Bitte wählen Sie ein gültiges Enddatum.';
$messages['vacation-invaliddateinterval'] = 'Das Intervall der Date ist ungültig.';
$messages['vacation-nosubject'] = 'Bitte geben Sie den Betreff Ihrer Abwesenheitsnachricht an.';
$messages['vacation-nomessage'] = 'Bitte geben Sie den Text Ihrer Abwesenheitsnachricht an.';


/* Purge */
/* Author: Philipp Kapfer */

$labels['purge'] = 'Ordnerbereinigung';
$labels['purge-domainsettings'] = 'Domain settings';
$labels['purge-trash'] = 'Nachrichten im Papierkorb behalten für';
$labels['purge-junk'] = 'Nachrichten im Spamordner behalten für';
$labels['purge-domainvalue'] = 'default domain value';
$labels['purge-always'] = 'immer';
$labels['purge-day'] = 'tag';
$labels['purge-days'] = 'tage';


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
