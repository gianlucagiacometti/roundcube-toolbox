<?php


$labels = [];
$messages = [];


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
$messages['toolbox-driverdefaulterror'] = 'Erreur inconnue lors de l’exécution du pilote';
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
$messages['vacation-driverdefaulterror'] = 'Erreur d’exécution inconnue';
$messages['vacation-driverconnecterror'] = 'Erreur lors de la connexion';
$messages['vacation-driverprocesserror'] = 'Erreur lors du traitement';


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

$labels['tags'] = 'Étiquettes';
$labels['tags-label'] = 'Étiquette';
$labels['tags-labels'] = 'Étiquettes';

$labels['tags-title'] = 'Étiquettes personnelles';
$labels['tags-empty'] = 'Aucune étiquette. Sélectionnez Nouvelle étiquette pour en créer une.';
$labels['tags-newtag'] = 'Nouvelle étiquette';
$labels['tags-showinlist'] = 'Afficher les étiquettes dans la liste des messages';
$labels['tags-listhelp'] = 'Les messages étiquetés occupent une ligne supplémentaire dans la liste.';
$labels['tags-emptyvalues'] = 'Aucune valeur. Sélectionnez Nouvelle valeur pour en ajouter une.';
$labels['tags-newvalue'] = 'Nouvelle valeur';
$labels['tags-editname'] = 'Modifier le nom';
$labels['tags-deletename'] = 'Supprimer le nom de l’étiquette';
$labels['tags-editvalue'] = 'Modifier la valeur';
$labels['tags-deletevalue'] = 'Supprimer la valeur';
$labels['tags-name'] = 'Nom de l’étiquette';
$labels['tags-value'] = 'Valeur';
$labels['tags-color'] = 'Couleur';
$labels['tags-choosecolor'] = 'Choisir la couleur de l’étiquette';
$labels['tags-save'] = 'Enregistrer';
$labels['tags-cancel'] = 'Annuler';
$labels['tags-newname'] = 'Nouveau nom d’étiquette';
$labels['tags-editnametitle'] = 'Modifier le nom de l’étiquette';
$labels['tags-parentprefix'] = 'Étiquette : ';
$labels['tags-confirmdeletegroup'] = 'Supprimer définitivement « {name} » et toutes ses valeurs ?';
$labels['tags-confirmdeletevalue'] = 'Supprimer définitivement « {value} » de « {name} » ?';
$labels['tags-deleteirreversible'] = 'Cette action est irréversible.';
$labels['tags-unavailable'] = 'Les étiquettes sont indisponibles.';
$labels['tags-noneassigned'] = 'Aucune étiquette attribuée';
$labels['tags-listprefix'] = 'Étiquettes : ';
$labels['tags-edit'] = 'Modifier les étiquettes';
$labels['tags-editorfailed'] = 'Impossible de charger l’éditeur des étiquettes. Vérifiez votre session, puis rechargez l’éditeur.';
$labels['tags-defssaving'] = 'Enregistrement des définitions d’étiquettes…';
$labels['tags-definitions'] = 'Définitions des étiquettes';
$labels['tags-defsloading'] = 'Chargement des définitions d’étiquettes…';
$labels['tags-opendefs'] = 'Ouvrez Définitions des étiquettes pour créer des noms et des valeurs.';
$labels['tags-loading'] = 'Chargement des étiquettes…';
$labels['tags-saving'] = 'Enregistrement de l’étiquette…';
$labels['tags-saveunconfirmed'] = 'Impossible de confirmer la modification. Rechargez les étiquettes avant de réessayer.';
$labels['tags-loadfailed'] = 'Impossible de charger les étiquettes. Réessayez.';
$labels['tags-updated'] = 'Étiquettes mises à jour.';
$labels['tags-updateunconfirmed'] = 'Impossible de confirmer la modification de l’étiquette.';
$labels['tags-reloadhint'] = 'Rechargez les étiquettes avant de réessayer.';
$labels['tags-selectone'] = 'Sélectionnez un seul message pour modifier ses étiquettes.';
$labels['tags-savepending'] = 'Une modification des étiquettes de ce message est en cours. Patientez.';
$labels['tags-options'] = 'Options des étiquettes';
$labels['tags-message'] = 'Étiquettes du message';
$labels['tags-reloadeditor'] = 'Recharger l’éditeur';
$labels['tags-reload'] = 'Recharger les étiquettes';
$labels['tags-defssavingclose'] = 'Enregistrement des définitions d’étiquettes… La fenêtre se fermera à la fin.';

