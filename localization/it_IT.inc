<?php


$labels = array();
$messages = array();


/* TOOLBOX */
/* Author: Gianluca Giacometti */

$labels['toolbox'] = 'Strumenti';
$labels['toolbox-description'] = 'Insieme di strumenti utili per una migliore gestione della posta web';
$labels['toolbox-arialabeltoolform'] = 'Modulo di configurazione strumento';
$labels['toolbox-addresses'] = 'Indirizzi';
$labels['toolbox-deleteall'] = 'Cancella tutto';
$labels['toolbox-enabled'] = 'Abilitato';
$labels['toolbox-disabled'] = 'Disabilitato';

$messages['toolbox-driverconnecterror'] = 'Errore di connessione del driver';
$messages['toolbox-driverprocesserror'] = 'Errore di esecuzione del driver';
$messages['toolbox-driverdefaulterror'] = 'Errore sconosciuto di esecuzione del driver';
$messages['toolbox-driverinternalerror'] = 'Errore interno del driver';
$messages['toolbox-unknowntool'] = 'Strumento sconosciuto';
$messages['toolbox-internalerror'] = 'Errore di esecuzione sconosciuto';
$messages['toolbox-datasuccessfullysaved'] = 'Dati salvati con successo';
$messages['toolbox-datasaveerror'] = 'Errore durante il salvataggio dei dati';


/* Aliases */
/* Author: Gianluca Giacometti */

$labels['aliases'] = 'Nomi alternativi';
$labels['aliases-manage'] = 'Configura i nomi alternativi';
$labels['aliases-aliasname'] = 'Nome alternativo';
$labels['aliases-addaddress'] = 'Aggiungi nome alternativo';

$messages['aliases-aliasexists'] = 'Nome alternativo già esistente';
$messages['aliases-aliasexistsindomain'] = 'Nome alternativo già esistente nel dominio';
$messages['aliases-aliasdeleteconfirm'] = 'Sei sicuro/a di di voler cancellare questo nome alternativo?';
$messages['aliases-aliasdeleted'] = 'Nome alternativo cancellato con successo';
$messages['aliases-aliasdeletederror'] = 'Impossibile cancellare il nome alternativo';
$messages['aliases-aliascreated'] = 'Nome alternativo creato con successo';
$messages['aliases-aliascreatederror'] = 'Impossibile creare il nome alternativo';
$messages['aliases-aliasupdated'] = 'Nome alternativo aggiornato con successo';
$messages['aliases-aliasupdatederror'] = 'Impossibile aggiornare il nome alternativo';
$messages['aliases-aliasnameerror'] = 'Il nome contiene caratteri non ammessi.';
$messages['aliases-noaliases'] = 'Nessun nome alternativo trovato';
$messages['aliases-novalidalias'] = 'Nome alternativo vuoto';


/* Forward */
/* Author: Gianluca Giacometti */

$labels['forward'] = 'Indirizzi di inoltro';
$labels['forward-manage'] = 'Configura gli indirizzi di inoltro';
$labels['forward-address'] = 'Indirizzo di inoltro';
$labels['forward-addaddress'] = 'Aggiungi indirizzo';
$labels['forward-keepcopies'] = 'Mantieni copia dei messaggi originali';

$messages['forward-invalidaddress'] = 'Indirizzo e-mail non valido';
$messages['forward-atleastoneaddress'] = 'La casella deve almeno contenere un indirizzo di inoltro se non si mantengono coipie dei messaggi';
$messages['forward-emptyaddress'] = 'L\'indirizzo di inoltro non può essere vuoto';
$messages['forward-deleteaddress'] = 'Vuoi eliminare l\'indirizzo di inoltro?';
$messages['forward-deletealladdresses'] = 'Cancella tutti gli indirizzi';
$messages['forward-addressexists'] = 'L\'indirizzo di inoltro è già esistente';
$messages['forward-noaddress'] = 'Nessun indirizzo trovato';


/* Vacation */
/* Author: Gianluica Giacometti */

$labels['vacation'] = 'Autorisponditore';
$labels['vacation-manage'] = 'Configura l\'autorisponditore';
$labels['vacation-subject'] = 'Oggetto:';
$labels['vacation-body'] = 'Messaggio:';
$labels['vacation-active'] = 'Attivo';
$labels['vacation-activefrom'] = 'Attivo da:';
$labels['vacation-activeuntil'] = 'Attivo fino a:';
$labels['vacation-intervaltime'] = 'Modalità di risposta:';
$labels['vacation-replyonce'] = 'Rispondi una volta sola per ciascun indirizzo';
$labels['vacation-replytoeverymail'] = 'Rispondi ad ogni messaggio';
$labels['vacation-replyonceperday'] = 'Rispondi una volta al giorno a ciascun indirizzo';
$labels['vacation-replyonceperweek'] = 'Rispondi una volta alla settimana a ciascun indirizzo';
$labels['vacation-htmleditor'] = 'Attiva la composizione HTML';

$messages['vacation-invalidstartdate'] = 'Seleziona una data di inizio valida.';
$messages['vacation-invalidenddate'] = 'Seleziona una data di fine valida.';
$messages['vacation-invaliddateinterval'] = 'L\'intervallo di date non è valido.';
$messages['vacation-nosubject'] = 'Il campo oggetto non può essere vuoto.';
$messages['vacation-nomessage'] = 'Il campo messaggio non può essere vuoto.';


/* Purge */
/* Author: Gianluca Giacometti */

