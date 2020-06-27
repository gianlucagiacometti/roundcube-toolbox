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