/* Tags backend errors */
/* Localised only at the user-facing response boundary. */
$messages['tags-error-invalidrequest'] = 'Requête d’étiquette non valide';
$messages['tags-error-missingmessage'] = 'Ce message n’est pas disponible dans ce dossier';
$messages['tags-error-missingtag'] = 'Cette définition d’étiquette n’existe plus';
$messages['tags-error-tagsnotallowed'] = 'Ce dossier n’autorise pas les étiquettes personnalisées';
$messages['tags-error-updateunconfirmed'] = 'Le serveur de messagerie n’a pas confirmé la modification de l’étiquette';
$messages['tags-error-verifyfailed'] = 'Impossible de vérifier l’étiquette modifiée sur le message';
$messages['tags-error-invalidoperation'] = 'Opération sur les étiquettes non valide';
$messages['tags-error-unknownoperation'] = 'Opération sur les étiquettes inconnue';
$messages['tags-error-listsavefailed'] = 'Impossible d’enregistrer l’option d’affichage dans la liste des messages';
$messages['tags-error-invalidlistoption'] = 'Option d’affichage dans la liste des messages non valide';
$messages['tags-error-invalidformvalue'] = 'Valeur du formulaire d’étiquettes non valide';
$messages['tags-error-toomanynames'] = 'Nombre maximal de noms d’étiquettes atteint';
$messages['tags-error-unknownname'] = 'Nom d’étiquette inconnu';
$messages['tags-error-unknowntag'] = 'Définition d’étiquette personnelle inconnue';
$messages['tags-error-toomanyvalues'] = 'Nombre maximal de définitions d’étiquettes atteint';
$messages['tags-error-restorefirst'] = 'Restaurez l’étiquette avant de la modifier';
$messages['tags-error-invalidstore'] = 'Stockage des définitions d’étiquettes non valide';
$messages['tags-error-corrupteddefinition'] = 'Définition d’étiquette personnelle corrompue';
$messages['tags-error-corruptedtext'] = 'Texte d’étiquette personnelle corrompu';
$messages['tags-error-duplicategroupid'] = 'Identifiant de groupe d’étiquettes en double';
$messages['tags-error-corruptedgroups'] = 'Groupes d’étiquettes corrompus';
$messages['tags-error-corruptedgroup'] = 'Groupe d’étiquettes corrompu';
$messages['tags-error-corruptedgroupname'] = 'Nom de groupe d’étiquettes corrompu';
$messages['tags-error-duplicategroupname'] = 'Noms de groupes d’étiquettes en double';
$messages['tags-error-noparent'] = 'Une valeur d’étiquette ne possède pas de groupe parent valide';
$messages['tags-error-definitionssavefailed'] = 'Impossible d’enregistrer les définitions d’étiquettes personnelles';
$messages['tags-error-inactive'] = 'Le nom de l’étiquette n’est pas actif';
$messages['tags-error-nameexists'] = 'Ce nom d’étiquette existe déjà';
$messages['tags-error-valueexists'] = 'Cette valeur d’étiquette existe déjà';
$messages['tags-error-invalidid'] = 'Identifiant d’étiquette non valide';
$messages['tags-error-invalidname'] = 'Le nom de l’étiquette doit contenir un texte valide et non vide';
$messages['tags-error-invalidvalue'] = 'La valeur de l’étiquette doit contenir un texte valide et non vide';
$messages['tags-error-invalidcolor'] = 'Couleur d’étiquette non valide';
$messages['tags-error-unexpected'] = 'Impossible de terminer l’opération sur les étiquettes';

/* Cloud */
/* Author: Helena Branch */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Insérez un lien au fichier';
$labels['cloud-insertfile'] = 'Insérez un fichier';

