<?php


$labels = array();
$messages = array();


/* TOOLBOX */
/* Author: Helena Branch */

$labels['toolbox'] = 'Outils';
$labels['toolbox-description'] = 'Ensemble des outils pour améliorer la gestion de la messagerie web';
$labels['toolbox-usersettings'] = 'User settings';
$labels['toolbox-arialabeltoolform'] = 'Formulaire des paramètres des outils';
$labels['toolbox-addresses'] = 'Addresses';
$labels['toolbox-deleteall'] = 'Delete all';
$labels['toolbox-enabled'] = 'Enabled';
$labels['toolbox-disabled'] = 'Disabled';

$messages['toolbox-driverconnecterror'] = 'Erreur de connexion à la base de donnée';
$messages['toolbox-driverprocesserror'] = 'Erreur lors de la mise à jour de la base de donnée';
$messages['toolbox-driverinternalerror'] = 'Erreur non spécifiée de la base de donnée';
$messages['toolbox-unknowntool'] = 'Unknown tool';
$messages['toolbox-internalerror'] = 'Unknown execution error';
$messages['toolbox-datasuccessfullysaved'] = 'Données enregistrées avec succès';
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
/* Author: Valentin Samir */
/* Author: Helena Branch */

$labels['forward'] = 'Adresses de réexpédition';
$labels['forward-manage'] = 'Gérer adresses de réexpédition';
$labels['forward-address'] = 'Adresse de réexpédition';
$labels['forward-addaddress'] = 'Ajouter une adresse de réexpédition';
$labels['forward-keepcopies'] = 'Garder une copie du message original';

$messages['forward-invalidaddress'] = 'Adresse email non valide';
$messages['forward-atleastoneaddress'] = 'At least one forward address is required if no copy is kept in mailbox';
$messages['forward-emptyaddress'] = 'The forward address cannot be empty';
$messages['forward-deleteaddress'] = 'Do you really want to delete this address?';
$messages['forward-deletealladdresses'] = 'Delete all addresses';
$messages['forward-addressexists'] = 'This address already exists';
$messages['forward-noaddress'] = 'No address found';


/* Vacation */
/* Author: Helena Branch */

$labels['vacation'] = 'Répondeur';
$labels['vacation-manage'] = 'Manage out of office message';
$labels['vacation-subject'] = 'Sujet :';
$labels['vacation-body'] = 'Message :';
$labels['vacation-active'] = 'Active';
$labels['vacation-activefrom'] = 'Active from:';
$labels['vacation-activeuntil'] = 'Active until:';
$labels['vacation-intervaltime'] = 'Reply type:';
$labels['vacation-replyonce'] = 'Reply once to each address';
$labels['vacation-replytoeverymail'] = 'Reply to every message';
$labels['vacation-replyonceperday'] = 'Reply once per day to each address';
$labels['vacation-replyonceperweek'] = 'Reply once per week to each address';
$labels['vacation-htmleditor'] = 'Activate HTML editor';

$messages['vacation-invalidstartdate'] = 'Veuillez saisir une date de début valide.';
$messages['vacation-invalidenddate'] = 'Veuillez saisir une date de fin valide.';
$messages['vacation-invaliddateinterval'] = 'L\'intervalle de date défini n\'est pas valide.';
$messages['vacation-nosubject'] = 'Veuillez saisir le sujet de votre répondeur.';
$messages['vacation-nomessage'] = 'Veuillez saisir le message de votre répondeur.';


/* Purge */
/* Author: Helena Branch */

$labels['purge'] = 'Vider les dossiers';
$labels['purge-manage'] = 'Manage purge options';
$labels['purge-domainsettings'] = 'Domain settings';
$labels['purge-trash'] = 'Garder les messages dans Corbeilles pendant';
$labels['purge-junk'] = 'Garder les messages dans Messages indésirables pendant';
$labels['purge-domainvalue'] = 'default domain value';
$labels['purge-always'] = 'toujours';
$labels['purge-day'] = 'jour';
$labels['purge-days'] = 'jours';


/* Preview */
/* Author:  */

$labels['preview'] = 'Message preview';
$labels['preview-manage'] = 'Configure message preview';
$labels['preview-disable-message'] = 'Disable message preview';
$labels['preview-markasread-doubleclick'] = 'Mark a message read also by double clicking with mouse';


