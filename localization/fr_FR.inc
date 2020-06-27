<?php


$labels = array();
$messages = array();


/* TOOLBOX */
/* Author: helena Branch */
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
$messages['aliases-noalias'] = 'No aliases found';
$messages['aliases-novalidalias'] = 'Alias name empty';


/* Forward */
/* Author: Valentin Samir */

$labels['forward'] = 'Adresses de réexpédition';
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
$labels['purge-domainsettings'] = 'Domain settings';
$labels['purge-trash'] = 'Garder les messages dans Corbeilles pendant';
$labels['purge-junk'] = 'Garder les messages dans Messages indésirables pendant';
$labels['purge-domainvalue'] = 'default domain value';
$labels['purge-always'] = 'toujours';
$labels['purge-day'] = 'jour';
$labels['purge-days'] = 'jours';


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