/* Cloud Settings (PHP view) */
$labels['cloud-ui-generate-recovery'] = 'Générer une clé de récupération';
$labels['cloud-ui-create-title'] = 'Créer votre coffre-fort Cloud personnel';
$labels['cloud-ui-create-explanation'] = 'Choisissez un mot de passe pour le coffre-fort (au moins 12 caractères), une clé de récupération générée, ou les deux. La clé ne s\'affiche qu\'une seule fois.';
$labels['cloud-ui-new-password-label'] = 'Mot de passe du coffre-fort (facultatif si une clé de récupération est sélectionnée)';
$labels['cloud-ui-confirm-password-label'] = 'Confirmer le mot de passe du coffre-fort';
$labels['cloud-ui-create-vault'] = 'Créer le coffre-fort';
$labels['cloud-ui-locked'] = 'Verrouillé';
$labels['cloud-ui-unlock-connections'] = 'Déverrouillez le coffre-fort pour gérer les connexions.';
$labels['cloud-ui-reset-timer'] = 'Réinitialiser le minuteur';
$labels['cloud-ui-lock-now'] = 'Verrouiller maintenant';
$labels['cloud-ui-vault-password'] = 'Mot de passe du coffre-fort';
$labels['cloud-ui-recovery-key'] = 'Clé de récupération';
$labels['cloud-ui-set-change-password'] = 'Définir ou modifier le mot de passe';
$labels['cloud-ui-generate-new-recovery'] = 'Générer une nouvelle clé de récupération';
$labels['cloud-ui-vault-security'] = 'Sécurité du coffre-fort';
$labels['cloud-ui-change-security-help'] = 'Modifier le mot de passe ou la clé de récupération';
$labels['cloud-ui-new-vault-password'] = 'Nouveau mot de passe du coffre-fort';
$labels['cloud-ui-confirm-new-password'] = 'Confirmer le nouveau mot de passe du coffre-fort';
$labels['cloud-ui-rotate-warning'] = 'La génération d\'une nouvelle clé de récupération invalide définitivement l\'ancienne.';
$labels['cloud-ui-personal-vault'] = 'Coffre-fort personnel';
$labels['cloud-ui-unlock-info'] = 'Déverrouillez le coffre-fort pour gérer les connexions Cloud. Le serveur le verrouille de nouveau après 5 minutes sans activité Cloud ou 15 minutes après le dernier déverrouillage ou la dernière réinitialisation manuelle du minuteur. Une fois déverrouillé, cliquez sur Réinitialiser le minuteur pour renouveler les deux limites, ou sur Verrouiller maintenant pour le verrouiller immédiatement. La clé déverrouillée est conservée temporairement dans votre session authentifiée sur le serveur.';
$labels['cloud-ui-unlock-method'] = 'Méthode de déverrouillage';
$labels['cloud-ui-unlock-secret'] = 'Mot de passe du coffre-fort / clé de récupération';
$labels['cloud-ui-unlock-vault'] = 'Déverrouiller le coffre-fort';
$labels['cloud-ui-nextcloud-description'] = 'Connectez-vous avec l\'URL WebDAV de Nextcloud et un mot de passe d\'application.';
$labels['cloud-ui-connect-account'] = 'Connecter un compte';
$labels['cloud-ui-owncloud-description'] = 'Connectez-vous avec l\'URL WebDAV d\'ownCloud et vos identifiants.';
$labels['cloud-ui-generic-webdav'] = 'WebDAV générique';
$labels['cloud-ui-webdav-description'] = 'Connectez-vous à tout point d\'accès WebDAV HTTPS public pris en charge.';
$labels['cloud-ui-google-description'] = 'Autorisez l\'accès en lecture seule à votre Drive avec Google.';
$labels['cloud-ui-connect-google'] = 'Connecter un compte Google';
$labels['cloud-ui-google-aria'] = 'Connecter un compte Google Drive';
$labels['cloud-ui-onedrive-business'] = 'OneDrive Entreprise';
$labels['cloud-ui-onedrive-business-description'] = 'Autorisez l\'accès en lecture seule à votre OneDrive professionnel ou scolaire.';
$labels['cloud-ui-connect-business'] = 'Connecter un compte professionnel';
$labels['cloud-ui-onedrive-business-aria'] = 'Connecter un compte OneDrive Entreprise';
$labels['cloud-ui-onedrive-personal'] = 'OneDrive Personnel';
$labels['cloud-ui-onedrive-personal-description'] = 'Autorisez l\'accès en lecture seule à votre OneDrive Microsoft personnel.';
$labels['cloud-ui-connect-personal'] = 'Connecter un compte personnel';
$labels['cloud-ui-onedrive-personal-aria'] = 'Connecter un compte OneDrive Personnel';
$labels['cloud-ui-dropbox-description'] = 'Autorisez l\'accès en lecture seule à votre compte Dropbox.';
$labels['cloud-ui-connect-dropbox'] = 'Connecter un compte Dropbox';
$labels['cloud-ui-cloud-connections'] = 'Connexions Cloud';
$labels['cloud-ui-choose-provider'] = 'Choisissez un fournisseur. WebDAV utilise des identifiants d\'application ; les fournisseurs OAuth doivent être configurés par l\'administrateur.';
$labels['cloud-ui-connected-accounts'] = 'Comptes connectés';
$labels['cloud-ui-unlock-to-list'] = 'Déverrouillez le coffre-fort pour afficher les comptes.';
$labels['cloud-ui-connect-cloud-account'] = 'Connecter un compte Cloud';
$labels['cloud-ui-close'] = 'Fermer';
$labels['cloud-ui-close-connection'] = 'Fermer la fenêtre de connexion';
$labels['cloud-ui-account-name'] = 'Nom du compte';
$labels['cloud-ui-my-cloud-account'] = 'Mon compte Cloud';
$labels['cloud-ui-public-webdav-url'] = 'URL WebDAV HTTPS publique complète';
$labels['cloud-ui-webdav-username'] = 'Nom d\'utilisateur WebDAV';
$labels['cloud-ui-webdav-password'] = 'Mot de passe d\'application WebDAV / mot de passe';
$labels['cloud-ui-connection-needs-unlock'] = 'Le coffre-fort Cloud doit être déverrouillé avant de connecter un compte.';
$labels['cloud-ui-add-account'] = 'Ajouter un compte';
$labels['cloud-ui-browse-files'] = 'Parcourir les fichiers Cloud';
$labels['cloud-ui-browse-explanation'] = 'Parcourez les dossiers et consultez les fichiers disponibles. Pour joindre des fichiers à un message, utilisez Joindre depuis Cloud dans la rédaction.';
$labels['cloud-ui-up-folder'] = 'Dossier parent';
$labels['cloud-ui-next-entries'] = '100 entrées suivantes';
$labels['cloud-ui-save-recovery-title'] = 'Enregistrez votre clé de récupération maintenant';
$labels['cloud-ui-recovery-once-help'] = 'La clé de récupération ne pourra plus être affichée. Copiez-la dans un endroit sûr en dehors de ce compte webmail.';
$labels['cloud-ui-recovery-saved'] = 'Je l\'ai enregistrée — masquer la clé';