/* Customise */
/* Author: Helena Branch */

$labels['customise'] = 'Personnaliser ';
$labels['customise-manage'] = 'Personnalisations pour le domaine';
$labels['customise-purge'] = 'Vider les dossiers';
$labels['customise-purge-trash'] = 'Garder les messages dans Corbeilles pendant';
$labels['customise-purge-junk'] = 'Garder les messages dans Messages indésirables pendant';
$labels['customise-blankpage'] = 'Page blanche personnalisé';
$labels['customise-blankpage-skin'] = 'Page blanche ';
$labels['customise-blankpage-skin-default'] = 'Page vierge originale';
$labels['customise-blankpage-skin-image'] = 'Page vierge avec image ';
$labels['customise-blankpage-skin-url'] = 'Connexion à une page externe';
$labels['customise-blankpage-skin-custom'] = 'Page vierge personnalisée';
$labels['customise-blankpage-image'] = 'Image';
$labels['customise-blankpage-url'] = 'Adresse';
$labels['customise-additional-css'] = 'CSS personnalisé';
$labels['customise-logo'] = 'Logo personnalisé';
$labels['customise-logo-template'] = 'Template';
$labels['customise-logo-template-new'] = 'Nouveau logo personnalisé';
$labels['customise-logo-template-all'] = 'Tous les modèles';
$labels['customise-logo-type'] = 'Type';
$labels['customise-logo-type-all'] = 'Tous les types';
$labels['customise-logo-type-favicon'] = 'Favicon';
$labels['customise-logo-type-print'] = 'Imprimer';
$labels['customise-logo-type-small'] = 'Petit';
$labels['customise-logo-type-dark'] = 'Logo in dark mode';
$labels['customise-logo-type-small-dark'] = 'Small in dark mode';
$labels['customise-logo-add-template'] = 'Ajouter un modèle';
$labels['customise-logo-image'] = 'Image';

$messages['customise-logo-customisedlogoexists'] = 'Le logo personnalisé existe déjà';
$messages['customise-logo-deletecustomisedlogo'] = 'Do you really want to delete this customised logo?';
$messages['customise-logo-deleteallcustomisedlogos'] = 'Do you really want to delete all customised logos for this skin?';
$messages['customise-logo-emptycustomisedlogotemplate'] = 'The logo template cannot be empty';
$messages['customise-logo-emptycustomisedlogoimage'] = 'The logo image cannot be empty';
$messages['customise-logo-invalidcustomisedlogoimage'] = 'The logo image is invalid';
$messages['customise-logo-nocustomisedlogo'] = 'No customised logo found';


/* Detach Attachments */
/* Author: Helena Branch */

$labels['attachment-expiry-date'] = 'Le téléchargement expire';
$labels['attachment-maxuploadsize'] = 'NOTE: If one attachment is greater than $singlesize MB or the entire set of attachments is grater than $totalsize MB the recipient will receive a link to a zip file, which will expire in $lifespan days';


/* Tags */
/* Author: Helena Branch */

$labels['tags'] = 'Tags';
$labels['tags-label'] = 'Tag';
$labels['tags-labels'] = 'Tags';


/* Cloud */
/* Author: Helena Branch */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Insérez un lien au fichier';
$labels['cloud-insertfile'] = 'Insérez un fichier';


/* Safe login */
/* Author: Helena Branch */

$labels['safelogin'] = 'Ouvrir une session en sécurité';
$labels['safelogin-continent'] = 'Continent';
$labels['safelogin-countryisocode'] = 'Code Nation';
$labels['safelogin-countryname'] = 'Nation';
$labels['safelogin-cityname'] = 'Ville';
$labels['safelogin-location'] = 'Adresse';
$labels['safelogin-lastlogin'] = 'Dernière connexion';
$labels['safelogin-history'] = 'Accéder à Historique';
$labels['safelogin-browser'] = 'Navigateur';
$labels['safelogin-os'] = 'Systeme d’exploitation';


/* Folders */
/* Author: Helena Branch */

$labels['folders'] = 'Riepilogo cartelle';
$labels['folders-numberoffiles'] = 'Numero file';