$labels['purge'] = 'Pulizia cartelle';
$labels['purge-manage'] = 'Configura la pulizia periodica delle cartelle';
$labels['purge-trash'] = 'Mantieni i messaggi nel Cestino per';
$labels['purge-junk'] = 'Mantieni i messaggi nella Posta indesiderata per';
$labels['purge-domainvalue'] = 'valore di base per il dominio';
$labels['purge-always'] = 'sempre';
$labels['purge-day'] = 'giorno';
$labels['purge-days'] = 'giorni';


/* Preview */
/* Author: Gianluca Giacometti */

$labels['preview'] = 'Anteprima messaggi';
$labels['preview-manage'] = 'Configura la visualizzazione dell\'anteprima dei messaggi';
$labels['preview-disable-message'] = 'Disabilita l\'anteprima dei messaggi';
$labels['preview-markasread-doubleclick'] = 'Imposta un messaggio come letto anche con il doppio click del mouse';


/* Customise */
/* Author: Gianluca Giacometti */

$labels['customise'] = 'Personalizza';
$labels['customise-manage'] = 'Personalizzazioni per il dominio';
$labels['customise-purge'] = 'Pulizia cartelle';
$labels['customise-purge-trash'] = 'Mantieni i messaggi nel Cestino per';
$labels['customise-purge-junk'] = 'Mantieni i messaggi nella Posta indesiderata per';
$labels['customise-blankpage'] = 'Pagina vuota personalizzata';
$labels['customise-blankpage-skin'] = 'Pagina vuota';
$labels['customise-blankpage-skin-default'] = 'Pagina vuota Roundcube originale';
$labels['customise-blankpage-skin-image'] = 'Pagina vuota con immagine';
$labels['customise-blankpage-skin-url'] = 'Collegamento a una pagina locale o esterna';
$labels['customise-blankpage-skin-custom'] = 'Pagina personalizzata';
$labels['customise-blankpage-image'] = 'Immagine';
$labels['customise-blankpage-url'] = 'Indirizzo';
$labels['customise-additional-css'] = 'CSS personalizzato';
$labels['customise-logo'] = 'Logo personalizzato';
$labels['customise-logo-template'] = 'Modello';
$labels['customise-logo-template-new'] = 'Nuovo logo personalizzato';
$labels['customise-logo-template-all'] = 'Tutti';
$labels['customise-logo-type'] = 'Tipo';
$labels['customise-logo-type-all'] = 'Tutti i tipi';
$labels['customise-logo-type-favicon'] = 'Favicon';
$labels['customise-logo-type-print'] = 'Print';
$labels['customise-logo-type-small'] = 'Small';
$labels['customise-logo-type-dark'] = 'Logo in modalità scrura';
$labels['customise-logo-type-small-dark'] = 'Small in modalità scura';
$labels['customise-logo-add-template'] = 'Aggiungi';
$labels['customise-logo-image'] = 'Immagine';

$messages['customise-logo-customisedlogoexists'] = 'Logo personalizzato già esistemte';
$messages['customise-logo-deletecustomisedlogo'] = 'Vuoi veramente cancellare questo logo personalizzato?';
$messages['customise-logo-deleteallcustomisedlogos'] = 'Vuoi veramente cancellare tutti i loghi personalizzati di questo tema?';
$messages['customise-logo-emptycustomisedlogotemplate'] = 'Il modello del logo non può essere vuoto';
$messages['customise-logo-emptycustomisedlogoimage'] = 'L\'immagine del logo non può essere vuota';
$messages['customise-logo-invalidcustomisedlogoimage'] = 'L\'immagine del logo non è valida';
$messages['customise-logo-nocustomisedlogo'] = 'Nessun logo personalizzato trovato';


/* Detach Attachments */
/* Author: Gianluca Giacometti */

$labels['attachment-expiry-date'] = 'Scadenza download';
$labels['attachment-maxuploadsize'] = 'NOTA: Se un singolo allegato supera $singlesize MB o l\'insieme degli allegati supera $totalsize MB verrà inviato al destinatario un collegamento ad un file .zip salvato localmente nel server valido per $lifespan giorno(i)';

/* Tags */
/* Author: Gianluca Giacometti */

$labels['tags'] = 'Tags';
$labels['tags-label'] = 'Etichetta';
$labels['tags-labels'] = 'Etichette';


/* Cloud */
/* Author: Gianluca Giacometti */

$labels['cloud'] = 'Cloud';
$labels['cloud-googledrive'] = 'Google Drive';
$labels['cloud-dropbox'] = 'Dropbox';
$labels['cloud-onedrive'] = 'Microsoft OneDrive';
$labels['cloud-insertlink'] = 'Inserisci un collegamento al file';
$labels['cloud-insertfile'] = 'Allega un file';


/* Safe login */
/* Author: Gianluca Giacometti */

$labels['safelogin'] = 'Accesso sicuro';
$labels['safelogin-continent'] = 'Continente';
$labels['safelogin-countryisocode'] = 'Codice Nazione';
$labels['safelogin-countryname'] = 'Nazione';
$labels['safelogin-cityname'] = 'Città';
$labels['safelogin-location'] = 'Indirizzo';
$labels['safelogin-lastlogin'] = 'Ultimo accesso';
$labels['safelogin-history'] = 'Storico Accessi';
$labels['safelogin-browser'] = 'Programma di navigazione';
$labels['safelogin-os'] = 'Sistema operativo';


/* Folders */
/* Author: Gianluca Giacometti */

$labels['folders'] = 'Riepilogo cartelle';
$labels['folders-numberoffiles'] = 'Numero file';