/* Cloud JavaScript interface */
/* Author: Gianluca Giacometti */

$labels['cloud-ui-unlocked'] = 'Déverrouillé';
$labels['cloud-ui-automatic-lock-in'] = 'Verrouillage automatique dans %s';
$labels['cloud-ui-unlock-expired'] = 'Déverrouillage expiré.';
$labels['cloud-ui-request-in-progress'] = 'Une opération Cloud est déjà en cours.';
$labels['cloud-ui-processing-request'] = 'Traitement de la demande Cloud…';
$labels['cloud-ui-request-send-failed'] = 'Impossible d’envoyer la demande Cloud.';
$labels['cloud-ui-unlock-secret-required'] = 'Saisissez le mot de passe du coffre ou la clé de récupération pour cette opération.';
$labels['cloud-ui-paste-recovery'] = 'Collez votre clé de récupération';
$labels['cloud-ui-enter-vault-password'] = 'Saisissez le mot de passe du coffre';
$labels['cloud-ui-nextcloud-webdav-help'] = 'Utilisez l’adresse WebDAV indiquée dans les paramètres Fichiers de Nextcloud.';
$labels['cloud-ui-owncloud-webdav-help'] = 'Utilisez l’adresse WebDAV affichée par votre installation ownCloud.';
$labels['cloud-ui-webdav-url-help'] = 'Saisissez l’URL HTTPS publique complète de votre dossier WebDAV.';
$labels['cloud-ui-dialog-unsupported'] = 'Votre navigateur ne prend pas en charge la fenêtre de connexion Cloud.';
$labels['cloud-ui-unlock-cloud-first'] = 'Déverrouillez d’abord votre coffre Cloud.';
$labels['cloud-ui-connect-named'] = 'Connecter %s';
$labels['cloud-ui-my-named'] = 'Mon %s';
$labels['cloud-ui-account-unavailable'] = 'Le compte Cloud n’est plus disponible. Actualisez la liste.';
$labels['cloud-ui-edit-named'] = 'Modifier %s';
$labels['cloud-ui-cloud-account-default'] = 'compte Cloud';
$labels['cloud-ui-cloud-account-title'] = 'Compte Cloud';
$labels['cloud-ui-edit-webdav-help'] = 'Mettez à jour votre connexion WebDAV. Les identifiants enregistrés ne sont jamais affichés.';
$labels['cloud-ui-keep-username'] = 'Laissez vide pour conserver l’identifiant actuel';
$labels['cloud-ui-keep-app-password'] = 'Laissez vide pour conserver le mot de passe d’application actuel';
$labels['cloud-ui-keep-credentials-help'] = 'Laissez l’identifiant ou le mot de passe vide pour conserver sa valeur enregistrée.';
$labels['cloud-ui-save-changes'] = 'Enregistrer les modifications';
$labels['cloud-ui-unlock-before-browse'] = 'Déverrouillez le coffre avant de parcourir les fichiers.';
$labels['cloud-ui-my-drive'] = 'Mon Drive';
$labels['cloud-ui-loading-folder'] = 'Chargement du dossier…';
$labels['cloud-ui-folder-list-failed-start'] = 'Impossible de démarrer le chargement du dossier.';
$labels['cloud-ui-select-valid-account'] = 'Déverrouillez le coffre et sélectionnez un compte WebDAV valide.';
$labels['cloud-ui-browse-files-named'] = 'Parcourir les fichiers : %s';
$labels['cloud-ui-folder-empty'] = 'Ce dossier est vide.';
$labels['cloud-ui-bytes'] = 'octets';
$labels['cloud-ui-no-cloud-accounts'] = 'Aucun compte Cloud connecté.';
$labels['cloud-ui-browse-cloud-files'] = 'Parcourir les fichiers Cloud';
$labels['cloud-ui-edit-cloud-account'] = 'Modifier le compte Cloud';
$labels['cloud-ui-edit-account'] = 'Modifier le compte';
$labels['cloud-ui-delete-cloud-account'] = 'Supprimer le compte Cloud';
$labels['cloud-ui-delete-account'] = 'Supprimer le compte';
$labels['cloud-ui-folder-list-failed'] = 'Échec du chargement du dossier. Consultez la notification d’erreur Roundcube.';
$labels['cloud-ui-operation-failed'] = 'Échec de l’opération Cloud.';
$labels['cloud-ui-vault-unlocked'] = 'Coffre Cloud déverrouillé.';
$labels['cloud-ui-vault-locked'] = 'Coffre Cloud verrouillé.';
$labels['cloud-ui-timer-reset'] = 'Minuteur du coffre réinitialisé.';
$labels['cloud-ui-vault-created'] = 'Coffre créé. Enregistrez la clé de récupération si elle a été générée.';
$labels['cloud-ui-google-open-failed'] = 'Impossible d’ouvrir l’autorisation Google. Autorisez les fenêtres surgissantes et réessayez.';
$labels['cloud-ui-microsoft-open-failed'] = 'Impossible d’ouvrir l’autorisation Microsoft. Autorisez les fenêtres surgissantes et réessayez.';
$labels['cloud-ui-personal-open-failed'] = 'Impossible d’ouvrir l’autorisation OneDrive Personnel. Autorisez les fenêtres surgissantes et réessayez.';
$labels['cloud-ui-dropbox-open-failed'] = 'Impossible d’ouvrir l’autorisation Dropbox. Autorisez les fenêtres surgissantes et réessayez.';
$labels['cloud-ui-accounts-loaded'] = 'Comptes Cloud chargés.';
$labels['cloud-ui-account-added'] = 'Compte Cloud ajouté.';
$labels['cloud-ui-account-updated'] = 'Compte Cloud mis à jour.';
$labels['cloud-ui-account-removed'] = 'Compte Cloud supprimé.';
$labels['cloud-ui-vault-password-updated'] = 'Mot de passe du coffre mis à jour.';
$labels['cloud-ui-recovery-changed'] = 'Clé de récupération modifiée. La précédente ne fonctionne plus.';
$labels['cloud-ui-vault-password-mismatch'] = 'Le mot de passe du coffre et sa confirmation ne correspondent pas.';
$labels['cloud-ui-vault-password-length'] = 'Le mot de passe du coffre doit contenir au moins 12 caractères.';
$labels['cloud-ui-vault-method-required'] = 'Choisissez un mot de passe, une clé de récupération ou les deux.';
$labels['cloud-ui-google-popups'] = 'Autorisez les fenêtres surgissantes pour ce webmail afin de connecter Google Drive.';
$labels['cloud-ui-google-connected'] = 'Google Drive connecté.';
$labels['cloud-ui-google-auth-failed'] = 'Échec de l’autorisation Google Drive ou expiration du coffre.';
$labels['cloud-ui-onedrive-popups'] = 'Autorisez les fenêtres surgissantes pour ce webmail afin de connecter OneDrive.';
$labels['cloud-ui-onedrive-connected'] = 'Microsoft OneDrive connecté.';
$labels['cloud-ui-onedrive-auth-failed'] = 'Échec de l’autorisation Microsoft OneDrive ou expiration du coffre.';
$labels['cloud-ui-personal-popups'] = 'Autorisez les fenêtres surgissantes pour ce webmail afin de connecter OneDrive Personnel.';
$labels['cloud-ui-personal-connected'] = 'OneDrive Personnel connecté.';
$labels['cloud-ui-personal-auth-failed'] = 'Échec de l’autorisation OneDrive Personnel ou expiration du coffre.';
$labels['cloud-ui-dropbox-popups'] = 'Autorisez les fenêtres surgissantes pour ce webmail afin de connecter Dropbox.';
$labels['cloud-ui-dropbox-connected'] = 'Dropbox connecté.';
$labels['cloud-ui-dropbox-auth-failed'] = 'Échec de l’autorisation Dropbox ou expiration du coffre.';
$labels['cloud-ui-unlock-first'] = 'Déverrouillez d’abord le coffre.';
$labels['cloud-ui-choose-provider-first'] = 'Choisissez d’abord un fournisseur Cloud.';
$labels['cloud-ui-account-edit-required'] = 'Saisissez un nom de compte et une URL WebDAV HTTPS publique.';
$labels['cloud-ui-account-new-required'] = 'Saisissez un nom de compte, une URL WebDAV HTTPS publique, un identifiant et un mot de passe d’application.';
$labels['cloud-ui-remove-account-confirm'] = 'Supprimer ce compte Cloud ?';
$labels['cloud-ui-new-password-mismatch'] = 'Le nouveau mot de passe du coffre et sa confirmation ne correspondent pas.';
$labels['cloud-ui-new-password-length'] = 'Le nouveau mot de passe du coffre doit contenir au moins 12 caractères.';
$labels['cloud-ui-rotate-recovery-confirm'] = 'Générer une nouvelle clé de récupération ? La précédente cessera de fonctionner.';
$labels['cloud-ui-compose-send-failed'] = 'Impossible d’envoyer la demande Cloud.';
$labels['cloud-ui-compose-choose-account'] = 'Choisir un compte Cloud';
$labels['cloud-ui-compose-select-account'] = 'Sélectionnez un compte à parcourir.';
$labels['cloud-ui-compose-no-accounts'] = 'Aucun compte connecté. Ajoutez-en un dans les paramètres Toolbox.';
$labels['cloud-ui-compose-attach'] = 'Joindre';
$labels['cloud-ui-compose-attach-named'] = 'Joindre %s';
$labels['cloud-ui-compose-invalid-id'] = 'Le fichier Cloud a été enregistré, mais l’identifiant de la pièce jointe est invalide. Actualisez le brouillon.';
$labels['cloud-ui-compose-attachment-default'] = 'Pièce jointe Cloud';
$labels['cloud-ui-compose-remove-attachment'] = 'Retirer la pièce jointe';
$labels['cloud-ui-compose-delete'] = 'Supprimer';
$labels['cloud-ui-compose-attached'] = '%s joint depuis Cloud.';
$labels['cloud-ui-compose-request-failed'] = 'Échec de la demande Cloud.';
$labels['cloud-ui-compose-create-vault'] = 'Créez d’abord votre coffre Cloud dans les paramètres Toolbox.';
$labels['cloud-ui-compose-attach-from-cloud'] = 'Joindre depuis Cloud';
$labels['cloud-ui-compose-unlock-help'] = 'Déverrouillez votre coffre Cloud pour joindre des fichiers.';
$labels['cloud-ui-compose-secret-label'] = 'Mot de passe ou clé de récupération';
$labels['cloud-ui-compose-account'] = 'Compte Cloud';
$labels['cloud-ui-compose-picker-unsupported'] = 'Votre navigateur ne prend pas en charge le sélecteur Cloud.';
$labels['cloud-ui-compose-secret-required'] = 'Saisissez le mot de passe du coffre ou la clé de récupération.';
$labels['cloud-ui-compose-importing'] = 'Importation de la pièce jointe Cloud…';

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

/* Cloud backend responses: translated only at user-facing boundaries */
$labels['cloud-backend-unlock-failed'] = 'Impossible de déverrouiller le coffre. Vérifiez votre mot de passe ou votre clé de récupération.';
$labels['cloud-backend-operation-failed'] = 'Échec de l’opération Cloud. Consultez les détails et les journaux du serveur.';
$labels['cloud-backend-google-success'] = 'Compte Google Drive connecté. Vous pouvez fermer cette fenêtre.';
$labels['cloud-backend-google-failed'] = 'Échec de la connexion à Google Drive ou expiration du déverrouillage du coffre. Vous pouvez fermer cette fenêtre.';
$labels['cloud-backend-business-success'] = 'Compte OneDrive Business connecté. Vous pouvez fermer cette fenêtre.';
$labels['cloud-backend-business-failed'] = 'Échec de l’autorisation OneDrive Business ou expiration du déverrouillage du coffre. Vous pouvez fermer cette fenêtre.';
$labels['cloud-backend-personal-success'] = 'Compte OneDrive Personal connecté. Vous pouvez fermer cette fenêtre.';
$labels['cloud-backend-personal-failed'] = 'Échec de l’autorisation OneDrive Personal ou expiration du déverrouillage du coffre. Vous pouvez fermer cette fenêtre.';
$labels['cloud-backend-dropbox-success'] = 'Compte Dropbox connecté. Vous pouvez fermer cette fenêtre.';
$labels['cloud-backend-dropbox-failed'] = 'Échec de l’autorisation Dropbox ou expiration du déverrouillage du coffre. Vous pouvez fermer cette fenêtre.';

/* Folders: read-only IMAP folder properties */
$labels['folders-properties'] = 'Propriétés du dossier';
$labels['folders-folder'] = 'Dossier';
$labels['folders-total'] = 'Messages';
$labels['folders-unread'] = 'Non lus';
$labels['folders-read'] = 'Lus';
$labels['folders-size'] = 'Taille du dossier';
$labels['folders-calculate'] = 'Calculer la taille';
$labels['folders-notcalculated'] = 'Non calculée';
$labels['folders-calculating'] = 'Calcul en cours…';
$labels['folders-sizewarning'] = 'Sur certains serveurs, le calcul de la taille peut prendre du temps pour les grands dossiers.';
$labels['folders-namespace'] = 'Espace de noms';
$labels['folders-special'] = 'Dossier spécial';
$labels['folders-yes'] = 'Oui';
$labels['folders-no'] = 'Non';
$labels['folders-loading'] = 'Chargement des informations du dossier…';
$labels['folders-loadfailed'] = 'Impossible de charger les propriétés du dossier.';
$labels['folders-sizefailed'] = 'Impossible de calculer la taille du dossier.';
$labels['folders-namespace-personal'] = 'Personnel';
$labels['folders-namespace-other'] = 'Autres utilisateurs';
$labels['folders-namespace-shared'] = 'Partagé';
$labels['folders-namespace-unknown'] = 'Inconnu';

// END OF FILE

